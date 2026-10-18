<?php
$menu = 'punto_venta';
$tituloPagina = 'Punto de venta';
$modoPantalla = 'pos';
$scripts = ['punto_venta'];
$productos = $productos ?? [];
$metodos = $metodos ?? [];
$clientes = $clientes ?? [];
include __DIR__ . '/../admin/_abrir.php';
$categorias = array_values(array_unique(array_filter(array_map(fn($p) => categoria_de($p), $productos))));
?>
<section class="pantalla pv" id="p-punto_venta">
  <header class="pv-top">
    <div class="pv-caja"><span class="marca-logo">P</span><div><b>Caja · Tienda física</b><span><?= $e($_SESSION['nombre'] ?? '') ?><?= !empty($turno->apertura) ? ', caja abierta desde ' . $e(date('g:i a', strtotime($turno->apertura))) : '' ?></span></div></div>
    <span class="tasa">Tasa BCV <b><?= number_format($tasa, 2, ',', '.') ?> Bs/$</b></span>
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
      <header class="pv-tk-cab"><div><b id="pv-tk-titulo">Ticket</b><small id="pv-tk-meta"></small></div><button type="button" class="pv-seguir" id="pv-seguir"><svg class="i"><use href="#i-mas"/></svg>Agregar productos</button></header>
      <button type="button" class="pv-cliente" id="pv-cliente"></button>
      <ol class="pv-lineas" id="pv-lineas"></ol>
      <div class="pv-vaciar-fila"><button type="button" class="pv-vaciar" id="pv-vaciar" hidden><svg class="i"><use href="#i-basura"/></svg>Vaciar ticket</button></div>
      <div class="pv-totales">
        <div class="fila-total"><span>Artículos</span><b id="pv-art">0</b></div>
        <div class="fila-total" id="pv-fila-desc" hidden><span>Descuentos</span><b id="pv-desc"></b></div>
        <div class="fila-total grande"><span>Total</span><b id="pv-total">$0.00</b></div>
        <div class="fila-total pv-fila-bs"><span>En bolívares <small class="pv-tasa-movil">a <?= number_format($tasa, 2, ',', '.') ?> Bs/$</small></span><b id="pv-bs">0.00 Bs</b></div>
      </div>
      <div class="pv-pagos-r" id="pv-pagos-r"></div>
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

    <section class="pv-reg" id="pv-cobro" aria-labelledby="pc-titulo" hidden>
      <header class="pv-reg-cab"><b id="pc-titulo">Cobro</b></header>
      <div class="pv-reg-cuerpo" id="pc-cuerpo"></div>
      <div class="pv-reg-pie" id="pc-pie" aria-live="polite"></div>
    </section>

    <section class="pv-reg" id="pv-listo" aria-labelledby="pl-cab" hidden>
      <header class="pv-reg-cab"><b id="pl-cab">Cobro</b></header>
      <div class="pv-reg-cuerpo">
        <div class="pl-ok"><span class="pl-ok-ic"><svg class="i"><use href="#i-check"/></svg></span><div><h2 id="pl-titulo"></h2><small>Se descontó del inventario de la tienda.</small></div></div>
        <div class="pl-entrega" id="pl-entrega" hidden><span>Entrega el vuelto</span><b id="pl-vuelto"></b><small id="pl-vuelto-det"></small></div>
        <section class="pl-wa" aria-labelledby="pl-wa-t">
          <h3 id="pl-wa-t">Comprobante para el cliente</h3>
          <div class="campo">
            <label for="pl-tel">WhatsApp del cliente</label>
            <input id="pl-tel" type="tel" inputmode="tel" autocomplete="off" placeholder="0414-0000000">
            <span class="ayuda" id="pl-tel-ayuda"></span>
          </div>
          <div class="pl-wa-acc">
            <button type="button" class="btn btn-wa" id="pl-compartir"><svg class="i"><use href="#i-wa"/></svg><span id="pl-compartir-txt">Enviar comprobante</span></button>
            <a class="btn btn-sec" id="pl-texto" href="#" target="_blank" rel="noopener">Enviar solo texto</a>
          </div>
          <details class="pl-wa-prev"><summary>Ver el comprobante</summary><img id="pl-img" alt="Comprobante de la venta"></details>
        </section>
      </div>
      <div class="pv-reg-pie"><a href="index.php?action=punto_venta" class="pv-accion" id="pl-nueva"><svg class="i"><use href="#i-mas"/></svg>Nueva venta</a><p class="pv-nota">o presiona <kbd>Enter</kbd></p></div>
    </section>
  </div>

  <form id="form-venta" action="index.php?action=guardar_venta_tienda" method="POST" hidden></form>
