<?php
$menu = 'metodos_pago';
$metodoId = $metodoId ?? (int) ($_GET['metodo'] ?? 0);
$metodoNombre = $metodoNombre ?? 'este método';
$tituloPagina = 'Cobrado con ' . $metodoNombre;
$ventas = $ventas ?? [];
include __DIR__ . '/../admin/_abrir.php';
$f = ['desde' => $_GET['desde'] ?? date('Y-m-01'), 'hasta' => $_GET['hasta'] ?? date('Y-m-d')];
$lista = array_filter($ventas, function ($v) use ($f) {
    $dia = date('Y-m-d', strtotime($v->fecha));
    return $dia >= $f['desde'] && $dia <= $f['hasta'];
});
$totUsd = array_sum(array_map(fn($v) => $v->total, $lista));
$totBs = array_sum(array_map(fn($v) => $v->total_bs ?? $v->total * $tasa, $lista));
$porDia = [];
foreach ($lista as $v) { $d = date('Y-m-d', strtotime($v->fecha)); $porDia[$d] = ($porDia[$d] ?? 0) + $v->total; }
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Lo cobrado</small><div class="titulo-atras"><a href="index.php?action=metodos_pago" class="atras" aria-label="Volver a Métodos de pago"><svg class="i"><use href="#i-volver"/></svg></a><h2><?= $e($metodoNombre) ?></h2></div></div>
    <div class="cab-acc"><a href="index.php?<?= $e(http_build_query(['action' => 'exportar_excel', 'tipo' => 'metodo', 'metodo' => $metodoId, 'desde' => $f['desde'], 'hasta' => $f['hasta']])) ?>" class="btn btn-sec"><svg class="i"><use href="#i-archivo"/></svg>Excel</a></div>
  </header>
  <form class="filtros" method="GET" action="index.php" aria-label="Filtros">
    <input type="hidden" name="action" value="ver_reporte">
    <input type="hidden" name="metodo" value="<?= (int) $metodoId ?>">
    <?php $periodoFiltro = ['modo' => 'rango', 'defecto' => 'Este mes']; include __DIR__ . '/../admin/_periodo.php'; ?>
  </form>
  <section class="mini-kpis" aria-label="Resumen del periodo">
    <div><span class="mk-ic"><svg class="i"><use href="#i-dolar"/></svg></span><span>Cobrado</span><b><?= $usd($totUsd) ?></b><small><?= number_format($totBs, 2, ',', '.') ?> Bs</small></div>
    <div class="mk-oscuro"><span class="mk-ic"><svg class="i"><use href="#i-recibo"/></svg></span><span>Ventas</span><b><?= count($lista) ?></b><small>promedio <?= $usd(count($lista) ? $totUsd / count($lista) : 0) ?></small></div>
    <div class="mk-claro"><span class="mk-ic"><svg class="i"><use href="#i-calendario"/></svg></span><span>Días con cobros</span><b><?= count($porDia) ?></b><small><?= $porDia ? 'mejor día ' . date('d/m', strtotime(array_search(max($porDia), $porDia))) . ', ' . $usd(max($porDia)) : 'sin cobros' ?></small></div>
  </section>
  <section class="tabla-caja" aria-label="Ventas cobradas">
    <div class="tabla-scroll">
      <table class="tabla-resp">
        <thead><tr><th>Venta</th><th>Cliente</th><th class="num">Total</th><th class="num c-media">En bolívares</th><th class="num"></th></tr></thead>
        <tbody>
        <?php foreach ($lista as $v): ?>
          <tr>
            <td class="t-tit"><div class="t-nombre"><b><?= $e(numero_doc('venta', $v)) ?></b><span><?= date('d/m/Y, g:i a', strtotime($v->fecha)) ?></span></div></td>
            <td data-label="Cliente"><?= $e($v->nombre_cliente) ?></td>
            <td class="num" data-label="Total"><b><?= $usd($v->total) ?></b></td>
            <td class="num c-media" data-label="En bolívares"><?= number_format($v->total_bs ?? $v->total * $tasa, 2, ',', '.') ?> Bs</td>
            <td class="t-acc"><div class="acc"><a href="index.php?action=detalle_venta&amp;<?= url_clave('venta', $v) ?>" class="ic-btn" aria-label="Ver venta <?= $e(numero_doc('venta', $v)) ?>"><svg class="i"><use href="#i-ojo"/></svg></a></div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (!$lista): ?><div class="vacio-filtro">No hubo cobros con <?= $e($metodoNombre) ?> en estas fechas.</div><?php endif; ?>
  </section>
</section>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>
