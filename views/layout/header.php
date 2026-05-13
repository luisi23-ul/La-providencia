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
            <h1 class="brand-logo">
                <a href="index.php?action=inicio">La<span>Providencia</span></a>
            </h1>
            
            <ul class="nav-menu">
               <li><a href="#inicio" class="<?php echo ($action == 'inicio' || $action == '') ? 'active' : ''; ?>">Inicio</a></li>
    
    <li><a href="#products">Categorías</a></li>
    
    <li><a href="#nosotros">Nosotros</a></li>
    
    <li><a href="#contacto">Contacto</a></li>
            </ul>

           <aside class="header-actions">
    <a href="index.php?action=login" class="action-item" aria-label="Iniciar Sesión">
        <i class="fas fa-user-circle"></i>
        <span>Iniciar Sesión</span>
    </a>

    <a href="index.php?action=registro" class="action-item" aria-label="Registrarse">
        <i class="fas fa-user-plus"></i>
        <span>Registrarse</span>
    </a>

    <a href="index.php?action=ver_catalogo" class="action-item" aria-label="Carrito">
        <i class="fas fa-shopping-basket"></i>
        <span class="cart-count">0</span>
    </a>
</aside>
        </nav>
    </header>

    <main>