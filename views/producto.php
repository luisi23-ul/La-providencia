<?php
include __DIR__ . '/tienda/_comun.php';
$producto = $producto ?? null;
$atributos = $atributos ?? [];
$relacionados = $relacionados ?? [];
if (!$producto): ?>
  <section class="carro"><div class="carro-vacio"><i class="ti ti-hanger" aria-hidden="true"></i><h2>No encontramos esta prenda</h2><p>Puede que ya no esté disponible.</p><a class="btn" href="index.php?action=ver_catalogo">Ver el catálogo</a></div></section>
<?php $seccionPie = 'catalogo'; include __DIR__ . '/tienda/_pie.php'; return; endif;

$opciones = function ($a) {
    $valor = trim((string) ($a->valor ?? ''));
    $lista = $valor !== '' ? $valor : implode(',', (array) ($a->opciones ?? []));
    return array_values(array_filter(array_map('trim', explode(',', $lista)), 'strlen'));
};
$stock = (int) ($producto->stock_online ?? 0);
$img = $foto($producto->imagen ?? '');
$categoria = $producto->nombre_categoria ?? ($nombresCategoria[(int) ($producto->id_categoria ?? 0)] ?? '');
?>
  <nav class="migas" aria-label="Ruta"><a href="index.php?action=inicio">Inicio</a><i class="ti ti-chevron-right" aria-hidden="true"></i><?php if ($categoria): ?><a href="index.php?action=ver_catalogo&amp;id_categoria=<?= (int) $producto->id_categoria ?>"><?= $e($categoria) ?></a><i class="ti ti-chevron-right" aria-hidden="true"></i><?php endif; ?><span aria-current="page"><?= $e($producto->nombre_producto) ?></span></nav>

  <section class="ficha">
    <div class="galeria galeria-una">
      <figure class="g-principal"><?php if ($img): ?><img src="<?= $e($img) ?>" alt="<?= $e($producto->nombre_producto) ?>" width="800" height="1067" fetchpriority="high"><?php else: ?><span class="sin-foto"><i class="ti ti-hanger"></i></span><?php endif; ?></figure>
    </div>

    <form class="f-info" id="f-producto" data-id="<?= (int) $producto->id ?>" data-nombre="<?= $e($producto->nombre_producto) ?>" data-precio="<?= $e((float) $producto->precio) ?>" data-imagen="<?= $e($img) ?>" data-stock="<?= $stock ?>">
      <div class="prod-meta"><span><?= $e($categoria) ?></span></div>
      <h1><?= $e($producto->nombre_producto) ?></h1>
      <div class="f-precio"><b><?= $usd($producto->precio) ?></b><span><?= $bs($producto->precio) ?></span></div>
      <?php if (!empty($producto->descripcion)): ?><p class="f-desc"><?= $e($producto->descripcion) ?></p><?php endif; ?>

      <?php foreach ($atributos as $a): $ops = $opciones($a); if (!$ops) continue; $nom = $a->nombre; $esColor = stripos($nom, 'color') !== false; ?>
      <fieldset class="f-op" data-atributo="<?= $e($nom) ?>">
        <legend><?= $e($nom) ?><?php if (count($ops) > 1): ?>: <b data-elegido>Elige una</b><?php else: ?>: <b data-elegido><?= $e($ops[0]) ?></b><?php endif; ?></legend>
        <div class="f-tallas" role="radiogroup" aria-label="<?= $e($nom) ?>">
          <?php foreach ($ops as $o): ?><button type="button" role="radio" aria-checked="<?= count($ops) === 1 ? 'true' : 'false' ?>" data-valor="<?= $e($o) ?>"><?= $e($o) ?></button><?php endforeach; ?>
        </div>
      </fieldset>
      <?php endforeach; ?>

      <p class="f-stock"<?= $stock <= 0 ? ' style="color:#8a4b12"' : '' ?>><i class="ti <?= $stock > 0 ? 'ti-circle-check' : 'ti-alert-circle' ?>" aria-hidden="true"></i><?= $stock <= 0 ? 'Agotado por ahora' : ($stock <= 3 ? 'Quedan ' . $stock : 'Disponible') ?></p>
      <p class="aviso-form" data-error hidden><i class="ti ti-alert-circle" aria-hidden="true"></i><span></span></p>

      <div class="f-comprar">
        <div class="cant"><button type="button" data-paso="-1" aria-label="Quitar una"><i class="ti ti-minus"></i></button><span data-cantidad>1</span><button type="button" data-paso="1" aria-label="Agregar una"><i class="ti ti-plus"></i></button></div>
        <button class="btn f-agregar" type="submit"<?= $stock <= 0 ? ' disabled' : '' ?>><i class="ti ti-shopping-bag-plus" aria-hidden="true"></i>Agregar al pedido</button>
      </div>

      <div class="f-detalles">
        <details><summary>Retiro y pago<i class="ti ti-chevron-down" aria-hidden="true"></i></summary><p>Retiras en la tienda o coordinamos la entrega por WhatsApp. Aceptamos pago móvil, transferencia y efectivo.</p></details>
      </div>
    </form>
  </section>

  <?php if ($relacionados): ?>
  <section class="lista" aria-labelledby="t-comb">
    <div class="lista-cab"><h2 class="sec-tit" id="t-comb">Combínalo con</h2></div>
    <ul class="rejilla"><?php foreach ($relacionados as $q) echo tarjetaProducto($q, $e, $usd, $bs, $foto, $nombresCategoria); ?></ul>
  </section>
  <?php endif; ?>

  <div class="barra-comprar">
    <div><b><?= $usd($producto->precio) ?></b><span data-resumen>Elige tu talla</span></div>
    <button class="btn" type="submit" form="f-producto"<?= $stock <= 0 ? ' disabled' : '' ?>><i class="ti ti-shopping-bag-plus" aria-hidden="true"></i>Agregar</button>
  </div>
<?php $seccionPie = 'catalogo'; $conMenuMovil = false; include __DIR__ . '/tienda/_pie.php'; ?>
