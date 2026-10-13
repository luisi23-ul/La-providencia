<?php
$menu = 'configuracion_sistema';
$tituloPagina = 'Configuración';
$panelMaster = true;
$scripts = ['configuracion'];
$config = $config ?? (object) [];
$c = fn($campo, $defecto = '') => $config->$campo ?? $defecto;
if (($_GET['status'] ?? '') === 'ok') $aviso = 'Configuración guardada';
include __DIR__ . '/admin/_abrir.php';
$manual = $c('tasa_modo', 'bcv') === 'manual';
$colores = ['color_primario' => ['Principal', '#0052d4'], 'color_secundario' => ['Secundario', '#0f172a'], 'color_terciario' => ['Fondo oscuro', '#020617'], 'color_cuaternario' => ['Texto suave', '#64748b']];
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Panel Master</small><h2>Configuración</h2></div>
  </header>

  <form class="cfg" id="form-config" action="index.php?action=actualizar_configuracion" method="POST" enctype="multipart/form-data">
    <section class="caja" aria-labelledby="cfg-t1">
      <header class="caja-cab"><h3 id="cfg-t1">Datos de la tienda</h3></header>
      <div class="caja-cuerpo form-grid">
        <div class="campo"><label for="cfg-nombre">Nombre</label><input id="cfg-nombre" name="nombre_empresa" maxlength="100" value="<?= $e($c('nombre_empresa', 'La Providencia')) ?>"></div>
        <div class="campo"><label for="cfg-rif">RIF</label><input id="cfg-rif" name="rif" maxlength="20" value="<?= $e($c('rif')) ?>" placeholder="J-00000000-0"></div>
        <div class="campo"><label for="cfg-tel">Teléfono</label><input id="cfg-tel" name="telefono" type="tel" maxlength="20" value="<?= $e($c('telefono')) ?>"></div>
        <div class="campo"><label for="cfg-correo">Correo</label><input id="cfg-correo" name="email_contacto" type="email" maxlength="100" value="<?= $e($c('email_contacto')) ?>"></div>
        <div class="campo ancho"><label for="cfg-dir">Dirección</label><input id="cfg-dir" name="direccion" value="<?= $e($c('direccion')) ?>"></div>
        <div class="campo ancho"><label for="cfg-wa">WhatsApp de la tienda</label><input id="cfg-wa" name="whatsapp" type="tel" maxlength="20" value="<?= $e($c('whatsapp')) ?>" placeholder="584121234567"><span class="ayuda">A este número llegan los pedidos de la tienda online. Escríbelo con el código del país, sin espacios ni signos.</span></div>
        <div class="campo ancho"><label for="cfg-logo">Logo</label>
          <div style="display:flex;align-items:center;gap:12px">
            <?php if ($c('logo_path')): ?><img src="<?= $e($c('logo_path')) ?>" alt="Logo actual" class="foto"><?php endif; ?>
            <input id="cfg-logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp">
          </div>
          <input type="hidden" name="logo_path_actual" value="<?= $e($c('logo_path')) ?>">
          <span class="ayuda">Si no eliges una imagen, se queda el logo actual.</span>
        </div>
      </div>
    </section>

    <section class="caja" aria-labelledby="cfg-t2">
      <header class="caja-cab"><h3 id="cfg-t2">Tasa del dólar</h3></header>
      <div class="caja-cuerpo">
        <div class="opciones" role="radiogroup" aria-labelledby="cfg-t2">
          <label class="opcion"><input type="radio" name="tasa_modo" value="bcv" <?= $manual ? '' : 'checked' ?>><span>Automática del BCV<small><?= isset($tasaBcv) ? 'Hoy: ' . number_format((float) $tasaBcv, 2, ',', '.') . ' Bs/$' : 'Se actualiza sola cada día' ?></small></span></label>
          <label class="opcion"><input type="radio" name="tasa_modo" value="manual" <?= $manual ? 'checked' : '' ?>><span>La pongo yo<small>Útil si el BCV no responde</small></span></label>
        </div>
        <div class="campo cfg-tasa" id="cfg-tasa-manual" <?= $manual ? '' : 'hidden' ?>><label for="cfg-tasa">Tasa manual (Bs por $)</label><input id="cfg-tasa" name="tasa_manual" type="number" step="0.0001" min="0" inputmode="decimal" value="<?= $e($c('tasa_manual')) ?>"></div>
      </div>
    </section>

    <section class="caja" aria-labelledby="cfg-t3">
      <header class="caja-cab"><h3 id="cfg-t3">Página web</h3></header>
      <div class="caja-cuerpo form-grid">
        <div class="campo ancho"><label for="cfg-hero">Título principal</label><input id="cfg-hero" name="titulo_hero" value="<?= $e($c('titulo_hero')) ?>"></div>
        <div class="campo ancho"><label for="cfg-sub">Subtítulo</label><textarea id="cfg-sub" name="subtitulo_hero" rows="2"><?= $e($c('subtitulo_hero')) ?></textarea></div>
        <div class="campo ancho"><label for="cfg-pie">Texto del pie de página</label><input id="cfg-pie" name="footer_texto" value="<?= $e($c('footer_texto')) ?>"></div>
        <fieldset class="campo ancho" style="border:0;padding:0;margin:0"><legend class="lbl" style="padding:0;margin-bottom:6px">Colores de la página</legend>
          <div class="cfg-colores">
            <?php foreach ($colores as $campo => [$texto, $defecto]): ?>
              <label><input type="color" name="<?= $campo ?>" value="<?= $e($c($campo, $defecto)) ?>"><span><?= $texto ?></span></label>
            <?php endforeach; ?>
          </div>
        </fieldset>
      </div>
    </section>

    <div class="barra-guardar">
      <span class="sub-resumen" id="cfg-estado">Sin cambios</span>
      <div class="acciones-pago" style="flex:0 1 auto"><button type="submit" class="btn btn-pri" id="cfg-guardar" disabled>Guardar cambios</button></div>
    </div>
  </form>
</section>
<?php include __DIR__ . '/admin/_cerrar.php'; ?>
