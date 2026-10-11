<?php
$menu = 'inventario';
$tituloPagina = 'Editar ' . ($p->nombre_producto ?? 'producto');
$scripts = ['form_producto'];
include __DIR__ . '/admin/_abrir.php';

$e = $e ?? fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');

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
      <h2>Editar <?= $e($p->nombre_producto) ?></h2>
      <small><?= $e($p->codigo) ?>, los cambios se ven al momento en la tienda online.</small>
    </div>
  </header>

  <form class="prod-layout" method="POST" action="index.php?action=actualizar_producto" enctype="multipart/form-data" id="form-producto">
    <input type="hidden" name="id" value="<?= (int)$p->id ?>">

    <!-- COLUMNA PRINCIPAL IZQUIERDA -->
    <div class="prod-principal">
      <section class="caja" aria-labelledby="t-info">
        <header class="caja-cab"><h3 id="t-info">Información</h3></header>
        <div class="caja-cuerpo form-grid">
          <div class="campo ancho">
            <label for="fp-nombre">Nombre <span class="ob">*</span></label>
            <input type="text" name="nombre_producto" id="fp-nombre" required value="<?= $e($p->nombre_producto) ?>">
          </div>

          <div class="campo">
            <label for="fp-cod">Código</label>
            <input type="text" name="codigo" id="fp-cod" value="<?= $e($p->codigo) ?>" placeholder="Si lo dejas vacío se asigna uno solo.">
          </div>

          <div class="campo">
            <label for="fp-cat">Categoría <span class="ob">*</span></label>
            <select name="id_categoria" id="fp-cat" required style="width:100%;">
              <option value="">Selecciona categoría</option>
              <?php foreach ($principales as $padre): ?>
                <?php if (!empty($subcategoriasPorPadre[$padre->id])): ?>
                  <optgroup label="<?= $e($padre->nombre_categoria) ?>">
                    <option value="<?= (int)$padre->id ?>" <?= ((int)$p->id_categoria === (int)$padre->id) ? 'selected' : '' ?>>
                      <?= $e($padre->nombre_categoria) ?> (General)
                    </option>
                    <?php foreach ($subcategoriasPorPadre[$padre->id] as $sub): ?>
                      <option value="<?= (int)$sub->id ?>" <?= ((int)$p->id_categoria === (int)$sub->id) ? 'selected' : '' ?>>
                        ↳ <?= $e($sub->nombre_categoria) ?>
                      </option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php else: ?>
                  <option value="<?= (int)$padre->id ?>" <?= ((int)$p->id_categoria === (int)$padre->id) ? 'selected' : '' ?>>
                    <?= $e($padre->nombre_categoria) ?>
                  </option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="campo ancho">
            <label for="fp-desc">Descripción</label>
            <textarea name="descripcion" id="fp-desc" rows="3"><?= $e($p->descripcion ?? '') ?></textarea>
          </div>
        </div>
      </section>

      <!-- GESTIÓN DE VARIANTES Y STOCK (TALLA, COLOR, MODELO) -->
      <section class="caja" id="caja-variantes" style="margin-top: 1.5rem;">
        <header class="caja-cab" style="display:flex; justify-content:space-between; align-items:center;">
          <h3>Inventario por Variantes (Talla, Color, Modelo)</h3>
          <button type="button" class="btn btn-sec btn-peq" id="btn-agregar-variante">
            <svg class="i"><use href="#i-mas"/></svg> Agregar combinación
          </button>
        </header>
        <div class="caja-cuerpo">
          <p class="ayuda" style="margin-top:0; margin-bottom: 12px;">
            Define las combinaciones específicas de inventario para este producto. La suma de estos stocks actualizará automáticamente el total general.
          </p>
          <div class="tabla-scroll">
            <table class="tabla-resp" style="width:100%;">
              <thead>
                <tr>
                  <th>Talla</th>
                  <th>Color</th>
                  <th>Modelo</th>
                  <th style="width:100px;">Stk Tienda</th>
                  <th style="width:100px;">Stk Online</th>
                  <th style="width:50px;"></th>
                </tr>
              </thead>
              <tbody id="lista-variantes-body">
                <!-- Se dibuja mediante JavaScript con las opciones de la categoría -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- PRECIOS -->
      <section class="caja" aria-labelledby="t-precio" style="margin-top: 1.5rem;">
        <header class="caja-cab"><h3 id="t-precio">Precio General</h3></header>
        <div class="caja-cuerpo form-grid">
          <div class="campo">
            <label for="fp-costo">Costo de compra ($)</label>
            <input type="number" step="0.01" name="costo_compra" id="fp-costo" value="<?= (float)$p->costo_compra ?>">
            <span class="ayuda">Lo que te cuesta a ti. No lo ve el cliente.</span>
          </div>

          <div class="campo">
            <label for="fp-margen-pct">Ganancia (%)</label>
            <input type="number" step="1" id="fp-margen-pct">
            <span class="ayuda">Calcula el precio sobre el costo.</span>
          </div>

          <div class="campo">
            <label for="fp-precio">Precio de venta ($) <span class="ob">*</span></label>
            <input type="number" step="0.01" name="precio" id="fp-precio" required value="<?= (float)$p->precio ?>">
            <span class="ayuda" id="fp-precio-bs"></span>
          </div>

          <p class="fp-margen ancho" id="fp-margen"></p>
        </div>
      </section>

      <!-- STOCK CONSOLIDADO -->
      <section class="caja" aria-labelledby="t-stock" style="margin-top: 1.5rem;">
        <header class="caja-cab"><h3 id="t-stock">Stock Consolidado</h3></header>
        <div class="caja-cuerpo form-grid">
          <p class="ancho aviso-caja" style="background:#f0fdf4; padding:10px 14px; border-radius:8px; color:#166534; margin:0;">
            El stock total se calcula automáticamente sumando las combinaciones de arriba.
          </p>
          <div class="campo">
            <label>Total en tienda</label>
            <input type="text" id="total-stock-tienda" value="<?= (int)$p->stock ?>" readonly disabled style="background:var(--fondo-2, #f1f5f9);">
          </div>
          <div class="campo">
            <label>Total online</label>
            <input type="text" id="total-stock-online" value="<?= (int)$p->stock_online ?>" readonly disabled style="background:var(--fondo-2, #f1f5f9);">
          </div>
          <div class="campo">
            <label for="fp-min">Stock mínimo</label>
            <input type="number" name="stock_minimo" id="fp-min" value="<?= (int)($p->stock_minimo ?? 5) ?>">
            <span class="ayuda">Te avisamos cuando la tienda baje de aquí.</span>
          </div>
        </div>
      </section>
    </div>

    <!-- COLUMNA LATERAL DERECHA -->
    <div class="prod-lateral">
      <section class="caja" aria-labelledby="t-foto">
        <header class="caja-cab"><h3 id="t-foto">Foto</h3></header>
        <div class="caja-cuerpo">
          <div class="marco-foto <?= !empty($p->imagen) ? 'con-foto' : '' ?>" id="fp-foto">
            <?php if (!empty($p->imagen)): ?>
              <img src="public/uploads/<?= $e($p->imagen) ?>" alt="Foto del producto">
            <?php else: ?>
              <span class="muted">+ Elegir foto</span>
            <?php endif; ?>
          </div>
          <input type="file" name="imagen" id="fp-img" accept="image/jpeg,image/png,image/webp">
          <span class="ayuda">JPG o PNG. Las fotos cuadradas se ven mejor en el catálogo.</span>
        </div>
      </section>

      <section class="caja" aria-labelledby="t-vis" style="margin-top: 1.5rem;">
        <header class="caja-cab"><h3 id="t-vis">Visibilidad</h3></header>
        <div class="caja-cuerpo">
          <label class="toggle" style="display:flex; align-items:center; gap:10px; cursor:pointer;">
            <input type="checkbox" name="estado" value="1" <?= $p->estado ? 'checked' : '' ?>>
            <span>Visible en la tienda online</span>
          </label>
          <span class="ayuda" style="display:block; margin-top:8px;">Si lo apagas no se borra: solo deja de mostrarse a los clientes.</span>
          
          <button type="submit" class="btn btn-pri btn-bloque" style="margin-top:1.5rem; width:100%;">Guardar cambios</button>
          <a href="index.php?action=listado_productos" class="btn btn-sec btn-bloque" style="margin-top:0.5rem; width:100%; text-align:center;">Cancelar</a>
        </div>
      </section>
    </div>
  </form>
