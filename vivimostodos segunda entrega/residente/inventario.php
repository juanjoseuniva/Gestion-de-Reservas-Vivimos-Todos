<?php
/**
 * residente/inventario.php — PUNTO 4
 * Vista de inventario para residentes con tarjetas e iconos
 */
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/session.php';
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/functions.php';
requireRole(['RESIDENTE']);

$db = getDB();
$pageTitle = 'Inventario del Salón';

$insumos = $db->query(
    "SELECT * FROM inventario ORDER BY estado ASC, nombre ASC"
)->fetchAll();

// Iconos por nombre de insumo
function getIconoInsumo(string $nombre): string {
    $nombre = strtolower($nombre);
    if (str_contains($nombre, 'silla'))      return '🪑';
    if (str_contains($nombre, 'mesa'))       return '🪵';
    if (str_contains($nombre, 'video') || str_contains($nombre, 'beam') || str_contains($nombre, 'proyector')) return '📽️';
    if (str_contains($nombre, 'tel'))        return '🎬';
    if (str_contains($nombre, 'sonido') || str_contains($nombre, 'parlante') || str_contains($nombre, 'micr')) return '🔈';
    if (str_contains($nombre, 'mantel'))     return '🧺';
    if (str_contains($nombre, 'extensi'))    return '🔌';
    if (str_contains($nombre, 'luz') || str_contains($nombre, 'foco')) return '💡';
    return '📦';
}

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0" style="font-size:.9rem">
        Insumos disponibles para solicitar en tu reserva del salón social.
    </p>
    <a href="reservar.php" class="btn btn-primary">📅 Hacer Reserva</a>
</div>

<div class="row g-3">
<?php foreach ($insumos as $i):
    $disponible = $i['estado'] === 'DISPONIBLE';
    $pct = $i['cantidad_total'] > 0
        ? round(($i['cantidad_disponible'] / $i['cantidad_total']) * 100) : 0;
    $colorBar = $pct < 20 ? 'danger' : ($pct < 50 ? 'warning' : 'success');
    $icono = getIconoInsumo($i['nombre']);
?>
<div class="col-sm-6 col-lg-4 animate-in">
    <div class="card h-100 <?= !$disponible ? 'opacity-75' : '' ?>"
         style="<?= !$disponible ? 'filter:grayscale(.4)' : '' ?>">
        <div class="card-body text-center py-4">
            <!-- Icono -->
            <div style="font-size:3rem;margin-bottom:12px"><?= $icono ?></div>

            <!-- Nombre y estado -->
            <h5 class="card-title mb-1"><?= sanitize($i['nombre']) ?></h5>
            <span class="badge bg-<?= $disponible ? 'success' : 'danger' ?> mb-3">
                <?= $i['estado'] ?>
            </span>

            <!-- Descripción -->
            <?php if ($i['descripcion']): ?>
            <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:12px">
                <?= sanitize($i['descripcion']) ?>
            </p>
            <?php endif; ?>

            <!-- Stock -->
            <?php if ($disponible): ?>
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1" style="font-size:.8rem">
                    <span class="text-muted">Disponible</span>
                    <span class="fw-bold text-<?= $colorBar ?>"><?= $i['cantidad_disponible'] ?> / <?= $i['cantidad_total'] ?></span>
                </div>
                <div class="progress" style="height:8px;border-radius:4px">
                    <div class="progress-bar bg-<?= $colorBar ?>"
                         style="width:<?= $pct ?>%"></div>
                </div>
                <div style="font-size:.68rem;color:var(--text-muted);margin-top:3px;font-family:var(--font-mono)"><?= $pct ?>% disponible</div>
            </div>

            <!-- Precio -->
            <div class="mt-2">
                <?php if ($i['precio_unitario'] > 0): ?>
                <div class="fw-bold text-primary" style="font-size:1.1rem">
                    <?= formatMoneda((float)$i['precio_unitario']) ?>
                </div>
                <div style="font-size:.75rem;color:var(--text-muted)">por evento</div>
                <?php else: ?>
                <span class="badge bg-success px-3 py-2" style="font-size:.85rem">✓ Sin costo</span>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="alert alert-danger py-2 mt-2" style="font-size:.82rem">
                No disponible actualmente
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- Nota informativa -->
<div class="alert alert-info mt-4" style="font-size:.83rem">
    <strong>💡 ¿Cómo reservar insumos?</strong> Al hacer tu reserva podrás seleccionar los insumos que necesitas.
    Los precios indicados se cobran por evento. Los insumos gratis no tienen costo adicional.
    El inventario se descuenta <strong>solo cuando el administrador aprueba tu reserva</strong>.
</div>

<?php include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
