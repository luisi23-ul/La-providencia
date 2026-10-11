<?php
$menu = 'compras';
$tituloPagina = 'Registrar compra';
$scripts = ['nueva_compra'];
$proveedores = $proveedores ?? [];
$productos = $productos ?? $listaProductos ?? [];
$metodos = $metodos ?? [];
$empleados = $empleados ?? [];

$bancosLista = [
  'Banesco', 'Banco de Venezuela', 'Mercantil', 'BBVA Provincial', 
  'BNC Nacional de Crédito', 'Bancaribe', 'Banco Exterior', 'Banco Plaza', 
  'Banco del Tesoro', 'Banco Bicentenario', 'Banplus', '100% Banco'
];

include __DIR__ . '/../admin/_abrir.php';
?>
<section class="contenido">
  <a href="index.php?action=compras" class="volver"><svg class="i"><use href="#i-volver"/></svg>Compras</a>
  <header class="cab">
    <div class="cab-txt"><h2>Registrar compra</h2><small>Carga la factura del proveedor. Al guardar, las unidades se suman al stock del destino.</small></div>
  </header>
  <form class="compra-layout" id="form-compra" action="index.php?action=guardar_compra" method="POST">
    <div class="compra-col">
      <section class="caja" aria-labelledby="t-factura">
        <header class="caja-cab"><h3 id="t-factura">Datos de la factura</h3></header>
        <div class="caja-cuerpo form-grid">
          <div class="campo">
            <label for="c-prov">Proveedor <span class="ob">*</span></label>
            <select id="c-prov" name="id_proveedor" required>
              <option value="">Elige un proveedor</option>
              <?php foreach ($proveedores as $p): if (!$p->estado) continue; ?><option value="<?= (int) $p->id ?>"><?= $e($p->razon_social . ' · ' . $p->rif_cedula) ?></option><?php endforeach; ?>
            </select>
            <button type="button" class="enlace-boton" data-abrir-modal="modal-proveedor"><svg class="i"><use href="#i-mas"/></svg>Proveedor nuevo</button>
          </div>
          <div class="campo"><label for="c-fac">N.º de factura <span class="ob">*</span></label><input id="c-fac" name="numero_factura" maxlength="50" placeholder="A-00046120" required></div>
          <div class="campo"><label for="c-fecha">Fecha de recepción <span class="ob">*</span></label><input id="c-fecha" type="datetime-local" name="fecha_compra" value="<?= date('Y-m-d\TH:i') ?>" required></div>
          <div class="campo">
            <label for="c-comprador">Empleado que compró <span class="ob">*</span></label>
            <input id="c-comprador" name="comprado_por" list="lista-empleados" placeholder="Nombre del empleado" value="<?= $e($_SESSION['nombre'] ?? '') ?>" required autocomplete="off">
            <datalist id="lista-empleados">
              <?php foreach ($empleados as $emp): ?>
                <option value="<?= $e($emp->nombre_completo) ?>"></option>
              <?php endforeach; ?>
            </datalist>
          </div>
          <div class="campo">
            <span class="lbl" id="l-destino">Destino del stock <span class="ob">*</span></span>
            <div class="opciones" role="radiogroup" aria-labelledby="l-destino">
              <label class="opcion"><input type="radio" name="destino_stock" value="tienda_fisica" checked><span>Tienda física</span></label>
              <label class="opcion"><input type="radio" name="destino_stock" value="online"><span>Online</span></label>
            </div>
          </div>
          <div class="campo">
            <label for="c-metodo">Método de pago <span class="ob">*</span></label>
            <select id="c-metodo" name="id_metodo_pago" required>
              <option value="">Elige método</option>
              <?php foreach ($metodos as $m): ?>
                <option value="<?= (int)$m->id ?>"><?= $e($m->nombre) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="campo">
            <label for="c-banco">Banco de salida</label>
            <select id="c-banco" name="banco">
              <option value="">Selecciona banco (si aplica)</option>
              <?php foreach ($bancosLista as $b): ?>
                <option value="<?= $e($b) ?>"><?= $e($b) ?></option>
              <?php endforeach; ?>
              <option value="Caja Chica">Caja Chica</option>
              <option value="Otro">Otro</option>
            </select>
          </div>
          <div class="campo">
            <label for="c-ref">N.º de referencia</label>
            <input id="c-ref" name="referencia_pago" maxlength="100" placeholder="Referencia bancaria">
          </div>
        </div>
      </section>

      <section class="caja" aria-labelledby="t-renglones">
        <header class="caja-cab"><h3 id="t-renglones">Productos recibidos</h3><span class="muted" id="cuenta-renglones"></span></header>
        <ul class="renglones-lista" id="renglones"></ul>
        <div class="agregar"><button type="button" class="btn btn-sec btn-peq" id="agregar-renglon"><svg class="i"><use href="#i-mas"/></svg>Agregar producto</button></div>
      </section>
    </div>

    <aside class="caja resumen-compra" aria-labelledby="t-resumen">
      <header class="caja-cab"><h3 id="t-resumen">Resumen</h3></header>
      <div class="caja-cuerpo">
        <div class="fila-total"><span>Productos</span><b id="r-renglones">0</b></div>
        <div class="fila-total"><span>Unidades</span><b id="r-unidades">0</b></div>
        <div class="fila-total"><span>Precios que cambian</span><b id="r-precios">0</b></div>
        <div class="fila-total"><span>Ganancia total estimada</span><b id="r-ganancia" style="color:#137333;">+$0.00 (0%)</b></div>
        <div class="fila-total grande"><span>Total factura</span><b id="r-total">$0.00</b></div>
        <div class="fila-total"><span>En bolívares</span><b id="r-bs">0.00 Bs</b></div>
        <p class="aviso" id="r-aviso"></p>
        <div class="acciones-col">
          <button type="submit" class="btn btn-pri btn-bloque" id="c-guardar">Guardar compra</button>
          <a href="index.php?action=compras" class="btn btn-sec btn-bloque">Cancelar</a>
        </div>
      </div>
    </aside>
  </form>
