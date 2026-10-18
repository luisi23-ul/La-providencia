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
    // 1. VISTA DE PUNTO DE VENTA (POS TIENDA)
    // =========================================================================
    public function puntoVenta() {
        // 1. Catálogo de productos activos con alias compatibles para la vista
        $sqlProd = "SELECT p.codigo, 
                           p.codigo AS id, 
                           p.codigo AS codigo_producto,
                           p.slug_categoria,
                           p.slug_categoria AS id_categoria,
                           c.nombre_categoria,
                           p.nombre_producto, 
                           p.descripcion,
                           p.precio, 
                           p.costo_compra, 
                           p.stock, 
                           p.imagen, 
                           p.estado
                    FROM productos p 
                    LEFT JOIN categorias c ON p.slug_categoria = c.slug
                    WHERE p.deleted_at = '2000-01-01 00:00:00' AND p.estado = 1 
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

        // 3. Categorías para la barra de botones de filtrado
        $categorias = $this->db->query("
            SELECT slug, 
                   slug AS id, 
                   nombre_categoria 
            FROM categorias 
            WHERE slug != 'raiz'
            ORDER BY nombre_categoria ASC
        ")->fetchAll(PDO::FETCH_OBJ);

        // 4. Métodos de pago activos en tienda
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

        // 5. Clientes activos
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

        // 6. Configuración general y tasa BCV
        $stmtSis = $this->db->query("SELECT * FROM sistema LIMIT 1");
        $sistema = $stmtSis->fetch(PDO::FETCH_OBJ);

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

            $cedulaCliente  = trim($_POST['cedula_cliente'] ?? $_POST['id_cliente'] ?? 'SIN_USUARIO');
            $cedulaCajero   = $_SESSION['cedula'] ?? $_SESSION['id_usuario'] ?? 'SIN_USUARIO';
            $codigoMetodo   = trim($_POST['codigo_metodo'] ?? $_POST['id_metodo_pago'] ?? 'efectivo_bs');
            $banco          = trim($_POST['banco'] ?? '');
            $referencia     = trim($_POST['referencia'] ?? '');
            $titular        = trim($_POST['titular'] ?? '');
            $telefono       = trim($_POST['telefono'] ?? '');

            $prodCodigos = $_POST['codigo_producto'] ?? $_POST['id_producto'] ?? [];
            $varCodigos  = $_POST['codigo_variante'] ?? $_POST['id_variante'] ?? [];
            $cants       = $_POST['cantidad'] ?? [];
            $precios     = $_POST['precio_unitario'] ?? [];

            if (empty($prodCodigos)) {
                throw new Exception("No hay productos en la orden de venta.");
            }

            // Tasa BCV
            $stmtSis = $this->db->query("SELECT tasa_bcv, tasa_manual, tasa_modo FROM sistema LIMIT 1");
            $sis = $stmtSis->fetch(PDO::FETCH_OBJ);
            $tasaCambio = ($sis && $sis->tasa_modo === 'manual') ? (float)$sis->tasa_manual : (float)($sis->tasa_bcv ?? 1.0000);
            if ($tasaCambio <= 0) $tasaCambio = 1.0000;

            // Totalizar productos
            $totalUsd = 0;
            for ($k = 0; $k < count($prodCodigos); $k++) {
                $totalUsd += ((int)$cants[$k] * (float)$precios[$k]);
            }

            // Generar número de orden formal
            $numeroOrden = 'ORD-' . strtoupper(substr(uniqid(), -6));

            // Guardar Venta (100% NOT NULL)
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
                    16.00, 0.00, ?, ?, 0.00,
                    'Bs', 'retirado', '2000-01-01 00:00:00'
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
                $tasaCambio
            ]);

            // Guardar Detalle de Ventas
            $stmtDet = $this->db->prepare("
                INSERT INTO detalle_ventas (
                    numero_orden, linea, codigo_producto, codigo_variante,
                    cantidad, costo_unitario, precio_unitario, descuento, subtotal
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 0.00, ?)
            ");

            $stmtCosto    = $this->db->prepare("SELECT costo_compra FROM productos WHERE codigo = ?");
            $stmtResta    = $this->db->prepare("UPDATE productos SET stock = GREATEST(0, stock - ?) WHERE codigo = ?");
            $stmtRestaVar = $this->db->prepare("UPDATE producto_variantes SET stock = GREATEST(0, stock - ?) WHERE codigo_variante = ? AND codigo_producto = ?");
            $stmtStk      = $this->db->prepare("SELECT stock FROM productos WHERE codigo = ?");

            $stmtSecKardex = $this->db->prepare("SELECT COALESCE(MAX(numero_movimiento), 0) + 1 FROM kardex_inventario WHERE codigo_producto = ?");
            $stmtKardex    = $this->db->prepare("
                INSERT INTO kardex_inventario 
                (codigo_producto, numero_movimiento, tipo_movimiento, codigo_variante, cantidad, stock_resultante, referencia, observacion, cedula_usuario, fecha_hora)
                VALUES (?, ?, 'venta_tienda', ?, ?, ?, ?, ?, ?, NOW())
            ");

            $linea = 1;
            for ($i = 0; $i < count($prodCodigos); $i++) {
                $codProd = trim($prodCodigos[$i]);
                $codVar  = trim($varCodigos[$i] ?? '');
                $cant    = (int)$cants[$i];
                $pu      = (float)$precios[$i];
                $subt    = round($cant * $pu, 2);

                if ($cant <= 0) continue;

                $stmtCosto->execute([$codProd]);
                $costo = (float)$stmtCosto->fetchColumn() ?: 0.00;

                // Registrar renglón de detalle_ventas
                $stmtDet->execute([
                    $numeroOrden,
                    $linea,
                    $codProd,
                    $codVar,
                    $cant,
                    $costo,
                    $pu,
                    $subt
                ]);

                // Descontar inventario
                $stmtResta->execute([$cant, $codProd]);
                if (!empty($codVar)) {
                    $stmtRestaVar->execute([$cant, $codVar, $codProd]);
                }

                // Kardex
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

                $linea++;
            }

            // Registrar Pago de la Venta en Tienda
            $stmtP = $this->db->prepare("
                INSERT INTO pagos (
                    numero_orden, numero_pago, codigo_metodo, cedula_cajero,
                    banco, moneda, monto, tasa_cambio, referencia,
                    titular_nombre, titular_cedula, titular_telefono,
                    comprobante_img, fecha_pago, estado, observacion
                ) VALUES (
                    ?, 1, ?, ?,
                    ?, 'Bs', ?, ?, ?,
                    ?, ?, ?,
                    '', NOW(), 'aprobado', 'Cobro en caja'
                )
            ");
            $stmtP->execute([
                $numeroOrden,
                $codigoMetodo,
                $cedulaCajero,
                $banco,
                $totalUsd,
                $tasaCambio,
                $referencia,
                $titular,
                $cedulaCliente,
                $telefono
            ]);

            $this->db->commit();
            $this->log('Ventas', 'INSERT', "Venta en caja #{$numeroOrden} por $" . number_format($totalUsd, 2));

            header("Location: index.php?action=detalle_venta&id={$numeroOrden}");
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
        $sql = "SELECT v.*, v.numero_orden AS id,
                       COALESCE(c.nombre_razon_social, u.nombre) AS nombre_cliente,
                       u.nombre AS cajero
                FROM ventas v
                LEFT JOIN clientes c ON v.cedula_cliente = c.cedula
                LEFT JOIN usuarios u ON v.cedula_usuario = u.cedula
                ORDER BY v.fecha DESC";
        $ventas = $this->db->query($sql)->fetchAll(PDO::FETCH_OBJ);

        require_once 'views/ventas/ventas.php';
    }

    public function verDetalle($numeroOrden = null) {
        $numeroOrden = trim($numeroOrden ?? $_GET['id'] ?? '');

        $venta = $this->modelo->obtenerVenta($numeroOrden);
        $detalles = $this->modelo->obtenerDetalles($numeroOrden);

        $stmtPago = $this->db->prepare("SELECT p.*, mp.nombre AS metodo_nombre FROM pagos p LEFT JOIN metodos_pago mp ON p.codigo_metodo = mp.codigo_metodo WHERE p.numero_orden = ? ORDER BY p.numero_pago ASC");
        $stmtPago->execute([$numeroOrden]);
        $pagos = $stmtPago->fetchAll(PDO::FETCH_OBJ);

        require_once 'views/ventas/detalle_venta.php';
    }

    // =========================================================================
    // 4. VERIFICACIÓN Y GESTIÓN DE PAGOS
    // =========================================================================
    public function verificarPagos() {
        $sql = "SELECT p.*, v.fecha AS fecha_venta, v.total AS total_venta,
                       COALESCE(c.nombre_razon_social, u.nombre) AS cliente_nombre,
                       mp.nombre AS metodo_nombre
                FROM pagos p
                INNER JOIN ventas v ON p.numero_orden = v.numero_orden
                LEFT JOIN clientes c ON v.cedula_cliente = c.cedula
                LEFT JOIN usuarios u ON v.cedula_usuario = u.cedula
                LEFT JOIN metodos_pago mp ON p.codigo_metodo = mp.codigo_metodo
                WHERE p.estado = 'pendiente'
                ORDER BY p.fecha_pago ASC";
        $pagosPendientes = $this->db->query($sql)->fetchAll(PDO::FETCH_OBJ);

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
    public function pagosBanco() {
        $sql = "SELECT p.*, v.total AS total_orden, mp.nombre AS metodo_nombre
                FROM pagos p
                INNER JOIN ventas v ON p.numero_orden = v.numero_orden
                LEFT JOIN metodos_pago mp ON p.codigo_metodo = mp.codigo_metodo
                ORDER BY p.fecha_pago DESC";
        $pagos = $this->db->query($sql)->fetchAll(PDO::FETCH_OBJ);

        require_once 'views/ventas/pagos_banco.php';
    }

    public function pagosPendientes() {
        $this->verificarPagos();
    }

    public function retiroPedidos() {
        $sql = "SELECT v.*, v.numero_orden AS id,
                       COALESCE(c.nombre_razon_social, u.nombre) AS cliente_nombre,
                       c.telefono AS cliente_telefono
                FROM ventas v
                LEFT JOIN clientes c ON v.cedula_cliente = c.cedula
                LEFT JOIN usuarios u ON v.cedula_usuario = u.cedula
                WHERE v.estado = 'pagado'
                ORDER BY v.fecha ASC";
        $pedidos = $this->db->query($sql)->fetchAll(PDO::FETCH_OBJ);

        require_once 'views/ventas/retiro_pedidos.php';
    }

    public function procesarRetiro($numeroOrden = null) {
        $numeroOrden = trim($numeroOrden ?? $_POST['numero_orden'] ?? $_GET['id'] ?? '');
        $retiradoPor = trim($_POST['retirado_por'] ?? 'Titular de la cuenta');
        $cedulaAdmin = $_SESSION['cedula'] ?? $_SESSION['id_usuario'] ?? 'SIN_USUARIO';

        if (!empty($numeroOrden)) {
            $this->db->prepare("
                UPDATE ventas 
                SET estado = 'retirado', fecha_entrega = NOW(), retirado_por = ? 
                WHERE numero_orden = ?
            ")->execute([$retiradoPor, $numeroOrden]);

            $this->log('Ventas', 'UPDATE', "Pedido retirado #{$numeroOrden} por: {$retiradoPor}");
        }

        header("Location: index.php?action=retiro_pedidos");
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