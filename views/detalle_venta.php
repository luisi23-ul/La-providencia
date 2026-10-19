<?php
$menu = 'ventas';
$tituloPagina = 'Detalle de venta';
$detalles = $detalles ?? $productos ?? [];
$pagos = $pagos ?? [];
include __DIR__ . '/admin/_abrir.php';
$estado = $venta->estado ?? '';
$cancelada = in_array($estado, ['cancelado', 'anulado'], true);
$enUsd = fn($p) => ($p->moneda ?? 'Bs') === 'USD' ? (float) $p->monto : (float) $p->monto / ((float) ($p->tasa_cambio ?? 0) ?: $tasa);
$aprobado = 0; $porVerificar = 0;
foreach ($pagos as $p) {
    if (ref('pago.estado', $p) === 'aprobado') $aprobado += $enUsd($p);
    elseif (ref('pago.estado', $p) === 'pendiente') $porVerificar += $enUsd($p);
}
$saldo = max(0, (float) $venta->total - $aprobado);
$conTasa = array_filter($pagos, fn($p) => (float) ($p->tasa_cambio ?? 0) > 0);
usort($conTasa, fn($x, $y) => strcmp((string) $x->fecha_pago, (string) $y->fecha_pago));
$tasaVenta = $conTasa ? (float) reset($conTasa)->tasa_cambio : (float) $tasa;
$bsVenta = fn($v) => number_format((float) $v * $tasaVenta, 2, ',', '.') . ' Bs';
$saldada = $saldo < 0.005;
$unidades = array_sum(array_map(fn($d) => $d->cantidad, $detalles));
$verif = ['aprobado' => ['ok', 'Aprobado'], 'pendiente' => ['pend', 'Por verificar'], 'rechazado' => ['agot', 'Rechazado']];
$origen = ($venta->origen_venta ?? 'online') === 'online' ? 'Tienda online' : 'Tienda física';

$dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$t = strtotime($venta->fecha);
$fechaLarga = ucfirst($dias[date('w', $t)]) . ' ' . date('j', $t) . ' de ' . $meses[date('n', $t) - 1] . ' de ' . date('Y', $t) . ', ' . date('g:i a', $t);
$corta = fn($f) => $f ? date('d/m, g:i a', strtotime($f)) : '';

if ($cancelada) $chipPago = null;
elseif ($saldada) $chipPago = ['', 'check', 'Pagada'];
elseif ($porVerificar > 0) $chipPago = ['pend', 'alerta', 'Pago por verificar'];
else $chipPago = ['pend', 'alerta', 'Pendiente de pago'];
$chipEstado = [
    'pagado' => ['neutro', 'bolsa', 'Por retirar'],
    'retirado' => ['', 'bolsa', 'Retirada'],
    'entregado' => ['', 'bolsa', 'Entregada'],
    'completado' => ['', 'check', 'Completada'],
    'cancelado' => ['agot', 'x', 'Cancelada'],
    'anulado' => ['agot', 'x', 'Anulada'],
][$estado] ?? null;

$iconoMetodo = function ($m) {
    $m = mb_strtolower($m ?? '');
    if (str_contains($m, 'móvil') || str_contains($m, 'movil')) return 'movil';
    if (str_contains($m, 'transfer')) return 'banco';
    if (str_contains($m, 'efectivo')) return 'efectivo';
    return 'tarjeta';
};
$titular = fn($p) => trim(($p->titular_nombre ?? '') . ' ' . ($p->titular_cedula ?? '')) !== ''
    ? implode(' · ', array_filter([trim($p->titular_nombre ?? ''), trim($p->titular_cedula ?? ''), trim($p->titular_telefono ?? '')]))
    : (preg_match('/Titular:\s*([^|]*?)\s*\|\s*Tel:\s*(.*)$/u', $p->observacion ?? '', $m) ? trim($m[1]) . ($m[2] !== '' ? ' · ' . trim($m[2]) : '') : '');
$iniciales = function ($nombre) {
    $partes = preg_split('/\s+/', trim((string) $nombre));
    return mb_strtoupper(mb_substr($partes[0] ?? '', 0, 1) . mb_substr($partes[1] ?? '', 0, 1));
};

