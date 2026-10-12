<?php
// Conexión y carga global de la configuración de la tienda
try {
    $dbSis = $GLOBALS['db'] ?? ($db ?? null);
    if (!$dbSis && class_exists('Conexion')) {
        $dbSis = Conexion::conectar();
    }
    
    if ($dbSis) {
        $stmtSis = $dbSis->query("SELECT * FROM sistema LIMIT 1");
        $sistema = $stmtSis->fetch(PDO::FETCH_OBJ);
    }
} catch (Exception $e) {
    $sistema = null;
}

// Variables directas de respaldo si no existiera la fila
$nombreTienda    = $sistema->nombre_empresa ?? 'La Providencia';
$rifTienda       = $sistema->rif ?? '';
$telefonoTienda  = $sistema->telefono ?? '';
$whatsappTienda  = $sistema->whatsapp ?? '';
$direccionTienda = $sistema->direccion ?? '';
$emailTienda     = $sistema->email_contacto ?? '';
$logoTienda      = !empty($sistema->logo_path) ? $sistema->logo_path : 'public/img/logo-removebg.png';

$heroTitulo      = $sistema->titulo_hero ?? 'Calidad, resistencia, confianza';
$heroSubtitulo   = $sistema->subtitulo_hero ?? 'Soluciones integrales en plásticos y mobiliario para transformar cada rincón de tu hogar.';
$footerTexto     = $sistema->footer_texto ?? 'Calidad y confianza en cada rincón de tu hogar';

// Colores personalizados
$colorPrimario   = $sistema->color_primario ?? '#1b34da';
$colorSecundario = $sistema->color_secundario ?? '#363c54';
$colorTerciario  = $sistema->color_terciario ?? '#dcdcea';
$colorCuaternario= $sistema->color_cuaternario ?? '#282b2f';

$inicialTienda   = mb_substr(trim($nombreTienda), 0, 1);