</section>

<dialog class="modal chico" id="modal-cliente" aria-labelledby="mcl-titulo">
  <form method="dialog">
    <header class="modal-cab"><span class="modal-ic"><svg class="i"><use href="#i-usuarios"/></svg></span><div><h3 id="mcl-titulo">Cliente de la venta</h3><p>Busca por cédula o RIF. Si no está, regístralo aquí mismo.</p></div><button type="button" class="ic-btn" data-cerrar aria-label="Cerrar"><svg class="i"><use href="#i-x"/></svg></button></header>
    <div class="modal-cuerpo">
      <div class="pv-busca-cli">
        <select id="mcl-tipo" aria-label="Tipo de documento"><option>V</option><option>E</option><option>J</option><option>G</option></select>
        <input id="mcl-num" inputmode="numeric" placeholder="18456789" aria-label="Número de cédula o RIF" autocomplete="off">
        <button type="button" class="btn btn-pri" id="mcl-buscar">Buscar</button>
      </div>
      <div class="pv-cli-res" id="mcl-res"></div>
    </div>
    <footer class="modal-pie"><button type="button" class="btn btn-cf" id="mcl-final"><svg class="i"><use href="#i-usuarios"/></svg>Usar Consumidor final</button></footer>
  </form>
</dialog>

<dialog class="modal chico" id="modal-variante" aria-labelledby="mv-titulo">
  <form method="dialog" id="mv-form">
    <header class="modal-cab"><span class="modal-ic"><svg class="i"><use href="#i-etiqueta"/></svg></span><div><h3 id="mv-titulo"></h3><p id="mv-precio"></p></div><button type="button" class="ic-btn" data-cerrar aria-label="Cerrar"><svg class="i"><use href="#i-x"/></svg></button></header>
    <div class="modal-cuerpo mv-cuerpo">
      <fieldset class="mv-grupo" id="mv-g-talla"><legend>Talla</legend><div class="mv-ops" id="mv-tallas"></div></fieldset>
      <fieldset class="mv-grupo" id="mv-g-color"><legend>Color</legend><div class="mv-ops" id="mv-colores"></div></fieldset>
      <p class="mv-quedan" id="mv-quedan" aria-live="polite"></p>
    </div>
    <footer class="modal-pie">
      <button type="button" class="btn btn-sec" data-cerrar>Cancelar</button>
      <button type="submit" class="btn btn-pri" id="mv-agregar" disabled>Agregar al ticket</button>
    </footer>
  </form>
</dialog>

<dialog class="modal chico" id="modal-espera" aria-labelledby="mes-titulo">
  <form method="dialog">
    <header class="modal-cab"><span class="modal-ic"><svg class="i"><use href="#i-historial"/></svg></span><div><h3 id="mes-titulo">Ventas en espera</h3><p>Tickets aparcados mientras atiendes a otro cliente.</p></div><button type="button" class="ic-btn" data-cerrar aria-label="Cerrar"><svg class="i"><use href="#i-x"/></svg></button></header>
    <div class="modal-cuerpo" id="mes-lista" style="display:flex;flex-direction:column;gap:8px"></div>
  </form>
