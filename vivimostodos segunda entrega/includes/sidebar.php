<?php
require_once 'C:\xampp\htdocs\vivimostodos/config/session.php';
require_once 'C:\xampp\htdocs\vivimostodos/config/functions.php';
$user = currentUser();
$rol  = currentRole();
$uid  = currentUserId();
$notifCount = $uid ? contarNotificacionesNoLeidas($uid) : 0;
$initials = strtoupper(substr($user['nombre'],0,1) . substr($user['apellido'],0,1));
?>
<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <a href="#" class="brand-logo">
            <div class="brand-icon">🏢</div>
            <div class="brand-text">
                <strong>Vivimostodos</strong>
                <span>Portal Residencial</span>
            </div>
        </a>
    </div>

    <div class="sidebar-user d-flex align-items-center gap-3">
        <div class="user-avatar"><?= $initials ?></div>
        <div class="user-info">
            <div class="user-name"><?= sanitize($user['nombre']) ?> <?= sanitize($user['apellido']) ?></div>
            <div class="user-role"><?= $rol ?></div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php if ($rol === 'ADMIN'): ?>
        <div class="nav-section-title">Administración</div>
        <a href="/vivimostodos/admin/dashboard.php" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'dashboard') ? 'active' : '' ?>">
            <span class="icon">📊</span> Dashboard
        </a>
        <a href="/vivimostodos/admin/usuarios/index.php" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'/usuarios/') ? 'active' : '' ?>">
            <span class="icon">👥</span> Usuarios
        </a>
        <a href="/vivimostodos/admin/inventario/index.php" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'/inventario/') ? 'active' : '' ?>">
            <span class="icon">📦</span> Inventario
        </a>
        <a href="/vivimostodos/admin/reservas/index.php" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'/reservas/') ? 'active' : '' ?>">
            <span class="icon">📅</span> Reservas
        </a>
        <a href="/vivimostodos/admin/pagos/index.php" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'/pagos/') ? 'active' : '' ?>">
            <span class="icon">💳</span> Pagos
        </a>
        <div class="nav-section-title">Herramientas</div>
        <a href="/vivimostodos/admin/reportes/index.php" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'/reportes/') ? 'active' : '' ?>">
            <span class="icon">📈</span> Reportes
        </a>
        <a href="/vivimostodos/admin/reportes/semaforo.php" class="sidebar-link">
            <span class="icon">🚦</span> Semáforo
        </a>
        <a href="/vivimostodos/admin/notificaciones/enviar.php" class="sidebar-link">
            <span class="icon">🔔</span> Notificaciones
            <?php if ($notifCount > 0): ?><span class="badge-count"><?= $notifCount ?></span><?php endif; ?>
        </a>

        <?php elseif ($rol === 'RESIDENTE'): ?>
        <div class="nav-section-title">Mi Portal</div>
        <a href="/vivimostodos/residente/reservar.php" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'reservar') ? 'active' : '' ?>">
            <span class="icon">➕</span> Nueva Reserva
        </a>
        <a href="/vivimostodos/residente/mis_reservas.php" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'mis_reservas') ? 'active' : '' ?>">
            <span class="icon">📅</span> Mis Reservas
        </a>
        <a href="/vivimostodos/residente/inventario.php" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'inventario') ? 'active' : '' ?>">
            <span class="icon">📦</span> Inventario
        </a>
        <?php if ($notifCount > 0): ?>
        <div class="nav-section-title">Alertas</div>
        <a href="#" class="sidebar-link" id="notif-sidebar-link">
            <span class="icon">🔔</span> Notificaciones
            <span class="badge-count"><?= $notifCount ?></span>
        </a>
        <?php endif; ?>

        <?php elseif ($rol === 'SUPERVISOR'): ?>
        <div class="nav-section-title">Supervisión</div>
        <a href="/vivimostodos/supervisor/cronograma.php" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'cronograma') ? 'active' : '' ?>">
            <span class="icon">📋</span> Cronograma
        </a>
        <a href="/vivimostodos/supervisor/verificar_inventario.php" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'verificar') ? 'active' : '' ?>">
            <span class="icon">🔍</span> Verificar Inventario
        </a>
        <?php endif; ?>

        <div class="nav-section-title" style="margin-top:auto">Cuenta</div>
        <a href="/vivimostodos/logout.php" class="sidebar-link" style="color:#f87171">
            <span class="icon">🚪</span> Cerrar Sesión
        </a>
    </nav>
</aside>

<!-- MAIN WRAPPER START -->
<div class="main-wrapper">
    <!-- TOPBAR -->
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm d-md-none" id="sidebar-toggle">☰</button>
            <span class="topbar-title"><?= $pageTitle ?? 'Panel' ?></span>
        </div>
        <div class="topbar-right">
            <div class="position-relative">
                <button class="notif-btn" id="notif-btn" title="Notificaciones">
                    🔔
                    <span class="notif-badge" id="notif-badge" style="display:<?= $notifCount > 0 ? 'flex' : 'none' ?>"><?= $notifCount ?></span>
                </button>
                <div class="notif-dropdown" id="notif-dropdown">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <strong style="font-size:.85rem">Notificaciones</strong>
                    </div>
                    <div id="notif-list">
                        <div class="p-3 text-center text-muted" style="font-size:.82rem">Cargando…</div>
                    </div>
                </div>
            </div>
            <span style="font-size:.8rem;color:var(--text-muted)"><?= date('d/m/Y') ?></span>
        </div>
    </div>
    <!-- PAGE CONTENT -->
    <div class="page-content">
