<?php
$menu = 'punto_venta';
$tituloPagina = 'Punto de venta';
$modoPantalla = 'pos';
$scripts = ['punto_venta'];
$productos = $productos ?? [];
$metodos = $metodos ?? [];
$clientes = $clientes ?? [];
$turno = $turno ?? null;
$arqueo = (object) ($arqueo ?? []);
$turnoDesde = $turno->fecha_apertura ?? $turno->apertura ?? '';
$turnoAbierto = !empty($turno) && $turnoDesde !== '';
$turnoViejo = $turnoAbierto && date('Y-m-d', strtotime($turnoDesde)) < date('Y-m-d');
$turnoEstado = $turnoViejo ? 'viejo' : ($turnoAbierto ? 'abierto' : 'sin');
$horaCorta = fn($f) => str_replace(['am', 'pm'], ['a. m.', 'p. m.'], date('g:i a', strtotime($f)));
$bsFmt = fn($v) => number_format((float) $v, 2, ',', '.') . ' Bs';
$cuadre = function ($d) { $d = round((float) $d, 2); return $d == 0 ? ['Cuadra', 'ok'] : ($d > 0 ? ['Sobra', 'sobra'] : ['Falta', 'falta']); };
$cierre = null;
if (!$turnoAbierto && !empty($_GET['comprobante_turno']) && !empty($_GET['cajero'])) {
    $conexion = isset($this) && isset($this->db) ? $this->db : ($db ?? null);
    if ($conexion) {
        require_once __DIR__ . '/../../models/TurnoCajaModel.php';
        $res = (new TurnoCajaModel($conexion))->obtenerResumenTurno((string) $_GET['cajero'], (int) $_GET['comprobante_turno']);
        if ($res && ($res['turno']->estado ?? '') === 'cerrado') {
            $t = $res['turno'];
            $espUsd = (float) $t->fondo_usd;
            $espBs = (float) $t->fondo_bs;
            foreach ($res['desglose'] as $d) {
                $met = strtolower($d->codigo_metodo ?? '');
                $mon = strtoupper(trim($d->moneda ?? ''));
                if ($met === 'efectivo_usd' || ($met === 'efectivo' && $mon === 'USD')) $espUsd += (float) $d->total_monto;
                elseif ($met === 'efectivo_bs' || ($met === 'efectivo' && $mon === 'BS')) $espBs += (float) $d->total_monto;
            }
            $stNom = $conexion->prepare('SELECT nombre FROM usuarios WHERE cedula = ? LIMIT 1');
            $stNom->execute([$t->cedula_cajero]);
            $cierre = (object) ['t' => $t, 'nombre' => ucwords(mb_strtolower((string) ($stNom->fetchColumn() ?: 'Cajero'))), 'esp_usd' => $espUsd, 'esp_bs' => $espBs, 'cobros' => $res['desglose']];
        }
    }
}
include __DIR__ . '/../admin/_abrir.php';
$categorias = array_values(array_unique(array_filter(array_map(fn($p) => categoria_de($p), $productos))));
?>
<section class="pantalla pv<?= $cierre ? ' tc-con-cierre' : '' ?>" id="p-punto_venta">
  <header class="pv-top">
    <div class="pv-caja"><span class="marca-logo">P</span><div><b>Caja · Tienda física</b>
      <?php if ($turnoViejo): ?>
        <span class="tc-estado viejo"><svg class="i"><use href="#i-alerta"/></svg>Turno del <?= $e(date('d/m', strtotime($turnoDesde))) ?> sin cerrar</span>
      <?php elseif ($turnoAbierto): ?>
        <span class="tc-estado abierto"><svg class="i"><use href="#i-reloj"/></svg>Turno abierto<span class="tc-quien">, <?= $e($turno->nombre_apertura ?? '') ?>,</span> desde <?= $e($horaCorta($turnoDesde)) ?></span>
      <?php else: ?>
        <span class="tc-estado sin"><svg class="i"><use href="#i-candado"/></svg>Sin turno</span>
      <?php endif; ?>
    </div></div>
    <span class="tasa">Tasa BCV <b><?= number_format($tasa, 2, ',', '.') ?> Bs/$</b></span>
    <?php if ($turnoAbierto): ?>
      <button type="button" class="pv-top-btn<?= $turnoViejo ? ' tc-urge' : '' ?>" data-abrir-modal="modal-turno-cerrar" aria-label="Cerrar turno"><svg class="i"><use href="#i-arqueo"/></svg><span class="txt">Cerrar turno</span></button>
    <?php else: ?>
      <button type="button" class="pv-top-btn tc-urge" data-abrir-modal="modal-turno-abrir" aria-label="Iniciar turno"><svg class="i"><use href="#i-caja"/></svg><span class="txt">Iniciar turno</span></button>
    <?php endif; ?>
    <button type="button" class="pv-top-btn" id="pv-ver-espera"><svg class="i"><use href="#i-recibo"/></svg><span class="txt">En espera</span><span class="cuenta" id="pv-cuenta-espera" hidden>0</span></button>
    <a href="index.php?action=ventas" class="pv-top-btn"><svg class="i"><use href="#i-lista"/></svg><span class="txt">Ventas del día</span></a>
    <a href="index.php?action=dashboard" class="pv-top-btn salir"><svg class="i"><use href="#i-salir"/></svg><span class="txt">Salir de la caja</span></a>
  </header>

  <?php if ($turnoViejo): ?>
    <p class="tc-franja" role="status"><svg class="i"><use href="#i-alerta"/></svg><span>Este turno se abrió el <?= $e(date('d/m/Y', strtotime($turnoDesde))) ?> a las <?= $e($horaCorta($turnoDesde)) ?> Ciérralo y abre uno nuevo para poder cobrar.</span></p>
  <?php endif; ?>

  <?php if ($cierre): $ct = $cierre->t; $difUsd = (float) $ct->cierre_usd - $cierre->esp_usd; $difBs = (float) $ct->cierre_bs - $cierre->esp_bs; ?>
  <section class="tc-cerrado" aria-labelledby="tcc-titulo">
    <header class="tcc-cab">
      <span class="pl-ok-ic"><svg class="i"><use href="#i-check"/></svg></span>
      <div><h2 id="tcc-titulo">Turno #<?= (int) $ct->numero_turno ?> cerrado</h2><p><?= $e($cierre->nombre) ?>, de <?= $e($horaCorta($ct->fecha_apertura)) ?> a <?= $e($horaCorta($ct->fecha_cierre)) ?><?= date('Y-m-d', strtotime($ct->fecha_apertura)) !== date('Y-m-d') ? ' del ' . $e(date('d/m/Y', strtotime($ct->fecha_apertura))) : '' ?></p></div>
    </header>
    <div class="tcc-filas">
      <?php foreach ([['Caja en dólares', $usd($ct->fondo_usd), $usd($cierre->esp_usd), $usd($ct->cierre_usd), $difUsd, $usd(abs($difUsd))], ['Caja en bolívares', $bsFmt($ct->fondo_bs), $bsFmt($cierre->esp_bs), $bsFmt($ct->cierre_bs), $difBs, $bsFmt(abs($difBs))]] as [$tit, $fondo, $esp, $cont, $dif, $difTxt]): [$cq, $cc] = $cuadre($dif); ?>
        <article class="tcc-fila">
          <h3><?= $tit ?></h3>
          <dl>
            <div><dt>Fondo</dt><dd><?= $fondo ?></dd></div>
            <div><dt>Esperado</dt><dd><?= $esp ?></dd></div>
            <div><dt>Contado</dt><dd><?= $cont ?></dd></div>
            <div class="tcc-dif"><dt>Diferencia</dt><dd><?= $cc === 'ok' ? '' : ($cc === 'sobra' ? '+' : '−') . $difTxt ?> <span class="chip tc-chip <?= $cc ?>"><?= $cq ?></span></dd></div>
          </dl>
        </article>
      <?php endforeach; ?>
    </div>
    <section class="tcc-cobros" aria-labelledby="tcc-cobros-t">
      <h3 id="tcc-cobros-t">Cobros del turno</h3>
      <?php if (!$cierre->cobros): ?>
        <p class="tcc-vacio">No hubo cobros en este turno.</p>
      <?php else: $totUsd = 0; $totN = 0; ?>
        <ul>
          <?php foreach ($cierre->cobros as $c): $esBs = in_array(strtoupper(trim((string) $c->moneda)), ['BS', 'VES'], true); $totUsd += (float) $c->total_usd; $totN += (int) $c->cantidad_transacciones; ?>
            <li><span><b><?= $e($c->metodo_nombre ?: $c->codigo_metodo) ?></b><small><?= (int) $c->cantidad_transacciones ?> <?= (int) $c->cantidad_transacciones === 1 ? 'pago' : 'pagos' ?></small></span><span class="tcc-monto"><b><?= $esBs ? $bsFmt($c->total_monto) : $usd($c->total_monto) ?></b><small><?= $esBs ? $usd($c->total_usd) : $bsFmt($c->total_bs) ?></small></span></li>
          <?php endforeach; ?>
        </ul>
        <p class="tcc-total"><span>Total cobrado <small><?= $totN ?> <?= $totN === 1 ? 'pago' : 'pagos' ?></small></span><b><?= $usd($totUsd) ?></b></p>
      <?php endif; ?>
    </section>
    <?php if (trim((string) $ct->nota_cierre) !== ''): ?>
      <p class="tcc-nota"><b>Nota del cierre</b><?= $e($ct->nota_cierre) ?></p>
    <?php endif; ?>
    <footer class="tcc-acc">
      <div class="tcc-rep"><span>Comprobante del cierre</span><?php $exportar = ['tipo' => 'cierre_caja', 'filtros' => ['cedula' => (string) $ct->cedula_cajero, 'turno' => (int) $ct->numero_turno]]; include __DIR__ . '/../admin/_exportar.php'; ?></div>
      <button type="button" class="btn btn-pri" data-abrir-modal="modal-turno-abrir"><svg class="i"><use href="#i-caja"/></svg>Iniciar nuevo turno</button>
    </footer>
  </section>
  <?php endif; ?>

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

