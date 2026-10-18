<?php
include __DIR__ . '/../_sistema.php';
require_once __DIR__ . '/../admin/_claves.php';
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

require_once __DIR__ . '/../../models/BcvService.php';
$bcvService = new BcvService();
$tasaTienda = $bcvService->obtenerTasaActiva() ?: ((float)($sistema->tasa_manual ?? 526.87));

$usd = fn($v) => '$' . number_format((float) $v, 2);
$bs = fn($v) => number_format((float) $v * $tasaTienda, 2, ',', '.') . ' Bs';
$foto = fn($archivo) => $archivo ? 'public/uploads/' . rawurlencode($archivo) : '';
$nombresCategoria = [];
foreach ($categorias ?? [] as $cat) $nombresCategoria[clave('categoria', $cat)] = $cat->nombre_categoria;

$conVariantes = [];
$catVisibles = [];
try {
    $dbTienda = $GLOBALS['db'] ?? Database::connect();
    $kVP = nombre_clave('variante.producto'); $kC = nombre_clave('categoria'); $kCP = nombre_clave('categoria.padre'); $kPC = nombre_clave('producto.categoria');
    foreach ($dbTienda->query("SELECT DISTINCT $kVP FROM producto_variantes WHERE stock > 0")->fetchAll(PDO::FETCH_COLUMN) as $idVar) $conVariantes[(string) $idVar] = true;
    foreach ($dbTienda->query("SELECT DISTINCT c.$kC, c.$kCP FROM productos p JOIN categorias c ON c.$kC = p.$kPC WHERE p.estado = 1 AND " . sql_sin_borrar('p'))->fetchAll(PDO::FETCH_OBJ) as $cv) {
        $catVisibles[clave('categoria', $cv)] = true;
        if (ref('categoria.padre', $cv) !== '') $catVisibles[ref('categoria.padre', $cv)] = true;
    }
} catch (Throwable $t) {
    $conVariantes = [];
    $catVisibles = [];
}
$soloConProductos = fn($lista) => $catVisibles ? array_values(array_filter(sin_relleno($lista, 'categoria'), fn($c) => isset($catVisibles[clave('categoria', $c)]))) : $lista;

if (!function_exists('tarjetaProducto')) {
    function tarjetaProducto($p, $e, $usd, $bs, $foto, $nombresCategoria = [], $conVariantes = []) {
        $id = clave('producto', $p);
        $nombre = $p->nombre_producto ?? '';
        $categoria = $nombresCategoria[ref('producto.categoria', $p)] ?? categoria_de($p);
        $stock = (int) ($p->stock ?? 0);
        $precio = (float) ($p->precio ?? 0);
        $url = 'index.php?action=ver_producto&amp;' . url_clave('producto', $p);
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
            $h .= '<button type="button" class="btn prod-btn" data-agregar-rapido data-id="' . $e($id) . '" data-nombre="' . $e($nombre) . '" data-precio="' . $precio . '" data-imagen="' . $e($img) . '" data-stock="' . $stock . '" aria-label="Agregar ' . $e($nombre) . ' a mi pedido"><i class="ti ti-shopping-bag-plus" aria-hidden="true"></i>Agregar</button>';
        }
        return $h . '</li>';
    }
}
