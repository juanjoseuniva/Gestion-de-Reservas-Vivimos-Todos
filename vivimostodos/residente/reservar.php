<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['RESIDENTE']);

$db = getDB();
$pageTitle = 'Nueva Reserva';
$errors = [];

// Verificar que el residente no tenga reservas pendientes/aprobadas futuras
$uid = currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha       = $_POST['fecha_evento'] ?? '';
    $hora_inicio = $_POST['hora_inicio'] ?? '12:00';
    $hora_fin    = $_POST['hora_fin']    ?? '23:59';
    $observaciones = trim($_POST['observaciones'] ?? '');
    $insumos     = $_POST['insumos'] ?? [];
    $cantidades  = $_POST['cantidades'] ?? [];

    if (!$fecha) $errors[] = 'La fecha del evento es requerida.';
    elseif ($fecha < date('Y-m-d')) $errors[] = 'No puedes reservar en una fecha pasada.';
    elseif (!fechaDisponible($fecha)) $errors[] = '❌ Esta fecha ya tiene una reserva activa. Por favor elige otra fecha.';

    if (empty($errors)) {
        $db->beginTransaction();
        try {
            // Calcular total
            $total = 0;
            $detallesPrep = [];
            foreach ($insumos as $key => $idInsumo) {
                $idInsumo = (int)$idInsumo;
                $cant     = (int)($cantidades[$key] ?? 0);
                if ($idInsumo && $cant > 0) {
                    $ins = $db->prepare("SELECT * FROM inventario WHERE id_insumo=? AND estado='DISPONIBLE'");
                    $ins->execute([$idInsumo]);
                    $ins = $ins->fetch();
                    if ($ins) {
                        if ($cant > $ins['cantidad_disponible']) {
                            $errors[] = "Stock insuficiente de '{$ins['nombre']}'. Disponible: {$ins['cantidad_disponible']}";
                        } else {
                            $subtotal = $cant * $ins['precio_unitario'];
                            $total += $subtotal;
                            $detallesPrep[] = ['id_insumo'=>$idInsumo,'cantidad'=>$cant,'subtotal'=>$subtotal,'precio'=>$ins['precio_unitario']];
                        }
                    }
                }
            }
            if (!empty($errors)) { $db->rollBack(); }
            else {
                // Crear reserva
                $stmt = $db->prepare(
                    "INSERT INTO reservas (id_usuario,fecha_evento,hora_inicio,hora_fin,observaciones) VALUES (?,?,?,?,?)"
                );
                $stmt->execute([$uid,$fecha,$hora_inicio,$hora_fin,$observaciones]);
                $idReserva = $db->lastInsertId();

                // Detalles e inventario
                foreach ($detallesPrep as $det) {
                    $db->prepare("INSERT INTO detalle_reserva (id_reserva,id_insumo,cantidad,subtotal) VALUES (?,?,?,?)")
                       ->execute([$idReserva,$det['id_insumo'],$det['cantidad'],$det['subtotal']]);
                    $db->prepare("UPDATE inventario SET cantidad_disponible=cantidad_disponible-? WHERE id_insumo=?")
                       ->execute([$det['cantidad'],$det['id_insumo']]);
                }

                // Crear pago si hay monto
                if ($total > 0) {
                    $db->prepare("INSERT INTO pagos (id_reserva,monto) VALUES (?,?)")->execute([$idReserva,$total]);
                }

                // Notificar admin
                $admins = $db->query("SELECT id_usuario FROM usuarios WHERE rol='ADMIN' AND estado='ACTIVO'")->fetchAll();
                $user = currentUser();
                foreach ($admins as $adm) {
                    crearNotificacion($adm['id_usuario'], "📅 Nueva reserva #{$idReserva} de {$user['nombre']} {$user['apellido']} para el " . formatFecha($fecha));
                }
                crearNotificacion($uid, "✅ Tu reserva #{$idReserva} para el " . formatFecha($fecha) . " fue enviada. Espera aprobación.");

                $db->commit();
                flashMessage('success',"Reserva #{$idReserva} creada correctamente. El administrador revisará tu solicitud." . ($total > 0 ? " Total a pagar: " . formatMoneda($total) : ""));
                redirect('/vivimostodos/residente/mis_reservas.php');
            }
        } catch(Exception $e) {
            $db->rollBack();
            $errors[] = 'Error al procesar la reserva: ' . $e->getMessage();
        }
    }
}

