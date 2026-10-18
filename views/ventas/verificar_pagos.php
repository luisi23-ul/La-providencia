<?php
$menu = 'verificar_pagos';
$tituloPagina = 'Verificar pagos';
$scripts = ['verificar_pagos'];
$pagos = $pagos ?? [];
$conteo = $conteo ?? ['pendiente' => count(array_filter($pagos, fn($p) => ($p->estado ?? $p->estado_verificacion ?? '') === 'pendiente'))];
include __DIR__ . '/../admin/_abrir.php';
$estado = $_GET['estado'] ?? 'pendiente';
$fecha = $_GET['fecha'] ?? '';
$tabs = ['pendiente' => 'Por verificar', 'aprobado' => 'Aprobados', 'rechazado' => 'Rechazados'];

$db = Database::connect();
// Corrección de clave primaria: codigo_metodo
$metodosList = $db->query("SELECT codigo_metodo AS id, codigo_metodo, nombre FROM metodos_pago ORDER BY nombre ASC")->fetchAll(PDO::FETCH_OBJ);
// Corrección de clave primaria: codigo
$productosLista = $db->query("SELECT codigo AS id, codigo, nombre_producto, precio FROM productos WHERE (deleted_at IS NULL OR deleted_at = '2000-01-01 00:00:00') AND estado = 1 ORDER BY nombre_producto ASC")->fetchAll(PDO::FETCH_OBJ);
?>
<section class="contenido" id="p-verificar_pagos">
  <header class="cab">
    <div class="cab-txt"><small>Ventas</small><h2>Verificar pagos</h2></div>
    <div class="cab-acc"><span class="tasa">Tasa BCV <b><?= number_format($tasa, 2, ',', '.') ?> Bs/$</b></span></div>
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
          $metodoNom = $p->metodo_nombre ?? $p->metodo ?? $p->codigo_metodo ?? 'Efectivo';
          $esEf = stripos($metodoNom, 'efectivo') !== false || empty($p->requiere_referencia);
          $estReal = $p->estado ?? $p->estado_verificacion ?? 'pendiente';
          $claseEstado = ['pendiente' => 'pend', 'rechazado' => 'rech'][$estReal] ?? '';
          $mt = mb_strtolower($metodoNom);
          $icono = $esEf ? 'efectivo' : (str_contains($mt, 'transfer') ? 'banco' : 'movil');
          $numOrden = $p->numero_orden ?? $p->id_venta ?? '';
          $nomCli = $p->cliente_nombre ?? $p->cliente ?? 'Consumidor Final';
        ?>
        <li><button type="button" class="vf-item <?= $claseEstado ?>" data-pago="<?= $i ?>" aria-current="<?= $i === 0 ? 'true' : 'false' ?>">
          <span class="vf-ic"><svg class="i"><use href="#i-<?= $icono ?>"/></svg></span>
          <b><?= $e($nomCli) ?>, #<?= $e($numOrden) ?></b>
          <span class="m"><?= ($p->moneda ?? 'Bs') === 'USD' ? $usd($p->monto) : number_format((float) $p->monto, 2, ',', '.') . ' Bs' ?></span>
          <small><?= $e($metodoNom) ?> · <?= $esEf ? 'en caja' : 'Ref. ' . $e($p->referencia ?: 'sin referencia') ?></small>
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
    <input type="hidden" name="numero_orden" id="mr-id">
    <input type="hidden" name="numero_pago" id="mr-pago" value="1">
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

<dialog class="modal mediano" id="modal-editar-pago" aria-labelledby="me-titulo">
  <form method="POST" action="index.php?action=editar_pago_verificacion" id="me-form">
    <input type="hidden" name="numero_orden" id="me-id-venta">
    <input type="hidden" name="numero_pago" id="me-id-pago" value="1">
    <input type="hidden" name="tasa_cambio" id="me-tasa" value="<?= $tasa ?>">
    <header class="modal-cab"><span class="modal-ic"><svg class="i"><use href="#i-editar"/></svg></span><div><h3 id="me-titulo">Editar pago</h3><p id="me-subtitulo"></p></div><button type="button" class="ic-btn" data-cerrar aria-label="Cerrar"><svg class="i"><use href="#i-x"/></svg></button></header>
    <div class="modal-cuerpo me-cuerpo">
      <div class="me-fila">
        <div class="campo">
          <label for="me-metodo">Método de pago <span class="ob">*</span></label>
          <select id="me-metodo" name="id_metodo_pago" required>
            <?php foreach ($metodosList as $m): ?><option value="<?= $e($m->codigo_metodo) ?>"><?= $e($m->nombre) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="campo">
          <label for="me-banco">Banco</label>
          <select id="me-banco" name="banco" data-buscador>
            <option value="">Sin banco</option>
            <?php foreach (['Banesco', 'Banco de Venezuela', 'Mercantil', 'Banco Provincial', 'BNC Nacional de Crédito', 'Bancaribe', 'Banco Exterior', 'Banco Plaza', 'Banco del Tesoro', 'Banco Bicentenario', 'Banplus', '100% Banco'] as $b): ?><option><?= $e($b) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="campo">
        <label for="me-ref">Referencia</label>
        <input type="text" id="me-ref" name="referencia" inputmode="numeric" autocomplete="off" placeholder="Ej.: 004512">
      </div>
      <section class="me-prods" aria-labelledby="me-prods-tit">
        <header><h4 id="me-prods-tit">Productos del pedido</h4><span class="ayuda">Agrega otros o pon 0 para quitar</span></header>
        <ul id="me-lista-prods"></ul>
        <div class="me-agregar">
          <select id="me-nuevo" data-buscador aria-label="Producto para agregar">
            <option value="">Buscar un producto para agregar…</option>
            <?php foreach ($productosLista as $pl): ?><option value="<?= $e($pl->codigo) ?>" data-nombre="<?= $e($pl->nombre_producto) ?>" data-precio="<?= (float) $pl->precio ?>"><?= $e($pl->nombre_producto) ?> · <?= $usd($pl->precio) ?></option><?php endforeach; ?>
          </select>
          <button type="button" class="btn btn-sec" id="me-agregar"><svg class="i"><use href="#i-mas"/></svg>Agregar</button>
        </div>
        <p class="me-total"><span>Nuevo total</span><b id="me-total">—</b></p>
      </section>
    </div>
    <footer class="modal-pie">
      <button type="button" class="btn btn-sec" data-cerrar>Cancelar</button>
      <button type="submit" class="btn btn-pri">Guardar cambios</button>
    </footer>
  </form>
