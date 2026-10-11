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

// Jerarquía de categorías principales y subcategorías
$principales = array_filter($categorias, fn($c) => empty($c->id_padre));
$subcategoriasPorPadre = [];
foreach ($categorias as $c) {
    if (!empty($c->id_padre)) {
        $subcategoriasPorPadre[$c->id_padre][] = $c;
    }
}
$variantesGuardadas = $p->variantes ?? [];
?>
<section class="contenido">
  <a href="index.php?action=listado_productos" class="volver"><svg class="i"><use href="#i-volver"/></svg>Productos</a>
  <header class="cab">
    <div class="cab-txt">
      <h2><?= $editando ? 'Editar ' . $e($p->nombre_producto) : 'Nuevo producto' ?></h2>
      <small><?= $editando ? $e($codigo) . ', los cambios se ven al momento en la tienda online.' : 'Al guardarlo aparece en el catálogo de la tienda online.' ?></small>
    </div>
  </header>

  <form class="compra-layout" id="form-producto" action="index.php?action=<?= $editando ? 'actualizar_producto' : 'guardar_producto' ?>" method="POST" enctype="multipart/form-data">
    <?php if ($editando): ?><input type="hidden" name="id" value="<?= (int) $p->id ?>"><?php endif; ?>

    <div class="compra-col">
      <!-- INFORMACIÓN GENERAL -->
      <section class="caja" aria-labelledby="fp-t-info">
        <header class="caja-cab"><h3 id="fp-t-info">Información</h3></header>
        <div class="caja-cuerpo form-grid">
          <div class="campo ancho">
            <label for="fp-nombre">Nombre <span class="ob">*</span></label>
            <input id="fp-nombre" name="<?= $campo('nombre_producto', 'nombre') ?>" value="<?= $e($p->nombre_producto ?? '') ?>" required placeholder="Ej.: Cesta de ropa con tapa">
          </div>

          <div class="campo">
            <label for="fp-codigo">Código</label>
            <input id="fp-codigo" name="codigo" value="<?= $e($codigo) ?>" autocomplete="off" placeholder="P-0013">
            <span class="ayuda">Si lo dejas vacío se asigna uno solo.</span>
          </div>

          <div class="campo">
            <label for="fp-cat">Categoría <span class="ob">*</span></label>
            <select id="fp-cat" name="id_categoria" required>
              <option value="">Elige una categoría</option>
              <?php foreach ($principales as $padre): ?>
                <?php if (!empty($subcategoriasPorPadre[$padre->id])): ?>
                  <optgroup label="<?= $e($padre->nombre_categoria) ?>">
                    <option value="<?= (int) $padre->id ?>" <?= ($p->id_categoria ?? '') == $padre->id ? 'selected' : '' ?>>
                      <?= $e($padre->nombre_categoria) ?> (General)
                    </option>
                    <?php foreach ($subcategoriasPorPadre[$padre->id] as $sub): ?>
                      <option value="<?= (int) $sub->id ?>" <?= ($p->id_categoria ?? '') == $sub->id ? 'selected' : '' ?>>
                        ↳ <?= $e($sub->nombre_categoria) ?>
                      </option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php else: ?>
                  <option value="<?= (int) $padre->id ?>" <?= ($p->id_categoria ?? '') == $padre->id ? 'selected' : '' ?>>
                    <?= $e($padre->nombre_categoria) ?>
                  </option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="campo ancho">
            <label for="fp-desc">Descripción</label>
            <textarea id="fp-desc" name="<?= $campo('descripcion', 'desc') ?>" rows="3" placeholder="Medidas, color, material. Es lo que lee el cliente en la tienda online."><?= $e($p->descripcion ?? '') ?></textarea>
          </div>
        </div>
      </section>

      <!-- SECCIÓN INTERACTIVA DE VARIANTES Y STOCK -->
      <section class="caja" id="caja-variantes" hidden>
        <header class="caja-cab" style="display:flex; justify-content:space-between; align-items:center;">
          <h3 id="fp-t-atr">Inventario por Variantes (Talla, Color, Modelo)</h3>
          <button type="button" class="btn btn-sec btn-peq" id="btn-agregar-variante">
            <svg class="i"><use href="#i-mas"/></svg> Agregar combinación
          </button>
        </header>
        <div class="caja-cuerpo">
          <p class="ayuda" style="margin-top:0; margin-bottom: 12px;">
            Agrega las combinaciones que tengas en inventario. El stock total se calculará sumando cada fila.
          </p>
          <div class="tabla-scroll">
            <table class="tabla-resp" style="width:100%;">
              <thead>
                <tr>
                  <th>Talla</th>
                  <th>Color</th>
                  <th>Modelo</th>
                  <th style="width:110px;">Stk Tienda</th>
                  <th style="width:110px;">Stk Online</th>
                  <th style="width:40px;"></th>
                </tr>
              </thead>
              <tbody id="lista-variantes-body">
                <!-- Se dibuja dinámicamente según la categoría seleccionada -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- PRECIO -->
      <section class="caja" aria-labelledby="fp-t-precio">
        <header class="caja-cab"><h3 id="fp-t-precio">Precio</h3></header>
        <div class="caja-cuerpo form-grid fp-3">
          <div class="campo">
            <label for="fp-costo">Costo de compra ($)</label>
            <input id="fp-costo" name="costo_compra" type="number" min="0" step="0.01" inputmode="decimal" value="<?= $e($p->costo_compra ?? '') ?>" placeholder="0.00">
            <span class="ayuda">Lo que te cuesta a ti. No lo ve el cliente.</span>
          </div>

          <div class="campo">
            <label for="fp-margen-pct">Ganancia (%)</label>
            <input id="fp-margen-pct" type="number" min="0" step="1" inputmode="numeric" placeholder="30">
            <span class="ayuda">Calcula el precio sobre el costo.</span>
          </div>

          <div class="campo">
            <label for="fp-precio">Precio de venta ($) <span class="ob">*</span></label>
            <input id="fp-precio" name="precio" type="number" min="0" step="0.01" inputmode="decimal" required value="<?= $e($p->precio ?? '') ?>" placeholder="0.00">
            <span class="ayuda" id="fp-precio-bs"></span>
          </div>
          <p class="fp-margen ancho" id="fp-margen" aria-live="polite"></p>
        </div>
      </section>

      <!-- STOCK CONSOLIDADO -->
      <section class="caja" aria-labelledby="fp-t-stock">
        <header class="caja-cab"><h3 id="fp-t-stock">Stock</h3></header>
        <div class="caja-cuerpo">
          <?php if ($editando): ?>
            <p class="fp-nota-stock">Para cambiar el stock usa <a href="index.php?action=ajustes_inventario&producto=<?= (int) $p->id ?>" class="enlace">Ajustes de stock</a>: así queda registrado en Movimientos quién lo cambió y por qué.</p>
          <?php endif; ?>
          <div class="form-grid fp-3">
            <div class="campo">
              <label for="fp-st">Stock en tienda</label>
              <input id="fp-st" name="stock" type="number" min="0" inputmode="numeric" value="<?= (int) ($p->stock ?? 0) ?>" <?= $editando ? 'readonly' : '' ?>>
            </div>

            <div class="campo">
              <label for="fp-so">Stock online</label>
              <input id="fp-so" name="stock_online" type="number" min="0" inputmode="numeric" value="<?= (int) ($p->stock_online ?? 0) ?>" <?= $editando ? 'readonly' : '' ?>>
            </div>

            <div class="campo">
              <label for="fp-sm">Stock mínimo</label>
              <input id="fp-sm" name="stock_minimo" type="number" min="0" inputmode="numeric" value="<?= (int) ($p->stock_minimo ?? 5) ?>">
              <span class="ayuda">Te avisamos cuando la tienda baje de aquí.</span>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- COLUMNA LATERAL DERECHA -->
    <aside class="compra-col">
      <section class="caja" aria-labelledby="fp-t-foto">
        <header class="caja-cab"><h3 id="fp-t-foto">Foto</h3></header>
        <div class="caja-cuerpo">
          <label class="fp-foto<?= !empty($p->imagen) ? ' con-foto' : '' ?>" for="fp-img" id="fp-foto">
            <?php if (!empty($p->imagen)): ?>
              <img src="public/uploads/<?= $e($p->imagen) ?>" alt="Foto del producto">
            <?php else: ?>
              <span class="fp-foto-vacia"><svg class="i"><use href="#i-mas"/></svg>Elegir foto</span>
            <?php endif; ?>
          </label>
          <input id="fp-img" name="imagen" type="file" accept="image/*" class="fp-file">
          <p class="ayuda" style="margin-top:8px">JPG o PNG. Las fotos cuadradas se ven mejor en el catálogo.</p>
        </div>
      </section>

      <section class="caja" aria-labelledby="fp-t-estado">
        <header class="caja-cab"><h3 id="fp-t-estado">Visibilidad</h3></header>
        <div class="caja-cuerpo">
          <label class="interruptor">
            <input type="checkbox" name="estado" value="1" <?= ($p->estado ?? 1) ? 'checked' : '' ?>>
            <span>Visible en la tienda online</span>
          </label>
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
    'categorias' => array_map(fn($c) => [
        'id'        => (int) $c->id,
        'nombre'    => $c->nombre_categoria,
        'atributos' => array_map(fn($a) => [
            'id'       => (int) $a->id,
            'nombre'   => strtolower(trim($a->nombre)),
            'opciones' => array_values(array_filter(array_map('trim', explode(',', $a->opciones ?? ''))))
        ], $c->atributos ?? [])
    ], $categorias),
    'variantes' => array_map(fn($v) => [
        'id'           => (int) $v->id,
        'talla'        => $v->talla ?? '',
        'color'        => $v->color ?? '',
        'modelo'       => $v->modelo ?? '',
        'stock'        => (int) $v->stock,
        'stock_online' => (int) $v->stock_online
    ], $variantesGuardadas),
    'valores'   => $valores
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var dataElem = document.getElementById('datos-atributos');
  var D = { categorias: [], variantes: [] };
  if (dataElem) {
    try { D = JSON.parse(dataElem.textContent); } catch (e) { console.error(e); }
  }

  var catSelect = document.getElementById('fp-cat');
  var cajaVariantes = document.getElementById('caja-variantes');
  var tbody = document.getElementById('lista-variantes-body');
  var btnAgregar = document.getElementById('btn-agregar-variante');
  var totalStkInput = document.getElementById('fp-st');
  var totalStkOnlineInput = document.getElementById('fp-so');

  function obtenerAtributosCat() {
    if (!catSelect || !catSelect.value) return [];
    var c = D.categorias.find(function(x) { return String(x.id) === String(catSelect.value); });
    return c ? (c.atributos || []) : [];
  }

  function generarControl(nombreAtr, valorActual, index) {
    var atrs = obtenerAtributosCat();
    var atr = atrs.find(function(a) { return a.nombre.toLowerCase() === nombreAtr.toLowerCase(); });
    var inputName = 'variantes[' + index + '][' + nombreAtr + ']';

    if (atr && atr.opciones && atr.opciones.length > 0) {
      var html = '<select name="' + inputName + '" style="width:100%;">';
      html += '<option value="">Selecciona ' + nombreAtr + '</option>';
      atr.opciones.forEach(function(op) {
        var sel = (String(op).toLowerCase() === String(valorActual || '').toLowerCase()) ? ' selected' : '';
        html += '<option value="' + op + '"' + sel + '>' + op + '</option>';
      });
      html += '</select>';
      return html;
    }
    return '<input type="text" name="' + inputName + '" value="' + (valorActual || '') + '" placeholder="Ej: ' + nombreAtr + '" style="width:100%;">';
  }

  function recalcularTotales() {
    if (!cajaVariantes || cajaVariantes.hidden) return;
    var sumTienda = 0;
    var sumOnline = 0;
    tbody.querySelectorAll('tr').forEach(function(tr) {
      var inStk = tr.querySelector('.var-stock');
      var inOn = tr.querySelector('.var-online');
      if (inStk) sumTienda += parseInt(inStk.value) || 0;
      if (inOn) sumOnline += parseInt(inOn.value) || 0;
    });
    if (totalStkInput) totalStkInput.value = sumTienda;
    if (totalStkOnlineInput) totalStkOnlineInput.value = sumOnline;
  }

  function agregarFila(v) {
    var idx = tbody.children.length;
    var varId = v ? (v.id || 0) : 0;
    var tr = document.createElement('tr');

    tr.innerHTML = 
      '<input type="hidden" name="variantes[' + idx + '][id]" value="' + varId + '">' +
      '<td>' + generarControl('talla', v ? v.talla : '', idx) + '</td>' +
      '<td>' + generarControl('color', v ? v.color : '', idx) + '</td>' +
      '<td>' + generarControl('modelo', v ? v.modelo : '', idx) + '</td>' +
      '<td><input type="number" name="variantes[' + idx + '][stock]" class="var-stock" value="' + (v ? v.stock : 0) + '" min="0" style="width:100%;"></td>' +
      '<td><input type="number" name="variantes[' + idx + '][stock_online]" class="var-online" value="' + (v ? v.stock_online : 0) + '" min="0" style="width:100%;"></td>' +
      '<td><button type="button" class="ic-btn btn-quitar-fila" aria-label="Eliminar"><svg class="i"><use href="#i-basura"/></svg></button></td>';

    tr.querySelector('.var-stock').addEventListener('input', recalcularTotales);
    tr.querySelector('.var-online').addEventListener('input', recalcularTotales);
    tr.querySelector('.btn-quitar-fila').addEventListener('click', function() {
      tr.remove();
      recalcularTotales();
    });

    tbody.appendChild(tr);
  }

  function actualizarVistaVariantes() {
    var atrs = obtenerAtributosCat();
    if (atrs.length > 0) {
      cajaVariantes.hidden = false;
      if (tbody.children.length === 0) {
        agregarFila(null);
      }
      if (totalStkInput) totalStkInput.readOnly = true;
      if (totalStkOnlineInput) totalStkOnlineInput.readOnly = true;
    } else {
      cajaVariantes.hidden = true;
      tbody.innerHTML = '';
      if (totalStkInput) totalStkInput.readOnly = false;
      if (totalStkOnlineInput) totalStkOnlineInput.readOnly = false;
    }
    recalcularTotales();
  }

  if (btnAgregar) {
    btnAgregar.addEventListener('click', function() {
      agregarFila(null);
    });
  }

  if (catSelect) {
    catSelect.addEventListener('change', function() {
      tbody.innerHTML = '';
      actualizarVistaVariantes();
    });
  }

  // Si ya tiene categoría seleccionada al cargar la vista
  if (catSelect && catSelect.value) {
    actualizarVistaVariantes();
  }
});
</script>