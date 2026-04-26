<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

$db = getDB();
$inicio = $_GET['inicio'] ?? date('Y-m-01');
$fin    = $_GET['fin']    ?? date('Y-m-d');

// Try Dompdf if available
$dompdfPath = __DIR__ . '/../../../../vendor/autoload.php';
$useDompdf  = file_exists($dompdfPath);

$reservas = $db->prepare(
    "SELECT r.id_reserva, r.fecha_evento, r.hora_inicio, r.hora_fin, r.estado, r.observaciones,
            u.nombre, u.apellido, u.cedula,
            COALESCE(SUM(p.monto),0) as total_pagado
     FROM reservas r
     JOIN usuarios u ON r.id_usuario=u.id_usuario
     LEFT JOIN pagos p ON p.id_reserva=r.id_reserva AND p.estado='VALIDADO'
     WHERE r.fecha_evento BETWEEN ? AND ?
     GROUP BY r.id_reserva
     ORDER BY r.fecha_evento ASC"
);
$reservas->execute([$inicio, $fin]);
$data = $reservas->fetchAll();

$totalIngresos = array_sum(array_column($data, 'total_pagado'));

// Registrar en tabla reportes
$nombreArchivo = 'reporte_' . date('Ymd_His') . '.html';
$db->prepare("INSERT INTO reportes (tipo, archivo) VALUES (?,?)")->execute(['PDF/HTML', $nombreArchivo]);

$html = '<!DOCTYPE html><html lang="es"><head>
<meta charset="UTF-8">
<title>Reporte Reservas ' . sanitize($inicio) . ' al ' . sanitize($fin) . '</title>
<style>
  body { font-family: Arial, sans-serif; font-size: 12px; color: #1e293b; margin: 20px; }
  h1 { color: #1a3c5e; margin-bottom: 4px; }
  .subtitle { color: #64748b; font-size: 11px; margin-bottom: 20px; }
  table { width: 100%; border-collapse: collapse; margin-top: 16px; }
  th { background: #1a3c5e; color: #fff; padding: 8px; text-align: left; font-size: 11px; }
  td { padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
  tr:nth-child(even) { background: #f8fafc; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
  .APROBADA { background: #dcfce7; color: #15803d; }
  .PENDIENTE { background: #fef9c3; color: #92400e; }
  .RECHAZADA { background: #fee2e2; color: #b91c1c; }
  .CANCELADA { background: #f1f5f9; color: #475569; }
  .totals { margin-top: 16px; text-align: right; font-weight: bold; font-size: 13px; }
  .header-info { display: flex; justify-content: space-between; align-items: start; }
  @media print { button { display: none; } }
</style>
</head><body>
<div class="header-info">
  <div>
    <h1>🏢 Unidad Residencial Vivimostodos</h1>
    <div class="subtitle">Reporte de Reservas — Período: ' . formatFecha($inicio) . ' al ' . formatFecha($fin) . '</div>
    <div class="subtitle">Generado: ' . date('d/m/Y H:i') . '</div>
  </div>
  <button onclick="window.print()" style="padding:8px 16px;background:#1a3c5e;color:#fff;border:none;border-radius:6px;cursor:pointer">🖨 Imprimir / PDF</button>
</div>
<table>
  <thead>
    <tr><th>#</th><th>Residente</th><th>Cédula</th><th>Fecha Evento</th><th>Horario</th><th>Estado</th><th>Total Pagado</th></tr>
  </thead>
  <tbody>';

foreach ($data as $r) {
    $html .= '<tr>
      <td>#' . $r['id_reserva'] . '</td>
      <td>' . sanitize($r['nombre']) . ' ' . sanitize($r['apellido']) . '</td>
      <td>' . sanitize($r['cedula']) . '</td>
      <td>' . formatFecha($r['fecha_evento']) . '</td>
      <td>' . substr($r['hora_inicio'],0,5) . ' – ' . substr($r['hora_fin'],0,5) . '</td>
      <td><span class="badge ' . $r['estado'] . '">' . $r['estado'] . '</span></td>
      <td>' . formatMoneda((float)$r['total_pagado']) . '</td>
    </tr>';
}

if (empty($data)) {
    $html .= '<tr><td colspan="7" style="text-align:center;padding:20px;color:#64748b">No hay reservas en este período</td></tr>';
}

$html .= '</tbody></table>
<div class="totals">Total registros: ' . count($data) . ' | Ingresos validados: ' . formatMoneda($totalIngresos) . '</div>
</body></html>';

// Save file
$dir = __DIR__ . '/../../assets/uploads/reportes/';
if (!is_dir($dir)) mkdir($dir, 0755, true);
file_put_contents($dir . $nombreArchivo, $html);

header('Content-Type: text/html; charset=UTF-8');
echo $html;
