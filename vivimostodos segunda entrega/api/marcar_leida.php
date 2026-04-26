<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/database.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id    = (int)($input['id'] ?? 0);
$uid   = currentUserId();

if (!$id) {
    echo json_encode(['ok' => false, 'error' => 'ID inválido']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("UPDATE notificaciones SET leido=1 WHERE id_notificacion=? AND id_usuario=?");
$stmt->execute([$id, $uid]);

echo json_encode(['ok' => true, 'affected' => $stmt->rowCount()]);
