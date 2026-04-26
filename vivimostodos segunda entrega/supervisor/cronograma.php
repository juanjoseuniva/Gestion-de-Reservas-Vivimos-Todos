<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['SUPERVISOR','ADMIN']);

$db = getDB();
$pageTitle = 'Cronograma de Reservas';

$fecha = $_GET['fecha'] ?? date('Y-m-d');

// Reservas del día seleccionado
$stmt = $db->prepare(
    "SELECT r.*, u.nombre, u.apellido, u.telefono
     FROM reservas r JOIN usuarios u ON r.id_usuario=u.id_usuario
     WHERE r.fecha_evento=? AND r.estado IN ('APROBADA','PENDIENTE')
     ORDER BY r.hora_inicio"
);
$stmt->execute([$fecha]);
$reservasHoy = $stmt->fetchAll();

// Próximas 7 días
$stmt7 = $db->prepare(
    "SELECT r.fecha_evento, r.estado, COUNT(*) as cnt
     FROM reservas r
     WHERE r.fecha_evento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
     AND r.estado IN ('APROBADA','PENDIENTE')
     GROUP BY r.fecha_evento, r.estado
     ORDER BY r.fecha_evento"
);
$stmt7->execute();
$proximas = $stmt7->fetchAll();

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<?= renderFlash() ?>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card animate-in">
            <div class="card-header">
                <h5>📋 Cronograma — <?= formatFecha($fecha) ?></h5>
                <form class="d-flex gap-2" method="GET">
                    <input type="date" name="fecha" class="form-control form-control-sm" value="<?= $fecha ?>" style="width:180px">
                    <button type="submit" class="btn btn-sm btn-primary">Ver</button>
                    <a href="?fecha=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline-secondary">Hoy</a>
                </form>
            </div>
            <div class="card-body">
                <?php if (empty($reservasHoy)): ?>
                <div class="text-center py-4">
                    <div style="font-size:2.5rem">🟢</div>
                    <h5 class="mt-2">Día libre</h5>
                    <p class="text-muted">No hay reservas programadas para esta fecha.</p>
                </div>
                <?php else: ?>
                <?php foreach ($reservasHoy as $r):
                    $sc=['PENDIENTE'=>'warning','APROBADA'=>'success'][$r['estado']]??'secondary';
                ?>
                <div class="p-3 rounded mb-3" style="border-left:4px solid var(--<?= $r['estado']==='APROBADA'?'success':'warning' ?>);background:var(--bg)">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-bold fs-5" style="font-family:var(--font-mono)">
                                <?= substr($r['hora_inicio'],0,5) ?> – <?= substr($r['hora_fin'],0,5) ?>
                            </div>
                            <div class="fw-semibold"><?= sanitize($r['nombre']) ?> <?= sanitize($r['apellido']) ?></div>
                            <div style="font-size:.82rem;color:var(--text-muted)">📱 <?= sanitize($r['telefono']) ?></div>
                            <?php if ($r['observaciones']): ?>
                            <div style="font-size:.82rem;margin-top:4px"><?= sanitize($r['observaciones']) ?></div>
                            <?php endif; ?>
                        </div>
                        <span class="badge bg-<?= $sc ?>"><?= $r['estado'] ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Próximas reservas -->
<?php if ($proximas): ?>
<div class="card animate-in delay-2">
    <div class="card-header"><h5>📅 Próximas Reservas (7 días)</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Fecha</th><th>Reservas</th><th>Estado</th><th>Acción</th></tr></thead>
                <tbody>
                <?php foreach ($proximas as $p):
                    $sc=['PENDIENTE'=>'warning text-dark','APROBADA'=>'success'][$p['estado']]??'secondary';
                ?>
                <tr>
                    <td><?= formatFecha($p['fecha_evento']) ?></td>
                    <td><?= $p['cnt'] ?> reserva(s)</td>
                    <td><span class="badge bg-<?= $sc ?>"><?= $p['estado'] ?></span></td>
                    <td><a href="?fecha=<?= $p['fecha_evento'] ?>" class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;padding:3px 10px">Ver día</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
