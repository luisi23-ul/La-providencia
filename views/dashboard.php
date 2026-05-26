<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">

<?php 
$permisos = $_SESSION['permisos'] ?? []; 
$rol = $_SESSION['rol'] ?? 0;
?>

<section class="panel-administracion">

    <?php include 'sidebar.php'; ?>

    <main class="main-dashboard-content">
        <header class="dashboard-header">
            <h2>Panel de Administración</h2>
            <p class="bienvenida-sub"><span>Bienvenido</span>. Selecciona una acción para continuar.</p>
        </header>

        <nav class="row-opciones">
            <?php if (in_array('cargar_producto', $permisos) || $rol == 1): ?>
            <article class="bloque-opcion">
                <span class="icon-admin"><i class="fas fa-plus-circle"></i></span>
                <h3>Cargar Producto</h3>
                <p>Añade nuevos componentes al inventario de La Providencia.</p>
                <a href="index.php?action=formulario_producto" class="btn-admin-azul">
                    Agregar Nuevo <i class="fas fa-arrow-right"></i>
                </a>
            </article>
            <?php endif; ?>

            <?php if (in_array('gestionar_productos', $permisos) || $rol == 1): ?>
            <article class="bloque-opcion">
                <span class="icon-admin"><i class="fas fa-boxes"></i></span>
                <h3>Gestionar Productos</h3>
                <p>Edita detalles o elimina registros del sistema.</p>
                <a href="index.php?action=listado_productos" class="btn-admin-azul">
                    Ir al Listado <i class="fas fa-arrow-right"></i>
                </a>
            </article>
            <?php endif; ?>

            <?php if (in_array('graficos', $permisos) || $rol == 1): ?>
            <article class="bloque-opcion">
                <span class="icon-admin"><i class="fas fa-chart-pie"></i></span>
                <h3>Gráficos Estadísticos</h3>
                <p>Visualiza análisis de stock.</p>
                <a href="index.php?action=seccion_graficos" class="btn-admin-azul">
                    Ver Gráficos <i class="fas fa-arrow-right"></i>
                </a>
            </article>
            <?php endif; ?>

            <?php if (in_array('pagos', $permisos) || $rol == 1): ?>
            <article class="bloque-opcion">
                <span class="icon-admin"><i class="fas fa-receipt"></i></span>
                <h3>Pagos Pendientes</h3>
                <p>Revisa las órdenes y confirma los pagos recibidos.</p>
                <a href="index.php?action=pagos_pendientes" class="btn-admin-azul">
                    Ver Pagos <i class="fas fa-arrow-right"></i>
                </a>
            </article>
            <?php endif; ?>

            <?php if (in_array('retiros', $permisos) || $rol == 1): ?>
            <article class="bloque-opcion">
                <span class="icon-admin"><i class="fas fa-box-open"></i></span>
                <h3>Retiro de Pedidos</h3>
                <p>Gestiona las entregas y pedidos listos para retirar.</p>
                <a href="index.php?action=retiro_pedidos" class="btn-admin-azul">
                    Ver Retiros <i class="fas fa-arrow-right"></i>
                </a>
            </article>
            <?php endif; ?>

            <?php if (in_array('metodos_pago', $permisos) || $rol == 1): ?>
            <article class="bloque-opcion">
                <span class="icon-admin"><i class="fas fa-credit-card"></i></span>
                <h3>Métodos de pago</h3>
                <p>Gestiona las cuentas bancarias, datos de pago móvil y divisas en efectivo del sistema.</p>
                <a href="index.php?action=metodos_pago" class="btn-admin-azul">
                    Ver Métodos <i class="fas fa-arrow-right"></i>
                </a>
            </article>
            <?php endif; ?>
        </nav>
    </main>

</section>