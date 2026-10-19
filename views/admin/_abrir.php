<?php
require_once __DIR__ . '/_claves.php';
$permisosUsuario = $_SESSION['permisos'] ?? [];
$rol = $_SESSION[nombre_clave('sesion.rol')] ?? '';
$panelMaster = $panelMaster ?? false;

$equivalencias = [
    'gestionar_productos' => ['gestionar_productos', 'listar_productos', 'cargar_producto'],
    'categorias'          => ['categorias', 'gestionar_categorias'],
    'ajustes_inventario'  => ['ajustes_inventario', 'ajustes_stock'],
    'kardex'              => ['kardex', 'ver_kardex'],
    'ventas'              => ['ventas', 'listar_ventas'],
    'pagos'               => ['pagos', 'verificar_pagos', 'pagos_banco'],
    'retiros'             => ['retiros', 'retiro_pedidos'],
    'punto_venta'         => ['punto_venta'],
    'proveedores'         => ['proveedores', 'gestionar_proveedores'],
    'compras'             => ['compras', 'gestionar_compras'],
    'clientes'            => ['clientes', 'gestionar_clientes'],
    'metodos_pago'        => ['metodos_pago', 'gestionar_metodos_pago'],
    'graficos'            => ['graficos', 'ver_estadisticas']
];

$puede = function($codigo) use ($rol, $permisosUsuario, $equivalencias) {
    if (es_rol('master', $rol) || in_array('*', $permisosUsuario, true)) {
        return true;
    }

    if (in_array($codigo, $permisosUsuario, true)) {
        return true;
    }

    if (isset($equivalencias[$codigo])) {
        foreach ($equivalencias[$codigo] as $posibleCodigo) {
            if (in_array($posibleCodigo, $permisosUsuario, true)) {
                return true;
            }
        }
    }

    return false;
};

$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$usd = fn($v) => '$' . number_format((float) $v, 2);

require_once __DIR__ . '/../../models/BcvService.php';
$bcvService = new BcvService();
$tasa = $bcvService->obtenerTasaActiva() ?: 875.65;
$bs = fn($v) => number_format((float) $v * (float) $tasa, 2, ',', '.') . ' Bs';

if (empty($aviso) && !empty($_SESSION['aviso'])) {
    $avisoSesion = $_SESSION['aviso'];
    unset($_SESSION['aviso']);
    $aviso = is_array($avisoSesion) ? ($avisoSesion[0] ?? '') : (string) $avisoSesion;
    $avisoTipo = is_array($avisoSesion) ? ($avisoSesion[1] ?? 'ok') : 'ok';
}
if (empty($aviso) && !empty($_SESSION['aviso_error'])) {
    $aviso = (string) $_SESSION['aviso_error'];
    $avisoTipo = 'error';
    unset($_SESSION['aviso_error']);
}
if (empty($aviso) && !empty($_SESSION['alerta']) && is_array($_SESSION['alerta'])) {
    $aviso = (string) ($_SESSION['alerta']['texto'] ?? $_SESSION['alerta']['titulo'] ?? '');
    $avisoTipo = ($_SESSION['alerta']['tipo'] ?? '') === 'error' ? 'error' : 'ok';
    unset($_SESSION['alerta']);
}
include __DIR__ . '/../_sistema.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $e(implode(' · ', array_filter([$tituloPagina ?? '', $nombreTienda]))) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Open+Sans:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="public/css/panel.css?v=<?= filemtime(__DIR__ . '/../../public/css/panel.css') ?>">
</head>
<body>
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