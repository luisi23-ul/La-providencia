<?php
class VentasController {
    private $db;
    private $modelo;

    public function __construct($db = null) {
        if ($db) {
            $this->db = $db;
        } else {
            require_once 'config/db.php';
            $this->db = Database::connect();
        }
        require_once 'models/VentaModel.php';
        $this->modelo = new VentaModel($this->db);
    }

    private function log($modulo, $accion, $detalleCambio) {
        try {
            $cedula = $_SESSION['cedula'] ?? $_SESSION['id_usuario'] ?? 'SIN_USUARIO';
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            $stmtSec = $this->db->prepare("SELECT COALESCE(MAX(numero_registro), 0) + 1 FROM auditoria_logs WHERE cedula = ?");
            $stmtSec->execute([$cedula]);
            $numReg = (int)$stmtSec->fetchColumn();

            $stmt = $this->db->prepare("
                INSERT INTO auditoria_logs (cedula, numero_registro, modulo, accion, detalle_cambio, ip_terminal, fecha_hora) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$cedula, $numReg, $modulo, $accion, $detalleCambio, $ip]);
        } catch (Exception $e) {}
    }
public function puntoVenta() {
        // 1. Obtener productos activos (compatibilidad total con borrado lógico y claves)
        $sqlProd = "SELECT p.codigo, 
                           p.codigo AS id, 
                           p.codigo AS codigo_producto,
                           p.slug_categoria,
                           p.slug_categoria AS id_categoria,
                           COALESCE(c.nombre_categoria, 'General') AS nombre_categoria,
                           p.nombre_producto, 
                           p.descripcion,
                           p.precio, 
                           p.costo_compra, 
                           p.stock, 
                           p.stock_minimo,
                           p.imagen, 
                           p.estado
                    FROM productos p 
                    LEFT JOIN categorias c ON p.slug_categoria = c.slug
                    WHERE (p.deleted_at = '2000-01-01 00:00:00' OR p.deleted_at IS NULL OR p.deleted_at = '0000-00-00 00:00:00')
                      AND p.estado = 1 
                    ORDER BY p.nombre_producto ASC";
        $stmtProd = $this->db->query($sqlProd);
        $productos = $stmtProd->fetchAll(PDO::FETCH_OBJ);

        // 2. Variantes por producto
        $stmtVars = $this->db->prepare("
            SELECT pv.codigo_variante, 
                   pv.codigo_variante AS id, 
                   pv.codigo_producto, 
                   pv.codigo_producto AS id_producto,
                   pv.talla, 
                   pv.color, 
                   pv.modelo, 
                   pv.stock, 
                   pv.precio 
            FROM producto_variantes pv 
            WHERE pv.codigo_producto = ? 
            ORDER BY pv.codigo_variante ASC
        ");

        foreach ($productos as $p) {
            $stmtVars->execute([$p->codigo]);
            $p->variantes = $stmtVars->fetchAll(PDO::FETCH_OBJ);
        }

        // 3. Categorías reales para las pestañas de filtro
        $categorias = $this->db->query("
            SELECT slug, 
                   slug AS id, 
                   nombre_categoria 
            FROM categorias 
            WHERE slug != 'raiz'
            ORDER BY nombre_categoria ASC
        ")->fetchAll(PDO::FETCH_OBJ);

        // 4. Métodos de pago activos
        $metodos = $this->db->query("
            SELECT codigo_metodo, 
                   codigo_metodo AS id, 
                   nombre, 
                   moneda, 
                   requiere_referencia, 
                   en_tienda 
            FROM metodos_pago 
            WHERE activo = 1 AND en_tienda = 1 
            ORDER BY nombre ASC
        ")->fetchAll(PDO::FETCH_OBJ);

        // 5. Clientes registrados
        $clientes = $this->db->query("
            SELECT cedula, 
                   cedula AS id, 
                   nombre_razon_social, 
                   telefono, 
                   correo 
            FROM clientes 
            WHERE estado = 1 
            ORDER BY nombre_razon_social ASC
        ")->fetchAll(PDO::FETCH_OBJ);

        // 6. Configuración general (tasa BCV)
        $stmtSis = $this->db->query("SELECT * FROM sistema LIMIT 1");
        $sistema = $stmtSis->fetch(PDO::FETCH_OBJ);

        // 7. Extraer venta recién registrada (si viene de guardarVentaTienda)
        $ventaRegistrada = $_SESSION['venta_comprobante'] ?? null;
        unset($_SESSION['venta_comprobante']);

        require_once 'views/ventas/punto_venta.php';
    }
   // =========================================================================
    // 2. GUARDAR VENTA EN TIENDA (POS)
    // =========================================================================
    // =========================================================================
    // 2. GUARDAR VENTA EN TIENDA (POS)
    // =========================================================================
  // =========================================================================
    // 2. GUARDAR VENTA EN TIENDA (POS)
    // =========================================================================
    public function guardarVentaTienda() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=punto_venta");
            exit();
        }

        try {
            $this->db->beginTransaction();

            // 1. Cliente y Cajero
            $cedulaCliente = trim((string)($_POST['cedula_cliente'] ?? $_POST['id_cliente'] ?? $_POST['id_usuario'] ?? 'SIN_USUARIO'));
            if (empty($cedulaCliente) || $cedulaCliente === '0') {
                $cedulaCliente = 'SIN_USUARIO';
            }
            $cedulaCajero = $_SESSION['cedula'] ?? $_SESSION['id_usuario'] ?? 'SIN_USUARIO';

            // Si es un cliente nuevo registrado desde el modal del POS
            if (!empty($_POST['nuevo_cliente']) && is_array($_POST['nuevo_cliente'])) {
                $ncDoc = trim((string)($_POST['nuevo_cliente']['cedula'] ?? ''));
                $ncNom = trim((string)($_POST['nuevo_cliente']['nombre'] ?? ''));
                $ncTel = trim((string)($_POST['nuevo_cliente']['telefono'] ?? ''));
                $ncDir = trim((string)($_POST['nuevo_cliente']['direccion'] ?? ''));

                if (!empty($ncDoc) && !empty($ncNom)) {
                    $stmtCheckCli = $this->db->prepare("SELECT cedula FROM clientes WHERE cedula = ?");
                    $stmtCheckCli->execute([$ncDoc]);
                    if (!$stmtCheckCli->fetchColumn()) {
                        $stmtInsCli = $this->db->prepare("
                            INSERT INTO clientes (cedula, nombre_razon_social, telefono, direccion, cedula_usuario, estado, created_at)
                            VALUES (?, ?, ?, ?, 'SIN_USUARIO', 1, NOW())
                        ");
                        $stmtInsCli->execute([$ncDoc, $ncNom, $ncTel, $ncDir]);
                    }
                    $cedulaCliente = $ncDoc;
                }
            }

            // 2. Arrays de Productos
            $prodCodigos = $_POST['codigo_producto'] ?? $_POST['id_producto'] ?? [];
            $varCodigos  = $_POST['codigo_variante'] ?? $_POST['id_variante'] ?? [];
            $cants       = $_POST['cantidad'] ?? [];
            $precios     = $_POST['precio_unitario'] ?? [];
            $descuentos  = $_POST['descuento'] ?? [];

            if (empty($prodCodigos) || !is_array($prodCodigos)) {
                throw new Exception("No hay productos en la orden de venta.");
            }

            // 3. Tasa de cambio BCV
            $stmtSis = $this->db->query("SELECT tasa_bcv, tasa_manual, tasa_modo FROM sistema LIMIT 1");
            $sis = $stmtSis->fetch(PDO::FETCH_OBJ);
            $tasaCambio = ($sis && $sis->tasa_modo === 'manual') ? (float)$sis->tasa_manual : (float)($sis->tasa_bcv ?? 1.0000);
            if ($tasaCambio <= 0) $tasaCambio = 1.0000;

            // 4. Totalizar productos
            $totalUsd = (float)($_POST['total'] ?? 0);
            if ($totalUsd <= 0) {
                for ($k = 0; $k < count($prodCodigos); $k++) {
                    $cantItem = (int)($cants[$k] ?? 1);
                    $puItem   = (float)($precios[$k] ?? 0);
                    $descItem = (float)($descuentos[$k] ?? 0);
                    $totalUsd += round($cantItem * $puItem * (1 - ($descItem / 100)), 2);
                }
            }

            $vuelto = (float)($_POST['vuelto'] ?? 0.00);
            $vueltoMoneda = trim((string)($_POST['vuelto_moneda'] ?? 'Bs'));
            if (empty($vueltoMoneda)) $vueltoMoneda = 'Bs';

            // 5. Determinar estado de la venta según métodos de pago (efectivo vs bancario)
            $metodosArray    = (array)($_POST['id_metodo_pago'] ?? $_POST['codigo_metodo'] ?? ['efectivo_usd']);
            $montosArray     = (array)($_POST['monto'] ?? [$totalUsd]);
            $bancosArray     = (array)($_POST['banco'] ?? ['']);
            $referenciasArr  = (array)($_POST['referencia'] ?? ['']);
            $titularesArr    = (array)($_POST['titular_nombre'] ?? ['']);
            $cedulasTitArr   = (array)($_POST['titular_cedula'] ?? ['']);
            $telefonosTitArr = (array)($_POST['titular_telefono'] ?? ['']);

            $stmtReq = $this->db->prepare("SELECT requiere_referencia FROM metodos_pago WHERE codigo_metodo = ? LIMIT 1");
            $requiereVerificacion = false;

            foreach ($metodosArray as $mCod) {
                $stmtReq->execute([trim((string)$mCod)]);
                if ((int)$stmtReq->fetchColumn() === 1) {
                    $requiereVerificacion = true;
                    break;
                }
            }

            $estadoVenta = $requiereVerificacion ? 'por_verificar' : 'retirado';

            // 6. Generar número de orden correlativo (ORD-0000XX)
            $stmtMax = $this->db->query("SELECT MAX(CAST(SUBSTRING(numero_orden, 5) AS UNSIGNED)) FROM ventas WHERE numero_orden LIKE 'ORD-%'");
            $maxNum = (int)$stmtMax->fetchColumn();
            $numeroOrden = 'ORD-' . str_pad($maxNum + 1, 6, '0', STR_PAD_LEFT);

            // 7. Insertar en ventas
            $stmtV = $this->db->prepare("
                INSERT INTO ventas (
                    numero_orden, cedula_cliente, cedula_usuario, cedula_vendedor,
                    origen_venta, numero_factura, numero_control, fecha,
                    fecha_entrega, retirado_por, retirado_cedula, subtotal,
                    porcentaje_iva, iva_monto, total, tasa_cambio, vuelto,
                    vuelto_moneda, estado, deleted_at
                ) VALUES (
                    ?, ?, ?, ?,
                    'tienda_fisica', '', '', NOW(),
                    NOW(), 'Caja Tienda', ?, ?,
                    16.00, 0.00, ?, ?, ?,
                    ?, ?, '2000-01-01 00:00:00'
                )
            ");
            $stmtV->execute([
                $numeroOrden,
                $cedulaCliente,
                $cedulaCajero,
                $cedulaCajero,
                $cedulaCliente,
                $totalUsd,
                $totalUsd,
                $tasaCambio,
                $vuelto,
                $vueltoMoneda,
                $estadoVenta
            ]);

            // 8. Insertar detalle_ventas y descontar inventario
            $stmtDet = $this->db->prepare("
                INSERT INTO detalle_ventas (
                    numero_orden, linea, codigo_producto, codigo_variante,
                    cantidad, costo_unitario, precio_unitario, descuento, subtotal
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmtCosto     = $this->db->prepare("SELECT costo_compra FROM productos WHERE codigo = ?");
            $stmtResta     = $this->db->prepare("UPDATE productos SET stock = GREATEST(0, stock - ?) WHERE codigo = ?");
            $stmtRestaVar  = $this->db->prepare("UPDATE producto_variantes SET stock = GREATEST(0, stock - ?) WHERE codigo_variante = ? AND codigo_producto = ?");
            $stmtStk       = $this->db->prepare("SELECT stock FROM productos WHERE codigo = ?");
            $stmtSecKardex = $this->db->prepare("SELECT COALESCE(MAX(numero_movimiento), 0) + 1 FROM kardex_inventario WHERE codigo_producto = ?");
            $stmtKardex    = $this->db->prepare("
                INSERT INTO kardex_inventario 
                (codigo_producto, numero_movimiento, tipo_movimiento, codigo_variante, cantidad, stock_resultante, referencia, observacion, cedula_usuario, fecha_hora)
                VALUES (?, ?, 'venta_tienda', ?, ?, ?, ?, ?, ?, NOW())
            ");

            $lineasTicket = [];

            for ($i = 0; $i < count($prodCodigos); $i++) {
                $codProd = trim((string)$prodCodigos[$i]);
                $codVar  = trim((string)($varCodigos[$i] ?? ''));
                $cant    = (int)($cants[$i] ?? 1);
                $pu      = (float)($precios[$i] ?? 0.00);
                $desc    = (float)($descuentos[$i] ?? 0.00);
                $subt    = round($cant * $pu * (1 - ($desc / 100)), 2);

                if ($cant <= 0) continue;

                $stmtCosto->execute([$codProd]);
                $costo = (float)$stmtCosto->fetchColumn() ?: 0.00;

                $stmtDet->execute([
                    $numeroOrden,
                    $i + 1,
                    $codProd,
                    $codVar,
                    $cant,
                    $costo,
                    $pu,
                    $desc,
                    $subt
                ]);

                // Descontar inventario
                $stmtResta->execute([$cant, $codProd]);
                if (!empty($codVar)) {
                    $stmtRestaVar->execute([$cant, $codVar, $codProd]);
                }

                // Registrar kardex
                $stmtStk->execute([$codProd]);
                $stockResultante = (int)$stmtStk->fetchColumn();

                $stmtSecKardex->execute([$codProd]);
                $numMov = (int)$stmtSecKardex->fetchColumn();

                $stmtKardex->execute([
                    $codProd,
                    $numMov,
                    $codVar,
                    -$cant,
                    $stockResultante,
                    $numeroOrden,
                    "Venta en caja #{$numeroOrden}",
                    $cedulaCajero
                ]);

                // Nombre para comprobante
                $stmtNom = $this->db->prepare("SELECT nombre_producto FROM productos WHERE codigo = ?");
                $stmtNom->execute([$codProd]);
                $nomProd = $stmtNom->fetchColumn() ?: $codProd;

                $lineasTicket[] = [
                    'nombre'    => $nomProd,
                    'cantidad'  => $cant,
                    'descuento' => $desc,
                    'subtotal'  => $subt
                ];
            }

            // 9. Insertar Pagos
            $stmtP = $this->db->prepare("
                INSERT INTO pagos (
                    numero_orden, numero_pago, codigo_metodo, cedula_cajero,
                    banco, moneda, monto, tasa_cambio, referencia,
                    titular_nombre, titular_cedula, titular_telefono,
                    comprobante_img, fecha_pago, estado, observacion
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    '', NOW(), ?, 'Cobro en caja'
                )
            ");

            $pagosTicket = [];

            for ($pIdx = 0; $pIdx < count($metodosArray); $pIdx++) {
                $metodoId = trim((string)($metodosArray[$pIdx] ?? 'efectivo_usd'));
                $montoP   = (float)($montosArray[$pIdx] ?? 0.00);
                $bancoP   = trim((string)($bancosArray[$pIdx] ?? ''));
                $refP     = trim((string)($referenciasArr[$pIdx] ?? ''));
                $titP     = trim((string)($titularesArr[$pIdx] ?? ''));
                $titCedP  = trim((string)($cedulasTitArr[$pIdx] ?? ''));
                $titTelP  = trim((string)($telefonosTitArr[$pIdx] ?? ''));

                if ($montoP <= 0 && count($metodosArray) > 1) continue;

                // Definir estado individual del pago según el método
                $stmtReq->execute([$metodoId]);
                $esBancario = (int)$stmtReq->fetchColumn() === 1;
                $estadoPago = $esBancario ? 'pendiente' : 'aprobado';

                $monedaP = (stripos($metodoId, 'usd') !== false) ? 'USD' : 'Bs';

                $stmtP->execute([
                    $numeroOrden,
                    $pIdx + 1,
                    $metodoId,
                    $cedulaCajero,
                    $bancoP,
                    $monedaP,
                    $montoP,
                    $tasaCambio,
                    $refP,
                    $titP,
                    $titCedP,
                    $titTelP,
                    $estadoPago
                ]);

                $stmtMP = $this->db->prepare("SELECT nombre FROM metodos_pago WHERE codigo_metodo = ?");
                $stmtMP->execute([$metodoId]);
                $nomMetodo = $stmtMP->fetchColumn() ?: $metodoId;

                $pagosTicket[] = [
                    'metodo'     => $nomMetodo,
                    'moneda'     => $monedaP,
                    'monto'      => $montoP,
                    'referencia' => $refP
                ];
            }

            $this->db->commit();
            $this->log('Ventas', 'INSERT', "Venta en caja #{$numeroOrden} por $" . number_format($totalUsd, 2));

            // 10. Datos de cliente para modal y sesión de ticket
            $stmtCliData = $this->db->prepare("SELECT nombre_razon_social, telefono FROM clientes WHERE cedula = ?");
            $stmtCliData->execute([$cedulaCliente]);
            $cliData = $stmtCliData->fetch(PDO::FETCH_OBJ);

            $_SESSION['venta_comprobante'] = [
                'id'            => $numeroOrden,
                'fecha'         => date('d/m/Y, g:i a'),
                'cliente'       => ($cedulaCliente === 'SIN_USUARIO') ? 'Consumidor final' : ($cliData->nombre_razon_social ?? $cedulaCliente),
                'documento'     => ($cedulaCliente === 'SIN_USUARIO') ? '' : $cedulaCliente,
                'telefono'      => $cliData->telefono ?? '',
                'total'         => $totalUsd,
                'vuelto'        => $vuelto,
                'vuelto_moneda' => $vueltoMoneda,
                'lineas'        => $lineasTicket,
                'pagos'         => $pagosTicket
            ];

            header("Location: index.php?action=punto_venta");
            exit();

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
            exit();
        }
    }
    // =========================================================================
    // 3. HISTORIAL DE VENTAS
    // =========================================================================
    public function listarVentas() {
        $sql = "SELECT v.*, 
                       v.numero_orden AS id,
                       COALESCE(c.nombre_razon_social, u.nombre, 'Consumidor Final') AS nombre_cliente,
                       u.nombre AS cajero,
                       COALESCE(
                           (SELECT GROUP_CONCAT(DISTINCT mp.nombre SEPARATOR ', ')
                            FROM pagos p 
                            LEFT JOIN metodos_pago mp ON p.codigo_metodo = mp.codigo_metodo 
                            WHERE p.numero_orden = v.numero_orden),
                           'Efectivo'
                       ) AS metodo_nombre
                FROM ventas v
                LEFT JOIN clientes c ON v.cedula_cliente = c.cedula
                LEFT JOIN usuarios u ON v.cedula_usuario = u.cedula
                ORDER BY v.fecha DESC";
        $ventas = $this->db->query($sql)->fetchAll(PDO::FETCH_OBJ);

        require_once 'views/ventas/ventas.php';
    }
    // =========================================================================
    // 4. VERIFICACIÓN Y GESTIÓN DE PAGOS
    // =========================================================================
    public function verificarPagos() {
        $estado = $_GET['estado'] ?? 'pendiente';
        $fecha  = $_GET['fecha'] ?? '';

        $where = [];
        $params = [];

        // Filtro por estado (pendiente, aprobado, rechazado)
        if (!empty($estado) && $estado !== 'todos') {
            $where[] = "p.estado = ?";
            $params[] = $estado;
        }

        // Filtro por fecha opcional (solo si el usuario la seleccionó expresamente)
        if (!empty($fecha)) {
            $where[] = "DATE(p.fecha_pago) = ?";
            $params[] = $fecha;
        }

        $sqlWhere = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = "SELECT p.*, 
                       v.fecha AS fecha_venta, 
                       v.total AS total_venta,
                       v.origen_venta,
                       COALESCE(c.nombre_razon_social, u.nombre, 'Consumidor Final') AS cliente_nombre,
                       mp.nombre AS metodo_nombre,
                       mp.requiere_referencia
                FROM pagos p
                INNER JOIN ventas v ON p.numero_orden = v.numero_orden
                LEFT JOIN clientes c ON v.cedula_cliente = c.cedula
                LEFT JOIN usuarios u ON v.cedula_usuario = u.cedula
                LEFT JOIN metodos_pago mp ON p.codigo_metodo = mp.codigo_metodo
                $sqlWhere
                ORDER BY p.fecha_pago DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $pagos = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Conteo real para las insignias numéricas de las pestañas
        $stmtC = $this->db->query("SELECT COUNT(*) FROM pagos WHERE estado = 'pendiente'");
        $conteo = ['pendiente' => (int)$stmtC->fetchColumn()];

        $tasa = 880.12;
        $stmtSis = $this->db->query("SELECT tasa_bcv FROM sistema LIMIT 1");
        if ($sis = $stmtSis->fetch(PDO::FETCH_OBJ)) {
            $tasa = (float)($sis->tasa_bcv ?: 880.12);
        }

        require_once 'views/ventas/verificar_pagos.php';
    }

    public function aprobarPago() {
        $numeroOrden = trim($_POST['numero_orden'] ?? $_GET['id'] ?? '');
        $numeroPago  = (int)($_POST['numero_pago'] ?? 1);
        $cedulaAdmin = $_SESSION['cedula'] ?? $_SESSION['id_usuario'] ?? 'SIN_USUARIO';

        if (!empty($numeroOrden)) {
            $this->db->prepare("UPDATE pagos SET estado = 'aprobado' WHERE numero_orden = ? AND numero_pago = ?")
                     ->execute([$numeroOrden, $numeroPago]);

            $this->db->prepare("UPDATE ventas SET estado = 'pagado' WHERE numero_orden = ?")
                     ->execute([$numeroOrden]);

            // Auditoría del pago
            $this->db->prepare("
                INSERT INTO pagos_auditoria (numero_orden, numero_pago, fecha_verificacion, verificado_por, estado_resultante, observacion)
                VALUES (?, ?, NOW(), ?, 'aprobado', 'Pago verificado exitosamente')
            ")->execute([$numeroOrden, $numeroPago, $cedulaAdmin]);

            $this->log('Ventas', 'VERIFICACION', "Aprobó pago de orden #{$numeroOrden}");
        }

        header("Location: index.php?action=verificar_pagos");
        exit();
    }

    public function rechazarPago() {
        $numeroOrden = trim($_POST['numero_orden'] ?? $_GET['id'] ?? '');
        $numeroPago  = (int)($_POST['numero_pago'] ?? 1);
        $cedulaAdmin = $_SESSION['cedula'] ?? $_SESSION['id_usuario'] ?? 'SIN_USUARIO';

        if (!empty($numeroOrden)) {
            $this->db->prepare("UPDATE pagos SET estado = 'rechazado' WHERE numero_orden = ? AND numero_pago = ?")
                     ->execute([$numeroOrden, $numeroPago]);

            $this->db->prepare("UPDATE ventas SET estado = 'anulado' WHERE numero_orden = ?")
                     ->execute([$numeroOrden]);

            $this->db->prepare("
                INSERT INTO pagos_auditoria (numero_orden, numero_pago, fecha_verificacion, verificado_por, estado_resultante, observacion)
                VALUES (?, ?, NOW(), ?, 'rechazado', 'Pago rechazado por inconsistencia')
            ")->execute([$numeroOrden, $numeroPago, $cedulaAdmin]);

            $this->log('Ventas', 'VERIFICACION', "Rechazó pago de orden #{$numeroOrden}");
        }

        header("Location: index.php?action=verificar_pagos");
        exit();
    }

    // =========================================================================
    // 5. MÉTODOS DE SOPORTE Y TIENDA ONLINE
    // =========================================================================
    // =========================================================================
    // 6. PAGOS POR BANCO (CONCILIACIÓN Y REPORTES)
    // =========================================================================
    public function pagosBanco() {
        $desde = $_GET['desde'] ?? '';
        $hasta = $_GET['hasta'] ?? '';
        $bancoFiltro = trim($_GET['banco'] ?? '');

        // Obtener la lista de bancos que tienen movimientos
        $stmtBancos = $this->db->query("
            SELECT DISTINCT banco 
            FROM pagos 
            WHERE banco IS NOT NULL AND banco != '' AND banco != 'Caja Física'
            ORDER BY banco ASC
        ");
        $bancos = $stmtBancos->fetchAll(PDO::FETCH_COLUMN);

        $where = ["p.estado = 'aprobado'", "(mp.requiere_referencia = 1 OR p.codigo_metodo IN ('pago_movil', 'transferencia'))"];
        $params = [];

        // Filtro por fecha usando fecha_pago o fecha_verificacion
        if (!empty($desde)) {
            $where[] = "DATE(COALESCE(p.fecha_verificacion, p.fecha_pago)) >= ?";
            $params[] = $desde;
        }
        if (!empty($hasta)) {
            $where[] = "DATE(COALESCE(p.fecha_verificacion, p.fecha_pago)) <= ?";
            $params[] = $hasta;
        }
        if (!empty($bancoFiltro) && $bancoFiltro !== 'todos') {
            $where[] = "p.banco = ?";
            $params[] = $bancoFiltro;
        }

        $sqlWhere = "WHERE " . implode(" AND ", $where);

        $sql = "SELECT p.*, 
                       v.numero_orden AS id,
                       v.fecha AS fecha_venta,
                       COALESCE(c.nombre_razon_social, u.nombre, 'Consumidor Final') AS cliente_nombre,
                       mp.nombre AS metodo_nombre
                FROM pagos p
                INNER JOIN ventas v ON p.numero_orden = v.numero_orden
                LEFT JOIN clientes c ON v.cedula_cliente = c.cedula
                LEFT JOIN usuarios u ON v.cedula_usuario = u.cedula
                LEFT JOIN metodos_pago mp ON p.codigo_metodo = mp.codigo_metodo
                $sqlWhere
                ORDER BY COALESCE(p.fecha_verificacion, p.fecha_pago) DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $pagos = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Agrupación de totales por banco para las tarjetas/resumen
        $totalesPorBanco = [];
        foreach ($pagos as $pg) {
            $b = !empty($pg->banco) ? $pg->banco : 'Otros bancos';
            if (!isset($totalesPorBanco[$b])) {
                $totalesPorBanco[$b] = ['bs' => 0.0, 'usd' => 0.0, 'cuenta' => 0];
            }
            if (($pg->moneda ?? 'Bs') === 'USD') {
                $totalesPorBanco[$b]['usd'] += (float)$pg->monto;
            } else {
                $totalesPorBanco[$b]['bs'] += (float)$pg->monto;
            }
            $totalesPorBanco[$b]['cuenta']++;
        }

        // Cargar la vista correspondiente
        $rutasPosibles = [
            'views/ventas/pagos_banco.php',
            'views/pagos_banco.php',
            'views/admin/pagos_banco.php'
        ];

        foreach ($rutasPosibles as $ruta) {
            if (file_exists($ruta)) {
                require_once $ruta;
                return;
            }
        }

        header("Location: index.php?action=ventas");
        exit();
    }
    public function pagosPendientes() {
        $this->verificarPagos();
    }

    // =========================================================================
    // 4. RETIRO DE PEDIDOS (ENTREGA EN TIENDA)
    // =========================================================================
   public function retiroPedidos() {
        // Leer el parámetro tab de la vista ('pendiente' o 'entregado')
        $tab = $_GET['tab'] ?? $_GET['estado'] ?? 'pendiente';

        if ($tab === 'entregado' || $tab === 'entregados') {
            // Pedidos que ya fueron entregados / retirados
            $condicion = "v.estado IN ('retirado', 'entregado', 'completado')";
            $orden = "v.fecha_entrega DESC, v.fecha DESC";
        } else {
            // Pedidos listos para entregar en tienda (pagados o por retirar)
            $condicion = "v.estado IN ('pagado', 'por_retirar', 'completado', 'retirado')";
            $orden = "v.fecha ASC";
        }

        $sql = "SELECT v.*, 
                       v.numero_orden AS id,
                       COALESCE(c.nombre_razon_social, u.nombre, 'Consumidor Final') AS cliente_nombre,
                       COALESCE(c.nombre_razon_social, u.nombre, 'Consumidor Final') AS cliente,
                       c.telefono AS cliente_telefono,
                       c.cedula AS cliente_cedula,
                       COALESCE(
                           (SELECT GROUP_CONCAT(DISTINCT mp.nombre SEPARATOR ', ')
                            FROM pagos p 
                            LEFT JOIN metodos_pago mp ON p.codigo_metodo = mp.codigo_metodo 
                            WHERE p.numero_orden = v.numero_orden),
                           'Efectivo'
                       ) AS metodo_pago,
                       COALESCE(
                           (SELECT GROUP_CONCAT(DISTINCT mp.nombre SEPARATOR ', ')
                            FROM pagos p 
                            LEFT JOIN metodos_pago mp ON p.codigo_metodo = mp.codigo_metodo 
                            WHERE p.numero_orden = v.numero_orden),
                           'Efectivo'
                       ) AS metodo,
                       (SELECT COUNT(*) FROM detalle_ventas dv WHERE dv.numero_orden = v.numero_orden) AS total_items
                FROM ventas v
                LEFT JOIN clientes c ON v.cedula_cliente = c.cedula
                LEFT JOIN usuarios u ON v.cedula_usuario = u.cedula
                WHERE $condicion
                ORDER BY $orden";

        $pedidos = $this->db->query($sql)->fetchAll(PDO::FETCH_OBJ);
        // Compatibilidad por si la vista itera sobre $ventas en lugar de $pedidos
        $ventas = $pedidos;

        // Conteos para los botones/pestañas
        $conteoPendientes = (int)$this->db->query("SELECT COUNT(*) FROM ventas WHERE estado IN ('pagado', 'por_retirar')")->fetchColumn();
        $conteoEntregados = (int)$this->db->query("SELECT COUNT(*) FROM ventas WHERE estado IN ('retirado', 'entregado')")->fetchColumn();

       $rutasPosibles = [
            'views/ventas/retiro_pedidos.php',
            'views/retiro_pedidos.php',
            'views/admin/retiro_pedidos.php',
            'views/ventas/retiro.php',
            'views/ventas/retiros.php',
            'views/admin/retiros.php',
            'views/retiro.php',
            'views/retiros.php'
        ];

        $cargado = false;
        foreach ($rutasPosibles as $ruta) {
            if (file_exists($ruta)) {
                require_once $ruta;
                $cargado = true;
                break;
            }
        }

        if (!$cargado) {
            die("No se encontró el archivo de la vista para retiro de pedidos. Revisa en tu carpeta views cómo se llama.");
        }
    }
    // =========================================================================
    // 5. PROCESAR RETIRO / ENTREGA
    // =========================================================================
    public function procesarRetiro($numeroOrden = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && empty($_GET['id'])) {
            header("Location: index.php?action=retiro_pedidos");
            exit();
        }

        $numeroOrden   = trim((string)($numeroOrden ?? $_POST['numero_orden'] ?? $_POST['id'] ?? $_GET['id'] ?? ''));
        $retiradoPor   = trim((string)($_POST['retirado_por'] ?? 'Titular de la cuenta'));
        $retiradoCed   = trim((string)($_POST['retirado_cedula'] ?? ''));
        $cedulaAdmin   = $_SESSION['cedula'] ?? $_SESSION['id_usuario'] ?? 'SIN_USUARIO';

        if (!empty($numeroOrden)) {
            $stmt = $this->db->prepare("
                UPDATE ventas 
                SET estado = 'retirado', 
                    fecha_entrega = NOW(), 
                    retirado_por = ?,
                    retirado_cedula = ?
                WHERE numero_orden = ?
            ");
            $stmt->execute([$retiradoPor, $retiradoCed, $numeroOrden]);

            $this->log('Ventas', 'UPDATE', "Pedido retirado #{$numeroOrden} entregado a: {$retiradoPor}");

            $_SESSION['alerta'] = [
                'tipo'   => 'success',
                'titulo' => '¡Entrega registrada!',
                'texto'  => "El pedido #{$numeroOrden} se marcó como retirado con éxito."
            ];
        }

        header("Location: index.php?action=retiro_pedidos&estado=entregados");
        exit();
    }
    public function mostrarCarrito() {
        require_once 'views/carrito.php';
    }

    public function añadir() {
        // Redirige al catálogo si se llama por get
        header("Location: index.php?action=ver_catalogo");
        exit();
    }

    public function eliminarItem() {
        header("Location: index.php?action=ver_carrito");
        exit();
    }

    public function finalizarCompra() {
        header("Location: index.php?action=ver_carrito");
        exit();
    }
}