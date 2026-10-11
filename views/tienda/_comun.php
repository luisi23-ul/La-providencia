<?php
include __DIR__ . '/../_sistema.php';
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$tasaTienda = (float) ($sistema->tasa_manual ?? 0) ?: 526.87;
$usd = fn($v) => '$' . number_format((float) $v, 2);
$bs = fn($v) => number_format((float) $v * $tasaTienda, 2, ',', '.') . ' Bs';
$foto = fn($archivo) => $archivo ? 'public/uploads/' . rawurlencode($archivo) : '';
$nombresCategoria = [];
foreach ($categorias ?? [] as $cat) $nombresCategoria[(int) $cat->id] = $cat->nombre_categoria;

if (!function_exists('tarjetaProducto')) {
    function tarjetaProducto($p, $e, $usd, $bs, $foto, $nombresCategoria = []) {
        $id = (int) $p->id;
        $nombre = $p->nombre_producto;
        $categoria = $p->nombre_categoria ?? ($nombresCategoria[(int) ($p->id_categoria ?? 0)] ?? '');
        $stock = (int) ($p->stock_online ?? $p->stock ?? 0);
        $url = 'index.php?action=ver_producto&amp;id=' . $id;
        $img = $foto($p->imagen ?? '');
        $h = '<li class="prod"><div class="prod-foto"><a href="' . $url . '" tabindex="-1" aria-hidden="true">';
        $h .= $img ? '<img src="' . $e($img) . '" alt="' . $e($nombre) . '" loading="lazy" width="600" height="800">' : '<span class="sin-foto"><i class="ti ti-hanger"></i></span>';
        $h .= '</a></div>';
        $h .= '<div class="prod-meta"><span>' . $e($categoria) . '</span>' . ($stock <= 0 ? '<b class="agotado">Agotado</b>' : ($stock <= 2 ? '<b>Quedan ' . $stock . '</b>' : '')) . '</div>';
        $h .= '<h3><a href="' . $url . '">' . $e($nombre) . '</a></h3>';
        $h .= '<div class="precio"><b>' . $usd($p->precio) . '</b><span>' . $bs($p->precio) . '</span></div></li>';
        return $h;
    }
}
