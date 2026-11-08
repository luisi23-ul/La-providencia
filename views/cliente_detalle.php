<?php
$menu = 'lista_clientes';
$cliente = $cliente ?? null;
$compras = $compras ?? [];
$tituloPagina = 'Compras del cliente';
include __DIR__ . '/admin/_abrir.php';

$f = [
    'cedula' => (string) ($cliente->cedula ?? ($_GET['cedula'] ?? '')),
    'desde'  => $_GET['desde'] ?? '',
    'hasta'  => $_GET['hasta'] ?? '',
    'estado' => $_GET['estado'] ?? '',
];
$nombre = trim((string) ($cliente->nombre_razon_social ?? '')) ?: 'Cliente';
$datos = array_filter([
    ['Cédula', trim((string) ($cliente->cedula ?? ''))],
    ['Teléfono', trim((string) ($cliente->telefono ?? ''))],
    ['Correo', trim((string) ($cliente->correo ?? ''))],
], fn($d) => $d[1] !== '' && !es_vacio($d[1]) && !str_ends_with($d[1], '@providencia.local'));

$estados = [
    'pendiente'     => ['pend', 'Pendiente de pago'],
    'por_verificar' => ['pend', 'Por verificar'],
    'pagado'        => ['ok', 'Pagada, por retirar'],
    'retirado'      => ['ok', 'Retirada'],
    'entregado'     => ['ok', 'Retirada'],
    'completado'    => ['ok', 'Completada'],
    'cancelado'     => ['agot', 'Anulada'],
    'anulado'       => ['agot', 'Anulada'],
];
$saldoDe = fn($c) => max(0, (float) ($c->total ?? 0) - (float) ($c->pagado ?? 0));
$validas = array_filter($compras, fn($c) => !in_array($c->estado ?? '', ['cancelado', 'anulado'], true));
$total = array_sum(array_map(fn($c) => (float) ($c->total ?? 0), $validas));
$saldo = array_sum(array_map($saldoDe, $validas));
$conSaldo = count(array_filter($validas, fn($c) => $saldoDe($c) > 0.004));
$n = count($validas);
$fechaCorta = function ($f) {
    $t = strtotime((string) $f);
    if (!$t) return '';
    $m = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    return date('j', $t) . ' ' . $m[date('n', $t) - 1] . ' ' . date('Y', $t) . ', ' . date('g:i a', $t);
};
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt">
      <small>Cliente</small>
      <div class="titulo-atras"><a href="index.php?action=lista_clientes" class="atras" aria-label="Volver a Clientes"><svg class="i"><use href="#i-volver"/></svg></a><h2><?= $e($nombre) ?></h2></div>
      <?php if ($datos): ?>
      <dl class="cd-datos">
        <?php foreach ($datos as [$t, $v]): ?><div><dt><?= $t ?></dt><dd><?= $e($v) ?></dd></div><?php endforeach; ?>
      </dl>
      <?php endif; ?>
    </div>
    <div class="cab-acc">
      <?php $exportar = ['tipo' => 'cliente', 'filtros' => $f]; include __DIR__ . '/admin/_exportar.php'; ?>
    </div>
  </header>

  <form class="filtros" method="GET" action="index.php" aria-label="Filtros">
    <input type="hidden" name="action" value="cliente_detalle">
    <input type="hidden" name="cedula" value="<?= $e($f['cedula']) ?>">
    <?php $periodoFiltro = ['modo' => 'rango', 'todo' => true, 'atajos' => ['Este mes', 'Mes pasado', 'Este año']]; include __DIR__ . '/admin/_periodo.php'; ?>
    <label class="f-select<?= $f['estado'] !== '' ? ' activo' : '' ?>"><svg class="i"><use href="#i-recibo"/></svg>
      <select name="estado" aria-label="Estado de pago" data-auto-enviar data-desplegable>
        <option value="">Todas las compras</option>
        <option value="pagadas"<?= $f['estado'] === 'pagadas' ? ' selected' : '' ?>>Pagadas</option>
        <option value="pendientes"<?= $f['estado'] === 'pendientes' ? ' selected' : '' ?>>Con saldo pendiente</option>
      </select>
    </label>
    <?php if ($f['desde'] !== '' || $f['hasta'] !== '' || $f['estado'] !== ''): ?>
    <a href="index.php?action=cliente_detalle&amp;cedula=<?= $e(rawurlencode($f['cedula'])) ?>" class="f-quitar"><svg class="i"><use href="#i-x"/></svg>Quitar filtros</a>
    <?php endif; ?>
  </form>

  <section class="mini-kpis" aria-label="Resumen del período">
    <div class="mk-oscuro"><span class="mk-ic"><svg class="i"><use href="#i-bolsa"/></svg></span><span>Compras</span><b><?= $n ?></b><small><?= $validas ? 'la última el ' . $e(strstr($fechaCorta(reset($validas)->fecha), ',', true)) : 'en el período' ?></small></div>
    <div><span class="mk-ic"><svg class="i"><use href="#i-dolar"/></svg></span><span>Total comprado</span><b><?= $usd($total) ?></b><small><?= $bs($total) ?></small></div>
    <div class="mk-claro"><span class="mk-ic"><svg class="i"><use href="#i-recibo"/></svg></span><span>Compra promedio</span><b><?= $usd($n ? $total / $n : 0) ?></b><small><?= $bs($n ? $total / $n : 0) ?></small></div>
    <div class="mk-ambar"><span class="mk-ic"><svg class="i"><use href="#i-alerta"/></svg></span><span>Saldo pendiente</span><b><?= $usd($saldo) ?></b><small><?= $conSaldo ? $conSaldo . ($conSaldo === 1 ? ' compra por cobrar' : ' compras por cobrar') : 'Todo pagado' ?></small></div>
  </section>

  <section class="tabla-caja cd-caja" aria-labelledby="cd-t">
    <h3 id="cd-t" class="sr-only">Compras</h3>
    <?php if ($compras): ?>
    <ol class="cd-lista" data-paginar="10">
      <?php foreach ($compras as $c):
        $es = $estados[strtolower(trim($c->estado ?? ''))] ?? ['pend', ucfirst((string) ($c->estado ?? ''))];
        $sal = $saldoDe($c);
        $anulada = in_array($c->estado ?? '', ['cancelado', 'anulado'], true);
        $orden = numero_doc('venta', $c);
        $prods = $c->productos ?? [];
        $unid = array_sum(array_map(fn($p) => (int) ($p->cantidad ?? 0), $prods)); ?>
      <li>
        <details class="cd-compra">
          <summary>
            <span class="cd-num"><b><?= $e($orden) ?></b><time datetime="<?= $e(date('c', strtotime((string) $c->fecha))) ?>"><?= $e($fechaCorta($c->fecha)) ?></time></span>
            <span class="cd-chips"><span class="chip"><?= ($c->origen_venta ?? '') === 'online' ? 'Online' : 'Tienda' ?></span><span class="est <?= $es[0] ?>"><?= $e($es[1]) ?></span><?php if (!$anulada && $sal > 0.004): ?><span class="est pend">Debe <?= $usd($sal) ?></span><?php endif; ?></span>
            <span class="cd-total"><b><?= $usd($c->total ?? 0) ?></b><small><?= $unid ?> <?= $unid === 1 ? 'artículo' : 'artículos' ?></small></span>
            <svg class="i cd-flecha" aria-hidden="true"><use href="#i-chevron"/></svg>
          </summary>
          <div class="cd-cuerpo">
            <ul class="cd-prods">
              <?php foreach ($prods as $p):
                $det = array_filter([
                    trim((string) ($p->talla ?? '')) !== '' ? 'Talla ' . mb_strtoupper(trim((string) $p->talla)) : '',
                    trim((string) ($p->color ?? '')),
                    trim((string) ($p->modelo ?? '')),
                ]); ?>
              <li>
                <span class="cd-prod"><b><?= $e($p->producto_nombre ?? '') ?></b><?php if ($det): ?><small><?= $e(implode(' · ', $det)) ?></small><?php endif; ?></span>
                <span class="cd-cant"><?= (int) ($p->cantidad ?? 0) ?> × <?= $usd($p->precio_unitario ?? 0) ?></span>
                <span class="cd-sub"><?= $usd($p->subtotal ?? 0) ?></span>
              </li>
              <?php endforeach; ?>
            </ul>
            <footer class="cd-pie">
              <dl class="cd-cuenta">
                <div><dt>Pagado</dt><dd><?= $usd($c->pagado ?? 0) ?></dd></div>
                <?php if ((float) ($c->por_verificar ?? 0) > 0.004): ?><div><dt>Por verificar</dt><dd><?= $usd($c->por_verificar) ?></dd></div><?php endif; ?>
                <div class="<?= $sal > 0.004 ? 'debe' : 'ok' ?>"><dt>Saldo</dt><dd><?= $usd($sal) ?></dd></div>
              </dl>
              <nav class="cd-acc" aria-label="Acciones de <?= $e($orden) ?>">
                <a href="index.php?action=detalle_venta&amp;<?= url_clave('venta', $c) ?>&amp;vista=nota" target="_blank" class="btn btn-sec"><svg class="i"><use href="#i-archivo"/></svg>Nota de entrega</a>
                <a href="index.php?action=detalle_venta&amp;<?= url_clave('venta', $c) ?>" class="btn btn-sec"><svg class="i"><use href="#i-ojo"/></svg>Ver venta</a>
              </nav>
            </footer>
          </div>
        </details>
      </li>
      <?php endforeach; ?>
    </ol>
    <?php else: ?>
    <div class="vacio-filtro">
      <?= $f['desde'] !== '' || $f['hasta'] !== '' || $f['estado'] !== '' ? 'Este cliente no tiene compras con estos filtros.' : 'Este cliente todavía no tiene compras.' ?>
    </div>
    <?php endif; ?>
  </section>
</section>
<?php include __DIR__ . '/admin/_cerrar.php'; ?>
