<?php
$root = str_replace('\\', '/', __DIR__);

$archivos = [
    'admin/dashboard.php',
    'admin/usuarios/index.php',
    'admin/usuarios/crear.php',
    'admin/usuarios/editar.php',
    'admin/usuarios/eliminar.php',
    'admin/inventario/index.php',
    'admin/inventario/crear.php',
    'admin/inventario/editar.php',
    'admin/inventario/eliminar.php',
    'admin/reservas/index.php',
    'admin/reservas/aprobar.php',
    'admin/reservas/rechazar.php',
    'admin/reservas/detalle.php',
    'admin/pagos/index.php',
    'admin/pagos/validar.php',
    'admin/reportes/index.php',
    'admin/reportes/exportar_pdf.php',
    'admin/reportes/exportar_excel.php',
    'admin/reportes/semaforo.php',
    'admin/notificaciones/enviar.php',
    'residente/reservar.php',
    'residente/mis_reservas.php',
    'residente/detalle_reserva.php',
    'residente/cancelar.php',
    'residente/pagar.php',
    'residente/inventario.php',
    'supervisor/cronograma.php',
    'supervisor/verificar_inventario.php',
    'api/disponibilidad.php',
    'api/graficos.php',
    'api/notificaciones.php',
    'api/marcar_leida.php',
    'includes/sidebar.php',
    'includes/footer.php',
    'index.php',
];

$patrones = [
    // require_once con rutas relativas dobles
    "#require_once\s+'(\.\./){1,3}config/session\.php'#"    => "require_once '$root/config/session.php'",
    "#require_once\s+'(\.\./){1,3}config/functions\.php'#"  => "require_once '$root/config/functions.php'",
    "#require_once\s+'(\.\./){1,3}config/database\.php'#"   => "require_once '$root/config/database.php'",
    "#require_once\s+'(\.\./){1,3}includes/header\.php'#"   => "require_once '$root/includes/header.php'",
    "#require_once\s+'(\.\./){1,3}includes/sidebar\.php'#"  => "require_once '$root/includes/sidebar.php'",
    "#require_once\s+'(\.\./){1,3}includes/footer\.php'#"   => "require_once '$root/includes/footer.php'",
    // include con rutas relativas
    "#include\s+'(\.\./){1,3}includes/header\.php'#"        => "include '$root/includes/header.php'",
    "#include\s+'(\.\./){1,3}includes/sidebar\.php'#"       => "include '$root/includes/sidebar.php'",
    "#include\s+'(\.\./){1,3}includes/footer\.php'#"        => "include '$root/includes/footer.php'",
    "#include\s+'(\.\./){1,3}config/session\.php'#"         => "include '$root/config/session.php'",
    "#include\s+'(\.\./){1,3}config/functions\.php'#"       => "include '$root/config/functions.php'",
    "#include\s+'(\.\./){1,3}config/database\.php'#"        => "include '$root/config/database.php'",
    // require_once con __DIR__
    "#require_once\s+__DIR__\s*\.\s*'(\.\./){1,3}config/session\.php'#"   => "require_once '$root/config/session.php'",
    "#require_once\s+__DIR__\s*\.\s*'(\.\./){1,3}config/functions\.php'#" => "require_once '$root/config/functions.php'",
    "#require_once\s+__DIR__\s*\.\s*'(\.\./){1,3}config/database\.php'#"  => "require_once '$root/config/database.php'",
];

$corregidos = 0;

foreach ($archivos as $archivo) {
    $ruta = $root . '/' . $archivo;
    if (!file_exists($ruta)) {
        echo "⚠️ No encontrado: $archivo<br>";
        continue;
    }
    $contenido = file_get_contents($ruta);
    $nuevo = $contenido;
    foreach ($patrones as $patron => $reemplazo) {
        $nuevo = preg_replace($patron, $reemplazo, $nuevo);
    }
    if ($nuevo !== $contenido) {
        file_put_contents($ruta, $nuevo);
        echo "✅ Corregido: <strong>$archivo</strong><br>";
        $corregidos++;
    } else {
        echo "⚪ Sin cambios: $archivo<br>";
    }
}

echo "<br><hr><strong>✅ Total corregidos: $corregidos archivos</strong><br><br>";
echo "<a href='/vivimostodos/index.php' style='background:#1a3c5e;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none'>👉 Ir al Login</a>";
echo "<br><br><span style='color:red;font-weight:bold'>⚠️ IMPORTANTE: Elimina este archivo fix_paths.php después de usarlo</span>";
?>