</dialog>

<script>
document.addEventListener('input', function(e) {
  if (e.target.matches('input[name*="referencia"], input[placeholder*="referencia"], input[placeholder*="Referencia"]')) {
    const contenedor = e.target.closest('.fila-pago, .pv-pago-item, tr, div');
    if (contenedor) {
      const selectMetodo = contenedor.querySelector('select');
      if (selectMetodo) {
        const texto = selectMetodo.options[selectMetodo.selectedIndex]?.text.toLowerCase() || '';
        if (texto.includes('efectivo')) {
          e.target.value = '';
          e.target.placeholder = 'No aplica (Efectivo)';
          e.target.disabled = true;
        }
      }
    }
  }
});
document.addEventListener('change', function(e) {
  if (e.target.matches('select[name*="metodo"], .selector-metodo, select')) {
    const texto = e.target.options[e.target.selectedIndex]?.text.toLowerCase() || '';
    const contenedor = e.target.closest('.fila-pago, .pv-pago-item, tr, div');
    if (contenedor) {
      const inputRef = contenedor.querySelector('input[name*="referencia"], input[placeholder*="referencia"], input[placeholder*="Referencia"]');
      if (inputRef) {
        if (texto.includes('efectivo')) {
          inputRef.value = '';
          inputRef.placeholder = 'No aplica (Efectivo)';
          inputRef.disabled = true;
        } else {
          inputRef.disabled = false;
          inputRef.placeholder = 'N.º de referencia';
        }
      }
    }
  }
});
</script>

<script type="application/json" id="datos-pos"><?= json_encode([
    'productos' => array_map(fn($p) => [
        'id' => clave('producto', $p),
        'c' => $p->codigo ?? ('P-' . str_pad(clave('producto', $p), 4, '0', STR_PAD_LEFT)),
        'n' => $p->nombre_producto,
        'precio' => (float) $p->precio,
        't' => (int) $p->stock,
        'min' => (int) ($p->stock_minimo ?? 5),
        'cat' => categoria_de($p),
        'img' => !empty($p->imagen) ? 'public/uploads/' . $p->imagen : '',
        'v' => array_values(array_map(fn($x) => [clave('variante', $x), trim($x->talla ?? ''), trim(($x->color ?? '') . (!empty($x->modelo) ? ' ' . $x->modelo : '')), (int) $x->stock], array_filter($p->variantes ?? [], fn($x) => (int) $x->stock > 0))),
    ], $productos),
    'metodos' => array_values(array_map(fn($m) => [
        'id' => clave('metodo', $m),
        'n' => $m->nombre,
        'mon' => ($m->moneda ?? 'Bs') === 'USD' ? '$' : 'Bs',
        'ref' => !empty($m->requiere_referencia) ? 'Referencia' : '',
        'requiere_referencia' => (bool) ($m->requiere_referencia ?? 0),
        'es_efectivo' => stripos($m->nombre ?? '', 'efectivo') !== false,
    ], array_filter($metodos, fn($m) => ($m->activo ?? 1) && ($m->en_tienda ?? 1)))),
    'clientes' => array_map(fn($c) => [
        'id' => clave('cliente', $c),
        'doc' => $c->cedula ?? '',
        'n' => trim($c->nombre . ' ' . ($c->apellido ?? '')),
        'tel' => $c->telefono ?? '',
    ], $clientes),
    'venta' => $ventaRegistrada ?? null,
    'puedeDescuento' => $puede('descuentos_pos'),
    'cajero' => $_SESSION['nombre'] ?? '',
    'tienda' => [
        'nombre' => $config->nombre_empresa ?? $nombreTienda,
        'rif' => $config->rif ?? '',
        'direccion' => $config->direccion ?? '',
        'telefono' => $config->telefono ?? '',
        'logo' => !empty($config->logo_path) ? $config->logo_path : 'public/img/logo-removebg.png',
    ],
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>