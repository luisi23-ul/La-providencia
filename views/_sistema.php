<?php
if (!isset($sistema)) {
    $sistema = null;
    try {
        require_once __DIR__ . '/../models/ConfiguracionModel.php';
        if (isset($GLOBALS['db'])) $sistema = (new ConfiguracionModel($GLOBALS['db']))->obtener() ?: null;
    } catch (Throwable $err) {
        $sistema = null;
    }
}
$nombreTienda = trim((string) ($sistema->nombre_empresa ?? '')) ?: 'Mi tienda';
$inicialTienda = mb_strtoupper(mb_substr($nombreTienda, 0, 1, 'UTF-8'), 'UTF-8');
