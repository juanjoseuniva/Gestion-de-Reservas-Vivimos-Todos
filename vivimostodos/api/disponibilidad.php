<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/database.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$fecha = trim($_GET['fecha'] ?? '');

if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    echo json_encode(['disponible' => false, 'error' => 'Fecha inválida']);
    exit;
}

if ($fecha < date('Y-m-d')) {
    echo json_encode(['disponible' => false, 'mensaje' => 'Fecha en el pasado']);
    exit;
}

$db = getDB();
$stmt = $db->prepare(
    "SELECT id_reserva, estado FROM reservas
     WHERE fecha_evento = ? AND estado IN ('PENDIENTE','APROBADA')
     LIMIT 1"
);
$stmt->execute([$fecha]);
$reserva = $stmt->fetch();

if ($reserva) {
    echo json_encode([
        'disponible' => false,
        'mensaje'    => 'Fecha ocupada',
        'estado'     => $reserva['estado'],
        'id_reserva' => $reserva['id_reserva'],
    ]);
} else {
    echo json_encode([
        'disponible' => true,
        'mensaje'    => 'Fecha disponible',
    ]);
}
