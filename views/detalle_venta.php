<?php
$menu = 'ventas';
$tituloPagina = 'Detalle de venta';
$detalles = $detalles ?? $productos ?? [];
$pagos = $pagos ?? [];
$sinPanel = ($_GET['vista'] ?? '') === 'nota';
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
if ($saldo < 0.005) $saldo = 0;
$conTasa = array_filter($pagos, fn($p) => (float) ($p->tasa_cambio ?? 0) > 0);
usort($conTasa, fn($x, $y) => strcmp((string) $x->fecha_pago, (string) $y->fecha_pago));
$tasaVenta = $conTasa ? (float) reset($conTasa)->tasa_cambio : (float) $tasa;
$saldada = $saldo < 0.005;
$unidades = array_sum(array_map(fn($d) => $d->cantidad, $detalles));
$verif = ['aprobado' => ['ok', 'Aprobado'], 'pendiente' => ['pend', 'Por verificar'], 'rechazado' => ['agot', 'Rechazado']];
$origen = ($venta->origen_venta ?? 'online') === 'online' ? 'Tienda online' : 'Tienda física';

$dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$t = strtotime($venta->fecha);
$fechaLarga = ucfirst($dias[date('w', $t)]) . ' ' . date('j', $t) . ' de ' . $meses[date('n', $t) - 1] . ' de ' . date('Y', $t) . ', ' . date('g:i a', $t);
$corta = fn($f) => $f ? date('d/m, g:i a', strtotime($f)) : '';

$sumaLineas = array_sum(array_map(fn($d) => (float) $d->subtotal, $detalles));
$descuento = max(0, $sumaLineas - (float) $venta->total);
$cliente = trim((string) ($venta->nombre ?? '')) ?: 'Consumidor final';
$contacto = implode(' · ', array_filter([trim((string) ($venta->cedula ?? '')), trim((string) ($venta->telefono ?? ''))], fn($v) => $v !== '' && !preg_match('/^sin_/i', $v)));
$estadoTxt = $cancelada ? 'Anulada' : ($saldada ? 'Pagada' : ($porVerificar > 0 ? 'Pago por verificar' : 'Pendiente de pago'));
$entrega = [
    'pagado' => 'Por retirar',
    'retirado' => 'Retirada',
    'entregado' => 'Entregada',
    'completado' => 'Completada',
][$venta->estado ?? ''] ?? '';

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
if ($sinPanel) {
    include __DIR__ . '/reportes/_nota_entrega.php';
    return;
}

$num = fn($v) => number_format((float) $v, 2, ',', '.');
$conSigno = fn($v) => ($v < 0 ? '− ' : '') . $num(abs($v));
$claseEstado = $cancelada ? 'agot' : ($saldada ? 'ok' : 'pend');
$pendientes = array_values(array_filter($pagos, fn($p) => ref('pago.estado', $p) === 'pendiente'));
$queFalta = function () use ($pendientes, $iconoMetodo) {
    if (count($pendientes) > 1) return count($pendientes) . ' pagos';
    $ic = $iconoMetodo($pendientes[0]->metodo ?? '');
    if ($ic === 'movil') return 'el pago móvil';
    if ($ic === 'banco') return 'la transferencia';
    return 'el pago con ' . mb_strtolower((string) ($pendientes[0]->metodo ?? ''));
};
if ($cancelada) $aviso = ['x', 'Venta anulada', false];
elseif ($saldada) $aviso = ['check', 'Venta saldada', false];
elseif ($pendientes) $aviso = [count($pendientes) === 1 ? $iconoMetodo($pendientes[0]->metodo) : 'alerta', 'Falta aprobar ' . $queFalta(), true];
else $aviso = ['alerta', 'Falta cobrar', false];