</section>

<!-- JSON CON LAS CATEGORÍAS, SUS ATRIBUTOS Y LAS VARIANTES EXISTENTES -->
<script type="application/json" id="datos-variantes"><?= json_encode([
    'categorias' => array_map(fn($c) => [
        'id'        => (int)$c->id,
        'nombre'    => $c->nombre_categoria,
        'atributos' => array_map(fn($a) => [
            'id'       => (int)$a->id,
            'nombre'   => strtolower(trim($a->nombre)),
            'opciones' => array_values(array_filter(array_map('trim', explode(',', $a->opciones ?? ''))))
        ], $c->atributos ?? [])
    ], $categorias),
    'variantes' => array_map(fn($v) => [
        'id'           => (int)$v->id,
        'talla'        => $v->talla ?? '',
        'color'        => $v->color ?? '',
        'modelo'       => $v->modelo ?? '',
        'stock'        => (int)$v->stock,
        'stock_online' => (int)$v->stock_online
    ], $variantesGuardadas)
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var dataElem = document.getElementById('datos-variantes');
  var D = { categorias: [], variantes: [] };
  if (dataElem) {
    try { D = JSON.parse(dataElem.textContent); } catch (e) { console.error(e); }
  }

  var catSelect = document.getElementById('fp-cat');
  var tbody = document.getElementById('lista-variantes-body');
  var btnAgregar = document.getElementById('btn-agregar-variante');
  var totalStkInput = document.getElementById('total-stock-tienda');
  var totalStkOnlineInput = document.getElementById('total-stock-online');

  function obtenerAtributosCat() {
    var c = D.categorias.find(function(x) { return String(x.id) === String(catSelect.value); });
    return c ? (c.atributos || []) : [];
  }

  function generarControl(nombreAtr, valorActual, index) {
    var atrs = obtenerAtributosCat();
    var atr = atrs.find(function(a) { return a.nombre === nombreAtr.toLowerCase(); });
    var inputName = 'variantes[' + index + '][' + nombreAtr + ']';

    if (atr && atr.opciones.length > 0) {
      var html = '<select name="' + inputName + '" style="width:100%;">';
      html += '<option value="">Sin especificar</option>';
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

  btnAgregar.addEventListener('click', function() {
    agregarFila(null);
  });

  // Cargar variantes existentes o agregar una fila vacía inicial
  if (D.variantes && D.variantes.length > 0) {
    D.variantes.forEach(agregarFila);
  } else {
    agregarFila(null);
  }

  recalcularTotales();
});
</script>

<?php include __DIR__ . '/admin/_cerrar.php'; ?>