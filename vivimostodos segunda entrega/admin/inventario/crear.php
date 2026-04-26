<?php
/**
 * admin/inventario/crear.php — PUNTO 4
 */
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/session.php';
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

$db = getDB();
$pageTitle = 'Agregar Insumo';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre      = trim($_POST['nombre']      ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $total       = (int)($_POST['cantidad_total']      ?? 0);
    $disponible  = (int)($_POST['cantidad_disponible'] ?? 0);
    $precio      = (float)str_replace(['.', ','], ['', '.'], $_POST['precio_unitario'] ?? '0');

    // Validaciones
    if (!$nombre)            $errors[] = 'El nombre del insumo es requerido.';
    if (strlen($nombre) > 100) $errors[] = 'El nombre no puede superar 100 caracteres.';
    if ($total < 0)          $errors[] = 'La cantidad total debe ser mayor o igual a 0.';
    if ($disponible < 0)     $errors[] = 'La cantidad disponible no puede ser negativa.';
    if ($disponible > $total) $errors[] = 'La cantidad disponible no puede ser mayor que la cantidad total.';
    if ($precio < 0)         $errors[] = 'El precio no puede ser negativo.';

    // Verificar nombre duplicado
    if (empty($errors)) {
        $dup = $db->prepare("SELECT COUNT(*) FROM inventario WHERE nombre = ?");
        $dup->execute([$nombre]);
        if ($dup->fetchColumn() > 0) {
            $errors[] = "Ya existe un insumo con el nombre \"$nombre\".";
        }
    }

    if (empty($errors)) {
        $db->prepare(
            "INSERT INTO inventario (nombre, descripcion, cantidad_total, cantidad_disponible, precio_unitario, estado)
             VALUES (?, ?, ?, ?, ?, 'DISPONIBLE')"
        )->execute([$nombre, $descripcion, $total, $disponible, $precio]);

        flashMessage('success', "✅ Insumo <strong>$nombre</strong> creado exitosamente.");
        redirect('/vivimostodos/admin/inventario/index.php');
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
                <h5>📦 Agregar Nuevo Insumo</h5>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">← Volver</a>
            </div>
            <div class="card-body">
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

                <form method="POST" id="form-insumo" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Nombre del insumo *</label>
                        <input type="text" name="nombre" class="form-control"
                            placeholder="ej: Sillas plásticas, Video Beam, Mesas..."
                            value="<?= sanitize($_POST['nombre'] ?? '') ?>"
                            maxlength="100" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2"
                            placeholder="Características, color, tamaño, marca..."><?= sanitize($_POST['descripcion'] ?? '') ?></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Cantidad total *</label>
                            <input type="number" name="cantidad_total" id="cant-total"
                                class="form-control" min="0" value="<?= (int)($_POST['cantidad_total'] ?? 1) ?>"
                                oninput="actualizarDisponible()" required>
                            <small class="text-muted">Unidades existentes</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cantidad disponible *</label>
                            <input type="number" name="cantidad_disponible" id="cant-disp"
                                class="form-control" min="0" value="<?= (int)($_POST['cantidad_disponible'] ?? 1) ?>"
                                oninput="validarDisponible()" required>
                            <small class="text-muted">Listas para prestar</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Precio por uso (COP)</label>
                            <input type="number" name="precio_unitario"
                                class="form-control" min="0" step="100"
                                value="<?= (int)($_POST['precio_unitario'] ?? 0) ?>"
                                placeholder="0 = Gratis">
                            <small class="text-muted">0 = Sin costo</small>
                        </div>
                    </div>

                    <!-- Preview barra de stock -->
                    <div class="mb-4 p-3 rounded" style="background:var(--bg)">
                        <label class="form-label mb-2" style="font-size:.8rem">Vista previa del stock:</label>
                        <div class="d-flex align-items-center gap-3">
                            <div class="flex-grow-1">
                                <div class="progress" style="height:12px;border-radius:6px">
                                    <div class="progress-bar bg-success" id="preview-bar" style="width:100%"></div>
                                </div>
                            </div>
                            <span id="preview-pct" class="fw-bold text-success" style="font-family:var(--font-mono);width:50px">100%</span>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">✅ Guardar Insumo</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = "<script>
function actualizarDisponible() {
    const total = parseInt(document.getElementById('cant-total').value) || 0;
    const disp  = document.getElementById('cant-disp');
    if (parseInt(disp.value) > total) disp.value = total;
    actualizarBar();
}

function validarDisponible() {
    const total = parseInt(document.getElementById('cant-total').value) || 0;
    const disp  = parseInt(document.getElementById('cant-disp').value) || 0;
    if (disp > total) {
        document.getElementById('cant-disp').value = total;
    }
    actualizarBar();
}

function actualizarBar() {
    const total = parseInt(document.getElementById('cant-total').value) || 0;
    const disp  = parseInt(document.getElementById('cant-disp').value) || 0;
    const pct   = total > 0 ? Math.round((disp/total)*100) : 0;
    const bar   = document.getElementById('preview-bar');
    const lbl   = document.getElementById('preview-pct');
    bar.style.width = pct + '%';
    lbl.textContent = pct + '%';
    const color = pct < 20 ? 'bg-danger' : pct < 50 ? 'bg-warning' : 'bg-success';
    bar.className = 'progress-bar ' + color;
    lbl.className = 'fw-bold ' + (pct < 20 ? 'text-danger' : pct < 50 ? 'text-warning' : 'text-success');
}
actualizarBar();
</script>";
include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
