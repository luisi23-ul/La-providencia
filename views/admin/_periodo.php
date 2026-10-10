<?php
$periodoFiltro = $periodoFiltro ?? [];
$pModo = $periodoFiltro['modo'] ?? 'rango';
$pHoy = date('Y-m-d');
$pAyer = date('Y-m-d', strtotime('-1 day'));
$pTodos = [
    'Todo'     => ['', ''],
    'Hoy'      => [$pHoy, $pHoy],
    'Ayer'     => [$pAyer, $pAyer],
    '7 días'   => [date('Y-m-d', strtotime('-6 days')), $pHoy],
    '14 días'  => [date('Y-m-d', strtotime('-13 days')), $pHoy],
    'Este mes' => [date('Y-m-01'), $pHoy],
];
$pNombres = $periodoFiltro['atajos'] ?? ($pModo === 'dia' ? ['Hoy', 'Ayer'] : ['Hoy', 'Ayer', '7 días', 'Este mes']);
if (!empty($periodoFiltro['todo'])) array_unshift($pNombres, 'Todo');
$pAtajos = [];
foreach ($pNombres as $pNombre) $pAtajos[$pNombre] = $pTodos[$pNombre];
[$pDefD, $pDefH] = $pTodos[$periodoFiltro['defecto'] ?? 'Todo'] ?? ['', ''];

if ($pModo === 'dia') {
    $pDesde = $_GET['fecha'] ?? $pDefD;
    $pHasta = $pDesde;
} else {
    $pDesde = $_GET['desde'] ?? $pDefD;
    $pHasta = $_GET['hasta'] ?? $pDefH;
}

$pBase = array_filter($_GET, fn($v, $k) => is_string($v) && $v !== '' && !in_array($k, ['desde', 'hasta', 'fecha'], true), ARRAY_FILTER_USE_BOTH);
$pUrl = function ($d, $h) use ($pBase, $pModo) {
    $fechas = $pModo === 'dia' ? ['fecha' => $d] : ['desde' => $d, 'hasta' => $h];
    return 'index.php?' . http_build_query(array_filter($pBase + $fechas, fn($v) => $v !== ''));
};

$pActivo = '';
foreach ($pAtajos as $pNombre => [$pD, $pH]) {
    if ($pDesde === $pD && $pHasta === $pH) $pActivo = $pNombre;
}
$pCorta = fn($f) => ($t = strtotime($f)) ? date('d/m', $t) : $f;
$pEtiqueta = $pActivo !== '' ? ($pModo === 'dia' ? 'Fecha' : 'Fechas') : ($pDesde === $pHasta ? $pCorta($pDesde) : $pCorta($pDesde) . ' – ' . $pCorta($pHasta));
?>
<nav class="f-grupo" aria-label="Periodo">
  <?php foreach ($pAtajos as $pNombre => [$pD, $pH]): ?>
  <a class="f-op" href="<?= htmlspecialchars($pUrl($pD, $pH), ENT_QUOTES, 'UTF-8') ?>" aria-current="<?= $pActivo === $pNombre ? 'true' : 'false' ?>"><?= $pNombre ?></a>
  <?php endforeach; ?>
  <details class="f-rango">
    <summary class="f-op" aria-current="<?= $pActivo === '' ? 'true' : 'false' ?>"><svg class="i"><use href="#i-calendario"/></svg><?= htmlspecialchars($pEtiqueta, ENT_QUOTES, 'UTF-8') ?></summary>
    <div class="f-pop cal" data-modo="<?= $pModo ?>" data-max="<?= $pHoy ?>">
      <?php if ($pModo === 'dia'): ?>
      <input type="hidden" name="fecha" value="<?= htmlspecialchars($pDesde, ENT_QUOTES, 'UTF-8') ?>" data-cal="fecha">
      <?php else: ?>
      <input type="hidden" name="desde" value="<?= htmlspecialchars($pDesde, ENT_QUOTES, 'UTF-8') ?>" data-cal="desde">
      <input type="hidden" name="hasta" value="<?= htmlspecialchars($pHasta, ENT_QUOTES, 'UTF-8') ?>" data-cal="hasta">
      <?php endif; ?>
    </div>
  </details>
</nav>
