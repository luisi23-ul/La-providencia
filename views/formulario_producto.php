<?php
$menu = 'listado_productos';
$editando = isset($p) && $p;
$tituloPagina = $editando ? 'Editar producto' : 'Nuevo producto';
$scripts = ['inventario'];
$categorias = $categorias ?? [];
include __DIR__ . '/admin/_abrir.php';


$codigo = $editando ? ($p->codigo ?? ('P-' . str_pad($p->id, 4, '0', STR_PAD_LEFT))) : '';
$campo = fn($crear, $editar) => $editando ? $editar : $crear;
$visible = (int) ($p->estado ?? 1) === 1;
$variantesGuardadas = $editando ? ($p->variantes ?? []) : [];
$principales = array_filter($categorias, fn($c) => empty($c->id_padre));
$hijas = [];
foreach ($categorias as $c) if (!empty($c->id_padre)) $hijas[$c->id_padre][] = $c;
$nombreCategoria = '';
foreach ($categorias as $c) if ($editando && (string) $c->id === (string) ($p->id_categoria ?? '')) $nombreCategoria = $c->nombre_categoria;
$opcionCat = fn($c, $prefijo = '') => '<option value="' . (int) $c->id . '"' . ((string) ($p->id_categoria ?? '') === (string) $c->id ? ' selected' : '') . '>' . $prefijo . htmlspecialchars($c->nombre_categoria, ENT_QUOTES, 'UTF-8') . '</option>';
?>
<section class="contenido">


  <header class="cab">
    <div class="cab-txt"><small>Inventario</small>
      <div class="titulo-atras"><a href="index.php?action=listado_productos" class="atras" aria-label="Volver a Productos"><svg class="i"><use href="#i-volver"/></svg></a><h2><?= $editando ? 'Editar ' . $e($p->nombre_producto) : 'Nuevo producto' ?></h2></div>
      <?php if ($editando): ?>
      <div class="fpx-chips">
        <span class="dv-chip neutro"><svg class="i"><use href="#i-etiqueta"/></svg><?= $e($codigo) ?></span>
        <?php if ($nombreCategoria): ?><span class="dv-chip neutro"><?= $e($nombreCategoria) ?></span><?php endif; ?>
        <span class="dv-chip<?= $visible ? '' : ' pend' ?>" id="fp-chip-vis"><?= $visible ? 'Visible en la tienda' : 'Oculto en la tienda' ?></span>
      </div>
      <?php endif; ?>
    </div>
  </header>

  <form class="fpx" id="form-producto" action="index.php?action=<?= $editando ? 'actualizar_producto' : 'guardar_producto' ?>" method="POST" enctype="multipart/form-data">
    <?php if ($editando): ?><input type="hidden" name="id" value="<?= (int) $p->id ?>"><?php endif; ?>
    <div class="fpx-col">
      <section class="fpx-sec" aria-labelledby="fp-t-info">
        <header><span class="fpx-ic"><svg class="i"><use href="#i-caja"/></svg></span><div><h3 id="fp-t-info">Información</h3><p>Lo que ve el cliente en la tienda online.</p></div></header>
        <div class="fpx-cuerpo">
          <div class="campo ancho"><label for="fp-nombre">Nombre <span class="ob">*</span></label><input id="fp-nombre" name="<?= $campo('nombre_producto', 'nombre') ?>" value="<?= $e($p->nombre_producto ?? '') ?>" required placeholder="Ej.: Blusa de lino manga corta"></div>
          <div class="campo"><label for="fp-codigo">Código</label><input id="fp-codigo" name="codigo" value="<?= $e($codigo) ?>" autocomplete="off" placeholder="P-0013"><span class="ayuda">Si lo dejas vacío se asigna uno solo.</span></div>
          <div class="campo"><label for="fp-cat">Categoría <span class="ob">*</span></label>
            <select id="fp-cat" name="id_categoria" required>
              <option value="">Elige una categoría</option>
              <?php foreach ($principales as $padre): ?>
                <?= $opcionCat($padre) ?>
                <?php foreach ($hijas[$padre->id] ?? [] as $sub) echo $opcionCat($sub, '↳ '); ?>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="campo ancho"><label for="fp-desc">Descripción</label><textarea id="fp-desc" name="<?= $campo('descripcion', 'desc') ?>" rows="3" placeholder="Tela, corte, medidas. Es lo que lee el cliente en la tienda online."><?= $e($p->descripcion ?? '') ?></textarea></div>
        </div>
      </section>

      <section class="fpx-sec" aria-labelledby="fp-t-precio">
        <header><span class="fpx-ic"><svg class="i"><use href="#i-dolar"/></svg></span><div><h3 id="fp-t-precio">Precio y ganancia</h3><p>El costo no lo ve el cliente.</p></div></header>
        <div class="fpx-cuerpo fpx-precios">
          <div class="campo"><label for="fp-costo">Costo de compra</label><div class="fpx-pre"><span aria-hidden="true">$</span><input id="fp-costo" name="costo_compra" type="number" min="0" step="0.01" inputmode="decimal" value="<?= $e($p->costo_compra ?? '') ?>" placeholder="0.00"></div></div>
          <div class="campo"><label for="fp-margen-pct">Ganancia sobre el costo</label><div class="fpx-pre pct"><span aria-hidden="true">%</span><input id="fp-margen-pct" type="number" min="0" step="0.1" inputmode="decimal" placeholder="30"></div><span class="ayuda">Escríbela y calculo el precio.</span></div>
          <div class="campo"><label for="fp-precio">Precio de venta <span class="ob">*</span></label><div class="fpx-pre"><span aria-hidden="true">$</span><input id="fp-precio" name="precio" type="number" min="0" step="0.01" inputmode="decimal" required value="<?= $e($p->precio ?? '') ?>" placeholder="0.00"></div><span class="ayuda" id="fp-precio-bs"></span></div>
          <dl class="fpx-gana" id="fp-gana" aria-live="polite" hidden>
            <div><dt id="fp-g-tit">Ganas por unidad</dt><dd id="fp-g-usd"></dd></div>
            <div><dt>Sobre el costo</dt><dd id="fp-g-costo"></dd><small>lo que le sumas al costo</small></div>
            <div><dt>Del precio de venta</dt><dd id="fp-g-precio"></dd><small>lo que te queda de cada venta</small></div>
          </dl>
        </div>
      </section>

      <section class="fpx-sec" aria-labelledby="fp-t-var" id="fp-variantes" hidden>
        <header><span class="fpx-ic"><svg class="i"><use href="#i-etiqueta"/></svg></span><div><h3 id="fp-t-var">Tallas, colores y stock</h3><p>Una fila por cada combinación que tengas. El stock total es la suma.</p></div></header>
        <div class="fpx-var">
          <div class="fpx-var-cab" aria-hidden="true"><span>Talla</span><span>Color</span><span>Modelo</span><span>Tienda</span><span>Online</span><span></span></div>
          <ul class="fpx-var-lista" id="fp-var-lista"></ul>
          <button type="button" class="fpx-var-mas" id="fp-var-mas"><svg class="i"><use href="#i-mas"/></svg>Agregar combinación</button>
        </div>
      </section>

      <section class="fpx-sec" aria-labelledby="fp-t-stock">
        <header><span class="fpx-ic"><svg class="i"><use href="#i-caja"/></svg></span><div><h3 id="fp-t-stock">Stock</h3><p id="fp-stock-nota"><?= $editando ? 'Se cambia en Ajustes de stock para que quede en Movimientos.' : 'Con cuántas unidades empieza.' ?></p></div></header>
        <div class="fpx-cuerpo fpx-stock">
          <div class="fpx-st"><span class="ic"><svg class="i"><use href="#i-tienda"/></svg></span><label for="fp-st">Unidades</label><input id="fp-st" name="stock" type="number" min="0" inputmode="numeric" value="<?= (int) ($p->stock ?? 0) ?>"<?= $editando ? ' readonly' : '' ?>></div>
          <div class="fpx-st min"><span class="ic"><svg class="i"><use href="#i-alerta"/></svg></span><label for="fp-sm">Avisar cuando baje de</label><input id="fp-sm" name="stock_minimo" type="number" min="0" inputmode="numeric" value="<?= (int) ($p->stock_minimo ?? 5) ?>"></div>
          <?php if ($editando): ?>
          <div class="fpx-ajuste" id="fp-ajuste"><span>¿Contaste y no cuadra? Corrígelo con un ajuste.</span><a href="index.php?action=ajustes_inventario&amp;producto=<?= (int) $p->id ?>" class="btn btn-sec"><svg class="i"><use href="#i-ajuste"/></svg>Ajustar stock</a></div>
          <?php endif; ?>
        </div>
      </section>
    </div>

    <aside class="fpx-col">
      <section class="fpx-sec" aria-labelledby="fp-t-foto">
        <header><span class="fpx-ic"><svg class="i"><use href="#i-archivo"/></svg></span><div><h3 id="fp-t-foto">Foto</h3><p>Vertical se ve mejor, como el teléfono parado.</p></div></header>
        <input id="fp-img" name="imagen" type="file" accept="image/jpeg,image/png,image/webp" class="fp-file"<?= $editando ? '' : ' required' ?>>
        <label class="fpx-foto<?= !empty($p->imagen) ? ' con-foto' : '' ?>" for="fp-img" id="fp-foto"><?php if (!empty($p->imagen)): ?><img src="public/uploads/<?= $e($p->imagen) ?>" alt="Foto del producto"><?php else: ?><span class="fpx-foto-vacia"><svg class="i"><use href="#i-mas"/></svg>Elegir foto</span><?php endif; ?></label>
        <div class="fpx-foto-pie"><span>JPG, PNG o WEBP</span><label for="fp-img" class="btn btn-sec" id="fp-foto-btn"><?= !empty($p->imagen) ? 'Cambiar foto' : 'Elegir foto' ?></label></div>
      </section>

      <section class="fpx-sec" aria-labelledby="fp-t-vis">
        <header><span class="fpx-ic"><svg class="i"><use href="#i-globo"/></svg></span><div><h3 id="fp-t-vis">Visibilidad</h3><p>Ocultarlo no lo borra.</p></div></header>
        <input type="hidden" name="estado" value="0">
        <label class="fpx-vis<?= $visible ? '' : ' oculto' ?>" id="fp-vis">
          <input type="checkbox" name="estado" value="1"<?= $visible ? ' checked' : '' ?>>
          <span><b id="fp-vis-t"><?= $visible ? 'Visible en la tienda' : 'Oculto en la tienda' ?></b><small id="fp-vis-s"><?= $visible ? 'Los clientes lo ven y lo pueden comprar.' : 'Los clientes no lo ven.' ?></small></span>
        </label>
      </section>

      <div class="fpx-guardar">
        <button type="submit" class="btn btn-pri"><svg class="i"><use href="#i-check"/></svg><?= $editando ? 'Guardar cambios' : 'Guardar producto' ?></button>
        <a href="index.php?action=listado_productos" class="btn btn-sec">Cancelar</a>
        <small><?= $editando ? 'Los cambios se ven al momento en la tienda online.' : 'Al guardarlo aparece en el catálogo de la tienda online.' ?></small>
      </div>
    </aside>
  </form>
  <div class="fpx-barra" id="fp-barra" role="region" aria-label="Cambios sin guardar" hidden>
    <b>Tienes cambios sin guardar</b>
    <button type="button" class="btn btn-sec" id="fp-descartar">Descartar</button>
    <button type="submit" form="form-producto" class="btn btn-pri"><svg class="i"><use href="#i-check"/></svg><?= $editando ? 'Guardar cambios' : 'Guardar producto' ?></button>
  </div>
</section>
<script type="application/json" id="datos-atributos"><?= json_encode([
    'categorias' => array_map(fn($c) => ['id' => (int) $c->id, 'atributos' => array_map(fn($a) => ['nombre' => mb_strtolower(trim($a->nombre)), 'opciones' => array_values(array_filter(array_map('trim', explode(',', (string) ($a->opciones ?? '')))))], $c->atributos ?? [])], $categorias),
    'variantes' => array_map(fn($v) => ['id' => (int) $v->id, 'talla' => $v->talla ?? '', 'color' => $v->color ?? '', 'modelo' => $v->modelo ?? '', 'stock' => (int) $v->stock, 'stock_online' => (int) $v->stock_online], $variantesGuardadas),
    'editando' => (bool) $editando,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php include __DIR__ . '/admin/_cerrar.php'; ?>
