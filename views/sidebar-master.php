
<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<aside class="sidebar-master">
    <header class="sidebar-brand-master">
        <h3>La Providencia</h3>
        <span>Master Panel</span>
    </header>
    
    <nav class="sidebar-menu-master">
        <a href="index.php?action=dashboard_master" class="menu-item-master <?php echo (($_GET['action'] ?? '') == 'dashboard_master') ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i> Inicio Master
        </a>
        
        <a href="index.php?action=gestionar_admins" class="menu-item-master <?php echo (($_GET['action'] ?? '') == 'gestionar_admins') ? 'active' : ''; ?>">
            <i class="fas fa-user-shield"></i> Administradores
        </a>
        
        <a href="index.php?action=configuracion_sistema" class="menu-item-master <?php echo (($_GET['action'] ?? '') == 'configuracion_sistema') ? 'active' : ''; ?>">
            <i class="fas fa-cogs"></i> Configuración
        </a>

        <a href="index.php?action=listado_administradores" class="menu-item-master <?php echo (($_GET['action'] ?? '') == 'listado_administradores') ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Listado Staff
        </a>

        <a href="index.php?action=dashboard" class="menu-item-master">
            <i class="fas fa-box-open"></i> Gestión Productos
        </a>

        <a href="index.php?action=inicio" class="menu-item-master return-store">
            <i class="fas fa-store"></i> Tienda
        </a>

        <a href="index.php?action=login" class="menu-item-master logout-item">
            <i class="fas fa-power-off"></i> Cerrar Sesión
        </a>
    </nav>
</aside>