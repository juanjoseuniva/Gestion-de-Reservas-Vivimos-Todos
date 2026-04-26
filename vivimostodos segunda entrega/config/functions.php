<?php
require_once __DIR__ . '/database.php';

// ── SANITIZACIÓN ──────────────────────────────────
function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

// ── REDIRECCIÓN ───────────────────────────────────
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

// ── FLASH MESSAGES ────────────────────────────────
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
    $type = match($flash['type']) {
        'success' => 'success',
        'error'   => 'danger',
        'warning' => 'warning',
        'info'    => 'info',
        default   => 'secondary'
    };
    return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
        {$flash['msg']}
        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
    </div>";
}

// ── NOTIFICACIONES ────────────────────────────────
function crearNotificacion(int $idUsuario, string $mensaje): void {
    $db   = getDB();
    $stmt = $db->prepare("INSERT INTO notificaciones (id_usuario, mensaje) VALUES (?, ?)");
    $stmt->execute([$idUsuario, $mensaje]);
}

function notificarTodos(string $mensaje): void {
    $db   = getDB();
    $stmt = $db->query("SELECT id_usuario FROM usuarios WHERE estado = 'ACTIVO'");
    foreach ($stmt->fetchAll() as $u) {
        crearNotificacion($u['id_usuario'], $mensaje);
    }
}

function notificarAdmins(string $mensaje): void {
    $db   = getDB();
    $stmt = $db->query("SELECT id_usuario FROM usuarios WHERE rol='ADMIN' AND estado='ACTIVO'");
    foreach ($stmt->fetchAll() as $u) {
        crearNotificacion($u['id_usuario'], $mensaje);
    }
}

function contarNotificacionesNoLeidas(int $idUsuario): int {
    $db   = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notificaciones WHERE id_usuario=? AND leido=0");
    $stmt->execute([$idUsuario]);
    return (int)$stmt->fetchColumn();
}

// ── FORMATO ───────────────────────────────────────
function formatFecha(string $fecha): string {
    return date('d/m/Y', strtotime($fecha));
}

function formatMoneda(float $monto): string {
    return '$' . number_format($monto, 0, ',', '.');
}

function badgeEstado(string $estado): string {
    $colores = [
        'PENDIENTE' => 'warning text-dark',
        'APROBADA'  => 'success',
        'RECHAZADA' => 'danger',
        'CANCELADA' => 'secondary',
    ];
    return $colores[$estado] ?? 'secondary';
}

// ── VALIDACIONES DE FECHA (PUNTO 7) ───────────────
/**
 * Valida que la fecha cumpla las reglas de negocio:
 * - Mínimo 48 horas de anticipación
 * - Máximo 90 días desde hoy
 * Retorna array ['valida'=>bool, 'mensaje'=>string]
 */
function validarFechaReserva(string $fecha): array {
    $hoy     = new DateTime('today');
    $minima  = (new DateTime('today'))->modify('+2 days');
    $maxima  = (new DateTime('today'))->modify('+90 days');
    $evento  = new DateTime($fecha);

    if ($evento < $minima) {
        $diasFaltan = (int)$hoy->diff($minima)->days;
        return [
            'valida'  => false,
            'mensaje' => "❌ Debe reservar con mínimo 48 horas de anticipación. La fecha más próxima disponible es " . $minima->format('d/m/Y') . "."
        ];
    }

    if ($evento > $maxima) {
        return [
            'valida'  => false,
            'mensaje' => "❌ No puede reservar con más de 90 días de anticipación. La fecha máxima es " . $maxima->format('d/m/Y') . "."
        ];
    }

    return ['valida' => true, 'mensaje' => '✓ Fecha válida'];
}

/**
 * Verifica si ya existe una reserva APROBADA o PENDIENTE en esa fecha
 */
function fechaDisponible(string $fecha, int $excluirReserva = 0): bool {
    $db   = getDB();
    $sql  = "SELECT COUNT(*) FROM reservas WHERE fecha_evento=? AND estado IN ('PENDIENTE','APROBADA')";
    $params = [$fecha];
    if ($excluirReserva > 0) {
        $sql .= " AND id_reserva != ?";
        $params[] = $excluirReserva;
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn() === 0;
}

/**
 * Validación completa de fecha (reglas + disponibilidad)
 */
function validarFechaCompleta(string $fecha): array {
    $reglas = validarFechaReserva($fecha);
    if (!$reglas['valida']) return $reglas;

    if (!fechaDisponible($fecha)) {
        return [
            'valida'  => false,
            'mensaje' => "❌ Esta fecha ya tiene una reserva activa. Por favor elige otra fecha."
        ];
    }

    return ['valida' => true, 'mensaje' => '✓ Fecha disponible y válida'];
}

// ── INVENTARIO ────────────────────────────────────
function getStockColor(int $disponible, int $total): string {
    if ($total === 0) return 'secondary';
    $pct = ($disponible / $total) * 100;
    if ($pct <= 0)  return 'danger';
    if ($pct < 20)  return 'danger';
    if ($pct < 50)  return 'warning';
    return 'success';
}

function stockBajo(int $disponible, int $total): bool {
    if ($total === 0) return false;
    return ($disponible / $total) < 0.20;
}

// ── UPLOAD COMPROBANTE ────────────────────────────
function uploadComprobante(array $file, int $idReserva): string|false {
    $dir = __DIR__ . '/../assets/uploads/comprobantes/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array($ext, $allowed)) return false;
    if ($file['size'] > 5 * 1024 * 1024) return false; // 5MB
    $nombre = 'pago_' . $idReserva . '_' . time() . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $dir . $nombre)) return $nombre;
    return false;
}

// ── SEMÁFORO DE RESERVAS ──────────────────────────
/**
 * Color semáforo según días que faltan para el evento
 */
function colorSemaforo(string $fechaEvento): array {
    $hoy   = new DateTime('today');
    $evento = new DateTime($fechaEvento);
    $diff   = (int)$hoy->diff($evento)->days;
    $pasado = $evento < $hoy;

    if ($pasado)   return ['color' => 'secondary', 'label' => 'Pasado',    'icon' => '⬜'];
    if ($diff <= 1) return ['color' => 'danger',    'label' => 'Hoy/Mañana','icon' => '🔴'];
    if ($diff <= 2) return ['color' => 'danger',    'label' => '<48h',      'icon' => '🔴'];
    if ($diff <= 7) return ['color' => 'warning',   'label' => 'Esta semana','icon' => '🟡'];
    return                  ['color' => 'success',  'label' => 'Con tiempo', 'icon' => '🟢'];
}
