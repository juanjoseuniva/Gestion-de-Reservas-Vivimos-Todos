<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

$db = getDB();
$pageTitle = 'Editar Insumo';
$id = (int)($_GET['id'] ?? 0);
$errors = [];

$stmt = $db->prepare("SELECT * FROM inventario WHERE id_insumo = ?");
$stmt->execute([$id]);
$insumo = $stmt->fetch();
if (!$insumo) { flashMessage('error','Insumo no encontrado.'); redirect('/vivimostodos/admin/inventario/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $total       = (int)($_POST['cantidad_total'] ?? 0);
    $disponible  = (int)($_POST['cantidad_disponible'] ?? 0);
    $precio      = (float)($_POST['precio_unitario'] ?? 0);
    $estado      = $_POST['estado'] ?? 'DISPONIBLE';

    if (!$nombre) $errors[] = 'El nombre es requerido.';
    if ($disponible > $total) $errors[] = 'Disponible no puede ser mayor que total.';

    if (empty($errors)) {
        $db->prepare("UPDATE inventario SET nombre=?,descripcion=?,cantidad_total=?,cantidad_disponible=?,precio_unitario=?,estado=? WHERE id_insumo=?")
           ->execute([$nombre,$descripcion,$total,$disponible,$precio,$estado,$id]);
        flashMessage('success','Insumo actualizado correctamente.');
        redirect('/vivimostodos/admin/inventario/index.php');
    }
    $insumo = array_merge($insumo,compact('nombre','descripcion','precio','estado') + ['cantidad_total'=>$total,'cantidad_disponible'=>$disponible]);
}

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <?= renderFlash() ?>
        <div class="card animate-in">
            <div class="card-header">
                <h5>✏️ Editar Insumo</h5>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">← Volver</a>
            </div>
            <div class="card-body">
                <?php if ($errors): ?>
                <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" value="<?= sanitize($insumo['nombre']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2"><?= sanitize($insumo['descripcion']) ?></textarea>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Total</label>
                            <input type="number" name="cantidad_total" class="form-control" min="0" value="<?= $insumo['cantidad_total'] ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Disponible</label>
                            <input type="number" name="cantidad_disponible" class="form-control" min="0" value="<?= $insumo['cantidad_disponible'] ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Precio/U (COP)</label>
                            <input type="number" name="precio_unitario" class="form-control" min="0" step="100" value="<?= $insumo['precio_unitario'] ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="DISPONIBLE" <?= $insumo['estado']==='DISPONIBLE'?'selected':'' ?>>DISPONIBLE</option>
                            <option value="NO DISPONIBLE" <?= $insumo['estado']==='NO DISPONIBLE'?'selected':'' ?>>NO DISPONIBLE</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">💾 Guardar</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
