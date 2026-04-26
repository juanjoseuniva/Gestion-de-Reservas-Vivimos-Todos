<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

$db = getDB();
$pageTitle = 'Dashboard';

// Stats
$totalUsuarios = $db->query("SELECT COUNT(*) FROM usuarios WHERE estado='ACTIVO'")->fetchColumn();
$totalReservas = $db->query("SELECT COUNT(*) FROM reservas")->fetchColumn();
$reservasPendientes = $db->query("SELECT COUNT(*) FROM reservas WHERE estado='PENDIENTE'")->fetchColumn();
$reservasAprobadas  = $db->query("SELECT COUNT(*) FROM reservas WHERE estado='APROBADA'")->fetchColumn();
$pagosPendientes    = $db->query("SELECT COUNT(*) FROM pagos WHERE estado='PENDIENTE'")->fetchColumn();
$ingresosMes        = $db->query("SELECT COALESCE(SUM(monto),0) FROM pagos WHERE estado='VALIDADO' AND MONTH(fecha_pago)=MONTH(NOW())")->fetchColumn();

// Last 5 reservas
$ultimasReservas = $db->query(
    "SELECT r.id_reserva, r.fecha_evento, r.estado, r.fecha_solicitud,
            u.nombre, u.apellido
     FROM reservas r
     JOIN usuarios u ON r.id_usuario = u.id_usuario
     ORDER BY r.fecha_solicitud DESC LIMIT 5"
)->fetchAll();

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3 animate-in delay-1">
        <div class="stat-card">
            <div class="stat-icon blue">👥</div>
            <div>
                <div class="stat-label">Residentes activos</div>
                <div class="stat-value"><?= $totalUsuarios ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 animate-in delay-2">
        <div class="stat-card">
            <div class="stat-icon green">📅</div>
            <div>
                <div class="stat-label">Total reservas</div>
                <div class="stat-value"><?= $totalReservas ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 animate-in delay-3">
        <div class="stat-card">
            <div class="stat-icon yellow">⏳</div>
            <div>
                <div class="stat-label">Pendientes</div>
                <div class="stat-value"><?= $reservasPendientes ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 animate-in delay-4">
        <div class="stat-card">
            <div class="stat-icon cyan">💰</div>
            <div>
                <div class="stat-label">Ingresos este mes</div>
                <div class="stat-value" style="font-size:1.3rem"><?= formatMoneda((float)$ingresosMes) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Chart: Reservas por estado -->
    <div class="col-lg-5 animate-in delay-2">
        <div class="card h-100">
            <div class="card-header"><h5>📊 Reservas por Estado</h5></div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="chartEstados" style="max-height:260px"></canvas>
            </div>
        </div>
    </div>
    <!-- Chart: Reservas últimos 6 meses -->
    <div class="col-lg-7 animate-in delay-3">
        <div class="card h-100">
            <div class="card-header"><h5>📈 Reservas Últimos 6 Meses</h5></div>
            <div class="card-body">
                <canvas id="chartMeses" style="max-height:260px"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Últimas reservas -->
    <div class="col-lg-8 animate-in delay-2">
        <div class="card">
            <div class="card-header">
                <h5>🕐 Últimas Reservas</h5>
                <a href="/vivimostodos/admin/reservas/index.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Residente</th>
                                <th>Fecha Evento</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($ultimasReservas as $r): ?>
                        <tr>
                            <td class="text-muted" style="font-family:var(--font-mono)">#<?= $r['id_reserva'] ?></td>
                            <td><?= sanitize($r['nombre']) ?> <?= sanitize($r['apellido']) ?></td>
                            <td><?= formatFecha($r['fecha_evento']) ?></td>
                            <td>
                                <?php
                                $estadoClass = [
                                    'PENDIENTE'  => 'warning text-dark',
                                    'APROBADA'   => 'success',
                                    'RECHAZADA'  => 'danger',
                                    'CANCELADA'  => 'secondary',
                                ][$r['estado']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $estadoClass ?>"><?= $r['estado'] ?></span>
                            </td>
                            <td>
                                <a href="/vivimostodos/admin/reservas/detalle.php?id=<?= $r['id_reserva'] ?>" class="btn btn-xs btn-outline-secondary" style="font-size:.75rem;padding:3px 10px">Ver</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones rápidas -->
    <div class="col-lg-4 animate-in delay-3">
        <div class="card">
            <div class="card-header"><h5>⚡ Acciones Rápidas</h5></div>
            <div class="card-body d-flex flex-column gap-2">
                <a href="/vivimostodos/admin/usuarios/crear.php" class="btn btn-primary">👤 Crear Usuario</a>
                <a href="/vivimostodos/admin/inventario/crear.php" class="btn btn-outline-primary">📦 Agregar Insumo</a>
                <a href="/vivimostodos/admin/pagos/index.php" class="btn btn-outline-warning">
                    💳 Validar Pagos
                    <?php if ($pagosPendientes > 0): ?><span class="badge bg-warning text-dark ms-1"><?= $pagosPendientes ?></span><?php endif; ?>
                </a>
                <a href="/vivimostodos/admin/reportes/semaforo.php" class="btn btn-outline-success">🚦 Ver Semáforo</a>
                <a href="/vivimostodos/admin/notificaciones/enviar.php" class="btn btn-outline-info">🔔 Enviar Notificación</a>
                <a href="/vivimostodos/admin/reportes/exportar_pdf.php" class="btn btn-outline-danger">📄 Exportar PDF</a>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = "<script>
(async () => {
    const res  = await fetch('/vivimostodos/api/graficos.php');
    const data = await res.json();

    new Chart(document.getElementById('chartEstados'), {
        type: 'doughnut',
        data: {
            labels: data.estados.labels,
            datasets: [{ data: data.estados.values,
                backgroundColor: ['#f59e0b','#16a34a','#dc2626','#64748b'],
                borderWidth: 2, borderColor: '#fff' }]
        },
        options: { plugins: { legend: { position: 'bottom', labels: { font: { family: 'Sora', size: 11 } } } }, cutout: '65%' }
    });

    new Chart(document.getElementById('chartMeses'), {
        type: 'bar',
        data: {
            labels: data.meses.labels,
            datasets: [{ label: 'Reservas', data: data.meses.values,
                backgroundColor: 'rgba(37,99,168,.7)', borderRadius: 6, borderSkipped: false }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
})();
</script>";
include 'C:/xampp/htdocs/vivimostodos/includes/footer.php';
?>
