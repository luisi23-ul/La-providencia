<?php
$menu = 'metodos_pago';
$tituloPagina = 'Métodos de pago';
$metodos = $metodos ?? array_map(fn($m) => (object) $m, [
    ['id' => 1, 'nombre' => 'Efectivo Bs', 'moneda' => 'Bs', 'requiere_referencia' => 0, 'en_tienda' => 1, 'en_online' => 1, 'datos' => '', 'activo' => 1],
    ['id' => 2, 'nombre' => 'Efectivo $', 'moneda' => 'USD', 'requiere_referencia' => 0, 'en_tienda' => 1, 'en_online' => 1, 'datos' => '', 'activo' => 1],
    ['id' => 3, 'nombre' => 'Transferencia', 'moneda' => 'Bs', 'requiere_referencia' => 1, 'en_tienda' => 1, 'en_online' => 1, 'datos' => '', 'activo' => 1],
    ['id' => 4, 'nombre' => 'Pago Móvil', 'moneda' => 'Bs', 'requiere_referencia' => 1, 'en_tienda' => 1, 'en_online' => 1, 'datos' => '', 'activo' => 1],
]);
include __DIR__ . '/admin/_abrir.php';
$icono = function ($nombre) {
    $n = mb_strtolower($nombre);
    if (str_contains($n, 'efectivo')) return 'efectivo';
    if (str_contains($n, 'móvil') || str_contains($n, 'movil')) return 'movil';
    if (str_contains($n, 'transfer')) return 'banco';
    return 'tarjeta';
};
$activos = count(array_filter($metodos, fn($m) => $m->activo));
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Gestión</small><h2>Métodos de pago</h2><p class="mp-sub"><?= $activos ?> de <?= count($metodos) ?> activos. Los activos aparecen al cobrar en la caja y en la tienda online.</p></div>
    <div class="cab-acc"><button type="button" class="btn btn-pri" data-abrir-modal="modal-metodo"><svg class="i"><use href="#i-mas"/></svg>Nuevo método</button></div>
  </header>
  <ul class="mp-rejilla" aria-label="Métodos de pago">
    <?php foreach ($metodos as $m):
        $usdM = ($m->moneda ?? 'Bs') === 'USD';
        $donde = $m->en_tienda && $m->en_online ? 'Tienda y online' : ($m->en_tienda ? 'Solo tienda física' : 'Solo tienda online'); ?>
    <li class="mp-card <?= $usdM ? 'usd' : 'bs' ?><?= $m->activo ? '' : ' apagado' ?>">
      <div class="mp-cab">
        <span class="mp-ic fpx-ic"><svg class="i"><use href="#i-<?= $icono($m->nombre) ?>"/></svg></span>
        <form method="POST" action="index.php?action=cambiar_estado_metodo">
          <input type="hidden" name="id" value="<?= (int) $m->id ?>"><input type="hidden" name="activo" value="<?= $m->activo ? 0 : 1 ?>">
          <label class="interruptor"><input type="checkbox" <?= $m->activo ? 'checked' : '' ?> onchange="this.form.submit()" aria-label="<?= $m->activo ? 'Apagar' : 'Activar' ?> <?= $e($m->nombre) ?>"><span><?= $m->activo ? 'Activo' : 'Apagado' ?></span></label>
        </form>
      </div>
      <h3><?= $e($m->nombre) ?></h3>
      <p class="mp-datos"><?= !empty($m->datos) ? nl2br($e($m->datos)) : 'Sin datos para el cliente.' ?></p>
      <dl class="mp-props">
        <div><dt>Moneda</dt><dd><?= $usdM ? 'Dólares' : 'Bolívares' ?></dd></div>
        <div><dt>Dónde</dt><dd><?= $donde ?></dd></div>
        <div><dt>Referencia</dt><dd><?= $m->requiere_referencia ? 'Se pide' : 'No se pide' ?></dd></div>
      </dl>
      <footer class="mp-pie">
        <a href="index.php?action=ver_reporte&amp;metodo=<?= (int) $m->id ?>" class="btn btn-sec btn-peq"><svg class="i"><use href="#i-grafico"/></svg>Ver lo cobrado</a>
        <button type="button" class="btn btn-sec btn-peq" data-abrir-modal="modal-metodo" data-registro="<?= $e(json_encode(['id' => $m->id, 'nombre' => $m->nombre, 'moneda' => $m->moneda ?? 'Bs', 'en_tienda' => $m->en_tienda, 'en_online' => $m->en_online, 'requiere_referencia' => $m->requiere_referencia, 'datos' => $m->datos ?? ''], JSON_UNESCAPED_UNICODE)) ?>"><svg class="i"><use href="#i-editar"/></svg>Editar</button>
      </footer>
    </li>
    <?php endforeach; ?>
  </ul>
