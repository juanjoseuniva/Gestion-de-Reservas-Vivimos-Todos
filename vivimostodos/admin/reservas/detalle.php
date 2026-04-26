<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

$db = getDB();
$pageTitle = 'Detalle de Reserva';
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare(
    "SELECT r.*, u.nombre, u.apellido, u.correo, u.telefono, u.cedula
     FROM reservas r JOIN usuarios u ON r.id_usuario=u.id_usuario
     WHERE r.id_reserva=?"
);
$stmt->execute([$id]);
$reserva = $stmt->fetch();
if (!$reserva) { flashMessage('error','Reserva no encontrada.'); redirect('/vivimostodos/admin/reservas/index.php'); }

$detalles = $db->prepare(
    "SELECT dr.*, i.nombre as insumo_nombre, i.precio_unitario
     FROM detalle_reserva dr JOIN inventario i ON dr.id_insumo=i.id_insumo
     WHERE dr.id_reserva=?"
);
$detalles->execute([$id]);
$items = $detalles->fetchAll();

$pago = $db->prepare("SELECT * FROM pagos WHERE id_reserva=? ORDER BY fecha_pago DESC LIMIT 1");
$pago->execute([$id]);
$pago = $pago->fetch();

$sClass=['PENDIENTE'=>'warning text-dark','APROBADA'=>'success','RECHAZADA'=>'danger','CANCELADA'=>'secondary'][$reserva['estado']]??'secondary';

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<?= renderFlash() ?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card animate-in">
            <div class="card-header">
                <h5>📋 Reserva #<?= $id ?></h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-<?= $sClass ?> fs-6"><?= $reserva['estado'] ?></span>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">← Volver</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Fecha del Evento</label>
                        <div class="fw-bold fs-5"><?= formatFecha($reserva['fecha_evento']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Horario</label>
                        <div class="fw-bold" style="font-family:var(--font-mono)">
                            <?= substr($reserva['hora_inicio'],0,5) ?> – <?= substr($reserva['hora_fin'],0,5) ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observaciones</label>
                        <div class="p-3 rounded" style="background:var(--bg)">
                            <?= $reserva['observaciones'] ? sanitize($reserva['observaciones']) : '<span class="text-muted">Sin observaciones</span>' ?>
                        </div>
                    </div>
                </div>

                <!-- Insumos solicitados -->
                <?php if ($items): ?>
                <h6 class="mb-3">📦 Insumos Solicitados</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm">
                        <thead><tr><th>Insumo</th><th>Cantidad</th><th>Precio/U</th><th>Subtotal</th></tr></thead>
                        <tbody>
                        <?php $totalGeneral = 0; foreach ($items as $item): $totalGeneral += $item['subtotal']; ?>
                        <tr>
                            <td><?= sanitize($item['insumo_nombre']) ?></td>
                            <td><?= $item['cantidad'] ?></td>
                            <td><?= formatMoneda((float)$item['precio_unitario']) ?></td>
                            <td><?= formatMoneda((float)$item['subtotal']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-active fw-bold">
                            <td colspan="3" class="text-end">TOTAL</td>
                            <td><?= formatMoneda($totalGeneral) ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info">Sin insumos adicionales solicitados.</div>
                <?php endif; ?>

                <!-- Acciones si pendiente -->
                <?php if ($reserva['estado'] === 'PENDIENTE'): ?>
                <div class="d-flex gap-2 mt-3">
                    <form method="POST" action="aprobar.php">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <button class="btn btn-success" onclick="return confirm('¿Aprobar reserva?')">✅ Aprobar Reserva</button>
                    </form>
                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalRechazar">❌ Rechazar</button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Info residente + pago -->
    <div class="col-lg-4">
        <div class="card animate-in delay-1 mb-3">
            <div class="card-header"><h5>👤 Residente</h5></div>
            <div class="card-body">
                <div class="mb-2"><strong><?= sanitize($reserva['nombre']) ?> <?= sanitize($reserva['apellido']) ?></strong></div>
                <div style="font-size:.83rem;color:var(--text-muted)" class="mb-1">📧 <?= sanitize($reserva['correo']) ?></div>
                <div style="font-size:.83rem;color:var(--text-muted)" class="mb-1">📱 <?= sanitize($reserva['telefono']) ?></div>
                <div style="font-size:.83rem;color:var(--text-muted)">🪪 <?= sanitize($reserva['cedula']) ?></div>
            </div>
        </div>

        <?php if ($pago): ?>
        <div class="card animate-in delay-2">
            <div class="card-header"><h5>💳 Pago</h5></div>
            <div class="card-body">
                <?php $pClass=['PENDIENTE'=>'warning text-dark','VALIDADO'=>'success','RECHAZADO'=>'danger'][$pago['estado']]??'secondary'; ?>
                <span class="badge bg-<?= $pClass ?> mb-2"><?= $pago['estado'] ?></span>
                <div class="mb-2"><strong>Monto:</strong> <?= formatMoneda((float)$pago['monto']) ?></div>
                <div class="mb-2" style="font-size:.8rem;color:var(--text-muted)"><?= date('d/m/Y H:i', strtotime($pago['fecha_pago'])) ?></div>
                <?php if ($pago['comprobante']): ?>
                <a href="/vivimostodos/assets/uploads/comprobantes/<?= $pago['comprobante'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">📎 Ver Comprobante</a>
                <?php endif; ?>
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
                <div class="modal-header"><h5 class="modal-title">❌ Rechazar Reserva</h5></div>
                <div class="modal-body">
                    <label class="form-label">Motivo del rechazo</label>
                    <textarea name="motivo" class="form-control" rows="3" placeholder="Explique el motivo..."></textarea>
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
