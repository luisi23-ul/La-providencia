<?php
$menu = 'listado_productos';
$editando = isset($p) && $p;
$tituloPagina = $editando ? 'Editar producto' : 'Nuevo producto';
$scripts = ['inventario'];
$categorias = $categorias ?? array_map(fn($id, $n) => (object) ['id' => $id, 'nombre_categoria' => $n], [1, 2, 3], ['Aluminio', 'Lencería', 'Plástico']);
include __DIR__ . '/admin/_abrir.php';
$valores = $editando ? (array) ($p->atributos ?? []) : [];
$codigo = $editando ? ($p->codigo ?? ('P-' . str_pad($p->id, 4, '0', STR_PAD_LEFT))) : '';
$campo = fn($crear, $editar) => $editando ? $editar : $crear;
?>
<section class="contenido">
  <a href="index.php?action=listado_productos" class="volver"><svg class="i"><use href="#i-volver"/></svg>Productos</a>
  <header class="cab">
    <div class="cab-txt"><h2><?= $editando ? 'Editar ' . $e($p->nombre_producto) : 'Nuevo producto' ?></h2><small><?= $editando ? $e($codigo) . ', los cambios se ven al momento en la tienda online.' : 'Al guardarlo aparece en el catálogo de la tienda online.' ?></small></div>
  </header>
  <form class="compra-layout" id="form-producto" action="index.php?action=<?= $editando ? 'actualizar_producto' : 'guardar_producto' ?>" method="POST" enctype="multipart/form-data">
    <?php if ($editando): ?><input type="hidden" name="id" value="<?= (int) $p->id ?>"><?php endif; ?>
    <div class="compra-col">
      <section class="caja" aria-labelledby="fp-t-info">
        <header class="caja-cab"><h3 id="fp-t-info">Información</h3></header>
        <div class="caja-cuerpo form-grid">
          <div class="campo ancho"><label for="fp-nombre">Nombre <span class="ob">*</span></label><input id="fp-nombre" name="<?= $campo('nombre_producto', 'nombre') ?>" value="<?= $e($p->nombre_producto ?? '') ?>" required placeholder="Ej.: Cesta de ropa con tapa"></div>
          <div class="campo"><label for="fp-codigo">Código</label><input id="fp-codigo" name="codigo" value="<?= $e($codigo) ?>" autocomplete="off" placeholder="P-0013"><span class="ayuda">Si lo dejas vacío se asigna uno solo.</span></div>
          <div class="campo"><label for="fp-cat">Categoría <span class="ob">*</span></label>
            <select id="fp-cat" name="id_categoria" required>
              <option value="">Elige una categoría</option>
              <?php foreach ($categorias as $c): ?><option value="<?= (int) $c->id ?>" <?= ($p->id_categoria ?? '') == $c->id ? 'selected' : '' ?>><?= $e($c->nombre_categoria) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="campo ancho"><label for="fp-desc">Descripción</label><textarea id="fp-desc" name="<?= $campo('descripcion', 'desc') ?>" rows="3" placeholder="Medidas, color, material. Es lo que lee el cliente en la tienda online."><?= $e($p->descripcion ?? '') ?></textarea></div>
        </div>
      </section>

      <section class="caja" aria-labelledby="fp-t-atr" id="fp-atributos-caja" hidden>
        <header class="caja-cab"><h3 id="fp-t-atr">Atributos de la categoría</h3><a href="index.php?action=categorias" class="enlace" style="font-size:13px">Editar atributos</a></header>
        <div class="caja-cuerpo form-grid" id="fp-atributos"></div>
      </section>

      <section class="caja" aria-labelledby="fp-t-precio">
        <header class="caja-cab"><h3 id="fp-t-precio">Precio</h3></header>
        <div class="caja-cuerpo form-grid fp-3">
          <div class="campo"><label for="fp-costo">Costo de compra ($)</label><input id="fp-costo" name="costo_compra" type="number" min="0" step="0.01" inputmode="decimal" value="<?= $e($p->costo_compra ?? '') ?>" placeholder="0.00"><span class="ayuda">Lo que te cuesta a ti. No lo ve el cliente.</span></div>
          <div class="campo"><label for="fp-margen-pct">Ganancia (%)</label><input id="fp-margen-pct" type="number" min="0" step="1" inputmode="numeric" placeholder="30"><span class="ayuda">Calcula el precio sobre el costo.</span></div>
          <div class="campo"><label for="fp-precio">Precio de venta ($) <span class="ob">*</span></label><input id="fp-precio" name="precio" type="number" min="0" step="0.01" inputmode="decimal" required value="<?= $e($p->precio ?? '') ?>" placeholder="0.00"><span class="ayuda" id="fp-precio-bs"></span></div>
          <p class="fp-margen ancho" id="fp-margen" aria-live="polite"></p>
        </div>
      </section>

      <section class="caja" aria-labelledby="fp-t-stock">
        <header class="caja-cab"><h3 id="fp-t-stock">Stock</h3></header>
        <div class="caja-cuerpo">
          <?php if ($editando): ?><p class="fp-nota-stock">Para cambiar el stock usa <a href="index.php?action=ajustes_inventario&producto=<?= (int) $p->id ?>" class="enlace">Ajustes de stock</a>: así queda registrado en Movimientos quién lo cambió y por qué.</p><?php endif; ?>
          <div class="form-grid fp-3">
            <div class="campo"><label for="fp-st">Stock en tienda</label><input id="fp-st" name="stock" type="number" min="0" inputmode="numeric" value="<?= (int) ($p->stock ?? 0) ?>" <?= $editando ? 'readonly' : '' ?>></div>
            <div class="campo"><label for="fp-so">Stock online</label><input id="fp-so" name="stock_online" type="number" min="0" inputmode="numeric" value="<?= (int) ($p->stock_online ?? 0) ?>" <?= $editando ? 'readonly' : '' ?>></div>
            <div class="campo"><label for="fp-sm">Stock mínimo</label><input id="fp-sm" name="stock_minimo" type="number" min="0" inputmode="numeric" value="<?= (int) ($p->stock_minimo ?? 5) ?>"><span class="ayuda">Te avisamos cuando la tienda baje de aquí.</span></div>
          </div>
        </div>
      </section>
    </div>

    <aside class="compra-col">
      <section class="caja" aria-labelledby="fp-t-foto">
        <header class="caja-cab"><h3 id="fp-t-foto">Foto</h3></header>
        <div class="caja-cuerpo">
          <label class="fp-foto<?= !empty($p->imagen) ? ' con-foto' : '' ?>" for="fp-img" id="fp-foto"><?php if (!empty($p->imagen)): ?><img src="public/uploads/<?= $e($p->imagen) ?>" alt="Foto del producto"><?php else: ?><span class="fp-foto-vacia"><svg class="i"><use href="#i-mas"/></svg>Elegir foto</span><?php endif; ?></label>
          <input id="fp-img" name="imagen" type="file" accept="image/*" class="fp-file" <?= $editando ? '' : 'required' ?>>
          <p class="ayuda" style="margin-top:8px">JPG o PNG. Las fotos cuadradas se ven mejor en el catálogo.</p>
        </div>
      </section>
      <section class="caja" aria-labelledby="fp-t-estado">
        <header class="caja-cab"><h3 id="fp-t-estado">Visibilidad</h3></header>
        <div class="caja-cuerpo">
          <label class="interruptor"><input type="checkbox" name="estado" value="1" <?= ($p->estado ?? 1) ? 'checked' : '' ?>><span>Visible en la tienda online</span></label>
          <p class="ayuda" style="margin-top:8px">Si lo apagas no se borra: solo deja de mostrarse a los clientes.</p>
        </div>
      </section>
      <div class="acciones-col">
        <button type="submit" class="btn btn-pri btn-bloque"><?= $editando ? 'Guardar cambios' : 'Guardar producto' ?></button>
        <a href="index.php?action=listado_productos" class="btn btn-sec btn-bloque">Cancelar</a>
      </div>
    </aside>
  </form>
</section>
<script type="application/json" id="datos-atributos"><?= json_encode([
    'categorias' => array_map(fn($c) => ['id' => (int) $c->id, 'atributos' => array_map(fn($a) => ['id' => (int) $a->id, 'nombre' => $a->nombre, 'opciones' => array_values(array_filter(array_map('trim', explode(',', $a->opciones ?? ''))))], $c->atributos ?? [])], $categorias),
    'valores' => $valores,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php include __DIR__ . '/admin/_cerrar.php'; ?>
