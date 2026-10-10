<?php
$menu = 'ventas';
$tituloPagina = 'Detalle de venta';
$detalles = $detalles ?? $productos ?? [];
$pagos = $pagos ?? [];
include __DIR__ . '/admin/_abrir.php';
$estados = ['pendiente' => ['pend', 'Pendiente de pago'], 'pagado' => ['az', 'Pagado, por retirar'], 'retirado' => ['ok', 'Retirado'], 'entregado' => ['ok', 'Entregado'], 'completado' => ['ok', 'Completado'], 'cancelado' => ['agot', 'Cancelado']];
$es = $estados[$venta->estado ?? ''] ?? ['pend', $venta->estado ?? ''];
$aprobado = 0; $porVerificar = 0;
foreach ($pagos as $p) {
    $enUsd = ($p->moneda ?? 'Bs') === 'USD' ? $p->monto : $p->monto / ($p->tasa_cambio ?: $tasa);
    if ($p->estado_verificacion === 'aprobado') $aprobado += $enUsd;
    elseif ($p->estado_verificacion === 'pendiente') $porVerificar += $enUsd;
}
$unidades = array_sum(array_map(fn($d) => $d->cantidad, $detalles));
$verif = ['aprobado' => ['ok', 'Aprobado'], 'pendiente' => ['pend', 'Por verificar'], 'rechazado' => ['agot', 'Rechazado']];
?>
<section class="contenido">
  <a href="index.php?action=ventas" class="volver"><svg class="i"><use href="#i-volver"/></svg>Ventas</a>
  <header class="cab">
    <div class="cab-txt"><small>Venta, <?= ($venta->origen_venta ?? 'online') === 'online' ? 'online' : 'tienda física' ?></small><div class="titulo-est"><h2>#<?= str_pad($venta->id, 4, '0', STR_PAD_LEFT) ?></h2><span class="est <?= $es[0] ?>"><?= $es[1] ?></span></div></div>
    <div class="cab-acc">
      <?php if ($porVerificar): ?><a href="index.php?action=verificar_pagos" class="btn btn-pri"><svg class="i"><use href="#i-check"/></svg>Revisar pago pendiente</a><?php endif; ?>
    </div>
  </header>

  <section class="caja" aria-label="Datos de la venta">
    <div class="datos-grid">
      <div class="dato"><span>Cliente</span><b><?= $e($venta->nombre) ?></b><?php if (!empty($venta->correo)): ?><small><?= $e($venta->correo) ?></small><?php endif; ?></div>
      <div class="dato"><span>Fecha</span><b><?= date('d/m/Y, g:i a', strtotime($venta->fecha)) ?></b></div>
      <div class="dato"><span>Origen</span><b><?= ($venta->origen_venta ?? 'online') === 'online' ? 'Tienda online' : 'Tienda física' ?></b></div>
      <?php if (!empty($venta->vendedor)): ?><div class="dato"><span>Atendió</span><b><?= $e($venta->vendedor) ?></b></div><?php endif; ?>
    </div>
  </section>

  <section class="tabla-caja" aria-label="Productos de la venta">
    <div class="tabla-scroll">
      <table class="tabla-resp">
        <thead><tr><th>Producto</th><th class="num">Cantidad</th><th class="num">Precio</th><th class="num">Subtotal</th></tr></thead>
        <tbody>
        <?php foreach ($detalles as $d): ?>
          <tr>
            <td class="t-tit"><div class="prod"><?php if (!empty($d->imagen)): ?><img class="foto chica" src="public/uploads/<?= $e($d->imagen) ?>" alt="" width="36" height="36"><?php endif; ?><div><b><?= $e($d->producto_nombre) ?></b><?php if (!empty($d->descuento) && $d->descuento > 0): ?><span>Descuento <?= (float) $d->descuento ?>%</span><?php endif; ?></div></div></td>
            <td class="num" data-label="Cantidad"><?= (int) $d->cantidad ?></td>
            <td class="num" data-label="Precio"><?= $usd($d->precio_unitario) ?></td>
            <td class="num" data-label="Subtotal"><b><?= $usd($d->subtotal) ?></b></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td></td><td class="num"><?= $unidades ?> unid.</td><td class="num">Total</td><td class="num"><?= $usd($venta->total) ?></td></tr></tfoot>
      </table>
    </div>
    <?php if (!$detalles): ?><div class="vacio-filtro">Esta venta no tiene productos registrados.</div><?php endif; ?>
  </section>

  <div class="rejilla-2">
    <section class="tabla-caja" aria-labelledby="t-pagos-venta">
      <header class="caja-cab"><h3 id="t-pagos-venta">Pagos registrados</h3><?php if (count($pagos) > 1): ?><span class="muted">Pago mixto</span><?php endif; ?></header>
      <div class="tabla-scroll">
        <table class="tabla-resp">
          <thead><tr><th>Método</th><th>Referencia</th><th class="num">Monto</th><th>Verificación</th></tr></thead>
          <tbody>
          <?php foreach ($pagos as $p): $vf = $verif[$p->estado_verificacion] ?? ['pend', $p->estado_verificacion]; ?>
            <tr>
              <td class="t-tit"><div class="t-nombre"><b><?= $e($p->metodo) ?></b><span><?= date('d/m, g:i a', strtotime($p->fecha_pago)) ?><?= !empty($p->banco) ? ', ' . $e($p->banco) : '' ?></span></div></td>
              <td data-label="Referencia" class="cod"><?= $e($p->referencia ?: 'No aplica') ?></td>
              <td class="num" data-label="Monto"><b><?= ($p->moneda ?? 'Bs') === 'USD' ? $usd($p->monto) : number_format($p->monto, 2) . ' Bs' ?></b></td>
              <td data-label="Verificación"><span class="est <?= $vf[0] ?>"><?= $vf[1] ?></span><?php if (!empty($p->verificado_por_nombre)): ?><span class="muted" style="display:block"><?= $e($p->verificado_por_nombre) ?></span><?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if (!$pagos): ?><div class="vacio-filtro">Todavía no hay pagos registrados.</div><?php endif; ?>
    </section>
    <section class="caja" aria-labelledby="t-saldo">
      <header class="caja-cab"><h3 id="t-saldo">Cuenta de la venta</h3></header>
      <div class="caja-cuerpo">
        <div class="fila-total"><span>Total de la venta</span><b><?= $usd($venta->total) ?></b></div>
        <div class="fila-total"><span>Pagos aprobados</span><b><?= $usd($aprobado) ?></b></div>
        <div class="fila-total"><span>Pagos por verificar</span><b><?= $usd($porVerificar) ?></b></div>
        <div class="fila-total grande"><span>Saldo pendiente</span><b><?= $usd(max(0, $venta->total - $aprobado)) ?></b></div>
      </div>
    </section>
  </div>
</section>
<?php include __DIR__ . '/admin/_cerrar.php'; ?>
