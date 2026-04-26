<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['id'] ?? 0);
    $accion = $_POST['accion'] ?? '';
    $db     = getDB();

    $stmt = $db->prepare("SELECT p.*, r.id_usuario, r.fecha_evento FROM pagos p JOIN reservas r ON p.id_reserva=r.id_reserva WHERE p.id_pago=?");
    $stmt->execute([$id]);
    $pago = $stmt->fetch();

    if ($pago && $pago['estado'] === 'PENDIENTE') {
        if ($accion === 'validar') {
            $db->prepare("UPDATE pagos SET estado='VALIDADO' WHERE id_pago=?")->execute([$id]);
            crearNotificacion($pago['id_usuario'], "💰 Tu pago #$id para la reserva #" . $pago['id_reserva'] . " ha sido VALIDADO. ¡Todo listo!");
            flashMessage('success',"Pago #{$id} validado correctamente.");
        } elseif ($accion === 'rechazar') {
            $db->prepare("UPDATE pagos SET estado='RECHAZADO' WHERE id_pago=?")->execute([$id]);
            crearNotificacion($pago['id_usuario'], "⚠️ Tu pago #$id fue RECHAZADO. Por favor sube un nuevo comprobante.");
            flashMessage('error',"Pago #{$id} rechazado.");
        }
    } else {
        flashMessage('error','Pago no encontrado o ya procesado.');
    }
}
redirect('/vivimostodos/admin/pagos/index.php');
