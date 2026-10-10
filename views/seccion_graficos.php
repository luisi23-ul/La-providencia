<?php
$menu = 'seccion_graficos';
$tituloPagina = 'Estadísticas';
$est = array_merge(['dias' => [], 'resumen' => [], 'top' => [], 'sin_movimiento' => [], 'categorias' => [], 'metodos' => [], 'proveedores' => []], $estadisticas ?? []);
$r = array_merge(['vendido' => 0, 'ganancia' => 0, 'ventas' => 0, 'tienda' => 0], $est['resumen']);
include __DIR__ . '/admin/_abrir.php';
$f = ['desde' => $_GET['desde'] ?? date('Y-m-d', strtotime('-13 days')), 'hasta' => $_GET['hasta'] ?? date('Y-m-d'), 'agrupar' => ($_GET['agrupar'] ?? 'dia') === 'mes' ? 'mes' : 'dia', 'tab' => $_GET['tab'] ?? 'ventas'];
$tabs = ['ventas' => 'Ventas', 'productos' => 'Productos', 'dinero' => 'Cobros y compras'];
if (!isset($tabs[$f['tab']])) $f['tab'] = 'ventas';
$url = fn($cambios) => 'index.php?' . http_build_query(array_merge(['action' => 'seccion_graficos'], $f, $cambios));
$rapidos = ['7 días' => [date('Y-m-d', strtotime('-6 days')), date('Y-m-d')], '14 días' => [date('Y-m-d', strtotime('-13 days')), date('Y-m-d')], 'Este mes' => [date('Y-m-01'), date('Y-m-d')]];
$tiendaPct = $r['vendido'] ? round($r['tienda'] / $r['vendido'] * 100) : 0;
$meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

