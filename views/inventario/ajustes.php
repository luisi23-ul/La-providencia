<?php
$menu = 'ajustes_inventario';
$tituloPagina = 'Ajustes de stock';
$scripts = ['ajustes'];
$productos = $productos ?? $listaProductos ?? [];
$ultimosAjustes = $ultimosAjustes ?? [];
include __DIR__ . '/../admin/_abrir.php';
$elegido = leer_url('producto', 'ajuste');
$desde = null;
foreach ($productos as $p) if ($elegido !== '' && clave('producto', $p) === $elegido) $desde = $p;
$variantesPorProducto = [];
try {
    $dbAjustes = $GLOBALS['db'] ?? Database::connect();
    $colV = nombre_clave('variante');
    $colP = nombre_clave('variante.producto');
    foreach ($dbAjustes->query("SELECT $colV, $colP, talla, color, modelo, stock FROM producto_variantes WHERE COALESCE(talla, '') <> '' OR COALESCE(color, '') <> '' OR COALESCE(modelo, '') <> '' ORDER BY $colV")->fetchAll(PDO::FETCH_OBJ) as $v) {
        $variantesPorProducto[ref('variante.producto', $v)][] = [clave('variante', $v), implode(' · ', array_filter([trim($v->talla ?? ''), trim($v->color ?? ''), trim($v->modelo ?? '')])), (int) $v->stock];
    }
} catch (Throwable $t) {
    $variantesPorProducto = [];
}
$tipos = ['ajuste' => ['mas', 'Entrada'], 'merma' => ['basura', 'Merma'], 'transferencia' => ['camion', 'Entre canales']];
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Inventario</small>
      <?php if ($desde): ?>
      <div class="titulo-atras"><a href="index.php?action=editar&<?= url_clave('producto', $desde) ?>" class="atras" aria-label="Volver a <?= $e($desde->nombre_producto) ?>"><svg class="i"><use href="#i-volver"/></svg></a><h2>Ajustes de stock</h2></div>
      <?php else: ?>
      <h2>Ajustes de stock</h2>
      <?php endif; ?>
    </div>
  </header>

  <form class="fpx" id="form-ajuste" action="index.php?action=guardar_ajuste" method="POST">
    <?php if ($desde): ?><input type="hidden" name="volver_producto" value="<?= $e(clave('producto', $desde)) ?>"><?php endif; ?>
    <div class="fpx-col">
      <section class="fpx-sec" aria-labelledby="aj-t-tipo">
        <header><span class="fpx-ic"><svg class="i"><use href="#i-ajuste"/></svg></span><div><h3 id="aj-t-tipo">¿Qué quieres hacer?</h3><p>Cada ajuste queda en Movimientos con quién y por qué.</p></div></header>
        <div class="fpx-cuerpo">
          <div class="ajx-tipos ancho" role="radiogroup" aria-labelledby="aj-t-tipo">
            <label class="ajx-tipo"><input type="radio" name="tipo_movimiento" value="ajuste" checked><span class="ic"><svg class="i"><use href="#i-mas"/></svg></span><b>Sumar unidades</b><small>Llegaron o aparecieron más</small></label>
            <label class="ajx-tipo"><input type="radio" name="tipo_movimiento" value="merma"><span class="ic"><svg class="i"><use href="#i-basura"/></svg></span><b>Registrar merma</b><small>Dañado, vencido, perdido</small></label>
          </div>
        </div>
      </section>

      <section class="fpx-sec" aria-labelledby="aj-t-prod">
        <header><span class="fpx-ic"><svg class="i"><use href="#i-caja"/></svg></span><div><h3 id="aj-t-prod">Producto y cantidad</h3><p id="aj-t-prod-sub">Lo que hay de verdad en el estante.</p></div></header>
        <div class="fpx-cuerpo">
          <div class="campo ancho"><label for="aj-prod">Producto <span class="ob">*</span></label>
            <select id="aj-prod" name="<?= campo('producto.ref') ?>" required data-buscar="Buscar producto">
              <?php foreach ($productos as $p): ?><option value="<?= $e(clave('producto', $p)) ?>"<?= $elegido === clave('producto', $p) ? ' selected' : '' ?>><?= $e(($p->codigo ?? 'P-' . str_pad(clave('producto', $p), 4, '0', STR_PAD_LEFT)) . ' · ' . $p->nombre_producto) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="ajx-prod ancho" id="aj-elegido"></div>
          <div class="campo ancho ajx-vars-campo" id="aj-vars" hidden></div>
          <div class="ajx-campos ancho" id="aj-campos"></div>
          <div class="campo ancho"><label for="aj-obs">Observación</label><textarea id="aj-obs" name="observacion" rows="2" maxlength="255" placeholder="Ej.: Conteo del viernes, faltaban 2 en el estante."></textarea></div>
        </div>
      </section>

      <section class="fpx-sec" aria-labelledby="aj-t-queda">
        <header><span class="fpx-ic"><svg class="i"><use href="#i-grafico"/></svg></span><div><h3 id="aj-t-queda">Así queda el stock</h3><p>Revísalo antes de registrar.</p></div></header>
        <div class="fpx-cuerpo">
          <div class="ajx-queda ancho" id="aj-queda" aria-live="polite"></div>
          <div class="ajx-acc ancho">
            <a href="<?= $desde ? 'index.php?action=editar&amp;' . url_clave('producto', $desde) : 'index.php?action=listado_productos' ?>" class="btn btn-sec">Cancelar</a>
            <button type="submit" class="btn btn-pri" id="aj-guardar"><svg class="i"><use href="#i-check"/></svg>Registrar ajuste</button>
          </div>
        </div>
      </section>
    </div>

    <aside class="fpx-col">
      <section class="fpx-sec" aria-labelledby="aj-t-ult">
        <header><span class="fpx-ic"><svg class="i"><use href="#i-historial"/></svg></span><div><h3 id="aj-t-ult">Últimos ajustes</h3><p><a href="index.php?action=kardex" class="enlace">Ver movimientos</a></p></div></header>
        <ul class="ajx-ult">
          <?php foreach ($ultimosAjustes as $a):
            $t = $tipos[$a->tipo_movimiento] ?? ['ajuste', $a->tipo_movimiento];
            $cant = (int) $a->cantidad;
          ?>
          <li>
            <span class="ic"><svg class="i"><use href="#i-<?= $t[0] ?>"/></svg></span>
            <b><?= $e($a->nombre_producto) ?></b>
            <span class="n<?= $cant < 0 ? ' neg' : ($cant > 0 ? ' pos' : '') ?>"><?= $cant < 0 ? '−' . abs($cant) : ($cant > 0 ? '+' . $cant : '0') ?></span>
            <small><?= $e(implode(' · ', array_filter([$t[1], $a->observacion ?? '', date('d/m g:i a', strtotime(ref('kardex.fecha', $a))), $a->usuario ?? '']))) ?></small>
          </li>
          <?php endforeach; ?>
          <?php if (!$ultimosAjustes): ?><li class="ajx-vacio">Todavía no hay ajustes.</li><?php endif; ?>
        </ul>
      </section>
    </aside>
  </form>
</section>
<script type="application/json" id="datos-ajustes"><?= json_encode(array_map(fn($p) => ['id' => clave('producto', $p), 'n' => $p->nombre_producto, 't' => (int) $p->stock, 'min' => (int) ($p->stock_minimo ?? 5), 'img' => !empty($p->imagen) ? 'public/uploads/' . $p->imagen : '', 'v' => $variantesPorProducto[clave('producto', $p)] ?? []], $productos), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>
