<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['RESIDENTE']);

$db  = getDB();
$uid = currentUserId();
$pageTitle = 'Mis Reservas';

$reservas = $db->prepare(
    "SELECT r.*, 
            COALESCE(p.estado, 'SIN PAGO') as estado_pago,
            COALESCE(p.monto, 0) as monto,
            p.id_pago
     FROM reservas r
     LEFT JOIN pagos p ON p.id_reserva=r.id_reserva
     WHERE r.id_usuario=?
     ORDER BY r.fecha_solicitud DESC"
);
$reservas->execute([$uid]);
$misReservas = $reservas->fetchAll();

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<?= renderFlash() ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div></div>
    <a href="reservar.php" class="btn btn-primary">➕ Nueva Reserva</a>
</div>

<?php if (empty($misReservas)): ?>
<div class="card animate-in">
    <div class="card-body text-center py-5">
        <div style="font-size:3rem;margin-bottom:16px">📅</div>
        <h5>No tienes reservas aún</h5>
        <p class="text-muted mb-3">¡Haz tu primera reserva del salón social!</p>
        <a href="reservar.php" class="btn btn-primary">Reservar ahora</a>
    </div>
</div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($misReservas as $r):
    $sClass=['PENDIENTE'=>'warning text-dark','APROBADA'=>'success','RECHAZADA'=>'danger','CANCELADA'=>'secondary'][$r['estado']]??'secondary';
    $pClass=['SIN PAGO'=>'secondary','PENDIENTE'=>'warning text-dark','VALIDADO'=>'success','RECHAZADO'=>'danger'][$r['estado_pago']]??'secondary';
?>
<div class="col-lg-6 animate-in">
    <div class="card h-100" style="border-left:4px solid var(--<?= $r['estado']==='APROBADA'?'success':($r['estado']==='RECHAZADA'?'danger':($r['estado']==='CANCELADA'?'text-muted':'warning')) ?>)">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span style="font-family:var(--font-mono);font-size:.75rem;color:var(--text-muted)">#<?= $r['id_reserva'] ?></span>
                    <h5 class="mb-0"><?= formatFecha($r['fecha_evento']) ?></h5>
                    <small style="color:var(--text-muted)">
                        <?= substr($r['hora_inicio'],0,5) ?> – <?= substr($r['hora_fin'],0,5) ?>
                    </small>
                </div>
                <span class="badge bg-<?= $sClass ?>"><?= $r['estado'] ?></span>
            </div>

            <?php if ($r['observaciones']): ?>
            <p style="font-size:.83rem;color:var(--text-muted);margin-bottom:12px"><?= sanitize($r['observaciones']) ?></p>
            <?php endif; ?>

            <div class="d-flex align-items-center gap-2 mb-3">
                <span style="font-size:.78rem">💳 Pago:</span>
                <span class="badge bg-<?= $pClass ?>"><?= $r['estado_pago'] ?></span>
                <?php if ($r['monto'] > 0): ?>
                <span style="font-family:var(--font-mono);font-size:.82rem;font-weight:bold"><?= formatMoneda((float)$r['monto']) ?></span>
                <?php endif; ?>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="detalle_reserva.php?id=<?= $r['id_reserva'] ?>" class="btn btn-sm btn-outline-secondary">👁 Detalle</a>
                <?php if ($r['estado'] === 'APROBADA' && ($r['estado_pago'] === 'SIN PAGO' || $r['estado_pago'] === 'RECHAZADO') && $r['monto'] > 0): ?>
                <a href="pagar.php?id=<?= $r['id_reserva'] ?>" class="btn btn-sm btn-warning text-dark">💳 Subir Pago</a>
                <?php endif; ?>
                <?php if (in_array($r['estado'], ['PENDIENTE','APROBADA']) && $r['fecha_evento'] >= date('Y-m-d')): ?>
                <form method="POST" action="cancelar.php" class="d-inline">
                    <input type="hidden" name="id" value="<?= $r['id_reserva'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Cancelar esta reserva?')">🗑 Cancelar</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-footer" style="font-size:.75rem;color:var(--text-muted)">
            Solicitada: <?= date('d/m/Y H:i', strtotime($r['fecha_solicitud'])) ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
