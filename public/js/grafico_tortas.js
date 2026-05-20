// Registramos el plugin de forma nativa para los porcentajes internos
Chart.register(ChartDataLabels);

// ==========================================================================
// 1. CLASE MATEMÁTICA PURA PARA DATOS NO AGRUPADOS (MANTIENE LAS TARJETAS)
// ==========================================================================
class Estadistica {
    constructor(datosNumericos) {
        this.datos = datosNumericos.map(val => parseInt(val || 0));
    }

    getMedia() {
        if (this.datos.length === 0) return 0;
        const suma = this.datos.reduce((a, b) => a + b, 0);
        return (suma / this.datos.length).toFixed(2);
    }

    getMediana() {
        if (this.datos.length === 0) return 0;
        const ordenados = [...this.datos].sort((a, b) => a - b);
        const mitad = Math.floor(ordenados.length / 2);
        return ordenados.length % 2 !== 0 ? ordenados[mitad] : (ordenados[mitad - 1] + ordenados[mitad]) / 2;
    }

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
// 2. CONTROLADOR PARA LA TORTA DE PRODUCTOS CRÍTICOS
// ==========================================================================
window.addEventListener('load', function () {
    const inputOculto = document.getElementById('datosBackendOcultos');
    let miGraficoTortaInstancia = null;

    if (!inputOculto || inputOculto.value === "null" || inputOculto.value === "") return;

    const datosBackend = JSON.parse(inputOculto.value);
    const inventarioCompleto = datosBackend.inventario || [];

    if (inventarioCompleto.length === 0) return;

    // Sincronización de tarjetas (Media, Mediana, Moda del inventario general)
    const todosLosStocks = inventarioCompleto.map(p => parseInt(p.stock || 0));
    const calculadora = new Estadistica(todosLosStocks);

    if (document.getElementById("txtMedia")) document.getElementById("txtMedia").innerHTML = `${calculadora.getMedia()} <span>uds</span>`;
    if (document.getElementById("txtMediana")) document.getElementById("txtMediana").innerHTML = `${calculadora.getMediana()} <span>uds</span>`;
    if (document.getElementById("txtModa")) document.getElementById("txtModa").innerHTML = `${calculadora.getModa()} <span>uds</span>`;

    // ==========================================================================
    // ORDENAR Y CORTAR EXACTAMENTE EL TOP 5 DE CRÍTICOS PARA LA SIMETRÍA
    // ==========================================================================
    const inventarioOrdenado = [...inventarioCompleto].sort((a, b) => parseInt(a.stock || 0) - parseInt(b.stock || 0));
    const top5Criticos = inventarioOrdenado.slice(0, 5);

    const labelsNombres = top5Criticos.map(p => p.nombre_producto); 
    const cantidadesStocks = top5Criticos.map(p => parseInt(p.stock || 0));

    const canvas = document.getElementById('canvasTorta');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        
        // Si ya existía un gráfico lo destruimos para evitar duplicados en memoria
        if (window.miGraficoTortaInstancia) {
            window.miGraficoTortaInstancia.destroy();
        }

        window.miGraficoTortaInstancia = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labelsNombres,
                datasets: [{
                    data: cantidadesStocks, 
                    backgroundColor: ['#0052d4', '#10b981', '#f59e0b', '#6366f1', '#ec4899'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 15 
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: 25 },
                plugins: {
                    // Leyenda premium mostrando el stock crítico actual al lado
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#334155',
                            padding: 18,
                            usePointStyle: true, 
                            pointStyle: 'circle',
                            font: { family: 'Poppins', size: 12, weight: '500' },
                            generateLabels: function (chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    return data.labels.map((label, i) => {
                                        const valorActual = data.datasets[0].data[i];
                                        return {
                                            text: `${label} (${valorActual} uds)`,
                                            index: i,
                                            fillStyle: data.datasets[0].backgroundColor[i],
                                            strokeStyle: data.datasets[0].borderColor,
                                            lineWidth: data.datasets[0].borderWidth,
                                            hidden: false,
                                            pointStyle: 'circle'
                                        };
                                    });
                                }
                                return [];
                            }
                        }
                    },
                    // Porcentajes internos calculados sobre el total de este grupo crítico
                    datalabels: {
                        color: '#ffffff', 
                        font: { family: 'Poppins', weight: 'bold', size: 13 },
                        textShadowColor: 'rgba(0, 0, 0, 0.4)',
                        textShadowBlur: 4,
                        formatter: (value, context) => {
                            let totalGrupo = 0;
                            let datasets = context.chart.data.datasets[0].data;
                            datasets.forEach(num => { totalGrupo += num; });
                            
                            // Si el valor o el total es cero, no mostramos texto para evitar divisiones inválidas
                            if (value === 0 || totalGrupo === 0) {
                                return null; 
                            }
                            return ((value * 100) / totalGrupo).toFixed(0) + '%';
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 10,
                        callbacks: {
                            label: function (context) {
                                return ` Stock Disponible: ${context.parsed} uds`;
                            }
                        }
                    }
                }
            }

            
        });
    }
});




