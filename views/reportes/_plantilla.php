<?php
$titulo    = $titulo ?? 'Reporte';
$subtitulo = $subtitulo ?? '';
$columnas  = $columnas ?? [];
$grupos    = $grupos ?? [];
$totales   = $totales ?? [];
$resumen   = $resumen ?? [];
$empresa   = (object) ($empresa ?? []);
$modo      = $modo ?? 'imprimir';
$elaborado = $elaborado ?? '';

$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$normCols = fn(array $cs) => array_map(fn($c) => (is_array($c) ? $c : ['texto' => $c]) + ['numero' => false, 'decimales' => 2], $cs);
$cols = $normCols($columnas);
$celdas = function (array $fila, string $etiqueta = 'td', ?array $cols = null) use ($h, &$colsBase) {
    $cols = $cols ?? $colsBase;
    $html = '';
    foreach (array_values($fila) as $i => $v) {
        $num = !empty($cols[$i]['numero']);
        if ($num && $etiqueta === 'td' && is_numeric($v)) $v = number_format((float) $v, (int) $cols[$i]['decimales'], ',', '.');
        $clase = trim(($num ? 'num' : '') . (!empty($cols[$i]['fijo']) ? ' fijo' : ''));
        $texto = is_array($v) ? $h($v[0] ?? '') . (($v[1] ?? '') !== '' ? '<small>' . $h($v[1]) . '</small>' : '') : $h($v);
        $html .= "<$etiqueta" . ($clase !== '' ? ' class="' . $clase . '"' : '') . '>' . $texto . "</$etiqueta>";
    }
    return $html;
};

$colsBase = $cols;
$anchos = function (array $cs) {
    $w = array_map(fn($c) => (float) ($c['ancho'] ?? max(10, min(28, mb_strlen($c['texto']) + 6))), $cs);
    $suma = array_sum($w) ?: 1;
    return '<colgroup>' . implode('', array_map(fn($x) => '<col style="width: ' . round($x / $suma * 100, 2) . '%">', $w)) . '</colgroup>';
};
include __DIR__ . '/_hoja_abrir.php';
?>

  <?php if ($resumen): ?>
  <section aria-label="Resumen">
    <table class="resumen" role="presentation"><tr>
      <?php foreach ($resumen as $dato): ?><td style="width: <?= round(100 / count($resumen), 3) ?>%"><span><?= $h($dato['texto'] ?? '') ?></span><b><?= $h($dato['valor'] ?? '') ?></b><small><?= ($dato['nota'] ?? '') !== '' ? $h($dato['nota']) : '&nbsp;' ?></small></td><?php endforeach; ?>
    </tr></table>
  </section>
  <?php endif; ?>

  <?php if (!$grupos): ?>
  <p class="vacio">No hay datos para los filtros elegidos.</p>
  <?php else: ?>
  <?php foreach ($grupos as $g): $gc = isset($g['columnas']) ? $normCols($g['columnas']) : $cols; ?>
  <table class="datos<?= count($g['filas'] ?? []) <= 8 ? ' junto' : '' ?>">
    <?= $anchos($gc) ?>
    <thead>
      <?php if (($g['titulo'] ?? '') !== ''): ?><tr class="grupo"><th colspan="<?= max(count($gc), 1) ?>" scope="colgroup"><?= $h($g['titulo']) ?></th></tr><?php endif; ?>
      <tr class="cols"><?= str_replace('<th', '<th scope="col"', $celdas(array_column($gc, 'texto'), 'th', $gc)) ?></tr>
    </thead>
    <tbody>
      <?php foreach ($g['filas'] ?? [] as $fila): ?><tr><?= $celdas($fila, 'td', $gc) ?></tr><?php endforeach; ?>
      <?php if (!empty($g['subtotal'])): ?><tr class="sub"><?= $celdas($g['subtotal'], 'td', $gc) ?></tr><?php endif; ?>
    </tbody>
  </table>
  <?php endforeach; ?>
  <?php if ($totales): ?>
  <table class="datos datos-total">
    <?= $anchos($cols) ?>
    <tbody><tr class="total"><?= $celdas($totales) ?></tr></tbody>
  </table>
  <?php endif; ?>
  <?php endif; ?>
  <?php if ($elaborado !== ''): ?><p class="firma">Elaborado por <?= $h($elaborado) ?></p><?php endif; ?>
<?php include __DIR__ . '/_hoja_cerrar.php'; ?>
