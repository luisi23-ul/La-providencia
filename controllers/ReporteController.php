<?php
//edgar ni se te ocurra tocar algo de aqui
require_once __DIR__ . '/../vendor/autoload.php';

// Conexión con los módulos de plantilla y formato de reportes
require_once __DIR__ . '/../views/reportes/_paquetes.php';
require_once __DIR__ . '/../views/reportes/_excel.php';

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

            // Conversión estricta según pr.moneda para evitar multiplicar dos veces en bolívares
            $sql = "SELECT v.numero_orden AS id, 
                           COALESCE(c.nombre_razon_social, u.nombre, 'Consumidor Final') AS nombre, 
                           pr.fecha_pago AS fecha, 
                           CASE 
                               WHEN UPPER(TRIM(pr.moneda)) IN ('BS', 'VES') THEN ROUND(pr.monto / NULLIF(pr.tasa_cambio, 0), 2)
                               ELSE pr.monto
                           END AS total, 
                           CASE 
                               WHEN UPPER(TRIM(pr.moneda)) IN ('BS', 'VES') THEN pr.monto
                               ELSE ROUND(pr.monto * COALESCE(NULLIF(pr.tasa_cambio, 0), 881.07), 2)
                           END AS total_bs, 
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
        } elseif ($tipo === 'clientes') {
            $estado = trim((string)($_GET['estado'] ?? ''));
            $q      = trim((string)($_GET['q'] ?? ''));

            $where = ["1=1"];
            $params = [];

            if ($estado !== '' && $estado !== 'todos') {
                $where[] = "c.estado = ?";
                $params[] = (int)$estado;
            }
            if (!empty($q)) {
                $where[] = "(c.cedula LIKE ? OR c.nombre_razon_social LIKE ? OR c.telefono LIKE ? OR c.correo LIKE ?)";
                $params[] = "%$q%";
                $params[] = "%$q%";
                $params[] = "%$q%";
                $params[] = "%$q%";
            }

            $sql = "SELECT c.cedula,
                           c.nombre_razon_social AS nombre,
                           c.telefono,
                           c.correo,
                           c.direccion,
                           CASE WHEN c.estado = 1 THEN 'Activo' ELSE 'Inactivo' END AS estado_nombre,
                           COUNT(v.numero_orden) AS total_compras,
                           COALESCE(SUM(v.total), 0) AS total_gastado
                    FROM clientes c
                    LEFT JOIN ventas v ON c.cedula = v.cedula_cliente AND v.estado != 'cancelado'
                    WHERE " . implode(' AND ', $where) . "
                    GROUP BY c.cedula, c.nombre_razon_social, c.telefono, c.correo, c.direccion, c.estado
                    ORDER BY c.nombre_razon_social ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $datos = $stmt->fetchAll(PDO::FETCH_OBJ);

            return [
                'titulo' => 'DIRECTORIO DE CLIENTES - LA PROVIDENCIA',
                'datos'  => $datos
            ];
       } elseif ($tipo === 'proveedores') {
            $estado = trim((string)($_GET['estado'] ?? ''));
            $q      = trim((string)($_GET['q'] ?? ''));

            $where = ["1=1"];
            $params = [];

            if ($estado !== '' && $estado !== 'todos') {
                $where[] = "p.estado = ?";
                $params[] = (int)$estado;
            }

            // Consulta directa sobre la tabla proveedores con p.* para evitar errores de columnas
            $sql = "SELECT p.* FROM proveedores p WHERE " . implode(' AND ', $where);

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $filas = $stmt->fetchAll(PDO::FETCH_OBJ);

            $datos = [];
            foreach ($filas as $f) {
                $rifProv = $f->rif ?? $f->rif_cedula ?? $f->id_proveedor ?? '';
                $nombreRazon = $f->razon_social ?? $f->nombre ?? 'Sin nombre';

                // Filtro de búsqueda por texto ($q)
                if (!empty($q)) {
                    $coincide = (stripos($rifProv, $q) !== false) 
                             || (stripos($nombreRazon, $q) !== false)
                             || (stripos((string)($f->telefono ?? ''), $q) !== false)
                             || (stripos((string)($f->correo ?? ''), $q) !== false);
                    if (!$coincide) continue;
                }

                // Total de compras asociadas (opcional/seguro)
                $totalCompras = 0;
                if (!empty($rifProv)) {
                    try {
                        $stmtC = $this->db->prepare("SELECT COUNT(*) FROM compras WHERE (rif_proveedor = ? OR id_proveedor = ?) AND estado != 'anulado'");
                        $stmtC->execute([$rifProv, $rifProv]);
                        $totalCompras = (int)$stmtC->fetchColumn();
                    } catch (Exception $e) {}
                }

                // Objeto limpio con las columnas exactas para la plantilla
                $item = new stdClass();
                $item->rif_cedula   = $rifProv;
                $item->razon_social = $nombreRazon;
                $item->telefono     = $f->telefono ?? 'S/T';
                $item->correo       = $f->correo ?? 'S/C';
                $item->direccion    = $f->direccion ?? 'S/D';
                $item->compras      = $totalCompras;
                $item->estado       = ((int)($f->estado ?? 1) === 1) ? 'Activo' : 'Inactivo';

                $datos[] = $item;
            }

            usort($datos, function($a, $b) {
                return strcmp($a->razon_social, $b->razon_social);
            });

            return [
                'titulo' => 'DIRECTORIO DE PROVEEDORES - LA PROVIDENCIA',
                'datos'  => $datos
            ];
       } elseif ($tipo === 'compras') {
            $desde  = trim((string)($_GET['desde'] ?? ''));
            $hasta  = trim((string)($_GET['hasta'] ?? ''));
            $estado = trim((string)($_GET['estado'] ?? ''));
            $q      = trim((string)($_GET['q'] ?? ''));

            $where = ["1=1"];
            $params = [];

            if (!empty($estado) && $estado !== 'todas') {
                $where[] = "c.estado = ?";
                $params[] = $estado;
            }

            // Consulta directa sobre compras sin JOINs que puedan fallar por nombres de columna
            $sql = "SELECT c.* FROM compras c WHERE " . implode(' AND ', $where);

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $filas = $stmt->fetchAll(PDO::FETCH_OBJ);

            // Consultas auxiliares preparadas
            $stmtArt = $this->db->prepare("SELECT COALESCE(SUM(cantidad), 0) FROM detalle_compras WHERE numero_factura = ?");
            $stmtProv = $this->db->prepare("SELECT razon_social FROM proveedores WHERE rif = ? OR rif_cedula = ? OR id_proveedor = ? LIMIT 1");
            $stmtUser = $this->db->prepare("SELECT nombre FROM usuarios WHERE cedula = ? LIMIT 1");

           $datos = [];
            foreach ($filas as $f) {
                // 1. Resolver fecha dinámicamente
                $fechaVal = $f->fecha_compra ?? $f->fecha ?? $f->created_at ?? $f->fecha_registro ?? date('Y-m-d');
                $fechaCorta = date('Y-m-d', strtotime($fechaVal));

                if (!empty($desde) && $fechaCorta < $desde) continue;
                if (!empty($hasta) && $fechaCorta > $hasta) continue;

                // 2. Resolver proveedor
                $rifProv = $f->rif_proveedor ?? $f->rif ?? $f->id_proveedor ?? '';
                $nombreProv = 'Proveedor General';
                if (!empty($rifProv)) {
                    try {
                        $stmtProv->execute([$rifProv, $rifProv, $rifProv]);
                        $nombreProv = $stmtProv->fetchColumn() ?: 'Proveedor General';
                    } catch (Exception $e) {}
                }

                // 3. Resolver usuario
                $cedUser = $f->cedula_usuario ?? $f->cedula ?? '';
                $nombreUser = 'Admin';
                if (!empty($cedUser)) {
                    try {
                        $stmtUser->execute([$cedUser]);
                        $nombreUser = $stmtUser->fetchColumn() ?: 'Admin';
                    } catch (Exception $e) {}
                }

                // 4. Total artículos
                $numFact = $f->numero_factura ?? $f->id ?? '';
                $totalArts = 0;
                if (!empty($numFact)) {
                    try {
                        $stmtArt->execute([$numFact]);
                        $totalArts = (int)$stmtArt->fetchColumn();
                    } catch (Exception $e) {}
                }

                // 5. Total en Bs
                $tasaC = (float)($f->tasa_cambio ?? 0) > 0 ? (float)$f->tasa_cambio : 881.07;
                $totalBs = !empty($f->total_bs) ? (float)$f->total_bs : ((float)$f->total * $tasaC);

                // Filtro de búsqueda por texto ($q)
                if (!empty($q)) {
                    $coincide = (stripos($numFact, $q) !== false) || (stripos($nombreProv, $q) !== false);
                    if (!$coincide) continue;
                }

                // Crear un objeto limpio únicamente con las 6 columnas visibles necesarias
                $item = new stdClass();
                $item->fecha          = date('d/m/Y', strtotime($fechaVal));
                $item->numero_factura = $numFact;
                $item->proveedor      = $nombreProv;
                $item->articulos      = $totalArts . ' uds';
                $item->total          = (float)$f->total;
                $item->total_bs       = $totalBs;
                $item->estado         = ucfirst($f->estado ?? 'Recibida');

                $datos[] = $item;
            
            }

            // Ordenar de más reciente a más antiguo
            usort($datos, function($a, $b) {
                return strtotime($b->fecha) <=> strtotime($a->fecha);
            });

            $titulo = 'REPORTE GENERAL DE COMPRAS';
            if ($desde && $hasta) {
                $titulo .= " (" . date('d/m/Y', strtotime($desde)) . " AL " . date('d/m/Y', strtotime($hasta)) . ")";
            }

            return [
                'titulo' => $titulo,
                'datos'  => $datos
            ];
        }

        exit("Tipo de reporte no válido.");
    }

    // Consultas específicas para el análisis estadístico
    private function datosEstadisticas() {
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

        // 3. Top Productos con Mayor Rendimiento
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

        return [
            'desde'   => $desde,
            'hasta'   => $hasta,
            'resumen' => $res,
            'dias'    => $diasVentas,
            'top'     => $topProductos,
        ];
    }

    // PDF / Impresión mediante la plantilla unificada
    public function generarPDF() {
        $tipo = $_GET['tipo'] ?? 'inventario';
        $reporte = $this->obtenerDatosReporte($tipo);
        $tasa = $this->obtenerTasaBCV();
        $extra = $tipo === 'estadisticas' ? $this->datosEstadisticas() : [];
        $p = paquete_reporte($tipo, $reporte, $tasa, $this->db, $extra);
        if (ob_get_length()) ob_end_clean();
        extract($p);
        $modo = 'imprimir';
        include __DIR__ . '/../views/reportes/_plantilla.php';
        exit;
    }

    // Excel generado mediante el módulo unificado
    public function generarExcel() {
        $tipo = $_GET['tipo'] ?? 'inventario';
        $reporte = $this->obtenerDatosReporte($tipo);
        $tasa = $this->obtenerTasaBCV();
        $extra = $tipo === 'estadisticas' ? $this->datosEstadisticas() : [];
        $p = paquete_reporte($tipo, $reporte, $tasa, $this->db, $extra);
        reporte_excel_descargar($p, 'reporte-' . $tipo . '-' . date('Y-m-d'));
    }
}