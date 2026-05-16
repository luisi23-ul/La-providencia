<section class="panel-administracion">
    <link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">
    
    <header>
        <h2>Panel de Administración</h2>
        <p class="bienvenida-sub">Bienvenida, Luisana. Selecciona una acción para continuar.</p>
    </header>

    <nav class="row-opciones">
        
        <article class="bloque-opcion">
            <span class="icon-admin">📥</span>
            <h3>Cargar Producto</h3>
            <p>Añade nuevos componentes al inventario de La Providencia.</p>
            <a href="index.php?action=formulario_producto" class="btn-admin-azul">Agregar Nuevo</a>
        </article>

        <article class="bloque-opcion">
            <span class="icon-admin">📋</span>
            <h3>Gestionar Productos</h3>
            <p>Edita detalles o elimina registros del sistema.</p>
            <a href="index.php?action=listado_productos" class="btn-admin-oscuro">Ir al Listado</a>
        </article>
        
    </nav>
</section>