<?php 
// Convertimos el arreglo PHP en una cadena de texto JSON para JavaScript
$jsonBackend = isset($datosEstadisticas) ? json_encode($datosEstadisticas) : "null"; 
?>
<input type="hidden" id="datosBackendOcultos" value='<?php echo $jsonBackend; ?>'>

<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="public/css/graficos.css?v=<?php echo time(); ?>">

<header class="dashboard-header">
    <h2>Análisis Estadístico: Gráfico de Barras</h2>
    <p class="bienvenida-sub">Métricas de control del stock general de <span>La Providencia</span></p>
</header>

<section class="row-opciones">
    
    <article class="bloque-opcion card-estadistica-media">
        <span class="etiqueta-formulario">Media</span>
        <span class="sub-etiqueta">Promedio</span>
        <h3 id="txtMedia">--</h3>
    </article>
    
    <article class="bloque-opcion card-estadistica-mediana">
        <span class="etiqueta-formulario">Mediana</span>
        <span class="sub-etiqueta">Centro</span>
        <h3 id="txtMediana">--</h3>
    </article>
    
    <article class="bloque-opcion card-estadistica-moda">
        <span class="etiqueta-formulario">Moda</span>
        <span class="sub-etiqueta">Más repetido</span>
        <h3 id="txtModa">--</h3>
    </article>

</section>

<section class="contenedor-grafico-canvas">
    <canvas id="canvasBarras"></canvas>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="public/js/grafico_barras.js?v=<?php echo time(); ?>"></script>

</main>