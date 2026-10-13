<?php
//edgar ni se te ocurra tocar algo de aqui
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Importaciones necesarias de PhpSpreadsheet para datos y archivos
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// importaciones para los graficos
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;

class ReporteController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Función interna para centralizar la búsqueda de datos según el tipo
    private function obtenerDatosReporte($tipo) {
        if ($tipo === 'inventario') {
            require_once "models/ProductoModel.php"; 
            $modelo = new ProductoModel($this->db); 
            return [
                'titulo' => 'REPORTE GENERAL DE INVENTARIO - LA PROVIDENCIA',
                'datos'  => $modelo->listarProductos()
            ];
        } elseif ($tipo === 'pendientes') {
            require_once "models/VentaModel.php";
            $modelo = new VentaModel($this->db);
            return [
                'titulo' => 'REPORTE DE VENTAS: PAGOS PENDIENTES',
                'datos'  => $modelo->obtenerPorEstado('pendiente') 
            ];
        } elseif ($tipo === 'retiros') {
            require_once "models/VentaModel.php";
            $modelo = new VentaModel($this->db); 
            return [
                'titulo' => 'REPORTE DE LOGÍSTICA: PEDIDOS LISTOS PARA RETIRO',
                'datos'  => $modelo->obtenerPorEstado('pagado')
            ];
        } elseif ($tipo === 'estadisticas') { 
            require_once "models/ProductoModel.php";
            $modelo = new ProductoModel($this->db);
            return [
                'titulo' => 'ANÁLISIS ESTADÍSTICO DE INVENTARIO - LA PROVIDENCIA',
                'datos'  => $modelo->listarProductos()
            ];
        } elseif ($tipo === 'metodo_pago') {
            $idMetodo = (int)($_GET['metodo'] ?? 0);
            $desde = trim($_GET['desde'] ?? '');
            $hasta = trim($_GET['hasta'] ?? '');

            // Obtener el nombre del método de pago
            $nombreMetodo = 'Método de Pago';
            if ($idMetodo > 0) {
                $stmtM = $this->db->prepare("SELECT nombre FROM metodos_pago WHERE id = ?");
                $stmtM->execute([$idMetodo]);
                $nombreMetodo = $stmtM->fetchColumn() ?: 'Método de Pago';
            }

            // Construir filtro
            $where = ["pr.estado_verificacion = 'aprobado'"];
            $params = [];

            if ($idMetodo > 0) {
                $where[] = "pr.id_metodo_pago = ?";
                $params[] = $idMetodo;
            }
            if ($desde !== '') {
                $where[] = "DATE(v.fecha) >= ?";
                $params[] = $desde;
            }
            if ($hasta !== '') {
                $where[] = "DATE(v.fecha) <= ?";
                $params[] = $hasta;
            }

            $sql = "SELECT v.id, 
                           COALESCE(u.nombre, 'Cliente') AS nombre, 
                           v.fecha, 
                           v.total, 
                           v.total_bs, 
                           v.estado
                    FROM ventas v
                    INNER JOIN pagos_registro pr ON pr.id_venta = v.id
                    LEFT JOIN usuarios u ON v.id_usuario = u.id
                    WHERE " . implode(' AND ', $where) . "
                    GROUP BY v.id, u.nombre, v.fecha, v.total, v.total_bs, v.estado
                    ORDER BY v.fecha DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $datos = $stmt->fetchAll(PDO::FETCH_OBJ);

            $subtitulo = "REPORTE DE VENTAS - COBRADO CON " . strtoupper($nombreMetodo);
            if ($desde && $hasta) {
                $subtitulo .= " (" . date('d/m/Y', strtotime($desde)) . " AL " . date('d/m/Y', strtotime($hasta)) . ")";
            }

            return [
                'titulo' => $subtitulo,
                'datos'  => $datos
            ];
        }

        exit("Tipo de reporte no válido.");
    }

    // Excel
    public function generarExcel() {
        $tipo = $_GET['tipo'] ?? '';
        $reporte = $this->obtenerDatosReporte($tipo);

        if ($tipo === 'estadisticas') {
            if (ob_get_length()) ob_end_clean();

            $datos = $reporte['datos'];
            // Ordenamos de menor a mayor stock
            usort($datos, function($a, $b) {
                return (int)$a->stock - (int)$b->stock;
            });
            // para obtener solo los primeros 5
            $datos = array_slice($datos, 0, 5);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Análisis Estadístico');

            $valoresStock = [];
            foreach ($datos as $p) {
                $valoresStock[] = (int)$p->stock;
            }

            // Tabla de Datos para la Gráfica 5 menos
            $sheet->setCellValue('A9', 'Producto Analizado');
            $sheet->setCellValue('B9', 'Stock Disponible');
            $sheet->getStyle('A9:B9')->getFont()->setBold(true);
            $sheet->getStyle('A9:B9')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('CBD5E1');

            $filaInicio = 10;
            foreach ($datos as $p) {
                $sheet->setCellValue('A' . $filaInicio, htmlspecialchars($p->nombre_producto));
                $sheet->setCellValue('B' . $filaInicio, (int)$p->stock);
                $filaInicio++;
            }
            $filaFin = $filaInicio - 1;

            $nombreHoja = 'Análisis Estadístico';
            $rangoCategorias = "'" . $nombreHoja . "'!\$A$10:\$A$" . $filaFin;
            $rangoValores = "'" . $nombreHoja . "'!\$B$10:\$B$" . $filaFin;

            $categories = [new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('String', $rangoCategorias, null, 5)];
            $values = [new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('Number', $rangoValores, null, 5)];

            // Tipo de gráfico dinámico
            $graficoTipo = ($_GET['grafico'] ?? '') === 'torta' 
                ? \PhpOffice\PhpSpreadsheet\Chart\DataSeries::TYPE_PIECHART 
                : \PhpOffice\PhpSpreadsheet\Chart\DataSeries::TYPE_BARCHART;

            $grouping = ($_GET['grafico'] ?? '') === 'torta' 
                ? null 
                : \PhpOffice\PhpSpreadsheet\Chart\DataSeries::GROUPING_CLUSTERED;

            $series = new \PhpOffice\PhpSpreadsheet\Chart\DataSeries(
                $graficoTipo, 
                $grouping, 
                range(0, count($values) - 1), 
                [], 
                $categories, 
                $values
            );

            // Dirección de columna solo si es gráfico de barras
            if ($graficoTipo === \PhpOffice\PhpSpreadsheet\Chart\DataSeries::TYPE_BARCHART) {
                $series->setPlotDirection(\PhpOffice\PhpSpreadsheet\Chart\DataSeries::DIRECTION_COL);
            }

            $plotArea = new \PhpOffice\PhpSpreadsheet\Chart\PlotArea(null, [$series]);
            $title = new \PhpOffice\PhpSpreadsheet\Chart\Title('Nivel de Existencias');
            $chart = new \PhpOffice\PhpSpreadsheet\Chart\Chart('grafico_stock', $title, null, $plotArea);

            $chart->setTopLeftPosition('D4');
            $chart->setBottomRightPosition('L15');
            $sheet->addChart($chart);

            $nombreArchivo = "Top5_Stock_Bajo_" . date('d_m_Y') . ".xlsx";
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $nombreArchivo . '"');
            header('Cache-Control: max-age=0');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->setIncludeCharts(true); 
            $writer->save('php://output');
            exit();
        }

        if (ob_get_length()) ob_end_clean();
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $tasa = 36.50; // Tasa de respaldo

        // 1. Título del Reporte
        $sheet->setCellValue('A1', $reporte['titulo']);
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // 2. Encabezados según el tipo
        if ($tipo === 'inventario') {
            $headers = ['Producto', 'Precio', 'Stock', 'Descripción', 'Categoría'];
        } else {
            $headers = ['ID Orden', 'Cliente', 'Fecha', 'Total (USD)', 'Total (Bs)', 'Estado'];
        }
        
        $sheet->fromArray($headers, NULL, 'A3');
        $sheet->getStyle('A3:F3')->getFont()->setBold(true);

        // 3. Llenar Datos
        $fila = 4;
        foreach ($reporte['datos'] as $d) {
            if ($tipo === 'inventario') {
                $sheet->fromArray([$d->nombre_producto, $d->precio, $d->stock, $d->descripcion, $d->id_categoria], NULL, 'A' . $fila);
            } else {
                $montoBs = !empty($d->total_bs) ? $d->total_bs : ($d->total * $tasa);
                $sheet->fromArray([
                    $d->id, 
                    $d->nombre, 
                    $d->fecha, 
                    $d->total, 
                    $montoBs, 
                    strtoupper($d->estado)
                ], NULL, 'A' . $fila);
            }
            $fila++;
        }

        $nombreArchivo = "Reporte_" . $tipo . "_" . date('d_m_Y') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $nombreArchivo . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
    }

    // PDF / Impresión nativa
    public function generarPDF() {
        $tipo = $_GET['tipo'] ?? '';
        
        if ($tipo === 'estadisticas') {
            $reporte = $this->obtenerDatosReporte($tipo);
            if (ob_get_length()) ob_end_clean();
            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title><?php echo $reporte['titulo']; ?></title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 30px; color: #333; }
                    .print-header { text-align: center; margin-bottom: 25px; border-bottom: 3px solid #4b5563; padding-bottom: 10px; }
                    .print-header h1 { margin: 0; font-size: 22pt; color: #1e293b; }
                    .print-header h3 { margin: 5px 0; font-size: 12pt; color: #6b7280; font-weight: normal; }
                    .alerta-pdf { background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; text-align: center; margin-top: 50px; }
                </style>
            </head>
            <body>
                <header class="print-header">
                    <h1>LA PROVIDENCIA</h1>
                    <h3><?php echo $reporte['titulo']; ?></h3>
                    <p style="font-size: 9pt; color: #64748b;">Generado el: <?php echo date('d/m/Y h:i A'); ?></p>
                </header>

                <main class="alerta-pdf">
                    <h2>Procesando Vista de Impresión...</h2>
                    <p>Por motivos de compatibilidad con gráficos dinámicos interactivos, el sistema abrirá la ventana de impresión nativa de su sistema operativo automáticamente.</p>
                    <p><strong>Sugerencia:</strong> Seleccione la opción "Guardar como PDF" en el destino de su impresora.</p>
                </main>

                <script>
                    window.onload = function() {
                        window.print();
                        setTimeout(function() { window.close(); }, 500);
                    }
                </script>
            </body>
            </html>
            <?php
            exit();
        }

        $reporte = $this->obtenerDatosReporte($tipo);
        if (ob_get_length()) ob_end_clean();
        $tasa = 36.50;
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title><?php echo $reporte['titulo']; ?></title>
            <style>
                body { font-family: sans-serif; margin: 20px; color: #1e293b; }
                .print-header { text-align: center; margin-bottom: 20px; }
                .print-header h1 { margin: 0; font-size: 20pt; font-weight: 800; }
                .print-header h3 { margin: 6px 0; font-size: 11pt; font-weight: 700; color: #334155; }
                table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 10pt; }
                th { background-color: #334155; color: white; padding: 10px 8px; text-align: left; font-size: 9pt; text-transform: uppercase; }
                td { padding: 9px 8px; border-bottom: 1px solid #e2e8f0; }
                tr:nth-child(even) { background-color: #f8fafc; }
                .text-bold { font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h1>LA PROVIDENCIA</h1>
                <h3><?php echo $reporte['titulo']; ?></h3>
            </div>
            <table class="print-table">
                <thead>
                    <tr>
                        <?php if ($tipo === 'inventario'): ?>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Categoría</th>
                        <?php else: ?>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Total (USD)</th>
                            <th>Total (Bs)</th>
                            <th>Estado</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reporte['datos'] as $d): ?>
                    <tr>
                        <?php if ($tipo === 'inventario'): ?>
                            <td><?php echo htmlspecialchars($d->nombre_producto); ?></td>
                            <td>$<?php echo number_format($d->precio, 2); ?></td>
                            <td><?php echo $d->stock; ?> unidades</td>
                            <td><?php echo $d->id_categoria; ?></td>
                        <?php else: ?>
                            <td>#<?php echo $d->id; ?></td>
                            <td><?php echo htmlspecialchars($d->nombre); ?></td>
                            <td><?php echo $d->fecha; ?></td>
                            <td>$<?php echo number_format($d->total, 2); ?></td>
                            <td>Bs. <?php echo number_format(!empty($d->total_bs) ? $d->total_bs : ($d->total * $tasa), 2, ',', '.'); ?></td>
                            <td><strong class="text-bold"><?php echo strtoupper($d->estado); ?></strong></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <script> 
                window.onload = function() { 
                    window.print(); 
                } 
            </script>
        </body>
        </html>
        <?php
        exit();
    }
}