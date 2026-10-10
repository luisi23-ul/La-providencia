<?php
$permisosUsuario = $_SESSION['permisos'] ?? [];
$rol = $_SESSION['rol'] ?? 0;
$panelMaster = $panelMaster ?? false;
$puede = fn($codigo) => $rol == 1 || in_array($codigo, $permisosUsuario);
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$usd = fn($v) => '$' . number_format((float) $v, 2);
$tasa = $tasa ?? 526.8694;
$bs = fn($v) => number_format((float) $v * $tasa, 2) . ' Bs';
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Open+Sans:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="public/css/panel.css?v=<?= filemtime(__DIR__ . '/../../public/css/panel.css') ?>">
<?php include __DIR__ . '/_iconos.php'; ?>
<section class="panel<?= $panelMaster ? ' modo-master' : '' ?><?= !empty($modoPantalla) ? ' modo-' . $modoPantalla : '' ?>" id="panel" data-tasa="<?= $e($tasa) ?>" data-aviso="<?= $e($aviso ?? '') ?>" data-aviso-tipo="<?= $e($avisoTipo ?? 'ok') ?>">
<?php include __DIR__ . '/_menu.php'; ?>
<div class="velo" id="velo"></div>
<div class="panel-contenido">
  <div class="barra-movil">
    <button type="button" id="abrir-menu" aria-label="Abrir menú"><svg class="i"><use href="#i-menu"/></svg></button>
    <b><?= $e($tituloPagina ?? '') ?></b>
    <span class="avatar"><?= $e($iniciales) ?></span>
  </div>
