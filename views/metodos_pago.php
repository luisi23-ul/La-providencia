<?php
$menu = 'metodos_pago';
$tituloPagina = 'Métodos de pago';
$metodos = $metodos ?? array_map(fn($m) => (object) $m, [
    ['id' => 2, 'nombre' => 'Efectivo $', 'moneda' => 'USD', 'requiere_referencia' => 0, 'en_tienda' => 1, 'en_online' => 1, 'datos' => 'Se paga en la caja al retirar.', 'activo' => 1],
    ['id' => 1, 'nombre' => 'Efectivo Bs', 'moneda' => 'Bs', 'requiere_referencia' => 0, 'en_tienda' => 1, 'en_online' => 1, 'datos' => 'Se paga en la caja al retirar.', 'activo' => 1],
    ['id' => 3, 'nombre' => 'Transferencia', 'moneda' => 'Bs', 'requiere_referencia' => 1, 'en_tienda' => 1, 'en_online' => 1, 'datos' => '', 'activo' => 1],
    ['id' => 4, 'nombre' => 'Pago móvil', 'moneda' => 'Bs', 'requiere_referencia' => 1, 'en_tienda' => 1, 'en_online' => 1, 'datos' => '', 'activo' => 1],
]);
include __DIR__ . '/admin/_abrir.php';
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Gestión</small><h2>Métodos de pago</h2><small>Los datos que pongas aquí son los que ve el cliente cuando va a pagar.</small></div>
    <div class="cab-acc"><button type="button" class="btn btn-pri" data-abrir-modal="modal-metodo"><svg class="i"><use href="#i-mas"/></svg>Nuevo método</button></div>
  </header>
  <section class="lista-metodos" aria-label="Métodos de pago">
    <?php foreach ($metodos as $m):
        $usdM = ($m->moneda ?? 'Bs') === 'USD';
        $donde = $m->en_tienda && $m->en_online ? 'Tienda y online' : ($m->en_tienda ? 'Solo tienda física' : 'Solo tienda online'); ?>
      <article class="metodo-fila<?= $m->activo ? '' : ' inactivo' ?>">
        <span class="mf-mon <?= $usdM ? 'usd' : 'bs' ?>"><?= $usdM ? '$' : 'Bs' ?></span>
        <div class="mf-info">
          <b><?= $e($m->nombre) ?></b>
          <?php if (!empty($m->datos)): ?><span class="mf-datos"><?= nl2br($e($m->datos)) ?></span><?php endif; ?>
          <span class="mf-chips"><span class="chip"><?= $donde ?></span><?php if ($m->requiere_referencia): ?><span class="chip">Pide referencia</span><?php endif; ?></span>
        </div>
        <div class="mf-acc">
          <form method="POST" action="index.php?action=cambiar_estado_metodo">
            <input type="hidden" name="id" value="<?= (int) $m->id ?>"><input type="hidden" name="activo" value="<?= $m->activo ? 0 : 1 ?>">
            <label class="interruptor"><input type="checkbox" <?= $m->activo ? 'checked' : '' ?> onchange="this.form.submit()"><span><?= $m->activo ? 'Activo' : 'Apagado' ?></span></label>
          </form>
          <a href="index.php?action=ver_reporte&metodo=<?= (int) $m->id ?>" class="enlace" style="font-size:13px">Ver lo cobrado</a>
          <button type="button" class="btn btn-sec btn-peq" data-abrir-modal="modal-metodo" data-registro="<?= $e(json_encode(['id' => $m->id, 'nombre' => $m->nombre, 'moneda' => $m->moneda ?? 'Bs', 'en_tienda' => $m->en_tienda, 'en_online' => $m->en_online, 'requiere_referencia' => $m->requiere_referencia, 'datos' => $m->datos ?? ''], JSON_UNESCAPED_UNICODE)) ?>">Editar</button>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
</section>

<dialog class="modal" id="modal-metodo" aria-labelledby="mm-titulo">
  <form method="POST" action="index.php?action=guardar_metodo_pago">
    <input type="hidden" name="id">
    <header class="modal-cab"><div><h3 id="mm-titulo" data-si-edita="Editar método de pago" data-si-crea="Nuevo método de pago">Nuevo método de pago</h3></div><button type="button" class="ic-btn" data-cerrar aria-label="Cerrar"><svg class="i"><use href="#i-x"/></svg></button></header>
    <div class="modal-cuerpo" style="display:flex;flex-direction:column;gap:14px">
      <div class="campo"><label for="mm-nombre">Nombre <span class="ob">*</span></label><input id="mm-nombre" name="nombre" required maxlength="50" placeholder="Ej.: Pago móvil"></div>
      <div class="campo"><span class="lbl">Moneda</span>
        <div class="opciones" role="radiogroup" aria-label="Moneda"><label class="opcion"><input type="radio" name="moneda" value="Bs" checked><span>Bolívares</span></label><label class="opcion"><input type="radio" name="moneda" value="USD"><span>Dólares</span></label></div>
      </div>
      <div class="campo"><span class="lbl">Dónde se puede usar</span>
        <div class="opciones"><label class="opcion"><input type="checkbox" name="en_tienda" value="1" checked><span>Tienda física</span></label><label class="opcion"><input type="checkbox" name="en_online" value="1" checked><span>Tienda online</span></label></div>
      </div>
      <label class="interruptor"><input type="checkbox" name="requiere_referencia" value="1"><span>Pedir número de referencia al cobrar</span></label>
      <div class="campo"><label for="mm-datos">Datos para el cliente</label><textarea id="mm-datos" name="datos" rows="3" placeholder="Banco, teléfono, cédula o número de cuenta"></textarea><span class="ayuda">Se muestran al cliente cuando elige este método en la tienda online.</span></div>
    </div>
    <footer class="modal-pie"><button type="button" class="btn btn-sec" data-cerrar>Cancelar</button><button type="submit" class="btn btn-pri">Guardar método</button></footer>
  </form>
</dialog>
<?php include __DIR__ . '/admin/_cerrar.php'; ?>
