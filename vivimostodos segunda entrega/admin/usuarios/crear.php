<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

$db = getDB();
$pageTitle = 'Crear Usuario';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo   = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    $cedula   = trim($_POST['cedula'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $rol      = $_POST['rol'] ?? '';

    if (!$nombre)   $errors[] = 'El nombre es requerido.';
    if (!$apellido) $errors[] = 'El apellido es requerido.';
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errors[] = 'Correo inválido.';
    if (strlen($password) < 6) $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
    if (!$cedula)   $errors[] = 'La cédula es requerida.';
    if (!in_array($rol, ['ADMIN','RESIDENTE','SUPERVISOR'])) $errors[] = 'Rol inválido.';

    if (empty($errors)) {
        // Check duplicates
        $dup = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE correo=? OR cedula=?");
        $dup->execute([$correo, $cedula]);
        if ($dup->fetchColumn() > 0) {
            $errors[] = 'Ya existe un usuario con ese correo o cédula.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare(
                "INSERT INTO usuarios (nombre,apellido,correo,password,cedula,telefono,rol) VALUES (?,?,?,?,?,?,?)"
            );
            $stmt->execute([$nombre,$apellido,$correo,$hash,$cedula,$telefono,$rol]);
            flashMessage('success', "Usuario {$nombre} {$apellido} creado exitosamente.");
            redirect('/vivimostodos/admin/usuarios/index.php');
        }
    }
}

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <?= renderFlash() ?>
        <div class="card animate-in">
            <div class="card-header">
                <h5>👤 Crear Nuevo Usuario</h5>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">← Volver</a>
            </div>
            <div class="card-body">
                <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $e): ?>
                        <li><?= sanitize($e) ?></li>
                    <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" class="form-control" value="<?= sanitize($_POST['nombre'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellido *</label>
                            <input type="text" name="apellido" class="form-control" value="<?= sanitize($_POST['apellido'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correo electrónico *</label>
                            <input type="email" name="correo" class="form-control" value="<?= sanitize($_POST['correo'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cédula *</label>
                            <input type="text" name="cedula" class="form-control" value="<?= sanitize($_POST['cedula'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control" value="<?= sanitize($_POST['telefono'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rol *</label>
                            <select name="rol" class="form-select" required>
                                <option value="">Seleccionar rol...</option>
                                <?php foreach (['ADMIN','RESIDENTE','SUPERVISOR'] as $r): ?>
                                <option value="<?= $r ?>" <?= ($_POST['rol'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Contraseña *</label>
                            <div class="input-group">
                                <input type="password" name="password" id="pwd" class="form-control" placeholder="Mínimo 6 caracteres" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePwd()">👁</button>
                            </div>
                        </div>
                        <div class="col-12 d-flex gap-2 mt-2">
                            <button type="submit" class="btn btn-primary">✅ Crear Usuario</button>
                            <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>function togglePwd(){const i=document.getElementById('pwd');i.type=i.type==='password'?'text':'password';}</script>
<?php include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
