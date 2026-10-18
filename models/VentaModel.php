<?php
require_once 'config/db.php';

class VentaModel {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: Database::connect();
    }

    public function guardarVentaModel($cedula_usuario, $total, $carrito, $codigo_metodo = 'efectivo_usd') {
        try {
            $this->db->beginTransaction();

            // Generar correlativo para numero_orden (ej: ORD-1791823)
            $numeroOrden = 'ORD-' . strtoupper(substr(uniqid(), -6));

            $cedulaCliente = 'SIN_USUARIO';
            if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'cliente') {
                $cedulaCliente = $cedula_usuario;
            }

            // Inserción en ventas (100% NOT NULL)
            $sqlVenta = "INSERT INTO ventas (
                            numero_orden, cedula_cliente, cedula_usuario, cedula_vendedor, 
                            origen_venta, numero_factura, numero_control, fecha, 
                            fecha_entrega, retirado_por, retirado_cedula, subtotal, 
                            porcentaje_iva, iva_monto, total, tasa_cambio, vuelto, 
                            vuelto_moneda, estado, deleted_at
                        ) VALUES (
                            ?, ?, ?, 'SIN_USUARIO', 
                            'tienda', '', '', NOW(), 
                            '2000-01-01 00:00:00', '', '', ?, 
                            16.00, 0.00, ?, 1.0000, 0.00, 
                            'Bs', 'pendiente', '2000-01-01 00:00:00'
                        )";
            $stmt = $this->db->prepare($sqlVenta);
            $stmt->execute([
                $numeroOrden,
                $cedulaCliente,
                $cedula_usuario,
                $total,
                $total
            ]);

            // Inserción en detalle_ventas con PK Compuesta (numero_orden, linea)
            $sqlDetalle = "INSERT INTO detalle_ventas (
                                numero_orden, linea, codigo_producto, codigo_variante, 
                                cantidad, costo_unitario, precio_unitario, descuento, subtotal
                           ) VALUES (?, ?, ?, ?, ?, ?, ?, 0.00, ?)";
            $stmtDetalle = $this->db->prepare($sqlDetalle);

            $stmtCosto = $this->db->prepare("SELECT costo_compra FROM productos WHERE codigo = ?");

            $linea = 1;
            foreach ($carrito as $item) {
                $codigoProd = $item['codigo_producto'] ?? $item['id_producto'] ?? $item['codigo'] ?? '';
                $codigoVar  = $item['codigo_variante'] ?? '';
                $cantidad   = (int)($item['cantidad'] ?? 1);
                $precio     = (float)($item['precio'] ?? 0.00);
                $subtotal   = round($precio * $cantidad, 2);

                $stmtCosto->execute([$codigoProd]);
                $costo = (float)$stmtCosto->fetchColumn() ?: 0.00;

                $stmtDetalle->execute([
                    $numeroOrden,
                    $linea,
                    $codigoProd,
                    $codigoVar,
                    $cantidad,
                    $costo,
                    $precio,
                    $subtotal
                ]);

                $linea++;
            }

            // Registrar pago inicial asociado a la venta
            $sqlPago = "INSERT INTO pagos (
                            numero_orden, numero_pago, codigo_metodo, cedula_cajero, 
                            banco, moneda, monto, tasa_cambio, referencia, 
                            titular_nombre, titular_cedula, titular_telefono, 
                            comprobante_img, fecha_pago, estado, observacion
                        ) VALUES (
                            ?, 1, ?, ?, 
                            '', 'USD', ?, 1.0000, '', 
                            '', '', '', 
                            '', NOW(), 'pendiente', 'Generado desde carrito'
                        )";
            $stmtP = $this->db->prepare($sqlPago);
            $stmtP->execute([
                $numeroOrden,
                $codigo_metodo,
                $cedula_usuario,
                $total
            ]);

            $this->db->commit();
            return $numeroOrden;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function obtenerPorEstado($estado) {
        try {
            $sql = "SELECT v.numero_orden, v.numero_orden AS id, 
                           COALESCE(c.nombre_razon_social, u.nombre) AS nombre, 
                           v.fecha, v.total, v.estado 
                    FROM ventas v
                    LEFT JOIN clientes c ON v.cedula_cliente = c.cedula
                    LEFT JOIN usuarios u ON v.cedula_usuario = u.cedula
                    WHERE v.estado = ?
                    ORDER BY v.fecha DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$estado]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function actualizarEstado($numero_orden, $nuevo_estado) {
        $sql = "UPDATE ventas SET estado = ? WHERE numero_orden = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nuevo_estado, $numero_orden]);
    }

    public function descontarStockDeVenta($numero_orden) {
        $sql = "SELECT codigo_producto, codigo_variante, cantidad FROM detalle_ventas WHERE numero_orden = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$numero_orden]);
        $detalles = $stmt->fetchAll(PDO::FETCH_OBJ);

        $stmtUpProd = $this->db->prepare("UPDATE productos SET stock = GREATEST(0, stock - ?) WHERE codigo = ?");
        $stmtUpVar  = $this->db->prepare("UPDATE producto_variantes SET stock = GREATEST(0, stock - ?) WHERE codigo_variante = ? AND codigo_producto = ?");

        foreach ($detalles as $d) {
            $stmtUpProd->execute([$d->cantidad, $d->codigo_producto]);
            if (!empty($d->codigo_variante)) {
                $stmtUpVar->execute([$d->cantidad, $d->codigo_variante, $d->codigo_producto]);
            }
        }
    }

    public function obtenerVenta($numero_orden) {
        $sql = "SELECT v.*, v.numero_orden AS id, 
                       COALESCE(c.nombre_razon_social, u.nombre) AS nombre 
                FROM ventas v 
                LEFT JOIN clientes c ON v.cedula_cliente = c.cedula
                LEFT JOIN usuarios u ON v.cedula_usuario = u.cedula 
                WHERE v.numero_orden = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$numero_orden]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function obtenerDetalles($numero_orden) {
        $sql = "SELECT dv.*, p.nombre_producto AS producto_nombre, p.nombre_producto 
                FROM detalle_ventas dv 
                INNER JOIN productos p ON dv.codigo_producto = p.codigo 
                WHERE dv.numero_orden = ?
                ORDER BY dv.linea ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$numero_orden]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerPendientes() {
        $sql = "SELECT v.*, v.numero_orden AS id, 
                       COALESCE(c.nombre_razon_social, u.nombre) AS nombre_cliente 
                FROM ventas v 
                LEFT JOIN clientes c ON v.cedula_cliente = c.cedula
                LEFT JOIN usuarios u ON v.cedula_usuario = u.cedula 
                WHERE v.estado = 'pendiente' 
                ORDER BY v.fecha DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerRetiros() {
        $sql = "SELECT v.*, v.numero_orden AS id, 
                       COALESCE(c.nombre_razon_social, u.nombre) AS nombre_cliente 
                FROM ventas v 
                LEFT JOIN clientes c ON v.cedula_cliente = c.cedula
                LEFT JOIN usuarios u ON v.cedula_usuario = u.cedula 
                WHERE v.estado IN ('pagado', 'completado') 
                ORDER BY v.fecha DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_OBJ);
    }

    public function confirmarEntrega($numero_orden) {
        $sql = "UPDATE ventas SET estado = 'retirado', fecha_entrega = NOW() WHERE numero_orden = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$numero_orden]);
    }

    public function obtenerVentasPorEstado($estadosArray) {
        if (empty($estadosArray)) return [];
        $in = "'" . implode("','", array_map('addslashes', $estadosArray)) . "'";

        $sql = "SELECT v.*, v.numero_orden AS id, 
                       COALESCE(c.nombre_razon_social, u.nombre) AS nombre_cliente,
                       p.codigo_metodo AS nombre_metodo
                FROM ventas v 
                LEFT JOIN clientes c ON v.cedula_cliente = c.cedula
                LEFT JOIN usuarios u ON v.cedula_usuario = u.cedula 
                LEFT JOIN pagos p ON v.numero_orden = p.numero_orden AND p.numero_pago = 1
                WHERE v.estado IN ($in) 
                ORDER BY v.fecha DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}