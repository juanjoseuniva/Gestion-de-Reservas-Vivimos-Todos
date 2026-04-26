<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

$db = getDB();
$pageTitle = 'Inventario';

$insumos = $db->query("SELECT * FROM inventario ORDER BY nombre")->fetchAll();

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div></div>
    <a href="crear.php" class="btn btn-primary">➕ Nuevo Insumo</a>
</div>

<?= renderFlash() ?>

<div class="card animate-in">
    <div class="card-header"><h5>📦 Inventario del Salón Social</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>ID</th><th>Nombre</th><th>Descripción</th>
                        <th>Total</th><th>Disponible</th><th>Precio/U</th>
                        <th>Estado</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($insumos as $i): ?>
                <tr>
                    <td style="font-family:var(--font-mono)">#<?= $i['id_insumo'] ?></td>
                    <td><strong><?= sanitize($i['nombre']) ?></strong></td>
                    <td style="color:var(--text-muted)"><?= sanitize($i['descripcion']) ?></td>
                    <td><?= $i['cantidad_total'] ?></td>
                    <td>
                        <?php $pct = $i['cantidad_total'] > 0 ? ($i['cantidad_disponible']/$i['cantidad_total'])*100 : 0; ?>
                        <span class="fw-bold <?= $pct < 20 ? 'text-danger' : ($pct < 60 ? 'text-warning' : 'text-success') ?>">
                            <?= $i['cantidad_disponible'] ?>
                        </span>
                        <div class="progress mt-1" style="height:4px;width:60px">
                            <div class="progress-bar bg-<?= $pct < 20 ? 'danger' : ($pct < 60 ? 'warning' : 'success') ?>"
                                 style="width:<?= $pct ?>%"></div>
                        </div>
                    </td>
                    <td><?= $i['precio_unitario'] > 0 ? formatMoneda((float)$i['precio_unitario']) : '<span class="text-muted">Gratis</span>' ?></td>
                    <td>
                        <span class="badge bg-<?= $i['estado'] === 'DISPONIBLE' ? 'success' : 'danger' ?>"><?= $i['estado'] ?></span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="editar.php?id=<?= $i['id_insumo'] ?>" class="btn btn-sm btn-outline-primary" style="font-size:.75rem;padding:3px 10px">✏️ Editar</a>
                            <form method="POST" action="eliminar.php" class="d-inline">
                                <input type="hidden" name="id" value="<?= $i['id_insumo'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:.75rem;padding:3px 10px"
                                    onclick="return confirm('¿Eliminar este insumo?')">🗑</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
