<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

$db = getDB();
$pageTitle = 'Editar Usuario';
$id = (int)($_GET['id'] ?? 0);
$errors = [];

$stmt = $db->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();
if (!$usuario) { flashMessage('error','Usuario no encontrado.'); redirect('/vivimostodos/admin/usuarios/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo   = trim($_POST['correo'] ?? '');
    $cedula   = trim($_POST['cedula'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $rol      = $_POST['rol'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$nombre)   $errors[] = 'El nombre es requerido.';
    if (!$apellido) $errors[] = 'El apellido es requerido.';
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errors[] = 'Correo inválido.';
    if (!$cedula)   $errors[] = 'La cédula es requerida.';
    if (!in_array($rol, ['ADMIN','RESIDENTE','SUPERVISOR'])) $errors[] = 'Rol inválido.';

    if (empty($errors)) {
        // Check for duplicates excluding self
        $dup = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE (correo=? OR cedula=?) AND id_usuario != ?");
        $dup->execute([$correo, $cedula, $id]);
        if ($dup->fetchColumn() > 0) {
            $errors[] = 'Correo o cédula ya en uso por otro usuario.';
        } else {
            if ($password) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $db->prepare("UPDATE usuarios SET nombre=?,apellido=?,correo=?,password=?,cedula=?,telefono=?,rol=? WHERE id_usuario=?")
                   ->execute([$nombre,$apellido,$correo,$hash,$cedula,$telefono,$rol,$id]);
            } else {
                $db->prepare("UPDATE usuarios SET nombre=?,apellido=?,correo=?,cedula=?,telefono=?,rol=? WHERE id_usuario=?")
                   ->execute([$nombre,$apellido,$correo,$cedula,$telefono,$rol,$id]);
            }
            flashMessage('success', 'Usuario actualizado correctamente.');
            redirect('/vivimostodos/admin/usuarios/index.php');
        }
    }
    // Refresh for display
    $usuario = array_merge($usuario, ['nombre'=>$nombre,'apellido'=>$apellido,'correo'=>$correo,'cedula'=>$cedula,'telefono'=>$telefono,'rol'=>$rol]);
}

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <?= renderFlash() ?>
        <div class="card animate-in">
            <div class="card-header">
                <h5>✏️ Editar Usuario</h5>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">← Volver</a>
            </div>
            <div class="card-body">
                <?php if ($errors): ?>
                <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" class="form-control" value="<?= sanitize($usuario['nombre']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellido *</label>
                            <input type="text" name="apellido" class="form-control" value="<?= sanitize($usuario['apellido']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correo *</label>
                            <input type="email" name="correo" class="form-control" value="<?= sanitize($usuario['correo']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cédula *</label>
                            <input type="text" name="cedula" class="form-control" value="<?= sanitize($usuario['cedula']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control" value="<?= sanitize($usuario['telefono']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rol *</label>
                            <select name="rol" class="form-select" required>
                                <?php foreach (['ADMIN','RESIDENTE','SUPERVISOR'] as $r): ?>
                                <option value="<?= $r ?>" <?= $usuario['rol'] === $r ? 'selected' : '' ?>><?= $r ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nueva contraseña <small class="text-muted">(dejar vacío para no cambiar)</small></label>
                            <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres">
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
                            <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
