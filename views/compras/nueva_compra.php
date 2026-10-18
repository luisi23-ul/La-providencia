<?php
require_once __DIR__ . '/../admin/_claves.php';
$menu = 'compras';
$tituloPagina = 'Registrar compra';
$scripts = ['nueva_compra'];
$proveedores = $proveedores ?? [];
$productos = $productos ?? $listaProductos ?? [];
$metodos = $metodos ?? [];
$empleados = sin_relleno($empleados ?? [], 'usuario');
$categorias = sin_relleno($categorias ?? [], 'categoria');
$catPrincipales = array_filter($categorias, fn($c) => empty(ref('categoria.padre', $c)));
$catHijas = [];
foreach ($categorias as $c) if (!empty(ref('categoria.padre', $c))) $catHijas[ref('categoria.padre', $c)][] = $c;
$bancosLista = ['Banesco', 'Banco de Venezuela', 'Mercantil', 'Banco Provincial', 'BNC Nacional de Crédito', 'Bancaribe', 'Banco Exterior', 'Banco Plaza', 'Banco del Tesoro', 'Banco Bicentenario', 'Banplus', '100% Banco', 'Caja chica', 'Otro'];
include __DIR__ . '/../admin/_abrir.php';
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Compras</small>
      <div class="titulo-atras"><a href="index.php?action=compras" class="atras" aria-label="Volver a Compras"><svg class="i"><use href="#i-volver"/></svg></a><h2>Registrar compra</h2></div>
    </div>
  </header>

  <form class="fpx" id="form-compra" action="index.php?action=guardar_compra" method="POST">
    <input type="hidden" name="destino_stock" value="tienda_fisica">
    <div class="fpx-col">
      <section class="fpx-sec" aria-labelledby="c-t-fac">
        <header><span class="fpx-ic"><svg class="i"><use href="#i-recibo"/></svg></span><div><h3 id="c-t-fac">Proveedor y factura</h3><p>Los datos de la factura que te entregaron.</p></div></header>
        <div class="fpx-cuerpo">
          <div class="campo"><label for="c-prov">Proveedor <span class="ob">*</span></label>
            <select id="c-prov" name="<?= campo('proveedor.ref') ?>" required>
              <option value="">Elige un proveedor</option>
              <?php foreach ($proveedores as $p): ?><option value="<?= $e(clave('proveedor', $p)) ?>"><?= $e($p->razon_social . ' · ' . ref('proveedor.rif', $p)) ?></option><?php endforeach; ?>
            </select>
            <button type="button" class="cpx-nuevo" data-abrir-modal="modal-proveedor"><svg class="i"><use href="#i-mas"/></svg>Proveedor nuevo</button>
          </div>
          <div class="campo"><label for="c-fac">N.º de factura <span class="ob">*</span></label><input id="c-fac" name="numero_factura" maxlength="50" autocomplete="off" placeholder="A-00046120" required></div>
          <div class="campo"><label for="c-comprador">Empleado que compró <span class="ob">*</span></label><input id="c-comprador" name="comprado_por" list="lista-empleados" maxlength="150" autocomplete="off" placeholder="Nombre del empleado" value="<?= $e($_SESSION['nombre'] ?? '') ?>" required>
            <datalist id="lista-empleados"><?php foreach ($empleados as $emp): ?><option value="<?= $e($emp->nombre_completo) ?>"></option><?php endforeach; ?></datalist>
          </div>
          <div class="campo"><label for="c-fecha">Fecha de recepción <span class="ob">*</span></label><input id="c-fecha" type="date" name="fecha_compra" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required></div>
        </div>
      </section>

      <section class="fpx-sec" aria-labelledby="c-t-pago">
        <header><span class="fpx-ic"><svg class="i"><use href="#i-tarjeta"/></svg></span><div><h3 id="c-t-pago">Pago de la compra</h3><p>Cómo se le pagó al proveedor.</p></div></header>
        <div class="fpx-cuerpo">
          <div class="campo"><label for="c-metodo">Método de pago <span class="ob">*</span></label>
            <select id="c-metodo" name="<?= campo('metodo.ref') ?>" required>
              <option value="">Elige el método</option>
              <?php foreach ($metodos as $m): ?><option value="<?= $e(clave('metodo', $m)) ?>"><?= $e($m->nombre) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="campo"><label for="c-banco">Banco de salida</label>
            <select id="c-banco" name="banco">
              <option value="">No aplica</option>
              <?php foreach ($bancosLista as $b): ?><option value="<?= $e($b) ?>"><?= $e($b) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="campo ancho"><label for="c-ref">Referencia del pago</label><input id="c-ref" name="referencia_pago" maxlength="100" autocomplete="off" inputmode="numeric" placeholder="Número de la transferencia o del pago móvil"></div>
        </div>
      </section>

      <section class="fpx-sec" aria-labelledby="c-t-prod">
        <header><span class="fpx-ic"><svg class="i"><use href="#i-caja"/></svg></span><div><h3 id="c-t-prod">Productos recibidos</h3><p>Cuántos llegaron y a qué costo.</p></div><span class="dv-chip neutro cpx-cuenta" id="cuenta-renglones"></span></header>
        <ul class="cpx-rgs" id="renglones"></ul>
        <button type="button" class="cpx-agregar" id="agregar-renglon"><svg class="i"><use href="#i-mas"/></svg>Agregar otro producto</button>
      </section>
    </div>

    <aside class="fpx-col">
      <div class="cpx-res">
        <div class="cpx-tot">
          <div class="cpx-tot-cab"><span>Total de la compra</span>
            <div class="cpx-montos"><div><small>Dólares</small><b id="r-total">$0.00</b></div><div><small>Bolívares</small><b id="r-bs">0.00 Bs</b></div></div>
          </div>
          <dl>
            <div><dt>Productos</dt><dd id="r-renglones">0</dd></div>
            <div><dt>Unidades que entran</dt><dd id="r-unidades">0</dd></div>
            <div><dt>Precios que cambian</dt><dd class="amb" id="r-precios">0</dd></div>
          </dl>
        </div>
        <div class="cpx-acc">
          <p class="cpx-aviso" id="r-aviso" hidden></p>
          <button type="submit" class="btn btn-pri" id="c-guardar"><svg class="i"><use href="#i-check"/></svg>Guardar compra</button>
          <a href="index.php?action=compras" class="btn btn-sec">Cancelar</a>
          <small id="r-destino">Al guardar, las unidades se suman al inventario.</small>
        </div>
      </div>
    </aside>
  </form>
  <div class="fpx-barra" id="cp-barra" role="region" aria-label="Cambios sin guardar" hidden>
    <b>Tienes una compra sin guardar</b>
    <button type="button" class="btn btn-sec" id="cp-descartar">Descartar</button>
    <button type="submit" form="form-compra" class="btn btn-pri"><svg class="i"><use href="#i-check"/></svg>Guardar compra</button>
  </div>
