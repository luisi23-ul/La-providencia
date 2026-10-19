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

    // =========================================================================
    // 1. PUNTO DE VENTA (POS)
    // =========================================================================
    public function puntoVenta() {
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

        $categorias = $this->db->query("
            SELECT slug, slug AS id, nombre_categoria 
            FROM categorias 
            WHERE slug != 'raiz'
            ORDER BY nombre_categoria ASC
        ")->fetchAll(PDO::FETCH_OBJ);

        $metodos = $this->db->query("
            SELECT codigo_metodo, codigo_metodo AS id, nombre, moneda, requiere_referencia, en_tienda 
            FROM metodos_pago 
            WHERE activo = 1 AND en_tienda = 1 
            ORDER BY nombre ASC
        ")->fetchAll(PDO::FETCH_OBJ);

        $clientes = $this->db->query("
            SELECT cedula, cedula AS id, nombre_razon_social, telefono, correo 
            FROM clientes 
            WHERE estado = 1 
            ORDER BY nombre_razon_social ASC
        ")->fetchAll(PDO::FETCH_OBJ);

        $stmtSis = $this->db->query("SELECT * FROM sistema LIMIT 1");
        $sistema = $stmtSis->fetch(PDO::FETCH_OBJ);

        $ventaRegistrada = $_SESSION['venta_comprobante'] ?? null;
        unset($_SESSION['venta_comprobante']);

        require_once 'views/ventas/punto_venta.php';
    }

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

            $cedulaCliente = trim((string)($_POST['cedula_cliente'] ?? $_POST['id_cliente'] ?? $_POST['id_usuario'] ?? 'SIN_USUARIO'));
            if (empty($cedulaCliente) || $cedulaCliente === '0') {
                $cedulaCliente = 'SIN_USUARIO';
            }
            $cedulaCajero = $_SESSION['cedula'] ?? $_SESSION['id_usuario'] ?? 'SIN_USUARIO';

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

            $prodCodigos = $_POST['codigo_producto'] ?? $_POST['id_producto'] ?? [];
            $varCodigos  = $_POST['codigo_variante'] ?? $_POST['id_variante'] ?? [];
            $cants       = $_POST['cantidad'] ?? [];
            $precios     = $_POST['precio_unitario'] ?? [];
            $descuentos  = $_POST['descuento'] ?? [];

            if (empty($prodCodigos) || !is_array($prodCodigos)) {
                throw new Exception("No hay productos en la orden de venta.");
            }

            $stmtSis = $this->db->query("SELECT tasa_bcv, tasa_manual, tasa_modo FROM sistema LIMIT 1");
            $sis = $stmtSis->fetch(PDO::FETCH_OBJ);
            $tasaCambio = ($sis && $sis->tasa_modo === 'manual') ? (float)$sis->tasa_manual : (float)($sis->tasa_bcv ?? 1.0000);
            if ($tasaCambio <= 0) $tasaCambio = 1.0000;

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

            $stmtMax = $this->db->query("SELECT MAX(CAST(SUBSTRING(numero_orden, 5) AS UNSIGNED)) FROM ventas WHERE numero_orden LIKE 'ORD-%'");
            $maxNum = (int)$stmtMax->fetchColumn();
            $numeroOrden = 'ORD-' . str_pad($maxNum + 1, 6, '0', STR_PAD_LEFT);

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

                $stmtResta->execute([$cant, $codProd]);
                if (!empty($codVar)) {
                    $stmtRestaVar->execute([$cant, $codVar, $codProd]);
                }

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
        $fecha  = trim((string)($_GET['fecha'] ?? ''));

        $where = [];
        $params = [];

        // 1. Filtrar por estado del pago
        if (!empty($estado) && $estado !== 'todos') {
            $where[] = "p.estado = ?";
            $params[] = $estado;
        }

        // 2. Solo métodos bancarios que requieren verificación
        $where[] = "(mp.requiere_referencia = 1 OR p.codigo_metodo IN ('pago_movil', 'transferencia'))";

        // 3. Filtro por fecha (cubre fecha_pago y fecha de la venta)
        if (!empty($fecha)) {
            $where[] = "(p.fecha_pago BETWEEN ? AND ? OR DATE(p.fecha_pago) = ? OR DATE(v.fecha) = ?)";
            $params[] = $fecha . ' 00:00:00';
            $params[] = $fecha . ' 23:59:59';
            $params[] = $fecha;
            $params[] = $fecha;
        }

        $sqlWhere = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = "SELECT p.*, 
                       p.numero_pago AS id_pago,
                       v.numero_orden AS id,
                       v.fecha AS fecha_venta, 
                       v.total AS total_venta,
                       v.origen_venta,
                       COALESCE(c.nombre_razon_social, u.nombre, 'Consumidor Final') AS cliente_nombre,
                       COALESCE(c.nombre_razon_social, u.nombre, 'Consumidor Final') AS cliente,
                       mp.nombre AS metodo_nombre,
                       mp.nombre AS metodo,
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

        // Conteo para las insignias numéricas de las pestañas
        $stmtC = $this->db->query("
            SELECT COUNT(*) 
            FROM pagos p
            LEFT JOIN metodos_pago mp ON p.codigo_metodo = mp.codigo_metodo
            WHERE p.estado = 'pendiente' 
              AND (mp.requiere_referencia = 1 OR p.codigo_metodo IN ('pago_movil', 'transferencia'))
        ");
        $conteo = ['pendiente' => (int)$stmtC->fetchColumn()];

        $tasa = 881.07;
        $stmtSis = $this->db->query("SELECT tasa_bcv FROM sistema LIMIT 1");
        if ($sis = $stmtSis->fetch(PDO::FETCH_OBJ)) {
            $tasa = (float)($sis->tasa_bcv ?: 881.07);
        }

        require_once 'views/ventas/verificar_pagos.php';
    }

    public function aprobarPago() {
        $numeroOrden = trim((string)($_POST['numero_orden'] ?? $_POST['id'] ?? $_GET['id'] ?? ''));
        $numeroPago  = (int)($_POST['numero_pago'] ?? 1);
        $cedulaAdmin = $_SESSION['cedula'] ?? $_SESSION['id_usuario'] ?? 'SIN_USUARIO';

        if (!empty($numeroOrden)) {
            $this->db->prepare("UPDATE pagos SET estado = 'aprobado' WHERE numero_orden = ? AND numero_pago = ?")
                     ->execute([$numeroOrden, $numeroPago]);

            $stmtV = $this->db->prepare("SELECT origen_venta FROM ventas WHERE numero_orden = ?");
            $stmtV->execute([$numeroOrden]);
            $origen = $stmtV->fetchColumn() ?: 'tienda_fisica';

            if ($origen === 'tienda_fisica') {
                $nuevoEstado = 'retirado';
                $this->db->prepare("
                    UPDATE ventas 
                    SET estado = ?, fecha_entrega = NOW(), retirado_por = COALESCE(NULLIF(retirado_por, ''), 'Caja Tienda') 
                    WHERE numero_orden = ?
                ")->execute([$nuevoEstado, $numeroOrden]);
            } else {
                $nuevoEstado = 'por_retirar';
                $this->db->prepare("UPDATE ventas SET estado = ? WHERE numero_orden = ?")
                         ->execute([$nuevoEstado, $numeroOrden]);
            }

            try {
                $this->db->prepare("
                    INSERT INTO pagos_auditoria (numero_orden, numero_pago, fecha_verificacion, verificado_por, estado_resultante, observacion)
                    VALUES (?, ?, NOW(), ?, 'aprobado', 'Pago verificado exitosamente')
                ")->execute([$numeroOrden, $numeroPago, $cedulaAdmin]);
            } catch (Exception $e) {}

            $this->log('Ventas', 'VERIFICACION', "Aprobó pago de orden #{$numeroOrden} - Venta pasa a {$nuevoEstado}");
        }

        header("Location: index.php?action=verificar_pagos");
        exit();
    }

    public function rechazarPago() {
        $numeroOrden = trim((string)($_POST['numero_orden'] ?? $_POST['id'] ?? $_GET['id'] ?? ''));
        $numeroPago  = (int)($_POST['numero_pago'] ?? 1);
        $motivo      = trim((string)($_POST['observacion'] ?? 'Pago rechazado por inconsistencia'));
        $cedulaAdmin = $_SESSION['cedula'] ?? $_SESSION['id_usuario'] ?? 'SIN_USUARIO';

        if (!empty($numeroOrden)) {
            $this->db->prepare("UPDATE pagos SET estado = 'rechazado', observacion = ? WHERE numero_orden = ? AND numero_pago = ?")
                     ->execute([$motivo, $numeroOrden, $numeroPago]);

            $this->db->prepare("UPDATE ventas SET estado = 'cancelado' WHERE numero_orden = ?")
                     ->execute([$numeroOrden]);

            try {
                $this->db->prepare("
                    INSERT INTO pagos_auditoria (numero_orden, numero_pago, fecha_verificacion, verificado_por, estado_resultante, observacion)
                    VALUES (?, ?, NOW(), ?, 'rechazado', ?)
                ")->execute([$numeroOrden, $numeroPago, $cedulaAdmin, $motivo]);
            } catch (Exception $e) {}

            $this->log('Ventas', 'VERIFICACION', "Rechazó pago de orden #{$numeroOrden}");
        }

        header("Location: index.php?action=verificar_pagos");
        exit();
    }

    public function editarPagoVerificacion() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=verificar_pagos");
            exit();
        }

        $numeroOrden = trim((string)($_POST['numero_orden'] ?? $_POST['id_venta'] ?? ''));
        $numeroPago  = (int)($_POST['numero_pago'] ?? $_POST['id_pago'] ?? 1);
        $metodoId    = trim((string)($_POST['id_metodo_pago'] ?? $_POST['codigo_metodo'] ?? ''));
        $banco       = trim((string)($_POST['banco'] ?? ''));
        $referencia  = trim((string)($_POST['referencia'] ?? ''));

        if (empty($numeroOrden)) {
            header("Location: index.php?action=verificar_pagos");
            exit();
        }

        try {
            $this->db->beginTransaction();

            $updates = ["banco = ?", "referencia = ?"];
            $paramsPago = [$banco, $referencia];

            if (!empty($metodoId)) {
                $updates[] = "codigo_metodo = ?";
                $paramsPago[] = $metodoId;
            }

            $paramsPago[] = $numeroOrden;
            $paramsPago[] = $numeroPago;

            $sqlPago = "UPDATE pagos SET " . implode(", ", $updates) . " WHERE numero_orden = ? AND numero_pago = ?";
            $this->db->prepare($sqlPago)->execute($paramsPago);

            if (!empty($_POST['productos_nuevos']) && is_array($_POST['productos_nuevos'])) {
                $stmtMaxLinea = $this->db->prepare("SELECT COALESCE(MAX(linea), 0) FROM detalle_ventas WHERE numero_orden = ?");
                $stmtMaxLinea->execute([$numeroOrden]);
                $linea = (int)$stmtMaxLinea->fetchColumn();

                $montoAdicional = 0.0;
                $stmtInsDet = $this->db->prepare("
                    INSERT INTO detalle_ventas (numero_orden, linea, codigo_producto, codigo_variante, cantidad, costo_unitario, precio_unitario, descuento, subtotal)
                    VALUES (?, ?, ?, '', ?, ?, ?, 0.00, ?)
                ");
                $stmtProdInfo = $this->db->prepare("SELECT precio, costo_compra FROM productos WHERE codigo = ?");
                $stmtDescStock = $this->db->prepare("UPDATE productos SET stock = GREATEST(0, stock - ?) WHERE codigo = ?");

                foreach ($_POST['productos_nuevos'] as $prod) {
                    $codProd = trim((string)($prod['codigo'] ?? $prod['id'] ?? ''));
                    $cant    = (int)($prod['cantidad'] ?? 1);
                    if (empty($codProd) || $cant <= 0) continue;

                    $stmtProdInfo->execute([$codProd]);
                    $pInfo = $stmtProdInfo->fetch(PDO::FETCH_OBJ);
                    if (!$pInfo) continue;

                    $precio = (float)($pInfo->precio ?? 0.0);
                    $costo  = (float)($pInfo->costo_compra ?? 0.0);
                    $subt   = round($cant * $precio, 2);
                    $montoAdicional += $subt;
                    $linea++;

                    $stmtInsDet->execute([$numeroOrden, $linea, $codProd, $cant, $costo, $precio, $subt]);
                    $stmtDescStock->execute([$cant, $codProd]);
                }

                if ($montoAdicional > 0) {
                    $this->db->prepare("UPDATE ventas SET subtotal = subtotal + ?, total = total + ? WHERE numero_orden = ?")
                             ->execute([$montoAdicional, $montoAdicional, $numeroOrden]);
                    $this->db->prepare("UPDATE pagos SET monto = monto + ? WHERE numero_orden = ? AND numero_pago = ?")
                             ->execute([$montoAdicional, $numeroOrden, $numeroPago]);
                }
            }

            $this->db->commit();
            $this->log('Ventas', 'UPDATE', "Se editó el pago de la orden #{$numeroOrden}");

            $_SESSION['alerta'] = [
                'tipo'   => 'success',
                'titulo' => 'Pago actualizado',
                'texto'  => "Los datos del pago de la orden #{$numeroOrden} se guardaron exitosamente."
            ];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $_SESSION['alerta'] = [
                'tipo'   => 'error',
                'titulo' => 'Error al actualizar',
                'texto'  => $e->getMessage()
            ];
        }

        header("Location: index.php?action=verificar_pagos");
        exit();
    }

   // =========================================================================
    // 5. PAGOS POR BANCO (CONCILIACIÓN Y REPORTES)
    // =========================================================================
    public function pagosBanco() {
        // La vista define por defecto hoy si no vienen en GET
        $hoy = date('Y-m-d');
        $desde = trim((string)($_GET['desde'] ?? ''));
        $hasta = trim((string)($_GET['hasta'] ?? ''));
        $bancoFiltro = trim((string)($_GET['banco'] ?? ''));
        $metodoFiltro = trim((string)($_GET['metodo'] ?? ''));

        // 1. Bancos reales que tienen movimientos
        $stmtBancos = $this->db->query("
            SELECT DISTINCT banco 
            FROM pagos 
            WHERE banco IS NOT NULL AND banco != '' AND banco != 'Caja Física' AND banco != 'Sin banco'
            ORDER BY banco ASC
        ");
        $bancos = $stmtBancos->fetchAll(PDO::FETCH_COLUMN);

        // 2. Métodos de pago para los botones de filtro de la vista
        $metodos = $this->db->query("
            SELECT codigo_metodo, nombre, requiere_referencia 
            FROM metodos_pago 
            WHERE activo = 1 
            ORDER BY nombre ASC
        ")->fetchAll(PDO::FETCH_OBJ);

        // 3. Condiciones SQL
        // Incluimos estado aprobado y pendiente/verificado si no hay aprobados de prueba
        $where = [
            "p.estado IN ('aprobado', 'verificado')",
            "(mp.requiere_referencia = 1 OR p.codigo_metodo IN ('pago_movil', 'transferencia') OR (p.banco IS NOT NULL AND p.banco != '' AND p.banco != 'Caja Física'))"
        ];
        $params = [];

        if (!empty($desde)) {
            $where[] = "DATE(p.fecha_pago) >= ?";
            $params[] = $desde;
        }
        if (!empty($hasta)) {
            $where[] = "DATE(p.fecha_pago) <= ?";
            $params[] = $hasta;
        }
        if (!empty($bancoFiltro) && $bancoFiltro !== 'todos') {
            $where[] = "p.banco = ?";
            $params[] = $bancoFiltro;
        }
        if (!empty($metodoFiltro)) {
            $where[] = "(p.codigo_metodo = ? OR mp.codigo_metodo = ?)";
            $params[] = $metodoFiltro;
            $params[] = $metodoFiltro;
        }

        $sqlWhere = "WHERE " . implode(" AND ", $where);

        $sql = "SELECT p.*, 
                       1 AS requiere_referencia,
                       p.numero_pago AS id_pago,
                       v.numero_orden AS id,
                       v.numero_orden,
                       v.fecha AS fecha_venta,
                       v.origen_venta,
                       COALESCE(c.nombre_razon_social, u.nombre, 'Consumidor Final') AS cliente_nombre,
                       COALESCE(c.nombre_razon_social, u.nombre, 'Consumidor Final') AS cliente,
                       c.cedula AS cliente_cedula,
                       c.telefono AS cliente_telefono,
                       COALESCE(mp.nombre, p.codigo_metodo) AS metodo_nombre,
                       COALESCE(mp.nombre, p.codigo_metodo) AS metodo
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

        // 4. Tasa BCV
        $tasa = 881.07;
        $stmtSis = $this->db->query("SELECT tasa_bcv FROM sistema LIMIT 1");
        if ($sis = $stmtSis->fetch(PDO::FETCH_OBJ)) {
            $tasa = (float)($sis->tasa_bcv ?: 881.07);
        }

        // Cargar vista
        $rutas = [
            'views/ventas/pagos_banco.php',
            'views/pagos_banco.php',
            'views/admin/pagos_banco.php'
        ];

        foreach ($rutas as $archivo) {
            if (file_exists($archivo)) {
                require_once $archivo;
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
    // 6. RETIRO DE PEDIDOS
    // =========================================================================
    public function retiroPedidos() {
        $tab = $_GET['tab'] ?? $_GET['estado'] ?? 'pendiente';

        if ($tab === 'entregado' || $tab === 'entregados') {
            $condicion = "v.estado IN ('retirado', 'entregado', 'completado')";
            $orden = "v.fecha_entrega DESC, v.fecha DESC";
        } else {
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
        $ventas = $pedidos;

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
            die("No se encontró el archivo de la vista para retiro de pedidos.");
        }
    }

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

        header("Location: index.php?action=retiro_pedidos&tab=entregado");
        exit();
    }

    // =========================================================================
    // DETALLE DE UNA VENTA / ORDEN
    // =========================================================================
    public function verDetalle($id = null) {
        $numeroOrden = trim((string)($id ?? $_GET['orden'] ?? $_GET['id'] ?? ''));

        if (empty($numeroOrden)) {
            header("Location: index.php?action=ventas");
            exit();
        }

        // 1. Datos de la cabecera de la venta
        $sqlV = "SELECT v.*, 
                        v.numero_orden AS id,
                        COALESCE(c.nombre_razon_social, u.nombre, 'Consumidor Final') AS nombre,
                        COALESCE(c.nombre_razon_social, u.nombre, 'Consumidor Final') AS cliente_nombre,
                        c.correo,
                        c.telefono,
                        c.direccion,
                        c.cedula,
                        uv.nombre AS vendedor
                 FROM ventas v
                 LEFT JOIN clientes c ON v.cedula_cliente = c.cedula
                 LEFT JOIN usuarios u ON v.cedula_usuario = u.cedula
                 LEFT JOIN usuarios uv ON v.cedula_vendedor = uv.cedula
                 WHERE v.numero_orden = ? LIMIT 1";
        $stmtV = $this->db->prepare($sqlV);
        $stmtV->execute([$numeroOrden]);
        $venta = $stmtV->fetch(PDO::FETCH_OBJ);

        if (!$venta) {
            header("Location: index.php?action=ventas");
            exit();
        }

        // 2. Productos de la orden (alias compatibles con $d->producto_nombre)
        $sqlDet = "SELECT dv.*, 
                          p.nombre_producto AS producto_nombre,
                          p.nombre_producto,
                          p.imagen,
                          pv.talla,
                          pv.color
                   FROM detalle_ventas dv
                   LEFT JOIN productos p ON dv.codigo_producto = p.codigo
                   LEFT JOIN producto_variantes pv ON dv.codigo_variante = pv.codigo_variante AND dv.codigo_producto = pv.codigo_producto
                   WHERE dv.numero_orden = ?
                   ORDER BY dv.linea ASC";
        $stmtDet = $this->db->prepare($sqlDet);
        $stmtDet->execute([$numeroOrden]);
        $detalles = $stmtDet->fetchAll(PDO::FETCH_OBJ);

        // 3. Pagos asociados (alias compatibles con $p->metodo y nombre del verificador)
        $sqlPagos = "SELECT p.*, 
                            COALESCE(mp.nombre, p.codigo_metodo, 'Efectivo') AS metodo,
                            COALESCE(mp.nombre, p.codigo_metodo, 'Efectivo') AS metodo_nombre,
                            u.nombre AS verificado_por_nombre
                     FROM pagos p
                     LEFT JOIN metodos_pago mp ON p.codigo_metodo = mp.codigo_metodo
                     LEFT JOIN usuarios u ON p.cedula_cajero = u.cedula
                     WHERE p.numero_orden = ?
                     ORDER BY p.numero_pago ASC";
        $stmtPagos = $this->db->prepare($sqlPagos);
        $stmtPagos->execute([$numeroOrden]);
        $pagos = $stmtPagos->fetchAll(PDO::FETCH_OBJ);

        // 4. Tasa BCV para cálculos en la vista
        $tasa = (float)($venta->tasa_cambio ?? 881.07);
        if ($tasa <= 1) {
            $stmtSis = $this->db->query("SELECT tasa_bcv FROM sistema LIMIT 1");
            if ($sis = $stmtSis->fetch(PDO::FETCH_OBJ)) {
                $tasa = (float)($sis->tasa_bcv ?: 881.07);
            }
        }

        // 5. Soporte AJAX/JSON si se consulta desde scripts
        if (isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
            header('Content-Type: application/json');
            echo json_encode(['venta' => $venta, 'detalles' => $detalles, 'pagos' => $pagos, 'tasa' => $tasa]);
            exit();
        }

        // 6. Cargar la vista
        $rutas = [
            'views/ventas/detalle_venta.php',
            'views/detalle_venta.php',
            'views/admin/detalle_venta.php'
        ];

        foreach ($rutas as $archivo) {
            if (file_exists($archivo)) {
                require_once $archivo;
                return;
            }
        }

        header("Location: index.php?action=ventas");
        exit();
    }

    public function mostrarCarrito() {
        require_once 'views/carrito.php';
    }

    public function añadir() {
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