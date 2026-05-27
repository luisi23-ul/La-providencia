


<header class="dashboard-header">
    <link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">
    <h2>Panel de Control Master</h2>
    <p class="bienvenida-sub"><span>Configuración Global</span>. Acceso exclusivo para gestión de sistemas y administradores.</p>
</header>

<nav class="row-opciones">

    <article class="bloque-opcion">
        <span class="icon-admin"><i class="fas fa-users-cog"></i></span>
        <h3>Gestionar Administradores</h3>
        <p>Crea, edita o elimina cuentas de administradores del sistema.</p>
        <a href="index.php?action=gestionar_admins" class="btn-admin-azul">
            Ver Personal <i class="fas fa-arrow-right"></i>
        </a>
    </article>

    <article class="bloque-opcion">
        <span class="icon-admin"><i class="fas fa-palette"></i></span>
        <h3>Configuración del Sistema</h3>
        <p>Cambia colores, logo, títulos y datos de contacto de La Providencia.</p>
        <a href="index.php?action=configuracion_sistema" class="btn-admin-azul">
            Editar Diseño <i class="fas fa-arrow-right"></i>
        </a>
    </article>

    <article class="bloque-opcion">
        <span class="icon-admin"><i class="fas fa-boxes"></i></span>
        <h3>listado administradores</h3>
        <p>Supervisa el inventario general del sistema.</p>
        <a href="index.php?action=listado_administradores" class="btn-admin-azul">
            Ir al Listado <i class="fas fa-arrow-right"></i>
        </a>
    </article>

    <article class="bloque-opcion">
        <span class="icon-admin"><i class="fas fa-boxes"></i></span>
        <h3>Gestión de administracion de productos</h3>
        <p>Supervisa el inventario general del sistema.</p>
        <a href="index.php?action=dashboard" class="btn-admin-azul">
            Ir al Listado <i class="fas fa-arrow-right"></i>
        </a>
    </article>

</nav>