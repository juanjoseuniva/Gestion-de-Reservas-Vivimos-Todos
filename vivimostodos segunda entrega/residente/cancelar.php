<?php
/**
 * residente/cancelar.php — PUNTO 5
 * Cancela reserva y restaura inventario si estaba APROBADA
 */
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/session.php';
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/functions.php';
requireRole(['RESIDENTE']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/vivimostodos/residente/mis_reservas.php');
}

$id  = (int)($_POST['id'] ?? 0);
$uid = currentUserId();
$db  = getDB();

$stmt = $db->prepare(
    "SELECT * FROM reservas WHERE id_reserva=? AND id_usuario=?"
);
$stmt->execute([$id, $uid]);
$reserva = $stmt->fetch();

if (!$reserva) {
    flashMessage('error', '❌ Reserva no encontrada o no te pertenece.');
    redirect('/vivimostodos/residente/mis_reservas.php');
}

if (!in_array($reserva['estado'], ['PENDIENTE', 'APROBADA'])) {
    flashMessage('error', "❌ No puedes cancelar una reserva en estado {$reserva['estado']}.");
    redirect('/vivimostodos/residente/mis_reservas.php');
}

if ($reserva['fecha_evento'] < date('Y-m-d')) {
    flashMessage('error', '❌ No puedes cancelar una reserva cuya fecha ya pasó.');
    redirect('/vivimostodos/residente/mis_reservas.php');
}

$db->beginTransaction();
try {
    // Cambiar estado
    $db->prepare("UPDATE reservas SET estado='CANCELADA' WHERE id_reserva=?")->execute([$id]);

    // Si estaba APROBADA, restaurar inventario
    if ($reserva['estado'] === 'APROBADA') {
        $detalles = $db->prepare(
            "SELECT id_insumo, cantidad FROM detalle_reserva WHERE id_reserva=?"
        );
        $detalles->execute([$id]);
        foreach ($detalles->fetchAll() as $det) {
            $db->prepare(
                "UPDATE inventario SET cantidad_disponible = cantidad_disponible + ?
                 WHERE id_insumo = ?"
            )->execute([$det['cantidad'], $det['id_insumo']]);
        }
    }

    // Notificar al admin
    $user = currentUser();
    notificarAdmins(
        "🚫 {$user['nombre']} {$user['apellido']} canceló la reserva #{$id} del " .
        formatFecha($reserva['fecha_evento']) .
        ($reserva['estado'] === 'APROBADA' ? " (inventario restaurado)" : "")
    );

    $db->commit();

    $msg = "✅ Reserva #{$id} cancelada correctamente.";
    if ($reserva['estado'] === 'APROBADA') {
        $msg .= " El inventario reservado fue liberado.";
    }
    flashMessage('success', $msg);

} catch (Exception $e) {
    $db->rollBack();
    flashMessage('error', '❌ Error al cancelar: ' . $e->getMessage());
}

redirect('/vivimostodos/residente/mis_reservas.php');
