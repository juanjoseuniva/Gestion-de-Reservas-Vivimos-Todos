<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

$db = getDB();
$pageTitle = 'Agregar Insumo';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $total       = (int)($_POST['cantidad_total'] ?? 0);
    $precio      = (float)($_POST['precio_unitario'] ?? 0);

    if (!$nombre) $errors[] = 'El nombre es requerido.';
    if ($total < 0) $errors[] = 'La cantidad debe ser mayor o igual a 0.';

    if (empty($errors)) {
        $db->prepare("INSERT INTO inventario (nombre,descripcion,cantidad_total,cantidad_disponible,precio_unitario) VALUES (?,?,?,?,?)")
           ->execute([$nombre,$descripcion,$total,$total,$precio]);
        flashMessage('success', "Insumo '{$nombre}' agregado exitosamente.");
        redirect('/vivimostodos/admin/inventario/index.php');
    }
}

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <?= renderFlash() ?>
        <div class="card animate-in">
            <div class="card-header">
                <h5>📦 Agregar Insumo</h5>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">← Volver</a>
            </div>
            <div class="card-body">
                <?php if ($errors): ?>
                <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nombre del insumo *</label>
                        <input type="text" name="nombre" class="form-control" value="<?= sanitize($_POST['nombre'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"><?= sanitize($_POST['descripcion'] ?? '') ?></textarea>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Cantidad total *</label>
                            <input type="number" name="cantidad_total" class="form-control" min="0" value="<?= (int)($_POST['cantidad_total'] ?? 1) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Precio unitario (COP)</label>
                            <input type="number" name="precio_unitario" class="form-control" min="0" step="100" value="<?= (float)($_POST['precio_unitario'] ?? 0) ?>">
                            <small class="text-muted">0 = Gratis</small>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">✅ Agregar Insumo</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
