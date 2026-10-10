<?php
$menu = 'listado_productos';
$tituloPagina = 'Productos';
$listaProductos = $listaProductos ?? $productos ?? [];
$categorias = $categorias ?? array_map(fn($id, $n) => (object) ['id' => $id, 'nombre_categoria' => $n], [1, 2, 3], ['Aluminio', 'Lencería', 'Plástico']);
include __DIR__ . '/admin/_abrir.php';
$nombreCat = [];
foreach ($categorias as $c) $nombreCat[$c->id] = $c->nombre_categoria;
$filtro = $_GET['stock'] ?? '';
$catSel = $_GET['categoria'] ?? '';
$estadoDe = function ($p) {
    $total = (int) $p->stock + (int) ($p->stock_online ?? 0);
    if ($total === 0) return 'agot';
    return (int) $p->stock < (int) ($p->stock_minimo ?? 5) ? 'bajo' : 'ok';
};
$conteo = ['bajo' => 0, 'agot' => 0];
foreach ($listaProductos as $p) { $s = $estadoDe($p); if ($s !== 'ok') $conteo['bajo']++; if ($s === 'agot') $conteo['agot']++; }
$lista = array_filter($listaProductos, function ($p) use ($filtro, $catSel, $estadoDe) {
    if ($catSel !== '' && (string) $p->id_categoria !== $catSel) return false;
    if ($filtro === 'bajo') return $estadoDe($p) !== 'ok';
    if ($filtro === 'agotado') return $estadoDe($p) === 'agot';
    return true;
});
$url = fn($stock) => 'index.php?' . http_build_query(array_filter(['action' => 'listado_productos', 'stock' => $stock, 'categoria' => $catSel]));
?>
<section class="contenido" id="p-inventario">
  <header class="cab">
    <div class="cab-txt"><small>Inventario</small><h2>Productos</h2></div>
    <div class="cab-acc">
      <a href="index.php?action=exportar_pdf&tipo=inventario" target="_blank" class="btn btn-sec"><svg class="i"><use href="#i-archivo"/></svg>PDF</a>
      <a href="index.php?action=exportar_excel&tipo=inventario" class="btn btn-sec"><svg class="i"><use href="#i-archivo"/></svg>Excel</a>
      <a href="index.php?action=formulario_producto" class="btn btn-pri"><svg class="i"><use href="#i-mas"/></svg>Nuevo producto</a>
    </div>
  </header>

  <form class="herr" method="GET" action="index.php">
    <input type="hidden" name="action" value="listado_productos">
    <?php if ($filtro): ?><input type="hidden" name="stock" value="<?= $e($filtro) ?>"><?php endif; ?>
    <label class="buscar"><svg class="i"><use href="#i-buscar"/></svg><input type="search" placeholder="Buscar por código o nombre" aria-label="Buscar producto" data-filtro-tabla="tabla-productos" data-filtro-vacio="vacio-productos"></label>
    <select class="selector" name="categoria" aria-label="Categoría" onchange="this.form.submit()">
      <option value="">Todas las categorías</option>
      <?php foreach ($categorias as $c): ?><option value="<?= (int) $c->id ?>" <?= $catSel === (string) $c->id ? 'selected' : '' ?>><?= $e($c->nombre_categoria) ?></option><?php endforeach; ?>
    </select>
    <div class="seg" role="group" aria-label="Filtrar por stock">
      <a href="<?= $e($url('')) ?>" class="seg-link" aria-current="<?= $filtro === '' ? 'page' : 'false' ?>">Todos<span><?= count($listaProductos) ?></span></a>
      <a href="<?= $e($url('bajo')) ?>" class="seg-link" aria-current="<?= $filtro === 'bajo' ? 'page' : 'false' ?>">Stock bajo<span><?= $conteo['bajo'] ?></span></a>
      <a href="<?= $e($url('agotado')) ?>" class="seg-link" aria-current="<?= $filtro === 'agotado' ? 'page' : 'false' ?>">Agotados<span><?= $conteo['agot'] ?></span></a>
    </div>
  </form>

  <section class="tabla-caja" aria-label="Listado de productos">
    <div class="tabla-scroll">
      <table id="tabla-productos">
        <thead><tr><th class="c-media">Código</th><th>Producto</th><th class="c-media">Categoría</th><th class="num">Precio</th><th class="num">Tienda</th><th class="num">Online</th><th class="num">Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($lista as $p):
            $codigo = $p->codigo ?? ('P-' . str_pad($p->id, 4, '0', STR_PAD_LEFT));
            $cat = $p->nombre_categoria ?? ($nombreCat[$p->id_categoria] ?? 'Sin categoría');
            $costo = (float) ($p->costo_compra ?? 0);
            $margen = $p->precio > 0 && $costo > 0 ? round(($p->precio - $costo) / $costo * 100) : null;
            $s = $estadoDe($p); ?>
          <tr>
            <td class="cod c-media"><?= $e($codigo) ?></td>
            <td><div class="prod"><?php if ($p->imagen): ?><img class="foto" src="public/uploads/<?= $e($p->imagen) ?>" alt="" width="44" height="44" loading="lazy"><?php endif; ?><div><b><?= $e($p->nombre_producto) ?></b><span><?= $e($p->descripcion ?? '') ?></span><span class="si-estrecho"><span class="cod"><?= $e($codigo) ?></span>, <?= $e($cat) ?></span></div></div></td>
            <td class="c-media"><span class="chip"><?= $e($cat) ?></span></td>
            <td class="num"><b><?= $usd($p->precio) ?></b><?php if ($costo): ?><br><span class="margen">costo <?= $usd($costo) ?><?= $margen !== null ? ', +' . $margen . '%' : '' ?></span><?php endif; ?></td>
            <td class="num"><div class="stk"><b><?= (int) $p->stock ?></b><?php if ($s === 'bajo'): ?><span class="est bajo">Bajo, mín. <?= (int) ($p->stock_minimo ?? 5) ?></span><?php elseif ($s === 'agot'): ?><span class="est agot">Agotado</span><?php endif; ?></div></td>
            <td class="num"><div class="stk"><b><?= (int) ($p->stock_online ?? 0) ?></b></div></td>
            <td><div class="acc">
              <a href="index.php?action=editar&id=<?= (int) $p->id ?>" class="ic-btn" aria-label="Editar <?= $e($p->nombre_producto) ?>"><svg class="i"><use href="#i-editar"/></svg></a>
              <a href="index.php?action=ajustes_inventario&producto=<?= (int) $p->id ?>" class="ic-btn" aria-label="Ajustar stock de <?= $e($p->nombre_producto) ?>"><svg class="i"><use href="#i-ajuste"/></svg></a>
              <a href="index.php?action=eliminar_producto&id=<?= (int) $p->id ?>" class="ic-btn" aria-label="Quitar <?= $e($p->nombre_producto) ?> del catálogo" data-confirmar="¿Quitar <?= $e($p->nombre_producto) ?> del catálogo?" data-confirmar-texto="No se borra: deja de mostrarse." data-confirmar-boton="Quitar del catálogo" data-confirmar-tipo="aviso"><svg class="i"><use href="#i-basura"/></svg></a>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <ul class="tarjetas-prod">
      <?php foreach ($lista as $p):
          $codigo = $p->codigo ?? ('P-' . str_pad($p->id, 4, '0', STR_PAD_LEFT));
          $cat = $p->nombre_categoria ?? ($nombreCat[$p->id_categoria] ?? 'Sin categoría');
          $s = $estadoDe($p); ?>
        <li>
          <?php if ($p->imagen): ?><img class="foto grande" src="public/uploads/<?= $e($p->imagen) ?>" alt="" width="56" height="56" loading="lazy"><?php else: ?><span></span><?php endif; ?>
          <div class="t-nom"><b><?= $e($p->nombre_producto) ?></b><span><?= $e($codigo) ?>, <?= $e($cat) ?></span></div>
          <div class="t-precio"><?= $usd($p->precio) ?></div>
          <div class="t-stock">Tienda <b><?= (int) $p->stock ?></b>, online <b><?= (int) ($p->stock_online ?? 0) ?></b> <?php if ($s === 'bajo'): ?><span class="est bajo">Bajo</span><?php elseif ($s === 'agot'): ?><span class="est agot">Agotado</span><?php endif; ?></div>
          <div class="t-acc"><a href="index.php?action=editar&id=<?= (int) $p->id ?>" class="btn btn-sec btn-peq"><svg class="i"><use href="#i-editar"/></svg>Editar</a><a href="index.php?action=ajustes_inventario&producto=<?= (int) $p->id ?>" class="btn btn-sec btn-peq"><svg class="i"><use href="#i-ajuste"/></svg>Ajustar stock</a></div>
        </li>
      <?php endforeach; ?>
    </ul>
    <div class="vacio-filtro" id="vacio-productos" <?= $lista ? 'hidden' : '' ?>>Ningún producto coincide con la búsqueda.</div>
    <footer class="tabla-pie"><span>Mostrando <?= count($lista) ?> de <?= count($listaProductos) ?> productos</span></footer>
  </section>
</section>
<?php include __DIR__ . '/admin/_cerrar.php'; ?>
