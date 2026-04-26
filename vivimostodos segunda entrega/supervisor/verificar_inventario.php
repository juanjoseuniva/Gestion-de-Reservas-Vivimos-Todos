<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['SUPERVISOR','ADMIN']);

$db = getDB();
$pageTitle = 'Verificar Inventario';
$errors = [];
$success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['cantidad_fisica'] as $id_insumo => $cantidad) {
        $id_insumo = (int)$id_insumo;
        $cantidad  = (int)$cantidad;
        if ($cantidad < 0) { $errors[] = "Cantidad inválida para insumo #{$id_insumo}."; continue; }

        $stmt = $db->prepare("SELECT * FROM inventario WHERE id_insumo=?");
        $stmt->execute([$id_insumo]);
        $ins = $stmt->fetch();
        if (!$ins) continue;

        // Actualizar cantidad disponible según conteo físico
        $db->prepare("UPDATE inventario SET cantidad_disponible=? WHERE id_insumo=?")->execute([$cantidad, $id_insumo]);

        $diff = $cantidad - $ins['cantidad_disponible'];
        if ($diff != 0) {
            $signo = $diff > 0 ? '+' : '';
            $success[] = "'{$ins['nombre']}': ajustado {$signo}{$diff} unidades (de {$ins['cantidad_disponible']} a {$cantidad}).";
        }
    }

    if (empty($errors)) {
        // Notificar admin si hubo diferencias
        if (!empty($success)) {
            $user = currentUser();
            $resumen = implode(' | ', $success);
            $admins = $db->query("SELECT id_usuario FROM usuarios WHERE rol='ADMIN' AND estado='ACTIVO'")->fetchAll();
            foreach ($admins as $adm) {
                crearNotificacion($adm['id_usuario'], "🔍 Supervisor {$user['nombre']} {$user['apellido']} realizó verificación de inventario: $resumen");
            }
        }
        flashMessage('success', empty($success) ? 'Verificación completada. Sin diferencias.' : 'Inventario actualizado con ' . count($success) . ' ajuste(s).');
        redirect('/vivimostodos/supervisor/verificar_inventario.php');
    }
}

$insumos = $db->query("SELECT * FROM inventario ORDER BY nombre")->fetchAll();

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<?= renderFlash() ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
    <ul class="mb-0 ps-3"><?php foreach($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="card animate-in">
    <div class="card-header">
        <h5>🔍 Verificación Física de Inventario</h5>
        <span style="font-size:.8rem;color:var(--text-muted)"><?= date('d/m/Y H:i') ?></span>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-4" style="font-size:.85rem">
            <strong>ℹ️ Instrucciones:</strong> Cuenta físicamente cada insumo e ingresa la cantidad real encontrada.
            El sistema actualizará la disponibilidad y notificará al administrador si hay diferencias.
        </div>

        <form method="POST">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Insumo</th>
                            <th>Descripción</th>
                            <th>Total registrado</th>
                            <th>Sistema dice disponible</th>
                            <th>Conteo físico real</th>
                            <th>Diferencia</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($insumos as $i): ?>
                    <tr>
                        <td><strong><?= sanitize($i['nombre']) ?></strong></td>
                        <td style="color:var(--text-muted);font-size:.82rem"><?= sanitize($i['descripcion']) ?></td>
                        <td class="text-center"><?= $i['cantidad_total'] ?></td>
                        <td class="text-center">
                            <span class="fw-bold <?= $i['cantidad_disponible'] < ($i['cantidad_total']*0.2) ? 'text-danger' : 'text-success' ?>">
                                <?= $i['cantidad_disponible'] ?>
                            </span>
                        </td>
                        <td>
                            <input type="number"
                                name="cantidad_fisica[<?= $i['id_insumo'] ?>]"
                                class="form-control form-control-sm conteo-input"
                                min="0"
                                max="<?= $i['cantidad_total'] ?>"
                                value="<?= $i['cantidad_disponible'] ?>"
                                data-original="<?= $i['cantidad_disponible'] ?>"
                                data-id="<?= $i['id_insumo'] ?>"
                                oninput="calcDiff(this)"
                                style="width:90px">
                        </td>
                        <td>
                            <span id="diff-<?= $i['id_insumo'] ?>" class="fw-bold" style="font-family:var(--font-mono)">0</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex gap-3 align-items-center mt-3">
                <button type="submit" class="btn btn-primary" onclick="return confirm('¿Confirmar verificación y actualizar inventario?')">
                    ✅ Confirmar Verificación
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="resetAll()">↺ Resetear</button>
                <span id="resumen-cambios" class="text-muted" style="font-size:.83rem"></span>
            </div>
        </form>
    </div>
</div>

<!-- Historial de reservas activas con insumos -->
<?php
$reservasActivas = $db->query(
    "SELECT r.id_reserva, r.fecha_evento, r.estado, u.nombre, u.apellido,
            GROUP_CONCAT(i.nombre, ' x', dr.cantidad SEPARATOR ', ') as insumos_usados
     FROM reservas r
     JOIN usuarios u ON r.id_usuario=u.id_usuario
     JOIN detalle_reserva dr ON dr.id_reserva=r.id_reserva
     JOIN inventario i ON i.id_insumo=dr.id_insumo
     WHERE r.estado IN ('APROBADA','PENDIENTE') AND r.fecha_evento >= CURDATE()
     GROUP BY r.id_reserva
     ORDER BY r.fecha_evento ASC"
)->fetchAll();
?>
<?php if ($reservasActivas): ?>
<div class="card animate-in delay-2 mt-3">
    <div class="card-header"><h5>📅 Insumos Comprometidos en Reservas Futuras</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>#</th><th>Fecha</th><th>Residente</th><th>Insumos</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($reservasActivas as $ra):
                    $sc=['PENDIENTE'=>'warning text-dark','APROBADA'=>'success'][$ra['estado']]??'secondary';
                ?>
                <tr>
                    <td style="font-family:var(--font-mono)">#<?= $ra['id_reserva'] ?></td>
                    <td><?= formatFecha($ra['fecha_evento']) ?></td>
                    <td><?= sanitize($ra['nombre']) ?> <?= sanitize($ra['apellido']) ?></td>
                    <td style="font-size:.82rem"><?= sanitize($ra['insumos_usados']) ?></td>
                    <td><span class="badge bg-<?= $sc ?>"><?= $ra['estado'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$extraJs = "<script>
function calcDiff(input) {
    const original = parseInt(input.dataset.original);
    const actual   = parseInt(input.value) || 0;
    const diff     = actual - original;
    const el       = document.getElementById('diff-' + input.dataset.id);
    el.textContent = (diff >= 0 ? '+' : '') + diff;
    el.className   = 'fw-bold ' + (diff > 0 ? 'text-success' : diff < 0 ? 'text-danger' : 'text-muted');
    updateResumen();
}

function updateResumen() {
    const inputs = document.querySelectorAll('.conteo-input');
    let cambios = 0;
    inputs.forEach(i => { if (parseInt(i.value) !== parseInt(i.dataset.original)) cambios++; });
    const el = document.getElementById('resumen-cambios');
    el.textContent = cambios > 0 ? cambios + ' diferencia(s) detectada(s)' : '';
    el.className = cambios > 0 ? 'text-warning fw-semibold' : 'text-muted';
}

function resetAll() {
    document.querySelectorAll('.conteo-input').forEach(i => {
        i.value = i.dataset.original;
        calcDiff(i);
    });
}
</script>";
include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
