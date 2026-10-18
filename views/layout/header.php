<?php
include __DIR__ . '/../_sistema.php';
require_once __DIR__ . '/../admin/_claves.php';
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

require_once __DIR__ . '/../../models/BcvService.php';
$bcvService = new BcvService();
$tasaTienda = $bcvService->obtenerTasaActiva() ?: ((float) ($sistema->tasa_manual ?? 0) ?: 526.87);

$waNumeroConfig = preg_replace('/\D/', '', (string) ($sistema->whatsapp ?? ''));

if (empty($waNumeroConfig)) {
    $waTienda = '584127818865';
} else {
    if (str_starts_with($waNumeroConfig, '0')) {
        $waTienda = '58' . substr($waNumeroConfig, 1);
    } elseif (!str_starts_with($waNumeroConfig, '58')) {
        $waTienda = '58' . $waNumeroConfig;
    } else {
        $waTienda = $waNumeroConfig;
    }
}

$accionActual = $action ?? 'inicio';
$seccionTienda = [
    'inicio' => 'inicio', 'ver_catalogo' => 'catalogo', 'ver_producto' => 'catalogo',
    'ver_carrito' => 'pedido', 'login_usuario' => 'cuenta', 'registro' => 'cuenta', 'mis_pedidos' => 'cuenta',
][$accionActual] ?? 'inicio';
$clienteSesion = usuario_sesion() !== '' && es_rol('cliente');
$enlaceCuenta = $clienteSesion ? 'index.php?action=mis_pedidos' : 'index.php?action=login_usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $e($tituloTienda ?? $nombreTienda) ?></title>
<meta name="description" content="<?= $e($sistema->footer_texto ?? 'Catálogo de la tienda') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,600&family=Jost:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="public/vendor/tabler/tabler-icons.min.css">
<link rel="stylesheet" href="public/css/tienda.css?v=<?= filemtime(__DIR__ . '/../../public/css/tienda.css') ?>">
<?php
$colorTienda = function ($valor) {
    $valor = strtolower(trim((string) $valor));
    $fabrica = ['#0052d4', '#1b34da', '#0f172a', '#363c54', '#020617', '#dcdcea', '#64748b', '#282b2f'];
    return preg_match('/^#[0-9a-f]{6}$/', $valor) && !in_array($valor, $fabrica, true) ? $valor : null;
};
$luz = function ($hex) {
    $c = array_map(fn($p) => hexdec($p) / 255, str_split(substr($hex, 1), 2));
    $c = array_map(fn($v) => $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4, $c);
    return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
};
$legibleConBlanco = function ($hex) use ($luz) {
    $c = array_map('hexdec', str_split(substr($hex, 1), 2));
    for ($i = 0; $i < 20 && 1.05 / ($luz(vsprintf('#%02x%02x%02x', $c)) + 0.05) < 4.5; $i++) $c = array_map(fn($v) => (int) round($v * 0.9), $c);
    return vsprintf('#%02x%02x%02x', $c);
};
$cPrincipal = $colorTienda($sistema->color_primario ?? '');
$cFondo = $colorTienda($sistema->color_secundario ?? '');
$cTexto = $colorTienda($sistema->color_terciario ?? '');
if ($cPrincipal) $cPrincipal = $legibleConBlanco($cPrincipal);
if ($cPrincipal || $cFondo || $cTexto): ?>
<style>
  :root {
<?php if ($cPrincipal): ?>    --acento: <?= $cPrincipal ?>; --acento-hover: color-mix(in srgb, <?= $cPrincipal ?> 85%, #000);
<?php endif; if ($cFondo): ?>    --fondo: <?= $cFondo ?>; --sup: color-mix(in srgb, <?= $cFondo ?> 35%, #fff);
<?php endif; if ($cTexto): ?>    --tinta: <?= $cTexto ?>; --tinta-2: color-mix(in srgb, <?= $cTexto ?> 75%, var(--fondo));
<?php endif; if ($cFondo || $cTexto): ?>    --suave: color-mix(in srgb, var(--fondo) 90%, var(--tinta)); --linea: color-mix(in srgb, var(--fondo) 86%, var(--tinta));
<?php endif; ?>  }
</style>
<?php endif; ?>
</head>
<body class="tienda" id="tienda" data-tasa="<?= $e($tasaTienda) ?>" data-wa="<?= $e($waTienda) ?>" data-tienda="<?= $e($nombreTienda) ?>" data-sesion="<?= $clienteSesion ? '1' : '0' ?>">
  <div class="franja"><i class="ti ti-brand-whatsapp" aria-hidden="true"></i>Arma tu pedido y lo confirmamos por WhatsApp</div>
  <header class="cab">
    <nav aria-label="Principal">
      <a href="index.php?action=inicio"<?= $seccionTienda === 'inicio' ? ' aria-current="page"' : '' ?>>Inicio</a>
      <a href="index.php?action=ver_catalogo"<?= $seccionTienda === 'catalogo' ? ' aria-current="page"' : '' ?>>Catálogo</a>
      <a href="#contacto">Contacto</a>
    </nav>
    <a class="logo" href="index.php?action=inicio" aria-label="<?= $e($nombreTienda) ?>, inicio"><?php
      if (!empty($sistema->logo_path) && file_exists($sistema->logo_path)): ?>
        <img src="<?= $e($sistema->logo_path) ?>" alt="<?= $e($nombreTienda) ?>" style="max-height: 42px; object-fit: contain;">
      <?php else:
        $partesNombre = explode(' ', $nombreTienda, 2);
        ?><b><?= $e($partesNombre[0]) ?></b><?php if (!empty($partesNombre[1])): ?><small><?= $e($partesNombre[1]) ?></small><?php endif; ?>
      <?php endif; ?></a>
    <div class="cab-acc">
      <a class="ic oculta-m" href="index.php?action=ver_catalogo#buscar" aria-label="Buscar"><i class="ti ti-search"></i></a>
      <a class="ic oculta-m" href="<?= $enlaceCuenta ?>" aria-label="<?= $clienteSesion ? 'Mis pedidos' : 'Entrar a mi cuenta' ?>"><i class="ti ti-user"></i></a>
      <a class="ic" href="index.php?action=ver_carrito" aria-label="Ver mi pedido" data-contador><i class="ti ti-shopping-bag"></i><span class="cuenta" hidden>0</span></a>
    </div>
  </header>