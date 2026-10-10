<?php
$menu = 'compras';
$tituloPagina = 'Detalle de compra';
$detalles = $detalles ?? [];
include __DIR__ . '/../admin/_abrir.php';
$num = '#' . str_pad($compra->id, 4, '0', STR_PAD_LEFT);
$anulada = $compra->estado === 'anulado';
$destino = $compra->destino_stock === 'online' ? 'Online' : 'Tienda física';
$unidades = array_sum(array_map(fn($d) => $d->cantidad, $detalles));
?>
<section class="contenido">
  <a href="index.php?action=compras" class="volver"><svg class="i"><use href="#i-volver"/></svg>Compras</a>
  <header class="cab">
    <div class="cab-txt"><small>Compra</small><div class="titulo-est"><h2><?= $num ?></h2><span class="est <?= $anulada ? 'agot' : 'ok' ?>"><?= $anulada ? 'Anulada' : 'Recibida' ?></span></div></div>
    <div class="cab-acc">
      <a href="index.php?action=exportar_pdf&tipo=compra&id=<?= (int) $compra->id ?>" target="_blank" class="btn btn-sec"><svg class="i"><use href="#i-archivo"/></svg>PDF</a>
      <?php if (!$anulada): ?><button type="button" class="btn btn-peligro" data-abrir-modal="modal-anular"><svg class="i"><use href="#i-x"/></svg>Anular compra</button><?php endif; ?>
    </div>
  </header>
  <?php if ($anulada): ?><p class="anulada-banda">Esta compra está anulada. Las unidades se restaron del stock de <?= $destino ?>.</p><?php endif; ?>

  <section class="caja" aria-label="Datos de la compra">
    <div class="datos-grid">
      <div class="dato"><span>Proveedor</span><b><?= $e($compra->razon_social) ?></b><small><?= $e($compra->rif_cedula ?? '') ?></small></div>
      <div class="dato"><span>Factura</span><b class="cod"><?= $e($compra->numero_factura ?: 'Sin número') ?></b></div>
      <div class="dato"><span>Recibida</span><b><?= date('d/m/Y, g:i a', strtotime($compra->fecha_compra)) ?></b></div>
      <div class="dato"><span>Destino</span><b><?= $destino ?></b></div>
      <div class="dato"><span>Registrada por</span><b><?= $e($compra->usuario ?? '') ?></b></div>
    </div>
  </section>

  <section class="tabla-caja" aria-label="Productos de la compra">
    <div class="tabla-scroll">
      <table class="tabla-resp">
        <thead><tr><th class="c-media">Código</th><th>Producto</th><th class="num">Cantidad</th><th class="num">Costo unitario</th><th class="num c-media">Precio de venta</th><th class="num">Subtotal</th></tr></thead>
        <tbody>
        <?php foreach ($detalles as $d): ?>
          <tr>
            <td class="cod c-media" data-label="Código"><?= $e($d->codigo ?? '') ?></td>
            <td class="t-tit"><div class="prod"><?php if (!empty($d->imagen)): ?><img class="foto chica" src="public/uploads/<?= $e($d->imagen) ?>" alt="" width="36" height="36"><?php endif; ?><div><b><?= $e($d->nombre_producto) ?></b><?php if (!empty($d->precio_venta)): ?><span class="si-estrecho">Venta <?= $usd($d->precio_venta) ?><?= $d->margen !== null ? ', +' . (float) $d->margen . '%' : '' ?></span><?php endif; ?></div></div></td>
            <td class="num" data-label="Cantidad"><?= (int) $d->cantidad ?></td>
            <td class="num" data-label="Costo unitario"><?= $usd($d->costo_unitario) ?></td>
            <td class="num c-media" data-label="Precio de venta"><?php if (!empty($d->precio_venta)): ?><?= $usd($d->precio_venta) ?><br><span class="margen">+<?= (float) $d->margen ?>%<?= !empty($d->actualizo_precio) ? ', aplicado' : '' ?></span><?php else: ?><span class="muted">No indicado</span><?php endif; ?></td>
            <td class="num" data-label="Subtotal"><b><?= $usd($d->subtotal) ?></b></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td class="c-media"></td><td></td><td class="num"><?= $unidades ?> unid.</td><td class="num c-media"></td><td class="num">Total</td><td class="num"><?= $usd($compra->total) ?></td></tr></tfoot>
      </table>
    </div>
  </section>
</section>

<?php if (!$anulada): ?>
<dialog class="modal chico" id="modal-anular" aria-labelledby="ma-titulo">
  <form method="POST" action="index.php?action=anular_compra">
    <input type="hidden" name="id" value="<?= (int) $compra->id ?>">
    <header class="modal-cab"><span class="modal-ic peligro"><svg class="i"><use href="#i-alerta"/></svg></span><div><h3 id="ma-titulo">¿Anular la compra <?= $num ?>?</h3></div></header>
    <div class="modal-cuerpo">
      <p>Se restarán <b><?= $unidades ?> unidades</b> del stock de <b><?= $destino ?></b> y quedará registrado en Movimientos.</p>
      <p class="muted">La compra no se borra: queda en el historial marcada como anulada.</p>
    </div>
    <footer class="modal-pie">
      <button type="button" class="btn btn-sec" data-cerrar>No, volver</button>
      <button type="submit" class="btn btn-peligro-lleno">Sí, anular compra</button>
    </footer>
  </form>
</dialog>
<?php endif; ?>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>
