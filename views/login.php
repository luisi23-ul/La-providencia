<?php
include __DIR__ . '/_sistema.php';
$errores = [
    'credenciales' => 'Correo o contraseña incorrectos. Revisa los datos e inténtalo otra vez.',
    'inactiva' => 'Tu cuenta está desactivada. Habla con el administrador para activarla.',
];
$error = $errores[$_GET['error'] ?? ''] ?? '';
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Iniciar sesión · <?= htmlspecialchars($nombreTienda, ENT_QUOTES, 'UTF-8') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Open+Sans:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="public/css/panel.css?v=<?= filemtime(__DIR__ . '/../public/css/panel.css') ?>">
<link rel="stylesheet" href="public/css/acceso.css?v=<?= filemtime(__DIR__ . '/../public/css/acceso.css') ?>">
</head>
<body>
<?php include __DIR__ . '/admin/_iconos.php'; ?>
<section class="acceso">
  <section class="acceso-marca">
    <div class="formas" aria-hidden="true"></div>
    <h2><?= $e($nombreTienda) ?></h2>
  </section>

  <div class="lado-form">
    <section class="acceso-form">
      <header>
        <h1>Iniciar sesión</h1>
        <p>Entra con el correo y la contraseña de tu cuenta del panel.</p>
      </header>

      <?php if ($error): ?>
      <p class="acceso-error" role="alert"><svg class="i"><use href="#i-error"/></svg><span><?= $e($error) ?></span></p>
      <?php endif; ?>

      <form action="index.php?action=validar_login" method="POST"<?= $error ? ' class="con-error"' : '' ?>>
        <label class="campo"><span>Correo electrónico</span>
          <span class="campo-caja">
            <input type="email" name="email" id="admin_email" autocomplete="username" required placeholder="nombre@correo.com">
            <svg class="i"><use href="#i-correo"/></svg>
          </span>
        </label>
        <label class="campo"><span>Contraseña</span>
          <span class="campo-caja">
            <input type="password" name="password" id="admin_password" autocomplete="current-password" required placeholder="Tu contraseña">
            <svg class="i"><use href="#i-candado"/></svg>
            <button type="button" class="ver-clave" aria-label="Mostrar contraseña" aria-pressed="false"><svg class="i"><use href="#i-ojo"/></svg></button>
          </span>
        </label>
        <button type="submit" class="btn-entrar"><span>Entrar al panel</span><svg class="i"><use href="#i-entrar"/></svg></button>
      </form>

      <footer class="acceso-pie">
        <a href="index.php?action=inicio" class="btn-tienda"><span>Volver a la tienda</span><svg class="i"><use href="#i-tienda"/></svg></a>
      </footer>
    </section>
  </div>
</section>
<script src="public/js/acceso.js?v=<?= filemtime(__DIR__ . '/../public/js/acceso.js') ?>"></script>
</body>
</html>