<dialog class="modal chico" id="modal-turno-abrir" aria-labelledby="mta-titulo">
  <form method="POST" action="index.php?action=abrir_turno">
    <header class="modal-cab"><span class="modal-ic"><svg class="i"><use href="#i-caja"/></svg></span><div><h3 id="mta-titulo">Iniciar turno</h3><p>Cuenta el efectivo con el que arranca la caja.</p></div><button type="button" class="ic-btn" data-cerrar aria-label="Cerrar"><svg class="i"><use href="#i-x"/></svg></button></header>
    <div class="modal-cuerpo tc-form">
      <div class="tc-fondos">
        <div class="campo"><label for="mta-usd">Fondo en dólares</label><span class="tc-monto"><span class="tc-unidad" aria-hidden="true">$</span><input id="mta-usd" name="fondo_usd" type="number" min="0" step="0.01" inputmode="decimal" value="0" required></span></div>
        <div class="campo"><label for="mta-bs">Fondo en bolívares</label><span class="tc-monto"><span class="tc-unidad" aria-hidden="true">Bs</span><input id="mta-bs" name="fondo_bs" type="number" min="0" step="0.01" inputmode="decimal" value="0" required></span></div>
      </div>
      <p class="tc-tasa"><svg class="i"><use href="#i-dolar"/></svg><span>Tasa del día</span><b><?= number_format($tasa, 2, ',', '.') ?> Bs/$</b></p>
      <div class="campo"><label for="mta-nota">Nota <span class="tc-opc">(opcional)</span></label><textarea id="mta-nota" name="nota_apertura" maxlength="255" rows="2"></textarea></div>
    </div>
    <footer class="modal-pie">
      <button type="button" class="btn btn-sec" data-cerrar>Cancelar</button>
      <button type="submit" class="btn btn-pri">Iniciar turno</button>
    </footer>
  </form>
