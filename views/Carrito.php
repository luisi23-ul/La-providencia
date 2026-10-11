<?php
include __DIR__ . '/tienda/_comun.php';
$clienteCarrito = !empty($_SESSION['id_usuario']);
?>
  <section class="carro" id="carro" data-sesion="<?= $clienteCarrito ? '1' : '0' ?>">
    <h1 class="sec-tit">Tu pedido</h1>

    <div class="carro-vacio" data-vista="vacio" hidden>
      <i class="ti ti-shopping-bag" aria-hidden="true"></i>
      <h2>Todavía no elegiste nada</h2>
      <p>Agrega las prendas que te gusten y aquí verás el total antes de enviarlo por WhatsApp.</p>
      <a class="btn" href="index.php?action=ver_catalogo">Ver el catálogo</a>
    </div>

    <div class="enviado" data-vista="enviado" hidden>
      <i class="ti ti-circle-check" aria-hidden="true"></i>
      <h2>¡Pedido enviado!</h2>
      <p data-enviado-txt>Te confirmamos disponibilidad y forma de pago por WhatsApp.</p>
      <a class="btn sec" href="#" data-wa-otra target="_blank" rel="noopener"><i class="ti ti-brand-whatsapp" aria-hidden="true"></i>Abrir WhatsApp otra vez</a>
      <a class="carro-seguir" href="index.php?action=ver_catalogo">Seguir viendo prendas</a>
    </div>

    <div class="carro-grid" data-vista="lista" hidden>
      <ul class="carro-lista" id="carro-lista"></ul>
      <aside class="carro-res" aria-label="Resumen">
        <dl>
          <div><dt>Prendas</dt><dd data-prendas>0</dd></div>
          <div class="carro-total"><dt>Total</dt><dd><b data-total>$0.00</b><small data-total-bs>0,00 Bs</small></dd></div>
        </dl>
        <?php if (!$clienteCarrito): ?>
        <p class="carro-nota"><i class="ti ti-info-circle" aria-hidden="true"></i>Para enviarlo te pediremos entrar con tu cuenta. Tu pedido no se pierde.</p>
        <?php endif; ?>
        <p class="aviso-form" data-error hidden><i class="ti ti-alert-circle" aria-hidden="true"></i><span></span></p>
        <button class="wa" type="button" data-pedir><i class="ti ti-brand-whatsapp"></i>Pedir por WhatsApp</button>
        <a class="carro-seguir" href="index.php?action=ver_catalogo">Seguir viendo prendas</a>
      </aside>
    </div>
  </section>

  <div class="barra-comprar barra-pedido" data-barra-pedido hidden>
    <div><b data-total>$0.00</b><span data-total-bs>0,00 Bs</span></div>
    <button class="wa" type="button" data-pedir><i class="ti ti-brand-whatsapp"></i>Pedir</button>
  </div>
<?php $seccionPie = 'pedido'; include __DIR__ . '/tienda/_pie.php'; ?>
