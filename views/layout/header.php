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
          
            <?php if ($action == 'inicio' || $action == ''): ?>
                <section class="nav-brand">
                    <a href="index.php?action=inicio">
                        <img src="public/img/logo-removebg.png" alt="La Providencia" class="brand-logo">
                    </a>
                </section>
            <?php endif; ?>
            
            <?php if ($action == 'inicio' || $action == ''): ?>
                <ul class="nav-menu">
                    <li><a href="index.php?action=inicio" class="nav-link active">Inicio</a></li>
                    <li><a href="#products" class="nav-link">Categorías</a></li>
                    <li><a href="#nosotros" class="nav-link">Nosotros</a></li>
                    <li><a href="#contacto" class="nav-link">Contacto</a></li>
                </ul>
            <?php endif; ?>

            <section class="nav-actions">
                <?php if ($action == 'inicio' || $action == ''): ?>
                   <a href="index.php?action=login" style="color: #0052d4; font-weight: bold; text-decoration: none;">Iniciar Sesión</a>
                        <i class="fas fa-user-circle"></i>
                        <span>Iniciar Sesión</span>
                    </a>

                    <a href="index.php?action=registro" class="action-link register-btn" aria-label="Registrarse">
                        <i class="fas fa-user-plus"></i>
                        <span>Registrarse</span>
                    </a>

                    <a href="index.php?action=ver_catalogo" class="cart-btn" aria-label="Carrito">
                        <i class="fas fa-shopping-basket cart-icon"></i>
                        <span class="cart-badge">0</span>
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

                <?php if ($action !== 'inicio' && $action !== ''): ?>
                <aside class="nav-menu-responsive">
                    
                    <a href="index.php?action=ver_catalogo" class="cart-btn-responsive">
                        <i class="fas fa-shopping-basket"></i>
                        <span>Ver Carrito (0)</span>
                    </a>

                    <nav class="responsive-nav">
                        <ul class="responsive-links-list">
                            <li><a href="index.php?action=inicio" class="nav-link-res active-res">Inicio</a></li>
                            <li><a href="index.php?action=categorias" class="nav-link-res">Categorías</a></li>
                            <li><a href="index.php?action=nosotros" class="nav-link-res">Nosotros</a></li>
                            <li><a href="index.php?action=contacto" class="nav-link-res">Contacto</a></li>
                        </ul>
                    </nav>

                </aside>
            <?php endif; ?>

        </nav>
    </header>

   <script src="js/navbar.js?v=<?php echo time(); ?>" defer></script>
    <main>