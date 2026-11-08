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
    </tr></table>
  </section>

  <section aria-labelledby="t-productos">
    <h2 class="bloque" id="t-productos">Productos</h2>
    <table class="datos auto">
      <tbody>
        <tr class="cols"><th scope="col">Producto</th><th scope="col">Talla y color</th><th scope="col" class="num">Cant.</th><th scope="col" class="num">Precio unit. $</th><th scope="col" class="num">Subtotal $</th></tr>
        <?php foreach ($detalles as $d): ?>
        <tr>
          <td><?= $h($d->producto_nombre) ?></td>
          <td><?= $h(implode(' · ', array_filter([trim((string) ($d->talla ?? '')), trim((string) ($d->color ?? ''))]))) ?></td>
          <td class="num"><?= (int) $d->cantidad ?></td>
          <td class="num"><?= $num($d->precio_unitario) ?></td>
          <td class="num"><?= $num($d->subtotal) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <table class="cuenta" role="presentation">
      <tr><td>Subtotal (<?= (int) $unidades ?> <?= (int) $unidades === 1 ? 'unidad' : 'unidades' ?>)</td><td>$ <?= $num($sumaLineas) ?></td></tr>
      <?php if ($descuento > 0.004): ?><tr><td>Descuento</td><td>− $ <?= $num($descuento) ?></td></tr><?php endif; ?>
      <tr class="grande"><td>Total</td><td>$ <?= $num($venta->total) ?></td></tr>
      <tr class="bs"><td></td><td>Bs <?= $num((float) $venta->total * $tasaVenta) ?></td></tr>
      <tr class="tasa"><td>Tasa usada</td><td><?= $num($tasaVenta) ?> Bs por $</td></tr>
    </table>
  </section>

  <?php if ($pagos): ?>
  <section aria-labelledby="t-pagos">
    <h2 class="bloque" id="t-pagos">Pagos</h2>
    <table class="datos auto">
      <tbody>
        <tr class="cols"><th scope="col">Método</th><th scope="col">Banco y referencia</th><th scope="col">Fecha</th><th scope="col">Estado</th><th scope="col" class="num">Monto $</th></tr>
        <?php foreach ($pagos as $p): $vf = $verif[ref('pago.estado', $p)] ?? ['pend', ref('pago.estado', $p)]; ?>
        <tr>
          <td><?= $h($p->metodo) ?></td>
          <td><?= $h(implode(' · ', array_filter([trim((string) ($p->banco ?? '')), trim((string) ($p->referencia ?? ''))]))) ?></td>
          <td><?= !empty($p->fecha_pago) ? date('d/m/Y', strtotime($p->fecha_pago)) : '' ?></td>
          <td><span class="est-pago <?= $h($vf[0]) ?>"><?= $h($vf[1]) ?></span></td>
          <td class="num"><?= $num($enUsd($p)) ?><?php if (($p->moneda ?? 'Bs') !== 'USD'): ?><br><small>Bs <?= $num($p->monto) ?></small><?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <table class="cuenta" role="presentation">
      <tr><td>Pagado</td><td>$ <?= $num($aprobado) ?></td></tr>
      <?php if ($porVerificar > 0.004): ?><tr><td>Por verificar</td><td>$ <?= $num($porVerificar) ?></td></tr><?php endif; ?>
      <tr class="saldo <?= $saldada ? 'ok' : 'pend' ?>"><td>Saldo</td><td>$ <?= $num($saldo) ?></td></tr>
    </table>
  </section>
  <?php endif; ?>

  <table class="firmas" role="presentation"><tr>
    <td><div class="linea"></div><b>Entregado por</b><?= !empty($venta->vendedor) ? $h($venta->vendedor) : 'Nombre y firma' ?></td>
    <td><div class="linea"></div><b>Recibido por</b><?= $recibe !== '' ? $h($recibe) : 'Nombre, cédula y firma' ?></td>
  </tr></table>
  <p class="aclara">Este documento no es una factura fiscal.</p>
<?php include __DIR__ . '/_hoja_cerrar.php'; ?>
