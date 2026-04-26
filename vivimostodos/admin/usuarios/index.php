<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

$db = getDB();
$pageTitle = 'Gestión de Usuarios';

$buscar = trim($_GET['buscar'] ?? '');
$sql = "SELECT * FROM usuarios WHERE 1=1";
$params = [];
if ($buscar) {
    $sql .= " AND (nombre LIKE ? OR apellido LIKE ? OR correo LIKE ? OR cedula LIKE ?)";
    $like = "%$buscar%";
    $params = [$like,$like,$like,$like];
}
$sql .= " ORDER BY fecha_creacion DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div></div>
    <a href="/vivimostodos/admin/usuarios/crear.php" class="btn btn-primary">
        ➕ Nuevo Usuario
    </a>
</div>

<?= renderFlash() ?>

<div class="card animate-in">
    <div class="card-header">
        <h5>👥 Usuarios del Sistema</h5>
        <form class="d-flex gap-2" method="GET">
            <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Buscar..." value="<?= sanitize($buscar) ?>" style="width:220px">
            <button type="submit" class="btn btn-sm btn-outline-primary">Buscar</button>
            <?php if ($buscar): ?><a href="?" class="btn btn-sm btn-outline-secondary">Limpiar</a><?php endif; ?>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>ID</th><th>Nombre</th><th>Correo</th><th>Cédula</th>
                        <th>Teléfono</th><th>Rol</th><th>Estado</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td style="font-family:var(--font-mono)">#<?= $u['id_usuario'] ?></td>
                    <td><?= sanitize($u['nombre']) ?> <?= sanitize($u['apellido']) ?></td>
                    <td><?= sanitize($u['correo']) ?></td>
                    <td style="font-family:var(--font-mono)"><?= sanitize($u['cedula']) ?></td>
                    <td><?= sanitize($u['telefono']) ?></td>
                    <td>
                        <?php $rClass = ['ADMIN'=>'primary','RESIDENTE'=>'info','SUPERVISOR'=>'warning text-dark'][$u['rol']] ?? 'secondary'; ?>
                        <span class="badge bg-<?= $rClass ?>"><?= $u['rol'] ?></span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $u['estado']==='ACTIVO' ? 'success' : 'secondary' ?>"><?= $u['estado'] ?></span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="editar.php?id=<?= $u['id_usuario'] ?>" class="btn btn-sm btn-outline-primary" style="font-size:.75rem;padding:3px 10px">✏️ Editar</a>
                            <form method="POST" action="eliminar.php" class="d-inline">
                                <input type="hidden" name="id" value="<?= $u['id_usuario'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:.75rem;padding:3px 10px"
                                    onclick="return confirm('¿Cambiar estado de este usuario?')">
                                    <?= $u['estado']==='ACTIVO' ? '🚫 Desactivar' : '✅ Activar' ?>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($usuarios)): ?>
                <tr><td colspan="8" class="text-center py-4 text-muted">No se encontraron usuarios</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
