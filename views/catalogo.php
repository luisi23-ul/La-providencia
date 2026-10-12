<?php
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/tienda/_comun.php';

$listaProductos = $listaProductos ?? [];
$categorias = $categorias ?? [];
$tallas = $tallas ?? [];
$busqueda = trim((string) ($_GET['busqueda'] ?? ''));
$catActual = (int) ($_GET['id_categoria'] ?? 0);
$tallaActual = (string) ($_GET['talla'] ?? '');
$titulo = $catActual && isset($nombresCategoria[$catActual]) ? $nombresCategoria[$catActual] : 'Catálogo';

$enlace = function ($cambios) use ($busqueda, $catActual, $tallaActual) {
    $q = array_filter(array_merge(['action' => 'ver_catalogo', 'busqueda' => $busqueda, 'id_categoria' => $catActual ?: '', 'talla' => $tallaActual], $cambios), fn($v) => $v !== '' && $v !== null);
    return 'index.php?' . htmlspecialchars(http_build_query($q), ENT_QUOTES);
};
$total = count($listaProductos);

if (!function_exists('tarjetaProducto')) {
    function tarjetaProducto($p, $e, $usd, $bs, $foto, $nombresCategoria = []) {
        $id = (int) ($p->id ?? 0);
        $nombre = $p->nombre_producto ?? '';
        $catId = (int) ($p->id_categoria ?? 0);
        $categoria = $nombresCategoria[$catId] ?? ($p->nombre_categoria ?? '');
        $stock = (int) ($p->stock_online > 0 ? $p->stock_online : ($p->stock ?? 0));
        $precio = (float) ($p->precio ?? 0);
        $url = 'index.php?action=ver_producto&amp;id=' . $id;
        $img = $foto($p->imagen ?? '');

        $h = '<li class="prod" data-id="' . $id . '">';
        $h .= '<div class="prod-foto"><a href="' . $url . '" tabindex="-1" aria-hidden="true">';
        $h .= $img ? '<img src="' . $e($img) . '" alt="' . $e($nombre) . '" loading="lazy" width="600" height="800">' : '';
        $h .= '</a></div>';
        $h .= '<div class="prod-meta"><span>' . $e($categoria) . '</span>' . ($stock <= 0 ? '<b class="agotado">Agotado</b>' : '') . '</div>';
        $h .= '<h3><a href="' . $url . '">' . $e($nombre) . '</a></h3>';
        $h .= '<div class="precio"><b>' . $usd($precio) . '</b><span>' . $bs($precio) . '</span></div>';
        
        if ($stock > 0) {
            // Redirige al detalle para seleccionar atributos (talla, color, modelo)
            $h .= '<a href="' . $url . '" class="btn btn-sm" style="margin-top: 10px; width: 100%; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">';
            $h .= '<i class="ti ti-adjustments-horizontal"></i> Elegir opciones</a>';
        }
        
        $h .= '</li>';
        return $h;
    }
}
?>
  <section class="lista" aria-labelledby="t-cat" style="padding-top:40px">
    <div class="filtros-cat">
      <div class="filtros-fila">
        <h1 class="sec-tit" id="t-cat"><?= $e($titulo) ?></h1>
        <form class="buscador" id="buscar" action="index.php" method="get" role="search">
          <input type="hidden" name="action" value="ver_catalogo">
          <?php if ($catActual): ?><input type="hidden" name="id_categoria" value="<?= $catActual ?>"><?php endif; ?>
          <i class="ti ti-search" aria-hidden="true"></i>
          <input type="search" name="busqueda" value="<?= $e($busqueda) ?>" placeholder="Busca un producto" aria-label="Buscar en el catálogo">
        </form>
      </div>
      <nav class="chips" aria-label="Categorías">
        <a href="<?= $enlace(['id_categoria' => '']) ?>"<?= !$catActual ? ' aria-current="true"' : '' ?>>Todo</a>
        <?php foreach ($categorias as $c): ?><a href="<?= $enlace(['id_categoria' => (int) $c->id]) ?>"<?= $catActual === (int) $c->id ? ' aria-current="true"' : '' ?>><?= $e($c->nombre_categoria) ?></a><?php endforeach; ?>
      </nav>
      <?php if ($tallas): ?>
      <nav class="chips" aria-label="Tallas">
        <a href="<?= $enlace(['talla' => '']) ?>"<?= $tallaActual === '' ? ' aria-current="true"' : '' ?>>Todas las tallas</a>
        <?php foreach ($tallas as $t): ?><a href="<?= $enlace(['talla' => $t]) ?>"<?= $tallaActual === (string) $t ? ' aria-current="true"' : '' ?>><?= $e($t) ?></a><?php endforeach; ?>
      </nav>
      <?php endif; ?>
      <p class="ped-hola" style="margin:0"><?= $total ?> <?= $total === 1 ? 'producto' : 'productos' ?><?= $busqueda !== '' ? ' para «' . $e($busqueda) . '»' : '' ?></p>
    </div>
    <ul class="rejilla">
      <?php foreach ($listaProductos as $p) echo tarjetaProducto($p, $e, $usd, $bs, $foto, $nombresCategoria); ?>
      <?php if (!$listaProductos): ?><li class="vacio-lista">No encontramos productos con esos filtros. <a href="index.php?action=ver_catalogo">Ver todo el catálogo</a></li><?php endif; ?>
    </ul>
  </section>

  <script>
  function agregarYRedirigir(id, nombre, precio, img) {
      var CLAVE = 'aura-pedido';
      var items = [];
      try {
          items = JSON.parse(localStorage.getItem(CLAVE)) || [];
      } catch (e) {
          items = [];
      }

      var claveProd = id + '|';
      var it = items.find(function(i) { return i.id === +id; });

      if (it) {
          it.cantidad += 1;
      } else {
          items.push({
              clave: claveProd,
              id: +id,
              nombre: nombre,
              precio: parseFloat(precio),
              imagen: img,
              variante: '',
              cantidad: 1,
              stock: 99
          });
      }

      localStorage.setItem(CLAVE, JSON.stringify(items));
      window.location.href = 'index.php?action=ver_carrito';
  }
  </script>
<?php $seccionPie = 'catalogo'; include __DIR__ . '/tienda/_pie.php'; ?>