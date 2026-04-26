<?php
/**
 * admin/reservas/index.php — PUNTOS 5 y 6
 * Ver todas las reservas, aprobar o rechazar
 */
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/session.php';
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

$db = getDB();
$pageTitle = 'Gestión de Reservas';

$filtroEstado = $_GET['estado']  ?? '';
$filtroFecha  = $_GET['fecha']   ?? '';
$buscar       = trim($_GET['buscar'] ?? '');

$sql    = "SELECT r.*, u.nombre, u.apellido, u.correo, u.telefono
           FROM reservas r JOIN usuarios u ON r.id_usuario = u.id_usuario
           WHERE 1=1";
$params = [];

if ($filtroEstado) { $sql .= " AND r.estado = ?";        $params[] = $filtroEstado; }
if ($filtroFecha)  { $sql .= " AND r.fecha_evento = ?";  $params[] = $filtroFecha; }
if ($buscar) {
    $sql .= " AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.correo LIKE ? OR u.cedula LIKE ?)";
    $like = "%$buscar%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}
$sql .= " ORDER BY
    CASE r.estado WHEN 'PENDIENTE' THEN 0 ELSE 1 END,
    r.fecha_evento ASC,
    r.fecha_solicitud DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$reservas = $stmt->fetchAll();

$hoy        = date('Y-m-d');
$pendientes = array_filter($reservas, fn($r) => $r['estado'] === 'PENDIENTE');

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<?= renderFlash() ?>

<?php if (count($pendientes) > 0): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-3" style="border-left:4px solid #f59e0b">
    <span style="font-size:1.4rem">⏳</span>
    <div>
        <strong><?= count($pendientes) ?> reserva(s) pendientes</strong> esperan tu aprobación.
    </div>
</div>
<?php endif; ?>

<div class="card animate-in">
    <div class="card-header flex-wrap gap-2">
        <h5>📅 Todas las Reservas</h5>
        <form class="d-flex gap-2 flex-wrap" method="GET">
            <select name="estado" class="form-select form-select-sm" style="width:140px" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                <?php foreach(['PENDIENTE','APROBADA','RECHAZADA','CANCELADA'] as $e): ?>
                <option value="<?=$e?>" <?= $filtroEstado===$e?'selected':'' ?>><?=$e?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="fecha" class="form-control form-control-sm"
                value="<?= sanitize($filtroFecha) ?>" style="width:160px"
                onchange="this.form.submit()">
            <input type="text" name="buscar" class="form-control form-control-sm"
                placeholder="Buscar residente..." value="<?= sanitize($buscar) ?>" style="width:190px">
            <button type="submit" class="btn btn-sm btn-outline-primary">Buscar</button>
            <?php if ($filtroEstado||$filtroFecha||$buscar): ?>
            <a href="index.php" class="btn btn-sm btn-outline-secondary">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha Evento</th>
                        <th>Residente</th>
                        <th>Solicitada</th>
                        <th>Estado</th>
                        <th>Semáforo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($reservas)): ?>
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <div style="font-size:2rem">📅</div>No hay reservas que mostrar
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($reservas as $r):
                    $sClass = badgeEstado($r['estado']);
                    $semaforo = colorSemaforo($r['fecha_evento']);
                    $esHoy    = $r['fecha_evento'] === $hoy;
                    $esPasado = $r['fecha_evento'] < $hoy;
                    $rowStyle = '';
                    if ($r['estado'] === 'PENDIENTE' && $esHoy)    $rowStyle = "background:#fef9c3";
                    elseif ($r['estado'] === 'PENDIENTE' && $esPasado) $rowStyle = "background:#fee2e2";
                ?>
                <tr style="<?= $rowStyle ?>">
                    <td style="font-family:var(--font-mono);color:var(--text-muted)">#<?= $r['id_reserva'] ?></td>
                    <td>
                        <div class="fw-bold"><?= formatFecha($r['fecha_evento']) ?></div>
                        <small style="font-family:var(--font-mono);color:var(--text-muted)">
                            <?= substr($r['hora_inicio'],0,5) ?>–<?= substr($r['hora_fin'],0,5) ?>
                        </small>
                        <?php if ($esHoy): ?><span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">HOY</span><?php endif; ?>
                        <?php if ($esPasado && $r['estado']==='PENDIENTE'): ?><span class="badge bg-danger ms-1" style="font-size:.6rem">VENCIDA</span><?php endif; ?>
                    </td>
                    <td>
                        <div class="fw-semibold"><?= sanitize($r['nombre']) ?> <?= sanitize($r['apellido']) ?></div>
                        <small style="color:var(--text-muted)"><?= sanitize($r['correo']) ?></small>
                    </td>
                    <td style="font-size:.8rem;color:var(--text-muted)">
                        <?= date('d/m/Y H:i', strtotime($r['fecha_solicitud'])) ?>
                    </td>
                    <td><span class="badge bg-<?= $sClass ?>"><?= $r['estado'] ?></span></td>
                    <td>
                        <span title="<?= $semaforo['label'] ?>"><?= $semaforo['icon'] ?> <?= $semaforo['label'] ?></span>
                    </td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="detalle.php?id=<?= $r['id_reserva'] ?>"
                               class="btn btn-sm btn-outline-secondary"
                               style="font-size:.72rem;padding:3px 8px">👁 Ver</a>

                            <?php if ($r['estado'] === 'PENDIENTE'): ?>
                            <form method="POST" action="aprobar.php" class="d-inline">
                                <input type="hidden" name="id" value="<?= $r['id_reserva'] ?>">
                                <button type="submit" class="btn btn-sm btn-success"
                                    style="font-size:.72rem;padding:3px 8px"
                                    onclick="return confirm('¿Aprobar la reserva #<?= $r['id_reserva'] ?>?')">
                                    ✅ Aprobar
                                </button>
                            </form>
                            <button type="button" class="btn btn-sm btn-danger"
                                style="font-size:.72rem;padding:3px 8px"
                                onclick="abrirRechazo(<?= $r['id_reserva'] ?>, '<?= sanitize($r['nombre'].' '.$r['apellido']) ?>')">
                                ❌ Rechazar
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between" style="font-size:.78rem;color:var(--text-muted)">
        <span><?= count($reservas) ?> reserva(s) mostrada(s)</span>
        <div class="d-flex gap-3">
            <span>🟢 Con tiempo &nbsp; 🟡 Esta semana &nbsp; 🔴 Urgente &nbsp; ⬜ Pasado</span>
        </div>
    </div>
</div>

<!-- Modal Rechazar -->
<div class="modal fade" id="modalRechazar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="rechazar.php">
                <input type="hidden" name="id" id="rechazar-id">
                <div class="modal-header">
                    <h5 class="modal-title">❌ Rechazar Reserva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Residente: <strong id="rechazar-nombre"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Motivo del rechazo *</label>
                        <textarea name="motivo" class="form-control" rows="3"
                            placeholder="Explica el motivo para informar al residente..."
                            required minlength="10"></textarea>
                        <small class="text-muted">Mínimo 10 caracteres. El residente recibirá este mensaje.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">❌ Confirmar Rechazo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraJs = "<script>
function abrirRechazo(id, nombre) {
    document.getElementById('rechazar-id').value = id;
    document.getElementById('rechazar-nombre').textContent = nombre;
    new bootstrap.Modal(document.getElementById('modalRechazar')).show();
}
</script>";
include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
