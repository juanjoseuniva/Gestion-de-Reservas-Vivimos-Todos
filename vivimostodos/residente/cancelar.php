<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['RESIDENTE']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id  = (int)($_POST['id'] ?? 0);
    $uid = currentUserId();
    $db  = getDB();

    $stmt = $db->prepare("SELECT * FROM reservas WHERE id_reserva=? AND id_usuario=?");
    $stmt->execute([$id, $uid]);
    $reserva = $stmt->fetch();

    if ($reserva && in_array($reserva['estado'], ['PENDIENTE','APROBADA']) && $reserva['fecha_evento'] >= date('Y-m-d')) {
        $db->prepare("UPDATE reservas SET estado='CANCELADA' WHERE id_reserva=?")->execute([$id]);
        // Liberar inventario
        $detalles = $db->prepare("SELECT id_insumo, cantidad FROM detalle_reserva WHERE id_reserva=?");
        $detalles->execute([$id]);
        foreach ($detalles->fetchAll() as $d) {
            $db->prepare("UPDATE inventario SET cantidad_disponible=cantidad_disponible+? WHERE id_insumo=?")->execute([$d['cantidad'],$d['id_insumo']]);
        }
        // Notificar admin
        $admins = $db->query("SELECT id_usuario FROM usuarios WHERE rol='ADMIN' AND estado='ACTIVO'")->fetchAll();
        $user = currentUser();
        foreach ($admins as $adm) {
            crearNotificacion($adm['id_usuario'], "🚫 El residente {$user['nombre']} {$user['apellido']} canceló la reserva #{$id} del " . formatFecha($reserva['fecha_evento']));
        }
        flashMessage('success',"Reserva #{$id} cancelada correctamente.");
    } else {
        flashMessage('error','No se puede cancelar esta reserva.');
    }
}
redirect('/vivimostodos/residente/mis_reservas.php');
