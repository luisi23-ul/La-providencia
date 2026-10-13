<?php
include __DIR__ . '/tienda/_comun.php';
$clienteCarrito = !empty($_SESSION['id_usuario']) && (int) ($_SESSION['rol'] ?? 0) === 3;
?>
  <section class="carro" id="carro" data-sesion="<?= $clienteCarrito ? '1' : '0' ?>">
    <h1 class="sec-tit">Tu pedido</h1>

    <div class="carro-vacio" data-vista="vacio" hidden>
      <i class="ti ti-shopping-bag" aria-hidden="true"></i>
      <h2>Todavía no elegiste nada</h2>
      <p>Agrega los artículos que desees y aquí verás el total antes de enviarlo por WhatsApp.</p>
      <a class="btn" href="index.php?action=ver_catalogo">Ver el catálogo</a>
    </div>

    <div class="enviado" data-vista="enviado" hidden>
      <i class="ti ti-circle-check" aria-hidden="true"></i>
      <h2>¡Pedido enviado!</h2>
      <p data-enviado-txt>Te confirmamos disponibilidad y validación de pago por WhatsApp.</p>
      <a class="btn sec" href="#" data-wa-otra target="_blank" rel="noopener"><i class="ti ti-brand-whatsapp" aria-hidden="true"></i>Abrir WhatsApp otra vez</a>
      <a class="carro-seguir" href="index.php?action=ver_catalogo">Seguir viendo productos</a>
    </div>

    <div class="carro-grid" data-vista="lista" hidden>
      <ul class="carro-lista" id="carro-lista"></ul>
      <aside class="carro-res" aria-label="Resumen">
        <dl>
          <div><dt>Artículos</dt><dd data-prendas>0</dd></div>
          <div class="carro-total"><dt>Total</dt><dd><b data-total>$0.00</b><small data-total-bs>0,00 Bs</small></dd></div>
        </dl>

        <!-- Bloque Forma de Pago y Banco -->
        <div class="bloque-metodo-pago" style="margin: 16px 0; padding: 14px; background: #faf8f5; border: 1px solid #e2ded9; border-radius: 8px;">
          <h3 style="margin: 0 0 12px 0; font-size: 15px; font-weight: 600; color: #333;"><i class="ti ti-wallet"></i> Información de Pago</h3>
          
          <label for="metodo_pago" style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 5px; color: #555;">¿Cómo vas a pagar?:</label>
          <select id="metodo_pago" name="metodo_pago" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #ccc; background: #fff; margin-bottom: 12px; font-size: 14px;">
            <option value="Pago Móvil">Pago Móvil</option>
            <option value="Transferencia">Transferencia Bancaria</option>
            <option value="Efectivo $">Efectivo Divisas ($)</option>
            <option value="Efectivo Bs">Efectivo Bolívares (Bs)</option>
          </select>

          <div id="campo_banco">
            <label for="banco_pago" style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 5px; color: #555;">¿De qué banco pagas?:</label>
            <select id="banco_pago" name="banco_pago" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #ccc; background: #fff; font-size: 14px;">
              <option value="Banco de Venezuela">Banco de Venezuela (BDV)</option>
              <option value="Banesco">Banesco</option>
              <option value="Mercantil">Mercantil</option>
              <option value="BBVA Provincial">BBVA Provincial</option>
              <option value="Bancamiga">Bancamiga</option>
              <option value="BNC">Banco Nacional de Crédito (BNC)</option>
              <option value="Bancaribe">Bancaribe</option>
              <option value="Otro Banco">Otro Banco</option>
            </select>
          </div>
        </div>

        <?php if (!$clienteCarrito): ?>
        <p class="carro-nota"><i class="ti ti-info-circle" aria-hidden="true"></i>Para enviarlo te pediremos entrar con tu cuenta. Tu pedido no se pierde.</p>
        <?php endif; ?>

        <p class="aviso-form" data-error hidden><i class="ti ti-alert-circle" aria-hidden="true"></i><span></span></p>
        <button class="wa" type="button" data-pedir><i class="ti ti-brand-whatsapp"></i>Confirmar y Enviar Pedido</button>
        <a class="carro-seguir" href="index.php?action=ver_catalogo">Seguir viendo productos</a>
      </aside>
    </div>
  </section>

  <div class="barra-comprar barra-pedido" data-barra-pedido hidden>
    <div><b data-total>$0.00</b><span data-total-bs>0,00 Bs</span></div>
    <button class="wa" type="button" data-pedir><i class="ti ti-brand-whatsapp"></i>Pedir</button>
  </div>

  <script>
  // Ocultar selector de banco cuando el método es efectivo
  document.addEventListener('DOMContentLoaded', function() {
    var selMetodo = document.getElementById('metodo_pago');
    var campoBanco = document.getElementById('campo_banco');
    if (selMetodo && campoBanco) {
      selMetodo.addEventListener('change', function() {
        if (this.value.indexOf('Efectivo') !== -1) {
          campoBanco.style.display = 'none';
        } else {
          campoBanco.style.display = 'block';
        }
      });
    }
  });
  </script>

<?php $seccionPie = 'pedido'; include __DIR__ . '/tienda/_pie.php'; ?>