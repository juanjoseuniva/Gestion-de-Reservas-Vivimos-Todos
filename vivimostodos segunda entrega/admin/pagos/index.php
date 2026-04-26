<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

$db = getDB();
$pageTitle = 'Gestión de Pagos';

$filtro = $_GET['estado'] ?? '';
$sql = "SELECT p.*, r.fecha_evento, u.nombre, u.apellido
        FROM pagos p
        JOIN reservas r ON p.id_reserva=r.id_reserva
        JOIN usuarios u ON r.id_usuario=u.id_usuario
        WHERE 1=1";
$params = [];
if ($filtro) { $sql .= " AND p.estado=?"; $params[]=$filtro; }
$sql .= " ORDER BY p.fecha_pago DESC";
$stmt = $db->prepare($sql); $stmt->execute($params);
$pagos = $stmt->fetchAll();

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<?= renderFlash() ?>

<div class="card animate-in">
    <div class="card-header">
        <h5>💳 Comprobantes de Pago</h5>
        <form class="d-flex gap-2" method="GET">
            <select name="estado" class="form-select form-select-sm" style="width:150px" onchange="this.form.submit()">
                <option value="">Todos</option>
                <?php foreach(['PENDIENTE','VALIDADO','RECHAZADO'] as $e): ?>
                <option value="<?=$e?>" <?= $filtro===$e?'selected':'' ?>><?=$e?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($filtro): ?><a href="?" class="btn btn-sm btn-outline-secondary">Limpiar</a><?php endif; ?>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr><th>#Pago</th><th>#Reserva</th><th>Residente</th><th>Fecha Evento</th><th>Monto</th><th>Comprobante</th><th>Estado</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                <?php foreach ($pagos as $p):
                    $pClass=['PENDIENTE'=>'warning text-dark','VALIDADO'=>'success','RECHAZADO'=>'danger'][$p['estado']]??'secondary';
                ?>
                <tr>
                    <td style="font-family:var(--font-mono)">#<?= $p['id_pago'] ?></td>
                    <td style="font-family:var(--font-mono)">
                        <a href="/vivimostodos/admin/reservas/detalle.php?id=<?= $p['id_reserva'] ?>">#<?= $p['id_reserva'] ?></a>
                    </td>
                    <td><?= sanitize($p['nombre']) ?> <?= sanitize($p['apellido']) ?></td>
                    <td><?= formatFecha($p['fecha_evento']) ?></td>
                    <td><strong><?= formatMoneda((float)$p['monto']) ?></strong></td>
                    <td>
                        <?php if ($p['comprobante']): ?>
                        <a href="/vivimostodos/assets/uploads/comprobantes/<?= $p['comprobante'] ?>" target="_blank" class="btn btn-sm btn-outline-info" style="font-size:.72rem;padding:3px 8px">📎 Ver</a>
                        <?php else: ?><span class="text-muted">Sin archivo</span><?php endif; ?>
                    </td>
                    <td><span class="badge bg-<?= $pClass ?>"><?= $p['estado'] ?></span></td>
                    <td>
                        <?php if ($p['estado'] === 'PENDIENTE'): ?>
                        <div class="d-flex gap-1">
                            <form method="POST" action="validar.php" class="d-inline">
                                <input type="hidden" name="id" value="<?= $p['id_pago'] ?>">
                                <input type="hidden" name="accion" value="validar">
                                <button class="btn btn-sm btn-success" style="font-size:.72rem;padding:3px 8px" onclick="return confirm('¿Validar pago?')">✅ Validar</button>
                            </form>
                            <form method="POST" action="validar.php" class="d-inline">
                                <input type="hidden" name="id" value="<?= $p['id_pago'] ?>">
                                <input type="hidden" name="accion" value="rechazar">
                                <button class="btn btn-sm btn-danger" style="font-size:.72rem;padding:3px 8px" onclick="return confirm('¿Rechazar pago?')">❌ Rechazar</button>
                            </form>
                        </div>
                        <?php else: ?>
                        <span class="text-muted" style="font-size:.8rem">Procesado</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($pagos)): ?>
                <tr><td colspan="8" class="text-center py-4 text-muted">No hay pagos registrados</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