</section>

<template id="tpl-renglon">
  <li class="cpx-rg nuevo">
    <div class="cpx-rg-cab">
      <span class="sin-foto"><svg class="i"><use href="#i-caja"/></svg></span>
      <select name="<?= campo('producto.ref') ?>[]" required aria-label="Producto" data-buscar="Buscar producto"></select>
      <select name="<?= campo('variante.ref') ?>[]" class="cpx-sel-variante" style="display:none;" aria-label="Variante">
        <option value="0">Variante principal / Única</option>
      </select>
      <small class="cpx-hoy">Elige qué llegó en la factura</small>
      <span class="cpx-stock" hidden></span>
      <button type="button" class="ic-btn" data-quitar aria-label="Quitar producto"><svg class="i"><use href="#i-basura"/></svg></button>
    </div>
    <div class="cpx-nuevo-prod" hidden>
      <div class="campo"><label>Código</label><input name="nuevo_codigo[]" maxlength="50" autocomplete="off" placeholder="P-0080"></div>
      <div class="campo"><label>Nombre</label><input name="nuevo_nombre[]" maxlength="150" autocomplete="off" placeholder="Ej.: Blusa de lino manga corta"></div>
      <?php if ($categorias): ?>
      <div class="campo ancho"><label>Categoría</label>
        <select name="nuevo_categoria[]">
          <option value="">Elige una categoría</option>
          <?php foreach ($catPrincipales as $padre): ?>
            <option value="<?= $e(clave('categoria', $padre)) ?>"><?= $e($padre->nombre_categoria) ?></option>
            <?php foreach ($catHijas[clave('categoria', $padre)] ?? [] as $sub): ?><option value="<?= $e(clave('categoria', $sub)) ?>">↳ <?= $e($sub->nombre_categoria) ?></option><?php endforeach; ?>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <p class="ayuda">Se crea en el inventario al guardar la compra. Después le agregas la foto desde Productos.</p>
    </div>
    <div class="cpx-rg-num">
      <div class="campo"><label>Cantidad</label><div class="ajx-cant"><button type="button" data-paso="-1" aria-label="Restar uno">−</button><input name="cantidad[]" type="number" min="1" step="1" inputmode="numeric" value="1" required><button type="button" data-paso="1" aria-label="Sumar uno">+</button></div></div>
      <div class="campo"><label>Costo unitario</label><div class="fpx-pre"><span aria-hidden="true">$</span><input name="costo_unitario[]" type="number" min="0" step="0.01" inputmode="decimal" placeholder="0.00" required></div></div>
      <div class="campo"><label>Ganancia</label><div class="fpx-pre pct"><span aria-hidden="true">%</span><input name="margen[]" type="number" step="0.1" inputmode="decimal" placeholder="30"></div></div>
      <div class="campo"><label>Precio de venta</label><div class="fpx-pre"><span aria-hidden="true">$</span><input name="precio_venta[]" type="number" min="0" step="0.01" inputmode="decimal" placeholder="0.00"></div></div>
    </div>
    <div class="cpx-rg-pie">
      <span class="cpx-izq">
        <label class="cpx-precio" hidden><input type="checkbox" checked><input type="hidden" name="actualizar_precio[]" value="0"><span>Actualizar el precio: <b></b></span></label>
        <span class="cpx-perdida" hidden><svg class="i"><use href="#i-alerta"/></svg><span></span></span>
      </span>
      <span class="cpx-sub">Subtotal <b>$0.00</b></span>
    </div>
  </li>
</template>
<?php include __DIR__ . '/_modal_proveedor.php'; ?>
<script type="application/json" id="datos-compra"><?= json_encode(array_map(function($p) {
    return [
        'id'        => clave('producto', $p),
        'c'         => $p->codigo ?? 'P-' . str_pad(clave('producto', $p), 4, '0', STR_PAD_LEFT),
        'n'         => $p->nombre_producto,
        't'         => (int) $p->stock,
        'costo'     => (float) ($p->costo_compra ?? 0),
        'precio'    => (float) $p->precio,
        'img'       => !empty($p->imagen) ? 'public/uploads/' . $p->imagen : '',
        'variantes' => array_map(function($v) {
            return [
                'id'     => clave('variante', $v),
                'talla'  => $v->talla ?? '',
                'color'  => $v->color ?? '',
                'modelo' => $v->modelo ?? '',
                'stock'  => (int) $v->stock,
                'precio' => (float) ($v->precio ?? 0)
            ];
        }, $p->variantes ?? [])
    ];
}, $productos), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>