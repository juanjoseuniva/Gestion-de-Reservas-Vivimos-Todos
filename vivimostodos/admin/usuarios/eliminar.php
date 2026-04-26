<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $db = getDB();
    $stmt = $db->prepare("SELECT estado FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if ($u) {
        $nuevo = $u['estado'] === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
        $db->prepare("UPDATE usuarios SET estado=? WHERE id_usuario=?")->execute([$nuevo, $id]);
        flashMessage('success', "Estado del usuario actualizado a {$nuevo}.");
    } else {
        flashMessage('error', 'Usuario no encontrado.');
    }
}
redirect('/vivimostodos/admin/usuarios/index.php');
