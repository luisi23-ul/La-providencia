<?php
include __DIR__ . '/tienda/_comun.php';
$pedidos = $pedidos ?? [];
$nombreCliente = $_SESSION['nombre'] ?? '';
$estados = [
    'pendiente' => ['Por confirmar', 'ti-clock', 'Te escribiremos por WhatsApp para confirmar disponibilidad y pago.', 1],
    'pagado' => ['Pago confirmado', 'ti-circle-check', 'Ya puedes retirarlo en la tienda.', 2],
    'retirado' => ['Retirado', 'ti-package', 'Gracias por tu compra.', 4],
    'completado' => ['Completado', 'ti-package', 'Gracias por tu compra.', 4],
    'cancelado' => ['Cancelado', 'ti-circle-x', 'Este pedido se canceló. Si tienes dudas, escríbenos.', 0],
];
$pasos = ['Enviado', 'Confirmado', 'Listo para retirar', 'Retirado'];
$meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$fecha = function ($f) use ($meses) { $t = strtotime((string) $f); return $t ? date('j', $t) . ' ' . $meses[date('n', $t) - 1] . ' ' . date('Y', $t) : ''; };
$wa = preg_replace('/\D/', '', (string) ($sistema->whatsapp ?? ''));
?>
  <section class="pedidos">
    <div class="ped-cab">
      <div><?php if ($nombreCliente): ?><p class="ped-hola">Hola, <?= $e($nombreCliente) ?></p><?php endif; ?><h1 class="sec-tit">Mis pedidos</h1></div>
      <a class="btn sec" href="index.php?action=cerrar_sesion"><i class="ti ti-logout" aria-hidden="true"></i>Salir</a>
    </div>

    <?php if (!$pedidos): ?>
    <div class="carro-vacio">
      <i class="ti ti-receipt" aria-hidden="true"></i>
      <h2>Aún no tienes pedidos</h2>
      <p>Cuando envíes uno por WhatsApp, aquí podrás ver en qué va.</p>
      <a class="btn" href="index.php?action=ver_catalogo">Ver el catálogo</a>
    </div>
    <?php else: ?>
    <ul class="ped-lista">
      <?php foreach ($pedidos as $p):
        $est = $p->estado ?? 'pendiente';
        [$etq, $ico, $txt, $paso] = $estados[$est] ?? $estados['pendiente'];
        $items = $p->items ?? [];
        $cant = array_sum(array_map(fn($i) => (int) $i->cantidad, $items));
        $msg = rawurlencode('Hola, te escribo por mi pedido #' . (int) $p->id . '.'); ?>
      <li class="ped <?= $e($est) ?>">
        <div class="ped-arriba">
          <div><h2>Pedido #<?= (int) $p->id ?></h2><span><?= $e($fecha($p->fecha ?? '')) ?><?= $cant ? ' · ' . $cant . ($cant === 1 ? ' prenda' : ' prendas') : '' ?></span></div>
          <span class="ped-estado"><i class="ti <?= $ico ?>" aria-hidden="true"></i><?= $etq ?></span>
        </div>
        <?php if ($paso > 0 && $paso < 4): ?>
        <ol class="ped-pasos" aria-label="Avance del pedido">
          <?php foreach ($pasos as $k => $ps): ?><li class="<?= $k < $paso ? 'hecho' : ($k === $paso ? 'actual' : '') ?>"><?= $ps ?></li><?php endforeach; ?>
        </ol>
        <?php endif; ?>
        <div class="ped-abajo">
          <div class="ped-fotos"><?php foreach (array_slice($items, 0, 4) as $i): $f = $foto($i->imagen ?? ''); if ($f): ?><img src="<?= $e($f) ?>" alt="<?= $e($i->nombre_producto) ?>" width="48" height="62" loading="lazy"><?php endif; endforeach; ?></div>
          <p class="ped-txt"><?= $txt ?></p>
          <div class="ped-total"><b><?= $usd($p->total) ?></b><small><?= $bs($p->total) ?></small></div>
          <?php if ($wa): ?><a class="ped-wa" href="https://wa.me/<?= $e($wa) ?>?text=<?= $msg ?>" target="_blank" rel="noopener"><i class="ti ti-brand-whatsapp" aria-hidden="true"></i>Escribir por WhatsApp</a><?php endif; ?>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>
<?php $seccionPie = 'cuenta'; include __DIR__ . '/tienda/_pie.php'; ?>
