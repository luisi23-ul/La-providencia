<?php
include __DIR__ . '/tienda/_comun.php';
$categorias = $categorias ?? [];
$novedades = $novedades ?? [];
if (!$categorias || !$novedades) {
    try {
        $dbInicio = $GLOBALS['db'] ?? Database::connect();
        if (!$categorias) $categorias = $dbInicio->query("SELECT * FROM categorias WHERE " . sql_principal() . " ORDER BY nombre_categoria ASC")->fetchAll(PDO::FETCH_OBJ);
        if (!$novedades) $novedades = $dbInicio->query("SELECT p.*, c.nombre_categoria FROM productos p LEFT JOIN categorias c ON c." . nombre_clave('categoria') . " = p." . nombre_clave('producto.categoria') . " WHERE p.estado = 1 AND " . sql_sin_borrar('p') . " AND p.stock > 0 ORDER BY " . (base_nueva() ? "(SELECT MAX(v.created_at) FROM producto_variantes v WHERE v.codigo_producto = p.codigo) DESC, p.codigo DESC" : "p.id DESC") . " LIMIT 8")->fetchAll(PDO::FETCH_OBJ);
    } catch (Throwable $t) {
        $categorias = $categorias ?: [];
        $novedades = $novedades ?: [];
    }
}
$titulo = trim((string) ($sistema->titulo_hero ?? '')) ?: 'Ropa que se siente tuya';
$palabras = explode(' ', $titulo);
$ultima = array_pop($palabras);
$categorias = $soloConProductos($categorias);
$conFoto = array_values(array_filter($categorias, fn($c) => !empty($c->imagen)));
?>
  <section class="hero">
    <div class="hero-txt">
      <span class="ceja">Nueva temporada</span>
      <h1><?= $e(implode(' ', $palabras)) ?> <em><?= $e($ultima) ?></em></h1>
      <p><?= $e(trim((string) ($sistema->subtitulo_hero ?? '')) ?: 'Blusas, conjuntos y básicos para todos los días. Elige tu talla y pídelo por WhatsApp.') ?></p>
      <a class="btn" href="index.php?action=ver_catalogo">Ver el catálogo</a>
    </div>
    <figure class="hero-foto"><img src="public/img/tienda/portada.webp" alt="Modelo con ropa de la nueva temporada" width="1200" height="1400" fetchpriority="high"></figure>
  </section>

  <?php if (count($conFoto) >= 5): ?>
  <section class="mosaico" aria-labelledby="t-cat">
    <h2 class="sec-tit" id="t-cat">Compra por categoría</h2>
    <?php foreach (array_slice($conFoto, 0, 5) as $i => $c): ?>
    <a class="tesela<?= $i === 0 ? ' grande' : '' ?>" href="index.php?action=ver_catalogo&amp;<?= url_clave('categoria', $c) ?>"><img src="<?= $e($foto($c->imagen)) ?>" alt="" loading="lazy"><span><?= $e($c->nombre_categoria) ?><i class="ti ti-arrow-right" aria-hidden="true"></i></span></a>
    <?php endforeach; ?>
  </section>
  <?php elseif ($categorias): ?>
  <section class="lista" aria-labelledby="t-cat" style="padding-bottom:0">
    <div class="lista-cab"><h2 class="sec-tit" id="t-cat">Compra por categoría</h2></div>
    <div class="chips"><?php foreach ($categorias as $c): ?><a href="index.php?action=ver_catalogo&amp;<?= url_clave('categoria', $c) ?>"><?= $e($c->nombre_categoria) ?></a><?php endforeach; ?></div>
  </section>
  <?php endif; ?>

  <?php if ($novedades): ?>
  <section class="lista" aria-labelledby="t-nov">
    <div class="lista-cab"><h2 class="sec-tit" id="t-nov">Lo más nuevo</h2></div>
    <ul class="rejilla"><?php foreach ($novedades as $p) echo tarjetaProducto($p, $e, $usd, $bs, $foto, $nombresCategoria, $conVariantes); ?></ul>
    <div class="ver-mas"><a class="btn sec" href="index.php?action=ver_catalogo">Ver todo el catálogo</a></div>
  </section>
  <?php endif; ?>

  <section class="pasos" aria-labelledby="t-pasos">
    <h2 class="sec-tit" id="t-pasos">Así de fácil es pedir</h2>
    <ol>
      <li><i class="ti ti-hanger" aria-hidden="true"></i><b>Elige tus prendas</b><span>Con la talla que buscas.</span></li>
      <li><i class="ti ti-shopping-bag" aria-hidden="true"></i><b>Revisa tu pedido</b><span>Ves el total en dólares y bolívares.</span></li>
      <li><i class="ti ti-brand-whatsapp" aria-hidden="true"></i><b>Envíalo por WhatsApp</b><span>Te confirmamos disponibilidad y pago.</span></li>
    </ol>
  </section>
<?php $seccionPie = 'inicio'; include __DIR__ . '/tienda/_pie.php'; ?>
