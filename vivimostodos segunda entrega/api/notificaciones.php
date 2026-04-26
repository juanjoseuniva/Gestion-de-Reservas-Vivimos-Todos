<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/database.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['count' => 0, 'notificaciones' => []]);
    exit;
}

$uid = currentUserId();
$db  = getDB();

// Obtener últimas 15 notificaciones (leídas y no leídas)
$stmt = $db->prepare(
    "SELECT id_notificacion, mensaje, leido,
            DATE_FORMAT(fecha, '%d/%m/%Y %H:%i') as fecha
     FROM notificaciones
     WHERE id_usuario = ?
     ORDER BY fecha DESC
     LIMIT 15"
);
$stmt->execute([$uid]);
$notificaciones = $stmt->fetchAll();

// Contar no leídas
$stmtCount = $db->prepare("SELECT COUNT(*) FROM notificaciones WHERE id_usuario=? AND leido=0");
$stmtCount->execute([$uid]);
$count = (int)$stmtCount->fetchColumn();

echo json_encode([
    'count'          => $count,
    'notificaciones' => $notificaciones,
]);