</section>

<template id="tpl-renglon">
  <li class="renglon">
    <div class="rg-prod">
      <div class="campo" style="flex: 1;"><label>Producto</label><select name="id_producto[]" required></select></div>
      <span class="rg-stock"></span>
      <button type="button" class="ic-btn" data-quitar aria-label="Quitar producto"><svg class="i"><use href="#i-basura"/></svg></button>
    </div>
    
    <!-- Bloque dinámico para producto nuevo -->
    <div class="rg-nuevo-prod" style="display: none; grid-template-columns: 140px 1fr; gap: 10px; margin: 8px 0; background: #f0fdf4; padding: 10px; border-radius: 8px; border: 1px dashed #86efac;">
      <div class="campo">
        <label style="font-size: 0.8rem; font-weight: 600; color: #166534;">Código producto <span class="ob">*</span></label>
        <input name="nuevo_codigo[]" type="text" placeholder="Ej: P-0080" autocomplete="off">
      </div>
      <div class="campo">
        <label style="font-size: 0.8rem; font-weight: 600; color: #166534;">Nombre del nuevo producto <span class="ob">*</span></label>
        <input name="nuevo_nombre[]" type="text" placeholder="Descripción o nombre del producto" autocomplete="off">
      </div>
    </div>

    <div class="rg-numeros">
      <div class="campo"><label>Cantidad</label><input name="cantidad[]" type="number" min="1" step="1" inputmode="numeric" value="1" required></div>
      <div class="campo"><label>Costo unitario ($)</label><input name="costo_unitario[]" type="number" min="0" step="0.01" inputmode="decimal" required></div>
      <div class="campo"><label>Ganancia (%)</label><input name="margen[]" type="number" min="0" step="1" inputmode="numeric"></div>
      <div class="campo"><label>Precio de venta ($)</label><input name="precio_venta[]" type="number" min="0" step="0.01" inputmode="decimal"></div>
      <div class="rg-sub"><span>Subtotal</span><b>$0.00</b></div>
    </div>
    <label class="rg-actualizar"><input type="checkbox" checked><input type="hidden" name="actualizar_precio[]" value="1"><span>Actualizar el precio del producto</span><small></small></label>
  </li>
</template>
<?php include __DIR__ . '/_modal_proveedor.php'; ?>
<script type="application/json" id="datos-compra"><?= json_encode(array_map(fn($p) => ['id' => (int) $p->id, 'c' => $p->codigo ?? 'P-' . str_pad($p->id, 4, '0', STR_PAD_LEFT), 'n' => $p->nombre_producto, 't' => (int) $p->stock, 'o' => (int) ($p->stock_online ?? 0), 'costo' => (float) ($p->costo_compra ?? 0), 'precio' => (float) $p->precio], $productos), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>