
<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


<section class="panel-administracion">

<?php include 'sidebar-master.php'; ?>

<main class="master-dashboard-container">
    
    <header class="dashboard-header">
        <h1>Panel de Control Master</h1>
        <p>Gestión global de sistemas y configuraciones avanzadas.</p>
    </header>

    <nav class="master-grid" aria-label="Navegación del Panel Master">
        
        <article class="bloque-master">
            <header>
                <div class="icon-wrapper"><i class="fas fa-user-shield"></i></div>
                <h3>Gestionar Administradores</h3>
            </header>
            <p>Crea, edita o elimina cuentas de administradores del sistema.</p>
            <footer>
                <a href="index.php?action=gestionar_admins" class="btn-master">
                    Ver Personal <i class="fas fa-arrow-right"></i>
                </a>
            </footer>
        </article>

        <article class="bloque-master">
            <header>
                <div class="icon-wrapper"><i class="fas fa-sliders-h"></i></div>
                <h3>Configuración del Sistema</h3>
            </header>
            <p>Cambia colores, logo, títulos y datos de contacto.</p>
            <footer>
                <a href="index.php?action=configuracion_sistema" class="btn-master">
                    Editar Diseño <i class="fas fa-arrow-right"></i>
                </a>
            </footer>
        </article>
<article class="bloque-master">
            <header>
                <div class="icon-wrapper"><i class="fa-solid fa-square-check"></i></div>
                <h3>Listado Administradores</h3>
            </header>
            <p>Supervisa el inventario general del sistema.</p>
            <footer>
                <a href="index.php?action=listado_administradores" class="btn-master">
                    Ir al Listado <i class="fas fa-arrow-right"></i>
                </a>
            </footer>
        </article>

        <article class="bloque-master">
            <header>
                <div class="icon-wrapper"><i class="fas fa-box-open"></i></div>
                <h3>Gestión de Productos</h3>
            </header>
            <p>Supervisa el inventario general del sistema.</p>
            <footer>
                <a href="index.php?action=dashboard" class="btn-master">
                    Ir al Listado <i class="fas fa-arrow-right"></i>
                </a>
            </footer>
        </article>

        </nav>
</main>
</section>    