<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $db = getDB();
    $stmt = $db->prepare("SELECT r.*, u.id_usuario, u.nombre, u.apellido FROM reservas r JOIN usuarios u ON r.id_usuario=u.id_usuario WHERE r.id_reserva=?");
    $stmt->execute([$id]);
    $reserva = $stmt->fetch();
    if ($reserva && $reserva['estado'] === 'PENDIENTE') {
        $db->prepare("UPDATE reservas SET estado='APROBADA' WHERE id_reserva=?")->execute([$id]);
        crearNotificacion($reserva['id_usuario'],
            "✅ Tu reserva #$id para el " . formatFecha($reserva['fecha_evento']) . " ha sido APROBADA.");
        flashMessage('success',"Reserva #{$id} aprobada. Notificación enviada al residente.");
    } else {
        flashMessage('error','No se puede aprobar esta reserva.');
    }
}
redirect('/vivimostodos/admin/reservas/index.php');