</section>

<dialog class="modal" id="modal-metodo" aria-labelledby="mm-titulo">
  <form method="POST" action="index.php?action=guardar_metodo_pago">
    <input type="hidden" name="id">
    <header class="modal-cab"><span class="modal-ic"><svg class="i"><use href="#i-tarjeta"/></svg></span><div><h3 id="mm-titulo" data-si-edita="Editar método de pago" data-si-crea="Nuevo método de pago">Nuevo método de pago</h3></div><button type="button" class="ic-btn" data-cerrar aria-label="Cerrar"><svg class="i"><use href="#i-x"/></svg></button></header>
    <div class="modal-cuerpo mm-cuerpo">
      <div class="mm-fila">
      <div class="campo"><label for="mm-nombre">Nombre <span class="ob">*</span></label><input id="mm-nombre" name="nombre" required maxlength="50" placeholder="Ej.: Pago móvil"></div>
      <div class="campo"><span class="lbl" id="mm-l-moneda">Moneda</span>
        <div class="ajx-seg" role="radiogroup" aria-labelledby="mm-l-moneda">
          <label><input type="radio" name="moneda" value="Bs" checked><svg class="i"><use href="#i-banco"/></svg>Bolívares</label>
          <label><input type="radio" name="moneda" value="USD"><svg class="i"><use href="#i-dolar"/></svg>Dólares</label>
        </div>
      </div>
      </div>
      <div class="campo"><span class="lbl">Opciones</span>
        <ul class="mm-ops">
          <li><label><span class="mm-op-ic"><svg class="i"><use href="#i-tienda"/></svg></span><span class="mm-op-txt"><b>Tienda física</b><small>Se puede cobrar en la caja.</small></span><span class="interruptor"><input type="checkbox" name="en_tienda" value="1" checked></span></label></li>
          <li><label><span class="mm-op-ic"><svg class="i"><use href="#i-globo"/></svg></span><span class="mm-op-txt"><b>Tienda online</b><small>El cliente lo puede elegir al pedir.</small></span><span class="interruptor"><input type="checkbox" name="en_online" value="1" checked></span></label></li>
          <li><label><span class="mm-op-ic"><svg class="i"><use href="#i-etiqueta"/></svg></span><span class="mm-op-txt"><b>Pedir referencia</b><small>Se pide el número del pago.</small></span><span class="interruptor"><input type="checkbox" name="requiere_referencia" value="1"></span></label></li>
        </ul>
      </div>
      <div class="campo"><label for="mm-datos">Datos para el cliente</label><textarea id="mm-datos" name="datos" rows="2" placeholder="Banco, teléfono, cédula o número de cuenta"></textarea><span class="ayuda">Se muestran al cliente cuando elige este método en la tienda online.</span></div>
    </div>
    <footer class="modal-pie"><button type="button" class="btn btn-sec" data-cerrar>Cancelar</button><button type="submit" class="btn btn-pri" data-si-crea="Guardar método" data-si-edita="Guardar cambios">Guardar método</button></footer>
  </form>
</dialog>
<?php include __DIR__ . '/admin/_cerrar.php'; ?>
