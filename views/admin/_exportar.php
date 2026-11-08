<?php
$expQuery = ['tipo' => $exportar['tipo']] + array_filter($exportar['filtros'] ?? [], fn($v) => $v !== '' && $v !== null);
$expEnlace = fn($accion) => 'index.php?' . http_build_query(['action' => $accion] + $expQuery);
?>
<a href="<?= $e($expEnlace('exportar_pdf')) ?>" target="_blank" class="btn btn-sec btn-rep pdf"><svg class="i"><use href="#i-pdf"/></svg>PDF</a>
<a href="<?= $e($expEnlace('exportar_excel')) ?>" class="btn btn-sec btn-rep xls"><svg class="i"><use href="#i-excel"/></svg>Excel</a>
<?php unset($exportar, $expQuery, $expEnlace); ?>