$barras = function ($id, $titulo, $filas, $vacio, $extra = '') { ?>
  <section class="caja" aria-labelledby="<?= $id ?>">
    <header class="caja-cab"><h3 id="<?= $id ?>"><?= $titulo ?></h3><?= $extra ?></header>
    <?php if ($filas): $max = max(array_column($filas, 'v')) ?: 1; ?>
      <ol class="hb-lista">
        <?php foreach ($filas as $x): ?>
          <li class="hb"><span class="hb-nom"><b><?= $x['n'] ?></b><?php if (!empty($x['s'])): ?><small><?= $x['s'] ?></small><?php endif; ?></span><span class="hb-val"><?= $x['vt'] ?><?php if (!empty($x['vs'])): ?><small><?= $x['vs'] ?></small><?php endif; ?></span><span class="hb-pista"><i class="<?= $x['c'] ?? '' ?>" style="width:<?= round(max(0, $x['v']) / $max * 100, 1) ?>%"></i></span></li>
        <?php endforeach; ?>
      </ol>
    <?php else: ?><div class="vacio-filtro"><?= $vacio ?></div><?php endif; ?>
  </section>
<?php };
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Gestión</small><h2>Estadísticas</h2></div>
    <div class="cab-acc"><a href="index.php?<?= $e(http_build_query(['action' => 'exportar_pdf', 'tipo' => 'estadisticas', 'desde' => $f['desde'], 'hasta' => $f['hasta']])) ?>" target="_blank" class="btn btn-sec"><svg class="i"><use href="#i-archivo"/></svg>PDF</a></div>
  </header>

  <form class="herr" method="GET" action="index.php">
    <input type="hidden" name="action" value="seccion_graficos">
    <input type="hidden" name="tab" value="<?= $e($f['tab']) ?>">
    <input type="hidden" name="agrupar" value="<?= $e($f['agrupar']) ?>">
    <label class="fecha-rango">Desde <input type="date" name="desde" value="<?= $e($f['desde']) ?>" aria-label="Desde"> hasta <input type="date" name="hasta" value="<?= $e($f['hasta']) ?>" aria-label="Hasta"></label>
    <button type="submit" class="btn btn-sec">Filtrar</button>
    <div class="seg" role="group" aria-label="Periodos rápidos">
      <?php foreach ($rapidos as $t => [$d, $h]): ?><a href="<?= $e($url(['desde' => $d, 'hasta' => $h])) ?>" class="seg-link" aria-current="<?= $f['desde'] === $d && $f['hasta'] === $h ? 'page' : 'false' ?>"><?= $t ?></a><?php endforeach; ?>
    </div>
  </form>

  <nav class="est-tabs" aria-label="Secciones de estadísticas">
    <?php foreach ($tabs as $k => $t): ?><a href="<?= $e($url(['tab' => $k])) ?>" aria-current="<?= $f['tab'] === $k ? 'page' : 'false' ?>"><?= $t ?></a><?php endforeach; ?>
  </nav>

  <?php if ($f['tab'] === 'ventas'): ?>
    <section class="mini-kpis" aria-label="Resumen del periodo">
      <div><span>Vendido</span><b><?= $usd($r['vendido']) ?></b><small><?= $bs($r['vendido']) ?></small></div>
      <div><span>Ganancia</span><b><?= $usd($r['ganancia']) ?></b><small>precio menos costo</small></div>
      <div><span>Tienda / Online</span><b><?= $tiendaPct ?>% / <?= $r['vendido'] ? 100 - $tiendaPct : 0 ?>%</b><small>de lo vendido</small></div>
      <div><span>Ticket promedio</span><b><?= $usd($r['ventas'] ? $r['vendido'] / $r['ventas'] : 0) ?></b><small><?= (int) $r['ventas'] ?> ventas</small></div>
    </section>

    <section class="caja" aria-labelledby="t-graf">
      <header class="caja-cab"><h3 id="t-graf">Ventas por <?= $f['agrupar'] === 'mes' ? 'mes' : 'día' ?></h3>
        <div class="seg" role="group" aria-label="Agrupar"><a href="<?= $e($url(['agrupar' => 'dia'])) ?>" class="seg-link" aria-current="<?= $f['agrupar'] === 'dia' ? 'page' : 'false' ?>">Por día</a><a href="<?= $e($url(['agrupar' => 'mes'])) ?>" class="seg-link" aria-current="<?= $f['agrupar'] === 'mes' ? 'page' : 'false' ?>">Por mes</a></div>
      </header>
      <?php if ($est['dias']):
          $maxDia = max(array_map(fn($d) => $d['tienda'] + $d['online'], $est['dias'])) ?: 1;
          $paso = $maxDia > 1500 ? 1500 : ($maxDia > 450 ? 300 : 150);
          $escala = ceil($maxDia / $paso) * $paso;
          $ultimo = count($est['dias']) - 1; ?>
        <div class="grafico">
          <div class="leyenda"><span><i class="c-tienda"></i>Tienda física</span><span><i class="c-online"></i>Online</span></div>
          <div class="barras" role="img" aria-label="Ventas por <?= $f['agrupar'] === 'mes' ? 'mes' : 'día' ?> y canal">
            <div class="eje"><span>$0</span><span>$<?= round($escala / 3) ?></span><span>$<?= round($escala / 3 * 2) ?></span><span>$<?= $escala ?></span></div>
            <div class="area">
              <?php foreach ($est['dias'] as $i => $d):
                  $ts = strtotime($d['fecha']);
                  $etq = $f['agrupar'] === 'mes' ? $meses[date('n', $ts) - 1] : ($d['fecha'] === date('Y-m-d') ? 'Hoy' : date('d', $ts)); ?>
                <div class="dia<?= $i === $ultimo ? ' hoy' : '' ?>" title="<?= $f['agrupar'] === 'mes' ? $meses[date('n', $ts) - 1] . ' ' . date('Y', $ts) : date('d/m', $ts) ?>: tienda <?= $usd($d['tienda']) ?>, online <?= $usd($d['online']) ?>"><div class="pila" style="height:<?= round(($d['tienda'] + $d['online']) / $escala * 100, 1) ?>%"><span class="o" style="flex:<?= (float) $d['online'] ?>"></span><span class="t" style="flex:<?= (float) $d['tienda'] ?>"></span></div><small><?= $etq ?></small></div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php else: ?><div class="vacio-filtro">No hay ventas en estas fechas.</div><?php endif; ?>
    </section>

    <?php $barras('t-cat', 'Ganancia por categoría', array_map(fn($c) => ['n' => $e($c->nombre), 's' => 'vendido ' . $usd($c->vendido), 'v' => $c->ganancia, 'vt' => $usd($c->ganancia), 'vs' => $c->vendido > $c->ganancia ? round($c->ganancia / ($c->vendido - $c->ganancia) * 100) . '% sobre el costo' : '', 'c' => 'verde'], $est['categorias']), 'No hay ventas en estas fechas.'); ?>

  <?php elseif ($f['tab'] === 'productos'): ?>
    <div class="rejilla-2">
      <?php $barras('t-top', 'Más vendidos', array_map(fn($p) => ['n' => $e($p->nombre), 's' => $e($p->categoria) . ', ' . (int) $p->unidades . ' vendidos', 'v' => $p->monto, 'vt' => $usd($p->monto), 'vs' => 'gana ' . $usd($p->ganancia)], $est['top']), 'No hay ventas en estas fechas.', '<span class="muted">por monto</span>'); ?>
      <section class="caja" aria-labelledby="t-sinmov">
        <header class="caja-cab"><h3 id="t-sinmov">Sin movimiento</h3><span class="muted">30 días o más sin venderse</span></header>
        <?php if ($est['sin_movimiento']): ?>
          <ul class="sin-mov" style="list-style:none;margin:0;padding:0">
            <?php foreach ($est['sin_movimiento'] as $p): ?><li><b><?= $e($p->nombre) ?></b><span class="dias"><?= $p->dias === null ? 'Nunca vendido' : (int) $p->dias . ' días' ?></span><small><?= $e($p->categoria) ?>, <?= (int) $p->stock ?> en stock</small></li><?php endforeach; ?>
          </ul>
        <?php else: ?><div class="vacio-filtro">Todos los productos se vendieron en los últimos 30 días.</div><?php endif; ?>
      </section>
    </div>

  <?php else:
      $totalMetodos = array_sum(array_map(fn($m) => $m->monto, $est['metodos'])) ?: 1; ?>
    <div class="rejilla-2">
      <?php $barras('t-met', 'Cobros por método', array_map(fn($m) => ['n' => $e($m->nombre), 'v' => $m->monto, 'vt' => $usd($m->monto), 'vs' => round($m->monto / $totalMetodos * 100) . '% del total'], $est['metodos']), 'No hay cobros verificados en estas fechas.'); ?>
      <?php $barras('t-prov', 'Compras por proveedor', array_map(fn($p) => ['n' => $e($p->razon_social), 's' => (int) $p->compras . ((int) $p->compras === 1 ? ' compra' : ' compras'), 'v' => $p->total, 'vt' => $usd($p->total), 'c' => 'marino'], $est['proveedores']), 'No hay compras en estas fechas.'); ?>
    </div>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/admin/_cerrar.php'; ?>
