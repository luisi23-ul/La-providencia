<?php
$menu = 'configuracion_sistema';
$tituloPagina = 'Configuración';
$panelMaster = true;
$scripts = ['configuracion'];
$config = (object) ($config ?? []);
$c = fn($campo, $defecto = '') => $config->$campo ?? $defecto;
if (($_GET['status'] ?? '') === 'ok') $aviso = 'Configuración guardada';
include __DIR__ . '/admin/_abrir.php';
$manual = $c('tasa_modo', 'bcv') === 'manual';
$cab = fn($id, $ic, $titulo, $sub) => '<header class="caja-cab cfg-cab"><h3 id="' . $id . '"><span class="fpx-ic"><svg class="i"><use href="#i-' . $ic . '"/></svg></span><span>' . $titulo . '<small>' . $sub . '</small></span></h3></header>';
$fabrica = ['#0052d4', '#1b34da', '#0f172a', '#363c54', '#020617', '#dcdcea', '#64748b', '#282b2f'];
$colorVista = function ($campo, $defecto) use ($c, $fabrica) {
    $v = strtolower(trim((string) $c($campo)));
    return preg_match('/^#[0-9a-f]{6}$/', $v) && !in_array($v, $fabrica, true) ? $v : $defecto;
};
$paletas = [
    'arena' => ['Arena', 'Cálida y neutra, la de Aura', '#8c4a2f', '#f5f0e8', '#2a2420'],
    'rosa' => ['Rosa', 'Suave y femenina', '#a83f62', '#fbf1f2', '#3b2229'],
    'salvia' => ['Salvia', 'Fresca y natural', '#4d6a4b', '#f0f2eb', '#1f2a20'],
];
$actual = 'arena';
foreach ($paletas as $clave => $pal) if ($colorVista('color_primario', '') === $pal[2]) $actual = $clave;
$elegida = $paletas[$actual];
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Panel Master</small><h2>Configuración</h2></div>
  </header>

  <form class="cfg cfg-rejilla" id="form-config" action="index.php?action=actualizar_configuracion" method="POST" enctype="multipart/form-data">
    <section class="caja cfg-col1" aria-labelledby="cfg-t1">
      <?= $cab('cfg-t1', 'tienda', 'Datos de la tienda', 'Salen en la tienda, los comprobantes y el panel') ?>
      <div class="caja-cuerpo form-grid">
        <div class="campo"><label for="cfg-nombre">Nombre</label><input id="cfg-nombre" name="nombre_empresa" maxlength="100" value="<?= $e($c('nombre_empresa', $nombreTienda ?? 'Aura Boutique')) ?>"></div>
        <div class="campo"><label for="cfg-rif">RIF</label><input id="cfg-rif" name="rif" maxlength="20" value="<?= $e($c('rif')) ?>" placeholder="J-00000000-0"></div>
        <div class="campo ancho"><span class="lbl">Logo</span>
          <div class="cfg-logo">
            <span class="cfg-logo-prev" id="cfg-logo-prev"><?php if ($c('logo_path')): ?><img src="<?= $e($c('logo_path')) ?>" alt="Logo actual"><?php else: ?><?= $e(mb_strtoupper(mb_substr($c('nombre_empresa', $nombreTienda ?? 'A'), 0, 1))) ?><?php endif; ?></span>
            <div class="cfg-logo-txt"><b id="cfg-logo-nombre"><?= $c('logo_path') ? 'Logo actual' : 'Sin logo' ?></b><small>PNG, JPG o WEBP. Si no eliges otra, se queda la actual.</small></div>
            <label class="btn btn-sec btn-peq" for="cfg-logo"><svg class="i"><use href="#i-archivo"/></svg>Elegir imagen</label>
            <input id="cfg-logo" name="logo" type="file" class="sr-only" accept="image/png,image/jpeg,image/webp">
          </div>
          <input type="hidden" name="logo_path_actual" value="<?= $e($c('logo_path')) ?>">
        </div>
      </div>
    </section>

    <section class="caja cfg-col1" aria-labelledby="cfg-t2">
      <?= $cab('cfg-t2', 'dolar', 'Tasa del dólar', 'Para convertir los precios a bolívares') ?>
      <div class="caja-cuerpo">
        <div class="opciones" role="radiogroup" aria-labelledby="cfg-t2">
          <label class="opcion"><input type="radio" name="tasa_modo" value="bcv" <?= $manual ? '' : 'checked' ?>><span>Automática del BCV<small><?= isset($tasaBcv) ? 'Hoy: ' . number_format((float) $tasaBcv, 2, ',', '.') . ' Bs/$' : 'Se actualiza sola cada día' ?></small></span></label>
          <label class="opcion"><input type="radio" name="tasa_modo" value="manual" <?= $manual ? 'checked' : '' ?>><span>La pongo yo<small>Útil si el BCV no responde</small></span></label>
        </div>
        <div class="campo cfg-tasa" id="cfg-tasa-manual" <?= $manual ? '' : 'hidden' ?>><label for="cfg-tasa">Tasa manual (Bs por $)</label><input id="cfg-tasa" name="tasa_manual" type="number" step="0.0001" min="0" inputmode="decimal" value="<?= $e($c('tasa_manual')) ?>"></div>
      </div>
    </section>

    <section class="caja cfg-col2" aria-labelledby="cfg-t5">
      <?= $cab('cfg-t5', 'wa', 'Contacto', 'Cómo te encuentran y te escriben los clientes') ?>
      <div class="caja-cuerpo form-grid">
        <div class="campo"><label for="cfg-tel">Teléfono</label><input id="cfg-tel" name="telefono" type="tel" maxlength="20" value="<?= $e($c('telefono')) ?>" placeholder="0412 555 0193"></div>
        <div class="campo"><label for="cfg-correo">Correo</label><input id="cfg-correo" name="email_contacto" type="email" maxlength="100" value="<?= $e($c('email_contacto')) ?>" placeholder="hola@tutienda.com"></div>
        <div class="campo ancho"><label for="cfg-dir">Dirección</label><input id="cfg-dir" name="direccion" value="<?= $e($c('direccion')) ?>" placeholder="Calle, ciudad"></div>
        <div class="campo ancho"><label for="cfg-wa">WhatsApp de la tienda</label><input id="cfg-wa" name="whatsapp" type="tel" maxlength="20" value="<?= $e($c('whatsapp')) ?>" placeholder="584121234567"><span class="ayuda">A este número llegan los pedidos de la tienda online. Escríbelo con el código del país, sin espacios ni signos.</span></div>
      </div>
    </section>


    <section class="caja cfg-ancho" aria-labelledby="cfg-t4">
      <?= $cab('cfg-t4', 'globo', 'Página web', 'Textos y colores de la tienda. A la derecha ves cómo queda') ?>
      <div class="caja-cuerpo cfg-colores-cuerpo">
        <div class="cfg-col-paletas">
          <div class="cfg-textos">
            <div class="campo ancho"><label for="cfg-hero">Título principal</label><input id="cfg-hero" name="titulo_hero" value="<?= $e($c('titulo_hero')) ?>" placeholder="Ropa que se siente tuya"></div>
            <div class="campo ancho"><label for="cfg-sub">Subtítulo</label><textarea id="cfg-sub" name="subtitulo_hero" rows="3" placeholder="Blusas, conjuntos y básicos para todos los días."><?= $e($c('subtitulo_hero')) ?></textarea></div>
            <div class="campo ancho"><label for="cfg-pie">Texto del pie de página</label><input id="cfg-pie" name="footer_texto" value="<?= $e($c('footer_texto')) ?>" placeholder="Moda casual para damas y caballeros."></div>
            <input type="hidden" name="color_cuaternario" value="<?= $e($c('color_cuaternario', '#64748b')) ?>">
          </div>
          <span class="lbl cfg-lbl-pal">Colores</span>
        <div class="cfg-paletas" role="radiogroup" aria-label="Combinación de colores">
          <?php foreach ($paletas as $clave => [$nombre, $desc, $cp, $cf, $ct]): ?>
            <label class="cfg-paleta"><input type="radio" name="paleta_tienda" value="<?= $clave ?>" data-p="<?= $cp ?>" data-f="<?= $cf ?>" data-t="<?= $ct ?>" <?= $clave === $actual ? 'checked' : '' ?>>
              <span class="cfg-muestra" style="--p: <?= $cp ?>; --f: <?= $cf ?>; --t: <?= $ct ?>" aria-hidden="true"><i></i><i></i><i></i></span>
              <span class="cfg-paleta-txt"><b><?= $nombre ?></b><small><?= $desc ?></small></span>
              <span class="cfg-paleta-ok" aria-hidden="true"><svg class="i"><use href="#i-check"/></svg></span>
            </label>
          <?php endforeach; ?>
        </div>
        <input type="hidden" name="color_primario" id="cfg-cp" value="<?= $elegida[2] ?>">
        <input type="hidden" name="color_secundario" id="cfg-cf" value="<?= $elegida[3] ?>">
        <input type="hidden" name="color_terciario" id="cfg-ct" value="<?= $elegida[4] ?>">
        </div>
        <?php
        $partesNombre = explode(' ', trim($c('nombre_empresa', $nombreTienda ?? 'Aura Boutique')), 2);
        $muestras = [['aura-blusa-satinada-celeste.webp', 'Blusa satinada', '$22.00'], ['aura-vestido-midi-lucia.webp', 'Vestido midi Lucía', '$34.00'], ['aura-conjunto-lino-marisol.webp', 'Conjunto lino', '$38.00']];
        ?>
        <div class="cfg-web" id="cfg-prev" aria-label="Vista previa de la página con la paleta elegida" style="--p: <?= $elegida[2] ?>; --f: <?= $elegida[3] ?>; --t: <?= $elegida[4] ?>">
          <div class="cw-barra" aria-hidden="true"><i></i><i></i><i></i><span>Vista previa</span></div>
          <div class="cw-pagina">
            <div class="cw-franja">Arma tu pedido y lo confirmamos por WhatsApp</div>
            <div class="cw-cab"><span class="cw-nav">Inicio <u>Catálogo</u> Contacto</span><span class="cw-logo" data-vp="logo"><?= $e(mb_strtoupper($partesNombre[0])) ?><small><?= $e(mb_strtoupper($partesNombre[1] ?? '')) ?></small></span><span class="cw-ics"><i></i><i></i><i class="cw-bolsa"></i></span></div>
            <div class="cw-hero">
              <div class="cw-hero-txt"><small>NUEVA TEMPORADA</small><?php $vpTit = explode(' ', trim($c('titulo_hero')) ?: 'Ropa que se siente tuya'); $vpUlt = array_pop($vpTit); ?><b data-vp="titulo"><?= $e(implode(' ', $vpTit)) ?> <em><?= $e($vpUlt) ?></em></b><span data-vp="sub"><?= $e(trim($c('subtitulo_hero')) ?: 'Blusas, conjuntos y básicos para todos los días.') ?></span><span class="cw-btn">Ver el catálogo</span></div>
              <img src="public/img/tienda/portada.webp" alt="">
            </div>
            <div class="cw-tit">Lo más nuevo</div>
            <div class="cw-prods">
              <?php foreach ($muestras as [$img, $nom, $precio]): ?>
                <span class="cw-prod"><img src="public/uploads/<?= $img ?>" alt=""><small>Blusas y Tops</small><b><?= $e($nom) ?></b><span class="cw-precio"><?= $precio ?></span><span class="cw-btn cw-btn-peq">Agregar</span></span>
              <?php endforeach; ?>
            </div>
            <div class="cw-pie"><span class="cw-logo cw-logo-pie" data-vp="logo"><?= $e(mb_strtoupper($partesNombre[0])) ?><small><?= $e(mb_strtoupper($partesNombre[1] ?? '')) ?></small></span><span data-vp="pie"><?= $e(trim($c('footer_texto')) ?: 'Moda casual para damas y caballeros.') ?></span></div>
          </div>
        </div>
      </div>
    </section>

    <div class="barra-guardar cfg-barra">
      <span class="sub-resumen" id="cfg-estado">Sin cambios</span>
      <div class="acciones-pago" style="flex:0 1 auto"><button type="submit" class="btn btn-pri" id="cfg-guardar" disabled>Guardar cambios</button></div>
    </div>
  </form>
</section>
<?php include __DIR__ . '/admin/_cerrar.php'; ?>
