<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

$db = getDB();
$pageTitle = 'Gestión de Reservas';

$filtroEstado = $_GET['estado'] ?? '';
$filtroBuscar = trim($_GET['buscar'] ?? '');

$sql = "SELECT r.*, u.nombre, u.apellido, u.correo
        FROM reservas r JOIN usuarios u ON r.id_usuario=u.id_usuario WHERE 1=1";
$params = [];
if ($filtroEstado) { $sql .= " AND r.estado=?"; $params[] = $filtroEstado; }
if ($filtroBuscar) {
    $sql .= " AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.cedula LIKE ?)";
    $like = "%$filtroBuscar%"; $params = array_merge($params, [$like,$like,$like]);
}
$sql .= " ORDER BY r.fecha_solicitud DESC";
$stmt = $db->prepare($sql); $stmt->execute($params);
$reservas = $stmt->fetchAll();

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<?= renderFlash() ?>

<div class="card animate-in">
    <div class="card-header flex-wrap gap-2">
        <h5>📅 Reservas del Salón Social</h5>
        <div class="d-flex gap-2 flex-wrap">
            <form class="d-flex gap-2" method="GET">
                <select name="estado" class="form-select form-select-sm" style="width:140px" onchange="this.form.submit()">
                    <option value="">Todos los estados</option>
                    <?php foreach(['PENDIENTE','APROBADA','RECHAZADA','CANCELADA'] as $e): ?>
                    <option value="<?=$e?>" <?= $filtroEstado===$e?'selected':'' ?>><?=$e?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Buscar residente..." value="<?= sanitize($filtroBuscar) ?>" style="width:190px">
                <button type="submit" class="btn btn-sm btn-outline-primary">Buscar</button>
                <?php if ($filtroEstado||$filtroBuscar): ?><a href="?" class="btn btn-sm btn-outline-secondary">Limpiar</a><?php endif; ?>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr><th>#</th><th>Residente</th><th>Fecha Evento</th><th>Horario</th><th>Solicitud</th><th>Estado</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                <?php foreach ($reservas as $r):
                    $sClass=['PENDIENTE'=>'warning text-dark','APROBADA'=>'success','RECHAZADA'=>'danger','CANCELADA'=>'secondary'][$r['estado']]??'secondary';
                ?>
                <tr>
                    <td style="font-family:var(--font-mono)">#<?= $r['id_reserva'] ?></td>
                    <td>
                        <div><?= sanitize($r['nombre']) ?> <?= sanitize($r['apellido']) ?></div>
                        <small style="color:var(--text-muted)"><?= sanitize($r['correo']) ?></small>
                    </td>
                    <td><?= formatFecha($r['fecha_evento']) ?></td>
                    <td style="font-family:var(--font-mono);font-size:.8rem">
                        <?= substr($r['hora_inicio'],0,5) ?> – <?= substr($r['hora_fin'],0,5) ?>
                    </td>
                    <td style="font-size:.8rem;color:var(--text-muted)"><?= date('d/m/Y H:i', strtotime($r['fecha_solicitud'])) ?></td>
                    <td><span class="badge bg-<?= $sClass ?>"><?= $r['estado'] ?></span></td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="detalle.php?id=<?= $r['id_reserva'] ?>" class="btn btn-sm btn-outline-secondary" style="font-size:.72rem;padding:3px 8px">👁 Detalle</a>
                            <?php if ($r['estado'] === 'PENDIENTE'): ?>
                            <form method="POST" action="aprobar.php" class="d-inline">
                                <input type="hidden" name="id" value="<?= $r['id_reserva'] ?>">
                                <button class="btn btn-sm btn-success" style="font-size:.72rem;padding:3px 8px" onclick="return confirm('¿Aprobar esta reserva?')">✅ Aprobar</button>
                            </form>
                            <form method="POST" action="rechazar.php" class="d-inline">
                                <input type="hidden" name="id" value="<?= $r['id_reserva'] ?>">
                                <button class="btn btn-sm btn-danger" style="font-size:.72rem;padding:3px 8px" onclick="return confirm('¿Rechazar esta reserva?')">❌ Rechazar</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($reservas)): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">No hay reservas que mostrar</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
