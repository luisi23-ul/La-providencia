<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Providencia</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="public/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="public/css/header.css?v=<?php echo time(); ?>">

    <?php if ($action == 'inicio' || $action == ''): ?>
        <link rel="stylesheet" href="public/css/home-style.css?v=<?php echo time(); ?>">
    <?php endif; ?>
</head>
<body class="<?php echo ($action == 'nosotros') ? 'page-about' : ''; ?>">

<header class="main-header">
    <nav class="nav-container">
        <section class="nav-brand">
            <a href="index.php?action=inicio">
                <img src="public/img/logo-removebg.png" alt="La Providencia" class="brand-logo">
            </a>
        </section>
        
        <?php if ($action == 'inicio' || $action == ''): ?>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link">Inicio</a></li>
                <li><a href="index.php?action=ver_catalogo" class="nav-link active-catalog"><i class="fas fa-store icon-small"></i> Catálogo</a></li>
                <li><a href="#products" class="nav-link">Categorías</a></li>
                <li><a href="#nosotros" class="nav-link">Nosotros</a></li>
                <li><a href="#contacto" class="nav-link">Contacto</a></li>
            </ul>
        <?php endif; ?>

        <section class="nav-actions">
            <?php if ($action == 'inicio' || $action == ''): ?>
                <a href="index.php?action=login_usuario" class="action-link login-btn"><i class="fas fa-user"></i> <span>Iniciar Sesión</span></a>
                <a href="index.php?action=registro" class="register-cta-btn"><i class="fas fa-user-plus"></i> <span>Registrarse</span></a>
                <a href="index.php?action=carrito" class="cart-custom-btn"><span>0</span></a>
            <?php endif; ?>

            <?php 
            $acciones_admin = ['dashboard', 'listado_productos', 'formulario_producto', 'editar', 'metodos_pago', 'retiro_pedidos', 'detalle_venta', 'admin', 'seccion_graficos'];
            if ($action !== 'inicio' && $action !== '' && !in_array($action, $acciones_admin)): ?>
                <button class="menu-toggle" aria-label="Abrir menú">
                    <span></span><span></span><span></span>
                </button>
            <?php endif; ?>
        </section>
    </nav>

    <?php if ($action !== 'inicio' && $action !== '' && !in_array($action, $acciones_admin)): ?>
        <aside class="nav-menu-responsive">
            </aside>
    <?php endif; ?>
</header>

<script src="public/js/navbar.js?v=<?php echo time(); ?>" defer></script>