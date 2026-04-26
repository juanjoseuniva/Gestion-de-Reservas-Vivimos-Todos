<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $db = getDB();
    // Check if used in reservas
    $used = $db->prepare("SELECT COUNT(*) FROM detalle_reserva WHERE id_insumo=?");
    $used->execute([$id]);
    if ($used->fetchColumn() > 0) {
        flashMessage('error','No se puede eliminar: el insumo está en uso en reservas existentes.');
    } else {
        $db->prepare("DELETE FROM inventario WHERE id_insumo=?")->execute([$id]);
        flashMessage('success','Insumo eliminado correctamente.');
    }
}
redirect('/vivimostodos/admin/inventario/index.php');
