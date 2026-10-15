<?php
$menu = 'kardex';
$tituloPagina = 'Movimientos';
$movimientos = $movimientos ?? [];
include __DIR__ . '/../admin/_abrir.php';
$f = ['desde' => $_GET['desde'] ?? '', 'hasta' => $_GET['hasta'] ?? '', 'tipo' => $_GET['tipo'] ?? ''];
$tipos = ['compra' => ['compra', 'Compra'], 'venta_tienda' => ['venta', 'Venta en tienda'], 'venta_online' => ['venta', 'Venta online'], 'ajuste' => ['ajuste', 'Ajuste por conteo'], 'merma' => ['merma', 'Merma'], 'transferencia' => ['transf', 'Transferencia'], 'anulacion_compra' => ['merma', 'Compra anulada']];

// Modificado: Pastilla de stock unificada y simple
$pastilla = function ($n, $min) {
    $agotado = (int) $n === 0;
    $bajo = !$agotado && $n < $min;
    $titulo = $agotado ? 'Agotado' : ($bajo ? "Por debajo del mínimo ($min)" : '');
    return '<span class="canal-stock' . ($agotado ? ' agotado' : ($bajo ? ' bajo' : '')) . '" title="' . $titulo . '"><svg class="i"><use href="#i-caja"/></svg><b class="cifra">' . (int) $n . '</b><span>unidades</span></span>';
};
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Inventario</small><h2>Movimientos</h2></div>
    <div class="cab-acc">
      <a href="index.php?action=ajustes_inventario" class="btn btn-sec"><svg class="i"><use href="#i-ajuste"/></svg>Nuevo ajuste</a>
      <a href="index.php?<?= $e(http_build_query(['action' => 'exportar_excel', 'tipo' => 'kardex', 'desde' => $f['desde'], 'hasta' => $f['hasta']])) ?>" class="btn btn-sec"><svg class="i"><use href="#i-archivo"/></svg>Excel</a>
    </div>
  </header>
  <form class="filtros" method="GET" action="index.php" aria-label="Filtros">
    <input type="hidden" name="action" value="kardex">
    <label class="buscar"><svg class="i"><use href="#i-buscar"/></svg><input type="search" placeholder="Buscar producto" aria-label="Buscar producto" data-filtro-tabla="tabla-mov" data-filtro-vacio="vacio-mov"></label>
    <?php $periodoFiltro = ['modo' => 'rango', 'todo' => true]; include __DIR__ . '/../admin/_periodo.php'; ?>
    <label class="f-select<?= $f['tipo'] !== '' ? ' activo' : '' ?>"><svg class="i"><use href="#i-lista"/></svg>
      <select name="tipo" aria-label="Tipo de movimiento" data-auto-enviar data-desplegable>
        <option value="">Todos los movimientos</option>
        <?php foreach ($tipos as $k => [, $t]): ?><option value="<?= $k ?>"<?= $f['tipo'] === $k ? ' selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
      </select>
    </label>
    <?php if ($f['tipo'] !== '' || isset($_GET['desde']) || isset($_GET['hasta'])): ?>
    <a href="index.php?action=kardex" class="f-quitar"><svg class="i"><use href="#i-x"/></svg>Quitar filtros</a>
    <?php endif; ?>
  </form>
  <section class="tabla-caja" aria-label="Movimientos de inventario">
    <div class="tabla-scroll">
      <table class="tabla-resp" id="tabla-mov" data-paginar="10">
        <thead><tr><th>Fecha</th><th>Producto</th><th>Movimiento</th><th class="num">Cantidad</th><th class="num">Stock que queda</th><th class="c-media">Origen</th><th class="c-media">Usuario</th></tr></thead>
        <tbody>
        <?php foreach ($movimientos as $m):
            $tm = $tipos[$m->tipo_movimiento] ?? ['ajuste', $m->tipo_movimiento];
            $transf = $m->tipo_movimiento === 'transferencia';
            $num = str_pad($m->id_referencia ?? 0, 4, '0', STR_PAD_LEFT);
            $origen = in_array($m->tipo_movimiento, ['venta_tienda', 'venta_online']) && $m->id_referencia ? '<a href="index.php?action=detalle_venta&id=' . (int) $m->id_referencia . '" class="enlace">Venta #' . $num . '</a>'
                : (in_array($m->tipo_movimiento, ['compra', 'anulacion_compra']) && $m->id_referencia ? '<a href="index.php?action=detalle_compra&id=' . (int) $m->id_referencia . '" class="enlace">Compra #' . $num . '</a>'
                : $e($m->observacion ?: 'Ajuste'));
            $min = (int) ($m->stock_minimo ?? 5);
            $stockTotal = (int) ($m->stock_resultante ?? 0);
        ?>
          <tr>
            <td data-label="Fecha" style="white-space:nowrap"><?= date('d/m, g:i a', strtotime($m->fecha)) ?></td>
            <td class="t-tit"><div class="prod"><?php if (!empty($m->imagen)): ?><img class="foto chica" src="public/uploads/<?= $e($m->imagen) ?>" alt="" width="36" height="36" loading="lazy"><?php endif; ?><div><b><?= $e($m->nombre_producto) ?></b><span class="si-estrecho"><?= strip_tags($origen) ?>, <?= $e($m->usuario ?? '') ?></span></div></div></td>
            <td data-label="Movimiento"><span class="mov <?= $tm[0] ?>"><?= $tm[1] ?></span></td>
            <td class="num" data-label="Cantidad"><?= $transf ? '<span class="cifra">' . abs($m->cantidad) . '</span>' : '<span class="' . ($m->cantidad > 0 ? 'cant-mas' : 'cant-menos') . ' cifra">' . ($m->cantidad > 0 ? '+' : '−') . abs($m->cantidad) . '</span>' ?></td>
            <td class="num" data-label="Stock que queda"><span class="stock-pila"><?= $pastilla($stockTotal, $min) ?></span></td>
            <td data-label="Origen" class="c-media"><?= $origen ?></td>
            <td data-label="Usuario" class="c-media"><?= $e($m->usuario ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="vacio-filtro" id="vacio-mov" <?= $movimientos ? 'hidden' : '' ?>>No hay movimientos con estos filtros.</div>
  </section>
</section>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>