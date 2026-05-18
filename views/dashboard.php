<section class="panel-administracion layout-dashboard">
    <link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">
    
    <aside class="sidebar-admin">
        <header class="sidebar-brand">
            <h3>La Providencia</h3>
            <span>Admin Panel</span>
        </header>
        <nav class="sidebar-menu">
            <a href="#" class="menu-item active"><i class="fas fa-chart-pie"></i> Inicio</a>
            <a href="index.php?action=formulario_producto" class="menu-item"><i class="fas fa-plus-circle"></i> Nuevo Producto</a>
            <a href="index.php?action=listado_productos" class="menu-item"><i class="fas fa-boxes"></i> Inventario</a>
            <a href="index.php?action=inicio" class="menu-item return-store"><i class="fas fa-store"></i> Tienda</a>
        </nav>
    </aside>

    <main class="main-dashboard-content">
        
        <header class="dashboard-header">
            <h2>Panel de Administración</h2>
            <p class="bienvenida-sub"><span>Bienvenido</span>. Selecciona una acción para continuar.</p>
        </header>

      <nav class="row-opciones">
    
    <article class="bloque-opcion">
        <span class="icon-admin"><i class="fas fa-plus-circle"></i></span>
        <h3>Cargar Producto</h3>
        <p>Añade nuevos componentes al inventario de La Providencia.</p>
        <a href="index.php?action=formulario_producto" class="btn-admin-azul">
            Agregar Nuevo <i class="fas fa-arrow-right"></i>
        </a>
    </article>

    <article class="bloque-opcion">
        <span class="icon-admin"><i class="fas fa-boxes"></i></span>
        <h3>Gestionar Productos</h3>
        <p>Edita detalles o elimina registros del sistema.</p>
        <a href="index.php?action=listado_productos" class="btn-admin-azul">
            Ir al Listado <i class="fas fa-arrow-right"></i>
        </a>
    </article>
    
</nav>
        
    </main>
</section>