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
                       c.comprado_por, c.banco, c.referencia_pago,
                       p.razon_social, p.rif_cedula,
                       COALESCE(u.nombre, 'Admin') AS usuario,
                       COALESCE(mp.nombre, '') AS metodo_pago,
                       (SELECT COALESCE(SUM(dc.cantidad), 0) FROM detalle_compras dc WHERE dc.id_compra = c.id) AS total_productos
                FROM compras c
                INNER JOIN proveedores p ON c.id_proveedor = p.id
                LEFT JOIN usuarios u ON c.id_usuario = u.id
                LEFT JOIN metodos_pago mp ON c.id_metodo_pago = mp.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY c.fecha_compra DESC, c.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $compras = $stmt->fetchAll(PDO::FETCH_OBJ);

        require_once 'views/compras/compras.php';
    }

   public function nuevaCompra() {
        $proveedores = $this->db->query("SELECT id, rif_cedula, razon_social, estado FROM proveedores WHERE estado = 1 ORDER BY razon_social ASC")->fetchAll(PDO::FETCH_OBJ);
        
        // Traer productos activos
        $productos = $this->db->query("SELECT id, codigo, nombre_producto, precio, costo_compra, stock FROM productos WHERE deleted_at IS NULL ORDER BY nombre_producto ASC")->fetchAll(PDO::FETCH_OBJ);
        
        // Cargar las variantes asociadas a cada producto
        $stmtVars = $this->db->prepare("SELECT id, id_producto, sku, talla, color, modelo, stock, precio FROM producto_variantes WHERE id_producto = ? ORDER BY id ASC");
        foreach ($productos as $p) {
            $stmtVars->execute([$p->id]);
            $p->variantes = $stmtVars->fetchAll(PDO::FETCH_OBJ);
        }

        $metodos     = $this->db->query("SELECT id, nombre FROM metodos_pago WHERE activo = 1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_OBJ);
        $empleados   = $this->db->query("SELECT id, CONCAT(nombre, ' ', apellido) AS nombre_completo FROM usuarios WHERE id_rol IN (1, 2) ORDER BY nombre ASC")->fetchAll(PDO::FETCH_OBJ);
        $categorias  = $this->db->query("SELECT id, id_padre, nombre_categoria FROM categorias ORDER BY nombre_categoria ASC")->fetchAll(PDO::FETCH_OBJ);
        
        require_once 'views/compras/nueva_compra.php';
    }

    // =========================================================================
    // 1. GUARDAR COMPRA
    // =========================================================================
   public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=compras");
            exit();
        }

        try {
            $this->db->beginTransaction();

            $idProveedor    = (int)($_POST['id_proveedor'] ?? 0);
            $idUsuario      = $_SESSION['id_usuario'] ?? 1;
            $compradoPor    = trim($_POST['comprado_por'] ?? '');
            $numeroFactura  = trim($_POST['numero_factura'] ?? '');
            $fechaCompra    = !empty($_POST['fecha_compra']) ? str_replace('T', ' ', $_POST['fecha_compra']) : date('Y-m-d H:i:s');
            $destinoStock   = 'general'; // Inventario unificado
            $idMetodoPago   = !empty($_POST['id_metodo_pago']) ? (int)$_POST['id_metodo_pago'] : null;
            $banco          = trim($_POST['banco'] ?? '');
            $referenciaPago = trim($_POST['referencia_pago'] ?? '');

            $prodIds    = $_POST['id_producto'] ?? [];
            $varIds     = $_POST['id_variante'] ?? [];
            $nuevosCod  = $_POST['nuevo_codigo'] ?? [];
            $nuevosNom  = $_POST['nuevo_nombre'] ?? [];
            $cants      = $_POST['cantidad'] ?? [];
            $costos     = $_POST['costo_unitario'] ?? [];
            $nuevosPv   = $_POST['precio_venta'] ?? [];
            $actPrecios = $_POST['actualizar_precio'] ?? [];
            $nuevosCat  = $_POST['nuevo_categoria'] ?? [];

            if ($idProveedor <= 0 || empty($numeroFactura)) {
                throw new Exception("Selecciona un proveedor e ingresa el número de factura.");
            }
            if (empty($prodIds)) {
                throw new Exception("Debes agregar al menos un producto a la compra.");
            }

            $total = 0;
            for ($k = 0; $k < count($prodIds); $k++) {
                $total += ((int)$cants[$k] * (float)$costos[$k]);
            }

            $stmtC = $this->db->prepare("
                INSERT INTO compras (id_proveedor, id_usuario, comprado_por, numero_factura, fecha_compra, destino_stock, id_metodo_pago, banco, referencia_pago, total, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'recibido')
            ");
            $stmtC->execute([
                $idProveedor, $idUsuario, $compradoPor, $numeroFactura,
                $fechaCompra, $destinoStock, $idMetodoPago, $banco, $referenciaPago, $total
            ]);
            $idCompra = (int)$this->db->lastInsertId();

            $stmtDet = $this->db->prepare("
                INSERT INTO detalle_compras (id_compra, id_producto, cantidad, costo_unitario, subtotal)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmtNuevoProd = $this->db->prepare("
                INSERT INTO productos (codigo, nombre_producto, id_categoria, precio, costo_compra, stock, estado)
                VALUES (?, ?, ?, ?, ?, 0, 1)
            ");

            $stmtStock       = $this->db->prepare("UPDATE productos SET stock = stock + ?, costo_compra = ? WHERE id = ?");
            $stmtStockVar    = $this->db->prepare("UPDATE producto_variantes SET stock = stock + ? WHERE id = ? AND id_producto = ?");
            $stmtUpdPrecio   = $this->db->prepare("UPDATE productos SET precio = ? WHERE id = ?");
            $stmtP           = $this->db->prepare("SELECT stock FROM productos WHERE id = ?");

            $stmtKardex = $this->db->prepare("
                INSERT INTO kardex_inventario 
                (id_producto, tipo_movimiento, canal, cantidad, stock_tienda_resultante, stock_online_resultante, id_referencia, observacion, id_usuario)
                VALUES (?, 'compra', 'general', ?, ?, 0, ?, ?, ?)
            ");

            for ($i = 0; $i < count($prodIds); $i++) {
                $rawPId = $prodIds[$i];
                $cnt    = (int)$cants[$i];
                $costo  = (float)$costos[$i];
                $subt   = round($cnt * $costo, 2);
                $pv     = !empty($nuevosPv[$i]) ? (float)$nuevosPv[$i] : $costo;
                $debeActualizar = !empty($actPrecios[$i]);
                $varId  = !empty($varIds[$i]) ? (int)$varIds[$i] : 0;

                if ($cnt <= 0) continue;

                if ($rawPId === 'nuevo') {
                    $cod = trim($nuevosCod[$i] ?? '');
                    $nom = trim($nuevosNom[$i] ?? '');
                    $cat = (int)($nuevosCat[$i] ?? 0);

                    if (empty($cod) || empty($nom)) {
                        throw new Exception("Debes indicar código y nombre para el nuevo producto.");
                    }
                    if ($cat <= 0) {
                        throw new Exception("Elige la categoría del producto nuevo.");
                    }

                    $stmtNuevoProd->execute([$cod, $nom, $cat, $pv, $costo]);
                    $pId = (int)$this->db->lastInsertId();
                    if (method_exists($this, 'log')) {
                        $this->log('Inventario', 'INSERT', "Producto creado desde Compra #{$idCompra}: {$nom} ({$cod})");
                    }
                } else {
                    $pId = (int)$rawPId;
                    if ($debeActualizar && $pv > 0) {
                        $stmtUpdPrecio->execute([$pv, $pId]);
                    }
                }

                // A. Registrar en detalle de compras
                $stmtDet->execute([$idCompra, $pId, $cnt, $costo, $subt]);

                // B. Actualizar stock y costo automáticamente
                $stmtStock->execute([$cnt, $costo, $pId]);

                // B.1 Si se especificó una variante, actualizar su stock
                if ($varId > 0) {
                    $stmtStockVar->execute([$cnt, $varId, $pId]);
                }

                // C. Kardex con el stock resultante
                $stmtP->execute([$pId]);
                $stockResultante = (int)$stmtP->fetchColumn();

                $obs = "Entrada por compra Fac #{$numeroFactura}";
                if ($varId > 0) {
                    $obs .= " (Variante #{$varId})";
                }

                $stmtKardex->execute([
                    $pId, 
                    $cnt, 
                    $stockResultante, 
                    $idCompra, 
                    $obs, 
                    $idUsuario
                ]);
            }

            $this->db->commit();
            if (method_exists($this, 'log')) {
                $this->log('Compras', 'INSERT', "Registró compra #{$idCompra} (Fac: {$numeroFactura}) por $" . number_format($total, 2));
            }

            header("Location: index.php?action=compras");
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
    // 2. DETALLE DE COMPRA
    // =========================================================================
    public function detalle() {
        $idCompra = (int)($_GET['id'] ?? 0);

        $stmtC = $this->db->prepare("
            SELECT c.*, p.razon_social, p.rif_cedula, p.telefono, p.direccion,
                   COALESCE(u.nombre, 'Admin') AS usuario,
                   COALESCE(mp.nombre, '') AS metodo_pago
            FROM compras c
            INNER JOIN proveedores p ON c.id_proveedor = p.id
            LEFT JOIN usuarios u ON c.id_usuario = u.id
            LEFT JOIN metodos_pago mp ON c.id_metodo_pago = mp.id
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
                   ROUND(((pr.precio - dc.costo_unitario) / NULLIF(dc.costo_unitario, 0)) * 100, 1) AS margen,
                   ROUND((pr.precio - dc.costo_unitario), 2) AS ganancia_unitaria,
                   ROUND((pr.precio - dc.costo_unitario) * dc.cantidad, 2) AS ganancia_total_linea
            FROM detalle_compras dc
            INNER JOIN productos pr ON dc.id_producto = pr.id
            WHERE dc.id_compra = ?
        ");
        $stmtD->execute([$idCompra]);
        $detalles = $stmtD->fetchAll(PDO::FETCH_OBJ);

        require_once 'views/compras/detalle_compra.php';
    }

    // =========================================================================
    // 3. ANULAR COMPRA
    // =========================================================================
    public function anular() {
        $idCompra  = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        $idUsuario = $_SESSION['id_usuario'] ?? 1;

        if ($idCompra > 0) {
            try {
                $this->db->beginTransaction();

                $stmtC = $this->db->prepare("SELECT estado, numero_factura FROM compras WHERE id = ?");
                $stmtC->execute([$idCompra]);
                $compra = $stmtC->fetch(PDO::FETCH_OBJ);

                if ($compra && $compra->estado === 'recibido') {
                    $stmtD = $this->db->prepare("SELECT id_producto, cantidad FROM detalle_compras WHERE id_compra = ?");
                    $stmtD->execute([$idCompra]);
                    $items = $stmtD->fetchAll(PDO::FETCH_OBJ);

                    $stmtResta  = $this->db->prepare("UPDATE productos SET stock = GREATEST(0, stock - ?) WHERE id = ?");
                    $stmtStk    = $this->db->prepare("SELECT stock FROM productos WHERE id = ?");
                    $stmtKardex = $this->db->prepare("
                        INSERT INTO kardex_inventario 
                        (id_producto, tipo_movimiento, canal, cantidad, stock_tienda_resultante, stock_online_resultante, id_referencia, observacion, id_usuario)
                        VALUES (?, 'ajuste_salida', 'general', ?, ?, 0, ?, ?, ?)
                    ");

                    foreach ($items as $it) {
                        $stmtResta->execute([(int)$it->cantidad, (int)$it->id_producto]);

                        $stmtStk->execute([(int)$it->id_producto]);
                        $actStock = (int)$stmtStk->fetchColumn();

                        $stmtKardex->execute([
                            (int)$it->id_producto,
                            -(int)$it->cantidad,
                            $actStock,
                            $idCompra,
                            "Anulación compra Fac #{$compra->numero_factura}",
                            $idUsuario
                        ]);
                    }

                    $this->db->prepare("UPDATE compras SET estado = 'anulado' WHERE id = ?")->execute([$idCompra]);
                    if (method_exists($this, 'log')) {
                        $this->log('Compras', 'UPDATE', "Anuló compra #{$idCompra}");
                    }
                }

                $this->db->commit();
            } catch (Exception $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
            }
        }

        header("Location: index.php?action=compras");
        exit();
    }
}