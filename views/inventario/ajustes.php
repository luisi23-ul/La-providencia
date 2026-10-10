<?php
$menu = 'ajustes_inventario';
$tituloPagina = 'Ajustes de stock';
$scripts = ['ajustes'];
$productos = $productos ?? $listaProductos ?? [];
$ultimosAjustes = $ultimosAjustes ?? [];
include __DIR__ . '/../admin/_abrir.php';
$elegido = (int) ($_GET['producto'] ?? 0);
$tipos = ['ajuste' => ['ajuste', 'Conteo'], 'merma' => ['merma', 'Merma'], 'transferencia' => ['transf', 'Transferencia']];
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Inventario</small><h2>Ajustes de stock</h2><small>Para corregir existencias sin editar el producto. Cada ajuste queda en Movimientos.</small></div>
  </header>
  <form class="ajuste-layout" id="form-ajuste" action="index.php?action=guardar_ajuste" method="POST">
    <section class="caja" aria-labelledby="t-ajuste">
      <header class="caja-cab"><h3 id="t-ajuste">Nuevo ajuste</h3></header>
      <div class="caja-cuerpo" style="display:flex;flex-direction:column;gap:16px">
        <div class="campo">
          <span class="lbl" id="l-tipo">¿Qué quieres hacer?</span>
          <div class="opciones" role="radiogroup" aria-labelledby="l-tipo">
            <label class="opcion"><input type="radio" name="tipo_movimiento" value="ajuste" checked><span>Corregir por conteo<small>Contaste y no cuadra</small></span></label>
            <label class="opcion"><input type="radio" name="tipo_movimiento" value="merma"><span>Registrar merma<small>Dañado, vencido, perdido</small></span></label>
            <label class="opcion"><input type="radio" name="tipo_movimiento" value="transferencia"><span>Pasar entre canales<small>Tienda ↔ online</small></span></label>
          </div>
        </div>
        <div class="campo">
          <label for="aj-prod">Producto <span class="ob">*</span></label>
          <select id="aj-prod" name="id_producto" required>
            <?php foreach ($productos as $p): ?><option value="<?= (int) $p->id ?>" <?= $elegido === (int) $p->id ? 'selected' : '' ?>><?= $e(($p->codigo ?? 'P-' . str_pad($p->id, 4, '0', STR_PAD_LEFT)) . ' · ' . $p->nombre_producto) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="prod-elegido" id="aj-elegido"></div>
        <div class="form-grid" id="aj-campos"></div>
        <div class="campo">
          <label for="aj-obs">Observación</label>
          <textarea id="aj-obs" name="observacion" rows="2" maxlength="255" placeholder="Ej.: Conteo del viernes, faltaban 2 en el estante."></textarea>
        </div>
        <div>
          <span class="lbl" style="font-size:12.5px;font-weight:500;color:var(--texto-2)">Así queda el stock</span>
          <div class="queda" id="aj-queda"></div>
        </div>
        <div class="acciones-pago">
          <a href="index.php?action=listado_productos" class="btn btn-sec">Cancelar</a>
          <button type="submit" class="btn btn-pri" id="aj-guardar">Registrar ajuste</button>
        </div>
      </div>
    </section>
    <aside class="caja" aria-labelledby="t-ult">
      <header class="caja-cab"><h3 id="t-ult">Últimos ajustes</h3><a href="index.php?action=kardex">Ver movimientos</a></header>
      <ul class="lista ult-ajustes">
        <?php foreach ($ultimosAjustes as $a): $t = $tipos[$a->tipo_movimiento] ?? ['ajuste', $a->tipo_movimiento]; ?>
          <li><div class="quien"><b><?= $e($a->nombre_producto) ?></b><span><span class="mov <?= $t[0] ?>"><?= $t[1] ?></span> <?= $e(trim(($a->observacion ? $a->observacion . ', ' : '') . date('d/m g:i a', strtotime($a->fecha)) . ', ' . ($a->usuario ?? ''), ', ')) ?></span></div><span class="cifra<?= $a->cantidad < 0 ? ' cant-menos' : '' ?>"><?= $a->cantidad < 0 ? '−' . abs($a->cantidad) : (int) $a->cantidad ?></span></li>
        <?php endforeach; ?>
        <?php if (!$ultimosAjustes): ?><li class="vacio-filtro" style="display:block">Todavía no hay ajustes.</li><?php endif; ?>
      </ul>
    </aside>
  </form>
</section>
<script type="application/json" id="datos-ajustes"><?= json_encode(array_map(fn($p) => ['id' => (int) $p->id, 'n' => $p->nombre_producto, 't' => (int) $p->stock, 'o' => (int) ($p->stock_online ?? 0), 'min' => (int) ($p->stock_minimo ?? 5), 'img' => $p->imagen ? 'public/uploads/' . $p->imagen : ''], $productos), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>
