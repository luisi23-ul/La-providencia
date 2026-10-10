<?php
$menu = 'verificar_pagos';
$tituloPagina = 'Verificar pagos';
$scripts = ['verificar_pagos'];
$pagos = $pagos ?? [];
$conteo = $conteo ?? ['pendiente' => count(array_filter($pagos, fn($p) => $p->estado_verificacion === 'pendiente'))];
include __DIR__ . '/../admin/_abrir.php';
$estado = $_GET['estado'] ?? 'pendiente';
$fecha = $_GET['fecha'] ?? '';
$tabs = ['pendiente' => 'Por verificar', 'aprobado' => 'Aprobados', 'rechazado' => 'Rechazados'];
?>
<section class="contenido" id="p-verificar_pagos">
  <header class="cab">
    <div class="cab-txt"><small>Ventas</small><h2>Verificar pagos</h2></div>
    <div class="cab-acc"><span class="tasa">Tasa BCV <b><?= number_format($tasa, 2) ?> Bs/$</b></span></div>
  </header>

  <form class="herr" method="GET" action="index.php">
    <input type="hidden" name="action" value="verificar_pagos">
    <input type="hidden" name="estado" value="<?= $e($estado) ?>">
    <div class="seg" role="group" aria-label="Estado de verificación">
      <?php foreach ($tabs as $k => $t): ?>
        <a href="index.php?action=verificar_pagos&estado=<?= $k ?><?= $fecha ? '&fecha=' . $e($fecha) : '' ?>" class="seg-link" aria-current="<?= $estado === $k ? 'page' : 'false' ?>"><?= $t ?><?php if ($k === 'pendiente' && !empty($conteo['pendiente'])): ?><span><?= (int) $conteo['pendiente'] ?></span><?php endif; ?></a>
      <?php endforeach; ?>
    </div>
    <label class="fecha-rango">Fecha del pago <input type="date" name="fecha" value="<?= $e($fecha) ?>" aria-label="Fecha del pago"></label>
    <button type="submit" class="btn btn-sec">Filtrar</button>
    <?php if ($fecha): ?><a href="index.php?action=verificar_pagos&estado=<?= $e($estado) ?>" class="btn btn-sec">Quitar fecha</a><?php endif; ?>
  </form>

  <div class="verif" id="verif">
    <section class="caja cola-caja" aria-label="Pagos">
      <ul class="cola">
        <?php foreach ($pagos as $i => $p): 
          $esEf = stripos($p->metodo ?? '', 'efectivo') !== false || empty($p->requiere_referencia);
        ?>
          <li><button type="button" class="item-pago" data-pago="<?= $i ?>" aria-current="<?= $i === 0 ? 'true' : 'false' ?>">
            <b><?= $e($p->cliente) ?>, #<?= str_pad($p->id_venta, 4, '0', STR_PAD_LEFT) ?></b>
            <span class="monto-item"><?= ($p->moneda ?? 'Bs') === 'USD' ? $usd($p->monto) : number_format($p->monto, 2) . ' Bs' ?></span>
            <span class="meta">
              <span class="chip"><?= $e($p->metodo) ?></span>
              <span class="ref"><?= $esEf ? 'No aplica' : 'Ref. ' . $e($p->referencia ?: 'sin referencia') ?></span>
            </span>
          </button></li>
        <?php endforeach; ?>
      </ul>
      <?php if (!$pagos): ?><div class="vacio-filtro">No hay pagos en esta lista.</div><?php endif; ?>
    </section>
    <section class="caja det-caja" aria-live="polite" id="det-pago"></section>
  </div>
</section>

<dialog class="modal chico" id="modal-rechazo" aria-labelledby="mr-titulo">
  <form method="POST" action="index.php?action=rechazar_pago">
    <input type="hidden" name="id" id="mr-id">
    <header class="modal-cab"><div><h3 id="mr-titulo">Rechazar pago</h3><p>El cliente verá este motivo.</p></div></header>
    <div class="modal-cuerpo">
      <div class="campo">
        <label for="mr-obs">Motivo <span class="ob">*</span></label>
        <textarea id="mr-obs" name="observacion" rows="3" required maxlength="255" placeholder="Ej.: El monto recibido no coincide con el total."></textarea>
        <span class="ayuda">Máximo 255 caracteres.</span>
      </div>
    </div>
    <footer class="modal-pie">
      <button type="button" class="btn btn-sec" data-cerrar>Cancelar</button>
      <button type="submit" class="btn btn-peligro-lleno">Rechazar pago</button>
    </footer>
  </form>
</dialog>

<form id="form-aprobar" method="POST" action="index.php?action=aprobar_pago" hidden><input type="hidden" name="id" id="ap-id"></form>

<?php if (!empty($_SESSION['alerta'])): 
  $al = $_SESSION['alerta'];
  unset($_SESSION['alerta']);
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire({
  icon: '<?= $al['tipo'] ?>',
  title: '<?= $al['titulo'] ?>',
  text: '<?= $al['texto'] ?>',
  showCancelButton: <?= !empty($al['deshacer_id']) ? 'true' : 'false' ?>,
  confirmButtonText: 'Aceptar',
  cancelButtonText: 'Deshacer acción',
  confirmButtonColor: '#0052d4',
  cancelButtonColor: '#e02424',
  reverseButtons: true
}).then((result) => {
  if (result.dismiss === Swal.DismissReason.cancel) {
    window.location.href = 'index.php?action=deshacer_pago&id=<?= (int)($al['deshacer_id'] ?? 0) ?>';
  }
});
</script>
<?php endif; ?>

<script type="application/json" id="datos-pagos"><?= json_encode(array_map(fn($p) => [
    'id' => (int) $p->id,
    'venta' => (int) $p->id_venta,
    'cliente' => $p->cliente,
    'cedula' => $p->cliente_cedula ?? '',
    'telefono' => $p->cliente_telefono ?? '',
    'metodo' => $p->metodo,
    'moneda' => $p->moneda ?? 'Bs',
    'monto' => (float) $p->monto,
    'tasa' => (float) ($p->tasa_cambio ?? $tasa),
    'ref' => (stripos($p->metodo ?? '', 'efectivo') !== false || empty($p->requiere_referencia)) ? 'No aplica' : ($p->referencia ?? ''),
    'es_efectivo' => stripos($p->metodo ?? '', 'efectivo') !== false || empty($p->requiere_referencia),
    'banco' => $p->banco ?? '',
    'origen' => $p->origen ?? '',
    'registrado_por' => $p->registrado_por_nombre ?? 'Online / Cliente',
    'img' => !empty($p->comprobante_img) ? 'public/uploads/' . $p->comprobante_img : '',
    'fecha' => !empty($p->fecha_pago) ? date('d/m/Y, g:i a', strtotime($p->fecha_pago)) : '',
    'estado' => $p->estado_verificacion,
    'por' => trim(($p->verificado_por_nombre ?? '') . (!empty($p->fecha_verificacion) ? ', ' . date('d/m g:i a', strtotime($p->fecha_verificacion)) : '')),
    'obs' => $p->observacion ?? '',
    'total' => (float) ($p->total_venta ?? 0),
    'previos' => (float) ($p->pagado_aprobado ?? 0),
], $pagos), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>