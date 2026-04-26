<?php
/**
 * admin/reservas/aprobar.php — PUNTO 6
 * Aprueba reserva y descuenta inventario
 */
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/session.php';
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/vivimostodos/admin/reservas/index.php');
}

$id = (int)($_POST['id'] ?? 0);
$db = getDB();

// Obtener reserva con datos del residente
$stmt = $db->prepare(
    "SELECT r.*, u.id_usuario, u.nombre, u.apellido, u.correo
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
    flashMessage('error', "❌ La reserva #{$id} no está en estado PENDIENTE (estado actual: {$reserva['estado']}).");
    redirect('/vivimostodos/admin/reservas/index.php');
}

// Verificar que siga siendo la única reserva en esa fecha
$stmt2 = $db->prepare(
    "SELECT COUNT(*) FROM reservas
     WHERE fecha_evento = ? AND estado = 'APROBADA' AND id_reserva != ?"
);
$stmt2->execute([$reserva['fecha_evento'], $id]);
if ($stmt2->fetchColumn() > 0) {
    flashMessage('error', '❌ Ya existe una reserva APROBADA para esa fecha. No se puede aprobar.');
    redirect('/vivimostodos/admin/reservas/index.php');
}

// Verificar stock disponible para los insumos
$detalles = $db->prepare(
    "SELECT dr.id_insumo, dr.cantidad, i.nombre, i.cantidad_disponible
     FROM detalle_reserva dr
     JOIN inventario i ON i.id_insumo = dr.id_insumo
     WHERE dr.id_reserva = ?"
);
$detalles->execute([$id]);
$items = $detalles->fetchAll();

$erroresStock = [];
foreach ($items as $item) {
    if ($item['cantidad'] > $item['cantidad_disponible']) {
        $erroresStock[] = "'{$item['nombre']}': se solicitan {$item['cantidad']} pero solo hay {$item['cantidad_disponible']} disponibles.";
    }
}

if (!empty($erroresStock)) {
    flashMessage('error', '❌ Stock insuficiente para aprobar:<br>' . implode('<br>', $erroresStock));
    redirect('/vivimostodos/admin/reservas/index.php');
}

// ── TRANSACCIÓN: Aprobar + Descontar inventario ────
$db->beginTransaction();
try {
    // 1. Cambiar estado a APROBADA
    $db->prepare("UPDATE reservas SET estado='APROBADA' WHERE id_reserva=?")->execute([$id]);

    // 2. Descontar inventario (solo al aprobar, no al crear)
    foreach ($items as $item) {
        $db->prepare(
            "UPDATE inventario SET cantidad_disponible = cantidad_disponible - ?
             WHERE id_insumo = ?"
        )->execute([$item['cantidad'], $item['id_insumo']]);
    }

    // 3. Notificar al residente
    $fechaFormato = formatFecha($reserva['fecha_evento']);
    crearNotificacion(
        $reserva['id_usuario'],
        "✅ Tu reserva #{$id} para el {$fechaFormato} ha sido APROBADA. ¡Disfruta tu evento!"
    );

    $db->commit();
    flashMessage('success', "✅ Reserva <strong>#{$id}</strong> aprobada correctamente. Se descontaron " . count($items) . " insumo(s) del inventario. Notificación enviada a {$reserva['nombre']}.");

} catch (Exception $e) {
    $db->rollBack();
    flashMessage('error', '❌ Error al aprobar la reserva: ' . $e->getMessage());
}

redirect('/vivimostodos/admin/reservas/index.php');
