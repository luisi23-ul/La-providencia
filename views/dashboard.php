<?php
$menu = 'dashboard';
$tituloPagina = 'Inicio';
$k = array_merge(['ventas_hoy' => 0, 'ventas_hoy_n' => 0, 'ventas_ayer' => 0, 'pagos_pendientes' => 0, 'pagos_pendientes_monto' => 0, 'por_retirar' => 0, 'stock_bajo' => 0, 'agotados' => 0], $kpis ?? []);
$pagosPendientes = $pagosPendientes ?? [];
$porRetirar = $porRetirar ?? [];
$stockBajo = $stockBajo ?? [];
$ventasSemana = $ventasSemana ?? [];
$contadores = ['verificar_pagos' => $k['pagos_pendientes'], 'retiro_pedidos' => $k['por_retirar']];
include __DIR__ . '/admin/_abrir.php';
$variacion = $k['ventas_ayer'] ? round(($k['ventas_hoy'] - $k['ventas_ayer']) / $k['ventas_ayer'] * 100) : null;
$maxDia = max(1, ...array_map(fn($d) => $d['tienda'] + $d['online'], $ventasSemana ?: [['tienda' => 0, 'online' => 0]]));
$escala = ceil($maxDia / 150) * 150;
$dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$semanaTotal = array_sum(array_map(fn($d) => $d['tienda'] + $d['online'], $ventasSemana));
$semanaTienda = array_sum(array_column($ventasSemana, 'tienda'));
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small><?= ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'][date('w')] ?> <?= date('j') ?> de <?= $meses[date('n') - 1] ?></small><h2>Resumen de hoy</h2></div>
    <div class="cab-acc">
      <span class="tasa">Tasa BCV <b><?= number_format($tasa, 2) ?> Bs/$</b></span>
      <?php if ($puede('compras')): ?><a href="index.php?action=nueva_compra" class="btn btn-sec"><svg class="i"><use href="#i-carrito"/></svg>Registrar compra</a><?php endif; ?>
      <?php if ($puede('punto_venta')): ?><a href="index.php?action=punto_venta" class="btn btn-pri"><svg class="i"><use href="#i-pos"/></svg>Nueva venta</a><?php endif; ?>
    </div>
  </header>

  <section class="kpis" aria-label="Indicadores de hoy">
    <a href="index.php?action=ventas" class="kpi"><span class="kpi-cab">Ventas de hoy<span class="kpi-ic az"><svg class="i"><use href="#i-dolar"/></svg></span></span><span class="kpi-val"><?= $usd($k['ventas_hoy']) ?></span><span class="kpi-sub"><?= $bs($k['ventas_hoy']) ?></span><span class="kpi-pie"><?php if ($variacion !== null): ?><span class="sube"><?= $variacion >= 0 ? '+' : '' ?><?= $variacion ?>%</span> que ayer, <?php endif; ?><?= (int) $k['ventas_hoy_n'] ?> ventas</span></a>
    <a href="index.php?action=verificar_pagos" class="kpi"><span class="kpi-cab">Pagos por verificar<span class="kpi-ic am"><svg class="i"><use href="#i-check"/></svg></span></span><span class="kpi-val"><?= (int) $k['pagos_pendientes'] ?></span><span class="kpi-sub"><?= $usd($k['pagos_pendientes_monto']) ?></span></a>
    <a href="index.php?action=retiro_pedidos" class="kpi"><span class="kpi-cab">Por retirar<span class="kpi-ic vi"><svg class="i"><use href="#i-bolsa"/></svg></span></span><span class="kpi-val"><?= (int) $k['por_retirar'] ?></span><span class="kpi-sub">pedidos pagados</span><span class="kpi-pie">Esperando al cliente en tienda</span></a>
    <a href="index.php?action=listado_productos&stock=bajo" class="kpi"><span class="kpi-cab">Stock bajo<span class="kpi-ic ro"><svg class="i"><use href="#i-alerta"/></svg></span></span><span class="kpi-val"><?= (int) $k['stock_bajo'] ?></span><span class="kpi-sub">productos bajo el mínimo</span><?php if ($k['agotados']): ?><span class="kpi-pie"><span class="est agot"><?= (int) $k['agotados'] ?> agotado<?= $k['agotados'] > 1 ? 's' : '' ?></span></span><?php endif; ?></a>
  </section>

  <div class="rejilla-2">
    <section class="caja" aria-labelledby="t-pagos">
      <header class="caja-cab"><h3 id="t-pagos">Pagos por verificar<?php if ($pagosPendientes): ?> <span class="contador"><?= count($pagosPendientes) ?></span><?php endif; ?></h3><a href="index.php?action=verificar_pagos">Ver todos</a></header>
      <ul class="lista">
        <?php foreach ($pagosPendientes as $p): ?>
          <li><div class="quien"><b><?= $e($p->cliente) ?></b><span><span class="chip"><?= $e($p->metodo) ?></span><span class="ref">Ref. <?= $e($p->referencia) ?></span></span></div><div class="monto"><b><?= ($p->moneda ?? 'Bs') === 'USD' ? $usd($p->monto) : number_format($p->monto, 2) . ' Bs' ?></b></div><a href="index.php?action=verificar_pagos" class="btn btn-sec btn-peq">Revisar</a></li>
        <?php endforeach; ?>
        <?php if (!$pagosPendientes): ?><li class="vacio-filtro" style="display:block">No hay pagos esperando verificación.</li><?php endif; ?>
      </ul>
    </section>
    <section class="caja" aria-labelledby="t-ventas">
      <header class="caja-cab"><h3 id="t-ventas">Ventas de los últimos 7 días</h3><a href="index.php?action=ventas">Ver ventas</a></header>
      <div class="grafico">
        <div class="leyenda"><span><i class="c-tienda"></i>Tienda física</span><span><i class="c-online"></i>Online</span></div>
        <div class="barras" role="img" aria-label="Ventas diarias en dólares de los últimos 7 días">
          <div class="eje"><span>$0</span><span>$<?= $escala / 3 ?></span><span>$<?= $escala / 3 * 2 ?></span><span>$<?= $escala ?></span></div>
          <div class="area">
            <?php foreach ($ventasSemana as $i => $d): $tot = $d['tienda'] + $d['online']; ?>
              <div class="dia<?= $i === count($ventasSemana) - 1 ? ' hoy' : '' ?>"><div class="pila" style="height:<?= round($tot / $escala * 100, 1) ?>%"><span class="o" style="flex:<?= $d['online'] ?: 0 ?>"></span><span class="t" style="flex:<?= $d['tienda'] ?: 0 ?>"></span></div><small><?= $i === count($ventasSemana) - 1 ? 'Hoy' : $dias[date('w', strtotime($d['fecha']))] ?></small></div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="resumen-g"><span>Semana: <b><?= $usd($semanaTotal) ?></b></span><span>Tienda <b><?= $semanaTotal ? round($semanaTienda / $semanaTotal * 100) : 0 ?>%</b>, online <b><?= $semanaTotal ? 100 - round($semanaTienda / $semanaTotal * 100) : 0 ?>%</b></span></div>
      </div>
    </section>
  </div>

  <div class="rejilla-2">
    <section class="caja" aria-labelledby="t-retiro">
      <header class="caja-cab"><h3 id="t-retiro">Listos para retirar<?php if ($porRetirar): ?> <span class="contador"><?= count($porRetirar) ?></span><?php endif; ?></h3><a href="index.php?action=retiro_pedidos">Ver retiros</a></header>
      <ul class="lista">
        <?php foreach ($porRetirar as $v): ?>
          <li><div class="quien"><b><?= $e($v->nombre_cliente) ?>, #<?= str_pad($v->id, 4, '0', STR_PAD_LEFT) ?></b><span>Pagado <?= date('d/m, g:i a', strtotime($v->fecha)) ?></span></div><div class="monto"><b><?= $usd($v->total) ?></b><span><?= $bs($v->total) ?></span></div><a href="index.php?action=retiro_pedidos" class="btn btn-sec btn-peq">Entregar</a></li>
        <?php endforeach; ?>
        <?php if (!$porRetirar): ?><li class="vacio-filtro" style="display:block">No hay pedidos esperando retiro.</li><?php endif; ?>
      </ul>
    </section>
    <section class="caja" aria-labelledby="t-stock">
      <header class="caja-cab"><h3 id="t-stock">Stock bajo</h3><a href="index.php?action=listado_productos">Ver inventario</a></header>
      <ul class="lista stock-l">
        <?php foreach ($stockBajo as $p): ?>
          <li><?php if (!empty($p->imagen)): ?><img class="foto chica" src="public/uploads/<?= $e($p->imagen) ?>" alt="" width="36" height="36"><?php else: ?><span></span><?php endif; ?><div class="quien"><b><?= $e($p->nombre_producto) ?></b><span class="nums">Tienda <?= (int) $p->stock ?>, online <?= (int) ($p->stock_online ?? 0) ?> (mín. <?= (int) ($p->stock_minimo ?? 5) ?>)</span></div><span class="est <?= $p->stock + ($p->stock_online ?? 0) == 0 ? 'agot">Agotado' : 'bajo">Bajo' ?></span></li>
        <?php endforeach; ?>
        <?php if (!$stockBajo): ?><li class="vacio-filtro" style="display:block">Todo el inventario está sobre el mínimo.</li><?php endif; ?>
      </ul>
    </section>
  </div>
</section>
<?php include __DIR__ . '/admin/_cerrar.php'; ?>
