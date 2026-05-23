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
                <a href="index.php?action=login_usuario" class="action-link login-btn" aria-label="Login Clientes">
                    <i class="fas fa-user"></i>
                    <span>Iniciar Sesión</span>
                </a>

                <a href="index.php?action=registro" class="register-cta-btn" aria-label="Registrarse">
                    <i class="fas fa-user-plus"></i>
                    <span>Registrarse</span>
                </a>

                <a href="index.php?action=carrito" class="cart-custom-btn" aria-label="Ver mi Carrito">
                    <i class="fas fa-shopping-basket"></i>
                    <span>0</span>
                </a>
            <?php endif; ?>

            <?php if ($action !== 'inicio' && $action !== ''): ?>
                <button class="menu-toggle" aria-label="Abrir menú">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            <?php endif; ?>
        </section>

    </nav>

    <?php if ($action !== 'inicio' && $action !== ''): ?>
        <aside class="nav-menu-responsive">
            <a href="index.php?action=carrito" class="cart-btn-responsive">
                <i class="fas fa-shopping-basket"></i>
                <span>Ver Carrito (0)</span>
            </a>
            
            <nav class="responsive-nav">
                <ul class="responsive-links-list">
                    <li><a href="index.php" class="nav-link-res">Inicio</a></li>
                    <li><a href="index.php?action=ver_catalogo" class="nav-link-res active-res">Catálogo</a></li>
                    <li><a href="index.php?action=login_usuario" class="nav-link-res">Iniciar Sesión</a></li>
                    <li><a href="index.php?action=registro" class="nav-link-res">Registrarse</a></li>
                </ul>
            </nav>
        </aside>
    <?php endif; ?>

</header>

    <script src="public/js/navbar.js?v=<?php echo time(); ?>" defer></script>
   
<?php
    // Evaluamos la bandera calculada desde el index
    if (isset($es_admin) && $es_admin === true): 
?>
        <section class="panel-administracion layout-dashboard">
            
            <?php include __DIR__ . '/../sidebar.php'; ?>
            
            <main class="main-dashboard-content">
<?php else: ?>
        <main>
<?php endif; ?>