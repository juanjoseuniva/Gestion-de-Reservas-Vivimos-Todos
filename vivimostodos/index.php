<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'config/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Redirect if already logged in
if (isLoggedIn()) {
    $rol = currentRole();
    if ($rol === 'ADMIN') redirect('/vivimostodos/admin/dashboard.php');
    elseif ($rol === 'RESIDENTE') redirect('/vivimostodos/residente/mis_reservas.php');
    elseif ($rol === 'SUPERVISOR') redirect('/vivimostodos/supervisor/cronograma.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo   = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$correo || !$password) {
        $error = 'Por favor completa todos los campos.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE correo = ? AND estado = 'ACTIVO' LIMIT 1");
        $stmt->execute([$correo]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['usuario'] = [
                'id_usuario' => $user['id_usuario'],
                'nombre'     => $user['nombre'],
                'apellido'   => $user['apellido'],
                'correo'     => $user['correo'],
                'rol'        => $user['rol'],
            ];
            session_regenerate_id(true);

            if ($user['rol'] === 'ADMIN') redirect('/vivimostodos/admin/dashboard.php');
            elseif ($user['rol'] === 'RESIDENTE') redirect('/vivimostodos/residente/mis_reservas.php');
            elseif ($user['rol'] === 'SUPERVISOR') redirect('/vivimostodos/supervisor/cronograma.php');
        } else {
            $error = 'Correo o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — Vivimostodos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card animate-in">
        <div class="login-logo">🏢</div>
        <h1 class="login-title">Vivimostodos</h1>
        <p class="login-subtitle">Unidad Residencial — Portal de Gestión</p>

        <?php if ($error): ?>
        <div class="alert alert-danger mb-3" style="font-size:.85rem"><?= sanitize($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Correo electrónico</label>
                <input type="email" name="correo" class="form-control" placeholder="usuario@vivimostodos.com"
                    value="<?= sanitize($_POST['correo'] ?? '') ?>" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Contraseña</label>
                <div class="input-group">
                    <input type="password" name="password" id="pwd-input" class="form-control" placeholder="••••••••" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePwd()">👁</button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                Iniciar Sesión
            </button>
        </form>

        <div class="mt-4 p-3 rounded" style="background:#f8fafc;font-size:.75rem;color:var(--text-muted)">
            <strong>Acceso de prueba:</strong><br>
            Admin: admin@vivimostodos.com / Admin123
        </div>
    </div>
</div>
<script>
function togglePwd() {
    const i = document.getElementById('pwd-input');
    i.type = i.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
