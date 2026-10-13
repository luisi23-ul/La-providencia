<?php
include __DIR__ . '/../_sistema.php';
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
$clienteSesion = !empty($_SESSION['id_usuario']) && (int) ($_SESSION['rol'] ?? 0) === 3;
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
<style>
  :root {
    --color-primario: <?= $e($sistema->color_primario ?? '#1b34da') ?>;
    --color-secundario: <?= $e($sistema->color_secundario ?? '#363c54') ?>;
    --color-terciario: <?= $e($sistema->color_terciario ?? '#dcdcea') ?>;
    --color-cuaternario: <?= $e($sistema->color_cuaternario ?? '#282b2f') ?>;
  }
</style>
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