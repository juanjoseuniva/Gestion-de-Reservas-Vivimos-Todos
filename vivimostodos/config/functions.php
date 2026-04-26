<?php
require_once __DIR__ . '/database.php';

function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function flashMessage(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';
    $type = $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'danger' : $flash['type']);
    return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
        {$flash['msg']}
        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
    </div>";
}

function crearNotificacion(int $idUsuario, string $mensaje): void {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notificaciones (id_usuario, mensaje) VALUES (?, ?)");
    $stmt->execute([$idUsuario, $mensaje]);
}

function notificarTodos(string $mensaje): void {
    $db = getDB();
    $stmt = $db->query("SELECT id_usuario FROM usuarios WHERE estado = 'ACTIVO'");
    $usuarios = $stmt->fetchAll();
    foreach ($usuarios as $u) {
        crearNotificacion($u['id_usuario'], $mensaje);
    }
}

function contarNotificacionesNoLeidas(int $idUsuario): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notificaciones WHERE id_usuario = ? AND leido = 0");
    $stmt->execute([$idUsuario]);
    return (int)$stmt->fetchColumn();
}

function formatFecha(string $fecha): string {
    return date('d/m/Y', strtotime($fecha));
}

function formatMoneda(float $monto): string {
    return '$' . number_format($monto, 0, ',', '.');
}

function fechaDisponible(string $fecha): bool {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM reservas 
         WHERE fecha_evento = ? AND estado IN ('PENDIENTE','APROBADA')"
    );
    $stmt->execute([$fecha]);
    return (int)$stmt->fetchColumn() === 0;
}

function uploadComprobante(array $file, int $idReserva): string|false {
    $dir = __DIR__ . '/../assets/uploads/comprobantes/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array($ext, $allowed)) return false;
    $nombre = 'pago_' . $idReserva . '_' . time() . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $dir . $nombre)) {
        return $nombre;
    }
    return false;
}
