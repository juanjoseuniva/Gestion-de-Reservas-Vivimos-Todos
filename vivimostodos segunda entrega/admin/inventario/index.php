<?php
/**
 * admin/inventario/index.php — PUNTO 4
 * Administrar y controlar insumos del salón
 */
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/session.php';
require_once 'C:\\xampp\\htdocs\\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

$db = getDB();
$pageTitle = 'Inventario';

// Búsqueda
$buscar = trim($_GET['buscar'] ?? '');
$filtroEstado = $_GET['estado'] ?? '';

$sql    = "SELECT * FROM inventario WHERE 1=1";
$params = [];
if ($buscar) {
    $sql    .= " AND (nombre LIKE ? OR descripcion LIKE ?)";
    $like    = "%$buscar%";
    $params  = [$like, $like];
}
if ($filtroEstado) {
    $sql    .= " AND estado = ?";
    $params[] = $filtroEstado;
}
$sql .= " ORDER BY nombre ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$insumos = $stmt->fetchAll();

// Contar stock bajo
$stockBajoCount = 0;
foreach ($insumos as $i) {
    if ($i['cantidad_total'] > 0 && ($i['cantidad_disponible'] / $i['cantidad_total']) < 0.20) {
        $stockBajoCount++;
    }
}

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<?= renderFlash() ?>

<?php if ($stockBajoCount > 0): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 animate-in mb-3" style="border-left:4px solid #dc2626">
    <span style="font-size:1.4rem">⚠️</span>
    <div>
        <strong><?= $stockBajoCount ?> insumo(s) con stock crítico</strong> (menos del 20% disponible).
        Considera reponer o marcar como NO DISPONIBLE.
    </div>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex gap-2">
        <!-- Stat rápida -->
        <div class="px-3 py-2 rounded" style="background:#dbeafe;font-size:.82rem;color:#1a3c5e">
            📦 Total insumos: <strong><?= count($insumos) ?></strong>
        </div>
        <?php if ($stockBajoCount > 0): ?>
        <div class="px-3 py-2 rounded" style="background:#fee2e2;font-size:.82rem;color:#b91c1c">
            ⚠️ Stock bajo: <strong><?= $stockBajoCount ?></strong>
        </div>
        <?php endif; ?>
    </div>
    <a href="crear.php" class="btn btn-primary">➕ Nuevo Insumo</a>
</div>

<div class="card animate-in">
    <div class="card-header flex-wrap gap-2">
        <h5>📦 Inventario del Salón Social</h5>
        <form class="d-flex gap-2 flex-wrap" method="GET">
            <input type="text" name="buscar" class="form-control form-control-sm"
                placeholder="Buscar insumo..." value="<?= sanitize($buscar) ?>" style="width:200px">
            <select name="estado" class="form-select form-select-sm" style="width:160px" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                <option value="DISPONIBLE"    <?= $filtroEstado==='DISPONIBLE'    ?'selected':'' ?>>DISPONIBLE</option>
                <option value="NO DISPONIBLE" <?= $filtroEstado==='NO DISPONIBLE' ?'selected':'' ?>>NO DISPONIBLE</option>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-primary">Buscar</button>
            <?php if ($buscar || $filtroEstado): ?>
            <a href="index.php" class="btn btn-sm btn-outline-secondary">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Total</th>
                        <th>Disponible / Stock</th>
                        <th>Precio/U</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($insumos)): ?>
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <div style="font-size:2rem">📦</div>
                        No se encontraron insumos
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($insumos as $i):
                    $pct      = $i['cantidad_total'] > 0 ? round(($i['cantidad_disponible'] / $i['cantidad_total']) * 100) : 0;
                    $colorBar = $pct < 20 ? 'danger' : ($pct < 50 ? 'warning' : 'success');
                    $esBajo   = $pct < 20 && $i['cantidad_total'] > 0;
                ?>
                <tr <?= $esBajo ? "style='background:#fff5f5'" : '' ?>>
                    <td style="font-family:var(--font-mono);color:var(--text-muted)">#<?= $i['id_insumo'] ?></td>
                    <td>
                        <strong><?= sanitize($i['nombre']) ?></strong>
                        <?php if ($esBajo): ?>
                        <span class="badge bg-danger ms-1" style="font-size:.6rem">⚠️ BAJO</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--text-muted);font-size:.82rem;max-width:200px">
                        <?= sanitize($i['descripcion'] ?? '—') ?>
                    </td>
                    <td class="text-center"><?= $i['cantidad_total'] ?></td>
                    <td style="min-width:140px">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold text-<?= $colorBar ?>"><?= $i['cantidad_disponible'] ?></span>
                            <div class="flex-grow-1">
                                <div class="progress" style="height:8px;border-radius:4px">
                                    <div class="progress-bar bg-<?= $colorBar ?>"
                                         role="progressbar"
                                         style="width:<?= $pct ?>%"
                                         title="<?= $pct ?>% disponible">
                                    </div>
                                </div>
                                <div style="font-size:.65rem;color:var(--text-muted);font-family:var(--font-mono)"><?= $pct ?>%</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if ($i['precio_unitario'] > 0): ?>
                        <span class="fw-semibold text-primary"><?= formatMoneda((float)$i['precio_unitario']) ?></span>
                        <?php else: ?>
                        <span class="badge bg-success">Gratis</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $i['estado']==='DISPONIBLE' ? 'success' : 'danger' ?>">
                            <?= $i['estado'] ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="editar.php?id=<?= $i['id_insumo'] ?>"
                               class="btn btn-sm btn-outline-primary"
                               style="font-size:.72rem;padding:3px 10px">✏️ Editar</a>

                            <!-- Toggle disponibilidad -->
                            <form method="POST" action="toggle_estado.php" class="d-inline">
                                <input type="hidden" name="id" value="<?= $i['id_insumo'] ?>">
                                <button type="submit"
                                    class="btn btn-sm btn-outline-<?= $i['estado']==='DISPONIBLE' ? 'warning' : 'success' ?>"
                                    style="font-size:.72rem;padding:3px 10px"
                                    onclick="return confirm('¿Cambiar estado del insumo?')">
                                    <?= $i['estado']==='DISPONIBLE' ? '🚫 Deshabilitar' : '✅ Habilitar' ?>
                                </button>
                            </form>

                            <!-- Eliminar -->
                            <form method="POST" action="eliminar.php" class="d-inline">
                                <input type="hidden" name="id" value="<?= $i['id_insumo'] ?>">
                                <button type="submit"
                                    class="btn btn-sm btn-outline-danger"
                                    style="font-size:.72rem;padding:3px 10px"
                                    onclick="return confirm('¿Eliminar este insumo? Esta acción no se puede deshacer.')">
                                    🗑
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer" style="font-size:.78rem;color:var(--text-muted)">
        Mostrando <?= count($insumos) ?> insumo(s)
        <?php if ($buscar): ?> · Búsqueda: "<?= sanitize($buscar) ?>"<?php endif; ?>
    </div>
</div>

<?php include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
