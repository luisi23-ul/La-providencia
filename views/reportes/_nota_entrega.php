<?php
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$num = fn($v) => number_format((float) $v, 2, ',', '.');
$sis = $sistema ?? null;
$empresa = [
    'nombre_empresa' => $sis->nombre_empresa ?? '',
    'razon_social'   => $sis->razon_social ?? '',
    'rif'            => $sis->rif ?? '',
    'direccion'      => $sis->direccion ?? '',
    'logo_path'      => $sis->logo_path ?? '',
];
$modo = $modo ?? 'imprimir';
$numero = numero_doc('venta', $venta);
$titulo = 'Nota de entrega';
$subtitulo = 'N.º ' . $numero . ' · ' . $fechaLarga;

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
$recibe = in_array($venta->estado ?? '', ['retirado', 'entregado', 'completado'], true) ? trim((string) ($venta->retirado_por ?? '')) : '';

include __DIR__ . '/_hoja_abrir.php';
?>

  <section aria-label="Datos de la venta">
    <table class="ficha" role="presentation"><tr>
      <td><span>Cliente</span><b><?= $h($cliente) ?></b><small><?= $contacto !== '' ? $h($contacto) : '&nbsp;' ?></small></td>
      <td><span>Venta</span><b><?= $h($origen) ?></b><small><?= !empty($venta->vendedor) ? 'Atendió ' . $h($venta->vendedor) : '&nbsp;' ?></small></td>
      <td><span>Estado</span><b><?= $h($estadoTxt) ?></b><small><?= $entrega !== '' ? $h($entrega) . ($recibe !== '' ? ' por ' . $h($recibe) : '') : '&nbsp;' ?></small></td>
      <td><span>Tasa de la venta</span><b><?= $num($tasaVenta) ?> Bs</b><small>por cada $</small></td>
    </tr></table>
  </section>

  <section aria-labelledby="t-productos">
    <h2 class="bloque" id="t-productos">Productos</h2>
    <table class="datos lineas">
      <colgroup><col><col class="c-det"><col class="c-cant"><col class="c-mon"><col class="c-mon"></colgroup>
      <thead>
        <tr class="cols"><th scope="col">Producto</th><th scope="col">Detalles</th><th scope="col" class="num">Cant.</th><th scope="col" class="num">Precio $</th><th scope="col" class="num">Subtotal $</th></tr>
      </thead>
      <tbody>
        <?php foreach ($detalles as $d):
          $det = array_filter([
              trim((string) ($d->talla ?? '')) !== '' ? 'Talla ' . mb_strtoupper(trim((string) $d->talla)) : '',
              trim((string) ($d->color ?? '')),
              trim((string) ($d->modelo ?? '')),
          ]); ?>
        <tr>
          <td class="prod"><?= $h($d->producto_nombre) ?><?php if ($det): ?><small class="det-movil"><?= $h(implode(' · ', $det)) ?></small><?php endif; ?></td>
          <td class="det"><?= $det ? $h(implode(' · ', $det)) : '' ?></td>
          <td class="num"><?= (int) $d->cantidad ?></td>
          <td class="num"><?= $num($d->precio_unitario) ?></td>
          <td class="num"><?= $num($d->subtotal) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <table class="cuenta">
      <thead><tr><td></td><th scope="col">$</th><th scope="col">Bs</th></tr></thead>
      <tbody>
        <tr><th scope="row">Subtotal · <?= (int) $unidades ?> <?= (int) $unidades === 1 ? 'unidad' : 'unidades' ?></th><td><?= $num($sumaLineas) ?></td><td><?= $num($sumaLineas * $tasaVenta) ?></td></tr>
        <?php if ($descuento > 0.004): ?><tr><th scope="row">Descuento</th><td>− <?= $num($descuento) ?></td><td>− <?= $num($descuento * $tasaVenta) ?></td></tr><?php endif; ?>
        <tr class="grande"><th scope="row">Total</th><td><?= $num($venta->total) ?></td><td><?= $num((float) $venta->total * $tasaVenta) ?></td></tr>
      </tbody>
    </table>
  </section>

  <?php if ($pagos): ?>
  <section aria-labelledby="t-pagos">
    <h2 class="bloque" id="t-pagos">Pagos</h2>
    <table class="datos lineas pagos">
      <colgroup><col><col class="c-banco"><col class="c-ref"><col class="c-fecha"><col class="c-est"><col class="c-pusd"><col class="c-pbs"></colgroup>
      <thead>
        <tr class="cols"><th scope="col">Método</th><th scope="col">Banco</th><th scope="col">Referencia</th><th scope="col">Fecha</th><th scope="col">Estado</th><th scope="col" class="num">Monto $</th><th scope="col" class="num">Monto Bs</th></tr>
      </thead>
      <tbody>
        <?php foreach ($pagos as $p): $vf = $verif[ref('pago.estado', $p)] ?? ['pend', ref('pago.estado', $p)];
          $banco = trim((string) ($p->banco ?? ''));
          $ref = trim((string) ($p->referencia ?? ''));
          $montoBs = ($p->moneda ?? 'Bs') === 'USD' ? (float) $p->monto * $tasaVenta : (float) $p->monto; ?>
        <tr>
          <td class="prod"><?= $h($p->metodo) ?><?php if ($banco !== '' || $ref !== ''): ?><small class="det-movil"><?= $h(implode(' · ', array_filter([$banco, $ref !== '' ? 'Ref. ' . $ref : '']))) ?></small><?php endif; ?></td>
          <td class="det"><?= $h($banco) ?></td>
          <td class="det"><?= $h($ref) ?></td>
          <td class="fecha"><?= !empty($p->fecha_pago) ? date('d/m/Y', strtotime($p->fecha_pago)) : '' ?></td>
          <td><span class="est-pago <?= $h($vf[0]) ?>"><?= $h($vf[1]) ?></span></td>
          <td class="num"><?= $num($enUsd($p)) ?></td>
          <td class="num"><?= $num($montoBs) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <table class="cuenta">
      <thead><tr><td></td><th scope="col">$</th><th scope="col">Bs</th></tr></thead>
      <tbody>
        <tr><th scope="row">Pagado</th><td><?= $num($aprobado) ?></td><td><?= $num($aprobado * $tasaVenta) ?></td></tr>
        <?php if ($porVerificar > 0.004): ?><tr><th scope="row">Por verificar</th><td><?= $num($porVerificar) ?></td><td><?= $num($porVerificar * $tasaVenta) ?></td></tr><?php endif; ?>
        <tr class="saldo <?= $saldada ? 'ok' : 'pend' ?>"><th scope="row">Saldo</th><td><?= $num($saldo) ?></td><td><?= $num($saldo * $tasaVenta) ?></td></tr>
      </tbody>
    </table>
  </section>
  <?php endif; ?>

  <table class="firmas" role="presentation"><tr>
    <td><div class="linea"></div><b>Entregado por</b><?= !empty($venta->vendedor) ? $h($venta->vendedor) : 'Nombre y firma' ?></td>
    <td><div class="linea"></div><b>Recibido por</b><?= $recibe !== '' ? $h($recibe) : 'Nombre, cédula y firma' ?></td>
  </tr></table>
  <p class="aclara">Este documento no es una factura fiscal.</p>
<?php include __DIR__ . '/_hoja_cerrar.php'; ?>
