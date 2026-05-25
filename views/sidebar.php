<aside class="sidebar-admin">
    <header class="sidebar-brand">
        <h3>La Providencia</h3>
        <span>Admin Panel</span>
    </header>
    
    <?php 
    // Aseguramos tener los permisos y el rol para la lógica
    $permisos = $_SESSION['permisos'] ?? []; 
    $rol = $_SESSION['rol'] ?? 0;
    ?>
    
    <nav class="sidebar-menu">
        <a href="index.php?action=dashboard" class="menu-item <?php echo (in_array($_GET['action'] ?? '', ['dashboard'])) ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i> Inicio
        </a>
        
        <?php if (in_array('cargar_producto', $permisos) || $rol == 1): ?>
        <a href="index.php?action=formulario_producto" class="menu-item <?php echo (($_GET['action'] ?? '') == 'formulario_producto') ? 'active' : ''; ?>">
            <i class="fas fa-plus-circle"></i> Nuevo Producto
        </a>
        <?php endif; ?>
        
        <?php if (in_array('gestionar_productos', $permisos) || $rol == 1): ?>
        <a href="index.php?action=listado_productos" class="menu-item <?php echo (($_GET['action'] ?? '') == 'listado_productos') ? 'active' : ''; ?>">
            <i class="fas fa-boxes"></i> Inventario
        </a>
        <?php endif; ?>

        <?php if (in_array('graficos', $permisos) || $rol == 1): ?>
        <a href="index.php?action=seccion_graficos" class="menu-item <?php echo (in_array($_GET['action'] ?? '', ['seccion_graficos', 'grafico_barras', 'grafico_tortas'])) ? 'active' : ''; ?>">
            <i class="fas fa-chart-pie"></i> Estadísticas
        </a>
        <?php endif; ?>

        <?php if (in_array('pagos', $permisos) || $rol == 1): ?>
        <a href="index.php?action=ver_pagos_pendientes" class="menu-item">
            <i class="fas fa-receipt"></i> Pagos Pendientes
        </a>
        <?php endif; ?>
        
        <?php if (in_array('retiros', $permisos) || $rol == 1): ?>
        <a href="index.php?action=ver_retiros" class="menu-item">
            <i class="fas fa-box-open"></i> Retiro de Pedidos
        </a>
        <?php endif; ?>

        <a href="index.php?action=inicio" class="menu-item return-store">
            <i class="fas fa-store"></i> Tienda
        </a>
    </nav>
</aside>