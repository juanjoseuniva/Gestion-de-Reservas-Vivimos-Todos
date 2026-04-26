<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

$db     = getDB();
$inicio = $_GET['inicio'] ?? date('Y-m-01');
$fin    = $_GET['fin']    ?? date('Y-m-d');

$reservas = $db->prepare(
    "SELECT r.id_reserva, r.fecha_evento, r.hora_inicio, r.hora_fin, r.estado, r.fecha_solicitud, r.observaciones,
            u.nombre, u.apellido, u.cedula, u.correo, u.telefono,
            COALESCE(SUM(p.monto),0) as total_pagado, p.estado as estado_pago
     FROM reservas r
     JOIN usuarios u ON r.id_usuario=u.id_usuario
     LEFT JOIN pagos p ON p.id_reserva=r.id_reserva AND p.estado='VALIDADO'
     WHERE r.fecha_evento BETWEEN ? AND ?
     GROUP BY r.id_reserva
     ORDER BY r.fecha_evento ASC"
);
$reservas->execute([$inicio, $fin]);
$data = $reservas->fetchAll();

// Try PhpSpreadsheet if available
$xlsxPath = __DIR__ . '/../../../../vendor/autoload.php';

if (file_exists($xlsxPath)) {
    require_once $xlsxPath;
    // PhpSpreadsheet export
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Reservas');
    $headers = ['#','Residente','Cédula','Correo','Teléfono','Fecha Evento','Hora Inicio','Hora Fin','Estado','Solicitud','Total Pagado','Observaciones'];
    foreach (range('A','L') as $i => $col) {
        $sheet->setCellValue($col . '1', $headers[$i]);
    }
    $row = 2;
    foreach ($data as $r) {
        $sheet->fromArray([
            $r['id_reserva'], $r['nombre'].' '.$r['apellido'], $r['cedula'], $r['correo'], $r['telefono'],
            $r['fecha_evento'], $r['hora_inicio'], $r['hora_fin'], $r['estado'],
            $r['fecha_solicitud'], $r['total_pagado'], $r['observaciones']
        ], null, 'A'.$row);
        $row++;
    }
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $tmpFile = tempnam(sys_get_temp_dir(), 'report_') . '.xlsx';
    $writer->save($tmpFile);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="reporte_reservas_' . date('Ymd') . '.xlsx"');
    readfile($tmpFile);
    unlink($tmpFile);
} else {
    // Fallback: CSV
    $filename = 'reporte_reservas_' . date('Ymd') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
    $out = fopen('php://output', 'w');
    fputcsv($out, ['#','Residente','Cedula','Correo','Telefono','Fecha Evento','Hora Inicio','Hora Fin','Estado','Fecha Solicitud','Total Pagado','Observaciones'], ';');
    foreach ($data as $r) {
        fputcsv($out, [
            $r['id_reserva'], $r['nombre'].' '.$r['apellido'], $r['cedula'], $r['correo'], $r['telefono'],
            $r['fecha_evento'], $r['hora_inicio'], $r['hora_fin'], $r['estado'],
            $r['fecha_solicitud'], $r['total_pagado'], $r['observaciones']
        ], ';');
    }
    fclose($out);
}

// Register in reportes table
$db->prepare("INSERT INTO reportes (tipo, archivo) VALUES (?,?)")->execute(['Excel/CSV', $filename ?? 'xlsx']);
