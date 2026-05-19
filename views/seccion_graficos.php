<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">

<header class="dashboard-header">
    <h2>Módulo de Estadística</h2>
    <p class="bienvenida-sub">Control de Sistema: <span>Análisis de Inventario</span></p>
</header>

<nav class="row-opciones">
    
    <article class="bloque-opcion">
        <span class="icon-admin"><i class="fas fa-chart-bar"></i></span>
        <h3>Gráficos de Barras</h3>
        <p>Visualiza el stock actual agrupado por categorías comerciales de forma apilada.</p>
        <a href="index.php?action=grafico_barras" class="btn-admin-azul">
            Ver Barras <i class="fas fa-arrow-right"></i>
        </a>
    </article>

    <article class="bloque-opcion">
        <span class="icon-admin"><i class="fas fa-chart-pie"></i></span>
        <h3>Gráficos de Torta</h3>
        <p>Analiza la proporción y distribución porcentual del catálogo general de productos.</p>
        <a href="index.php?action=grafico_tortas" class="btn-admin-azul">
            Ver Torta <i class="fas fa-arrow-right"></i>
        </a>
    </article>

</nav>