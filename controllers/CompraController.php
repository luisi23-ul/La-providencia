<?php
class CompraController {
    private $db;

    public function __construct($db = null) {
        if ($db) {
            $this->db = $db;
        } else {
            require_once 'config/db.php';
            $this->db = Database::connect();
        }
    }

    private function log($modulo, $accion, $descripcion) {
        try {
            $idUsuario = $_SESSION['id_usuario'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $this->db->prepare("INSERT INTO auditoria_logs (id_usuario, modulo, accion, descripcion, ip) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$idUsuario, $modulo, $accion, $descripcion, $ip]);
        } catch (Exception $e) {}
    }

    // =========================================================================
    // 1. LISTADO DE COMPRAS (Alimenta views/compras/compras.php)
    // =========================================================================
    public function listar() {
        $desde  = trim($_GET['desde'] ?? date('Y-m-01'));
        $hasta  = trim($_GET['hasta'] ?? date('Y-m-d'));
        $estado = trim($_GET['estado'] ?? '');
        $q      = trim($_GET['q'] ?? '');

        $where = ["1=1"];
        $params = [];

        if ($desde !== '') {
            $where[] = "DATE(c.fecha_compra) >= ?";
            $params[] = $desde;
        }
        if ($hasta !== '') {
            $where[] = "DATE(c.fecha_compra) <= ?";
            $params[] = $hasta;
        }
        if ($estado !== '') {
            $where[] = "c.estado = ?";
            $params[] = $estado;
        }
        if ($q !== '') {
            $where[] = "(c.numero_factura LIKE ? OR p.razon_social LIKE ?)";
            $params[] = "%$q%";
            $params[] = "%$q%";
        }

        $sql = "SELECT c.id, c.id_proveedor, c.numero_factura, c.fecha_compra, c.destino_stock, c.total, c.estado,
                       p.razon_social, p.rif_cedula,
                       COALESCE(u.nombre, 'Admin') AS usuario,
                       (SELECT COALESCE(SUM(dc.cantidad), 0) FROM detalle_compras dc WHERE dc.id_compra = c.id) AS total_productos
                FROM compras c
                INNER JOIN proveedores p ON c.id_proveedor = p.id
                LEFT JOIN usuarios u ON c.id_usuario = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY c.fecha_compra DESC, c.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $compras = $stmt->fetchAll(PDO::FETCH_OBJ);

        require_once 'views/compras/compras.php';
    }

    // =========================================================================
    // 2. NUEVA COMPRA (Alimenta views/compras/nueva_compra.php)
    // =========================================================================
    public function nuevaCompra() {
        $proveedores = $this->db->query("SELECT id, rif_cedula, razon_social FROM proveedores WHERE estado = 1 ORDER BY razon_social ASC")->fetchAll(PDO::FETCH_OBJ);
        $productos = $this->db->query("SELECT id, codigo, nombre_producto, precio, costo_compra, stock, stock_online, imagen FROM productos WHERE estado = 1 AND deleted_at IS NULL ORDER BY nombre_producto ASC")->fetchAll(PDO::FETCH_OBJ);

        require_once 'views/compras/nueva_compra.php';
    }

    // =========================================================================
    // 3. GUARDAR COMPRA (Procesa el formulario, suma stock y asienta Kardex)
    // =========================================================================
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=compras");
            exit();
        }

        try {
            $this->db->beginTransaction();

            $idProveedor   = (int)($_POST['id_proveedor'] ?? 0);
            $idUsuario     = $_SESSION['id_usuario'] ?? 1;
            $numeroFactura = trim($_POST['numero_factura'] ?? '');
            $fechaCompra   = !empty($_POST['fecha_compra']) ? $_POST['fecha_compra'] . ' ' . date('H:i:s') : date('Y-m-d H:i:s');
            $destinoStock  = trim($_POST['destino_stock'] ?? 'tienda');
            $total         = (float)($_POST['total'] ?? 0);

            if ($idProveedor <= 0 || empty($numeroFactura)) {
                throw new Exception("Debes seleccionar un proveedor e ingresar el número de factura.");
            }

            // Insertar cabecera con estado 'recibido'
            $stmtC = $this->db->prepare("
                INSERT INTO compras (id_proveedor, id_usuario, numero_factura, fecha_compra, destino_stock, total, estado)
                VALUES (?, ?, ?, ?, ?, ?, 'recibido')
            ");
            $stmtC->execute([$idProveedor, $idUsuario, $numeroFactura, $fechaCompra, $destinoStock, $total]);
            $idCompra = (int)$this->db->lastInsertId();

            $prodIds     = $_POST['id_producto'] ?? [];
            $cants       = $_POST['cantidad'] ?? [];
            $costos      = $_POST['costo_unitario'] ?? [];
            $nuevosPv    = $_POST['precio_venta'] ?? [];

            $stmtDet = $this->db->prepare("
                INSERT INTO detalle_compras (id_compra, id_producto, cantidad, costo_unitario, subtotal)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmtStockTienda = $this->db->prepare("UPDATE productos SET stock = stock + ?, costo_compra = ? WHERE id = ?");
            $stmtStockOnline = $this->db->prepare("UPDATE productos SET stock_online = stock_online + ?, costo_compra = ? WHERE id = ?");
            $stmtUpdPrecio   = $this->db->prepare("UPDATE productos SET precio = ? WHERE id = ?");

            $stmtKardex = $this->db->prepare("
                INSERT INTO kardex_inventario 
                (id_producto, tipo_movimiento, canal, cantidad, stock_tienda_resultante, stock_online_resultante, id_referencia, observacion, id_usuario)
                VALUES (?, 'compra', ?, ?, ?, ?, ?, ?, ?)
            ");

            for ($i = 0; $i < count($prodIds); $i++) {
                $pId   = (int)$prodIds[$i];
                $cnt   = (int)$cants[$i];
                $costo = (float)$costos[$i];
                $subt  = $cnt * $costo;
                $pv    = !empty($nuevosPv[$i]) ? (float)$nuevosPv[$i] : null;

                if ($cnt <= 0) continue;

                $stmtDet->execute([$idCompra, $pId, $cnt, $costo, $subt]);

                // Actualizar nuevo precio de venta si se ingresó
                if ($pv !== null && $pv > 0) {
                    $stmtUpdPrecio->execute([$pv, $pId]);
                }

                // Consultar stock actual
                $stmtP = $this->db->prepare("SELECT stock, stock_online FROM productos WHERE id = ?");
                $stmtP->execute([$pId]);
                $prodActual = $stmtP->fetch(PDO::FETCH_OBJ);

                $stTienda = (int)($prodActual->stock ?? 0);
                $stOnline = (int)($prodActual->stock_online ?? 0);

                if ($destinoStock === 'tienda') {
                    $stmtStockTienda->execute([$cnt, $costo, $pId]);
                    $stTienda += $cnt;
                    $canal = 'tienda_fisica';
                } else {
                    $stmtStockOnline->execute([$cnt, $costo, $pId]);
                    $stOnline += $cnt;
                    $canal = 'online';
                }

                // Asentar entrada en Kardex
                $stmtKardex->execute([
                    $pId, $canal, $cnt, $stTienda, $stOnline,
                    $idCompra, "Compra Fac #{$numeroFactura}", $idUsuario
                ]);
            }

            $this->db->commit();
            $this->log('Compras', 'INSERT', "Registró compra #{$idCompra} (Fac: {$numeroFactura}) por $" . number_format($total, 2));

            header("Location: index.php?action=compras");
            exit();

        } catch (Exception $e) {
            $this->db->rollBack();
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
            exit();
        }
    }

    // =========================================================================
    // 4. DETALLE DE COMPRA (Alimenta views/compras/detalle_compra.php)
    // =========================================================================
    public function detalle() {
        $idCompra = (int)($_GET['id'] ?? 0);

        $stmtC = $this->db->prepare("
            SELECT c.*, p.razon_social, p.rif_cedula, p.telefono, p.direccion,
                   COALESCE(u.nombre, 'Admin') AS usuario
            FROM compras c
            INNER JOIN proveedores p ON c.id_proveedor = p.id
            LEFT JOIN usuarios u ON c.id_usuario = u.id
            WHERE c.id = ?
        ");
        $stmtC->execute([$idCompra]);
        $compra = $stmtC->fetch(PDO::FETCH_OBJ);

        if (!$compra) {
            header("Location: index.php?action=compras");
            exit();
        }

        $stmtD = $this->db->prepare("
            SELECT dc.*, pr.nombre_producto, pr.codigo, pr.imagen, pr.precio AS precio_venta,
                   ROUND(((pr.precio - dc.costo_unitario) / NULLIF(dc.costo_unitario, 0)) * 100, 1) AS margen
            FROM detalle_compras dc
            INNER JOIN productos pr ON dc.id_producto = pr.id
            WHERE dc.id_compra = ?
        ");
        $stmtD->execute([$idCompra]);
        $detalles = $stmtD->fetchAll(PDO::FETCH_OBJ);

        require_once 'views/compras/detalle_compra.php';
    }

    // =========================================================================
    // 5. ANULAR COMPRA (Revierte el stock y marca como 'anulado')
    // =========================================================================
    public function anular() {
        $idCompra = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        $idUsuario = $_SESSION['id_usuario'] ?? 1;

        if ($idCompra > 0) {
            try {
                $this->db->beginTransaction();

                $stmtC = $this->db->prepare("SELECT estado, destino_stock, numero_factura FROM compras WHERE id = ?");
                $stmtC->execute([$idCompra]);
                $compra = $stmtC->fetch(PDO::FETCH_OBJ);

                if ($compra && $compra->estado === 'recibido') {
                    $stmtD = $this->db->prepare("SELECT id_producto, cantidad FROM detalle_compras WHERE id_compra = ?");
                    $stmtD->execute([$idCompra]);
                    $items = $stmtD->fetchAll(PDO::FETCH_OBJ);

                    $colStock = ($compra->destino_stock === 'online') ? 'stock_online' : 'stock';

                    foreach ($items as $it) {
                        $this->db->prepare("UPDATE productos SET {$colStock} = GREATEST(0, {$colStock} - ?) WHERE id = ?")
                                 ->execute([$it->cantidad, $it->id_producto]);

                        $stmtP = $this->db->prepare("SELECT stock, stock_online FROM productos WHERE id = ?");
                        $stmtP->execute([$it->id_producto]);
                        $act = $stmtP->fetch(PDO::FETCH_OBJ);

                        $this->db->prepare("
                            INSERT INTO kardex_inventario 
                            (id_producto, tipo_movimiento, canal, cantidad, stock_tienda_resultante, stock_online_resultante, id_referencia, observacion, id_usuario)
                            VALUES (?, 'ajuste_salida', 'ambos', ?, ?, ?, ?, ?, ?)
                        ")->execute([
                            $it->id_producto, -$it->cantidad, (int)$act->stock, (int)$act->stock_online,
                            $idCompra, "Anulación compra Fac #{$compra->numero_factura}", $idUsuario
                        ]);
                    }

                    $this->db->prepare("UPDATE compras SET estado = 'anulado' WHERE id = ?")->execute([$idCompra]);
                    $this->log('Compras', 'UPDATE', "Anuló compra #{$idCompra}");
                }

                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollBack();
            }
        }

        header("Location: index.php?action=compras");
        exit();
    }
}