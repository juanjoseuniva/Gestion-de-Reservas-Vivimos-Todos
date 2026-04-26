<?php
$root = str_replace('\\', '/', __DIR__);
require_once $root . '/config/database.php';

echo "<h2>👥 Gestión de Usuarios de Prueba</h2>";

try {
    $db = getDB();
    echo "✅ Conexión a BD: OK<br><br>";
} catch (Exception $e) {
    die("❌ Error BD: " . $e->getMessage());
}

// Acciones
if (isset($_POST['accion'])) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);

    $usuarios = [
        'admin' => [
            'nombre'=>'Administrador','apellido'=>'Sistema',
            'correo'=>'admin@vivimostodos.com',
            'cedula'=>'1000000000','telefono'=>'3000000000','rol'=>'ADMIN'
        ],
        'supervisor' => [
            'nombre'=>'Carlos','apellido'=>'Supervisor',
            'correo'=>'supervisor@vivimostodos.com',
            'cedula'=>'1000000001','telefono'=>'3001111111','rol'=>'SUPERVISOR'
        ],
        'residente' => [
            'nombre'=>'María','apellido'=>'García',
            'correo'=>'residente@vivimostodos.com',
            'cedula'=>'1000000002','telefono'=>'3002222222','rol'=>'RESIDENTE'
        ],
    ];

    $objetivo = $_POST['accion']; // 'todos', 'admin', 'supervisor', 'residente'
    $lista = $objetivo === 'todos' ? array_keys($usuarios) : [$objetivo];

    foreach ($lista as $key) {
        $u = $usuarios[$key];
        // Verificar si existe
        $existe = $db->prepare("SELECT id_usuario FROM usuarios WHERE correo=?");
        $existe->execute([$u['correo']]);
        $fila = $existe->fetch();

        if ($fila) {
            // Actualizar password y estado
            $db->prepare("UPDATE usuarios SET password=?, estado='ACTIVO', nombre=?, apellido=?, rol=? WHERE correo=?")
               ->execute([$hash, $u['nombre'], $u['apellido'], $u['rol'], $u['correo']]);
            echo "🔄 Actualizado: <strong>{$u['correo']}</strong> → contraseña: admin123<br>";
        } else {
            // Insertar
            try {
                $db->prepare("INSERT INTO usuarios (nombre,apellido,correo,password,cedula,telefono,rol,estado) VALUES (?,?,?,?,?,?,?,'ACTIVO')")
                   ->execute([$u['nombre'],$u['apellido'],$u['correo'],$hash,$u['cedula'],$u['telefono'],$u['rol']]);
                echo "✅ Creado: <strong>{$u['correo']}</strong> → contraseña: admin123<br>";
            } catch(Exception $e) {
                echo "❌ Error con {$u['correo']}: " . $e->getMessage() . "<br>";
            }
        }
    }
    echo "<br>";
}

// Mostrar tabla de usuarios actual
echo "<h3>📋 Usuarios actuales:</h3>";
$usuarios = $db->query("SELECT id_usuario, nombre, apellido, correo, rol, estado FROM usuarios ORDER BY id_usuario")->fetchAll();

echo "<table border='1' cellpadding='8' style='border-collapse:collapse;width:100%;font-size:14px'>
<tr style='background:#1a3c5e;color:#fff'>
    <th>ID</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th>
</tr>";
foreach ($usuarios as $u) {
    $bg = $u['estado'] === 'ACTIVO' ? '#f0fff4' : '#fff5f5';
    echo "<tr style='background:$bg'>
        <td>{$u['id_usuario']}</td>
        <td>{$u['nombre']} {$u['apellido']}</td>
        <td>{$u['correo']}</td>
        <td><strong>{$u['rol']}</strong></td>
        <td>{$u['estado']}</td>
    </tr>";
}
if (empty($usuarios)) echo "<tr><td colspan='5' style='text-align:center'>Sin usuarios</td></tr>";
echo "</table><br>";

// Botones
$style = "padding:10px 18px;color:#fff;border:none;border-radius:6px;cursor:pointer;margin:4px;font-size:14px";
echo "<h3>🔧 Acciones:</h3>";
echo "<form method='POST'>
    <button name='accion' value='todos' style='background:#1a3c5e;$style'>👥 Crear/Reparar TODOS los usuarios</button>
    <button name='accion' value='admin' style='background:#7c3aed;$style'>🔑 Reparar ADMIN</button>
    <button name='accion' value='supervisor' style='background:#0891b2;$style'>🔍 Crear/Reparar SUPERVISOR</button>
    <button name='accion' value='residente' style='background:#16a34a;$style'>🏠 Crear/Reparar RESIDENTE</button>
</form>";

echo "<br><hr>
<h3>🔑 Credenciales (después de usar este script):</h3>
<table border='1' cellpadding='8' style='border-collapse:collapse'>
<tr style='background:#f0f4f8'><th>Rol</th><th>Correo</th><th>Contraseña</th></tr>
<tr><td>ADMIN</td><td>admin@vivimostodos.com</td><td><strong>admin123</strong></td></tr>
<tr><td>SUPERVISOR</td><td>supervisor@vivimostodos.com</td><td><strong>admin123</strong></td></tr>
<tr><td>RESIDENTE</td><td>residente@vivimostodos.com</td><td><strong>admin123</strong></td></tr>
</table><br>

<a href='/vivimostodos/index.php' style='background:#2563a8;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold'>
    👉 Ir al Login
</a>

<br><br><span style='color:red;font-weight:bold'>⚠️ Elimina este archivo usuarios_prueba.php después de usarlo</span>";
?>
