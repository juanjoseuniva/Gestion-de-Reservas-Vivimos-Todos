<?php
/**
 * residente/mis_reservas.php — PUNTO 5
 */
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/session.php';
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/functions.php';
requireRole(['RESIDENTE']);

$db  = getDB();
$uid = currentUserId();
$pageTitle = 'Mis Reservas';

$reservas = $db->prepare(
    "SELECT r.*,
            COALESCE(p.estado, 'SIN PAGO') as estado_pago,
            COALESCE(p.monto, 0)            as monto_pago,
            p.id_pago
     FROM reservas r
     LEFT JOIN pagos p ON p.id_reserva = r.id_reserva
     WHERE r.id_usuario = ?
     ORDER BY r.fecha_evento DESC"
);
$reservas->execute([$uid]);
$misReservas = $reservas->fetchAll();

// Stats rápidas
$stats = ['PENDIENTE'=>0,'APROBADA'=>0,'RECHAZADA'=>0,'CANCELADA'=>0];
foreach ($misReservas as $r) $stats[$r['estado']] = ($stats[$r['estado']] ?? 0) + 1;

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<?= renderFlash() ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <!-- Mini stats -->
    <div class="d-flex gap-2 flex-wrap">
        <span class="px-3 py-2 rounded" style="background:#fef9c3;font-size:.8rem;color:#92400e">⏳ Pendientes: <strong><?= $stats['PENDIENTE'] ?></strong></span>
        <span class="px-3 py-2 rounded" style="background:#dcfce7;font-size:.8rem;color:#15803d">✅ Aprobadas: <strong><?= $stats['APROBADA'] ?></strong></span>
        <span class="px-3 py-2 rounded" style="background:#fee2e2;font-size:.8rem;color:#b91c1c">❌ Rechazadas: <strong><?= $stats['RECHAZADA'] ?></strong></span>
    </div>
    <a href="reservar.php" class="btn btn-primary">➕ Nueva Reserva</a>
</div>

<?php if (empty($misReservas)): ?>
<div class="card animate-in">
    <div class="card-body text-center py-5">
        <div style="font-size:3.5rem;margin-bottom:16px">📅</div>
        <h5>Aún no tienes reservas</h5>
        <p class="text-muted mb-4">¡Reserva el salón social para tu próximo evento!</p>
        <a href="reservar.php" class="btn btn-primary px-4">Hacer mi primera reserva</a>
    </div>
</div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($misReservas as $r):
    $sClass   = badgeEstado($r['estado']);
    $semaforo = colorSemaforo($r['fecha_evento']);
    $esHoy    = $r['fecha_evento'] === date('Y-m-d');
    $esFutura = $r['fecha_evento'] >= date('Y-m-d');

    // Color borde según estado
    $borderColor = match($r['estado']) {
        'APROBADA'  => 'var(--success)',
        'RECHAZADA' => 'var(--danger)',
        'CANCELADA' => '#94a3b8',
        default     => 'var(--warning)',
    };
?>
<div class="col-lg-6 animate-in">
    <div class="card h-100" style="border-left:4px solid <?= $borderColor ?>">
        <div class="card-body">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span style="font-family:var(--font-mono);font-size:.72rem;color:var(--text-muted)">#<?= $r['id_reserva'] ?></span>
                    <div class="fw-bold fs-5" style="color:var(--primary)"><?= formatFecha($r['fecha_evento']) ?></div>
                    <small style="color:var(--text-muted)">
                        🕐 12:00 — 23:59 <?= $esHoy ? '<span class="badge bg-warning text-dark ms-1">HOY</span>' : '' ?>
                    </small>
                </div>
                <div class="text-end">
                    <span class="badge bg-<?= $sClass ?> mb-1"><?= $r['estado'] ?></span>
                    <div style="font-size:.8rem"><?= $semaforo['icon'] ?> <?= $semaforo['label'] ?></div>
                </div>
            </div>

            <!-- Observaciones -->
            <?php if ($r['observaciones'] && !str_starts_with($r['observaciones'], 'RECHAZADA')): ?>
            <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:10px">
                <?= sanitize(substr($r['observaciones'], 0, 100)) ?><?= strlen($r['observaciones']) > 100 ? '...' : '' ?>
            </p>
            <?php endif; ?>

            <!-- Motivo rechazo -->
            <?php if ($r['estado'] === 'RECHAZADA' && $r['observaciones']): ?>
            <div class="alert alert-danger py-2 mb-3" style="font-size:.82rem">
                <?= sanitize($r['observaciones']) ?>
            </div>
            <?php endif; ?>

            <!-- Estado pago -->
            <div class="d-flex align-items-center gap-2 mb-3">
                <span style="font-size:.78rem;color:var(--text-muted)">💳 Pago:</span>
                <?php
                $pClass = match($r['estado_pago']) {
                    'VALIDADO'  => 'success',
                    'PENDIENTE' => 'warning text-dark',
                    'RECHAZADO' => 'danger',
                    default     => 'secondary'
                };
                ?>
                <span class="badge bg-<?= $pClass ?>"><?= $r['estado_pago'] ?></span>
                <?php if ($r['monto_pago'] > 0): ?>
                <span style="font-family:var(--font-mono);font-size:.82rem;font-weight:600">
                    <?= formatMoneda((float)$r['monto_pago']) ?>
                </span>
                <?php endif; ?>
            </div>

            <!-- Acciones -->
            <div class="d-flex gap-2 flex-wrap">
                <a href="detalle_reserva.php?id=<?= $r['id_reserva'] ?>"
                   class="btn btn-sm btn-outline-secondary">👁 Ver detalle</a>

                <?php if ($r['estado'] === 'APROBADA'
                    && in_array($r['estado_pago'], ['SIN PAGO', 'RECHAZADO'])
                    && (float)$r['monto_pago'] > 0): ?>
                <a href="pagar.php?id=<?= $r['id_reserva'] ?>"
                   class="btn btn-sm btn-warning text-dark">💳 Subir Comprobante</a>
                <?php endif; ?>

                <?php if (in_array($r['estado'], ['PENDIENTE','APROBADA']) && $esFutura): ?>
                <form method="POST" action="cancelar.php" class="d-inline">
                    <input type="hidden" name="id" value="<?= $r['id_reserva'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"
                        onclick="return confirm('¿Cancelar la reserva del <?= formatFecha($r['fecha_evento']) ?>? Esta acción no se puede deshacer.')">
                        🗑 Cancelar
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-footer" style="font-size:.73rem;color:var(--text-muted)">
            Solicitada: <?= date('d/m/Y H:i', strtotime($r['fecha_solicitud'])) ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