$insumos_disponibles = $db->query("SELECT * FROM inventario WHERE estado='DISPONIBLE' AND cantidad_disponible>0 ORDER BY nombre")->fetchAll();

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<?= renderFlash() ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card animate-in">
            <div class="card-header"><h5>📅 Solicitar Reserva del Salón Social</h5></div>
            <div class="card-body">
                <?php if ($errors): ?>
                <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>

                <form method="POST" id="form-reserva">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Fecha del evento *</label>
                            <input type="date" name="fecha_evento" id="fecha-input" class="form-control"
                                min="<?= date('Y-m-d') ?>"
                                value="<?= sanitize($_POST['fecha_evento'] ?? '') ?>" required>
                            <div id="disponibilidad-msg" class="mt-1" style="font-size:.82rem"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Hora inicio</label>
                            <input type="time" name="hora_inicio" class="form-control" value="<?= $_POST['hora_inicio'] ?? '12:00' ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Hora fin</label>
                            <input type="time" name="hora_fin" class="form-control" value="<?= $_POST['hora_fin'] ?? '23:59' ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones / Descripción del evento</label>
                            <textarea name="observaciones" class="form-control" rows="3" placeholder="Tipo de evento, número aproximado de personas, requerimientos especiales..."><?= sanitize($_POST['observaciones'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Insumos -->
                    <?php if ($insumos_disponibles): ?>
                    <hr class="my-4">
                    <h6 class="mb-3">📦 Insumos Adicionales <small class="text-muted fw-normal">(opcional)</small></h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Seleccionar</th><th>Insumo</th><th>Disponible</th><th>Precio/U</th><th>Cantidad</th><th>Subtotal</th></tr></thead>
                            <tbody>
                            <?php foreach ($insumos_disponibles as $idx => $ins): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input insumo-check" data-idx="<?= $idx ?>"
                                        data-precio="<?= $ins['precio_unitario'] ?>" onchange="toggleInsumo(this)">
                                    <input type="hidden" name="insumos[]" value="" id="insumo-id-<?= $idx ?>">
                                </td>
                                <td><?= sanitize($ins['nombre']) ?><br><small class="text-muted"><?= sanitize($ins['descripcion']) ?></small></td>
                                <td><?= $ins['cantidad_disponible'] ?></td>
                                <td><?= $ins['precio_unitario'] > 0 ? formatMoneda((float)$ins['precio_unitario']) : 'Gratis' ?></td>
                                <td>
                                    <input type="number" name="cantidades[]" id="cant-<?= $idx ?>" class="form-control form-control-sm"
                                        min="1" max="<?= $ins['cantidad_disponible'] ?>" value="0" style="width:80px"
                                        onchange="calcSubtotal(<?= $idx ?>, <?= $ins['precio_unitario'] ?>)" disabled>
                                    <input type="hidden" id="insumo-real-id-<?= $idx ?>" value="<?= $ins['id_insumo'] ?>">
                                </td>
                                <td id="sub-<?= $idx ?>" style="font-family:var(--font-mono)">$0</td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-active fw-bold">
                                    <td colspan="5" class="text-end">TOTAL INSUMOS:</td>
                                    <td id="total-insumos" style="font-family:var(--font-mono)">$0</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>

                    <div class="alert alert-info mt-3" style="font-size:.83rem">
                        <strong>ℹ️ Importante:</strong> La reserva quedará en estado PENDIENTE hasta ser aprobada por el administrador.
                        Si hay insumos con costo, deberás subir el comprobante de pago después.
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">📅 Enviar Solicitud de Reserva</button>
                        <a href="mis_reservas.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = "<script>
initCalendar('fecha-input');
let total = 0;
const precios = {};
const cantidades = {};

function toggleInsumo(cb) {
    const idx = cb.dataset.idx;
    const cantInput = document.getElementById('cant-'+idx);
    const idInput = document.getElementById('insumo-id-'+idx);
    const realId = document.getElementById('insumo-real-id-'+idx).value;
    if (cb.checked) {
        cantInput.disabled = false;
        cantInput.value = 1;
        idInput.value = realId;
        calcSubtotal(idx, cb.dataset.precio);
    } else {
        cantInput.disabled = true;
        cantInput.value = 0;
        idInput.value = '';
        document.getElementById('sub-'+idx).textContent = '\$0';
        calcTotal();
    }
}

function calcSubtotal(idx, precio) {
    const cant = parseInt(document.getElementById('cant-'+idx).value) || 0;
    const sub = cant * parseFloat(precio);
    document.getElementById('sub-'+idx).textContent = '\$' + sub.toLocaleString('es-CO');
    precios[idx] = sub;
    calcTotal();
}

function calcTotal() {
    const sum = Object.values(precios).reduce((a,b) => a+b, 0);
    document.getElementById('total-insumos').textContent = '\$' + sum.toLocaleString('es-CO');
}
</script>";
include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
