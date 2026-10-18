<?php
$menu = 'ventas';
$tituloPagina = 'Ventas';
$ventas = $ventas ?? [];
include __DIR__ . '/../admin/_abrir.php';

$f = [
    'desde'  => $_GET['desde'] ?? '',
    'hasta'  => $_GET['hasta'] ?? '',
    'estado' => $_GET['estado'] ?? '',
    'origen' => $_GET['origen'] ?? '',
    'q'      => $_GET['q'] ?? ''
];

// Mapeo completo de estados soportados en BD
$estados = [
    'pendiente'   => ['pend', 'Pendiente de pago'],
    'por_verificar'=> ['pend', 'Por verificar'],
    'por_retirar' => ['ok',   'Pagado, por retirar'],
    'pagado'      => ['ok',   'Pagado, por retirar'],
    'retirado'    => ['ok',   'Retirado'],
    'entregado'   => ['ok',   'Retirado'],
    'completado'  => ['ok',   'Completado'],
    'cancelado'   => ['agot', 'Cancelado']
];

// Cálculo de métricas sobre las ventas filtradas
$validas   = array_filter($ventas, fn($v) => ($v->estado ?? '') !== 'cancelado');
$total     = array_sum(array_map(fn($v) => (float)($v->total ?? 0), $validas));
$tienda    = array_sum(array_map(fn($v) => (float)($v->total ?? 0), array_filter($validas, fn($v) => ($v->origen_venta ?? '') === 'tienda_fisica')));
$porCobrar = array_sum(array_map(fn($v) => (float)($v->total ?? 0), array_filter($ventas, fn($v) => in_array($v->estado ?? '', ['pendiente', 'por_verificar']))));
$pctTienda = $total > 0 ? round(($tienda / $total) * 100) : 0;
$pctOnline = $total > 0 ? (100 - $pctTienda) : 0;
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Ventas</small><h2>Ventas</h2></div>
    <div class="cab-acc">
      <a href="index.php?action=exportar_excel&tipo=ventas&<?= http_build_query($f) ?>" class="btn btn-sec"><svg class="i"><use href="#i-archivo"/></svg>Excel</a>
      <a href="index.php?action=punto_venta" class="btn btn-pri"><svg class="i"><use href="#i-pos"/></svg>Nueva venta</a>
    </div>
  </header>

  <form class="filtros" method="GET" action="index.php" aria-label="Filtros">
    <input type="hidden" name="action" value="ventas">
    <label class="buscar"><svg class="i"><use href="#i-buscar"/></svg><input type="search" name="q" value="<?= $e($f['q']) ?>" placeholder="Buscar por n.º de venta o cliente" aria-label="Buscar venta"></label>
    <?php $periodoFiltro = ['modo' => 'rango', 'todo' => true]; include __DIR__ . '/../admin/_periodo.php'; ?>
    <label class="f-select<?= $f['estado'] !== '' ? ' activo' : '' ?>"><svg class="i"><use href="#i-recibo"/></svg>
      <select name="estado" aria-label="Estado" data-auto-enviar data-desplegable>
        <option value="">Todos los estados</option>
        <?php foreach ($estados as $k => [, $t]): ?>
        <option value="<?= $k ?>"<?= $f['estado'] === $k ? ' selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="f-select<?= $f['origen'] !== '' ? ' activo' : '' ?>"><svg class="i"><use href="#i-tienda"/></svg>
      <select name="origen" aria-label="Origen" data-auto-enviar data-desplegable>
        <option value="">Tienda y online</option>
        <option value="tienda_fisica"<?= $f['origen'] === 'tienda_fisica' ? ' selected' : '' ?>>Solo tienda</option>
        <option value="online"<?= $f['origen'] === 'online' ? ' selected' : '' ?>>Solo online</option>
      </select>
    </label>
    <?php if ($f['desde'] !== '' || $f['hasta'] !== '' || $f['estado'] !== '' || $f['origen'] !== '' || $f['q'] !== ''): ?>
    <a href="index.php?action=ventas" class="f-quitar"><svg class="i"><use href="#i-x"/></svg>Quitar filtros</a>
    <?php endif; ?>
  </form>

  <section class="mini-kpis" aria-label="Resumen del periodo">
    <div><span class="mk-ic"><svg class="i"><use href="#i-dolar"/></svg></span><span>Vendido</span><b><?= $usd($total) ?></b><small><?= $bs($total) ?></small></div>
    <div class="mk-oscuro"><span class="mk-ic"><svg class="i"><use href="#i-recibo"/></svg></span><span>Ventas</span><b><?= count($validas) ?></b><small>ticket promedio <?= $usd(count($validas) ? $total / count($validas) : 0) ?></small></div>
    <div class="mk-claro"><span class="mk-ic"><svg class="i"><use href="#i-tienda"/></svg></span><span>Tienda / Online</span><b><?= $pctTienda ?>% / <?= $pctOnline ?>%</b><small><?= $usd($tienda) ?> en tienda</small><span class="mk-barra" aria-hidden="true"><i style="width: <?= $pctTienda ?>%"></i><i style="width: <?= $pctOnline ?>%"></i></span></div>
    <div class="mk-ambar"><span class="mk-ic"><svg class="i"><use href="#i-alerta"/></svg></span><span>Por cobrar</span><b><?= $usd($porCobrar) ?></b><small>pagos sin verificar</small></div>
  </section>

  <section class="tabla-caja" aria-label="Listado de ventas">
    <div class="tabla-scroll">
      <table class="tabla-resp" data-paginar="10">
        <thead>
          <tr>
            <th>Venta</th>
            <th>Cliente</th>
            <th class="c-media">Origen</th>
            <th class="c-media">Pago</th>
            <th class="num">Total</th>
            <th>Estado</th>
            <th class="num"></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($ventas as $v): 
          $rawEst = trim($v->estado ?? '');
          $keyEstado = empty($rawEst) ? 'pendiente' : strtolower($rawEst);
          $es = $estados[$keyEstado] ?? ['pend', ($rawEst ?: 'Pendiente')]; 
          $origen = ($v->origen_venta ?? 'online') === 'online' ? 'Online' : 'Tienda'; 
        ?>
          <tr>
            <td class="t-tit">
              <div class="t-nombre">
                <b><?= $e(numero_doc('venta', $v)) ?></b>
                <span><?= !empty($v->fecha) ? date('d/m, g:i a', strtotime($v->fecha)) : '—' ?></span>
              </div>
            </td>
            <td data-label="Cliente">
              <div class="t-nombre">
                <b><?= $e($v->nombre_cliente ?? 'Cliente general') ?></b>
                <span class="si-estrecho"><?= $origen ?>, <?= $e($v->nombre_metodo ?? 'General') ?></span>
              </div>
            </td>
            <td data-label="Origen" class="c-media"><span class="chip"><?= $origen ?></span></td>
            <td data-label="Pago" class="c-media"><?= $e($v->nombre_metodo ?? 'N/A') ?></td>
            <td class="num" data-label="Total">
              <b><?= $usd($v->total ?? 0) ?></b><br>
              <span class="muted"><?= $bs($v->total ?? 0) ?></span>
            </td>
            <td data-label="Estado">
              <span class="est <?= $es[0] ?>"><?= $e($es[1]) ?></span>
            </td>
            <td class="t-acc">
              <div class="acc">
                <a href="index.php?action=detalle_venta&amp;<?= url_clave('venta', $v) ?>" class="ic-btn" aria-label="Ver venta <?= $e(numero_doc('venta', $v)) ?>">
                  <svg class="i"><use href="#i-ojo"/></svg>
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (!$ventas): ?>
      <div class="vacio-filtro">
        No hay ventas con los filtros aplicados.
      </div>
    <?php endif; ?>
  </section>
</section>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>