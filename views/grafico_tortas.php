<?php 
// Convertimos el arreglo PHP en una cadena de texto JSON para JavaScript
$jsonBackend = isset($datosEstadisticas) ? json_encode($datosEstadisticas) : "null"; 
?>
<input type="hidden" id="datosBackendOcultos" value='<?php echo $jsonBackend; ?>'>

<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="public/css/graficos.css?v=<?php echo time(); ?>">

<section class="panel-administracion">

    <?php include 'sidebar.php'; ?>

    <main class="main-dashboard-content">

        <header class="pagos-header">
            <nav class="header-top">
                <a href="index.php?action=seccion_graficos" class="btn-regresar-panel">
                    <i class="fas fa-arrow-left"></i>Volver
                </a>
            </nav>

            <section class="header-bottom">
                <h2>Análisis Estadístico: Gráfico de Tortas</h2>
                
                <nav class="acciones-exportar">
                    <a href="index.php?action=exportar_excel&tipo=estadisticas&grafico=torta" class="btn-exportar-excel">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="#" onclick="generarPDFGrafico()" class="btn-exportar-pdf">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </nav>
            </section>
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

        <section class="panel-modulo-contenedor">
            
            <table class="tabla-modulo-admin">
                <thead>
                    
                </thead>
                <tbody>
                    <?php foreach ($listaProductos as $index => $producto): ?>
                  
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="contenedor-grafico-canvas" style="max-width: 600px; margin: 20px auto;">
            <canvas id="canvasTorta"></canvas>
        </section>

    </main>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<script src="public/js/grafico_tortas.js?v=<?php echo time(); ?>"></script>