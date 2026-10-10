<?php
$menu = 'ventas';
$tituloPagina = 'Ventas';
$ventas = $ventas ?? [];
include __DIR__ . '/../admin/_abrir.php';
$f = ['desde' => $_GET['desde'] ?? date('Y-m-d', strtotime('-7 days')), 'hasta' => $_GET['hasta'] ?? date('Y-m-d'), 'estado' => $_GET['estado'] ?? '', 'origen' => $_GET['origen'] ?? '', 'q' => $_GET['q'] ?? ''];
$estados = ['pendiente' => ['pend', 'Pendiente de pago'], 'pagado' => ['az', 'Pagado, por retirar'], 'retirado' => ['ok', 'Retirado'], 'entregado' => ['ok', 'Entregado'], 'completado' => ['ok', 'Completado'], 'cancelado' => ['agot', 'Cancelado']];
$validas = array_filter($ventas, fn($v) => $v->estado !== 'cancelado');
$total = array_sum(array_map(fn($v) => $v->total, $validas));
$tienda = array_sum(array_map(fn($v) => $v->total, array_filter($validas, fn($v) => ($v->origen_venta ?? '') === 'tienda_fisica')));
$porCobrar = array_sum(array_map(fn($v) => $v->total, array_filter($ventas, fn($v) => $v->estado === 'pendiente')));
$pctTienda = $total ? round($tienda / $total * 100) : 0;
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Ventas</small><h2>Ventas</h2></div>
    <div class="cab-acc">
      <a href="index.php?action=exportar_excel&tipo=ventas" class="btn btn-sec"><svg class="i"><use href="#i-archivo"/></svg>Excel</a>
      <a href="index.php?action=punto_venta" class="btn btn-pri"><svg class="i"><use href="#i-pos"/></svg>Nueva venta</a>
    </div>
  </header>

  <form class="herr" method="GET" action="index.php">
    <input type="hidden" name="action" value="ventas">
    <label class="buscar"><svg class="i"><use href="#i-buscar"/></svg><input type="search" name="q" value="<?= $e($f['q']) ?>" placeholder="Buscar por n.º de venta o cliente" aria-label="Buscar venta"></label>
    <label class="fecha-rango">Desde <input type="date" name="desde" value="<?= $e($f['desde']) ?>" aria-label="Desde"> hasta <input type="date" name="hasta" value="<?= $e($f['hasta']) ?>" aria-label="Hasta"></label>
    <select class="selector" name="estado" aria-label="Estado">
      <option value="">Todos los estados</option>
      <?php foreach ($estados as $k => [, $t]): ?><option value="<?= $k ?>" <?= $f['estado'] === $k ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
    </select>
    <select class="selector" name="origen" aria-label="Origen">
      <option value="">Tienda y online</option>
      <option value="tienda_fisica" <?= $f['origen'] === 'tienda_fisica' ? 'selected' : '' ?>>Solo tienda</option>
      <option value="online" <?= $f['origen'] === 'online' ? 'selected' : '' ?>>Solo online</option>
    </select>
    <button type="submit" class="btn btn-sec">Filtrar</button>
  </form>

  <section class="mini-kpis" aria-label="Resumen del periodo">
    <div><span>Vendido</span><b><?= $usd($total) ?></b><small><?= $bs($total) ?></small></div>
    <div><span>Ventas</span><b><?= count($validas) ?></b><small>ticket promedio <?= $usd(count($validas) ? $total / count($validas) : 0) ?></small></div>
    <div><span>Tienda / Online</span><b><?= $pctTienda ?>% / <?= $total ? 100 - $pctTienda : 0 ?>%</b><small><?= $usd($tienda) ?> en tienda</small></div>
    <div><span>Por cobrar</span><b><?= $usd($porCobrar) ?></b><small>pagos sin verificar</small></div>
  </section>

  <section class="tabla-caja" aria-label="Listado de ventas">
    <div class="tabla-scroll">
      <table class="tabla-resp">
        <thead><tr><th>Venta</th><th>Cliente</th><th class="c-media">Origen</th><th class="c-media">Pago</th><th class="num">Total</th><th>Estado</th><th class="num"></th></tr></thead>
        <tbody>
        <?php foreach ($ventas as $v): $es = $estados[$v->estado] ?? ['pend', $v->estado]; $origen = ($v->origen_venta ?? 'online') === 'online' ? 'Online' : 'Tienda'; ?>
          <tr>
            <td class="t-tit"><div class="t-nombre"><b>#<?= str_pad($v->id, 4, '0', STR_PAD_LEFT) ?></b><span><?= date('d/m, g:i a', strtotime($v->fecha)) ?></span></div></td>
            <td data-label="Cliente"><div class="t-nombre"><b><?= $e($v->nombre_cliente ?? '') ?></b><span class="si-estrecho"><?= $origen ?>, <?= $e($v->nombre_metodo ?? '') ?></span></div></td>
            <td data-label="Origen" class="c-media"><span class="chip"><?= $origen ?></span></td>
            <td data-label="Pago" class="c-media"><?= $e($v->nombre_metodo ?? 'N/A') ?></td>
            <td class="num" data-label="Total"><b><?= $usd($v->total) ?></b><br><span class="muted"><?= $bs($v->total) ?></span></td>
            <td data-label="Estado"><span class="est <?= $es[0] ?>"><?= $es[1] ?></span></td>
            <td class="t-acc"><div class="acc"><a href="index.php?action=detalle_venta&id=<?= (int) $v->id ?>" class="ic-btn" aria-label="Ver venta #<?= (int) $v->id ?>"><svg class="i"><use href="#i-ojo"/></svg></a></div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (!$ventas): ?><div class="vacio-filtro">No hay ventas con estos filtros.</div><?php endif; ?>
  </section>
</section>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>
