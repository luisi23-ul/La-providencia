<?php
include __DIR__ . '/tienda/_comun.php';
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
?>
  <section class="lista" aria-labelledby="t-cat" style="padding-top:40px">
    <div class="filtros-cat">
      <div class="filtros-fila">
        <h1 class="sec-tit" id="t-cat"><?= $e($titulo) ?></h1>
        <form class="buscador" id="buscar" action="index.php" method="get" role="search">
          <input type="hidden" name="action" value="ver_catalogo">
          <?php if ($catActual): ?><input type="hidden" name="id_categoria" value="<?= $catActual ?>"><?php endif; ?>
          <i class="ti ti-search" aria-hidden="true"></i>
          <input type="search" name="busqueda" value="<?= $e($busqueda) ?>" placeholder="Busca una prenda" aria-label="Buscar en el catálogo">
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
      <p class="ped-hola" style="margin:0"><?= $total ?> <?= $total === 1 ? 'prenda' : 'prendas' ?><?= $busqueda !== '' ? ' para «' . $e($busqueda) . '»' : '' ?></p>
    </div>
    <ul class="rejilla">
      <?php foreach ($listaProductos as $p) echo tarjetaProducto($p, $e, $usd, $bs, $foto, $nombresCategoria); ?>
      <?php if (!$listaProductos): ?><li class="vacio-lista">No encontramos prendas con esos filtros. <a href="index.php?action=ver_catalogo">Ver todo el catálogo</a></li><?php endif; ?>
    </ul>
  </section>
<?php $seccionPie = 'catalogo'; include __DIR__ . '/tienda/_pie.php'; ?>