</dialog>

<form id="form-aprobar" method="POST" action="index.php?action=aprobar_pago" hidden>
  <input type="hidden" name="numero_orden" id="ap-id">
  <input type="hidden" name="numero_pago" id="ap-pago" value="1">
</form>

<?php
$alerta = $_SESSION['alerta'] ?? null;
unset($_SESSION['alerta']);
?>
<?php if ($alerta): ?>
<script type="application/json" id="alerta-pago"><?= json_encode([
    'tipo' => ['success' => 'ok', 'error' => 'error', 'warning' => 'aviso', 'info' => 'info'][$alerta['tipo'] ?? 'info'] ?? 'info',
    'titulo' => $alerta['titulo'] ?? '',
    'texto' => $alerta['texto'] ?? '',
    'deshacer' => !empty($alerta['deshacer_id']) ? 'index.php?action=deshacer_pago&id=' . urlencode((string)$alerta['deshacer_id']) : '',
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php endif; ?>

<script type="application/json" id="datos-pagos"><?= json_encode(array_map(function ($p) use ($tasa) {
    $metodoNom = $p->metodo_nombre ?? $p->metodo ?? $p->codigo_metodo ?? 'Efectivo';
    $esEf = stripos($metodoNom, 'efectivo') !== false || empty($p->requiere_referencia);
    $numOrden = (string)($p->numero_orden ?? $p->id_venta ?? '');
    
    // Titular extraído de columnas directas o de observación
    $titular = [
        'nombre' => $p->titular_nombre ?? '',
        'doc'    => $p->titular_cedula ?? '',
        'tel'    => $p->titular_telefono ?? ''
    ];

    return [
        'id'             => (string)$numOrden,
        'venta'          => (string)$numOrden,
        'numero_pago'    => (int)($p->numero_pago ?? 1),
        'cliente'        => $p->cliente_nombre ?? $p->cliente ?? 'Consumidor Final',
        'metodo'         => $metodoNom,
        'id_metodo'      => (string)($p->codigo_metodo ?? $p->id_metodo_pago ?? ''),
        'moneda'         => $p->moneda ?? 'Bs',
        'monto'          => (float)$p->monto,
        'tasa'           => (float)($p->tasa_cambio ?? 0) > 1 ? (float)$p->tasa_cambio : (float)$tasa,
        'ref'            => $esEf ? '' : ($p->referencia ?? ''),
        'es_efectivo'    => $esEf,
        'banco'          => $esEf || ($p->banco ?? '') === 'Caja Física' ? '' : ($p->banco ?? ''),
        'origen'         => ($p->origen_venta ?? $p->origen ?? '') === 'tienda_fisica' ? 'Tienda física' : 'Online',
        'registrado_por' => $p->cajero ?? $p->registrado_por_nombre ?? '',
        'img'            => !empty($p->comprobante_img) ? 'public/uploads/' . $p->comprobante_img : '',
        'fecha'          => !empty($p->fecha_pago) ? date('d/m, g:i a', strtotime($p->fecha_pago)) : '',
        'estado'         => $p->estado ?? $p->estado_verificacion ?? 'pendiente',
        'por'            => $p->verificado_por ?? '',
        'cuando'         => !empty($p->fecha_verificacion) ? date('d/m, g:i a', strtotime($p->fecha_verificacion)) : '',
        'motivo'         => ($p->estado ?? '') === 'rechazado' ? ($p->observacion ?? '') : '',
        'titular'        => $esEf ? null : $titular,
        'total'          => (float)($p->total_venta ?? $p->total ?? 0),
        'previos'        => (float)($p->pagado_aprobado ?? 0),
    ];
}, $pagos), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>