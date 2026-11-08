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

    private function obtenerTasaBCV() {
        try {
            $stmt = $this->db->query("SELECT tasa_bcv, tasa_manual, tasa_modo FROM sistema LIMIT 1");
            if ($res = $stmt->fetch(PDO::FETCH_OBJ)) {
                return ($res->tasa_modo === 'manual' && (float)$res->tasa_manual > 0)
                    ? (float)$res->tasa_manual
                    : (float)($res->tasa_bcv ?: 881.07);
            }
        } catch (Exception $e) {}
        return 881.07;
    }
    
    // Función interna para centralizar la búsqueda de datos según el tipo
    private function obtenerDatosReporte($tipo) {
        if ($tipo === 'inventario') {
            require_once "models/ProductoModel.php"; 
            $modelo = new ProductoModel($this->db); 
            return [
                'titulo' => 'REPORTE GENERAL DE INVENTARIO - LA PROVIDENCIA',
                'datos'  => $modelo->obtenerTodos()
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
                'datos'  => $modelo->obtenerTodos()
            ];
        } elseif ($tipo === 'kardex' || $tipo === 'movimientos') {
            $tipoMov = trim((string)($_GET['tipo_movimiento'] ?? $_GET['movimiento'] ?? ''));
            $desde   = trim((string)($_GET['desde'] ?? ''));
            $hasta   = trim((string)($_GET['hasta'] ?? ''));
            $q       = trim((string)($_GET['q'] ?? ''));

            $where = ["1=1"];
            $params = [];

            if (!empty($tipoMov) && $tipoMov !== 'todos') {
                $where[] = "k.tipo_movimiento = ?";
                $params[] = $tipoMov;
            }
            if (!empty($desde)) {
                $where[] = "DATE(k.fecha_hora) >= ?";
                $params[] = $desde;
            }
            if (!empty($hasta)) {
                $where[] = "DATE(k.fecha_hora) <= ?";
                $params[] = $hasta;
            }
            if (!empty($q)) {
                $where[] = "(p.nombre_producto LIKE ? OR p.codigo LIKE ? OR k.referencia LIKE ?)";
                $params[] = "%$q%";
                $params[] = "%$q%";
                $params[] = "%$q%";
            }

            $sql = "SELECT k.fecha_hora,
                           p.nombre_producto,
                           p.codigo AS codigo_producto,
                           k.tipo_movimiento,
                           k.cantidad,
                           k.stock_resultante,
                           k.referencia,
                           k.observacion,
                           COALESCE(u.nombre, 'Admin') AS usuario
                    FROM kardex_inventario k
                    INNER JOIN productos p ON k.codigo_producto = p.codigo
                    LEFT JOIN usuarios u ON k.cedula_usuario = u.cedula
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY k.fecha_hora DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $datos = $stmt->fetchAll(PDO::FETCH_OBJ);

            return [
                'titulo' => 'REPORTE DE MOVIMIENTOS DE INVENTARIO (KARDEX)',
                'datos'  => $datos
            ];
        } elseif ($tipo === 'metodo_pago' || $tipo === 'metodo') {
            $idMetodo = trim((string)($_GET['metodo'] ?? ''));
            $desde = trim($_GET['desde'] ?? '');
            $hasta = trim($_GET['hasta'] ?? '');
            $banco = trim($_GET['banco'] ?? '');

            $nombreMetodo = 'Todos los Métodos de Pago';
            if (!empty($idMetodo) && $idMetodo !== 'todos') {
                $stmtM = $this->db->prepare("SELECT nombre FROM metodos_pago WHERE codigo_metodo = ? LIMIT 1");
                $stmtM->execute([$idMetodo]);
                $nombreMetodo = $stmtM->fetchColumn() ?: $idMetodo;
            }

            $where = ["pr.estado IN ('aprobado', 'verificado')"];
            $params = [];

            if (!empty($idMetodo) && $idMetodo !== 'todos') {
                $where[] = "pr.codigo_metodo = ?";
                $params[] = $idMetodo;
            }
            if ($desde !== '') {
                $where[] = "DATE(pr.fecha_pago) >= ?";
                $params[] = $desde;
            }
            if ($hasta !== '') {
                $where[] = "DATE(pr.fecha_pago) <= ?";
                $params[] = $hasta;
            }
            if (!empty($banco) && $banco !== 'todos') {
                $where[] = "pr.banco = ?";
                $params[] = $banco;
            }

$sql = "SELECT v.numero_orden AS id, 
                           COALESCE(c.nombre_razon_social, u.nombre, 'Consumidor Final') AS nombre, 
                           pr.fecha_pago AS fecha, 
                           pr.monto AS total, 
                           (pr.monto * COALESCE(NULLIF(pr.tasa_cambio, 0), 881.07)) AS total_bs, 
                           pr.estado,
                           COALESCE(mp.nombre, pr.codigo_metodo) AS metodo_nombre,
                           COALESCE(pr.banco, 'Sin banco') AS banco,
                           COALESCE(pr.referencia, 'S/R') AS referencia
                    FROM pagos pr
                    INNER JOIN ventas v ON pr.numero_orden = v.numero_orden
                    LEFT JOIN clientes c ON v.cedula_cliente = c.cedula
                    LEFT JOIN usuarios u ON v.cedula_usuario = u.cedula
                    LEFT JOIN metodos_pago mp ON pr.codigo_metodo = mp.codigo_metodo
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY pr.fecha_pago DESC";

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
        $tipo = $_GET['tipo'] ?? 'inventario';
        $reporte = $this->obtenerDatosReporte($tipo);
        $tasa = $this->obtenerTasaBCV();

        if ($tipo === 'estadisticas') {
            if (ob_get_length()) ob_end_clean();

            $datos = $reporte['datos'];
            usort($datos, function($a, $b) {
                return (int)$a->stock <=> (int)$b->stock;
            });
            $datos = array_slice($datos, 0, 5);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Análisis Estadístico');

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
            $filaFin = max(10, $filaInicio - 1);

            $nombreHoja = 'Análisis Estadístico';
            $rangoCategorias = "'" . $nombreHoja . "'!\$A$10:\$A$" . $filaFin;
            $rangoValores = "'" . $nombreHoja . "'!\$B$10:\$B$" . $filaFin;

            $categories = [new DataSeriesValues('String', $rangoCategorias, null, count($datos))];
            $values = [new DataSeriesValues('Number', $rangoValores, null, count($datos))];

            $graficoTipo = ($_GET['grafico'] ?? '') === 'torta' 
                ? DataSeries::TYPE_PIECHART 
                : DataSeries::TYPE_BARCHART;

            $grouping = ($_GET['grafico'] ?? '') === 'torta' 
                ? null 
                : DataSeries::GROUPING_CLUSTERED;

            $series = new DataSeries(
                $graficoTipo, 
                $grouping, 
                range(0, count($values) - 1), 
                [], 
                $categories, 
                $values
            );

            if ($graficoTipo === DataSeries::TYPE_BARCHART) {
                $series->setPlotDirection(DataSeries::DIRECTION_COL);
            }

            $plotArea = new PlotArea(null, [$series]);
            $title = new Title('Nivel de Existencias');
            $chart = new Chart('grafico_stock', $title, null, $plotArea);

            $chart->setTopLeftPosition('D4');
            $chart->setBottomRightPosition('L15');
            $sheet->addChart($chart);

            $nombreArchivo = "Top5_Stock_Bajo_" . date('d_m_Y') . ".xlsx";
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $nombreArchivo . '"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->setIncludeCharts(true); 
            $writer->save('php://output');
            exit();
        }

        if (ob_get_length()) ob_end_clean();
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', $reporte['titulo']);
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        if ($tipo === 'inventario') {
            $headers = ['Código', 'Producto', 'Precio ($)', 'Stock', 'Descripción', 'Categoría'];
        } elseif ($tipo === 'kardex' || $tipo === 'movimientos') {
            $headers = ['Fecha y Hora', 'Producto', 'Movimiento', 'Cantidad', 'Stock Resultante', 'Referencia / Origen', 'Usuario'];
        } elseif ($tipo === 'metodo_pago' || $tipo === 'metodo') {
            $headers = ['Nro Orden', 'Cliente', 'Fecha Cobro', 'Método', 'Banco / Referencia', 'Total ($)', 'Total (Bs)'];
        } else {
            $headers = ['Nro Orden', 'Cliente', 'Fecha', 'Total (USD)', 'Total (Bs)', 'Estado'];
        }
        
        $sheet->fromArray($headers, NULL, 'A3');
        $sheet->getStyle('A3:G3')->getFont()->setBold(true);

        $fila = 4;
        foreach ($reporte['datos'] as $d) {
            if ($tipo === 'inventario') {
                $catNom = !empty($d->nombre_categoria) ? $d->nombre_categoria : (!empty($d->slug_categoria) ? $d->slug_categoria : 'General');
                $sheet->fromArray([$d->codigo, $d->nombre_producto, (float)$d->precio, (int)$d->stock, $d->descripcion, $catNom], NULL, 'A' . $fila);
            } elseif ($tipo === 'kardex' || $tipo === 'movimientos') {
                $sheet->fromArray([
                    $d->fecha_hora,
                    $d->nombre_producto . " ({$d->codigo_producto})",
                    ucfirst(str_replace('_', ' ', $d->tipo_movimiento)),
                    (int)$d->cantidad,
                    (int)$d->stock_resultante,
                    $d->referencia ?: ($d->observacion ?: 'S/R'),
                    $d->usuario
                ], NULL, 'A' . $fila);
            } elseif ($tipo === 'metodo_pago' || $tipo === 'metodo') {
                $montoBs = !empty($d->total_bs) ? (float)$d->total_bs : ((float)$d->total * $tasa);
                $bancoRef = trim(($d->banco ?? '') . ' - Ref: ' . ($d->referencia ?? ''));
                $sheet->fromArray([
                    $d->id ?? $d->numero_orden,
                    $d->nombre,
                    $d->fecha,
                    $d->metodo_nombre ?? 'Método',
                    $bancoRef,
                    (float)$d->total,
                    $montoBs
                ], NULL, 'A' . $fila);
            } else {
                $montoBs = !empty($d->total_bs) ? (float)$d->total_bs : ((float)$d->total * $tasa);
                $sheet->fromArray([
                    $d->id ?? $d->numero_orden, 
                    $d->nombre, 
                    $d->fecha, 
                    (float)$d->total, 
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
        $tipo = $_GET['tipo'] ?? 'inventario';
        $reporte = $this->obtenerDatosReporte($tipo);
        $tasa = $this->obtenerTasaBCV();
        
        if ($tipo === 'estadisticas') {
            if (ob_get_length()) ob_end_clean();

            $desde = !empty($_GET['desde']) ? date('Y-m-d', strtotime($_GET['desde'])) : date('Y-m-d', strtotime('-13 days'));
            $hasta = !empty($_GET['hasta']) ? date('Y-m-d', strtotime($_GET['hasta'])) : date('Y-m-d');

            // 1. Totales de Ventas y Ganancia
            $sqlResumen = "
                SELECT 
                    COALESCE(SUM(v.total), 0) AS vendido,
                    COUNT(v.numero_orden) AS ventas,
                    COALESCE(SUM(CASE WHEN v.origen_venta IN ('tienda', 'tienda_fisica') THEN v.total ELSE 0 END), 0) AS tienda,
                    COALESCE(SUM(
                        (SELECT COALESCE(SUM((dv.precio_unitario - dv.costo_unitario) * dv.cantidad), 0)
                         FROM detalle_ventas dv 
                         WHERE dv.numero_orden = v.numero_orden)
                    ), 0) AS ganancia
                FROM ventas v
                WHERE v.estado != 'cancelado' 
                  AND DATE(v.fecha) BETWEEN ? AND ?
            ";
            $stmtR = $this->db->prepare($sqlResumen);
            $stmtR->execute([$desde, $hasta]);
            $res = $stmtR->fetch(PDO::FETCH_OBJ);

            $vendido = (float)($res->vendido ?? 0);
            $ganancia = (float)($res->ganancia ?? 0);
            $ventas = (int)($res->ventas ?? 0);
            $ticket = $ventas > 0 ? ($vendido / $ventas) : 0;

            // 2. Desglose de Ventas por Día
            $sqlDias = "
                SELECT 
                    DATE(v.fecha) AS fecha,
                    COUNT(v.numero_orden) AS total_pedidos,
                    COALESCE(SUM(CASE WHEN v.origen_venta IN ('tienda', 'tienda_fisica') THEN v.total ELSE 0 END), 0) AS tienda,
                    COALESCE(SUM(CASE WHEN v.origen_venta NOT IN ('tienda', 'tienda_fisica') THEN v.total ELSE 0 END), 0) AS online,
                    COALESCE(SUM(v.total), 0) AS total_dia
                FROM ventas v
                WHERE v.estado != 'cancelado' 
                  AND DATE(v.fecha) BETWEEN ? AND ?
                GROUP BY DATE(v.fecha)
                ORDER BY fecha DESC
            ";
            $stmtD = $this->db->prepare($sqlDias);
            $stmtD->execute([$desde, $hasta]);
            $diasVentas = $stmtD->fetchAll(PDO::FETCH_OBJ);

            // 3. Top Productos
            $sqlTop = "
                SELECT 
                    p.nombre_producto,
                    COALESCE(c.nombre_categoria, 'General') AS categoria,
                    SUM(dv.cantidad) AS unidades,
                    SUM(dv.subtotal) AS monto,
                    SUM((dv.precio_unitario - dv.costo_unitario) * dv.cantidad) AS ganancia
                FROM detalle_ventas dv
                INNER JOIN ventas v ON dv.numero_orden = v.numero_orden
                INNER JOIN productos p ON dv.codigo_producto = p.codigo
                LEFT JOIN categorias c ON p.slug_categoria = c.slug
                WHERE v.estado != 'cancelado' 
                  AND DATE(v.fecha) BETWEEN ? AND ?
                GROUP BY p.codigo, p.nombre_producto, c.nombre_categoria
                ORDER BY monto DESC
                LIMIT 5
            ";
            $stmtTop = $this->db->prepare($sqlTop);
            $stmtTop->execute([$desde, $hasta]);
            $topProductos = $stmtTop->fetchAll(PDO::FETCH_OBJ);
            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Reporte Estadístico de Ventas</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 25px; color: #1e293b; background: #fff; }
                    .print-header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0f3d2e; padding-bottom: 10px; }
                    .print-header h1 { margin: 0; font-size: 20pt; color: #0f3d2e; }
                    .print-header h3 { margin: 5px 0; font-size: 11pt; color: #475569; }
                    .print-header p { margin: 2px 0 0; font-size: 8.5pt; color: #64748b; }
                    
                    .kpi-container { display: flex; justify-content: space-between; margin-bottom: 20px; gap: 10px; }
                    .kpi-card { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; text-align: center; background: #f8fafc; }
                    .kpi-card span { font-size: 8.5pt; color: #64748b; text-transform: uppercase; font-weight: bold; }
                    .kpi-card h2 { margin: 5px 0 0; font-size: 14pt; color: #0f3d2e; }

                    .seccion-titulo { margin-top: 25px; margin-bottom: 8px; font-size: 11pt; color: #0f3d2e; font-weight: bold; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 9pt; }
                    th { background-color: #0f3d2e; color: white; padding: 8px 6px; text-align: left; font-size: 8.5pt; text-transform: uppercase; }
                    td { padding: 7px 6px; border-bottom: 1px solid #e2e8f0; }
                    tr:nth-child(even) { background-color: #f8fafc; }
                    .text-right { text-align: right; }
                    .text-center { text-align: center; }
                    @media print { body { margin: 10mm; } }
                </style>
            </head>
            <body>
                <div class="print-header">
                    <h1>LA PROVIDENCIA</h1>
                    <h3>ANÁLISIS ESTADÍSTICO DE VENTAS</h3>
                    <p>Período: <?php echo date('d/m/Y', strtotime($desde)); ?> al <?php echo date('d/m/Y', strtotime($hasta)); ?> | Tasa BCV: Bs. <?php echo number_format($tasa, 2, ',', '.'); ?></p>
                </div>

                <div class="kpi-container">
                    <div class="kpi-card">
                        <span>Total Vendido</span>
                        <h2>$<?php echo number_format($vendido, 2); ?></h2>
                        <small style="color: #64748b;">Bs. <?php echo number_format($vendido * $tasa, 2, ',', '.'); ?></small>
                    </div>
                    <div class="kpi-card">
                        <span>Ganancia Estimada</span>
                        <h2>$<?php echo number_format($ganancia, 2); ?></h2>
                        <small style="color: #64748b;">Bs. <?php echo number_format($ganancia * $tasa, 2, ',', '.'); ?></small>
                    </div>
                    <div class="kpi-card">
                        <span>Órdenes Concretadas</span>
                        <h2><?php echo $ventas; ?></h2>
                        <small style="color: #64748b;">Ventas registradas</small>
                    </div>
                    <div class="kpi-card">
                        <span>Ticket Promedio</span>
                        <h2>$<?php echo number_format($ticket, 2); ?></h2>
                        <small style="color: #64748b;">Por pedido</small>
                    </div>
                </div>

                <div class="seccion-titulo">1. Desglose Cronológico de Ventas</div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 25%;">Fecha</th>
                            <th style="width: 15%;" class="text-center">Nro Pedidos</th>
                            <th style="width: 20%;" class="text-right">Tienda Física ($)</th>
                            <th style="width: 20%;" class="text-right">Tienda Online ($)</th>
                            <th style="width: 20%;" class="text-right">Total ($)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($diasVentas)): ?>
                            <tr><td colspan="5" class="text-center" style="padding: 15px; color: #64748b;">No hay movimientos de ventas en este período.</td></tr>
                        <?php else: ?>
                            <?php foreach ($diasVentas as $dv): ?>
                            <tr>
                                <td><b><?php echo date('d/m/Y', strtotime($dv->fecha)); ?></b></td>
                                <td class="text-center"><?php echo $dv->total_pedidos; ?></td>
                                <td class="text-right">$<?php echo number_format((float)$dv->tienda, 2); ?></td>
                                <td class="text-right">$<?php echo number_format((float)$dv->online, 2); ?></td>
                                <td class="text-right"><b>$<?php echo number_format((float)$dv->total_dia, 2); ?></b></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="seccion-titulo">2. Top 5 Productos con Mayor Rendimiento</div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40%;">Producto</th>
                            <th style="width: 20%;">Categoría</th>
                            <th style="width: 10%;" class="text-center">Unidades</th>
                            <th style="width: 15%;" class="text-right">Total Ventas ($)</th>
                            <th style="width: 15%;" class="text-right">Ganancia ($)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topProductos)): ?>
                            <tr><td colspan="5" class="text-center" style="padding: 15px; color: #64748b;">Sin productos vendidos en el rango de fechas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($topProductos as $tp): ?>
                            <tr>
                                <td><b><?php echo htmlspecialchars($tp->nombre_producto); ?></b></td>
                                <td><?php echo htmlspecialchars($tp->categoria); ?></td>
                                <td class="text-center"><?php echo (int)$tp->unidades; ?></td>
                                <td class="text-right">$<?php echo number_format((float)$tp->monto, 2); ?></td>
                                <td class="text-right" style="color: #047857;"><b>+$<?php echo number_format((float)$tp->ganancia, 2); ?></b></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <script>
                    window.onload = function() {
                        window.print();
                    };
                </script>
            </body>
            </html>
            <?php
            exit();
        }

        if (ob_get_length()) ob_end_clean();
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title><?php echo htmlspecialchars($reporte['titulo']); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 25px; color: #1e293b; background: #fff; }
                .print-header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0f3d2e; padding-bottom: 10px; }
                .print-header h1 { margin: 0; font-size: 20pt; font-weight: 800; color: #0f3d2e; }
                .print-header h3 { margin: 5px 0; font-size: 11pt; font-weight: 700; color: #334155; }
                .print-header p { margin: 2px 0 0; font-size: 8.5pt; color: #64748b; }
                table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 9.5pt; }
                th { background-color: #0f3d2e; color: white; padding: 9px 8px; text-align: left; font-size: 8.5pt; text-transform: uppercase; border: 1px solid #0f3d2e; }
                td { padding: 8px; border-bottom: 1px solid #e2e8f0; border-left: 1px solid #f8fafc; border-right: 1px solid #f8fafc; }
                tr:nth-child(even) { background-color: #f8fafc; }
                .text-bold { font-weight: bold; }
                .text-right { text-align: right; }
                .badge { padding: 2px 6px; border-radius: 4px; font-size: 8pt; font-weight: bold; background: #e2e8f0; text-transform: uppercase; }
                @media print {
                    body { margin: 10mm; }
                }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h1>LA PROVIDENCIA</h1>
                <h3><?php echo htmlspecialchars($reporte['titulo']); ?></h3>
                <p>Fecha de emisión: <?php echo date('d/m/Y h:i A'); ?> | Tasa BCV: Bs. <?php echo number_format($tasa, 2, ',', '.'); ?></p>
            </div>
            <table class="print-table">
                <thead>
                    <tr>
                        <?php if ($tipo === 'inventario'): ?>
                            <th style="width: 15%;">Código</th>
                            <th style="width: 35%;">Producto</th>
                            <th style="width: 15%;" class="text-right">Precio ($)</th>
                            <th style="width: 15%;" class="text-right">Stock</th>
                            <th style="width: 20%;">Categoría</th>
                        <?php elseif ($tipo === 'kardex' || $tipo === 'movimientos'): ?>
                            <th style="width: 18%;">Fecha</th>
                            <th style="width: 27%;">Producto</th>
                            <th style="width: 15%;">Movimiento</th>
                            <th style="width: 10%; text-align: center;">Cantidad</th>
                            <th style="width: 10%; text-align: center;">Stock Final</th>
                            <th style="width: 20%;">Origen / Usuario</th>
                        <?php elseif ($tipo === 'metodo_pago' || $tipo === 'metodo'): ?>
                            <th style="width: 15%;">Nro Orden</th>
                            <th style="width: 25%;">Cliente</th>
                            <th style="width: 15%;">Fecha Pago</th>
                            <th style="width: 20%;">Banco / Ref</th>
                            <th style="width: 12%;" class="text-right">Total ($)</th>
                            <th style="width: 13%;" class="text-right">Total (Bs)</th>
                        <?php else: ?>
                            <th style="width: 15%;">Nro Orden</th>
                            <th style="width: 30%;">Cliente</th>
                            <th style="width: 15%;">Fecha</th>
                            <th style="width: 15%;" class="text-right">Total ($)</th>
                            <th style="width: 15%;" class="text-right">Total (Bs)</th>
                            <th style="width: 10%; text-align: center;">Estado</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reporte['datos'])): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px; color: #64748b;">No hay registros disponibles para este método o fechas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reporte['datos'] as $d): ?>
                        <tr>
                            <?php if ($tipo === 'inventario'): ?>
                                <td><b><?php echo htmlspecialchars($d->codigo); ?></b></td>
                                <td><?php echo htmlspecialchars($d->nombre_producto); ?></td>
                                <td class="text-right">$<?php echo number_format((float)$d->precio, 2); ?></td>
                                <td class="text-right"><?php echo (int)$d->stock; ?> uds</td>
                                <td><?php echo htmlspecialchars(!empty($d->nombre_categoria) ? $d->nombre_categoria : (!empty($d->slug_categoria) ? $d->slug_categoria : 'General')); ?></td>
                            <?php elseif ($tipo === 'kardex' || $tipo === 'movimientos'): ?>
                                <td><?php echo date('d/m/Y h:i a', strtotime($d->fecha_hora)); ?></td>
                                <td><b><?php echo htmlspecialchars($d->nombre_producto); ?></b><br><small style="color: #64748b;"><?php echo htmlspecialchars($d->codigo_producto); ?></small></td>
                                <td><span class="badge"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $d->tipo_movimiento))); ?></span></td>
                                <td style="text-align: center; font-weight: bold;"><?php echo (int)$d->cantidad; ?></td>
                                <td style="text-align: center;"><?php echo (int)$d->stock_resultante; ?> uds</td>
                                <td>
                                    <?php echo htmlspecialchars($d->referencia ?: ($d->observacion ?: 'General')); ?>
                                    <br><small style="color: #64748b;">Resp: <?php echo htmlspecialchars($d->usuario); ?></small>
                                </td>
                            <?php elseif ($tipo === 'metodo_pago' || $tipo === 'metodo'): ?>
                                <td><b>#<?php echo htmlspecialchars($d->id ?? $d->numero_orden); ?></b></td>
                                <td><?php echo htmlspecialchars($d->nombre); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($d->fecha)); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($d->banco); ?>
                                    <br><small style="color: #64748b;">Ref: <?php echo htmlspecialchars($d->referencia); ?></small>
                                </td>
                                <td class="text-right">$<?php echo number_format((float)$d->total, 2); ?></td>
                                <td class="text-right">Bs. <?php echo number_format(!empty($d->total_bs) ? (float)$d->total_bs : ((float)$d->total * $tasa), 2, ',', '.'); ?></td>
                            <?php else: ?>
                                <td><b>#<?php echo htmlspecialchars($d->id ?? $d->numero_orden); ?></b></td>
                                <td><?php echo htmlspecialchars($d->nombre); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($d->fecha)); ?></td>
                                <td class="text-right">$<?php echo number_format((float)$d->total, 2); ?></td>
                                <td class="text-right">Bs. <?php echo number_format(!empty($d->total_bs) ? (float)$d->total_bs : ((float)$d->total * $tasa), 2, ',', '.'); ?></td>
                                <td style="text-align: center;"><span class="badge"><?php echo strtoupper($d->estado); ?></span></td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
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