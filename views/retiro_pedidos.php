<?php
$menu = 'retiro_pedidos';
$tituloPagina = 'Retiro de pedidos';
$ventas = $ventas ?? $pedidos ?? [];
include __DIR__ . '/admin/_abrir.php';

$tab = ($_GET['tab'] ?? 'pendiente') === 'entregado' ? 'entregado' : 'pendiente';

// Filtrar según el estado real de la BD
$porRetirar = array_filter($ventas, fn($v) => in_array($v->estado, ['pagado', 'por_retirar', 'completado']));
$entregados = array_filter($ventas, fn($v) => in_array($v->estado, ['retirado', 'entregado']));

// Si en tu flujo actual las ventas físicas se marcan 'retirado' pero quieres verlas en pendiente si no hay por_retirar:
if (empty($porRetirar) && $tab === 'pendiente') {
    $lista = array_filter($ventas, fn($v) => in_array($v->estado, ['pagado', 'por_retirar', 'completado']));
} else {
    $lista = $tab === 'pendiente' ? $porRetirar : $entregados;
}
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Ventas</small><h2>Retiro de pedidos</h2></div>
    <div class="cab-acc">
      <a href="index.php?action=exportar_pdf&tipo=retiros" target="_blank" class="btn btn-sec"><svg class="i"><use href="#i-archivo"/></svg>PDF</a>
      <a href="index.php?action=exportar_excel&tipo=retiros" class="btn btn-sec"><svg class="i"><use href="#i-archivo"/></svg>Excel</a>
    </div>
  </header>

  <div class="filtros">
    <nav class="f-grupo" aria-label="Estado del retiro">
      <a href="index.php?action=retiro_pedidos" class="f-op" aria-current="<?= $tab === 'pendiente' ? 'page' : 'false' ?>">
        Por retirar<?php if ($porRetirar): ?><span class="f-cuenta"><?= count($porRetirar) ?></span><?php endif; ?>
      </a>
      <a href="index.php?action=retiro_pedidos&tab=entregado" class="f-op" aria-current="<?= $tab === 'entregado' ? 'page' : 'false' ?>">
        Entregados<?php if ($entregados): ?><span class="f-cuenta"><?= count($entregados) ?></span><?php endif; ?>
      </a>
    </nav>
  </div>

  <section class="tabla-caja" aria-label="Pedidos">
    <div class="tabla-scroll">
      <table class="tabla-resp" data-paginar="10">
        <thead>
          <tr>
            <th>Pedido</th>
            <th>Cliente</th>
            <th class="c-media">Pago</th>
            <th class="num">Total</th>
            <th>Entrega</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($lista as $v): 
          $num = '#' . (string)($v->numero_orden ?? $v->id ?? '');
          $nombreCli = $v->cliente_nombre ?? $v->nombre_cliente ?? $v->cliente ?? 'Consumidor Final';
          $telCli = $v->cliente_telefono ?? $v->telefono ?? '';
          $metodo = $v->metodo_pago ?? $v->nombre_metodo ?? $v->pago ?? 'Efectivo';
          $totalV = (float)($v->total ?? 0);
          $tasaV = (float)($v->tasa_cambio ?? $tasa ?? 1);
          $totalBs = $v->total_bs ?? ($totalV * $tasaV);
        ?>
          <tr>
            <td class="t-tit">
              <div class="t-nombre">
                <b><?= $e($num) ?></b>
                <span><?= !empty($v->fecha) ? date('d/m, g:i a', strtotime($v->fecha)) : '—' ?></span>
              </div>
            </td>
            <td data-label="Cliente">
              <div class="t-nombre">
                <b><?= $e($nombreCli) ?></b>
                <?php if (!empty($telCli)): ?>
                  <span class="tel-cli"><?= $e($telCli) ?></span>
                <?php endif; ?>
              </div>
            </td>
            <td data-label="Pago" class="c-media centro">
              <span class="chip"><?= $e($metodo) ?></span>
            </td>
            <td class="num" data-label="Total">
              <b><?= $usd($totalV) ?></b><br>
              <span class="muted"><?= number_format($totalBs, 2, ',', '.') ?> Bs</span>
            </td>
            <td class="t-acc centro">
              <?php if (in_array($v->estado, ['pagado', 'por_retirar', 'completado'])): ?>
                <div class="acc">
                  <button type="button" class="btn btn-ok btn-peq" data-abrir-modal="modal-entrega" data-registro="<?= $e(json_encode(['id' => ($v->numero_orden ?? $v->id), 'cliente' => $nombreCli, 'pedido' => $num])) ?>">
                    <svg class="i"><use href="#i-bolsa"/></svg>Entregar
                  </button>
                </div>
              <?php else: ?>
                <span class="entrega-ok">
                  <b>Entregado</b>
                  <?= $e(trim((!empty($v->fecha_entrega) ? date('d/m, g:i a', strtotime($v->fecha_entrega)) . '. ' : '') . (!empty($v->retirado_por) ? 'Retiró ' . $v->retirado_por . '.' : 'Retiró el cliente.'))) ?>
                </span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (!$lista): ?>
      <div class="vacio-filtro">No hay pedidos en esta lista.</div>
    <?php endif; ?>
  </section>
</section>

<dialog class="modal chico" id="modal-entrega" aria-labelledby="me-titulo">
  <form method="POST" action="index.php?action=procesar_retiro">
    <input type="hidden" name="numero_orden" id="me-id">
    <header class="modal-cab">
      <span class="modal-ic"><svg class="i"><use href="#i-bolsa"/></svg></span>
      <div>
        <h3 id="me-titulo">Entregar pedido</h3>
        <p>Confirma quién se lo lleva.</p>
      </div>
      <button type="button" class="ic-btn" data-cerrar aria-label="Cerrar"><svg class="i"><use href="#i-x"/></svg></button>
    </header>
    <div class="modal-cuerpo" style="display:flex;flex-direction:column;gap:12px">
      <div class="campo"><span class="lbl">¿Quién lo retira?</span>
        <div class="opciones" role="radiogroup" aria-label="Quién retira">
          <label class="opcion"><input type="radio" name="retira" value="cliente" checked><span>El cliente</span></label>
          <label class="opcion"><input type="radio" name="retira" value="otro"><span>Otra persona</span></label>
        </div>
      </div>
      <div class="form-grid entrega-otro">
        <div class="campo"><label for="me-nombre">Nombre de quien retira</label><input id="me-nombre" name="retirado_por"></div>
        <div class="campo"><label for="me-cedula">Cédula</label><input id="me-cedula" name="retirado_cedula" inputmode="numeric" placeholder="V-12345678"></div>
      </div>
      <label class="check-confirma"><input type="checkbox" name="revisado" value="1" required><span>Revisé los productos con quien retira y están completos</span></label>
    </div>
    <footer class="modal-pie">
      <button type="button" class="btn btn-sec" data-cerrar>Cancelar</button>
      <button type="submit" class="btn btn-ok">Confirmar entrega</button>
    </footer>
  </form>
</dialog>

<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('[data-abrir-modal="modal-entrega"]').forEach(function(btn) {
    btn.addEventListener('click', function() {
      try {
        var data = JSON.parse(this.getAttribute('data-registro'));
        var modal = document.getElementById('modal-entrega');
        if (modal) {
          modal.querySelector('#me-id').value = data.id;
          var tit = modal.querySelector('#me-titulo');
          if (tit) tit.textContent = 'Entregar pedido ' + data.pedido;
          modal.showModal();
        }
      } catch(e) {}
    });
  });
});
</script>

<?php include __DIR__ . '/admin/_cerrar.php'; ?>