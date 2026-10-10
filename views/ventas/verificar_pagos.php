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

  <form class="filtros" method="GET" action="index.php" aria-label="Filtros">
    <input type="hidden" name="action" value="verificar_pagos">
    <input type="hidden" name="estado" value="<?= $e($estado) ?>">
    <?php $periodoFiltro = ['modo' => 'dia', 'todo' => true]; include __DIR__ . '/../admin/_periodo.php'; ?>
    <nav class="f-grupo" aria-label="Estado de verificación">
      <?php foreach ($tabs as $k => $t): ?>
      <a href="index.php?action=verificar_pagos&amp;estado=<?= $k ?><?= $fecha ? '&amp;fecha=' . $e($fecha) : '' ?>" class="f-op" aria-current="<?= $estado === $k ? 'page' : 'false' ?>"><?= $t ?><?php if ($k === 'pendiente' && !empty($conteo['pendiente'])): ?><span class="f-cuenta"><?= (int) $conteo['pendiente'] ?></span><?php endif; ?></a>
      <?php endforeach; ?>
    </nav>
    <?php if ($fecha): ?>
    <a href="index.php?action=verificar_pagos&amp;estado=<?= $e($estado) ?>" class="f-quitar"><svg class="i"><use href="#i-x"/></svg>Quitar fecha</a>
    <?php endif; ?>
  </form>

  <div class="vf" id="verif">
    <section class="vf-lista" aria-label="Pagos">
      <label class="buscar"><svg class="i"><use href="#i-buscar"/></svg><input type="search" placeholder="Cliente, n.º de venta o referencia" aria-label="Buscar pago" data-filtro-tabla="vf-cola" data-filtro-vacio="vf-sin-resultados"></label>
      <ul class="vf-items" id="vf-cola" data-paginar="10">
        <?php foreach ($pagos as $i => $p):
          $esEf = stripos($p->metodo ?? '', 'efectivo') !== false || empty($p->requiere_referencia);
          $claseEstado = ['pendiente' => 'pend', 'rechazado' => 'rech'][$p->estado_verificacion] ?? '';
          $mt = mb_strtolower($p->metodo ?? '');
          $icono = $esEf ? 'efectivo' : (str_contains($mt, 'transfer') ? 'banco' : 'movil');
        ?>
        <li><button type="button" class="vf-item <?= $claseEstado ?>" data-pago="<?= $i ?>" aria-current="<?= $i === 0 ? 'true' : 'false' ?>">
          <span class="vf-ic"><svg class="i"><use href="#i-<?= $icono ?>"/></svg></span>
          <b><?= $e($p->cliente) ?>, #<?= str_pad($p->id_venta, 4, '0', STR_PAD_LEFT) ?></b>
          <span class="m"><?= ($p->moneda ?? 'Bs') === 'USD' ? $usd($p->monto) : number_format((float) $p->monto, 2) . ' Bs' ?></span>
          <small><?= $e($p->metodo) ?> · <?= $esEf ? 'en caja' : 'Ref. ' . $e($p->referencia ?: 'sin referencia') ?></small>
        </button></li>
        <?php endforeach; ?>
      </ul>
      <p class="vacio-filtro" id="vf-sin-resultados" hidden>Ningún pago coincide con esa búsqueda.</p>
      <?php if (!$pagos): ?><p class="vacio-filtro">No hay pagos en esta lista.</p><?php endif; ?>
    </section>
    <section class="vf-det" aria-live="polite" id="det-pago"></section>
  </div>
</section>

<dialog class="modal chico" id="modal-rechazo" aria-labelledby="mr-titulo">
  <form method="POST" action="index.php?action=rechazar_pago">
    <input type="hidden" name="id" id="mr-id">
    <header class="modal-cab"><span class="modal-ic peligro"><svg class="i"><use href="#i-x"/></svg></span><div><h3 id="mr-titulo">Rechazar pago</h3><p>El cliente verá este motivo.</p></div></header>
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

<?php
$alerta = $_SESSION['alerta'] ?? null;
unset($_SESSION['alerta']);
$titularDe = function ($obs) {
    $d = ['nombre' => '', 'doc' => '', 'tel' => ''];
    if (preg_match('/Nombre:\s*([^|]*)/u', $obs ?? '', $m)) $d['nombre'] = trim($m[1]);
    if (preg_match('/Titular:\s*([^|]*)/u', $obs ?? '', $m)) $d['doc'] = trim($m[1]);
    if (preg_match('/Tel:\s*([^|]*)/u', $obs ?? '', $m)) $d['tel'] = trim($m[1]);
    return $d;
};
?>
<?php if ($alerta): ?>
<script type="application/json" id="alerta-pago"><?= json_encode([
    'tipo' => ['success' => 'ok', 'error' => 'error', 'warning' => 'aviso', 'info' => 'info'][$alerta['tipo'] ?? 'info'] ?? 'info',
    'titulo' => $alerta['titulo'] ?? '',
    'texto' => $alerta['texto'] ?? '',
    'deshacer' => !empty($alerta['deshacer_id']) ? 'index.php?action=deshacer_pago&id=' . (int) $alerta['deshacer_id'] : '',
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php endif; ?>

<script type="application/json" id="datos-pagos"><?= json_encode(array_map(function ($p) use ($tasa, $titularDe) {
    $esEf = stripos($p->metodo ?? '', 'efectivo') !== false || empty($p->requiere_referencia);
    $titular = $titularDe($p->observacion ?? '');
    return [
        'id' => (int) $p->id,
        'venta' => (int) $p->id_venta,
        'cliente' => $p->cliente,
        'metodo' => $p->metodo,
        'moneda' => $p->moneda ?? 'Bs',
        'monto' => (float) $p->monto,
        'tasa' => (float) ($p->tasa_cambio ?? 0) > 1 ? (float) $p->tasa_cambio : (float) $tasa,
        'ref' => $esEf ? '' : ($p->referencia ?? ''),
        'es_efectivo' => $esEf,
        'banco' => $esEf || ($p->banco ?? '') === 'Caja Física' ? '' : ($p->banco ?? ''),
        'origen' => ($p->origen ?? '') === 'tienda_fisica' ? 'Tienda física' : (($p->origen ?? '') === 'online' ? 'Online' : ''),
        'registrado_por' => $p->registrado_por_nombre ?? '',
        'img' => !empty($p->comprobante_img) ? 'public/uploads/' . $p->comprobante_img : '',
        'fecha' => !empty($p->fecha_pago) ? date('d/m, g:i a', strtotime($p->fecha_pago)) : '',
        'estado' => $p->estado_verificacion,
        'por' => $p->verificado_por_nombre ?? '',
        'cuando' => !empty($p->fecha_verificacion) ? date('d/m, g:i a', strtotime($p->fecha_verificacion)) : '',
        'motivo' => $p->estado_verificacion === 'rechazado' ? ($p->observacion ?? '') : '',
        'titular' => $esEf ? null : $titular,
        'total' => (float) ($p->total_venta ?? 0),
        'previos' => (float) ($p->pagado_aprobado ?? 0),
    ];
}, $pagos), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>
