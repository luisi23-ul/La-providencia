window.addEventListener('load', function () {
    
    // 1. LEER EL INPUT OCULTO DESDE EL HTML
    const inputOculto = document.getElementById('datosBackendOcultos');
    let datosCrudos = inputOculto ? inputOculto.value : "null";

    // 2. CONVERTIR TEXTO A DATOS REALES (O usar los de prueba si viene vacío)
    let productosDesdeBackend = null;

    if (datosCrudos !== "null") {
        // Si el backend mandó datos, los transformamos de texto a objetos JS
        productosDesdeBackend = JSON.parse(datosCrudos);
    }

    // 3. RESPALDO FRONTEND (Mock Data): Si no hay datos reales, usamos estos de una vez
    if (!productosDesdeBackend || productosDesdeBackend.length === 0) {
        productosDesdeBackend = [
            { nombre_producto: "Sillas Plásticas Confort", stock: "45" },
            { nombre_producto: "Mesas Organizadoras", stock: "15" },
            { nombre_producto: "Contenedor Industrial 20L", stock: "85" },
            { nombre_producto: "Cesta Multiuso Grande", stock: "120" },
            { nombre_producto: "Gavetero Modular Premium", stock: "15" }
        ];
    }

    // 4. EXTRAER LISTAS PARA EL GRÁFICO (Nombres y Cantidades)
    const labels = productosDesdeBackend.map(p => p.nombre_producto);
    const stocks = productosDesdeBackend.map(p => parseInt(p.stock || 0));
    const totalProductos = stocks.length;

    // ==========================================================================
    // CÁLCULOS ESTADÍSTICOS (MEDIA, MEDIANA Y MODA)
    // ==========================================================================

    // A. MEDIA
    const sumaStock = stocks.reduce((acumulador, valorActual) => acumulador + valorActual, 0);
    const media = (sumaStock / totalProductos).toFixed(2);

    // B. MEDIANA
    const stocksOrdenados = [...stocks].sort((a, b) => a - b);
    const mitad = Math.floor(totalProductos / 2);
    let mediana = 0;
    if (totalProductos % 2 !== 0) {
        mediana = stocksOrdenados[mitad];
    } else {
        mediana = (stocksOrdenados[mitad - 1] + stocksOrdenados[mitad]) / 2;
    }

    // C. MODA
    const frecuencias = {};
    let maxFrecuencia = 0;
    let moda = stocks[0];
    stocks.forEach(val => {
        frecuencias[val] = (frecuencias[val] || 0) + 1;
        if (frecuencias[val] > maxFrecuencia) {
            maxFrecuencia = frecuencias[val];
            moda = val;
        }
    });

    // ==========================================================================
    // INYECTAR RESULTADOS EN LAS TARJETAS DEL PANEL
    // ==========================================================================
    document.getElementById("txtMedia").innerHTML = `${media} <span>uds</span>`;
    document.getElementById("txtMediana").innerHTML = `${mediana} <span>uds</span>`;
    document.getElementById("txtModa").innerHTML = `${moda} <span>uds</span>`;

    // ==========================================================================
    // DIBUJAR EL GRÁFICO DE BARRAS CON CHART.JS
    // ==========================================================================
    const ctx = document.getElementById('canvasBarras').getContext('2d');
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Unidades en Stock',
                data: stocks,
                backgroundColor: 'rgba(0, 82, 212, 0.15)', 
                borderColor: '#0052d4',                     
                borderWidth: 2,
                borderRadius: 8,                            
                hoverBackgroundColor: '#0052d4'             
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });
});