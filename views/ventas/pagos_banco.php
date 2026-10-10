<?php
$menu = 'pagos_banco';
$tituloPagina = 'Pagos por banco';
$pagosRecibidos = $pagosRecibidos ?? [];
$metodos = $metodos ?? [];
include __DIR__ . '/../admin/_abrir.php';
$f = ['desde' => $_GET['desde'] ?? date('Y-m-d'), 'hasta' => $_GET['hasta'] ?? date('Y-m-d'), 'banco' => $_GET['banco'] ?? '', 'metodo' => $_GET['metodo'] ?? ''];
$porBanco = [];
foreach ($pagosRecibidos as $p) {
    $b = $p->banco ?: 'Sin banco (efectivo)';
    $porBanco[$b]['n'] = ($porBanco[$b]['n'] ?? 0) + 1;
    $porBanco[$b]['usd'] = ($porBanco[$b]['usd'] ?? 0) + (($p->moneda ?? 'Bs') === 'USD' ? $p->monto : $p->monto / ($p->tasa_cambio ?: $tasa));
    $porBanco[$b]['bs'] = ($porBanco[$b]['bs'] ?? 0) + (($p->moneda ?? 'Bs') === 'USD' ? 0 : $p->monto);
}
$visibles = $f['banco'] ? array_filter($pagosRecibidos, fn($p) => ($p->banco ?: 'Sin banco (efectivo)') === $f['banco']) : $pagosRecibidos;
$url = fn($banco) => 'index.php?' . http_build_query(['action' => 'pagos_banco', 'desde' => $f['desde'], 'hasta' => $f['hasta'], 'metodo' => $f['metodo'], 'banco' => $banco]);
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Ventas</small><h2>Pagos por banco</h2><small>Pagos ya verificados. Úsalo para cuadrar con el estado de cuenta de cada banco.</small></div>
    <div class="cab-acc"><a href="index.php?action=exportar_excel&tipo=pagos_banco" class="btn btn-sec"><svg class="i"><use href="#i-archivo"/></svg>Excel</a></div>
  </header>

  <form class="herr" method="GET" action="index.php">
    <input type="hidden" name="action" value="pagos_banco">
    <label class="fecha-rango">Desde <input type="date" name="desde" value="<?= $e($f['desde']) ?>" aria-label="Desde"> hasta <input type="date" name="hasta" value="<?= $e($f['hasta']) ?>" aria-label="Hasta"></label>
    <select class="selector" name="metodo" aria-label="Método de pago">
      <option value="">Todos los métodos</option>
      <?php foreach ($metodos as $m): ?><option value="<?= (int) $m->id ?>" <?= $f['metodo'] == $m->id ? 'selected' : '' ?>><?= $e($m->nombre) ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-sec">Filtrar</button>
  </form>

  <section class="bancos" aria-label="Total por banco">
    <a href="<?= $e($url('')) ?>" class="banco-tot" aria-current="<?= $f['banco'] === '' ? 'true' : 'false' ?>"><span>Todos los bancos</span><b><?= $usd(array_sum(array_column($porBanco, 'usd'))) ?></b><small><?= count($pagosRecibidos) ?> pagos</small></a>
    <?php foreach ($porBanco as $banco => $t): ?>
      <a href="<?= $e($url($banco)) ?>" class="banco-tot" aria-current="<?= $f['banco'] === $banco ? 'true' : 'false' ?>"><span><?= $e($banco) ?></span><b><?= $usd($t['usd']) ?></b><small><?= $t['n'] ?> pagos<?= $t['bs'] ? ', ' . number_format($t['bs'], 2) . ' Bs' : '' ?></small></a>
    <?php endforeach; ?>
  </section>

  <section class="tabla-caja" aria-label="Pagos">
    <div class="tabla-scroll">
      <table class="tabla-resp">
        <thead><tr><th>Fecha</th><th>Cliente</th><th>Método</th><th class="c-media">Banco</th><th>Referencia</th><th class="num">Monto</th></tr></thead>
        <tbody>
        <?php foreach ($visibles as $p): ?>
          <tr>
            <td class="t-tit"><div class="t-nombre"><b><?= date('d/m/Y', strtotime($p->fecha_pago)) ?></b><span><?= date('g:i a', strtotime($p->fecha_pago)) ?>, venta #<?= str_pad($p->id_venta, 4, '0', STR_PAD_LEFT) ?></span></div></td>
            <td data-label="Cliente"><div class="t-nombre"><b><?= $e($p->cliente) ?></b><span class="si-estrecho"><?= $e($p->banco ?: 'Efectivo') ?></span></div></td>
            <td data-label="Método"><span class="chip"><?= $e($p->metodo) ?></span></td>
            <td data-label="Banco" class="c-media"><?= $e($p->banco ?: 'Efectivo') ?></td>
            <td data-label="Referencia" class="cod"><?= $e($p->referencia ?: 'No aplica') ?></td>
            <td class="num" data-label="Monto"><b><?= ($p->moneda ?? 'Bs') === 'USD' ? $usd($p->monto) : number_format($p->monto, 2) . ' Bs' ?></b></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (!$visibles): ?><div class="vacio-filtro">No hay pagos verificados con estos filtros.</div><?php endif; ?>
  </section>
</section>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>
