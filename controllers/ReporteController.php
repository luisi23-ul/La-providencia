<?php
// controllers/ReporteController.php

class ReporteController {
    private $db;

    // Recibe la conexión desde el index.php
    public function __construct($db) {
        $this->db = $db;
    }
    // Función interna para centralizar la búsqueda de datos según el tipo
    private function obtenerDatosReporte($tipo) {
        if ($tipo === 'inventario') {
            require_once "models/ProductoModel.php"; // Asegúrate de que no lleve la 's' si tu archivo se llama ProductoModel.php
            $modelo = new ProductoModel($this->db); // <<< PASAR $this->db AQUÍ
            return [
                'titulo' => 'REPORTE GENERAL DE INVENTARIO - LA PROVIDENCIA',
                'datos'  => $modelo->listarProductos()
            ];
        } elseif ($tipo === 'pendientes') {
            require_once "models/VentaModel.php";
            $modelo = new VentaModel($this->db);
            
            // Fuerza a que ignore cualquier ID individual para el reporte general de la cabecera
            return [
                'titulo' => 'REPORTE DE VENTAS: PAGOS PENDIENTES',
                'datos'  => $modelo->obtenerPorEstado('pendiente') 
            ];
        } elseif ($tipo === 'retiros') {
            require_once "models/VentaModel.php";
            $modelo = new VentaModel($this->db); // <<< PASAR $this->db AQUÍ
            return [
                'titulo' => 'REPORTE DE LOGÍSTICA: PEDIDOS LISTOS PARA RETIRO',
                'datos'  => $modelo->obtenerPorEstado('pagado')
            ];
        }
        exit("Tipo de reporte no válido.");
    }

    // ==========================================
    // LOGICA PARA EXPORTAR A EXCEL
    // ==========================================
    public function generarExcel() {
        $tipo = $_GET['tipo'] ?? '';
        $reporte = $this->obtenerDatosReporte($tipo);

        // Limpiamos el búfer de salida para evitar que el archivo se descargue corrupto
        if (ob_get_length()) ob_end_clean();

        $nombreArchivo = "Reporte_" . $tipo . "_" . date('d_m_Y') . ".xls";

        // Cabeceras HTTP para indicarle al navegador que es un archivo descargable de Excel
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=$nombreArchivo");
        header("Pragma: no-cache");
        header("Expires: 0");

        // Construcción de la tabla que Excel interpretará nativamente en celdas perfectas
        echo "<table border='1' style='font-family: Arial, sans-serif; border-collapse: collapse;'>";
        echo "<tr><th colspan='5' style='background-color: #4b5563; color: white; font-size: 14pt; padding: 10px;'> " . $reporte['titulo'] . "</th></tr>";
        echo "<tr><td colspan='5' style='text-align: center; font-size: 9pt; color: #555;'>Fecha de generación: " . date('d/m/Y H:i:s') . "</td></tr>";
        echo "<tr><td colspan='5'></td></tr>"; // Fila de separación vacía

        if ($tipo === 'inventario') {
            // Encabezados de columnas para Inventario
            echo "<tr style='background-color: #cbd5e1; font-weight: bold;'>
                    <th style='padding: 5px;'>Producto</th>
                    <th style='padding: 5px;'>Precio</th>
                    <th style='padding: 5px;'>Stock Disponible</th>
                    <th style='padding: 5px;'>Descripción</th>
                    <th style='padding: 5px;'>Categoría</th>
                  </tr>";
            
            foreach ($reporte['datos'] as $p) {
                echo "<tr>
                        <td style='padding: 5px;'>" . htmlspecialchars($p->nombre_producto) . "</td>
                        <td style='padding: 5px; text-align: right;'>$" . number_format($p->precio, 2) . "</td>
                        <td style='padding: 5px; text-align: center;'>{$p->stock} unidades</td>
                        <td style='padding: 5px;'>" . htmlspecialchars($p->descripcion) . "</td>
                        <td style='padding: 5px; text-align: center;'>{$p->id_categoria}</td>
                      </tr>";
            }
        } else {
            // Encabezados de columnas para Ventas (Pendientes o Retiros)
            echo "<tr style='background-color: #cbd5e1; font-weight: bold;'>
                    <th style='padding: 5px;'>ID Orden</th>
                    <th style='padding: 5px;'>Cliente</th>
                    <th style='padding: 5px;'>Fecha de Registro</th>
                    <th style='padding: 5px;'>Monto Total</th>
                    <th style='padding: 5px;'>Estado Actual</th>
                  </tr>";
            
            foreach ($reporte['datos'] as $v) {
                echo "<tr>
                        <td style='padding: 5px; text-align: center;'>#{$v->id}</td>
                        <td style='padding: 5px;'>" . htmlspecialchars($v->nombre) . "</td>
                        <td style='padding: 5px; text-align: center;'>{$v->fecha}</td>
                        <td style='padding: 5px; text-align: right;'>$" . number_format($v->total, 2) . "</td>
                        <td style='padding: 5px; text-align: center; font-weight: bold;'>" . strtoupper($v->estado) . "</td>
                      </tr>";
            }
        }
        echo "</table>";
        exit();
    }