</dialog>

<?php if ($turnoAbierto): ?>
<dialog class="modal chico tc-modal-cierre" id="modal-turno-cerrar" aria-labelledby="mtc-titulo">
  <form method="POST" action="index.php?action=cerrar_turno" id="mtc-form">
    <input type="hidden" name="codigo_turno" value="<?= $e($turno->codigo_turno ?? '') ?>">
    <header class="modal-cab"><span class="modal-ic"><svg class="i"><use href="#i-arqueo"/></svg></span><div><h3 id="mtc-titulo">Cerrar turno #<?= $e($turno->codigo_turno ?? '') ?></h3><p>Cuenta el efectivo de la caja y escribe cuánto hay.</p></div><button type="button" class="ic-btn" data-cerrar aria-label="Cerrar"><svg class="i"><use href="#i-x"/></svg></button></header>
    <div class="tc-parcial"><p><b>Arqueo parcial</b><span>Imprímelo para contar sin cerrar el turno.</span></p><div class="tc-parcial-btn"><?php $exportar = ['tipo' => 'cierre_caja', 'filtros' => ['cedula' => (string) ($turno->cedula_cajero ?? ''), 'turno' => (int) ($turno->codigo_turno ?? 0)]]; include __DIR__ . '/../admin/_exportar.php'; ?></div></div>
    <div class="modal-cuerpo tc-form">
      <?php foreach ([['usd', 'Efectivo en dólares', 'Contado en dólares', '$', 'contado_usd', (float) ($arqueo->esperado_usd ?? 0), $usd($arqueo->esperado_usd ?? 0)], ['bs', 'Efectivo en bolívares', 'Contado en bolívares', 'Bs', 'contado_bs', (float) ($arqueo->esperado_bs ?? 0), $bsFmt($arqueo->esperado_bs ?? 0)]] as [$k, $tit, $lbl, $uni, $name, $espNum, $espTxt]): ?>
        <section class="tc-arq" aria-labelledby="mtc-t-<?= $k ?>">
          <header><h4 id="mtc-t-<?= $k ?>"><?= $tit ?></h4><span>Esperado <b><?= $espTxt ?></b></span></header>
          <div class="campo"><label for="mtc-<?= $k ?>"><?= $lbl ?></label><span class="tc-monto"><span class="tc-unidad" aria-hidden="true"><?= $uni ?></span><input id="mtc-<?= $k ?>" name="<?= $name ?>" type="number" min="0" step="0.01" inputmode="decimal" required data-esperado="<?= number_format($espNum, 2, '.', '') ?>" data-moneda="<?= $k ?>"></span></div>
          <p class="tc-dif"><span>Diferencia</span><output for="mtc-<?= $k ?>" id="mtc-dif-<?= $k ?>" aria-live="polite"><b>Por contar</b></output></p>
        </section>
      <?php endforeach; ?>
      <div class="campo"><label for="mtc-nota">Nota <span class="tc-opc" id="mtc-nota-req">(obligatoria si no cuadra)</span></label><textarea id="mtc-nota" name="nota_cierre" maxlength="255" rows="2"></textarea></div>
    </div>
    <footer class="modal-pie">
      <button type="button" class="btn btn-sec" data-cerrar>Cancelar</button>
      <button type="submit" class="btn btn-pri">Cerrar turno</button>
    </footer>
  </form>
</dialog>
<?php endif; ?>

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
        'n' => trim(($c->nombre_razon_social ?? $c->nombre ?? '') . ' ' . ($c->apellido ?? '')),
        'tel' => $c->telefono ?? '',
    ], $clientes),
    'venta' => $ventaRegistrada ?? null,
    'puedeDescuento' => $puede('descuentos_pos'),
    'cajero' => $_SESSION['nombre'] ?? '',
    'turno' => $turnoEstado,
    'tienda' => [
        'nombre' => $config->nombre_empresa ?? $nombreTienda,
        'rif' => $config->rif ?? '',
        'direccion' => $config->direccion ?? '',
        'telefono' => $config->telefono ?? '',
        'logo' => !empty($config->logo_path) ? $config->logo_path : 'public/img/logo-removebg.png',
    ],
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>