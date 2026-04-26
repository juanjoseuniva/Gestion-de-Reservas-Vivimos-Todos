<?php
/**
 * api/disponibilidad.php
 * Endpoint AJAX — Verifica disponibilidad de fecha para reserva
 * Validaciones: 48h mínimo, 90 días máximo, sin doble reserva
 */
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/session.php';
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/database.php';
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['disponible' => false, 'mensaje' => 'No autorizado']);
    exit;
}

$fecha = trim($_GET['fecha'] ?? '');

// Validar formato de fecha
if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !checkdate(
    (int)substr($fecha, 5, 2),
    (int)substr($fecha, 8, 2),
    (int)substr($fecha, 0, 4)
)) {
    echo json_encode(['disponible' => false, 'mensaje' => '❌ Formato de fecha inválido.']);
    exit;
}

// ── REGLA 1: Mínimo 48 horas (2 días) ─────────────
$fechaMinima = date('Y-m-d', strtotime('+2 days'));
if ($fecha < $fechaMinima) {
    echo json_encode([
        'disponible'   => false,
        'codigo'       => 'MIN_48H',
        'mensaje'      => '❌ Debe reservar con mínimo 48 horas de anticipación. Fecha mínima: ' . date('d/m/Y', strtotime($fechaMinima)),
        'fecha_minima' => $fechaMinima,
    ]);
    exit;
}

// ── REGLA 2: Máximo 90 días ────────────────────────
$fechaMaxima = date('Y-m-d', strtotime('+90 days'));
if ($fecha > $fechaMaxima) {
    echo json_encode([
        'disponible'   => false,
        'codigo'       => 'MAX_90D',
        'mensaje'      => '❌ No puede reservar con más de 90 días de anticipación. Fecha máxima: ' . date('d/m/Y', strtotime($fechaMaxima)),
        'fecha_maxima' => $fechaMaxima,
    ]);
    exit;
}

// ── REGLA 3: Sin doble reserva en el mismo día ─────
$db   = getDB();
$stmt = $db->prepare(
    "SELECT r.id_reserva, r.estado, u.nombre, u.apellido
     FROM reservas r JOIN usuarios u ON r.id_usuario = u.id_usuario
     WHERE r.fecha_evento = ? AND r.estado IN ('PENDIENTE','APROBADA')
     LIMIT 1"
);
$stmt->execute([$fecha]);
$reservaExistente = $stmt->fetch();

if ($reservaExistente) {
    echo json_encode([
        'disponible' => false,
        'codigo'     => 'FECHA_OCUPADA',
        'mensaje'    => '❌ Esta fecha ya tiene una reserva activa (' . $reservaExistente['estado'] . '). El salón solo puede reservarse una vez por día.',
    ]);
    exit;
}

// ── TODO OK: Fecha disponible ──────────────────────
echo json_encode([
    'disponible' => true,
    'codigo'     => 'OK',
    'mensaje'    => '✓ Fecha disponible para reservar.',
    'fecha'      => date('d/m/Y', strtotime($fecha)),
]);
