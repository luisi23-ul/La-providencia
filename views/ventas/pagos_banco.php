<?php
$menu = 'pagos_banco';
$tituloPagina = 'Pagos por banco';
$pagosRecibidos = $pagosRecibidos ?? [];
$metodos = $metodos ?? [];
include __DIR__ . '/../admin/_abrir.php';

$hoy = date('Y-m-d');
$f = [
    'desde'  => $_GET['desde'] ?? $hoy,
    'hasta'  => $_GET['hasta'] ?? $hoy,
    'metodo' => $_GET['metodo'] ?? '',
    'banco'  => $_GET['banco'] ?? '',
];

$esEfectivo = fn($p) => empty($p->requiere_referencia) || stripos($p->metodo ?? '', 'efectivo') !== false || ($p->banco ?? '') === 'Caja Física';
$pagos = array_values(array_filter($pagosRecibidos, fn($p) => !$esEfectivo($p)));
$tipos = array_values(array_filter($metodos, fn($m) => stripos($m->nombre, 'efectivo') === false));

$tasaDe = fn($p) => (float) ($p->tasa_cambio ?? 0) > 1 ? (float) $p->tasa_cambio : $tasa;
$aUsd = fn($p) => ($p->moneda ?? 'Bs') === 'USD' ? (float) $p->monto : (float) $p->monto / $tasaDe($p);
$aBs = fn($p) => ($p->moneda ?? 'Bs') === 'Bs' ? (float) $p->monto : (float) $p->monto * $tasaDe($p);
$fmtBs = fn($v) => number_format((float) $v, 2, ',', '.') . ' Bs';
$fmtUsd = fn($v) => '$' . number_format((float) $v, 2, ',', '.');

$porBanco = [];
$repetidas = [];
$totUsd = 0;
$totBs = 0;
foreach ($pagos as $p) {
    $nombre = trim($p->banco ?? '') ?: 'Sin banco';
    $porBanco[$nombre] ??= ['n' => 0, 'usd' => 0, 'bs' => 0];
    $porBanco[$nombre]['n']++;
    $porBanco[$nombre]['usd'] += $aUsd($p);
    $porBanco[$nombre]['bs'] += $aBs($p);
    $totUsd += $aUsd($p);
    $totBs += $aBs($p);
    $clave = $nombre . '|' . trim($p->referencia ?? '');
    $repetidas[$clave] = ($repetidas[$clave] ?? 0) + 1;
}
uasort($porBanco, fn($a, $b) => $b['usd'] <=> $a['usd']);
$mayor = $porBanco ? max(array_column($porBanco, 'usd')) : 0;

$bancosLista = ['Banesco', 'Banco de Venezuela', 'Mercantil', 'BBVA Provincial', 'BNC Nacional de Crédito', 'Bancaribe', 'Banco Exterior', 'Banco Plaza', 'Banco del Tesoro', 'Banco Bicentenario', 'Banplus', '100% Banco'];
foreach (array_merge(array_keys($porBanco), [$f['banco']]) as $b) {
    if ($b !== '' && $b !== 'Sin banco' && !in_array($b, $bancosLista, true)) $bancosLista[] = $b;
}

$url = function (array $cambios = []) use ($f) {
    $q = array_merge(['action' => 'pagos_banco'], $f, $cambios);
    return 'index.php?' . http_build_query(array_filter($q, fn($v) => $v !== '' && $v !== null));
};

