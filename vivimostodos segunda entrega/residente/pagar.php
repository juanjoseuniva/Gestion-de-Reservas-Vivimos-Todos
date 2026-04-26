<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['RESIDENTE']);

$db  = getDB();
$uid = currentUserId();
$id  = (int)($_GET['id'] ?? 0);
$pageTitle = 'Subir Comprobante de Pago';
$errors = [];

$stmt = $db->prepare(
    "SELECT r.*, COALESCE(SUM(dr.subtotal),0) as total
     FROM reservas r
     LEFT JOIN detalle_reserva dr ON dr.id_reserva=r.id_reserva
     WHERE r.id_reserva=? AND r.id_usuario=? AND r.estado='APROBADA'
     GROUP BY r.id_reserva"
);
$stmt->execute([$id, $uid]);
$reserva = $stmt->fetch();
if (!$reserva) { flashMessage('error','Reserva no encontrada o no elegible para pago.'); redirect('/vivimostodos/residente/mis_reservas.php'); }

// Verificar pago existente no rechazado
$pagoPrev = $db->prepare("SELECT * FROM pagos WHERE id_reserva=? AND estado!='RECHAZADO' ORDER BY fecha_pago DESC LIMIT 1");
$pagoPrev->execute([$id]);
$pagoPrev = $pagoPrev->fetch();
if ($pagoPrev && $pagoPrev['estado'] !== 'RECHAZADO') {
    flashMessage('error','Esta reserva ya tiene un pago registrado en estado: ' . $pagoPrev['estado']);
    redirect('/vivimostodos/residente/mis_reservas.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monto = (float)($_POST['monto'] ?? 0);
    if ($monto <= 0) $errors[] = 'El monto debe ser mayor a 0.';
    if (empty($_FILES['comprobante']['name'])) $errors[] = 'Debes subir el comprobante de pago.';

    if (empty($errors)) {
        $archivo = uploadComprobante($_FILES['comprobante'], $id);
        if (!$archivo) {
            $errors[] = 'Error al subir el archivo. Formatos permitidos: JPG, PNG, PDF. Máximo 5MB.';
        } else {
            $db->prepare("INSERT INTO pagos (id_reserva,comprobante,monto) VALUES (?,?,?)")->execute([$id,$archivo,$monto]);
            // Notificar admin
            $admins = $db->query("SELECT id_usuario FROM usuarios WHERE rol='ADMIN' AND estado='ACTIVO'")->fetchAll();
            $user = currentUser();
            foreach ($admins as $adm) {
                crearNotificacion($adm['id_usuario'], "💳 {$user['nombre']} {$user['apellido']} subió comprobante de pago para reserva #{$id}. Monto: " . formatMoneda($monto));
            }
            crearNotificacion($uid, "📎 Comprobante de pago para reserva #{$id} enviado. El administrador lo revisará pronto.");
            flashMessage('success','Comprobante subido correctamente. El administrador lo revisará a la brevedad.');
            redirect('/vivimostodos/residente/mis_reservas.php');
        }
    }
}

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <?= renderFlash() ?>
        <div class="card animate-in">
            <div class="card-header">
                <h5>💳 Subir Comprobante de Pago</h5>
                <a href="mis_reservas.php" class="btn btn-sm btn-outline-secondary">← Volver</a>
            </div>
            <div class="card-body">
                <!-- Info reserva -->
                <div class="p-3 rounded mb-4" style="background:var(--bg)">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div style="font-size:.75rem;color:var(--text-muted)">Reserva</div>
                            <strong>#<?= $id ?> — <?= formatFecha($reserva['fecha_evento']) ?></strong>
                        </div>
                        <div class="text-end">
                            <div style="font-size:.75rem;color:var(--text-muted)">Total a pagar</div>
                            <strong class="text-primary fs-5"><?= formatMoneda((float)$reserva['total']) ?></strong>
                        </div>
                    </div>
                </div>

                <?php if ($errors): ?>
                <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Monto pagado (COP) *</label>
                        <input type="number" name="monto" class="form-control" min="1" step="100"
                            value="<?= $reserva['total'] > 0 ? $reserva['total'] : '' ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Comprobante *</label>
                        <input type="file" name="comprobante" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                        <small class="text-muted">Formatos aceptados: JPG, PNG, PDF. Máximo 5MB.</small>
                    </div>
                    <div class="alert alert-info" style="font-size:.82rem">
                        📱 Puedes transferir a la cuenta bancaria de la unidad y subir el soporte aquí.
                        El administrador validará el pago en máximo 24 horas hábiles.
                    </div>
                    <button type="submit" class="btn btn-primary w-100">📤 Subir Comprobante</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
