// ==========================================================================
// 1. CLASE MATEMÁTICA PURA PARA DATOS NO AGRUPADOS (TODO EL INVENTARIO)
// ==========================================================================
class Estadistica {
    constructor(datosNumericos) {
        // Filtramos para asegurar que trabajamos solo con números válidos
        this.datos = datosNumericos.map(val => parseInt(val || 0));
    }

    // Media Aritmética (Suma de todos los stocks dividida entre el número total de productos)
    getMedia() {
        if (this.datos.length === 0) return 0;
        const suma = this.datos.reduce((acumulado, valor) => acumulado + valor, 0);
        return (suma / this.datos.length).toFixed(2);
    }

    // Mediana (El valor central de los datos ordenados de menor a mayor)
    getMediana() {
        if (this.datos.length === 0) return 0;
        const ordenados = [...this.datos].sort((a, b) => a - b);
        const mitad = Math.floor(ordenados.length / 2);
        
        // Si es impar, es el del medio. Si es par, el promedio de los dos centrales.
        return ordenados.length % 2 !== 0 
            ? ordenados[mitad] 
            : (ordenados[mitad - 1] + ordenados[mitad]) / 2;
    }

    // Moda (El valor o cantidad de stock que más se repite en la tabla)
    getModa() {
        if (this.datos.length === 0) return 0;
        const frecuencias = {};
        let maxRepetidos = 0;
        let moda = this.datos[0];

        this.datos.forEach(val => {
            frecuencias[val] = (frecuencias[val] || 0) + 1;
            if (frecuencias[val] > maxRepetidos) {
                maxRepetidos = frecuencias[val];
                moda = val;
            }
        });
        return moda;
    }
}

// ==========================================================================
// 2. CONTROLADOR CENTRAL DE LA VISTA
// ==========================================================================
window.addEventListener('load', function () {
    const inputOculto = document.getElementById('datosBackendOcultos');
    let miGraficoInstancia = null;

    if (!inputOculto || inputOculto.value === "null" || inputOculto.value === "") {
        console.error("No se detectaron datos provenientes de la base de datos.");
        return;
    }

    const datosBackend = JSON.parse(inputOculto.value);
    
    // CAPTURAMOS EL INVENTARIO COMPLETO (Base de datos real)
    const inventarioCompleto = datosBackend.inventario || [];

    if (inventarioCompleto.length === 0) {
        console.warn("El inventario está vacío.");
        return;
    }

    // Extraemos todos los stocks individuales de la tabla de la empresa
    const todosLosStocks = inventarioCompleto.map(p => parseInt(p.stock || 0));

    // ==========================================================================
    // CÁLCULO ESTADÍSTICO DE DATOS NO AGRUPADOS
    // ==========================================================================
    // Pasamos el arreglo completo a la clase para que calcule sobre todo el universo de productos
    const calculadoraCompleta = new Estadistica(todosLosStocks);

    // Inyectamos de inmediato los valores reales y definitivos en las tarjetas del Dashboard
    if (document.getElementById("txtMedia")) {
        document.getElementById("txtMedia").innerHTML = `${calculadoraCompleta.getMedia()} <span>uds</span>`;
    }
    if (document.getElementById("txtMediana")) {
        document.getElementById("txtMediana").innerHTML = `${calculadoraCompleta.getMediana()} <span>uds</span>`;
    }
    if (document.getElementById("txtModa")) {
        document.getElementById("txtModa").innerHTML = `${calculadoraCompleta.getModa()} <span>uds</span>`;
    }

    // Determinar el pico más alto del inventario general para la escala del eje Y
    const stockMaximoInventario = Math.max(...todosLosStocks);

    // ==========================================================================
    // FILTRADO DE LOS 5 CRÍTICOS PARA EL GRÁFICO DE BARRAS
    // ==========================================================================
    const topMenosStock = inventarioCompleto.slice(0, 5);

    // Formateamos las etiquetas en dos líneas para mantenerlas rectas y legibles
    const labelsDobleLinea = topMenosStock.map(p => [
        `${p.nombre_producto}`, 
        `(${p.stock} uds)`
    ]);

    const datosGrafico = topMenosStock.map(p => parseInt(p.stock || 0));

    // ==========================================================================
    // RENDERIZADO DEL GRÁFICO
    // ==========================================================================
    const canvas = document.getElementById('canvasBarras');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        
        if (miGraficoInstancia) {
            miGraficoInstancia.destroy();
        }

        miGraficoInstancia = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labelsDobleLinea,
                datasets: [{
                    label: 'Nivel de Existencias Críticas (Unidades Disponibles)',
                    data: datosGrafico,
                    backgroundColor: 'rgba(0, 82, 212, 0.12)',
                    borderColor: '#0052d4',
                    borderWidth: 2,
                    borderRadius: 5,
                    hoverBackgroundColor: '#0052d4',
                    barPercentage: 0.55
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1200,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: { 
                        display: true, 
                        position: 'top',
                        labels: { 
                            font: { family: 'Poppins', size: 12, weight: '500' },
                            color: '#1e293b'
                        } 
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { family: 'Poppins', size: 13, weight: '600' },
                        bodyFont: { family: 'Poppins', size: 12 },
                        padding: 12,
                        callbacks: {
                            label: function (context) {
                                return ` Cantidad en Inventario: ${context.parsed.y} uds`;
                            }
                        }
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        max: stockMaximoInventario, 
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            font: { family: 'Poppins', size: 11, color: '#64748b' },
                            stepSize: Math.ceil(stockMaximoInventario / 5)
                        },
                        title: {
                            display: true,
                            text: 'Escala Comparativa del Inventario',
                            font: { family: 'Poppins', size: 12, weight: '500' }
                        }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { 
                            font: { family: 'Poppins', size: 10, weight: '500' },
                            color: '#334155',
                            padding: 8,
                            minRotation: 0,
                            maxRotation: 0,
                            autoSkip: false 
                        }
                    }
                }
            }
        });
    }
});