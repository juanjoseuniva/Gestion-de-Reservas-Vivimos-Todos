<?php
/**
 * admin/reservas/rechazar.php — PUNTO 6
 * Rechaza reserva y guarda motivo. NO descuenta inventario.
 */
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/session.php';
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/vivimostodos/admin/reservas/index.php');
}

$id     = (int)($_POST['id']     ?? 0);
$motivo = trim($_POST['motivo']  ?? '');
$db     = getDB();

if (!$motivo || strlen($motivo) < 10) {
    flashMessage('error', '❌ Debes ingresar un motivo de rechazo (mínimo 10 caracteres).');
    redirect('/vivimostodos/admin/reservas/index.php');
}

$stmt = $db->prepare(
    "SELECT r.*, u.id_usuario, u.nombre, u.apellido
     FROM reservas r JOIN usuarios u ON r.id_usuario = u.id_usuario
     WHERE r.id_reserva = ?"
);
$stmt->execute([$id]);
$reserva = $stmt->fetch();

if (!$reserva) {
    flashMessage('error', '❌ Reserva no encontrada.');
    redirect('/vivimostodos/admin/reservas/index.php');
}

if ($reserva['estado'] !== 'PENDIENTE') {
    flashMessage('error', "❌ La reserva #{$id} no está en estado PENDIENTE.");
    redirect('/vivimostodos/admin/reservas/index.php');
}

// Guardar motivo en observaciones y cambiar estado
// NO se descuenta inventario al rechazar
$observacion = "RECHAZADA — Motivo: $motivo";
$db->prepare(
    "UPDATE reservas SET estado='RECHAZADA', observaciones=? WHERE id_reserva=?"
)->execute([$observacion, $id]);

// Notificar al residente con el motivo
$fechaFormato = formatFecha($reserva['fecha_evento']);
crearNotificacion(
    $reserva['id_usuario'],
    "❌ Tu reserva #{$id} para el {$fechaFormato} fue RECHAZADA. Motivo: $motivo"
);

flashMessage('success', "✅ Reserva <strong>#{$id}</strong> rechazada. Notificación enviada a {$reserva['nombre']} con el motivo.");
redirect('/vivimostodos/admin/reservas/index.php');
