<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/database.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !in_array(currentRole(), ['ADMIN'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$db = getDB();

// ── 1. Reservas por estado ────────────────────────
$estados = $db->query(
    "SELECT estado, COUNT(*) as total FROM reservas GROUP BY estado"
)->fetchAll();

$estadosData = ['labels' => [], 'values' => []];
$estadoMap = ['PENDIENTE' => 0, 'APROBADA' => 0, 'RECHAZADA' => 0, 'CANCELADA' => 0];
foreach ($estados as $e) {
    $estadoMap[$e['estado']] = (int)$e['total'];
}
foreach ($estadoMap as $label => $val) {
    $estadosData['labels'][] = $label;
    $estadosData['values'][] = $val;
}

// ── 2. Reservas por mes (últimos 6 meses) ─────────
$meses = $db->query(
    "SELECT DATE_FORMAT(fecha_evento,'%Y-%m') as mes,
            DATE_FORMAT(fecha_evento,'%b %Y') as mes_label,
            COUNT(*) as total
     FROM reservas
     WHERE fecha_evento >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(fecha_evento,'%Y-%m')
     ORDER BY mes ASC"
)->fetchAll();

$mesesData = ['labels' => [], 'values' => []];
foreach ($meses as $m) {
    $mesesData['labels'][] = $m['mes_label'];
    $mesesData['values'][] = (int)$m['total'];
}

// ── 3. Ingresos por mes ───────────────────────────
$ingresos = $db->query(
    "SELECT DATE_FORMAT(fecha_pago,'%b %Y') as mes_label,
            DATE_FORMAT(fecha_pago,'%Y-%m') as mes,
            SUM(monto) as total
     FROM pagos
     WHERE estado='VALIDADO' AND fecha_pago >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(fecha_pago,'%Y-%m')
     ORDER BY mes ASC"
)->fetchAll();

$ingresosData = ['labels' => [], 'values' => []];
foreach ($ingresos as $i) {
    $ingresosData['labels'][] = $i['mes_label'];
    $ingresosData['values'][] = (float)$i['total'];
}

// ── 4. Insumos más solicitados ────────────────────
$insumos = $db->query(
    "SELECT i.nombre, SUM(dr.cantidad) as total_usado
     FROM detalle_reserva dr
     JOIN inventario i ON dr.id_insumo = i.id_insumo
     GROUP BY i.id_insumo
     ORDER BY total_usado DESC
     LIMIT 5"
)->fetchAll();

$insumosData = ['labels' => [], 'values' => []];
foreach ($insumos as $ins) {
    $insumosData['labels'][] = $ins['nombre'];
    $insumosData['values'][] = (int)$ins['total_usado'];
}

echo json_encode([
    'estados'  => $estadosData,
    'meses'    => $mesesData,
    'ingresos' => $ingresosData,
    'insumos'  => $insumosData,
]);
