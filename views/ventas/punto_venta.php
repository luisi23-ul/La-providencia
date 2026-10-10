<?php
$menu = 'punto_venta';
$tituloPagina = 'Punto de venta';
$modoPantalla = 'pos';
$scripts = ['punto_venta'];
$productos = $productos ?? [];
$metodos = $metodos ?? [];
$clientes = $clientes ?? [];
include __DIR__ . '/../admin/_abrir.php';
$categorias = array_values(array_unique(array_filter(array_map(fn($p) => $p->nombre_categoria ?? '', $productos))));
?>
<section class="pantalla pv" id="p-punto_venta">
  <header class="pv-top">
    <div class="pv-caja"><span class="marca-logo">P</span><div><b>Caja · Tienda física</b><span><?= $e($_SESSION['nombre'] ?? '') ?><?= !empty($turno->apertura) ? ', caja abierta desde ' . $e(date('g:i a', strtotime($turno->apertura))) : '' ?></span></div></div>
    <span class="tasa">Tasa BCV <b><?= number_format($tasa, 2) ?> Bs/$</b></span>
    <button type="button" class="pv-top-btn" id="pv-ver-espera"><svg class="i"><use href="#i-recibo"/></svg><span class="txt">En espera</span><span class="cuenta" id="pv-cuenta-espera" hidden>0</span></button>
    <a href="index.php?action=ventas" class="pv-top-btn"><svg class="i"><use href="#i-lista"/></svg><span class="txt">Ventas del día</span></a>
    <a href="index.php?action=dashboard" class="pv-top-btn salir"><svg class="i"><use href="#i-salir"/></svg><span class="txt">Salir de la caja</span></a>
  </header>

  <div class="seg pv-tabs" role="group" aria-label="Vista">
    <button type="button" data-pv-vista="productos" aria-pressed="true">Productos</button>
    <button type="button" data-pv-vista="ticket" aria-pressed="false">Ticket <span id="pv-tab-cuenta"></span></button>
  </div>

  <div class="pv-cuerpo" id="pv-cuerpo" data-vista="productos">
    <section class="pv-ticket" aria-label="Ticket de venta">
      <button type="button" class="pv-cliente" id="pv-cliente"></button>
      <ol class="pv-lineas" id="pv-lineas"></ol>
      <div class="pv-totales">
        <div class="fila-total"><span>Artículos</span><b id="pv-art">0</b></div>
        <div class="fila-total" id="pv-fila-desc" hidden><span>Descuentos</span><b id="pv-desc"></b></div>
        <div class="fila-total grande"><span>Total</span><b id="pv-total">$0.00</b></div>
        <div class="fila-total"><span>En bolívares</span><b id="pv-bs">0.00 Bs</b></div>
      </div>
      <div class="pv-acciones">
        <button type="button" class="btn btn-sec" id="pv-espera">En espera</button>
        <button type="button" class="btn btn-pri pv-cobrar" id="pv-cobrar" disabled>Cobrar</button>
      </div>
    </section>

    <section class="pv-catalogo" aria-label="Productos">
      <label class="pv-scan"><svg class="i"><use href="#i-buscar"/></svg><input type="search" id="pv-scan" placeholder="Escanea el código de barras o escribe código o nombre" aria-label="Escanear o buscar producto" autocomplete="off"><kbd>Enter agrega</kbd></label>
      <div class="chips" role="group" aria-label="Categoría">
        <button type="button" class="chip-btn" data-pv-cat="" aria-pressed="true">Todas</button>
        <?php foreach ($categorias as $cat): ?>
          <button type="button" class="chip-btn" data-pv-cat="<?= $e($cat) ?>" aria-pressed="false"><?= $e($cat) ?></button>
        <?php endforeach; ?>
      </div>
      <div class="pv-grid" id="pv-grid"></div>
      <p class="vacio-filtro" id="pv-sin-productos" hidden>No hay productos con stock en tienda.</p>
      <div class="pv-barra"><span>Ticket · <span id="pv-barra-art">0</span> artículos<b id="pv-barra-total">$0.00</b></span><button type="button" class="btn btn-pri" data-pv-vista="ticket">Ver ticket</button></div>
    </section>
  </div>

  <div class="pv-cobro" id="pv-cobro" hidden>
    <div class="pc-col">
      <button type="button" class="volver pc-volver" id="pc-volver"><svg class="i"><use href="#i-volver"/></svg>Volver al ticket</button>
      <header class="pc-cab">
        <div class="pc-total"><span>Total a cobrar</span><b id="pc-total"></b><small id="pc-bs"></small></div>
        <div class="pc-cli" id="pc-cli"></div>
      </header>
      <div class="pc-prog-caja"><div class="pc-progreso"><i id="pc-prog"></i></div><span id="pc-prog-txt"></span></div>
      <section id="pc-pagos" class="pc-pagos" aria-label="Pagos de esta venta"></section>
      <section class="pc-elegir" aria-labelledby="pc-elegir-t">
        <h3 id="pc-elegir-t"></h3>
        <div class="pc-metodos" id="pc-metodos"></div>
      </section>
    </div>
    <footer class="pc-pie">
      <div class="pc-pie-in">
        <div class="pc-saldo" id="pc-saldo" aria-live="polite"></div>
        <div class="pc-validar-caja">
          <button type="button" class="btn btn-ok pv-validar" id="pc-validar">Validar venta</button>
          <p class="pc-motivo" id="pc-aviso"></p>
        </div>
      </div>
    </footer>
  </div>

  <div class="pv-listo" id="pv-listo" hidden>
    <span class="ok-ic"><svg class="i"><use href="#i-check"/></svg></span>
    <h2 id="pl-titulo"></h2>
    <p id="pl-detalle"></p>
    <p class="vuelto-grande" id="pl-vuelto"></p>
    <section class="pl-wa" aria-labelledby="pl-wa-t">
      <h3 id="pl-wa-t">Enviar comprobante por WhatsApp</h3>
      <div class="campo">
        <label for="pl-tel">Teléfono del cliente</label>
        <input id="pl-tel" type="tel" inputmode="tel" autocomplete="off" placeholder="0414-0000000">
        <span class="ayuda" id="pl-tel-ayuda"></span>
      </div>
      <details class="pl-wa-prev"><summary>Ver el mensaje que se envía</summary><p class="pl-wa-msg" id="pl-wa-msg"></p></details>
      <a class="btn btn-wa" id="pl-wa" href="#" target="_blank" rel="noopener"><svg class="i"><use href="#i-wa"/></svg><span id="pl-wa-txt">Enviar por WhatsApp</span></a>
    </section>
    <div class="acciones-pago"><a href="index.php?action=punto_venta" class="btn btn-pri">Nueva venta</a></div>
  </div>

  <form id="form-venta" action="index.php?action=guardar_venta_tienda" method="POST" hidden></form>
