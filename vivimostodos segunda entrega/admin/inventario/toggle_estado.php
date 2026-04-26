<?php
/**
 * admin/inventario/toggle_estado.php — PUNTO 4
 * Marcar insumo como DISPONIBLE / NO DISPONIBLE
 */
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/session.php';
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $db = getDB();

    $stmt = $db->prepare("SELECT estado, nombre FROM inventario WHERE id_insumo=?");
    $stmt->execute([$id]);
    $insumo = $stmt->fetch();

    if ($insumo) {
        $nuevo = $insumo['estado'] === 'DISPONIBLE' ? 'NO DISPONIBLE' : 'DISPONIBLE';
        $db->prepare("UPDATE inventario SET estado=? WHERE id_insumo=?")->execute([$nuevo, $id]);
        flashMessage('success', "✅ Insumo <strong>{$insumo['nombre']}</strong> marcado como <strong>$nuevo</strong>.");
    } else {
        flashMessage('error', 'Insumo no encontrado.');
    }
}
redirect('/vivimostodos/admin/inventario/index.php');
