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
        } elseif ($tipo === 'ventas') {
            $desde = trim((string)($_GET['desde'] ?? ''));
            $hasta = trim((string)($_GET['hasta'] ?? ''));
            
            $where = ["(v.deleted_at = '2000-01-01 00:00:00' OR v.deleted_at IS NULL)"];
            $params = [];

            if (!empty($desde)) {
                $where[] = "DATE(v.fecha) >= ?";
                $params[] = $desde;
            }
            if (!empty($hasta)) {
                $where[] = "DATE(v.fecha) <= ?";
                $params[] = $hasta;
            }

            $sql = "SELECT v.numero_orden, 
                           v.fecha, 
                           COALESCE(c.nombre_razon_social, 'Consumidor Final') AS cliente, 
                           COALESCE(u.nombre, 'Cajero') AS cajero, 
                           v.total, 
                           v.tasa_cambio, 
                           v.estado, 
                           v.origen_venta
                    FROM ventas v
                    LEFT JOIN clientes c ON v.cedula_cliente = c.cedula
                    LEFT JOIN usuarios u ON v.cedula_usuario = u.cedula
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY v.fecha DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $datos = $stmt->fetchAll(PDO::FETCH_OBJ);

            $titulo = 'REPORTE GENERAL DE VENTAS';
            if ($desde && $hasta) {
                $titulo .= " (" . date('d/m/Y', strtotime($desde)) . " AL " . date('d/m/Y', strtotime($hasta)) . ")";
            }

            return [
                'titulo' => $titulo,
                'datos'  => $datos
            ];
        } elseif ($tipo === 'pagos_banco') {
            $desde = trim((string)($_GET['desde'] ?? ''));
            $hasta = trim((string)($_GET['hasta'] ?? ''));

            $where = ["1=1"];
            $params = [];

            if (!empty($desde)) {
                $where[] = "DATE(p.fecha_pago) >= ?";
                $params[] = $desde;
            }
            if (!empty($hasta)) {
                $where[] = "DATE(p.fecha_pago) <= ?";
                $params[] = $hasta;
            }

            $sql = "SELECT p.numero_orden, 
                           p.fecha_pago, 
                           p.banco, 
                           p.codigo_metodo, 
                           p.referencia, 
                           p.moneda, 
                           p.monto, 
                           p.tasa_cambio, 
                           p.estado, 
                           COALESCE(u.nombre, 'Sistema') AS cajero
                    FROM pagos p
                    LEFT JOIN usuarios u ON p.cedula_cajero = u.cedula
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY p.fecha_pago DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $datos = $stmt->fetchAll(PDO::FETCH_OBJ);

            $titulo = 'REPORTE DE PAGOS POR BANCO (CONCILIACIÓN)';
            if ($desde && $hasta) {
                $titulo .= " (" . date('d/m/Y', strtotime($desde)) . " AL " . date('d/m/Y', strtotime($hasta)) . ")";
            }

            return [
                'titulo' => $titulo,
                'datos'  => $datos
            ];
        } elseif ($tipo === 'auditoria') {
            $desde = trim((string)($_GET['desde'] ?? ''));
            $hasta = trim((string)($_GET['hasta'] ?? ''));

            $where = ["1=1"];
            $params = [];

            if (!empty($desde)) {
                $where[] = "DATE(a.fecha_hora) >= ?";
                $params[] = $desde;
            }
            if (!empty($hasta)) {
                $where[] = "DATE(a.fecha_hora) <= ?";
                $params[] = $hasta;
            }

            $sql = "SELECT a.fecha_hora, 
                           COALESCE(u.nombre, a.cedula) AS usuario, 
                           a.modulo, 
                           a.accion, 
                           a.detalle_cambio, 
                           a.ip_terminal
                    FROM auditoria_logs a
                    LEFT JOIN usuarios u ON a.cedula = u.cedula
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY a.fecha_hora DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $datos = $stmt->fetchAll(PDO::FETCH_OBJ);

            $titulo = 'REPORTE DE AUDITORÍA DEL SISTEMA';
            if ($desde && $hasta) {
                $titulo .= " (" . date('d/m/Y', strtotime($desde)) . " AL " . date('d/m/Y', strtotime($hasta)) . ")";
            }

            return [
                'titulo' => $titulo,
                'datos'  => $datos
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

            $sql = "SELECT p.* FROM proveedores p WHERE " . implode(' AND ', $where);

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $filas = $stmt->fetchAll(PDO::FETCH_OBJ);

            $datos = [];
            foreach ($filas as $f) {
                $rifProv = $f->rif ?? $f->rif_cedula ?? $f->id_proveedor ?? '';
                $nombreRazon = $f->razon_social ?? $f->nombre ?? 'Sin nombre';

                if (!empty($q)) {
                    $coincide = (stripos($rifProv, $q) !== false) 
                             || (stripos($nombreRazon, $q) !== false)
                             || (stripos((string)($f->telefono ?? ''), $q) !== false)
                             || (stripos((string)($f->correo ?? ''), $q) !== false);
                    if (!$coincide) continue;
                }

                $totalCompras = 0;
                if (!empty($rifProv)) {
                    try {
                        $stmtC = $this->db->prepare("SELECT COUNT(*) FROM compras WHERE (rif_proveedor = ? OR id_proveedor = ?) AND estado != 'anulado'");
                        $stmtC->execute([$rifProv, $rifProv]);
                        $totalCompras = (int)$stmtC->fetchColumn();
                    } catch (Exception $e) {}
                }

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

            $sql = "SELECT c.* FROM compras c WHERE " . implode(' AND ', $where);

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $filas = $stmt->fetchAll(PDO::FETCH_OBJ);

            $stmtArt = $this->db->prepare("SELECT COALESCE(SUM(cantidad), 0) FROM detalle_compras WHERE numero_factura = ?");
            $stmtProv = $this->db->prepare("SELECT razon_social FROM proveedores WHERE rif = ? OR rif_cedula = ? OR id_proveedor = ? LIMIT 1");
            $stmtUser = $this->db->prepare("SELECT nombre FROM usuarios WHERE cedula = ? LIMIT 1");

            $datos = [];
            foreach ($filas as $f) {
                $fechaVal = $f->fecha_compra ?? $f->fecha ?? $f->created_at ?? $f->fecha_registro ?? date('Y-m-d');
                $fechaCorta = date('Y-m-d', strtotime($fechaVal));

                if (!empty($desde) && $fechaCorta < $desde) continue;
                if (!empty($hasta) && $fechaCorta > $hasta) continue;

                $rifProv = $f->rif_proveedor ?? $f->rif ?? $f->id_proveedor ?? '';
                $nombreProv = 'Proveedor General';
                if (!empty($rifProv)) {
                    try {
                        $stmtProv->execute([$rifProv, $rifProv, $rifProv]);
                        $nombreProv = $stmtProv->fetchColumn() ?: 'Proveedor General';
                    } catch (Exception $e) {}
                }

                $cedUser = $f->cedula_usuario ?? $f->cedula ?? '';
                $nombreUser = 'Admin';
                if (!empty($cedUser)) {
                    try {
                        $stmtUser->execute([$cedUser]);
                        $nombreUser = $stmtUser->fetchColumn() ?: 'Admin';
                    } catch (Exception $e) {}
                }

                $numFact = $f->numero_factura ?? $f->id ?? '';
                $totalArts = 0;
                if (!empty($numFact)) {
                    try {
                        $stmtArt->execute([$numFact]);
                        $totalArts = (int)$stmtArt->fetchColumn();
                    } catch (Exception $e) {}
                }

                $tasaC = (float)($f->tasa_cambio ?? 0) > 0 ? (float)$f->tasa_cambio : 881.07;
                $totalBs = !empty($f->total_bs) ? (float)$f->total_bs : ((float)$f->total * $tasaC);

                if (!empty($q)) {
                    $coincide = (stripos($numFact, $q) !== false) || (stripos($nombreProv, $q) !== false);
                    if (!$coincide) continue;
                }

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
        } elseif ($tipo === 'cliente') {
            $cedula = trim($_GET['cedula'] ?? '');
            $desde  = trim($_GET['desde'] ?? '');
            $hasta  = trim($_GET['hasta'] ?? '');
            $estado = trim($_GET['estado'] ?? '');

            $st = $this->db->prepare("SELECT cedula, nombre_razon_social, telefono, correo, direccion
                                      FROM clientes 
                                      WHERE cedula = ? OR cedula_usuario = ? 
                                      ORDER BY (cedula = ?) DESC 
                                      LIMIT 1");
            $st->execute([$cedula, $cedula, $cedula]);
            $cliente = $st->fetch(PDO::FETCH_OBJ);

            if ($cliente) {
                $cedula = $cliente->cedula;
            } else {
                $cliente = (object)[
                    'cedula'              => $cedula ?: 'SIN_USUARIO',
                    'nombre_razon_social' => ($cedula === 'SIN_USUARIO' || empty($cedula)) ? 'Consumidor Final' : 'Cliente (' . $cedula . ')',
                    'telefono'            => 'S/T',
                    'correo'              => 'S/C',
                    'direccion'           => 'S/D'
                ];
            }

            $sql = "SELECT v.numero_orden, v.fecha, v.origen_venta, v.estado, v.total,
                           COALESCE(SUM(CASE WHEN p.estado = 'aprobado'
                               THEN IF(p.moneda = 'USD', p.monto, p.monto / NULLIF(p.tasa_cambio, 0)) END), 0) AS pagado,
                           COALESCE(SUM(CASE WHEN p.estado IN ('pendiente', 'por_verificar')
                               THEN IF(p.moneda = 'USD', p.monto, p.monto / NULLIF(p.tasa_cambio, 0)) END), 0) AS por_verificar
                    FROM ventas v
                    LEFT JOIN pagos p ON p.numero_orden = v.numero_orden
                    WHERE v.cedula_cliente = ? AND v.deleted_at = '2000-01-01 00:00:00'";
            
            $args = [$cedula];

            if ($desde !== '') { 
                $sql .= " AND v.fecha >= ?"; 
                $args[] = $desde . ' 00:00:00'; 
            }
            if ($hasta !== '') { 
                $sql .= " AND v.fecha <= ?"; 
                $args[] = $hasta . ' 23:59:59'; 
            }

            $sql .= " GROUP BY v.numero_orden";

            if ($estado === 'pagadas') {
                $sql .= " HAVING pagado >= (v.total - 0.005)";
            }
            if ($estado === 'pendientes') {
                $sql .= " HAVING pagado < (v.total - 0.005) AND v.estado NOT IN ('cancelado', 'anulado')";
            }

            $sql .= " ORDER BY v.fecha DESC";

            $st = $this->db->prepare($sql);
            $st->execute($args);
            $compras = $st->fetchAll(PDO::FETCH_OBJ);

            $sd = $this->db->prepare("SELECT dv.cantidad, dv.precio_unitario, dv.subtotal,
                                             p.nombre_producto AS producto_nombre, 
                                             pv.talla, pv.color, pv.modelo
                                      FROM detalle_ventas dv
                                      LEFT JOIN productos p ON p.codigo = dv.codigo_producto
                                      LEFT JOIN producto_variantes pv ON pv.codigo_variante = dv.codigo_variante
                                                                     AND pv.codigo_producto = dv.codigo_producto
                                      WHERE dv.numero_orden = ? 
                                      ORDER BY dv.linea");

            foreach ($compras as $c) { 
                $sd->execute([$c->numero_orden]); 
                $c->productos = $sd->fetchAll(PDO::FETCH_OBJ); 
            }

            return [
                'titulo'  => 'Historial de compras del cliente',
                'cliente' => $cliente,
                'datos'   => $compras
            ];
        } elseif ($tipo === 'cierre_caja' || $tipo === 'cierre') {
            $cedula = trim((string)($_GET['cajero'] ?? $_GET['cedula'] ?? ''));
            $turno  = (int)($_GET['turno'] ?? 0);
            $fecha  = trim((string)($_GET['fecha'] ?? ''));
            $desde  = trim((string)($_GET['desde'] ?? ''));
            $hasta  = trim((string)($_GET['hasta'] ?? ''));

            // Caso A: Arqueo individual de un turno concreto
            if (!empty($cedula) && $turno > 0) {
                require_once "models/TurnoCajaModel.php";
                $mTurno = new TurnoCajaModel($this->db);

                $resumen  = $mTurno->obtenerResumenTurno($cedula, $turno);
                $turnoObj = $resumen['turno'] ?? null;
                $desglose = $resumen['desglose'] ?? [];

                $nombreCajero = 'Cajero';
                if ($turnoObj && !empty($turnoObj->cedula_cajero)) {
                    $stCaj = $this->db->prepare("SELECT CONCAT(nombre, ' ', apellido) FROM usuarios WHERE cedula = ? LIMIT 1");
                    $stCaj->execute([$turnoObj->cedula_cajero]);
                    $nombreCajero = trim((string)$stCaj->fetchColumn()) ?: 'Cajero';
                }

                $datos = [];
                foreach ($desglose as $d) {
                    $fila = new stdClass();
                    $fila->metodo_nombre          = $d->metodo_nombre ?? $d->codigo_metodo;
                    $fila->cantidad_transacciones = (int)$d->cantidad_transacciones;
                    $fila->total_monto            = (float)$d->total_monto;
                    $fila->moneda                 = $d->moneda;
                    $fila->total_usd              = (float)$d->total_usd;
                    $fila->total_bs               = (float)$d->total_bs;
                    $datos[] = $fila;
                }

                return [
                    'titulo' => 'COMPROBANTE DE CIERRE Y ARQUEO DE CAJA',
                    'cajero' => $nombreCajero,
                    'turno'  => $turnoObj,
                    'datos'  => $datos
                ];
            }

            // Caso B: Cierre de un día completo (tipo=cierre&fecha=...)
            if (!empty($fecha)) {
                $stmtTurnos = $this->db->prepare("
                    SELECT tc.cedula_cajero,
                           tc.numero_turno,
                           CONCAT(COALESCE(u.nombre, ''), ' ', COALESCE(u.apellido, '')) AS cajero,
                           DATE_FORMAT(tc.fecha_apertura, '%Y-%m-%d %H:%i:%s') AS fecha_apertura,
                           IF(tc.fecha_cierre IS NOT NULL, DATE_FORMAT(tc.fecha_cierre, '%Y-%m-%d %H:%i:%s'), NULL) AS fecha_cierre,
                           tc.estado,
                           COALESCE(tc.fondo_usd, 0) AS fondo_usd,
                           COALESCE(tc.fondo_bs, 0) AS fondo_bs,
                           IF(tc.estado = 'cerrado', COALESCE(tc.cierre_usd, 0), 0) AS cierre_usd,
                           IF(tc.estado = 'cerrado', COALESCE(tc.cierre_bs, 0), 0) AS cierre_bs
                    FROM turnos_caja tc
                    LEFT JOIN usuarios u ON tc.cedula_cajero = u.cedula
                    WHERE DATE(tc.fecha_apertura) = ?
                    ORDER BY tc.fecha_apertura ASC
                ");
                $stmtTurnos->execute([$fecha]);
                $listaTurnos = $stmtTurnos->fetchAll(PDO::FETCH_ASSOC);

                $stmtPagos = $this->db->prepare("
                    SELECT v.numero_orden,
                           DATE_FORMAT(p.fecha_pago, '%Y-%m-%d %H:%i:%s') AS fecha_pago,
                           COALESCE(c.nombre_razon_social, u.nombre, 'Consumidor Final') AS cliente,
                           p.codigo_metodo,
                           COALESCE(mp.nombre, p.codigo_metodo) AS metodo_nombre,
                           p.moneda,
                           p.monto,
                           p.tasa_cambio,
                           COALESCE(p.banco, '') AS banco,
                           COALESCE(p.referencia, '') AS referencia,
                           v.estado AS estado_venta
                    FROM pagos p
                    INNER JOIN ventas v ON p.numero_orden = v.numero_orden
                    LEFT JOIN metodos_pago mp ON p.codigo_metodo = mp.codigo_metodo
                    LEFT JOIN clientes c ON v.cedula_cliente = c.cedula
                    LEFT JOIN usuarios u ON v.cedula_usuario = u.cedula
                    WHERE DATE(p.fecha_pago) = ?
                      AND v.origen_venta IN ('tienda', 'tienda_fisica')
                    ORDER BY p.fecha_pago ASC
                ");
                $stmtPagos->execute([$fecha]);
                $listaPagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

                return [
                    'titulo' => 'REPORTE DE CIERRE DIARIO (' . date('d/m/Y', strtotime($fecha)) . ')',
                    'fecha'  => $fecha,
                    'turnos' => $listaTurnos,
                    'datos'  => $listaPagos
                ];
            }

            // Caso C: Cierres de caja por período (desde/hasta)
            $where = ["tc.estado = 'cerrado'"];
            $params = [];

            if (!empty($desde)) {
                $where[] = "DATE(tc.fecha_apertura) >= ?";
                $params[] = $desde;
            }
            if (!empty($hasta)) {
                $where[] = "DATE(tc.fecha_apertura) <= ?";
                $params[] = $hasta;
            }

            $sql = "SELECT tc.cedula_cajero, 
                           tc.numero_turno, 
                           tc.fecha_apertura, 
                           tc.fecha_cierre, 
                           tc.fondo_usd, 
                           tc.fondo_bs, 
                           tc.cierre_usd, 
                           tc.cierre_bs, 
                           tc.tasa_apertura, 
                           tc.nota_cierre,
                           CONCAT(COALESCE(u.nombre, ''), ' ', COALESCE(u.apellido, '')) AS cajero
                    FROM turnos_caja tc
                    LEFT JOIN usuarios u ON tc.cedula_cajero = u.cedula
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY tc.fecha_apertura DESC, tc.numero_turno DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $turnos = $stmt->fetchAll(PDO::FETCH_OBJ);

            $titulo = 'REPORTE DE CIERRES DE CAJA POR PERÍODO';
            if ($desde && $hasta) {
                $titulo .= " (" . date('d/m/Y', strtotime($desde)) . " AL " . date('d/m/Y', strtotime($hasta)) . ")";
            }

            return [
                'titulo' => $titulo,
                'datos'  => $turnos
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