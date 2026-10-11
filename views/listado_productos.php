<?php
$menu = 'listado_productos';
$tituloPagina = 'Productos';
$listaProductos = $listaProductos ?? $productos ?? [];
$categorias = $categorias ?? array_map(fn($id, $n) => (object) ['id' => $id, 'nombre_categoria' => $n], [1, 2, 3], ['Aluminio', 'Lencería', 'Plástico']);
include __DIR__ . '/admin/_abrir.php';

// Definición de respaldo de helpers en caso de que _abrir.php no los declare
$e = $e ?? fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$usd = $usd ?? fn($n) => '$' . number_format((float)($n ?? 0), 2, '.', ',');

// Mapa de nombres y jerarquía de categorías
$nombreCat = [];
$padresCat = [];
$categoriasPrincipales = [];
$subcategoriasPorPadre = [];

foreach ($categorias as $c) {
    $nombreCat[$c->id] = $c->nombre_categoria;
    if (!empty($c->id_padre)) {
        $padresCat[$c->id] = $c->id_padre;
        $subcategoriasPorPadre[$c->id_padre][] = $c;
    } else {
        $categoriasPrincipales[] = $c;
    }
}

$filtro = $_GET['stock'] ?? '';
$catSel = $_GET['categoria'] ?? '';

$estadoDe = function ($p) {
    $total = (int) $p->stock + (int) ($p->stock_online ?? 0);
    if ($total === 0) return 'agot';
    return (int) $p->stock < (int) ($p->stock_minimo ?? 5) ? 'bajo' : 'ok';
};

