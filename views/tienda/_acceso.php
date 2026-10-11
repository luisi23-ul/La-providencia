<?php
include_once __DIR__ . '/_comun.php';
$pestana = $pestana ?? 'entrar';
$volver = in_array($_GET['volver'] ?? '', ['carrito', 'pedidos'], true) ? $_GET['volver'] : '';
$errores = [
    'credenciales' => 'Correo o contraseña incorrectos. Revisa los datos e inténtalo otra vez.',
    'inactiva' => 'Tu cuenta está desactivada. Escríbenos por WhatsApp para ayudarte.',
    'correo' => 'Ya existe una cuenta con ese correo. Entra con tu contraseña.',
];
$error = $errores[$_GET['error'] ?? ''] ?? '';
$esRegistro = $pestana === 'registro';
?>
  <section class="acceso">
    <figure class="acceso-foto"><img src="public/img/tienda/interior-tienda.webp" alt="Percheros con ropa dentro de la tienda" width="1200" height="1400"></figure>

    <div class="acceso-panel">
      <?php if ($volver === 'carrito'): ?>
      <div class="acceso-aviso"><i class="ti ti-shopping-bag" aria-hidden="true"></i><p><b>Tu pedido te espera.</b> Entra o crea tu cuenta para enviarlo por WhatsApp. No se pierde nada.</p></div>
      <?php endif; ?>

      <div class="pestanas" role="tablist" aria-label="Acceso">
        <button type="button" role="tab" id="tab-entrar" aria-controls="p-entrar" aria-selected="<?= $esRegistro ? 'false' : 'true' ?>" data-pestana="p-entrar">Entrar</button>
        <button type="button" role="tab" id="tab-registro" aria-controls="p-registro" aria-selected="<?= $esRegistro ? 'true' : 'false' ?>" data-pestana="p-registro">Crear cuenta</button>
      </div>

      <form id="p-entrar" class="acceso-form" role="tabpanel" aria-labelledby="tab-entrar" action="index.php?action=valider_login_registro" method="post"<?= $esRegistro ? ' hidden' : '' ?>>
        <h1>Qué bueno verte otra vez</h1>
        <?php if ($error && !$esRegistro): ?><p class="aviso-form" role="alert"><i class="ti ti-alert-circle" aria-hidden="true"></i><?= $e($error) ?></p><?php endif; ?>
        <?php if ($volver): ?><input type="hidden" name="volver" value="<?= $e($volver) ?>"><?php endif; ?>
        <div class="campo"><label for="e-correo">Correo</label><input id="e-correo" name="correo_ingreso" type="email" autocomplete="email" placeholder="nombre@correo.com" required></div>
        <div class="campo"><label for="e-clave">Contraseña</label><div class="con-ojo"><input id="e-clave" name="clave_ingreso" type="password" autocomplete="current-password" required><button type="button" data-ver-clave="e-clave" aria-label="Mostrar contraseña"><i class="ti ti-eye"></i></button></div></div>
        <button class="btn" type="submit"><?= $volver === 'carrito' ? 'Entrar y seguir con mi pedido' : 'Entrar' ?></button>
        <p class="acceso-cambio">¿Primera vez aquí? <button type="button" class="enlace" data-ir-pestana="tab-registro">Crea tu cuenta</button></p>
      </form>

      <form id="p-registro" class="acceso-form" role="tabpanel" aria-labelledby="tab-registro" action="index.php?action=registrar_cliente" method="post"<?= $esRegistro ? '' : ' hidden' ?>>
        <h1>Crea tu cuenta</h1>
        <?php if ($error && $esRegistro): ?><p class="aviso-form" role="alert"><i class="ti ti-alert-circle" aria-hidden="true"></i><?= $e($error) ?></p><?php endif; ?>
        <?php if ($volver): ?><input type="hidden" name="volver" value="<?= $e($volver) ?>"><?php endif; ?>
        <div class="dos">
          <div class="campo"><label for="r-nombre">Nombre</label><input id="r-nombre" name="nombre" autocomplete="given-name" maxlength="100" required></div>
          <div class="campo"><label for="r-apellido">Apellido</label><input id="r-apellido" name="apellido" autocomplete="family-name" maxlength="50" required></div>
        </div>
        <div class="campo"><label for="r-tel">Teléfono con WhatsApp</label><input id="r-tel" name="telefono" type="tel" autocomplete="tel" inputmode="tel" placeholder="0412 555 0193" maxlength="20" pattern="[0-9+ ()-]{10,20}" title="Escribe tu número con el código, por ejemplo 0412 555 0193" required><span class="ayuda">Por aquí te confirmamos el pedido.</span></div>
        <div class="campo"><label for="r-correo">Correo</label><input id="r-correo" name="correo" type="email" autocomplete="email" placeholder="nombre@correo.com" maxlength="100" required></div>
        <div class="campo"><label for="r-clave">Contraseña</label><div class="con-ojo"><input id="r-clave" name="clave" type="password" autocomplete="new-password" minlength="8" required><button type="button" data-ver-clave="r-clave" aria-label="Mostrar contraseña"><i class="ti ti-eye"></i></button></div><span class="ayuda">Mínimo 8 caracteres.</span></div>
        <button class="btn" type="submit"><?= $volver === 'carrito' ? 'Crear cuenta y seguir' : 'Crear cuenta' ?></button>
        <p class="acceso-cambio">¿Ya tienes cuenta? <button type="button" class="enlace" data-ir-pestana="tab-entrar">Entra aquí</button></p>
      </form>
    </div>
  </section>
<?php $seccionPie = 'cuenta'; include __DIR__ . '/_pie.php'; ?>
