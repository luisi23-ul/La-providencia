<?php
$menu = 'compras';
$tituloPagina = 'Detalle de compra';
$detalles = $detalles ?? [];
include __DIR__ . '/../admin/_abrir.php';
$num = '#' . str_pad((string) $compra->id, 4, '0', STR_PAD_LEFT);
$anulada = $compra->estado === 'anulado';
$destino = 'Tienda';
$unidades = array_sum(array_map(fn($d) => (int) $d->cantidad, $detalles));
$ganancia = array_sum(array_map(fn($d) => (float) ($d->ganancia_total_linea ?? 0), $detalles));
$margenTotal = (float) $compra->total > 0 ? ($ganancia / (float) $compra->total) * 100 : 0;
$dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$t = strtotime($compra->fecha_compra);
$fechaLarga = ucfirst($dias[date('w', $t)]) . ' ' . date('j', $t) . ' de ' . $meses[date('n', $t) - 1] . ' de ' . date('Y', $t) . ', ' . date('g:i a', $t);
$iniciales = function ($nombre) { $p = preg_split('/\s+/', trim((string) $nombre)); return mb_strtoupper(mb_substr($p[0] ?? '', 0, 1) . mb_substr($p[1] ?? '', 0, 1)); };
$metodo = mb_strtolower($compra->metodo_pago ?? '');
$icoPago = str_contains($metodo, 'móvil') || str_contains($metodo, 'movil') ? 'movil' : (str_contains($metodo, 'transfer') ? 'banco' : (str_contains($metodo, 'efectivo') ? 'efectivo' : 'tarjeta'));
$pct = fn($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.') . ' %';
?>
<section class="contenido dv">
  <header class="cab">
    <div class="cab-txt"><small>Compra</small><div class="titulo-atras"><a href="index.php?action=compras" class="atras" aria-label="Volver a Compras"><svg class="i"><use href="#i-volver"/></svg></a><h2><?= $num ?></h2></div><p class="dv-fecha"><?= $fechaLarga ?></p></div>
    <div class="cab-acc dv-chips">
      <span class="dv-chip<?= $anulada ? ' agot' : '' ?>"><svg class="i"><use href="#i-<?= $anulada ? 'x' : 'check' ?>"/></svg><?= $anulada ? 'Anulada' : 'Recibida' ?></span>
      <span class="dv-chip neutro"><svg class="i"><use href="#i-tienda"/></svg><?= $destino ?></span>
      <a href="index.php?action=exportar_pdf&amp;tipo=compra&amp;id=<?= (int) $compra->id ?>" target="_blank" class="btn btn-sec"><svg class="i"><use href="#i-archivo"/></svg>PDF</a>
      <?php if (!$anulada): ?>
      <form method="POST" action="index.php?action=anular_compra" class="dc-anular">
        <input type="hidden" name="id" value="<?= (int) $compra->id ?>">
        <button type="submit" class="btn btn-peligro" data-confirmar="¿Anular la compra <?= $num ?>?" data-confirmar-texto="Se restarán <?= $unidades ?> unidades del stock de <?= $destino ?> y quedará registrado en Movimientos. La compra no se borra: queda en el historial como anulada." data-confirmar-boton="Anular compra"><svg class="i"><use href="#i-x"/></svg>Anular compra</button>
      </form>
      <?php endif; ?>
    </div>
  </header>
  <?php if ($anulada): ?><p class="anulada-banda">Esta compra está anulada. Las unidades se restaron del stock de <?= $destino ?>.</p><?php endif; ?>

  <section class="rc dc-rc" aria-label="Cuenta de la compra">
    <div class="rc-lineas">
      <h3>Cuenta</h3>
      <div class="rc-linea"><span><?= count($detalles) === 1 ? '1 producto' : count($detalles) . ' productos' ?> (<?= $unidades ?> <?= $unidades === 1 ? 'unidad' : 'unidades' ?>)</span><b><?= $usd($compra->total) ?></b></div>
      <div class="rc-linea total"><span>Total pagado</span><span class="dc-tot"><span><small>Dólares</small><b><?= $usd($compra->total) ?></b></span><span><small>Bolívares</small><b><?= $bs($compra->total) ?></b></span></span></div>
    </div>
    <div class="rc-saldo dc-ganancia">
      <span>Ganancia estimada</span>
      <b>+<?= $usd($ganancia) ?></b>
      <small class="dc-pct"><?= $pct($margenTotal) ?> sobre el costo</small>
    </div>
  </section>

  <div class="dv-cuerpo">
    <div class="dv-col">
      <section class="dv-caja" aria-labelledby="dc-prod-t">
        <header class="dc-cab"><span class="fpx-ic"><svg class="i"><use href="#i-caja"/></svg></span><div><h3 id="dc-prod-t">Productos</h3><p><?= count($detalles) === 1 ? '1 producto' : count($detalles) . ' productos' ?> · <?= $unidades ?> <?= $unidades === 1 ? 'unidad recibida' : 'unidades recibidas' ?></p></div></header>
        <div class="dc-lineas" role="table" aria-label="Productos de la compra">
          <div class="dc-linea dc-linea-cab" role="row">
            <span role="columnheader">Producto</span><span role="columnheader">Cantidad</span><span role="columnheader">Costo c/u</span><span role="columnheader">Precio de venta</span><span role="columnheader">Total</span>
          </div>
          <?php foreach ($detalles as $d): $g = (float) ($d->ganancia_total_linea ?? 0); ?>
          <div class="dc-linea" role="row">
            <div class="dc-l-prod" role="cell">
              <?php if (!empty($d->imagen)): ?><img src="public/uploads/<?= $e($d->imagen) ?>" alt="" width="48" height="48" loading="lazy"><?php else: ?><span class="dv-sin-foto"><svg class="i"><use href="#i-caja"/></svg></span><?php endif; ?>
              <div><b><?= $e($d->nombre_producto) ?></b><small><?= $e($d->codigo ?? '') ?></small></div>
            </div>
            <span class="dc-l-num" role="cell" data-et="Cantidad"><?= (int) $d->cantidad ?></span>
            <span class="dc-l-num" role="cell" data-et="Costo c/u"><?= $usd($d->costo_unitario) ?></span>
            <span class="dc-l-num dc-l-venta" role="cell" data-et="Se vende a"><?= !empty($d->precio_venta) ? $usd($d->precio_venta) : 'Sin precio' ?></span>
            <span class="dc-l-num dc-l-total" role="cell" data-et="Total"><?= $usd($d->subtotal) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="dv-caja" aria-labelledby="dc-prov-t">
        <header class="dc-cab"><span class="fpx-ic"><svg class="i"><use href="#i-camion"/></svg></span><div><h3 id="dc-prov-t">Proveedor</h3><p>A quién se le compró la mercancía.</p></div></header>
        <div class="dv-cliente"><span class="dv-av" aria-hidden="true"><?= $e($iniciales($compra->razon_social)) ?></span><b><?= $e($compra->razon_social) ?></b><small>RIF <?= $e($compra->rif_cedula ?? '') ?></small></div>
        <dl class="dc-dl dc-dl-fila">
          <div><dt><svg class="i" aria-hidden="true"><use href="#i-movil"/></svg>Teléfono</dt><dd><?= $e($compra->telefono ?: 'Sin teléfono') ?></dd></div>
          <div><dt><svg class="i" aria-hidden="true"><use href="#i-recibo"/></svg>N.º de factura</dt><dd><?= $e($compra->numero_factura ?: 'Sin número') ?></dd></div>
          <div><dt><svg class="i" aria-hidden="true"><use href="#i-bolsa"/></svg>Comprado por</dt><dd><?= $e($compra->comprado_por ?: 'No indicado') ?></dd></div>
          <div><dt><svg class="i" aria-hidden="true"><use href="#i-usuarios"/></svg>Registrada por</dt><dd><?= $e($compra->usuario ?? 'Admin') ?></dd></div>
        </dl>
      </section>
    </div>

    <div class="dv-col">
      <?php if (!empty($compra->metodo_pago)): ?>
      <section class="dv-caja" aria-labelledby="dc-pago-t">
        <header class="dc-cab"><span class="fpx-ic"><svg class="i"><use href="#i-tarjeta"/></svg></span><div><h3 id="dc-pago-t">Pago al proveedor</h3><p>Cómo se pagó esta factura.</p></div></header>
        <dl class="dc-dl">
          <div><dt><svg class="i" aria-hidden="true"><use href="#i-<?= $icoPago ?>"/></svg>Método</dt><dd><?= $e($compra->metodo_pago) ?></dd></div>
          <div><dt><svg class="i" aria-hidden="true"><use href="#i-banco"/></svg>Banco</dt><dd><?= $e($compra->banco ?: 'No aplica') ?></dd></div>
          <div><dt><svg class="i" aria-hidden="true"><use href="#i-etiqueta"/></svg>Referencia</dt><dd class="dc-ref"><?php if (!empty($compra->referencia_pago)): ?><?= $e($compra->referencia_pago) ?><button type="button" class="vf-copiar" data-tip="Copiar" data-copiar="<?= $e($compra->referencia_pago) ?>" aria-label="Copiar referencia <?= $e($compra->referencia_pago) ?>"><svg class="i"><use href="#i-copiar"/></svg></button><?php else: ?>Sin referencia<?php endif; ?></dd></div>
          <div class="dc-dl-total"><dt><svg class="i" aria-hidden="true"><use href="#i-dolar"/></svg>Monto pagado</dt><dd class="dc-tot"><span><small>Dólares</small><b><?= $usd($compra->total) ?></b></span><span><small>Bolívares</small><b><?= $bs($compra->total) ?></b></span></dd></div>
        </dl>
      </section>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>