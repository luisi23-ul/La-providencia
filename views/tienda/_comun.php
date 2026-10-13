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

$conVariantes = [];
try {
    $dbTienda = $GLOBALS['db'] ?? Database::connect();
    foreach ($dbTienda->query("SELECT DISTINCT id_producto FROM producto_variantes WHERE stock > 0 OR stock_online > 0")->fetchAll(PDO::FETCH_COLUMN) as $idVar) $conVariantes[(int) $idVar] = true;
} catch (Throwable $t) {
    $conVariantes = [];
}

if (!function_exists('tarjetaProducto')) {
    function tarjetaProducto($p, $e, $usd, $bs, $foto, $nombresCategoria = [], $conVariantes = []) {
        $id = (int) ($p->id ?? 0);
        $nombre = $p->nombre_producto ?? '';
        $categoria = $nombresCategoria[(int) ($p->id_categoria ?? 0)] ?? ($p->nombre_categoria ?? '');
        $stock = (int) (($p->stock_online ?? 0) > 0 ? $p->stock_online : ($p->stock ?? 0));
        $precio = (float) ($p->precio ?? 0);
        $url = 'index.php?action=ver_producto&amp;id=' . $id;
        $img = $foto($p->imagen ?? '');
        $h = '<li class="prod"><div class="prod-foto"><a href="' . $url . '" tabindex="-1" aria-hidden="true">';
        $h .= $img ? '<img src="' . $e($img) . '" alt="' . $e($nombre) . '" loading="lazy" width="600" height="800">' : '<span class="sin-foto"><i class="ti ti-hanger"></i></span>';
        $h .= '</a></div>';
        $h .= '<div class="prod-meta"><span>' . $e($categoria) . '</span>' . ($stock <= 0 ? '<b class="agotado">Agotado</b>' : ($stock <= 2 ? '<b>Quedan ' . $stock . '</b>' : '')) . '</div>';
        $h .= '<h3><a href="' . $url . '">' . $e($nombre) . '</a></h3>';
        $h .= '<div class="precio"><b>' . $usd($precio) . '</b><span>' . $bs($precio) . '</span></div>';
        if ($stock > 0 && !empty($conVariantes[$id])) {
            $h .= '<a href="' . $url . '" class="btn sec prod-btn"><i class="ti ti-adjustments-horizontal" aria-hidden="true"></i>Elegir opciones</a>';
        } elseif ($stock > 0) {
            $h .= '<button type="button" class="btn prod-btn" data-agregar-rapido data-id="' . $id . '" data-nombre="' . $e($nombre) . '" data-precio="' . $precio . '" data-imagen="' . $e($img) . '" data-stock="' . $stock . '" aria-label="Agregar ' . $e($nombre) . ' a mi pedido"><i class="ti ti-shopping-bag-plus" aria-hidden="true"></i>Agregar</button>';
        }
        return $h . '</li>';
    }
}
