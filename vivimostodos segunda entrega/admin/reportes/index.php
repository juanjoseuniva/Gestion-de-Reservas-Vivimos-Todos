<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['ADMIN']);

$db = getDB();
$pageTitle = 'Reportes';

// Stats for report preview
$stats = [
    'total_reservas'   => $db->query("SELECT COUNT(*) FROM reservas")->fetchColumn(),
    'aprobadas'        => $db->query("SELECT COUNT(*) FROM reservas WHERE estado='APROBADA'")->fetchColumn(),
    'rechazadas'       => $db->query("SELECT COUNT(*) FROM reservas WHERE estado='RECHAZADA'")->fetchColumn(),
    'pendientes'       => $db->query("SELECT COUNT(*) FROM reservas WHERE estado='PENDIENTE'")->fetchColumn(),
    'ingresos_total'   => $db->query("SELECT COALESCE(SUM(monto),0) FROM pagos WHERE estado='VALIDADO'")->fetchColumn(),
    'ingresos_mes'     => $db->query("SELECT COALESCE(SUM(monto),0) FROM pagos WHERE estado='VALIDADO' AND MONTH(fecha_pago)=MONTH(NOW())")->fetchColumn(),
    'usuarios_activos' => $db->query("SELECT COUNT(*) FROM usuarios WHERE estado='ACTIVO' AND rol='RESIDENTE'")->fetchColumn(),
];

// Reservas por mes (last 6)
$reservasMes = $db->query(
    "SELECT DATE_FORMAT(fecha_solicitud,'%Y-%m') as mes, COUNT(*) as total
     FROM reservas GROUP BY mes ORDER BY mes DESC LIMIT 6"
)->fetchAll();

// Insumos más usados
$insumosTop = $db->query(
    "SELECT i.nombre, SUM(dr.cantidad) as total_usado
     FROM detalle_reserva dr JOIN inventario i ON dr.id_insumo=i.id_insumo
     GROUP BY i.id_insumo ORDER BY total_usado DESC LIMIT 5"
)->fetchAll();

$reportesGuardados = $db->query("SELECT * FROM reportes ORDER BY fecha_generacion DESC LIMIT 10")->fetchAll();

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<?= renderFlash() ?>

<!-- Stat cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-xl-2 animate-in delay-1">
        <div class="stat-card"><div class="stat-icon blue">📅</div><div><div class="stat-label">Total</div><div class="stat-value"><?= $stats['total_reservas'] ?></div></div></div>
    </div>
    <div class="col-md-4 col-xl-2 animate-in delay-2">
        <div class="stat-card"><div class="stat-icon green">✅</div><div><div class="stat-label">Aprobadas</div><div class="stat-value"><?= $stats['aprobadas'] ?></div></div></div>
    </div>
    <div class="col-md-4 col-xl-2 animate-in delay-3">
        <div class="stat-card"><div class="stat-icon red">❌</div><div><div class="stat-label">Rechazadas</div><div class="stat-value"><?= $stats['rechazadas'] ?></div></div></div>
    </div>
    <div class="col-md-4 col-xl-2 animate-in delay-4">
        <div class="stat-card"><div class="stat-icon yellow">⏳</div><div><div class="stat-label">Pendientes</div><div class="stat-value"><?= $stats['pendientes'] ?></div></div></div>
    </div>
    <div class="col-md-4 col-xl-2 animate-in delay-1">
        <div class="stat-card"><div class="stat-icon cyan">💰</div><div><div class="stat-label">Ingresos Mes</div><div class="stat-value" style="font-size:1rem"><?= formatMoneda((float)$stats['ingresos_mes']) ?></div></div></div>
    </div>
    <div class="col-md-4 col-xl-2 animate-in delay-2">
        <div class="stat-card"><div class="stat-icon green">👥</div><div><div class="stat-label">Residentes</div><div class="stat-value"><?= $stats['usuarios_activos'] ?></div></div></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8 animate-in delay-2">
        <div class="card">
            <div class="card-header"><h5>📊 Reservas por Mes</h5></div>
            <div class="card-body"><canvas id="chartMeses" style="max-height:240px"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4 animate-in delay-3">
        <div class="card">
            <div class="card-header"><h5>📦 Insumos Más Usados</h5></div>
            <div class="card-body">
                <?php if ($insumosTop): ?>
                <?php foreach ($insumosTop as $ins): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:.85rem"><?= sanitize($ins['nombre']) ?></span>
                    <span class="badge bg-primary"><?= $ins['total_usado'] ?></span>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <p class="text-muted text-center">Sin datos</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Exportar -->
<div class="card animate-in delay-3 mb-4">
    <div class="card-header"><h5>📤 Exportar Reportes</h5></div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Rango de fechas</label>
                <input type="date" id="fecha_inicio" class="form-control" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Hasta</label>
                <input type="date" id="fecha_fin" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-6 d-flex gap-2">
                <button onclick="exportar('pdf')" class="btn btn-danger flex-fill">
                    📄 Exportar PDF
                </button>
                <button onclick="exportar('excel')" class="btn btn-success flex-fill">
                    📊 Exportar Excel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Historial de reportes -->
<?php if ($reportesGuardados): ?>
<div class="card animate-in">
    <div class="card-header"><h5>🗂 Reportes Generados</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Tipo</th><th>Fecha generación</th><th>Archivo</th></tr></thead>
                <tbody>
                <?php foreach ($reportesGuardados as $rp): ?>
                <tr>
                    <td><?= sanitize($rp['tipo']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($rp['fecha_generacion'])) ?></td>
                    <td>
                        <?php if ($rp['archivo']): ?>
                        <a href="/vivimostodos/assets/uploads/reportes/<?= $rp['archivo'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="font-size:.75rem">📎 Descargar</a>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$labels = array_reverse(array_column($reservasMes, 'mes'));
$values = array_reverse(array_column($reservasMes, 'total'));
$extraJs = "<script>
new Chart(document.getElementById('chartMeses'), {
    type: 'bar',
    data: {
        labels: " . json_encode($labels) . ",
        datasets: [{ label: 'Reservas', data: " . json_encode($values) . ",
            backgroundColor: 'rgba(37,99,168,.7)', borderRadius:6, borderSkipped:false }]
    },
    options: { plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}}} }
});

function exportar(tipo) {
    const fi = document.getElementById('fecha_inicio').value;
    const ff = document.getElementById('fecha_fin').value;
    if (!fi || !ff) { alert('Selecciona rango de fechas'); return; }
    const url = tipo==='pdf'
        ? '/vivimostodos/admin/reportes/exportar_pdf.php?inicio='+fi+'&fin='+ff
        : '/vivimostodos/admin/reportes/exportar_excel.php?inicio='+fi+'&fin='+ff;
    window.open(url, '_blank');
}
</script>";
include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
