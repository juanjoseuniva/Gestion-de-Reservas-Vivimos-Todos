<?php
/**
 * residente/reservar.php — PUNTOS 5 y 7
 * Con validaciones: 48h mínimo, 90 días máximo, sin doble reserva
 */
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/session.php';
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/functions.php';
requireRole(['RESIDENTE']);

$db  = getDB();
$uid = currentUserId();
$pageTitle = 'Nueva Reserva';
$errors = [];

// Calcular fechas límite para el frontend
$fechaMinima = date('Y-m-d', strtotime('+2 days'));
$fechaMaxima = date('Y-m-d', strtotime('+90 days'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha         = $_POST['fecha_evento']  ?? '';
    $observaciones = trim($_POST['observaciones'] ?? '');
    $insumos_ids   = $_POST['insumos']       ?? [];
    $cantidades    = $_POST['cantidades']    ?? [];

    // ── VALIDACIONES DE FECHA (PUNTO 7) ───────────
    if (!$fecha) {
        $errors[] = 'La fecha del evento es requerida.';
    } else {
        $validacion = validarFechaCompleta($fecha);
        if (!$validacion['valida']) {
            $errors[] = $validacion['mensaje'];
        }
    }

    if (empty($errors)) {
        $db->beginTransaction();
        try {
            // Verificar y calcular insumos
            $detallesPrep = [];
            $totalCosto   = 0;

            foreach ($insumos_ids as $key => $idInsumo) {
                $idInsumo = (int)$idInsumo;
                $cant     = (int)($cantidades[$key] ?? 0);
                if ($idInsumo <= 0 || $cant <= 0) continue;

                $ins = $db->prepare(
                    "SELECT * FROM inventario WHERE id_insumo=? AND estado='DISPONIBLE'"
                );
                $ins->execute([$idInsumo]);
                $ins = $ins->fetch();

                if (!$ins) {
                    $errors[] = "Insumo #{$idInsumo} no disponible.";
                    continue;
                }
                if ($cant > $ins['cantidad_disponible']) {
                    $errors[] = "'{$ins['nombre']}': solicitadas $cant, disponibles {$ins['cantidad_disponible']}.";
                    continue;
                }

                $subtotal       = $cant * (float)$ins['precio_unitario'];
                $totalCosto    += $subtotal;
                $detallesPrep[] = [
                    'id_insumo'  => $idInsumo,
                    'cantidad'   => $cant,
                    'subtotal'   => $subtotal,
                    'nombre'     => $ins['nombre'],
                ];
            }

            if (!empty($errors)) {
                $db->rollBack();
            } else {
                // 1. Crear reserva (inventario NO se descuenta aquí, solo al aprobar)
                $db->prepare(
                    "INSERT INTO reservas (id_usuario, fecha_evento, hora_inicio, hora_fin, observaciones, estado)
                     VALUES (?, ?, '12:00:00', '23:59:00', ?, 'PENDIENTE')"
                )->execute([$uid, $fecha, $observaciones]);
                $idReserva = $db->lastInsertId();

                // 2. Guardar detalles de insumos
                foreach ($detallesPrep as $det) {
                    $db->prepare(
                        "INSERT INTO detalle_reserva (id_reserva, id_insumo, cantidad, subtotal)
                         VALUES (?, ?, ?, ?)"
                    )->execute([$idReserva, $det['id_insumo'], $det['cantidad'], $det['subtotal']]);
                }

                // 3. Crear registro de pago pendiente si hay costo
                if ($totalCosto > 0) {
                    $db->prepare(
                        "INSERT INTO pagos (id_reserva, monto, estado) VALUES (?, ?, 'PENDIENTE')"
                    )->execute([$idReserva, $totalCosto]);
                }

                // 4. Notificar al admin
                $user = currentUser();
                notificarAdmins(
                    "📅 Nueva reserva #{$idReserva} de {$user['nombre']} {$user['apellido']} " .
                    "para el " . formatFecha($fecha) .
                    ($totalCosto > 0 ? " — Total: " . formatMoneda($totalCosto) : " — Sin costo")
                );

                // 5. Notificar al residente
                crearNotificacion($uid,
                    "📬 Tu solicitud de reserva #{$idReserva} para el " . formatFecha($fecha) .
                    " fue enviada. El administrador la revisará pronto."
                );

                $db->commit();

                $msg = "✅ Reserva #{$idReserva} creada para el <strong>" . formatFecha($fecha) . "</strong>. ";
                $msg .= "Espera la aprobación del administrador.";
                if ($totalCosto > 0) $msg .= " Total a pagar si es aprobada: <strong>" . formatMoneda($totalCosto) . "</strong>.";

                flashMessage('success', $msg);
                redirect('/vivimostodos/residente/mis_reservas.php');
            }
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Error al crear la reserva: ' . $e->getMessage();
        }
    }
}

// Insumos disponibles
$insumos = $db->query(
    "SELECT * FROM inventario WHERE estado='DISPONIBLE' AND cantidad_disponible > 0 ORDER BY nombre"
)->fetchAll();

// Fechas con reserva activa para bloquear en calendario
$reservasActivas = $db->query(
    "SELECT DISTINCT fecha_evento FROM reservas
     WHERE estado IN ('PENDIENTE','APROBADA')
     AND fecha_evento >= CURDATE()"
)->fetchAll(PDO::FETCH_COLUMN);

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<?= renderFlash() ?>

<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="card animate-in">
            <div class="card-header">
                <h5>📅 Solicitar Reserva del Salón Social</h5>
            </div>
            <div class="card-body">

                <!-- Reglas de negocio -->
                <div class="alert alert-info mb-4" style="font-size:.85rem">
                    <strong>📋 Reglas de reserva:</strong>
                    <ul class="mb-0 mt-1 ps-3">
                        <li>Mínimo <strong>48 horas</strong> de anticipación (desde <?= formatFecha($fechaMinima) ?>)</li>
                        <li>Máximo <strong>90 días</strong> de anticipación (hasta <?= formatFecha($fechaMaxima) ?>)</li>
                        <li>Solo <strong>una reserva por día</strong> — El salón opera de 12:00 a 23:59</li>
                        <li>La reserva queda en estado <strong>PENDIENTE</strong> hasta ser aprobada por el administrador</li>
                    </ul>
                </div>

                <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <strong>Por favor corrige los siguientes errores:</strong>
                    <ul class="mb-0 mt-2 ps-3">
                        <?php foreach ($errors as $e): ?>
                        <li><?= sanitize($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" id="form-reserva" novalidate>
                    <div class="row g-3 mb-4">
                        <div class="col-md-5">
                            <label class="form-label">Fecha del evento *</label>
                            <input type="date" name="fecha_evento" id="fecha-input"
                                class="form-control"
                                min="<?= $fechaMinima ?>"
                                max="<?= $fechaMaxima ?>"
                                value="<?= sanitize($_POST['fecha_evento'] ?? '') ?>"
                                required>

                            <!-- Mensaje disponibilidad AJAX -->
                            <div id="msg-disponibilidad" class="mt-2" style="font-size:.83rem;display:none"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Hora inicio</label>
                            <input type="time" class="form-control" value="12:00" readonly
                                style="background:var(--bg);cursor:not-allowed">
                            <small class="text-muted">Fijo: mediodía</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Hora fin</label>
                            <input type="time" class="form-control" value="23:59" readonly
                                style="background:var(--bg);cursor:not-allowed">
                            <small class="text-muted">Fijo: medianoche</small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Descripción del evento <small class="text-muted">(opcional)</small></label>
                        <textarea name="observaciones" class="form-control" rows="2"
                            placeholder="Tipo de evento, número aproximado de invitados, requerimientos especiales..."><?= sanitize($_POST['observaciones'] ?? '') ?></textarea>
                    </div>

                    <!-- Insumos -->
                    <?php if ($insumos): ?>
                    <hr>
                    <h6 class="mb-3">📦 Insumos del Salón <small class="text-muted fw-normal">(opcional — selecciona lo que necesitas)</small></h6>

                    <div class="table-responsive">
                        <table class="table table-sm" id="tabla-insumos">
                            <thead>
                                <tr>
                                    <th style="width:44px">Sel.</th>
                                    <th>Insumo</th>
                                    <th>Disponible</th>
                                    <th>Precio/U</th>
                                    <th style="width:110px">Cantidad</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($insumos as $idx => $ins): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input chk-insumo"
                                        data-idx="<?= $idx ?>"
                                        data-precio="<?= $ins['precio_unitario'] ?>"
                                        data-max="<?= $ins['cantidad_disponible'] ?>"
                                        onchange="toggleInsumo(this)">
                                    <input type="hidden" name="insumos[]"
                                        id="hid-id-<?= $idx ?>" value="">
                                </td>
                                <td>
                                    <strong><?= sanitize($ins['nombre']) ?></strong>
                                    <?php if ($ins['descripcion']): ?>
                                    <br><small class="text-muted"><?= sanitize($ins['descripcion']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-bold text-<?= $ins['cantidad_disponible'] < 5 ? 'danger' : 'success' ?>">
                                        <?= $ins['cantidad_disponible'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?= $ins['precio_unitario'] > 0
                                        ? '<span class="text-primary fw-semibold">' . formatMoneda((float)$ins['precio_unitario']) . '</span>'
                                        : '<span class="badge bg-success">Gratis</span>' ?>
                                </td>
                                <td>
                                    <input type="number" name="cantidades[]"
                                        id="cant-<?= $idx ?>" class="form-control form-control-sm"
                                        min="1" max="<?= $ins['cantidad_disponible'] ?>"
                                        value="0" disabled
                                        oninput="calcSubtotal(<?= $idx ?>, <?= $ins['precio_unitario'] ?>)"
                                        style="width:90px">
                                    <input type="hidden" id="real-id-<?= $idx ?>" value="<?= $ins['id_insumo'] ?>">
                                </td>
                                <td id="sub-<?= $idx ?>" class="fw-semibold" style="font-family:var(--font-mono)">
                                    —
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-active">
                                    <td colspan="5" class="text-end fw-bold">TOTAL INSUMOS:</td>
                                    <td id="total-insumos" class="fw-bold text-primary" style="font-family:var(--font-mono)">$0</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">No hay insumos disponibles en este momento.</div>
                    <?php endif; ?>

                    <div class="d-flex gap-3 mt-4 pt-3 border-top align-items-center">
                        <button type="submit" class="btn btn-primary px-4" id="btn-enviar" disabled>
                            📅 Enviar Solicitud de Reserva
                        </button>
                        <a href="mis_reservas.php" class="btn btn-outline-secondary">Cancelar</a>
                        <span id="msg-btn" class="text-muted" style="font-size:.82rem">
                            Selecciona una fecha válida para continuar
                        </span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$fechasOcupadas = json_encode($reservasActivas);
$extraJs = "<script>
const fechasOcupadas = $fechasOcupadas;
const subtotales = {};

// ── VALIDACIÓN AJAX DE FECHA ──────────────────────
const inputFecha = document.getElementById('fecha-input');
const msgDisp    = document.getElementById('msg-disponibilidad');
const btnEnviar  = document.getElementById('btn-enviar');
const msgBtn     = document.getElementById('msg-btn');

inputFecha.addEventListener('change', async () => {
    const fecha = inputFecha.value;
    if (!fecha) return resetFecha();

    msgDisp.style.display = 'block';
    msgDisp.className = 'mt-2 text-muted';
    msgDisp.textContent = '⏳ Verificando disponibilidad...';

    try {
        const res  = await fetch('/vivimostodos/api/disponibilidad.php?fecha=' + fecha);
        const data = await res.json();

        if (data.disponible) {
            msgDisp.className = 'mt-2 fw-semibold text-success';
            msgDisp.textContent = data.mensaje;
            btnEnviar.disabled = false;
            msgBtn.textContent = '';
        } else {
            msgDisp.className = 'mt-2 fw-semibold text-danger';
            msgDisp.textContent = data.mensaje;
            btnEnviar.disabled = true;
            msgBtn.textContent = 'Elige otra fecha para continuar';
            if (data.codigo !== 'MIN_48H' && data.codigo !== 'MAX_90D') {
                inputFecha.value = '';
            }
        }
    } catch(e) {
        msgDisp.className = 'mt-2 text-warning';
        msgDisp.textContent = '⚠️ Error verificando disponibilidad. Intenta de nuevo.';
        btnEnviar.disabled = true;
    }
});

function resetFecha() {
    msgDisp.style.display = 'none';
    btnEnviar.disabled = true;
    msgBtn.textContent = 'Selecciona una fecha válida para continuar';
}

// ── INSUMOS ───────────────────────────────────────
function toggleInsumo(cb) {
    const idx    = cb.dataset.idx;
    const cantEl = document.getElementById('cant-' + idx);
    const hidEl  = document.getElementById('hid-id-' + idx);
    const realId = document.getElementById('real-id-' + idx).value;

    if (cb.checked) {
        cantEl.disabled = false;
        cantEl.value    = 1;
        hidEl.value     = realId;
        calcSubtotal(idx, cb.dataset.precio);
    } else {
        cantEl.disabled = true;
        cantEl.value    = 0;
        hidEl.value     = '';
        subtotales[idx] = 0;
        document.getElementById('sub-' + idx).textContent = '—';
        actualizarTotal();
    }
}

function calcSubtotal(idx, precio) {
    const cant = parseInt(document.getElementById('cant-' + idx).value) || 0;
    const sub  = cant * parseFloat(precio);
    subtotales[idx] = sub;
    document.getElementById('sub-' + idx).textContent =
        precio > 0 ? '\$' + sub.toLocaleString('es-CO') : 'Gratis';
    actualizarTotal();
}

function actualizarTotal() {
    const sum = Object.values(subtotales).reduce((a,b) => a+b, 0);
    document.getElementById('total-insumos').textContent = '\$' + sum.toLocaleString('es-CO');
}
</script>";
include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
