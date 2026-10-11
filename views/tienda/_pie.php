<?php
$seccionPie = $seccionPie ?? 'inicio';
$conMenuMovil = $conMenuMovil ?? true;
$clientePie = !empty($_SESSION['id_usuario']);
$waPie = preg_replace('/\D/', '', (string) ($sistema->whatsapp ?? ''));
?>
  <footer class="pie" id="contacto">
    <div><span class="logo"><?php $pn = explode(' ', $nombreTienda, 2); ?><b><?= $e($pn[0]) ?></b><?php if (!empty($pn[1])): ?><small><?= $e($pn[1]) ?></small><?php endif; ?></span><?= $e($sistema->footer_texto ?? '') ?></div>
    <div><b>Visítanos</b><?= $e($sistema->direccion ?? '') ?><br>Lunes a sábado, de 9:00 a. m. a 6:00 p. m.</div>
    <div><b>Escríbenos</b><?php if ($waPie): ?><a href="https://wa.me/<?= $e($waPie) ?>" target="_blank" rel="noopener"><i class="ti ti-brand-whatsapp" aria-hidden="true"></i><?= $e($sistema->telefono ?? $waPie) ?></a><br><?php endif; ?><?php if (!empty($sistema->email_contacto)): ?><a href="mailto:<?= $e($sistema->email_contacto) ?>"><i class="ti ti-mail" aria-hidden="true"></i><?= $e($sistema->email_contacto) ?></a><?php endif; ?></div>
  </footer>
  <?php if ($conMenuMovil): ?>
  <nav class="nav-movil" aria-label="Menú">
    <a href="index.php?action=inicio"<?= $seccionPie === 'inicio' ? ' aria-current="true"' : '' ?>><i class="ti ti-home"></i>Inicio</a>
    <a href="index.php?action=ver_catalogo"<?= $seccionPie === 'catalogo' ? ' aria-current="true"' : '' ?>><i class="ti ti-layout-grid"></i>Catálogo</a>
    <a href="index.php?action=ver_carrito" data-contador<?= $seccionPie === 'pedido' ? ' aria-current="true"' : '' ?>><i class="ti ti-shopping-bag"></i><span class="cuenta" hidden>0</span>Pedido</a>
    <a href="index.php?action=<?= $clientePie ? 'mis_pedidos' : 'login_usuario' ?>"<?= $seccionPie === 'cuenta' ? ' aria-current="true"' : '' ?>><i class="ti ti-user"></i>Cuenta</a>
  </nav>
  <?php endif; ?>
<script src="public/js/tienda.js?v=<?= filemtime(__DIR__ . '/../../public/js/tienda.js') ?>"></script>