$conteo = ['bajo' => 0, 'agot' => 0];
foreach ($listaProductos as $p) { 
    $s = $estadoDe($p); 
    if ($s !== 'ok') $conteo['bajo']++; 
    if ($s === 'agot') $conteo['agot']++; 
}

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

  <form class="filtros" method="GET" action="index.php" aria-label="Filtros">
    <input type="hidden" name="action" value="listado_productos">
    <?php if ($filtro): ?><input type="hidden" name="stock" value="<?= $e($filtro) ?>"><?php endif; ?>
    <label class="buscar"><svg class="i"><use href="#i-buscar"/></svg><input type="search" placeholder="Buscar por código o nombre" aria-label="Buscar producto" data-filtro-tabla="tabla-productos" data-filtro-vacio="vacio-productos"></label>
    <nav class="f-grupo" aria-label="Filtrar por stock">
      <a href="<?= $e($url('')) ?>" class="f-op" aria-current="<?= $filtro === '' ? 'page' : 'false' ?>">Todos<span class="f-cuenta"><?= count($listaProductos) ?></span></a>
      <a href="<?= $e($url('bajo')) ?>" class="f-op" aria-current="<?= $filtro === 'bajo' ? 'page' : 'false' ?>">Stock bajo<span class="f-cuenta"><?= $conteo['bajo'] ?></span></a>
      <a href="<?= $e($url('agotado')) ?>" class="f-op" aria-current="<?= $filtro === 'agotado' ? 'page' : 'false' ?>">Agotados<span class="f-cuenta"><?= $conteo['agot'] ?></span></a>
    </nav>
    <label class="f-select<?= $catSel !== '' ? ' activo' : '' ?>"><svg class="i"><use href="#i-etiqueta"/></svg>
      <select name="categoria" aria-label="Categoría" data-auto-enviar data-desplegable>
        <option value="">Todas las categorías</option>
        <?php foreach ($categoriasPrincipales as $cp): ?>
          <?php if (!empty($subcategoriasPorPadre[$cp->id])): ?>
            <optgroup label="<?= $e($cp->nombre_categoria) ?>">
              <option value="<?= (int) $cp->id ?>"<?= $catSel === (string) $cp->id ? ' selected' : '' ?>><?= $e($cp->nombre_categoria) ?> (General)</option>
              <?php foreach ($subcategoriasPorPadre[$cp->id] as $sub): ?>
                <option value="<?= (int) $sub->id ?>"<?= $catSel === (string) $sub->id ? ' selected' : '' ?>>↳ <?= $e($sub->nombre_categoria) ?></option>
              <?php endforeach; ?>
            </optgroup>
          <?php else: ?>
            <option value="<?= (int) $cp->id ?>"<?= $catSel === (string) $cp->id ? ' selected' : '' ?>><?= $e($cp->nombre_categoria) ?></option>
          <?php endif; ?>
        <?php endforeach; ?>
      </select>
    </label>
    <?php if ($filtro !== '' || $catSel !== ''): ?>
    <a href="index.php?action=listado_productos" class="f-quitar"><svg class="i"><use href="#i-x"/></svg>Quitar filtros</a>
    <?php endif; ?>
  </form>

  <section class="tabla-caja" aria-label="Listado de productos">
    <div class="tabla-scroll">
      <table id="tabla-productos" data-paginar="10" data-filtro-tambien=".tarjetas-prod">
        <thead>
          <tr>
            <th class="c-media">Código</th>
            <th>Producto</th>
            <th class="c-media">Categoría</th>
            <th class="num">Precio</th>
            <th class="num">Tienda</th>
            <th class="num">Online</th>
            <th class="num">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($lista as $p):
            $codigo = $p->codigo ?? ('P-' . str_pad($p->id, 4, '0', STR_PAD_LEFT));
            
            // Detección de subcategoría y categoría padre
            $catId = $p->id_categoria ?? null;
            $cat = $p->nombre_categoria ?? ($nombreCat[$catId] ?? 'Sin categoría');
            $padreId = $padresCat[$catId] ?? null;
            $padreNombre = $p->nombre_categoria_padre ?? ($padreId ? ($nombreCat[$padreId] ?? '') : '');

            $costo = (float) ($p->costo_compra ?? 0);
            $margen = ($p->precio > 0 && $costo > 0) ? round((($p->precio - $costo) / $costo) * 100) : null;
            $s = $estadoDe($p); ?>
          <tr>
            <td class="cod c-media"><?= $e($codigo) ?></td>
            <td>
              <div class="prod">
                <?php if ($p->imagen): ?><img class="foto" src="public/uploads/<?= $e($p->imagen) ?>" alt="" width="44" height="44" loading="lazy"><?php endif; ?>
                <div>
                  <b><?= $e($p->nombre_producto) ?></b>
                  <span><?= $e($p->descripcion ?? '') ?></span>

                  <?php if (!empty($p->resumen_variantes)): ?>
                    <div class="atr-chips" style="display:flex; flex-wrap:wrap; gap:4px; margin-top:4px;">
                      <?php 
                        $itemsVar = explode('|', $p->resumen_variantes);
                        foreach ($itemsVar as $iv):
                          $partes = explode(':', $iv);
                          if (count($partes) >= 5):
                            $t = trim($partes[0]);
                            $c = trim($partes[1]);
                            $m = trim($partes[2]);
                            $stk = (int)$partes[3];

                            // Construir etiquetas con el nombre de cada atributo
                            $detalles = [];
                            if ($t !== '') $detalles[] = '<b>Talla:</b> ' . $e($t);
                            if ($c !== '') $detalles[] = '<b>Color:</b> ' . $e($c);
                            if ($m !== '') $detalles[] = '<b>Modelo:</b> ' . $e($m);
                      ?>
                        <span class="chip" style="font-size:0.75rem; padding:3px 8px; background:var(--fondo-2, #f1f5f9); border-radius:6px; line-height:1.3;" title="Stock en tienda: <?= $stk ?>">
                          <?= implode(' <span style="opacity:0.4;">·</span> ', $detalles) ?>
                          <span style="margin-left:4px; color:var(--primario, #166534); font-weight:bold;">(<?= $stk ?>)</span>
                        </span>
                      <?php 
                          endif;
                        endforeach; 
                      ?>
                    </div>
                  <?php endif; ?>

                  <span class="si-estrecho"><span class="cod"><?= $e($codigo) ?></span>, <?= !empty($padreNombre) ? $e($padreNombre) . ' › ' : '' ?><?= $e($cat) ?></span>
                </div>
              </div>
            </td>
            <td class="c-media">
              <span class="chip">
                <?php if (!empty($padreNombre)): ?>
                  <span class="chip-padre"><?= $e($padreNombre) ?> ›</span>
                <?php endif; ?>
                <?= $e($cat) ?>
              </span>
            </td>
            <td class="num">
              <b><?= $usd($p->precio) ?></b>
              <?php if ($costo): ?><br><span class="margen">costo <?= $usd($costo) ?><?= $margen !== null ? ', +' . $margen . '%' : '' ?></span><?php endif; ?>
            </td>
            <td class="num">
              <div class="stk">
                <b><?= (int) $p->stock ?></b>
                <?php if ($s === 'bajo'): ?><span class="est bajo">Bajo, mín. <?= (int) ($p->stock_minimo ?? 5) ?></span>
                <?php elseif ($s === 'agot'): ?><span class="est agot">Agotado</span><?php endif; ?>
              </div>
            </td>
            <td class="num">
              <div class="stk"><b><?= (int) ($p->stock_online ?? 0) ?></b></div>
            </td>
            <td>
              <div class="acc">
                <a href="index.php?action=editar&id=<?= (int) $p->id ?>" class="ic-btn" aria-label="Editar <?= $e($p->nombre_producto) ?>"><svg class="i"><use href="#i-editar"/></svg></a>
                <a href="index.php?action=ajustes_inventario&producto=<?= (int) $p->id ?>" class="ic-btn" aria-label="Ajustar stock de <?= $e($p->nombre_producto) ?>"><svg class="i"><use href="#i-ajuste"/></svg></a>
                <a href="index.php?action=eliminar_producto&id=<?= (int) $p->id ?>" class="ic-btn" aria-label="Quitar <?= $e($p->nombre_producto) ?> del catálogo" data-confirmar="¿Quitar <?= $e($p->nombre_producto) ?> del catálogo?" data-confirmar-texto="No se borra: deja de mostrarse." data-confirmar-boton="Quitar del catálogo" data-confirmar-tipo="aviso"><svg class="i"><use href="#i-basura"/></svg></a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <ul class="tarjetas-prod" data-paginar="10">
      <?php foreach ($lista as $p):
          $codigo = $p->codigo ?? ('P-' . str_pad($p->id, 4, '0', STR_PAD_LEFT));
          $catId = $p->id_categoria ?? null;
          $cat = $p->nombre_categoria ?? ($nombreCat[$catId] ?? 'Sin categoría');
          $padreId = $padresCat[$catId] ?? null;
          $padreNombre = $p->nombre_categoria_padre ?? ($padreId ? ($nombreCat[$padreId] ?? '') : '');
          $s = $estadoDe($p); ?>
        <li>
          <?php if ($p->imagen): ?><img class="foto grande" src="public/uploads/<?= $e($p->imagen) ?>" alt="" width="56" height="56" loading="lazy"><?php else: ?><span></span><?php endif; ?>
          <div class="t-nom">
            <b><?= $e($p->nombre_producto) ?></b>
            <span><?= $e($codigo) ?>, <?= !empty($padreNombre) ? $e($padreNombre) . ' › ' : '' ?><?= $e($cat) ?></span>
          </div>
          <div class="t-precio"><?= $usd($p->precio) ?></div>
          <div class="t-stock">Tienda <b><?= (int) $p->stock ?></b>, online <b><?= (int) ($p->stock_online ?? 0) ?></b> <?php if ($s === 'bajo'): ?><span class="est bajo">Bajo</span><?php elseif ($s === 'agot'): ?><span class="est agot">Agotado</span><?php endif; ?></div>
          <div class="t-acc">
            <a href="index.php?action=editar&id=<?= (int) $p->id ?>" class="btn btn-sec btn-peq"><svg class="i"><use href="#i-editar"/></svg>Editar</a>
            <a href="index.php?action=ajustes_inventario&producto=<?= (int) $p->id ?>" class="btn btn-sec btn-peq"><svg class="i"><use href="#i-ajuste"/></svg>Ajustar stock</a>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="vacio-filtro" id="vacio-productos" <?= $lista ? 'hidden' : '' ?>>Ningún producto coincide con la búsqueda.</div>
    <footer class="tabla-pie"><span>Mostrando <?= count($lista) ?> de <?= count($listaProductos) ?> productos</span></footer>
  </section>
</section>
<?php include __DIR__ . '/admin/_cerrar.php'; ?>