</section>

<dialog class="modal chico" id="modal-cliente" aria-labelledby="mcl-titulo">
  <form method="dialog">
    <header class="modal-cab"><div><h3 id="mcl-titulo">Cliente de la venta</h3><p>Busca por cédula o RIF. Si no está, regístralo aquí mismo.</p></div><button type="button" class="ic-btn" data-cerrar aria-label="Cerrar"><svg class="i"><use href="#i-x"/></svg></button></header>
    <div class="modal-cuerpo">
      <div class="pv-busca-cli">
        <select id="mcl-tipo" aria-label="Tipo de documento"><option>V</option><option>E</option><option>J</option><option>G</option></select>
        <input id="mcl-num" inputmode="numeric" placeholder="18456789" aria-label="Número de cédula o RIF" autocomplete="off">
        <button type="button" class="btn btn-pri" id="mcl-buscar">Buscar</button>
      </div>
      <div class="pv-cli-res" id="mcl-res"></div>
    </div>
    <footer class="modal-pie"><button type="button" class="btn btn-sec" id="mcl-final">Usar Consumidor final</button></footer>
  </form>
</dialog>

<dialog class="modal chico" id="modal-espera" aria-labelledby="mes-titulo">
  <form method="dialog">
    <header class="modal-cab"><div><h3 id="mes-titulo">Ventas en espera</h3><p>Tickets aparcados mientras atiendes a otro cliente.</p></div><button type="button" class="ic-btn" data-cerrar aria-label="Cerrar"><svg class="i"><use href="#i-x"/></svg></button></header>
    <div class="modal-cuerpo" id="mes-lista" style="display:flex;flex-direction:column;gap:8px"></div>
  </form>
</dialog>

<script type="application/json" id="datos-pos"><?= json_encode([
    'productos' => array_map(fn($p) => [
        'id' => (int) $p->id,
        'c' => $p->codigo ?? ('P-' . str_pad($p->id, 4, '0', STR_PAD_LEFT)),
        'n' => $p->nombre_producto,
        'precio' => (float) $p->precio,
        't' => (int) $p->stock,
        'min' => (int) ($p->stock_minimo ?? 5),
        'cat' => $p->nombre_categoria ?? '',
        'img' => !empty($p->imagen) ? 'public/uploads/' . $p->imagen : '',
    ], $productos),
    'metodos' => array_values(array_map(fn($m) => [
        'id' => (int) $m->id,
        'n' => $m->nombre,
        'mon' => ($m->moneda ?? 'Bs') === 'USD' ? '$' : 'Bs',
        'ref' => !empty($m->requiere_referencia) ? 'Referencia' : '',
    ], array_filter($metodos, fn($m) => ($m->activo ?? 1) && ($m->en_tienda ?? 1)))),
    'clientes' => array_map(fn($c) => [
        'id' => (int) $c->id,
        'doc' => $c->cedula ?? '',
        'n' => trim($c->nombre . ' ' . ($c->apellido ?? '')),
        'tel' => $c->telefono ?? '',
    ], $clientes),
    'venta' => $ventaRegistrada ?? null,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>
