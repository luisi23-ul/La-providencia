<aside class="sidebar-admin">
    <header class="sidebar-brand">
        <h3>La Providencia</h3>
        <span>Admin Panel</span>
    </header>
    
    <nav class="sidebar-menu">
        <a href="index.php?action=dashboard" class="menu-item <?php echo (in_array($_GET['action'] ?? '', ['dashboard'])) ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i> Inicio
        </a>
        
        <a href="index.php?action=formulario_producto" class="menu-item <?php echo (($_GET['action'] ?? '') == 'formulario_producto') ? 'active' : ''; ?>">
            <i class="fas fa-plus-circle"></i> Nuevo Producto
        </a>
        
        <a href="index.php?action=listado_productos" class="menu-item <?php echo (($_GET['action'] ?? '') == 'listado_productos') ? 'active' : ''; ?>">
            <i class="fas fa-boxes"></i> Inventario
        </a>

        <a href="index.php?action=seccion_graficos" class="menu-item <?php echo (in_array($_GET['action'] ?? '', ['seccion_graficos', 'grafico_barras', 'grafico_tortas'])) ? 'active' : ''; ?>">
            <i class="fas fa-chart-pie"></i> Estadísticas
        </a>
        
        <a href="index.php?action=inicio" class="menu-item return-store">
            <i class="fas fa-store"></i> Tienda
        </a>
    </nav>
</aside>