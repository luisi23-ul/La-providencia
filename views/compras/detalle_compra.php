<?php
$menu = 'compras';
$tituloPagina = 'Detalle de compra';
$detalles = $detalles ?? [];
include __DIR__ . '/../admin/_abrir.php';
$numDoc = numero_doc('compra', $compra);
$anulada = $compra->estado === 'anulado';
$unidades = array_sum(array_map(fn($d) => (int) $d->cantidad, $detalles));
$costo = (float) $compra->total;
$conPrecio = array_filter($detalles, fn($d) => (float) $d->precio_venta > 0);
$sinPrecio = count($detalles) - count($conPrecio);
$valorVenta = array_sum(array_map(fn($d) => (float) $d->precio_venta * (int) $d->cantidad, $conPrecio));
$costoConPrecio = array_sum(array_map(fn($d) => (float) $d->costo_unitario * (int) $d->cantidad, $conPrecio));
$ganancia = $valorVenta - $costoConPrecio;
if ($anulada) $ganancia = 0.0;
$margen = $costoConPrecio > 0 ? $ganancia / $costoConPrecio * 100 : 0;
$num = fn($v) => number_format((float) $v, 2, ',', '.');
$signo = fn($v, $pre = '') => ($v < 0 ? '-' : ($v > 0 ? '+' : '')) . $pre . number_format(abs((float) $v), 2, ',', '.');
$pct = rtrim(rtrim(number_format($margen, 1, ',', '.'), '0'), ',') . ' %';
$dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$t = strtotime($compra->fecha_compra);
$conHora = date('H:i', $t) !== '00:00';
$fechaLarga = ucfirst($dias[date('w', $t)]) . ' ' . date('j', $t) . ' de ' . $meses[date('n', $t) - 1] . ' de ' . date('Y', $t) . ($conHora ? ', ' . date('g:i a', $t) : '');
$corta = date('d/m', $t) . ($conHora ? ', ' . date('g:i a', $t) : '');
$metodo = mb_strtolower($compra->metodo_pago ?? '');
$icoPago = str_contains($metodo, 'móvil') || str_contains($metodo, 'movil') ? 'movil' : (str_contains($metodo, 'transfer') ? 'banco' : (str_contains($metodo, 'efectivo') ? 'efectivo' : 'tarjeta'));
$banco = trim((string) $compra->banco);
$refPago = trim((string) $compra->referencia_pago);
$clase = $anulada ? 'agot' : 'ok';
$pasos = [
    ['recibo', 'Compra registrada', $corta . ' · ' . ($compra->usuario ?? 'Admin'), ''],
    [$icoPago, 'Pagada al proveedor', $compra->metodo_pago, ''],
    $anulada ? ['x', 'Anulada', 'Unidades restadas del stock', 'dcn-anulada'] : ['caja', 'Recibida', $unidades . ($unidades === 1 ? ' unidad' : ' unidades') . ' en inventario', ''],
];
$cifras = [['Total pagado', $costo, 'cu-tot'], ['Valor de venta', $conPrecio ? $valorVenta : null, '']];
$claseCu = $anulada ? 'agot' : ($conPrecio && $ganancia >= 0 ? 'ok' : 'pend');
$avisoCu = match (true) {
    $anulada => 'Compra anulada: no deja ganancia',
    !$conPrecio => 'Sin precio de venta: no se puede estimar la ganancia',
    default => 'Ganancia del ' . $pct . ' sobre el costo' . ($sinPrecio ? ($sinPrecio === 1 ? ' (sin contar 1 producto sin precio)' : " (sin contar $sinPrecio productos sin precio)") : ''),
};
$datos = array_filter([
    'Teléfono' => trim((string) $compra->telefono),
    'Dirección' => trim((string) $compra->direccion),
    'Comprado por' => trim((string) $compra->comprado_por),
]);
?>
<section class="contenido dv dn">
  <header class="cab">
    <div class="cab-txt"><small>Compra</small><div class="titulo-atras"><a href="index.php?action=compras" class="atras" aria-label="Volver a Compras"><svg class="i"><use href="#i-volver"/></svg></a><h2>Fact. <?= $e($compra->numero_factura) ?></h2></div><p class="dv-fecha"><?= $e($fechaLarga) ?></p></div>
    <div class="cab-acc dv-chips">
      <div class="dv-botones">
        <?php if (!$anulada): ?>
        <form method="POST" action="index.php?action=anular_compra" class="dc-anular">
          <?= ocultos('compra', $compra) ?>
          <button type="submit" class="btn btn-peligro" data-confirmar="¿Anular la compra <?= $e($numDoc) ?>?" data-confirmar-texto="Se restarán <?= $unidades ?> unidades del stock y quedará registrado en Movimientos. La compra no se borra: queda en el historial como anulada." data-confirmar-boton="Anular compra"><svg class="i"><use href="#i-x"/></svg>Anular compra</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <ol class="dn-pasos" aria-label="Recorrido de la compra" style="--pasos: <?= count($pasos) - 1 ?>">
    <?php foreach ($pasos as [$ic, $titulo, $detalle, $cl]): ?>
    <li class="<?= $cl ?>"><span class="punto"><svg class="i"><use href="#i-<?= $ic ?>"/></svg></span><div><b><?= $e($titulo) ?></b><?php if ($detalle): ?><small><?= $e($detalle) ?></small><?php endif; ?></div></li>
    <?php endforeach; ?>
  </ol>

  <article class="dn-hoja" aria-label="Compra <?= $e($numDoc) ?>">
    <div class="dcn-enc">
      <section aria-labelledby="dcn-prov-t">
        <h3 id="dcn-prov-t" class="dcn-enc-t"><svg class="i" aria-hidden="true"><use href="#i-camion"/></svg>Datos del proveedor</h3>
        <dl>
          <div><dt>Proveedor</dt><dd><b><?= $e($compra->razon_social) ?></b></dd></div>
          <div><dt>RIF</dt><dd><?= $e(ref('proveedor.rif', $compra)) ?></dd></div>
          <div><dt>Teléfono</dt><dd><?= $datos['Teléfono'] ?? '' ? $e($datos['Teléfono']) : '<span class="dn-nada">Sin teléfono</span>' ?></dd></div>
          <div><dt>Dirección</dt><dd><?= $datos['Dirección'] ?? '' ? $e($datos['Dirección']) : '<span class="dn-nada">Sin dirección</span>' ?></dd></div>
        </dl>
      </section>
      <section aria-labelledby="dcn-comp-t">
        <h3 id="dcn-comp-t" class="dcn-enc-t"><svg class="i" aria-hidden="true"><use href="#i-recibo"/></svg>Datos de la compra</h3>
        <dl>
          <div><dt>Estado</dt><dd><b class="dcn-<?= $clase ?>"><?= $anulada ? 'Anulada' : 'Recibida' ?></b> · <?= $unidades ?> <?= $unidades === 1 ? 'unidad' : 'unidades' ?></dd></div>
          <div><dt>Pago</dt><dd><?= $e(implode(' · ', array_filter([$compra->metodo_pago, $banco]))) ?></dd></div>
          <div><dt>Tasa del día</dt><dd><?= $num($tasa) ?> Bs por cada $</dd></div>
          <div><dt>Comprado por</dt><dd><?= $datos['Comprado por'] ?? '' ? $e($datos['Comprado por']) : '<span class="dn-nada">No indicado</span>' ?></dd></div>
        </dl>
      </section>
    </div>

    <section aria-labelledby="dn-prod-t">
      <h3 id="dn-prod-t" class="dn-tit">Productos <span><?= count($detalles) === 1 ? '1 producto' : count($detalles) . ' productos' ?></span></h3>
      <?php if ($detalles): ?>
      <table class="dn-tabla dn-prods dcn-prods">
        <thead><tr><th scope="col" class="izq">Producto</th><th scope="col" class="izq">Detalles</th><th scope="col" class="num">Cant.</th><th scope="col" class="num">Costo $</th><th scope="col" class="num">Precio venta $</th><th scope="col" class="num">Subtotal $</th></tr></thead>
        <tbody>
          <?php foreach ($detalles as $d): ?>
          <tr>
            <td class="dn-prod" data-c="Producto"><?php if (!empty($d->imagen)): ?><img src="public/uploads/<?= $e($d->imagen) ?>" alt="" width="44" height="44" loading="lazy"><?php else: ?><span class="dn-sinfoto"><svg class="i"><use href="#i-caja"/></svg></span><?php endif; ?><span class="dcn-nom"><b><?= $e($d->nombre_producto) ?></b><small><?= $e($d->codigo) ?></small></span></td>
            <td class="dn-det" data-c="Detalles"><span class="dn-nada">Sin talla ni color</span></td>
            <td class="num" data-c="Cant."><?= (int) $d->cantidad ?></td>
            <td class="num" data-c="Costo $"><?= $num($d->costo_unitario) ?></td>
            <td class="num" data-c="Precio venta $"><?= (float) $d->precio_venta > 0 ? $num($d->precio_venta) : 'Sin precio' ?></td>
            <td class="num fuerte" data-c="Subtotal $"><?= $num($d->subtotal) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?><p class="vacio-filtro">Esta compra no tiene productos registrados.</p><?php endif; ?>
    </section>

    <section aria-labelledby="dn-pag-t">
      <h3 id="dn-pag-t" class="dn-tit">Pago al proveedor</h3>
      <table class="dn-tabla dcn-pagos">
        <thead><tr><th scope="col" class="izq">Método</th><th scope="col" class="izq">Banco y referencia</th><th scope="col" class="izq">Fecha</th><th scope="col" class="num">Monto $</th><th scope="col" class="num">Monto Bs</th></tr></thead>
        <tbody>
          <tr>
            <td class="dn-metodo" data-c="Método"><span class="dn-ic <?= $clase ?>"><svg class="i"><use href="#i-<?= $icoPago ?>"/></svg></span><b><?= $e($compra->metodo_pago) ?></b></td>
            <td class="dn-det" data-c="Banco y ref."><?php if ($banco !== '' || $refPago !== ''): ?><span class="dcn-ref"><?= $e(implode(' · ', array_filter([$banco, $refPago !== '' ? 'Ref. ' . $refPago : '']))) ?><?php if ($refPago !== ''): ?><button type="button" class="vf-copiar" data-tip="Copiar" data-copiar="<?= $e($refPago) ?>" aria-label="Copiar referencia <?= $e($refPago) ?>"><svg class="i"><use href="#i-copiar"/></svg></button><?php endif; ?></span><?php else: ?><span class="dn-nada">Sin banco ni referencia</span><?php endif; ?></td>
            <td data-c="Fecha"><?= date('d/m/Y', $t) ?></td>
            <td class="num fuerte" data-c="Monto $"><?= $num($costo) ?></td>
            <td class="num" data-c="Monto Bs"><?= $num($costo * $tasa) ?></td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="dn-cu <?= $claseCu ?>" aria-labelledby="dn-cu-t">
      <h3 id="dn-cu-t" class="dn-tit">Cuenta <span>a <?= $num($tasa) ?> Bs por $</span></h3>
      <table class="dn-cu-libro">
        <thead><tr><td></td><?php foreach ($cifras as [$txt, , $cl]): ?><th scope="col" class="<?= $cl ?>"><?= $txt ?></th><?php endforeach; ?><th scope="col" class="cu-saldo">Ganancia estimada</th></tr></thead>
        <tbody>
          <tr><th scope="row">Dólares</th><?php foreach ($cifras as [, $v, $cl]): ?><td class="<?= $cl ?>"><?= $v === null ? 'Sin precio' : '$' . $num($v) ?></td><?php endforeach; ?><td class="cu-saldo"><?= $conPrecio || $anulada ? $signo($ganancia, '$') : 'Sin precio' ?></td></tr>
          <tr><th scope="row">Bolívares</th><?php foreach ($cifras as [, $v, $cl]): ?><td class="<?= $cl ?>"><?= $v === null ? 'Sin precio' : $num($v * $tasa) ?></td><?php endforeach; ?><td class="cu-saldo"><?= $conPrecio || $anulada ? $signo($ganancia * $tasa) : 'Sin precio' ?></td></tr>
        </tbody>
      </table>
      <table class="dn-cu-tabla">
        <thead><tr><th scope="col"><span class="dn-oculto">Concepto</span></th><th scope="col">Dólares</th><th scope="col">Bolívares</th></tr></thead>
        <tbody>
          <?php foreach ($cifras as [$txt, $v, $cl]): ?>
          <tr class="<?= $cl ?>"><th scope="row"><?= $txt ?></th><?php if ($v === null): ?><td>Sin precio</td><td>Sin precio</td><?php else: ?><td>$<?= $num($v) ?></td><td><?= $num($v * $tasa) ?></td><?php endif; ?></tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot><tr class="cu-saldo"><th scope="row">Ganancia estimada</th><?php if ($conPrecio || $anulada): ?><td><?= $signo($ganancia, '$') ?></td><td><?= $signo($ganancia * $tasa) ?></td><?php else: ?><td>Sin precio</td><td>Sin precio</td><?php endif; ?></tr></tfoot>
      </table>
      <div class="dn-aviso" role="status"><span class="dn-aviso-ic"><svg class="i"><use href="#i-<?= $anulada ? 'x' : ($claseCu === 'ok' ? 'check' : 'alerta') ?>"/></svg></span><p><?= $e($avisoCu) ?></p></div>
    </section>
  </article>
</section>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>
