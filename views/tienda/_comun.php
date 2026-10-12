<?php
include __DIR__ . '/../_sistema.php';
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

require_once __DIR__ . '/../../models/BcvService.php';
$bcvService = new BcvService();
$tasaTienda = $bcvService->obtenerTasaActiva() ?: ((float)($sistema->tasa_manual ?? 526.87));

$usd = fn($v) => '$' . number_format((float) $v, 2);
$bs = fn($v) => number_format((float) $v * $tasaTienda, 2, ',', '.') . ' Bs';
$foto = fn($archivo) => $archivo ? 'public/uploads/' . rawurlencode($archivo) : '';
$nombresCategoria = [];
foreach ($categorias ?? [] as $cat) $nombresCategoria[(int) $cat->id] = $cat->nombre_categoria;