$atajos = [
    'Hoy'       => [$hoy, $hoy],
    'Ayer'      => [date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day'))],
    '7 días'    => [date('Y-m-d', strtotime('-6 days')), $hoy],
    'Este mes'  => [date('Y-m-01'), $hoy],
];
$atajoActivo = '';
foreach ($atajos as $nombre => [$d, $h]) {
    if ($f['desde'] === $d && $f['hasta'] === $h) $atajoActivo = $nombre;
}

$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$fechaLarga = function ($fecha, $conMes = true) use ($meses) {
    $t = strtotime($fecha);
    if (!$t) return $fecha;
    $txt = date('j', $t) . ($conMes ? ' de ' . $meses[date('n', $t) - 1] : '');
    return $conMes && date('Y', $t) !== date('Y') ? $txt . ' de ' . date('Y', $t) : $txt;
};
if ($f['desde'] === $f['hasta']) {
    $periodo = $f['desde'] === $hoy ? 'hoy, ' . $fechaLarga($hoy) : 'el ' . $fechaLarga($f['desde']);
} elseif (substr($f['desde'], 0, 7) === substr($f['hasta'], 0, 7)) {
    $periodo = 'del ' . $fechaLarga($f['desde'], false) . ' al ' . $fechaLarga($f['hasta']);
} else {
    $periodo = 'del ' . $fechaLarga($f['desde']) . ' al ' . $fechaLarga($f['hasta']);
}

$nombreTipo = '';
foreach ($tipos as $t) if ((string) $t->id === (string) $f['metodo']) $nombreTipo = $t->nombre;
$hayFiltros = $f['metodo'] !== '' || $f['banco'] !== '' || $atajoActivo !== 'Hoy';

$titular = function ($p) {
    if (!preg_match('/Titular:\s*([^|]*?)\s*\|\s*Tel:\s*(.*)$/u', $p->observacion ?? '', $m)) return null;
    return [trim($m[1]), trim($m[2])];
};
?>
<section class="contenido pb">
  <header class="cab">
    <div class="cab-txt">
      <small>Ventas</small>
      <h2>Pagos por banco</h2>
    </div>
    <div class="cab-acc">
      <a href="index.php?action=exportar_excel&amp;tipo=pagos_banco&amp;<?= $e(http_build_query($f)) ?>" class="btn btn-sec"><svg class="i"><use href="#i-archivo"/></svg>Excel</a>
    </div>
  </header>

  <form class="filtros" method="GET" action="index.php" aria-label="Filtros">
    <input type="hidden" name="action" value="pagos_banco">
    <?php if ($f['metodo'] !== ''): ?><input type="hidden" name="metodo" value="<?= $e($f['metodo']) ?>"><?php endif; ?>
    <?php $periodoFiltro = ['modo' => 'rango', 'defecto' => 'Hoy']; include __DIR__ . '/../admin/_periodo.php'; ?>
    <nav class="f-grupo" aria-label="Tipo de pago">
      <a class="f-op" href="<?= $e($url(['metodo' => ''])) ?>" aria-current="<?= $f['metodo'] === '' ? 'true' : 'false' ?>">Todos</a>
      <?php foreach ($tipos as $t): ?>
      <a class="f-op" href="<?= $e($url(['metodo' => $t->id])) ?>" aria-current="<?= (string) $f['metodo'] === (string) $t->id ? 'true' : 'false' ?>"><?= $e($t->nombre) ?></a>
      <?php endforeach; ?>
    </nav>
    <label class="f-select<?= $f['banco'] !== '' ? ' activo' : '' ?>"><svg class="i"><use href="#i-banco"/></svg>
      <select name="banco" aria-label="Banco" data-auto-enviar data-desplegable data-buscar="Buscar banco">
        <option value="">Todos los bancos</option>
        <?php foreach ($bancosLista as $b): ?>
        <option value="<?= $e($b) ?>"<?= $f['banco'] === $b ? ' selected' : '' ?>><?= $e($b) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <?php if ($hayFiltros): ?>
    <a href="index.php?action=pagos_banco" class="f-quitar"><svg class="i"><use href="#i-x"/></svg>Quitar filtros</a>
    <?php endif; ?>
  </form>

  <?php if (!$pagos): ?>
  <section class="pb-vacio">
    <span class="pb-vacio-ic"><svg class="i"><use href="#i-banco"/></svg></span>
    <h3>No hay pagos verificados <?= $e($periodo) ?></h3>
    <p>Aquí salen los pagos móviles y las transferencias cuando ya se verificaron en «Verificar pagos».</p>
    <div class="pb-vacio-acc">
      <?php if ($atajoActivo !== 'Este mes'): ?><a class="btn btn-pri" href="<?= $e($url(['desde' => date('Y-m-01'), 'hasta' => $hoy])) ?>">Ver este mes</a><?php endif; ?>
      <?php if ($hayFiltros): ?><a class="btn btn-sec" href="index.php?action=pagos_banco">Quitar filtros</a><?php endif; ?>
    </div>
  </section>
  <?php else: ?>

  <p class="pb-periodo">Recibido <?= $e($periodo) ?><?= $f['banco'] !== '' ? ' en ' . $e($f['banco']) : '' ?><?= $nombreTipo !== '' ? ' por ' . $e(mb_strtolower($nombreTipo)) : '' ?></p>
  <section class="pb-resumen" aria-label="Resumen del periodo">
    <dl>
      <div class="pb-total"><dt>Total en dólares</dt><dd><?= $fmtUsd($totUsd) ?></dd></div>
      <div><dt>Total en bolívares</dt><dd><?= $fmtBs($totBs) ?></dd></div>
      <div><dt>Pagos</dt><dd><?= count($pagos) ?></dd></div>
    </dl>
  </section>

  <section class="pb-reparto" aria-labelledby="pb-reparto-t">
    <header><h3 id="pb-reparto-t">Por banco</h3><small>Pulsa un banco para ver solo sus pagos</small></header>
    <ol class="pb-bancos">
      <?php foreach ($porBanco as $nombre => $b):
        $activo = $f['banco'] === $nombre;
        $pct = $totUsd > 0 ? $b['usd'] / $totUsd * 100 : 0;
      ?>
      <li>
        <a href="<?= $e($url(['banco' => $activo ? '' : ($nombre === 'Sin banco' ? '' : $nombre)])) ?>" class="pb-banco" aria-current="<?= $activo ? 'true' : 'false' ?>">
          <span class="pb-banco-nom"><b><?= $e($nombre) ?></b><small><?= $b['n'] ?> <?= $b['n'] === 1 ? 'pago' : 'pagos' ?> · <?= number_format($pct, 0) ?> %</small></span>
          <span class="pb-banco-monto"><b><?= $fmtUsd($b['usd']) ?></b><small><?= $fmtBs($b['bs']) ?></small></span>
          <span class="pb-barra" aria-hidden="true"><i style="--p: <?= $mayor > 0 ? round($b['usd'] / $mayor * 100, 1) : 0 ?>%"></i></span>
        </a>
      </li>
      <?php endforeach; ?>
    </ol>
  </section>

  <section class="pb-pagos" aria-labelledby="pb-pagos-t">
    <header class="pb-pagos-cab">
      <h3 id="pb-pagos-t">Pagos <span><?= count($pagos) ?></span></h3>
      <label class="buscar"><svg class="i"><use href="#i-buscar"/></svg><input type="search" placeholder="Buscar referencia, cédula o cliente" aria-label="Buscar pago" data-filtro-tabla="pb-tabla" data-filtro-vacio="pb-sin-resultados"></label>
    </header>
    <div class="tabla-caja">
      <div class="tabla-scroll">
        <table class="tabla-resp" id="pb-tabla" data-paginar="10">
          <thead>
            <tr><th>Fecha</th><th>Cliente</th><th>Tipo</th><th>Banco</th><th>Referencia</th><th>Titular</th><th class="num">Monto</th></tr>
          </thead>
          <tbody>
          <?php foreach ($pagos as $p):
            $banco = trim($p->banco ?? '') ?: 'Sin banco';
            $ref = trim($p->referencia ?? '');
            $repetida = $ref !== '' && ($repetidas[$banco . '|' . $ref] ?? 0) > 1;
            $tit = $titular($p);
            $t = strtotime($p->fecha_pago);
          ?>
            <tr<?= $repetida ? ' class="pb-repetida"' : '' ?>>
              <td class="t-tit">
                <div class="t-nombre">
                  <b><?= date('d/m/Y', $t) ?> · <?= date('g:i a', $t) ?></b>
                  <span>Venta #<?= str_pad((string) $p->id_venta, 4, '0', STR_PAD_LEFT) ?> · <?= ($p->origen_venta ?? '') === 'tienda_fisica' ? 'Tienda física' : 'Online' ?></span>
                </div>
              </td>
              <td data-label="Cliente"><?= $e(trim($p->cliente ?? '') ?: 'Cliente') ?></td>
              <td data-label="Tipo"><span class="chip"><?= $e($p->metodo) ?></span></td>
              <td data-label="Banco"><b class="pb-tbanco"><?= $e($banco) ?></b></td>
              <td data-label="Referencia">
                <span class="pb-ref">
                  <span class="cod"><?= $e($ref ?: 'Sin referencia') ?></span>
                  <?php if ($ref !== ''): ?><button type="button" class="pb-copiar" data-copiar="<?= $e($ref) ?>" aria-label="Copiar referencia <?= $e($ref) ?>">Copiar</button><?php endif; ?>
                </span>
                <?php if ($repetida): ?><span class="pb-aviso"><svg class="i"><use href="#i-alerta"/></svg>Referencia repetida</span><?php endif; ?>
              </td>
              <td data-label="Titular">
                <?php if ($tit): ?>
                <span class="pb-titular"><?= $e($tit[0] ?: '—') ?><small><?= $e($tit[1] ?: '') ?></small></span>
                <?php else: ?>
                <span class="muted">—</span>
                <?php endif; ?>
              </td>
              <td class="num" data-label="Monto">
                <b class="pb-monto"><?= ($p->moneda ?? 'Bs') === 'USD' ? $fmtUsd($p->monto) : $fmtBs($p->monto) ?></b>
                <?php if (($p->moneda ?? 'Bs') === 'Bs'): ?><small class="pb-equiv"><?= $fmtUsd($aUsd($p)) ?></small><?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="vacio-filtro" id="pb-sin-resultados" hidden>Ningún pago coincide con esa búsqueda. Prueba con los últimos dígitos de la referencia.</p>
    </div>
  </section>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>