$recorrido = [['recibo', 'Venta registrada', $corta($venta->fecha) . (!empty($venta->vendedor) ? ' · ' . $venta->vendedor : ''), true]];
foreach ($pagos as $p) {
    if (ref('pago.estado', $p) === 'aprobado') $recorrido[] = ['check', $p->metodo . ' aprobado', trim($corta($p->fecha_verificacion ?? $p->fecha_pago) . (!empty($p->verificado_por_nombre) ? ' · ' . $p->verificado_por_nombre : ''), ' ·'), true];
    elseif (ref('pago.estado', $p) === 'rechazado') $recorrido[] = ['x', $p->metodo . ' rechazado', trim($corta($p->fecha_verificacion ?? $p->fecha_pago) . (!empty($p->verificado_por_nombre) ? ' · ' . $p->verificado_por_nombre : ''), ' ·'), true];
    else $recorrido[] = ['alerta', $p->metodo . ' por verificar', $corta($p->fecha_pago), false];
}
if (in_array($estado, ['retirado', 'entregado', 'completado'], true)) {
    $quien = !empty($venta->retirado_por) ? ' · ' . $venta->retirado_por : '';
    $recorrido[] = ['bolsa', $estado === 'entregado' ? 'Entregada' : 'Retirada', !es_vacio($venta->fecha_entrega ?? null) ? $corta($venta->fecha_entrega) . $quien : 'Sin hora registrada' . $quien, true];
} elseif ($estado === 'pagado') {
    $recorrido[] = ['bolsa', 'Por retirar', 'El cliente aún no la retira', false];
} elseif ($cancelada) {
    $recorrido[] = ['x', 'Cancelada', '', true];
}
?>
<section class="contenido dv">
  <header class="cab">
    <div class="cab-txt"><small>Venta, <?= mb_strtolower($origen) === 'tienda online' ? 'online' : 'tienda física' ?></small><div class="titulo-atras"><a href="index.php?action=ventas" class="atras" aria-label="Volver a Ventas"><svg class="i"><use href="#i-volver"/></svg></a><h2><?= $e(numero_doc('venta', $venta)) ?></h2></div><p class="dv-fecha"><?= $e($fechaLarga) ?></p></div>
    <div class="cab-acc dv-chips">
      <?php if ($chipPago): ?><span class="dv-chip <?= $chipPago[0] ?>"><svg class="i"><use href="#i-<?= $chipPago[1] ?>"/></svg><?= $chipPago[2] ?></span><?php endif; ?>
      <?php if ($chipEstado): ?><span class="dv-chip <?= $chipEstado[0] ?>"><svg class="i"><use href="#i-<?= $chipEstado[1] ?>"/></svg><?= $chipEstado[2] ?></span><?php endif; ?>
    </div>
  </header>

  <section class="rc<?= $saldada || $cancelada ? '' : ' pend' ?>" aria-label="Cuenta de la venta">
    <div class="rc-lineas">
      <h3>Cuenta</h3>
      <div class="rc-linea"><span><?= count($detalles) === 1 ? '1 producto' : count($detalles) . ' productos' ?><?= $unidades != count($detalles) ? ' (' . (int) $unidades . ' unidades)' : '' ?></span><b><?= $usd($venta->total) ?></b></div>
      <div class="rc-linea total"><span>Total</span><span><?= $usd($venta->total) ?> · <?= $bsVenta($venta->total) ?></span></div>
      <?php foreach ($pagos as $p): $vf = $verif[ref('pago.estado', $p)] ?? ['pend', ref('pago.estado', $p)]; ?>
      <div class="rc-linea <?= $vf[0] ?>"><span><?= $e($p->metodo) ?><span class="est <?= $vf[0] ?>"><?= $vf[1] ?></span></span><b><?= ref('pago.estado', $p) === 'aprobado' ? '− ' : '' ?><?= $usd($enUsd($p)) ?></b></div>
      <?php endforeach; ?>
      <?php if (!$pagos): ?><div class="rc-linea"><span>Todavía no hay pagos registrados</span></div><?php endif; ?>
    </div>
    <div class="rc-saldo">
      <span>Saldo</span>
      <b><?= $usd($saldo) ?></b>
      <?php if ($cancelada): ?><small><svg class="i"><use href="#i-x"/></svg>Venta cancelada</small>
      <?php elseif ($saldada): ?><small><svg class="i"><use href="#i-check"/></svg>Venta saldada</small>
      <?php elseif ($porVerificar > 0): ?><small><svg class="i"><use href="#i-alerta"/></svg><?= $usd($porVerificar) ?> por verificar</small><a href="index.php?action=verificar_pagos" class="btn btn-pri">Revisar pago</a>
      <?php else: ?><small><svg class="i"><use href="#i-alerta"/></svg>Falta cobrar</small><?php endif; ?>
    </div>
  </section>

  <div class="dv-cuerpo">
    <div class="dv-col">
      <section class="dv-caja" aria-labelledby="dv-prod-t">
        <h3 id="dv-prod-t">Productos <span><?= count($detalles) ?></span></h3>
        <?php if ($detalles): ?>
        <ul class="dv-prods">
          <?php foreach ($detalles as $d): ?>
          <li class="dv-prod">
            <?php if (!empty($d->imagen)): ?><img src="public/uploads/<?= $e($d->imagen) ?>" alt="" width="56" height="56" loading="lazy"><?php else: ?><span class="dv-sin-foto"><svg class="i"><use href="#i-caja"/></svg></span><?php endif; ?>
            <div><b><?= $e($d->producto_nombre) ?></b><?php $varTxt = implode(' · ', array_filter([trim($d->talla ?? '') !== '' ? 'Talla ' . trim($d->talla) : '', trim($d->color ?? '')])); if ($varTxt !== ''): ?><small class="dv-var"><?= $e($varTxt) ?></small><?php endif; ?><small><?= (int) $d->cantidad ?> ×<?= $usd($d->precio_unitario) ?><?php if (!empty($d->descuento) && $d->descuento > 0): ?> · descuento <?= (float) $d->descuento ?>%<?php endif; ?></small></div>
            <span><?= $usd($d->subtotal) ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php else: ?><p class="vacio-filtro">Esta venta no tiene productos registrados.</p><?php endif; ?>
      </section>

      <section class="dv-caja" aria-labelledby="dv-pagos-t">
        <h3 id="dv-pagos-t">Pagos <span><?= count($pagos) ?></span><?php if (count($pagos) > 1): ?><small class="muted">Pago mixto</small><?php endif; ?></h3>
        <?php if (!$pagos): ?><p class="vacio-filtro">Todavía no hay pagos registrados.</p><?php endif; ?>
        <div class="dv-pagos">
          <?php foreach ($pagos as $p): $vf = $verif[ref('pago.estado', $p)] ?? ['pend', ref('pago.estado', $p)]; $tit = $titular($p); ?>
          <article class="dv-pago <?= $vf[0] ?>">
            <span class="dv-pago-ic"><svg class="i"><use href="#i-<?= $iconoMetodo($p->metodo) ?>"/></svg></span>
            <b><?= $e($p->metodo) ?></b>
            <span class="monto"><?= ($p->moneda ?? 'Bs') === 'USD' ? $usd($p->monto) : number_format((float) $p->monto, 2, ',', '.') . ' Bs' ?><?php if (($p->moneda ?? 'Bs') !== 'USD'): ?><small><?= $usd($enUsd($p)) ?></small><?php endif; ?></span>
            <dl>
              <?php if (!empty($p->banco) && $p->banco !== 'Caja Física'): ?><div><dt>Banco</dt><dd><?= $e($p->banco) ?></dd></div><?php endif; ?>
              <?php if (!empty($p->referencia) && $p->referencia !== 'No aplica'): ?><div><dt>Ref.</dt><dd><?= $e($p->referencia) ?></dd></div><?php endif; ?>
              <?php if ($tit): ?><div><dt>Titular</dt><dd><?= $e($tit) ?></dd></div><?php endif; ?>
            </dl>
            <div class="pie"><span class="est <?= $vf[0] ?>"><?= $vf[1] ?></span><?= !empty($p->verificado_por_nombre) ? 'por ' . $e($p->verificado_por_nombre) . ' · ' : '' ?><?= $corta($p->fecha_pago) ?></div>
          </article>
          <?php endforeach; ?>
        </div>
      </section>
    </div>

    <div class="dv-col">
      <section class="dv-caja" aria-labelledby="dv-cli-t">
        <h3 id="dv-cli-t">Cliente</h3>
        <div class="dv-cliente"><span class="dv-av" aria-hidden="true"><?= $e($iniciales($venta->nombre)) ?></span><b><?= $e($venta->nombre) ?></b><?php if (!empty($venta->correo)): ?><small><?= $e($venta->correo) ?></small><?php endif; ?></div>
        <dl class="dv-datos">
          <?php if (!empty($venta->cedula) && !es_vacio($venta->cedula)): ?>
          <div><dt>Cédula</dt><dd><?= $e($venta->cedula) ?></dd></div>
          <?php if (!empty($venta->telefono)): ?><div><dt>Teléfono</dt><dd><?= $e($venta->telefono) ?></dd></div><?php endif; ?>
          <?php if (!empty($venta->direccion)): ?><div><dt>Dirección</dt><dd><?= $e($venta->direccion) ?></dd></div><?php endif; ?>
          <?php endif; ?>
          <div><dt>Origen</dt><dd><?= $origen ?></dd></div>
          <?php if (!empty($venta->vendedor)): ?><div><dt>Atendió</dt><dd><?= $e($venta->vendedor) ?></dd></div><?php endif; ?>
        </dl>
      </section>

      <section class="dv-caja" aria-labelledby="dv-rec-t">
        <h3 id="dv-rec-t">Recorrido</h3>
        <ol class="dv-tiempo">
          <?php foreach ($recorrido as [$ic, $titulo, $detalle, $hecho]): ?>
          <li class="<?= $hecho ? '' : 'falta' ?>"><span class="punto"><svg class="i"><use href="#i-<?= $ic ?>"/></svg></span><div><b><?= $e($titulo) ?></b><?php if ($detalle): ?><small><?= $e($detalle) ?></small><?php endif; ?></div></li>
          <?php endforeach; ?>
        </ol>
      </section>
    </div>
  </div>
</section>
<?php include __DIR__ . '/admin/_cerrar.php'; ?>
