<?php
/**
 * admin/inventario/eliminar.php — PUNTO 4
 */
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/session.php';
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $db = getDB();

    // Verificar que no esté en reservas activas
    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM detalle_reserva dr
         JOIN reservas r ON r.id_reserva = dr.id_reserva
         WHERE dr.id_insumo = ? AND r.estado IN ('PENDIENTE','APROBADA')"
    );
    $stmt->execute([$id]);

    if ($stmt->fetchColumn() > 0) {
        flashMessage('error', '❌ No se puede eliminar: el insumo está comprometido en reservas activas. Primero cancela o rechaza esas reservas.');
    } else {
        // Verificar si tiene historial en detalle_reserva
        $hist = $db->prepare("SELECT COUNT(*) FROM detalle_reserva WHERE id_insumo=?");
        $hist->execute([$id]);
        if ($hist->fetchColumn() > 0) {
            // Tiene historial: marcar como NO DISPONIBLE en lugar de eliminar
            $db->prepare("UPDATE inventario SET estado='NO DISPONIBLE' WHERE id_insumo=?")->execute([$id]);
            flashMessage('warning', '⚠️ El insumo tiene historial de uso, fue marcado como <strong>NO DISPONIBLE</strong> en lugar de eliminado para conservar el historial.');
        } else {
            $db->prepare("DELETE FROM inventario WHERE id_insumo=?")->execute([$id]);
            flashMessage('success', '✅ Insumo eliminado correctamente.');
        }
    }
}
redirect('/vivimostodos/admin/inventario/index.php');
