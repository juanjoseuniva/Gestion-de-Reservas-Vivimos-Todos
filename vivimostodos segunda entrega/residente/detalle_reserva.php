<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['RESIDENTE']);

$db  = getDB();
$uid = currentUserId();
$id  = (int)($_GET['id'] ?? 0);
$pageTitle = 'Detalle de Reserva';

$stmt = $db->prepare(
    "SELECT r.*, u.nombre, u.apellido, u.correo
     FROM reservas r JOIN usuarios u ON r.id_usuario=u.id_usuario
     WHERE r.id_reserva=? AND r.id_usuario=?"
);
$stmt->execute([$id, $uid]);
$reserva = $stmt->fetch();
if (!$reserva) { flashMessage('error','Reserva no encontrada.'); redirect('/vivimostodos/residente/mis_reservas.php'); }

$detalles = $db->prepare(
    "SELECT dr.*, i.nombre as nombre_insumo, i.precio_unitario
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

<div class="row g-3 justify-content-center">
    <div class="col-lg-8">
        <div class="card animate-in">
            <div class="card-header">
                <h5>📋 Reserva #<?= $id ?></h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-<?= $sClass ?>"><?= $reserva['estado'] ?></span>
                    <a href="mis_reservas.php" class="btn btn-sm btn-outline-secondary">← Volver</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 rounded" style="background:var(--bg)">
                            <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase;font-family:var(--font-mono)">Fecha del evento</div>
                            <div class="fw-bold fs-4"><?= formatFecha($reserva['fecha_evento']) ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded" style="background:var(--bg)">
                            <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase;font-family:var(--font-mono)">Horario</div>
                            <div class="fw-bold fs-4" style="font-family:var(--font-mono)">
                                <?= substr($reserva['hora_inicio'],0,5) ?> – <?= substr($reserva['hora_fin'],0,5) ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($reserva['observaciones']): ?>
                    <div class="col-12">
                        <label class="form-label">Descripción del evento</label>
                        <div class="p-3 rounded" style="background:var(--bg)"><?= sanitize($reserva['observaciones']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($items): ?>
                <h6 class="mb-3">📦 Insumos solicitados</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-sm">
                        <thead><tr><th>Insumo</th><th>Cantidad</th><th>Precio/U</th><th>Subtotal</th></tr></thead>
                        <tbody>
                        <?php $total=0; foreach($items as $it): $total+=$it['subtotal']; ?>
                        <tr>
                            <td><?= sanitize($it['nombre_insumo']) ?></td>
                            <td><?= $it['cantidad'] ?></td>
                            <td><?= formatMoneda((float)$it['precio_unitario']) ?></td>
                            <td><?= formatMoneda((float)$it['subtotal']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="fw-bold table-active"><td colspan="3" class="text-end">TOTAL</td><td><?= formatMoneda($total) ?></td></tr>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- Estado de pago -->
                <?php if ($pago): ?>
                <div class="alert alert-<?= ['PENDIENTE'=>'warning','VALIDADO'=>'success','RECHAZADO'=>'danger'][$pago['estado']]??'info' ?>">
                    <strong>Estado de pago: <?= $pago['estado'] ?></strong>
                    <?php if ($pago['estado']==='PENDIENTE'): ?> — En revisión por el administrador.
                    <?php elseif ($pago['estado']==='VALIDADO'): ?> — ✅ Pago confirmado. ¡Todo listo para tu evento!
                    <?php elseif ($pago['estado']==='RECHAZADO'): ?> — Por favor sube un nuevo comprobante.
                    <?php endif; ?>
                </div>
                <?php elseif ($total > 0 && $reserva['estado']==='APROBADA'): ?>
                <div class="alert alert-warning">
                    ⚠️ Tu reserva fue aprobada pero falta subir el comprobante de pago por <?= formatMoneda($total) ?>.
                    <a href="pagar.php?id=<?= $id ?>" class="btn btn-sm btn-warning ms-2">💳 Pagar ahora</a>
                </div>
                <?php endif; ?>

                <div class="d-flex gap-2 mt-3">
                    <?php if (in_array($reserva['estado'], ['PENDIENTE','APROBADA']) && $reserva['fecha_evento'] >= date('Y-m-d')): ?>
                    <form method="POST" action="cancelar.php">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('¿Cancelar esta reserva?')">🗑 Cancelar Reserva</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($reserva['estado']==='APROBADA' && (!$pago || $pago['estado']==='RECHAZADO') && ($total??0) > 0): ?>
                    <a href="pagar.php?id=<?= $id ?>" class="btn btn-primary">💳 Subir Comprobante</a>
                    <?php endif; ?>
                </div>

                <div class="mt-3" style="font-size:.75rem;color:var(--text-muted)">
                    Solicitada el: <?= date('d/m/Y H:i', strtotime($reserva['fecha_solicitud'])) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
