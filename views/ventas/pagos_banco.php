<?php
$menu = 'pagos_banco';
$tituloPagina = 'Pagos por banco';
$pagosRecibidos = $pagosRecibidos ?? [];
$metodos = $metodos ?? [];
include __DIR__ . '/../admin/_abrir.php';

$f = [
    'desde'  => $_GET['desde'] ?? date('Y-m-d'),
    'hasta'  => $_GET['hasta'] ?? date('Y-m-d'),
    'banco'  => $_GET['banco'] ?? '',
    'metodo' => $_GET['metodo'] ?? ''
];

// Agrupación por Banco y tipo de fondo
$porBanco = [];
foreach ($pagosRecibidos as $p) {
    $esEfectivo = stripos($p->metodo ?? '', 'efectivo') !== false || empty($p->requiere_referencia);
    $nombreTarjeta = $esEfectivo ? 'Efectivo en caja' : ($p->banco ?: 'Sin banco asignado');
    
    $montoUsd = ($p->moneda ?? 'Bs') === 'USD' ? (float)$p->monto : (float)$p->monto / ((float)$p->tasa_cambio ?: $tasa);
    $montoBs  = ($p->moneda ?? 'Bs') === 'USD' ? 0 : (float)$p->monto;

    if (!isset($porBanco[$nombreTarjeta])) {
        $porBanco[$nombreTarjeta] = ['n' => 0, 'usd' => 0, 'bs' => 0, 'banco_real' => $p->banco, 'es_efectivo' => $esEfectivo];
    }

    $porBanco[$nombreTarjeta]['n']++;
    $porBanco[$nombreTarjeta]['usd'] += $montoUsd;
    $porBanco[$nombreTarjeta]['bs']  += $montoBs;
}

$url = fn($banco) => 'index.php?' . http_build_query([
    'action' => 'pagos_banco',
    'desde'  => $f['desde'],
    'hasta'  => $f['hasta'],
    'metodo' => $f['metodo'],
    'banco'  => $banco
]);
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt">
      <small>Ventas</small>
      <h2>Pagos por banco</h2>
      <small>Pagos ya verificados. Conciliación de cuentas de Pago Móvil, Transferencias y Caja.</small>
    </div>
    <div class="cab-acc">
      <a href="index.php?action=exportar_excel&tipo=pagos_banco&<?= http_build_query($f) ?>" class="btn btn-sec">
        <svg class="i"><use href="#i-archivo"/></svg>Excel
      </a>
    </div>
  </header>

  <form class="herr" method="GET" action="index.php" style="flex-wrap: wrap; gap: 0.5rem; display: flex; align-items: center;">
    <input type="hidden" name="action" value="pagos_banco">
    
    <label class="fecha-rango">
      Desde <input type="date" name="desde" value="<?= $e($f['desde']) ?>" aria-label="Desde"> 
      hasta <input type="date" name="hasta" value="<?= $e($f['hasta']) ?>" aria-label="Hasta">
    </label>

    <select class="selector" name="metodo" aria-label="Método de pago">
      <option value="">Todos los métodos</option>
      <?php foreach ($metodos as $m): ?>
        <option value="<?= (int)$m->id ?>" <?= $f['metodo'] == $m->id ? 'selected' : '' ?>>
          <?= $e($m->nombre) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button type="submit" class="btn btn-pri">Filtrar</button>
    <?php if ($f['desde'] !== date('Y-m-d') || $f['hasta'] !== date('Y-m-d') || $f['metodo'] !== '' || $f['banco'] !== ''): ?>
      <a href="index.php?action=pagos_banco" class="btn btn-sec" style="text-decoration:none;">Limpiar</a>
    <?php endif; ?>
  </form>

  <!-- Tarjetas de desglose de saldo por Banco receptor / Efectivo -->
  <section class="bancos" aria-label="Total por banco">
    <a href="<?= $e($url('')) ?>" class="banco-tot" aria-current="<?= $f['banco'] === '' ? 'true' : 'false' ?>">
      <span>Todos los fondos</span>
      <b><?= $usd(array_sum(array_column($porBanco, 'usd'))) ?></b>
      <small><?= count($pagosRecibidos) ?> pagos conciliados</small>
    </a>

    <?php foreach ($porBanco as $titulo => $t): ?>
      <a href="<?= $e($url($t['es_efectivo'] ? '' : $t['banco_real'])) ?>" class="banco-tot" aria-current="<?= $f['banco'] === $t['banco_real'] ? 'true' : 'false' ?>">
        <span><?= $e($titulo) ?></span>
        <b><?= $usd($t['usd']) ?></b>
        <small>
          <?= $t['n'] ?> pagos<?= $t['bs'] > 0 ? ', ' . number_format($t['bs'], 2) . ' Bs' : '' ?>
        </small>
      </a>
    <?php endforeach; ?>
  </section>

  <!-- Tabla de movimientos conciliados -->
  <section class="tabla-caja" aria-label="Pagos">
    <div class="tabla-scroll">
      <table class="tabla-resp">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Cliente</th>
            <th>Método</th>
            <th class="c-media">Banco Receptor</th>
            <th>Referencia</th>
            <th>Custodio / Responsable</th>
            <th class="num">Monto</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($pagosRecibidos as $p): 
          $esEf = stripos($p->metodo ?? '', 'efectivo') !== false || empty($p->requiere_referencia);
        ?>
          <tr>
            <td class="t-tit">
              <div class="t-nombre">
                <b><?= date('d/m/Y', strtotime($p->fecha_pago)) ?></b>
                <span><?= date('g:i a', strtotime($p->fecha_pago)) ?>, venta #<?= str_pad($p->id_venta, 4, '0', STR_PAD_LEFT) ?></span>
              </div>
            </td>
            <td data-label="Cliente">
              <div class="t-nombre">
                <b><?= $e($p->cliente) ?></b>
                <span class="si-estrecho"><?= $e($esEf ? 'Efectivo' : $p->banco) ?></span>
              </div>
            </td>
            <td data-label="Método"><span class="chip"><?= $e($p->metodo) ?></span></td>
            <td data-label="Banco" class="c-media">
              <?php if ($esEf): ?>
                <span class="muted">Caja Tienda</span>
              <?php else: ?>
                <strong><?= $e($p->banco ?: 'Sin banco') ?></strong>
              <?php endif; ?>
            </td>
            <td data-label="Referencia" class="cod">
              <?= $esEf ? 'No aplica' : $e($p->referencia ?: 'Sin referencia') ?>
            </td>
            <td data-label="Custodio / Responsable">
              <b><?= $e($p->custodio_cajero ?? 'Cajero') ?></b><br>
              <small class="muted"><?= ($p->origen_venta ?? '') === 'tienda_fisica' ? 'Tienda física' : 'Venta online' ?></small>
            </td>
            <td class="num" data-label="Monto">
              <b><?= ($p->moneda ?? 'Bs') === 'USD' ? $usd($p->monto) : number_format($p->monto, 2) . ' Bs' ?></b>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (!$pagosRecibidos): ?>
      <div class="vacio-filtro" style="padding: 2.5rem; text-align: center; color: #64748b;">
        No hay pagos verificados con las fechas o métodos seleccionados.
      </div>
    <?php endif; ?>
  </section>
</section>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>