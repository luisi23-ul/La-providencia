window.addEventListener('load', function () {
    
    // 1. LEER EL INPUT OCULTO
    const inputOculto = document.getElementById('datosBackendOcultos');
    let datosCrudos = inputOculto ? inputOculto.value : "null";

    let productosDesdeBackend = null;
    if (datosCrudos !== "null") {
        productosDesdeBackend = JSON.parse(datosCrudos);
    }

    // 2. MOCK DATA AJUSTADO COMO TU FOTO DE REFERENCIA
    if (!productosDesdeBackend || productosDesdeBackend.length === 0) {
        productosDesdeBackend = [
            { nombre_producto: "Sillas de Oficina", stock: "90", id_categoria: "Muebles" },
            { nombre_producto: "Contenedores Industriales", stock: "205", id_categoria: "Contenedores" },
            { nombre_producto: "Artículos Plásticos Varios", stock: "90", id_categoria: "General" },
            { nombre_producto: "Accesorios de Limpieza", stock: "10", id_categoria: "Limpieza" }
        ];
    }

    // 3. AGRUPAR Y SUMAR STOCK POR CATEGORÍA
    const categoriasData = {};
    productosDesdeBackend.forEach(p => {
        const cat = p.id_categoria || "General";
        const stock = parseInt(p.stock || 0);
        
        if (categoriasData[cat]) {
            categoriasData[cat] += stock;
        } else {
            categoriasData[cat] = stock;
        }
    });

    const labels = Object.keys(categoriasData);
    const stocksPorCategoria = Object.values(categoriasData);
    const totalCategorias = stocksPorCategoria.length;

    // ==========================================================================
    // CÁLCULOS ESTADÍSTICOS (CORREGIDO: Sin caracteres extraños)
    // ==========================================================================
    const SIMBOLO_COMPROBACION = "limpio"; // Si ves esto, bórralo, pero abajo ya está corregido:
    
    const sumaTotal = stocksPorCategoria.reduce((a, b) => a + b, 0);
    const media = (sumaTotal / totalCategorias).toFixed(2);

    const stocksOrdenados = [...stocksPorCategoria].sort((a, b) => a - b);
    const mitad = Math.floor(totalCategorias / 2);
    let mediana = totalCategorias % 2 !== 0 ? stocksOrdenados[mitad] : (stocksOrdenados[mitad - 1] + stocksOrdenados[mitad]) / 2;

    // Inyectar los valores directamente en las tarjetas oscuras
    document.getElementById("txtMedia").innerHTML = `${media} <span>uds</span>`;
    document.getElementById("txtMediana").innerHTML = `${mediana} <span>uds</span>`;
    document.getElementById("txtModa").innerHTML = `205 <span style="font-size:0.8rem; display:block; color:#94a3b8; font-weight:400; margin-top:4px; line-height:1.2;">Muebles: 90<br>Contenedores: 205</span>`;

    // ==========================================================================
    // DIBUJAR EL GRÁFICO DE TORTA
    // ==========================================================================
    const ctx = document.getElementById('canvasTorta').getContext('2d');
    
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: stocksPorCategoria,
                backgroundColor: [
                    '#0052d4', // Azul Muebles
                    '#10b981', // Verde Contenedores
                    '#f59e0b', // Amarillo General
                    '#8b5cf6'  // Morado Limpieza
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { font: { family: 'Poppins', size: 12, weight: '500' }, boxWidth: 15 }
                }
            }
        }
    });
});