<?php
/**
 * admin/reservas/detalle.php — PUNTO 6
 */
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/session.php';
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

$db = getDB();
$pageTitle = 'Detalle de Reserva';
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare(
    "SELECT r.*, u.nombre, u.apellido, u.correo, u.telefono, u.cedula
     FROM reservas r JOIN usuarios u ON r.id_usuario = u.id_usuario
     WHERE r.id_reserva = ?"
);
$stmt->execute([$id]);
$reserva = $stmt->fetch();
if (!$reserva) {
    flashMessage('error', 'Reserva no encontrada.');
    redirect('/vivimostodos/admin/reservas/index.php');
}

$detalles = $db->prepare(
    "SELECT dr.*, i.nombre as nombre_insumo, i.precio_unitario, i.cantidad_disponible
     FROM detalle_reserva dr JOIN inventario i ON dr.id_insumo = i.id_insumo
     WHERE dr.id_reserva = ?"
);
$detalles->execute([$id]);
$items = $detalles->fetchAll();

$pago = $db->prepare("SELECT * FROM pagos WHERE id_reserva=? ORDER BY fecha_pago DESC LIMIT 1");
$pago->execute([$id]);
$pago = $pago->fetch();

$semaforo = colorSemaforo($reserva['fecha_evento']);
$sClass   = badgeEstado($reserva['estado']);
$total    = array_sum(array_column($items, 'subtotal'));

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<?= renderFlash() ?>

<div class="row g-3">
    <!-- Detalle principal -->
    <div class="col-lg-8">
        <div class="card animate-in">
            <div class="card-header">
                <div>
                    <h5 class="mb-0">📋 Reserva #<?= $id ?></h5>
                    <small style="color:var(--text-muted)">
                        Solicitada: <?= date('d/m/Y H:i', strtotime($reserva['fecha_solicitud'])) ?>
                    </small>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-<?= $sClass ?> fs-6"><?= $reserva['estado'] ?></span>
                    <span><?= $semaforo['icon'] ?> <?= $semaforo['label'] ?></span>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">← Volver</a>
                </div>
            </div>
            <div class="card-body">
                <!-- Fecha y horario -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 rounded text-center" style="background:var(--bg)">
                            <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;font-family:var(--font-mono)">Fecha del evento</div>
                            <div class="fw-bold fs-3" style="color:var(--primary)"><?= formatFecha($reserva['fecha_evento']) ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded text-center" style="background:var(--bg)">
                            <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;font-family:var(--font-mono)">Horario</div>
                            <div class="fw-bold fs-4" style="font-family:var(--font-mono);color:var(--primary)">
                                <?= substr($reserva['hora_inicio'],0,5) ?> — <?= substr($reserva['hora_fin'],0,5) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Observaciones -->
                <?php if ($reserva['observaciones']): ?>
                <div class="mb-4">
                    <label class="form-label">Descripción / Observaciones</label>
                    <div class="p-3 rounded" style="background:var(--bg);font-size:.875rem">
                        <?= sanitize($reserva['observaciones']) ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Insumos -->
                <?php if ($items): ?>
                <h6 class="mb-3">📦 Insumos Solicitados</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Insumo</th><th>Cantidad</th><th>Precio/U</th><th>Subtotal</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= sanitize($item['nombre_insumo']) ?></td>
                            <td><?= $item['cantidad'] ?></td>
                            <td><?= $item['precio_unitario'] > 0 ? formatMoneda((float)$item['precio_unitario']) : 'Gratis' ?></td>
                            <td><?= formatMoneda((float)$item['subtotal']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-active fw-bold">
                                <td colspan="3" class="text-end">TOTAL</td>
                                <td><?= formatMoneda($total) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info" style="font-size:.85rem">Sin insumos adicionales solicitados.</div>
                <?php endif; ?>

                <!-- Acciones si pendiente -->
                <?php if ($reserva['estado'] === 'PENDIENTE'): ?>
                <div class="d-flex gap-2 mt-3 pt-3 border-top">
                    <form method="POST" action="aprobar.php">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <button type="submit" class="btn btn-success"
                            onclick="return confirm('¿Aprobar esta reserva?')">
                            ✅ Aprobar Reserva
                        </button>
                    </form>
                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalRechazar">
                        ❌ Rechazar Reserva
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar info -->
    <div class="col-lg-4">
        <!-- Datos residente -->
        <div class="card animate-in delay-1 mb-3">
            <div class="card-header"><h5>👤 Residente</h5></div>
            <div class="card-body">
                <div class="fw-bold mb-2 fs-6"><?= sanitize($reserva['nombre']) ?> <?= sanitize($reserva['apellido']) ?></div>
                <div class="d-flex flex-column gap-1" style="font-size:.83rem">
                    <div>📧 <a href="mailto:<?= sanitize($reserva['correo']) ?>"><?= sanitize($reserva['correo']) ?></a></div>
                    <div>📱 <?= sanitize($reserva['telefono'] ?? '—') ?></div>
                    <div>🪪 <?= sanitize($reserva['cedula']) ?></div>
                </div>
            </div>
        </div>

        <!-- Estado del pago -->
        <?php if ($pago): ?>
        <div class="card animate-in delay-2">
            <div class="card-header"><h5>💳 Pago</h5></div>
            <div class="card-body">
                <?php $pClass = ['PENDIENTE'=>'warning text-dark','VALIDADO'=>'success','RECHAZADO'=>'danger'][$pago['estado']]??'secondary'; ?>
                <span class="badge bg-<?= $pClass ?> mb-2"><?= $pago['estado'] ?></span>
                <div class="fw-bold mb-1"><?= formatMoneda((float)$pago['monto']) ?></div>
                <div style="font-size:.8rem;color:var(--text-muted)"><?= date('d/m/Y H:i', strtotime($pago['fecha_pago'])) ?></div>
                <?php if ($pago['comprobante']): ?>
                <a href="/vivimostodos/assets/uploads/comprobantes/<?= sanitize($pago['comprobante']) ?>"
                   target="_blank" class="btn btn-sm btn-outline-primary mt-2">📎 Ver Comprobante</a>
                <?php endif; ?>
            </div>
        </div>
        <?php elseif ($total > 0): ?>
        <div class="card animate-in delay-2">
            <div class="card-header"><h5>💳 Pago</h5></div>
            <div class="card-body">
                <span class="badge bg-secondary">SIN PAGO REGISTRADO</span>
                <div class="mt-2" style="font-size:.83rem;color:var(--text-muted)">
                    Total a pagar: <strong><?= formatMoneda($total) ?></strong>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal rechazar -->
<div class="modal fade" id="modalRechazar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="rechazar.php">
                <input type="hidden" name="id" value="<?= $id ?>">
                <div class="modal-header">
                    <h5 class="modal-title">❌ Rechazar Reserva #<?= $id ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Motivo del rechazo *</label>
                    <textarea name="motivo" class="form-control" rows="4"
                        placeholder="Explica el motivo al residente..." required minlength="10"></textarea>
                    <small class="text-muted mt-1 d-block">El residente recibirá este mensaje por notificación.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Rechazo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
