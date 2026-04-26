<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['RESIDENTE']);

$db = getDB();
$pageTitle = 'Inventario Disponible';
$insumos = $db->query("SELECT * FROM inventario ORDER BY nombre")->fetchAll();

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<div class="card animate-in">
    <div class="card-header">
        <h5>📦 Inventario del Salón Social</h5>
        <a href="reservar.php" class="btn btn-sm btn-primary">📅 Hacer Reserva</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr><th>Insumo</th><th>Descripción</th><th>Disponible</th><th>Precio por uso</th><th>Estado</th></tr>
                </thead>
                <tbody>
                <?php foreach ($insumos as $i):
                    $pct = $i['cantidad_total'] > 0 ? ($i['cantidad_disponible']/$i['cantidad_total'])*100 : 0;
                ?>
                <tr>
                    <td><strong><?= sanitize($i['nombre']) ?></strong></td>
                    <td style="color:var(--text-muted)"><?= sanitize($i['descripcion']) ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold <?= $pct < 20 ? 'text-danger' : ($pct < 60 ? 'text-warning' : 'text-success') ?>">
                                <?= $i['cantidad_disponible'] ?> / <?= $i['cantidad_total'] ?>
                            </span>
                            <div class="progress" style="height:6px;width:60px;flex-shrink:0">
                                <div class="progress-bar bg-<?= $pct < 20 ? 'danger' : ($pct < 60 ? 'warning' : 'success') ?>" style="width:<?= $pct ?>%"></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if ($i['precio_unitario'] > 0): ?>
                        <span class="fw-bold text-primary"><?= formatMoneda((float)$i['precio_unitario']) ?></span>
                        <?php else: ?>
                        <span class="badge bg-success">Gratis</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $i['estado']==='DISPONIBLE' ? 'success' : 'danger' ?>"><?= $i['estado'] ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="alert alert-info mt-3" style="font-size:.83rem">
    💡 Al hacer tu reserva podrás seleccionar los insumos que necesites. Los precios indicados se cobran por evento.
</div>

<?php include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
