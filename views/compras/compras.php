<?php
$menu = 'compras';
$tituloPagina = 'Compras';
$compras = $compras ?? [];
include __DIR__ . '/../admin/_abrir.php';
$f = ['desde' => $_GET['desde'] ?? date('Y-m-01'), 'hasta' => $_GET['hasta'] ?? date('Y-m-d'), 'estado' => $_GET['estado'] ?? ''];
$lista = $f['estado'] ? array_filter($compras, fn($c) => $c->estado === $f['estado']) : $compras;
$recibidas = array_filter($compras, fn($c) => $c->estado === 'recibido');
$total = array_sum(array_map(fn($c) => (float)$c->total, $recibidas));
$url = fn($estado) => 'index.php?' . http_build_query(array_filter(['action' => 'compras', 'desde' => $f['desde'], 'hasta' => $f['hasta'], 'estado' => $estado]));
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Compras</small><h2>Compras</h2></div>
    <div class="cab-acc">
      <a href="index.php?action=exportar_excel&tipo=compras" class="btn btn-sec"><svg class="i"><use href="#i-archivo"/></svg>Excel</a>
      <a href="index.php?action=nueva_compra" class="btn btn-pri"><svg class="i"><use href="#i-mas"/></svg>Registrar compra</a>
    </div>
  </header>
  <form class="filtros" method="GET" action="index.php" aria-label="Filtros">
    <input type="hidden" name="action" value="compras">
    <?php if ($f['estado']): ?><input type="hidden" name="estado" value="<?= $e($f['estado']) ?>"><?php endif; ?>
    <label class="buscar"><svg class="i"><use href="#i-buscar"/></svg><input type="search" placeholder="Buscar por n.º de factura o proveedor" aria-label="Buscar compra" data-filtro-tabla="tabla-compras" data-filtro-vacio="vacio-compras"></label>
    <?php $periodoFiltro = ['modo' => 'rango', 'defecto' => 'Este mes']; include __DIR__ . '/../admin/_periodo.php'; ?>
    <nav class="f-grupo" aria-label="Filtrar por estado">
      <a href="<?= $e($url('')) ?>" class="f-op" aria-current="<?= $f['estado'] === '' ? 'page' : 'false' ?>">Todas</a>
      <a href="<?= $e($url('recibido')) ?>" class="f-op" aria-current="<?= $f['estado'] === 'recibido' ? 'page' : 'false' ?>">Recibidas</a>
      <a href="<?= $e($url('anulado')) ?>" class="f-op" aria-current="<?= $f['estado'] === 'anulado' ? 'page' : 'false' ?>">Anuladas</a>
    </nav>
    <?php if ($f['estado'] !== '' || isset($_GET['desde']) || isset($_GET['hasta'])): ?>
    <a href="index.php?action=compras" class="f-quitar"><svg class="i"><use href="#i-x"/></svg>Quitar filtros</a>
    <?php endif; ?>
  </form>
  <p class="sub-resumen"><?= count($recibidas) ?> compras recibidas en el periodo por <b><?= $usd($total) ?></b> (<?= $bs($total) ?>).</p>
  <section class="tabla-caja" aria-label="Listado de compras">
    <div class="tabla-scroll">
      <table class="tabla-resp" id="tabla-compras" data-paginar="10">
        <thead><tr><th>Compra</th><th>Proveedor</th><th class="c-media">Destino</th><th class="num c-media">Productos</th><th class="num">Total</th><th>Estado</th><th class="num"></th></tr></thead>
        <tbody>
        <?php foreach ($lista as $c): 
          $destino = $c->destino_stock === 'online' ? 'Online' : 'Tienda física'; 
          $infoPago = !empty($c->metodo_pago) ? $c->metodo_pago . (!empty($c->banco) ? ' (' . $c->banco . ')' : '') : '';
        ?>
          <tr>
            <td class="t-tit">
              <div class="t-nombre">
                <b>#<?= str_pad((string)$c->id, 4, '0', STR_PAD_LEFT) ?></b>
                <span><?= date('d/m/Y', strtotime($c->fecha_compra)) ?><?= $c->numero_factura ? ', fact. ' . $e($c->numero_factura) : '' ?></span>
                <?php if (!empty($c->comprado_por)): ?>
                  <small style="color: #64748b;">Compró: <?= $e($c->comprado_por) ?></small>
                <?php endif; ?>
              </div>
            </td>
            <td data-label="Proveedor">
              <div class="t-nombre">
                <b><?= $e($c->razon_social) ?></b>
                <span class="si-estrecho"><?= $destino ?>, <?= (int) ($c->total_productos ?? 0) ?> productos</span>
                <?php if ($infoPago): ?>
                  <small style="color: #64748b;"><?= $e($infoPago) ?></small>
                <?php endif; ?>
              </div>
            </td>
            <td data-label="Destino" class="c-media"><span class="chip"><?= $destino ?></span></td>
            <td class="num c-media" data-label="Productos"><?= (int) ($c->total_productos ?? 0) ?></td>
            <td class="num" data-label="Total"><b<?= $c->estado === 'anulado' ? ' style="text-decoration:line-through;color:var(--texto-3)"' : '' ?>><?= $usd($c->total) ?></b></td>
            <td data-label="Estado"><span class="est <?= $c->estado === 'anulado' ? 'agot' : 'ok' ?>"><?= $c->estado === 'anulado' ? 'Anulada' : 'Recibida' ?></span></td>
            <td class="t-acc"><div class="acc"><a href="index.php?action=detalle_compra&id=<?= (int) $c->id ?>" class="ic-btn" aria-label="Ver compra #<?= (int) $c->id ?>"><svg class="i"><use href="#i-ojo"/></svg></a></div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="vacio-filtro" id="vacio-compras" <?= $lista ? 'hidden' : '' ?>>No hay compras con estos filtros.</div>
  </section>
</section>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>