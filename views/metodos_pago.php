<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">

<section class="panel-administracion">

    <?php include 'sidebar.php'; ?>

    <main class="main-dashboard-content">
        
        <header class="dashboard-header">
            <h2>⚙️ Gestión de Métodos de Pago</h2>
            <p class="bienvenida-sub">Configuración visual de canales de recepción de ingresos para <span>La Providencia</span></p>
        </header>

        <nav class="row-opciones">
            
            <article class="bloque-opcion">
                <span class="icon-admin"><i class="fas fa-dollar-sign"></i></span>
                <h3>Efectivo Dólares</h3>
                <p>Caja física para la recepción segura de divisas extranjeras directamente en la tienda.</p>
                <a href="index.php?action=ver_reporte&metodo=2" class="btn-admin-azul">
                    Consultar <i class="fas fa-file-invoice-dollar"></i>
                </a>
            </article>

            <article class="bloque-opcion">
                <span class="icon-admin"><i class="fas fa-money-bill-wave"></i></span>
                <h3>Efectivo Bolívares</h3>
                <p>Canal para el manejo de moneda local en efectivo, calculados a la tasa oficial del BCV.</p>
                <a href="index.php?action=ver_reporte&metodo=1" class="btn-admin-azul">
                    Consultar <i class="fas fa-file-invoice-dollar"></i>
                </a>
            </article>

            <article class="bloque-opcion">
                <span class="icon-admin"><i class="fas fa-university"></i></span>
                <h3>Transferencias</h3>
                <p>Cuentas corrientes nacionales habilitadas para la recepción y conciliación de fondos.</p>
                <a href="index.php?action=ver_reporte&metodo=3" class="btn-admin-azul">
                    Detalles <i class="fas fa-info-circle"></i>
                </a>
            </article>

            <article class="bloque-opcion">
                <span class="icon-admin"><i class="fas fa-mobile-alt"></i></span>
                <h3>Pago Móvil</h3>
                <p>Información de datos interbancarios, cédula de identidad y teléfono principal vinculados.</p>
                <a href="index.php?action=ver_reporte&metodo=4" class="btn-admin-azul">
                    Detalles <i class="fas fa-info-circle"></i>
                </a>
            </article>

        </nav>
    </main>

</section>


