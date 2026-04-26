<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? '');
    $db = getDB();
    $stmt = $db->prepare("SELECT r.*, u.id_usuario FROM reservas r JOIN usuarios u ON r.id_usuario=u.id_usuario WHERE r.id_reserva=?");
    $stmt->execute([$id]);
    $reserva = $stmt->fetch();
    if ($reserva && $reserva['estado'] === 'PENDIENTE') {
        $obs = $motivo ? "Rechazado: $motivo" : 'Rechazado por administrador.';
        $db->prepare("UPDATE reservas SET estado='RECHAZADA', observaciones=? WHERE id_reserva=?")->execute([$obs,$id]);
        // Liberar inventario si tenía detalles
        $detalles = $db->prepare("SELECT id_insumo, cantidad FROM detalle_reserva WHERE id_reserva=?");
        $detalles->execute([$id]);
        foreach ($detalles->fetchAll() as $d) {
            $db->prepare("UPDATE inventario SET cantidad_disponible=cantidad_disponible+? WHERE id_insumo=?")->execute([$d['cantidad'],$d['id_insumo']]);
        }
        crearNotificacion($reserva['id_usuario'], "❌ Tu reserva #$id para el " . formatFecha($reserva['fecha_evento']) . " fue RECHAZADA. Motivo: $obs");
        flashMessage('success',"Reserva #{$id} rechazada.");
    } else {
        flashMessage('error','No se puede rechazar esta reserva.');
    }
}
redirect('/vivimostodos/admin/reservas/index.php');