$cifras = [['Subtotal', $sumaLineas, '']];
if ($descuento > 0.004) $cifras[] = ['Descuento', -$descuento, 'cu-desc'];
$cifras[] = ['Total', (float) $venta->total, 'cu-tot'];
$cifras[] = ['Pagado', $aprobado, 'cu-pag'];
if ($porVerificar > 0.004) $cifras[] = ['Por verificar', $porVerificar, 'cu-ver'];
$cifras[] = ['Saldo', $saldo, 'cu-saldo'];
?>
<section class="contenido dv dn">
  <header class="cab">
    <div class="cab-txt"><small>Venta</small><div class="titulo-atras"><a href="index.php?action=ventas" class="atras" aria-label="Volver a Ventas"><svg class="i"><use href="#i-volver"/></svg></a><h2><?= $e(numero_doc('venta', $venta)) ?></h2></div><p class="dv-fecha"><?= $e($fechaLarga) ?></p></div>
    <div class="cab-acc dv-chips">
      <div class="dv-botones">
        <a href="index.php?action=cliente_detalle&amp;<?= url_valor('cliente', !empty($venta->cedula) ? $venta->cedula : 'SIN_USUARIO') ?>" class="btn btn-sec btn-ir"><svg class="i"><use href="#i-historial"/></svg>Compras del cliente</a>
        <a href="index.php?action=detalle_venta&amp;<?= url_clave('venta', $venta) ?>&amp;vista=nota&amp;ver=1" target="_blank" class="btn btn-sec btn-rep ver"><svg class="i"><use href="#i-ojo"/></svg>Ver</a>
        <a href="index.php?action=detalle_venta&amp;<?= url_clave('venta', $venta) ?>&amp;vista=nota" target="_blank" class="btn btn-sec btn-rep pdf"><svg class="i"><use href="#i-pdf"/></svg>Nota de entrega</a>
      </div>
    </div>
  </header>

  <ol class="dn-pasos" aria-label="Recorrido de la venta" style="--pasos: <?= max(1, count($recorrido) - 1) ?>">
    <?php foreach ($recorrido as [$ic, $titulo, $detalle, $hecho]): ?>
    <li class="<?= $hecho ? '' : 'falta' ?>"><span class="punto"><svg class="i"><use href="#i-<?= $ic ?>"/></svg></span><div><b><?= $e($titulo) ?></b><?php if ($detalle): ?><small><?= $e($detalle) ?></small><?php endif; ?></div></li>
    <?php endforeach; ?>
  </ol>

  <article class="dn-hoja" aria-label="Nota de entrega <?= $e(numero_doc('venta', $venta)) ?>">
    <dl class="dn-membrete">
      <div><dt>Cliente</dt><dd><?= $e($cliente) ?><small><?= $contacto !== '' ? $e($contacto) : 'Sin datos del cliente' ?></small></dd></div>
      <div><dt>Venta</dt><dd><?= $origen ?><small><?= !empty($venta->vendedor) ? 'Atendió ' . $e($venta->vendedor) : 'Compra en línea' ?></small></dd></div>
      <div><dt>Estado</dt><dd class="dn-estado <?= $claseEstado ?>"><?= $estadoTxt ?><?php if ($entrega !== ''): ?><small><?= $entrega ?></small><?php endif; ?></dd></div>
      <div><dt>Tasa de la venta</dt><dd><?= $num($tasaVenta) ?> Bs<small>por cada $</small></dd></div>
    </dl>

    <section aria-labelledby="dn-prod-t">
      <h3 id="dn-prod-t" class="dn-tit">Productos <span><?= (int) $unidades ?> <?= (int) $unidades === 1 ? 'unidad' : 'unidades' ?></span></h3>
      <?php if ($detalles): ?>
      <table class="dn-tabla dn-prods">
        <thead><tr><th scope="col" class="izq">Producto</th><th scope="col" class="izq">Detalles</th><th scope="col" class="num">Cant.</th><th scope="col" class="num">Precio $</th><th scope="col" class="num">Subtotal $</th></tr></thead>
        <tbody>
          <?php foreach ($detalles as $d): $det = implode(' · ', array_filter([trim($d->talla ?? '') !== '' ? 'Talla ' . trim($d->talla) : '', trim($d->color ?? ''), !empty($d->descuento) && $d->descuento > 0 ? 'Descuento ' . (float) $d->descuento . '%' : ''])); ?>
          <tr>
            <td class="dn-prod" data-c="Producto"><?php if (!empty($d->imagen)): ?><img src="public/uploads/<?= $e($d->imagen) ?>" alt="" width="44" height="44" loading="lazy"><?php else: ?><span class="dn-sinfoto"><svg class="i"><use href="#i-caja"/></svg></span><?php endif; ?><b><?= $e($d->producto_nombre) ?></b></td>
            <td class="dn-det" data-c="Detalles"><?= $det !== '' ? $e($det) : '<span class="dn-nada">Sin talla ni color</span>' ?></td>
            <td class="num" data-c="Cant."><?= (int) $d->cantidad ?></td>
            <td class="num" data-c="Precio $"><?= $num($d->precio_unitario) ?></td>
            <td class="num fuerte" data-c="Subtotal $"><?= $num($d->subtotal) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?><p class="vacio-filtro">Esta venta no tiene productos registrados.</p><?php endif; ?>
    </section>

    <section aria-labelledby="dn-pag-t">
      <h3 id="dn-pag-t" class="dn-tit">Pagos <span><?= count($pagos) === 1 ? '1 pago' : count($pagos) . ' pagos' ?></span></h3>
      <?php if ($pagos): ?>
      <table class="dn-tabla dn-pagos">
        <thead><tr><th scope="col" class="izq">Método</th><th scope="col" class="izq">Banco y referencia</th><th scope="col" class="izq">Fecha</th><th scope="col" class="izq">Estado</th><th scope="col" class="num">Monto $</th><th scope="col" class="num">Monto Bs</th></tr></thead>
        <tbody>
          <?php foreach ($pagos as $p): $vf = $verif[ref('pago.estado', $p)] ?? ['pend', ref('pago.estado', $p)]; $tit = $titular($p); $banco = !empty($p->banco) && $p->banco !== 'Caja Física' ? $p->banco : ''; $refPago = !empty($p->referencia) && $p->referencia !== 'No aplica' ? $p->referencia : ''; ?>
          <tr>
            <td class="dn-metodo" data-c="Método"><span class="dn-ic <?= $vf[0] ?>"><svg class="i"><use href="#i-<?= $iconoMetodo($p->metodo) ?>"/></svg></span><b><?= $e($p->metodo) ?></b></td>
            <td class="dn-det" data-c="Banco y ref."><?php if ($banco !== '' || $refPago !== ''): ?><?= $e(implode(' · ', array_filter([$banco, $refPago !== '' ? 'Ref. ' . $refPago : '']))) ?><?php if ($tit): ?><small>Titular: <?= $e($tit) ?></small><?php endif; ?><?php else: ?><span class="dn-nada">En caja</span><?php endif; ?></td>
            <td data-c="Fecha"><?= $p->fecha_pago ? date('d/m/Y', strtotime($p->fecha_pago)) : '' ?></td>
            <td data-c="Estado"><span class="est <?= $vf[0] ?>"><?= $e($vf[1]) ?></span></td>
            <td class="num fuerte" data-c="Monto $"><?= $num($enUsd($p)) ?></td>
            <td class="num" data-c="Monto Bs"><?= $num(($p->moneda ?? 'Bs') === 'USD' ? (float) $p->monto * $tasaVenta : (float) $p->monto) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?><p class="vacio-filtro">Todavía no hay pagos registrados.</p><?php endif; ?>
    </section>

    <section class="dn-cu <?= $claseEstado ?>" aria-labelledby="dn-cu-t">
      <h3 id="dn-cu-t" class="dn-tit">Cuenta <span>a la tasa de la venta, <?= $num($tasaVenta) ?> Bs por $</span></h3>
      <table class="dn-cu-libro">
        <thead><tr><td></td><?php foreach ($cifras as [$txt, , $cl]): ?><th scope="col" class="<?= $cl ?>"><?= $txt ?></th><?php endforeach; ?></tr></thead>
        <tbody>
          <tr><th scope="row">Dólares</th><?php foreach ($cifras as [, $v, $cl]): ?><td class="<?= $cl ?>"><?= ($v < 0 ? '− ' : '') . '$' . $num(abs($v)) ?></td><?php endforeach; ?></tr>
          <tr><th scope="row">Bolívares</th><?php foreach ($cifras as [, $v, $cl]): ?><td class="<?= $cl ?>"><?= $conSigno($v * $tasaVenta) ?></td><?php endforeach; ?></tr>
        </tbody>
      </table>
      <table class="dn-cu-tabla">
        <thead><tr><th scope="col"><span class="dn-oculto">Concepto</span></th><th scope="col">Dólares</th><th scope="col">Bolívares</th></tr></thead>
        <tbody>
          <?php foreach (array_slice($cifras, 0, -1) as [$txt, $v, $cl]): ?>
          <tr class="<?= $cl ?>"><th scope="row"><?= $txt ?></th><td><?= ($v < 0 ? '− ' : '') . '$' . $num(abs($v)) ?></td><td><?= $conSigno($v * $tasaVenta) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot><tr class="cu-saldo"><th scope="row">Saldo</th><td>$<?= $num($saldo) ?></td><td><?= $num($saldo * $tasaVenta) ?></td></tr></tfoot>
      </table>
      <div class="dn-aviso" role="status"><span class="dn-aviso-ic"><svg class="i"><use href="#i-<?= $aviso[0] ?>"/></svg></span><p><?= $e($aviso[1]) ?></p><?php if ($aviso[2]): ?><a href="index.php?action=verificar_pagos" class="btn btn-pri"><svg class="i"><use href="#i-ojo"/></svg>Revisar pago</a><?php endif; ?></div>
    </section>
  </article>
</section>
<?php include __DIR__ . '/admin/_cerrar.php'; ?>
