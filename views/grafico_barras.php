  <?php 
// Convertimos el arreglo PHP en una cadena de texto JSON limpia y segura para JavaScript
$jsonBackend = isset($datosEstadisticas) ? json_encode($datosEstadisticas) : "null"; 
?>
<input type="hidden" id="datosBackendOcultos" value="<?php echo htmlspecialchars($jsonBackend, ENT_QUOTES, 'UTF-8'); ?>">

<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="public/css/graficos.css?v=<?php echo time(); ?>">

<style>
@media print {
    /* Ocultamos el sidebar, navbar y la sección de botones para que el PDF salga limpio */
    .sidebar, .navbar, .grupo-botones-reporte, .btn-regresar-panel, header p span {
        display: none !important;
    }
    
    body, main, .main-content, .contenedor-grafico-canvas {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        box-shadow: none !important;
    }

    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }

    .row-opciones {
        display: flex !important;
        justify-content: space-between !important;
        gap: 10px !important;
        margin-bottom: 20px !important;
    }

    .bloque-opcion {
        flex: 1 !important;
        padding: 15px !important;
        border-radius: 6px !important;
        border: 1px solid #cbd5e1 !important;
    }

    .contenedor-grafico-canvas {
        margin-top: 30px !important;
        page-break-inside: avoid;
    }
}
</style>

<header class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <section>
        <h2>Análisis Estadístico: Gráfico de Barras</h2>
        <p class="bienvenida-sub">Métricas de control del stock general de <span>La Providencia</span></p>
    </section>
    
   <section class="grupo-botones-reporte" style="display: flex; gap: 10px; align-items: center;">
    <a href="index.php?action=exportar_excel&tipo=estadisticas" style="text-decoration: none;">
        <button type="button" style="background-color: #22c55e; color: white; border: none; cursor: pointer; padding: 10px 16px; border-radius: 6px; font-weight: bold; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            📊 Excel Estadísticas
        </button>
    </a>
    
    <a href="index.php?action=exportar_pdf&tipo=estadisticas" style="text-decoration: none;">
        <button type="button" style="background-color: #ef4444; color: white; border: none; cursor: pointer; padding: 10px 16px; border-radius: 6px; font-weight: bold; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            📄 PDF Estadísticas (FPDF)
        </button>
    </a>
    
    <a href="index.php?action=dashboard" class="btn-regresar-panel" style="text-decoration: none; padding: 10px 14px; font-size: 0.9rem; margin-left: 10px;">← Volver</a>
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

<section class="contenedor-grafico-canvas">
    <canvas id="canvasBarras"></canvas>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="public/js/grafico_barras.js?v=<?php echo time(); ?>"></script>