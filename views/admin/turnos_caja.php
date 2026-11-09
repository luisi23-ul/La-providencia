<?php
$menu = 'turnos_caja';
$tituloPagina = 'Cierres de caja';
$turnos = $turnos ?? [];
$cajeros = $cajeros ?? [];
$panelMaster = strtolower(trim((string) ($_SESSION['codigo_rol'] ?? $_SESSION['rol'] ?? ''))) === 'master';
include __DIR__ . '/_abrir.php';

$f = [
    'cajero' => trim((string) ($_GET['cajero'] ?? '')),
    'desde'  => $_GET['desde'] ?? '',
    'hasta'  => $_GET['hasta'] ?? '',
];
$hayFiltros = $f['cajero'] !== '' || $f['desde'] !== '' || $f['hasta'] !== '';
$bsMonto = fn($v) => number_format((float) $v, 2, ',', '.') . ' Bs';
$meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$dia = fn($t) => date('j', $t) . ' ' . $meses[date('n', $t) - 1] . ' ' . date('Y', $t);
$hora = fn($t) => date('g:i a', $t);
$hoy = date('Y-m-d');

$abiertos = array_filter($turnos, fn($t) => ($t->estado ?? '') === 'abierto');
$cerrados = array_filter($turnos, fn($t) => ($t->estado ?? '') === 'cerrado');
$fondoUsd = array_sum(array_map(fn($t) => (float) ($t->fondo_usd ?? 0), $turnos));
$fondoBs = array_sum(array_map(fn($t) => (float) ($t->fondo_bs ?? 0), $turnos));
$contUsd = array_sum(array_map(fn($t) => (float) ($t->cierre_usd ?? 0), $cerrados));
$contBs = array_sum(array_map(fn($t) => (float) ($t->cierre_bs ?? 0), $cerrados));
$abierto = reset($abiertos) ?: null;
$nombreDe = fn($t) => trim(ucwords(mb_strtolower(trim(($t->cajero_nombre ?? '') . ' ' . ($t->cajero_apellido ?? ''))))) ?: (string) ($t->cedula_cajero ?? '');
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt">
      <small>Caja</small>
      <h2>Cierres de caja</h2>
    </div>
  </header>

  <form class="filtros" method="GET" action="index.php" aria-label="Filtros">
    <input type="hidden" name="action" value="turnos_caja">
    <?php $periodoFiltro = ['modo' => 'rango', 'todo' => true, 'atajos' => ['Hoy', 'Ayer', '7 días', 'Este mes']]; include __DIR__ . '/_periodo.php'; ?>
    <label class="f-select<?= $f['cajero'] !== '' ? ' activo' : '' ?>"><svg class="i"><use href="#i-usuarios"/></svg>
      <select name="cajero" aria-label="Cajero" data-auto-enviar data-desplegable>
        <option value="">Todos los cajeros</option>
        <?php foreach ($cajeros as $c): $ced = (string) ($c->cedula ?? ''); ?>
        <option value="<?= $e($ced) ?>"<?= $f['cajero'] === $ced ? ' selected' : '' ?>><?= $e(trim(ucwords(mb_strtolower(trim(($c->nombre ?? '') . ' ' . ($c->apellido ?? '')))))) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <?php if ($hayFiltros): ?>
    <a href="index.php?action=turnos_caja" class="f-quitar"><svg class="i"><use href="#i-x"/></svg>Quitar filtros</a>
    <?php endif; ?>
  </form>

  <section class="mini-kpis" aria-label="Resumen del período">
    <div class="mk-oscuro"><span class="mk-ic"><svg class="i"><use href="#i-arqueo"/></svg></span><span>Turnos</span><b><?= count($turnos) ?></b><small><?= count($cerrados) === 1 ? '1 cerrado' : count($cerrados) . ' cerrados' ?></small></div>
    <div class="mk-ambar"><span class="mk-ic"><svg class="i"><use href="#i-reloj"/></svg></span><span>Caja abierta</span><b><?= $abierto ? $e(explode(' ', $nombreDe($abierto))[0]) : 'No' ?></b><small><?= $abierto ? 'desde ' . $e(substr((string) $abierto->fecha_apertura, 0, 10) === $hoy ? $hora(strtotime($abierto->fecha_apertura)) : $dia(strtotime($abierto->fecha_apertura))) : 'Ningún turno en curso' ?></small></div>
    <div><span class="mk-ic"><svg class="i"><use href="#i-dolar"/></svg></span><span>Fondo inicial</span><b><?= $usd($fondoUsd) ?></b><small><?= $bsMonto($fondoBs) ?></small></div>
    <div class="mk-claro"><span class="mk-ic"><svg class="i"><use href="#i-check"/></svg></span><span>Contado al cerrar</span><b><?= $usd($contUsd) ?></b><small><?= $bsMonto($contBs) ?></small></div>
  </section>

  <section class="tabla-caja cd-caja" aria-labelledby="tcx-t">
    <h3 id="tcx-t" class="sr-only">Turnos de caja</h3>
    <?php if ($turnos): ?>
    <ol class="cd-lista" data-paginar="10">
      <?php foreach ($turnos as $t):
        $esAbierto = ($t->estado ?? '') === 'abierto';
        $ta = strtotime((string) ($t->fecha_apertura ?? ''));
        $tc = !$esAbierto && !empty($t->fecha_cierre) ? strtotime((string) $t->fecha_cierre) : null;
        $viejo = $esAbierto && $ta && date('Y-m-d', $ta) < $hoy;
        $num = (int) ($t->numero_turno ?? 0);
        $nombre = $nombreDe($t);
        $notaA = trim((string) ($t->nota_apertura ?? ''));
        $notaC = trim((string) ($t->nota_cierre ?? ''));
        $horario = $ta ? $dia($ta) . ', ' . $hora($ta) : '';
        if ($tc) $horario .= ' a ' . (date('Y-m-d', $tc) === date('Y-m-d', $ta) ? $hora($tc) : $dia($tc) . ', ' . $hora($tc)); ?>
      <li>
        <details class="cd-compra tcx-turno">
          <summary>
            <span class="cd-num"><b><?= $e($nombre) ?></b><small>Turno <?= $num ?></small></span>
            <span class="tcx-hora"><svg class="i" aria-hidden="true"><use href="#i-reloj"/></svg><time datetime="<?= $ta ? $e(date('c', $ta)) : '' ?>"><?= $e($horario) ?></time></span>
            <span class="cd-chips"><?php if ($esAbierto): ?><span class="est pend"><?= $viejo ? 'Sin cerrar desde el ' . $e($dia($ta)) : 'Abierto' ?></span><?php else: ?><span class="est ok">Cerrado</span><?php endif; ?></span>
            <span class="tcx-dinero">
              <span><small>Fondo</small><b><?= $usd($t->fondo_usd ?? 0) ?></b></span>
              <svg class="i" aria-hidden="true"><use href="#i-chevron"/></svg>
              <span><small>Contado</small><b><?= $esAbierto ? 'En curso' : $usd($t->cierre_usd ?? 0) ?></b></span>
            </span>
            <svg class="i cd-flecha" aria-hidden="true"><use href="#i-chevron"/></svg>
          </summary>
          <div class="cd-cuerpo">
            <dl class="tcx-det">
              <div><dt>Cajero</dt><dd><?= $e($t->cedula_cajero ?? '') ?></dd></div>
              <div><dt>Tasa al abrir</dt><dd><?= $e(number_format((float) ($t->tasa_apertura ?? 0), 2, ',', '.')) ?> Bs por $</dd></div>
              <div><dt>Fondo inicial</dt><dd><?= $usd($t->fondo_usd ?? 0) ?> y <?= $bsMonto($t->fondo_bs ?? 0) ?></dd></div>
              <div><dt>Contado al cerrar</dt><dd><?= $esAbierto ? 'Se cuenta al cerrar el turno' : $usd($t->cierre_usd ?? 0) . ' y ' . $bsMonto($t->cierre_bs ?? 0) ?></dd></div>
              <div class="tcx-nota"><dt>Nota al abrir</dt><dd><?= $notaA !== '' ? $e($notaA) : 'Sin nota' ?></dd></div>
              <?php if (!$esAbierto): ?><div class="tcx-nota"><dt>Nota al cerrar</dt><dd><?= $notaC !== '' ? $e($notaC) : 'Sin nota' ?></dd></div><?php endif; ?>
            </dl>
            <footer class="cd-pie">
              <?php if ($esAbierto && $viejo): ?>
              <p class="tcx-aviso">Este turno quedó abierto. Hay que cerrarlo desde Punto de venta para que la caja pueda cobrar.</p>
              <?php endif; ?>
              <?php if ($esAbierto): ?><p class="tcx-aviso tcx-parcial"><b>Arqueo parcial</b>Lo cobrado hasta ahora, sin cerrar el turno.</p><?php endif; ?>
              <nav class="cd-acc" aria-label="<?= $esAbierto ? 'Arqueo parcial' : 'Acciones' ?> del turno <?= $num ?> de <?= $e($nombre) ?>">
                <?php $exportar = ['tipo' => 'cierre_caja', 'filtros' => ['cedula' => (string) ($t->cedula_cajero ?? ''), 'turno' => $num]]; include __DIR__ . '/_exportar.php'; ?>
              </nav>
            </footer>
          </div>
        </details>
      </li>
      <?php endforeach; ?>
    </ol>
    <?php else: ?>
    <div class="vacio-filtro">
      <?php if ($hayFiltros): ?>No hay turnos con estos filtros. <a href="index.php?action=turnos_caja" class="enlace">Ver todos los turnos</a><?php else: ?>Todavía no hay turnos de caja. Se abren desde Punto de venta.<?php endif; ?>
    </div>
    <?php endif; ?>
  </section>
</section>
<?php include __DIR__ . '/_cerrar.php'; ?>