    // ==========================================
    // LOGICA PARA IMPRIMIR / EXPORTAR A PDF
    // ==========================================
    public function generarPDF() {
        $tipo = $_GET['tipo'] ?? '';
        $reporte = $this->obtenerDatosReporte($tipo);

        if (ob_get_length()) ob_end_clean();

        // Creamos una plantilla HTML ultra limpia, formateada específicamente para hojas tamaño Carta/A4
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title><?php echo $reporte['titulo']; ?></title>
            <style>
                body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #333; margin: 30px; }
                .print-header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #4b5563; padding-bottom: 10px; }
                .print-header h1 { margin: 5px 0; font-size: 20pt; color: #1e293b; }
                .print-header h3 { margin: 5px 0; font-size: 13pt; color: #4b5563; font-weight: normal; }
                .print-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 10pt; }
                .print-table th { background-color: #0f172a; color: white; padding: 10px; text-align: left; text-transform: uppercase; font-size: 9pt; }
                .print-table td { padding: 10px; border-bottom: 1px solid #cbd5e1; }
                .print-table tr:nth-child(even) { background-color: #f8fafc; }
                .meta-fecha { text-align: right; font-size: 9pt; color: #64748b; margin-top: 5px; }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h1>LA PROVIDENCIA</h1>
                <h3><?php echo $reporte['titulo']; ?></h3>
                <div class="meta-fecha">Emitido el: <?php echo date('d/m/Y h:i A'); ?></div>
            </div>

            <table class="print-table">
                <?php if ($tipo === 'inventario'): ?>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Stock Disponible</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reporte['datos'] as $p): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($p->nombre_producto); ?></strong></td>
                            <td>$<?php echo number_format($p->precio, 2); ?></td>
                            <td><?php echo $p->stock; ?> unidades</td>
                            <td><?php echo htmlspecialchars($p->descripcion); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                <?php else: ?>
                    <thead>
                        <tr>
                            <th>ID Orden</th>
                            <th>Cliente / Usuario</th>
                            <th>Fecha Registro</th>
                            <th>Total Cobrado</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reporte['datos'] as $v): ?>
                        <tr>
                            <td>#<?php echo $v->id; ?></td>
                            <td><?php echo htmlspecialchars($v->nombre); ?></td>
                            <td><?php echo $v->fecha; ?></td>
                            <td>$<?php echo number_format($v->total, 2); ?></td>
                            <td style="font-weight: bold;"><?php echo strtoupper($v->estado); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                <?php endif; ?>
            </table>

            <script>
                // Abre de forma automática el cuadro de diálogo de impresión del sistema al cargar
                // Esto le permite al administrador guardarlo como PDF o mandarlo a una impresora física
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