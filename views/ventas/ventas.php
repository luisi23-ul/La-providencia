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

$estados = [
    'pendiente'  => ['pend', 'Pendiente de pago'],
    'pagado'     => ['az', 'Pagado, por retirar'],
    'retirado'   => ['ok', 'Retirado'],
    'entregado'  => ['ok', 'Entregado'],
    'completado' => ['ok', 'Completado'],
    'cancelado'  => ['agot', 'Cancelado']
];

// Cálculo de métricas sobre las ventas filtradas
$validas = array_filter($ventas, fn($v) => $v->estado !== 'cancelado');
$total = array_sum(array_map(fn($v) => (float)$v->total, $validas));
$tienda = array_sum(array_map(fn($v) => (float)$v->total, array_filter($validas, fn($v) => ($v->origen_venta ?? '') === 'tienda_fisica')));
$porCobrar = array_sum(array_map(fn($v) => (float)$v->total, array_filter($ventas, fn($v) => $v->estado === 'pendiente')));
$pctTienda = $total > 0 ? round(($tienda / $total) * 100) : 0;
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Ventas</small><h2>Ventas</h2></div>
    <div class="cab-acc">
      <a href="index.php?action=exportar_excel&tipo=ventas&<?= http_build_query($f) ?>" class="btn btn-sec"><svg class="i"><use href="#i-archivo"/></svg>Excel</a>
      <a href="index.php?action=punto_venta" class="btn btn-pri"><svg class="i"><use href="#i-pos"/></svg>Nueva venta</a>
    </div>
  </header>

  <form id="form-filtro-ventas" class="herr" method="GET" action="index.php" style="flex-wrap: wrap; gap: 0.5rem;">
    <input type="hidden" name="action" value="ventas">

    <label class="buscar">
      <svg class="i"><use href="#i-buscar"/></svg>
      <input type="search" name="q" value="<?= $e($f['q']) ?>" placeholder="Buscar por n.º de venta o cliente">
    </label>

    <div style="display: inline-flex; align-items: center; gap: 0.4rem; background: var(--bg-card, #fff); padding: 0.35rem 0.6rem; border-radius: 6px; border: 1px solid #ccc;">
      <span style="font-size: 0.85rem; color: #555;">Desde</span>
      <input type="date" name="desde" value="<?= $e($f['desde']) ?>" style="border: none; outline: none; font-size: 0.85rem;">
      <span style="font-size: 0.85rem; color: #555;">hasta</span>
      <input type="date" name="hasta" value="<?= $e($f['hasta']) ?>" style="border: none; outline: none; font-size: 0.85rem;">
    </div>

    <select class="selector" name="estado">
      <option value="">Todos los estados</option>
      <?php foreach ($estados as $k => [, $t]): ?>
        <option value="<?= $k ?>" <?= $f['estado'] === $k ? 'selected' : '' ?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>

    <select class="selector" name="origen">
      <option value="">Tienda y online</option>
      <option value="tienda_fisica" <?= $f['origen'] === 'tienda_fisica' ? 'selected' : '' ?>>Solo tienda</option>
      <option value="online" <?= $f['origen'] === 'online' ? 'selected' : '' ?>>Solo online</option>
    </select>

    <button type="submit" onclick="this.form.submit()" class="btn btn-pri">Filtrar</button>
    <?php if ($f['desde'] !== '' || $f['hasta'] !== '' || $f['estado'] !== '' || $f['origen'] !== '' || $f['q'] !== ''): ?>
      <a href="index.php?action=ventas" class="btn btn-sec" style="text-decoration:none;">Limpiar</a>
    <?php endif; ?>
  </form>

  <section class="mini-kpis" aria-label="Resumen del periodo">
    <div><span>Vendido</span><b><?= $usd($total) ?></b><small><?= $bs($total) ?></small></div>
    <div><span>Ventas</span><b><?= count($validas) ?></b><small>ticket promedio <?= $usd(count($validas) ? $total / count($validas) : 0) ?></small></div>
    <div><span>Tienda / Online</span><b><?= $pctTienda ?>% / <?= $total ? (100 - $pctTienda) : 0 ?>%</b><small><?= $usd($tienda) ?> en tienda</small></div>
    <div><span>Por cobrar</span><b><?= $usd($porCobrar) ?></b><small>pagos sin verificar</small></div>
  </section>

  <section class="tabla-caja" aria-label="Listado de ventas">
    <div class="tabla-scroll">
      <table class="tabla-resp">
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
          $es = $estados[$v->estado] ?? ['pend', $v->estado]; 
          $origen = ($v->origen_venta ?? 'online') === 'online' ? 'Online' : 'Tienda'; 
        ?>
          <tr>
            <td class="t-tit">
              <div class="t-nombre">
                <b>#<?= str_pad($v->id, 4, '0', STR_PAD_LEFT) ?></b>
                <span><?= date('d/m, g:i a', strtotime($v->fecha)) ?></span>
              </div>
            </td>
            <td data-label="Cliente">
              <div class="t-nombre">
                <b><?= $e($v->nombre_cliente ?? '') ?></b>
                <span class="si-estrecho"><?= $origen ?>, <?= $e($v->nombre_metodo ?? '') ?></span>
              </div>
            </td>
            <td data-label="Origen" class="c-media"><span class="chip"><?= $origen ?></span></td>
            <td data-label="Pago" class="c-media"><?= $e($v->nombre_metodo ?? 'N/A') ?></td>
            <td class="num" data-label="Total">
              <b><?= $usd($v->total) ?></b><br>
              <span class="muted"><?= $bs($v->total) ?></span>
            </td>
            <td data-label="Estado"><span class="est <?= $es[0] ?>"><?= $es[1] ?></span></td>
            <td class="t-acc">
              <div class="acc">
                <a href="index.php?action=detalle_venta&id=<?= (int) $v->id ?>" class="ic-btn" aria-label="Ver venta #<?= (int) $v->id ?>">
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
      <div class="vacio-filtro" style="padding: 2.5rem; text-align: center; color: #64748b;">
        No hay ventas con los filtros aplicados.
      </div>
    <?php endif; ?>
  </section>
</section>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>