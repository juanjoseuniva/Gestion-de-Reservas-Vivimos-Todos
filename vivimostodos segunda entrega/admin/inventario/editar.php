<?php
/**
 * admin/inventario/editar.php — PUNTO 4
 */
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/session.php';
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

$db     = getDB();
$pageTitle = 'Editar Insumo';
$id     = (int)($_GET['id'] ?? 0);
$errors = [];

$stmt = $db->prepare("SELECT * FROM inventario WHERE id_insumo = ?");
$stmt->execute([$id]);
$insumo = $stmt->fetch();
if (!$insumo) {
    flashMessage('error', 'Insumo no encontrado.');
    redirect('/vivimostodos/admin/inventario/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre      = trim($_POST['nombre']      ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $total       = (int)($_POST['cantidad_total']      ?? 0);
    $disponible  = (int)($_POST['cantidad_disponible'] ?? 0);
    $precio      = (float)str_replace(['.', ','], ['', '.'], $_POST['precio_unitario'] ?? '0');
    $estado      = in_array($_POST['estado'] ?? '', ['DISPONIBLE', 'NO DISPONIBLE'])
                   ? $_POST['estado'] : 'DISPONIBLE';

    if (!$nombre)             $errors[] = 'El nombre es requerido.';
    if ($total < 0)           $errors[] = 'La cantidad total debe ser >= 0.';
    if ($disponible < 0)      $errors[] = 'La cantidad disponible no puede ser negativa.';
    if ($disponible > $total) $errors[] = 'La cantidad disponible no puede superar la total.';
    if ($precio < 0)          $errors[] = 'El precio no puede ser negativo.';

    // Nombre duplicado (excluyendo este)
    if (empty($errors)) {
        $dup = $db->prepare("SELECT COUNT(*) FROM inventario WHERE nombre=? AND id_insumo!=?");
        $dup->execute([$nombre, $id]);
        if ($dup->fetchColumn() > 0) $errors[] = "Ya existe otro insumo con el nombre \"$nombre\".";
    }

    if (empty($errors)) {
        $db->prepare(
            "UPDATE inventario SET nombre=?, descripcion=?, cantidad_total=?,
             cantidad_disponible=?, precio_unitario=?, estado=? WHERE id_insumo=?"
        )->execute([$nombre, $descripcion, $total, $disponible, $precio, $estado, $id]);

        flashMessage('success', "✅ Insumo <strong>$nombre</strong> actualizado correctamente.");
        redirect('/vivimostodos/admin/inventario/index.php');
    }

    $insumo = array_merge($insumo, compact('nombre','descripcion','estado') + [
        'cantidad_total'=>$total,'cantidad_disponible'=>$disponible,'precio_unitario'=>$precio
    ]);
}

// Insumos comprometidos en reservas activas
$comprometido = $db->prepare(
    "SELECT COALESCE(SUM(dr.cantidad), 0) as total_comprometido
     FROM detalle_reserva dr
     JOIN reservas r ON r.id_reserva = dr.id_reserva
     WHERE dr.id_insumo = ? AND r.estado IN ('PENDIENTE','APROBADA')"
);
$comprometido->execute([$id]);
$cantComprometida = (int)$comprometido->fetchColumn();

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <?= renderFlash() ?>
        <div class="card animate-in">
            <div class="card-header">
                <h5>✏️ Editar Insumo — <?= sanitize($insumo['nombre']) ?></h5>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">← Volver</a>
            </div>
            <div class="card-body">
                <?php if ($cantComprometida > 0): ?>
                <div class="alert alert-warning mb-3" style="font-size:.85rem">
                    ⚠️ <strong><?= $cantComprometida ?> unidad(es)</strong> de este insumo están comprometidas en reservas activas.
                    Ten cuidado al reducir la cantidad disponible.
                </div>
                <?php endif; ?>

                <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3"><?php foreach($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" class="form-control"
                            value="<?= sanitize($insumo['nombre']) ?>" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2"><?= sanitize($insumo['descripcion'] ?? '') ?></textarea>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Cantidad total *</label>
                            <input type="number" name="cantidad_total" id="cant-total"
                                class="form-control" min="0"
                                value="<?= $insumo['cantidad_total'] ?>"
                                oninput="actualizarBar()" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cantidad disponible *</label>
                            <input type="number" name="cantidad_disponible" id="cant-disp"
                                class="form-control" min="0"
                                value="<?= $insumo['cantidad_disponible'] ?>"
                                oninput="actualizarBar()" required>
                            <?php if ($cantComprometida > 0): ?>
                            <small class="text-warning">⚠️ <?= $cantComprometida ?> comprometidas</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Precio/U (COP)</label>
                            <input type="number" name="precio_unitario" class="form-control"
                                min="0" step="100" value="<?= $insumo['precio_unitario'] ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="DISPONIBLE"    <?= $insumo['estado']==='DISPONIBLE'    ? 'selected':'' ?>>✅ DISPONIBLE</option>
                            <option value="NO DISPONIBLE" <?= $insumo['estado']==='NO DISPONIBLE' ? 'selected':'' ?>>🚫 NO DISPONIBLE</option>
                        </select>
                    </div>
                    <!-- Preview stock -->
                    <div class="mb-4 p-3 rounded" style="background:var(--bg)">
                        <div class="d-flex align-items-center gap-3">
                            <div class="flex-grow-1">
                                <div class="progress" style="height:10px">
                                    <div class="progress-bar bg-success" id="preview-bar"
                                         style="width:<?= $insumo['cantidad_total']>0 ? round(($insumo['cantidad_disponible']/$insumo['cantidad_total'])*100) : 0 ?>%"></div>
                                </div>
                            </div>
                            <span id="preview-pct" class="fw-bold" style="font-family:var(--font-mono)">
                                <?= $insumo['cantidad_total']>0 ? round(($insumo['cantidad_disponible']/$insumo['cantidad_total'])*100) : 0 ?>%
                            </span>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = "<script>
function actualizarBar() {
    const total = parseInt(document.getElementById('cant-total').value) || 0;
    const disp  = parseInt(document.getElementById('cant-disp').value) || 0;
    const pct   = total > 0 ? Math.round((disp/total)*100) : 0;
    document.getElementById('preview-bar').style.width = pct + '%';
    document.getElementById('preview-bar').className = 'progress-bar ' + (pct<20?'bg-danger':pct<50?'bg-warning':'bg-success');
    document.getElementById('preview-pct').textContent = pct + '%';
}
</script>";
include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
