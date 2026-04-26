<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

$db = getDB();
$pageTitle = 'Enviar Notificaciones';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje    = trim($_POST['mensaje'] ?? '');
    $destinatario = $_POST['destinatario'] ?? 'todos';
    $rolFiltro  = $_POST['rol'] ?? '';
    $idUsuario  = (int)($_POST['id_usuario'] ?? 0);

    if (!$mensaje) {
        flashMessage('error', 'El mensaje no puede estar vacío.');
    } else {
        $count = 0;
        if ($destinatario === 'todos') {
            notificarTodos($mensaje);
            $count = $db->query("SELECT COUNT(*) FROM usuarios WHERE estado='ACTIVO'")->fetchColumn();
        } elseif ($destinatario === 'rol' && $rolFiltro) {
            $stmt = $db->prepare("SELECT id_usuario FROM usuarios WHERE rol=? AND estado='ACTIVO'");
            $stmt->execute([$rolFiltro]);
            foreach ($stmt->fetchAll() as $u) { crearNotificacion($u['id_usuario'], $mensaje); $count++; }
        } elseif ($destinatario === 'individual' && $idUsuario) {
            crearNotificacion($idUsuario, $mensaje);
            $count = 1;
        }
        flashMessage('success', "Notificación enviada a {$count} usuario(s) correctamente.");
        redirect('/vivimostodos/admin/notificaciones/enviar.php');
    }
}

$usuarios = $db->query("SELECT id_usuario, nombre, apellido, rol FROM usuarios WHERE estado='ACTIVO' ORDER BY nombre")->fetchAll();

// Últimas notificaciones
$ultimas = $db->query(
    "SELECT n.*, u.nombre, u.apellido FROM notificaciones n JOIN usuarios u ON n.id_usuario=u.id_usuario
     ORDER BY n.fecha DESC LIMIT 20"
)->fetchAll();

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<?= renderFlash() ?>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card animate-in">
            <div class="card-header"><h5>🔔 Enviar Notificación</h5></div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Destinatarios</label>
                        <select name="destinatario" id="dest-select" class="form-select" onchange="toggleDest(this.value)">
                            <option value="todos">📢 Todos los usuarios</option>
                            <option value="rol">🎭 Por rol</option>
                            <option value="individual">👤 Usuario específico</option>
                        </select>
                    </div>
                    <div class="mb-3" id="rol-select" style="display:none">
                        <label class="form-label">Rol</label>
                        <select name="rol" class="form-select">
                            <option value="RESIDENTE">RESIDENTE</option>
                            <option value="SUPERVISOR">SUPERVISOR</option>
                            <option value="ADMIN">ADMIN</option>
                        </select>
                    </div>
                    <div class="mb-3" id="user-select" style="display:none">
                        <label class="form-label">Usuario</label>
                        <select name="id_usuario" class="form-select">
                            <option value="">Seleccionar...</option>
                            <?php foreach ($usuarios as $u): ?>
                            <option value="<?= $u['id_usuario'] ?>"><?= sanitize($u['nombre']) ?> <?= sanitize($u['apellido']) ?> (<?= $u['rol'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mensaje</label>
                        <textarea name="mensaje" class="form-control" rows="4" maxlength="500" placeholder="Escribe la notificación..." required></textarea>
                        <small class="text-muted">Máximo 500 caracteres</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">🚀 Enviar Notificación</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card animate-in delay-2">
            <div class="card-header"><h5>📋 Últimas Notificaciones Enviadas</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Usuario</th><th>Mensaje</th><th>Leído</th><th>Fecha</th></tr></thead>
                        <tbody>
                        <?php foreach ($ultimas as $n): ?>
                        <tr>
                            <td style="white-space:nowrap"><?= sanitize($n['nombre']) ?> <?= sanitize($n['apellido']) ?></td>
                            <td style="font-size:.8rem;max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= sanitize($n['mensaje']) ?>">
                                <?= sanitize($n['mensaje']) ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $n['leido'] ? 'success' : 'warning text-dark' ?>">
                                    <?= $n['leido'] ? '✓ Leído' : '○ No leído' ?>
                                </span>
                            </td>
                            <td style="font-size:.75rem;color:var(--text-muted);white-space:nowrap"><?= date('d/m H:i', strtotime($n['fecha'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ultimas)): ?>
                        <tr><td colspan="4" class="text-center py-3 text-muted">Sin notificaciones</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDest(val) {
    document.getElementById('rol-select').style.display  = val==='rol' ? '' : 'none';
    document.getElementById('user-select').style.display = val==='individual' ? '' : 'none';
}
</script>
<?php include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
