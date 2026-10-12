<?php
$menu = 'retiro_pedidos';
$tituloPagina = 'Retiro de pedidos';
$ventas = $ventas ?? [];
include __DIR__ . '/admin/_abrir.php';
$tab = ($_GET['tab'] ?? 'pendiente') === 'entregado' ? 'entregado' : 'pendiente';
$porRetirar = array_filter($ventas, fn($v) => $v->estado === 'pagado');
$entregados = array_filter($ventas, fn($v) => in_array($v->estado, ['retirado', 'entregado']));
$lista = $tab === 'pendiente' ? $porRetirar : $entregados;
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
    <a href="index.php?action=retiro_pedidos" class="f-op" aria-current="<?= $tab === 'pendiente' ? 'page' : 'false' ?>">Por retirar<?php if ($porRetirar): ?><span class="f-cuenta"><?= count($porRetirar) ?></span><?php endif; ?></a>
    <a href="index.php?action=retiro_pedidos&tab=entregado" class="f-op" aria-current="<?= $tab === 'entregado' ? 'page' : 'false' ?>">Entregados</a>
  </nav>
  </div>
  <section class="tabla-caja" aria-label="Pedidos">
    <div class="tabla-scroll">
      <table class="tabla-resp" data-paginar="10">
        <thead><tr><th>Pedido</th><th>Cliente</th><th class="c-media">Pago</th><th class="num">Total</th><th>Entrega</th></tr></thead>
        <tbody>
        <?php foreach ($lista as $v): $num = '#' . str_pad($v->id, 4, '0', STR_PAD_LEFT); ?>
          <tr>
            <td class="t-tit"><div class="t-nombre"><b><?= $num ?></b><span>Pagado <?= date('d/m, g:i a', strtotime($v->fecha)) ?></span></div></td>
            <td data-label="Cliente"><div class="t-nombre"><b><?= $e($v->nombre_cliente) ?></b><?php if (!empty($v->telefono)): ?><span class="tel-cli"><?= $e($v->telefono) ?></span><?php endif; ?></div></td>
            <td data-label="Pago" class="c-media centro"><span class="chip"><?= $e($v->nombre_metodo ?? 'N/A') ?></span></td>
            <td class="num" data-label="Total"><b><?= $usd($v->total) ?></b><br><span class="muted"><?= number_format($v->total_bs ?? $v->total * $tasa, 2, ',', '.') ?> Bs</span></td>
            <td class="t-acc centro">
              <?php if ($v->estado === 'pagado'): ?>
                <div class="acc"><button type="button" class="btn btn-ok btn-peq" data-abrir-modal="modal-entrega" data-registro="<?= $e(json_encode(['id' => $v->id, 'cliente' => $v->nombre_cliente, 'pedido' => $num])) ?>"><svg class="i"><use href="#i-bolsa"/></svg>Entregar</button></div>
              <?php else: ?>
                <span class="entrega-ok"><b>Entregado</b><?= $e(trim(($v->fecha_entrega ?? '') ? date('d/m, g:i a', strtotime($v->fecha_entrega)) . '. ' : '') . (!empty($v->retirado_por) ? 'Retiró ' . $v->retirado_por . '.' : 'Retiró el cliente.')) ?></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (!$lista): ?><div class="vacio-filtro">No hay pedidos en esta lista.</div><?php endif; ?>
  </section>
</section>

<dialog class="modal chico" id="modal-entrega" aria-labelledby="me-titulo">
  <form method="GET" action="index.php">
    <input type="hidden" name="action" value="procesar_retiro">
    <input type="hidden" name="id">
    <header class="modal-cab"><span class="modal-ic"><svg class="i"><use href="#i-bolsa"/></svg></span><div><h3 id="me-titulo">Entregar pedido</h3><p>Confirma quién se lo lleva.</p></div><button type="button" class="ic-btn" data-cerrar aria-label="Cerrar"><svg class="i"><use href="#i-x"/></svg></button></header>
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
<?php include __DIR__ . '/admin/_cerrar.php'; ?>
