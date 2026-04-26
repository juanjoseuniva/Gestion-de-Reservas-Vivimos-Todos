<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
requireRole(['ADMIN','SUPERVISOR']);

$db = getDB();
$pageTitle = 'Semáforo de Disponibilidad';

// Get current month or requested month
$year  = (int)($_GET['year']  ?? date('Y'));
$month = (int)($_GET['month'] ?? date('m'));
$month = max(1, min(12, $month));
$year  = max(2020, min(2030, $year));

$primerDia = mktime(0,0,0,$month,1,$year);
$totalDias = (int)date('t', $primerDia);
$diaSemana = (int)date('w', $primerDia); // 0=domingo

// Reservas del mes
$stmt = $db->prepare(
    "SELECT fecha_evento, estado FROM reservas
     WHERE YEAR(fecha_evento)=? AND MONTH(fecha_evento)=?
     AND estado IN ('PENDIENTE','APROBADA')"
);
$stmt->execute([$year, $month]);
$reservasMes = [];
foreach ($stmt->fetchAll() as $r) {
    $d = (int)date('d', strtotime($r['fecha_evento']));
    $reservasMes[$d] = $r['estado'];
}

$prevYear  = $month == 1 ? $year-1 : $year;
$prevMonth = $month == 1 ? 12 : $month-1;
$nextYear  = $month == 12 ? $year+1 : $year;
$nextMonth = $month == 12 ? 1 : $month+1;

$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$diasSemana = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
$hoy = (int)date('d');
$mesActual = (int)date('m');
$anioActual = (int)date('Y');

include 'C:/xampp/htdocs/vivimostodos/includes/header.php';
include 'C:/xampp/htdocs/vivimostodos/includes/sidebar.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="card animate-in">
            <div class="card-header">
                <h5>🚦 Semáforo de Disponibilidad — <?= $meses[$month] ?> <?= $year ?></h5>
                <div class="d-flex gap-2">
                    <a href="?year=<?=$prevYear?>&month=<?=$prevMonth?>" class="btn btn-sm btn-outline-secondary">◀ Anterior</a>
                    <a href="?year=<?=$anioActual?>&month=<?=$mesActual?>" class="btn btn-sm btn-outline-primary">Hoy</a>
                    <a href="?year=<?=$nextYear?>&month=<?=$nextMonth?>" class="btn btn-sm btn-outline-secondary">Siguiente ▶</a>
                </div>
            </div>
            <div class="card-body">
                <!-- Leyenda -->
                <div class="d-flex gap-3 mb-4 flex-wrap">
                    <span class="semaforo-day disponible px-3 py-1" style="cursor:default">✓ Disponible</span>
                    <span class="semaforo-day pendiente px-3 py-1" style="cursor:default">⏳ Pendiente</span>
                    <span class="semaforo-day ocupado px-3 py-1" style="cursor:default">✗ Ocupado</span>
                    <span class="semaforo-day pasado px-3 py-1" style="cursor:default">— Pasado</span>
                </div>

                <!-- Header días semana -->
                <div class="semaforo-grid mb-2">
                    <?php foreach ($diasSemana as $ds): ?>
                    <div class="text-center fw-bold text-muted" style="font-size:.75rem;font-family:var(--font-mono);padding:6px"><?= $ds ?></div>
                    <?php endforeach; ?>
                </div>

                <!-- Calendario -->
                <div class="semaforo-grid">
                    <!-- Espacios vacíos antes del primer día -->
                    <?php for ($i = 0; $i < $diaSemana; $i++): ?>
                    <div></div>
                    <?php endfor; ?>

                    <?php for ($dia = 1; $dia <= $totalDias; $dia++):
                        $esPasado = ($year < $anioActual) || ($year == $anioActual && $month < $mesActual) || ($year == $anioActual && $month == $mesActual && $dia < $hoy);
                        $esHoy    = ($year == $anioActual && $month == $mesActual && $dia == $hoy);
                        $estadoReserva = $reservasMes[$dia] ?? null;

                        if ($esPasado) $clase = 'pasado';
                        elseif ($estadoReserva === 'APROBADA') $clase = 'ocupado';
                        elseif ($estadoReserva === 'PENDIENTE') $clase = 'pendiente';
                        else $clase = 'disponible';

                        $fechaCompleta = sprintf('%04d-%02d-%02d', $year, $month, $dia);
                        $title = $clase === 'disponible' ? 'Disponible' : ($clase === 'ocupado' ? 'Ocupado (Aprobada)' : ($clase === 'pendiente' ? 'Pendiente de aprobación' : 'Fecha pasada'));
                    ?>
                    <div class="semaforo-day <?= $clase ?>" title="<?= $dia ?> <?= $meses[$month] ?> — <?= $title ?>"
                         <?= ($clase === 'disponible' && !$esPasado) ? "onclick=\"window.location='/vivimostodos/admin/reservas/index.php'\"" : '' ?>
                         style="<?= $esHoy ? 'box-shadow:0 0 0 2px #1a3c5e;font-weight:900;' : '' ?>">
                        <div><?= $dia ?></div>
                        <div style="font-size:.6rem;margin-top:2px">
                            <?= $clase === 'disponible' ? '✓' : ($clase === 'ocupado' ? '✗' : ($clase === 'pendiente' ? '⏳' : '—')) ?>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>

                <!-- Reservas del mes -->
                <?php if (!empty($reservasMes)): ?>
                <div class="mt-4">
                    <h6 class="mb-3">📋 Reservas en <?= $meses[$month] ?></h6>
                    <?php
                    $stmtDet = $db->prepare(
                        "SELECT r.*, u.nombre, u.apellido FROM reservas r JOIN usuarios u ON r.id_usuario=u.id_usuario
                         WHERE YEAR(r.fecha_evento)=? AND MONTH(r.fecha_evento)=? AND r.estado IN ('PENDIENTE','APROBADA')
                         ORDER BY r.fecha_evento ASC"
                    );
                    $stmtDet->execute([$year, $month]);
                    $detalle = $stmtDet->fetchAll();
                    ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>#</th><th>Fecha</th><th>Residente</th><th>Estado</th><th>Ver</th></tr></thead>
                            <tbody>
                            <?php foreach ($detalle as $d):
                                $sc=['PENDIENTE'=>'warning text-dark','APROBADA'=>'success'][$d['estado']]??'secondary';
                            ?>
                            <tr>
                                <td>#<?= $d['id_reserva'] ?></td>
                                <td><?= formatFecha($d['fecha_evento']) ?></td>
                                <td><?= sanitize($d['nombre']) ?> <?= sanitize($d['apellido']) ?></td>
                                <td><span class="badge bg-<?= $sc ?>"><?= $d['estado'] ?></span></td>
                                <td><a href="/vivimostodos/admin/reservas/detalle.php?id=<?= $d['id_reserva'] ?>" class="btn btn-xs btn-outline-secondary" style="font-size:.72rem;padding:2px 8px">Ver</a></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'C:/xampp/htdocs/vivimostodos/includes/footer.php'; ?>
