<?php
if (!class_exists('CompraController')) {

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
            $where[] = "(c.numero_factura LIKE ? OR p.razon_social LIKE ? OR c.rif_proveedor LIKE ?)";
            $params[] = "%$q%";
            $params[] = "%$q%";
            $params[] = "%$q%";
        }

        $sql = "SELECT c.rif_proveedor, c.numero_factura, c.fecha_compra, c.total, c.estado,
                       c.comprado_por, c.banco, c.referencia_pago, c.codigo_metodo,
                       p.razon_social, p.rif, p.telefono,
                       COALESCE(u.nombre, 'Admin') AS usuario,
                       COALESCE(mp.nombre, c.codigo_metodo) AS metodo_pago,
                       (SELECT COALESCE(SUM(dc.cantidad), 0) 
                        FROM detalle_compras dc 
                        WHERE dc.rif_proveedor = c.rif_proveedor AND dc.numero_factura = c.numero_factura) AS total_productos
                FROM compras c
                INNER JOIN proveedores p ON c.rif_proveedor = p.rif
                LEFT JOIN usuarios u ON c.cedula_usuario = u.cedula
                LEFT JOIN metodos_pago mp ON c.codigo_metodo = mp.codigo_metodo
                WHERE " . implode(' AND ', $where) . "
                ORDER BY c.fecha_compra DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $compras = $stmt->fetchAll(PDO::FETCH_OBJ);

        foreach ($compras as $c) {
            $c->id = $c->numero_factura;
            $c->rif_cedula = $c->rif;
        }

        require_once 'views/compras/compras.php';
    }

    public function nuevaCompra() {
        $proveedores = $this->db->query("SELECT rif, rif AS id, rif AS rif_cedula, razon_social, estado FROM proveedores WHERE estado = 1 ORDER BY razon_social ASC")->fetchAll(PDO::FETCH_OBJ);

        $productos = $this->db->query("SELECT codigo, codigo AS id, nombre_producto, precio, costo_compra, stock FROM productos WHERE deleted_at = '2000-01-01 00:00:00' ORDER BY nombre_producto ASC")->fetchAll(PDO::FETCH_OBJ);

        $stmtVars = $this->db->prepare("SELECT codigo_variante, codigo_variante AS id, codigo_producto, talla, color, modelo, stock, precio FROM producto_variantes WHERE codigo_producto = ? ORDER BY codigo_variante ASC");
        foreach ($productos as $p) {
            $stmtVars->execute([$p->codigo]);
            $p->variantes = $stmtVars->fetchAll(PDO::FETCH_OBJ);
        }

        $metodos = $this->db->query("SELECT codigo_metodo, codigo_metodo AS id, nombre FROM metodos_pago WHERE activo = 1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_OBJ);
        $empleados = $this->db->query("SELECT cedula, cedula AS id, CONCAT(nombre, ' ', apellido) AS nombre_completo FROM usuarios WHERE codigo_rol IN ('master', 'administrador', 'almacen') ORDER BY nombre ASC")->fetchAll(PDO::FETCH_OBJ);
        $categorias = $this->db->query("SELECT slug, slug AS id, slug_padre, nombre_categoria FROM categorias ORDER BY nombre_categoria ASC")->fetchAll(PDO::FETCH_OBJ);

        require_once 'views/compras/nueva_compra.php';
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=compras");
            exit();
        }

        try {
            $this->db->beginTransaction();

            $rifProveedor   = trim($_POST['rif_proveedor'] ?? $_POST['id_proveedor'] ?? '');
            $cedulaUsuario  = $_SESSION['cedula'] ?? $_SESSION['id_usuario'] ?? 'SIN_USUARIO';
            $compradoPor    = trim($_POST['comprado_por'] ?? '');
            $numeroFactura  = trim($_POST['numero_factura'] ?? '');
            $fechaCompra    = !empty($_POST['fecha_compra']) ? str_replace('T', ' ', $_POST['fecha_compra']) : date('Y-m-d H:i:s');
            $codigoMetodo   = trim($_POST['codigo_metodo'] ?? $_POST['id_metodo_pago'] ?? 'efectivo_usd');
            $banco          = trim($_POST['banco'] ?? '');
            $referenciaPago = trim($_POST['referencia_pago'] ?? '');

            $prodCodigos = $_POST['codigo_producto'] ?? $_POST['id_producto'] ?? [];
            $varCodigos  = $_POST['codigo_variante'] ?? $_POST['id_variante'] ?? [];
            $nuevosCod   = $_POST['nuevo_codigo'] ?? [];
            $nuevosNom   = $_POST['nuevo_nombre'] ?? [];
            $cants       = $_POST['cantidad'] ?? [];
            $costos      = $_POST['costo_unitario'] ?? [];
            $nuevosPv    = $_POST['precio_venta'] ?? [];
            $actPrecios  = $_POST['actualizar_precio'] ?? [];
            $nuevosCat   = $_POST['nuevo_categoria'] ?? [];

            if (empty($rifProveedor) || empty($numeroFactura)) {
                throw new Exception("Selecciona un proveedor e ingresa el número de factura.");
            }
            if (empty($prodCodigos)) {
                throw new Exception("Debes agregar al menos un producto a la compra.");
            }

            $stmtExiste = $this->db->prepare("SELECT COUNT(*) FROM compras WHERE rif_proveedor = ? AND numero_factura = ?");
            $stmtExiste->execute([$rifProveedor, $numeroFactura]);
            if ($stmtExiste->fetchColumn() > 0) {
                throw new Exception("La factura '{$numeroFactura}' ya fue registrada para este proveedor.");
            }

            $total = 0;
            for ($k = 0; $k < count($prodCodigos); $k++) {
                $total += ((int)$cants[$k] * (float)$costos[$k]);
            }

            $stmtC = $this->db->prepare("
                INSERT INTO compras (rif_proveedor, numero_factura, cedula_usuario, comprado_por, fecha_compra, codigo_metodo, banco, referencia_pago, total, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'recibido')
            ");
            $stmtC->execute([
                $rifProveedor, $numeroFactura, $cedulaUsuario, $compradoPor,
                $fechaCompra, $codigoMetodo, $banco, $referenciaPago, $total
            ]);

            $stmtDet = $this->db->prepare("
                INSERT INTO detalle_compras (rif_proveedor, numero_factura, linea, codigo_producto, codigo_variante, cantidad, costo_unitario, margen, precio_venta, actualizo_precio, subtotal)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmtNuevoProd = $this->db->prepare("
                INSERT INTO productos (codigo, slug_categoria, nombre_producto, descripcion, precio, costo_compra, stock, stock_minimo, imagen, estado, deleted_at)
                VALUES (?, ?, ?, '', ?, ?, 0, 5, '', 1, '2000-01-01 00:00:00')
            ");

            $stmtStock     = $this->db->prepare("UPDATE productos SET stock = stock + ?, costo_compra = ? WHERE codigo = ?");
            $stmtStockVar  = $this->db->prepare("UPDATE producto_variantes SET stock = stock + ? WHERE codigo_variante = ? AND codigo_producto = ?");
            $stmtUpdPrecio = $this->db->prepare("UPDATE productos SET precio = ? WHERE codigo = ?");
            $stmtP         = $this->db->prepare("SELECT stock FROM productos WHERE codigo = ?");

            $stmtSecKardex = $this->db->prepare("SELECT COALESCE(MAX(numero_movimiento), 0) + 1 FROM kardex_inventario WHERE codigo_producto = ?");
            $stmtKardex    = $this->db->prepare("
                INSERT INTO kardex_inventario 
                (codigo_producto, numero_movimiento, tipo_movimiento, codigo_variante, cantidad, stock_resultante, referencia, observacion, cedula_usuario, fecha_hora)
                VALUES (?, ?, 'compra', ?, ?, ?, ?, ?, ?, NOW())
            ");

            $linea = 1;
            for ($i = 0; $i < count($prodCodigos); $i++) {
                $rawPCod = trim($prodCodigos[$i]);
                $cnt     = (int)$cants[$i];
                $costo   = (float)$costos[$i];
                $subt    = round($cnt * $costo, 2);
                $pv      = !empty($nuevosPv[$i]) ? (float)$nuevosPv[$i] : $costo;
                $debeActualizar = !empty($actPrecios[$i]) ? 1 : 0;
                $varCod  = !empty($varCodigos[$i]) ? trim($varCodigos[$i]) : '';

                if ($cnt <= 0) continue;

                if ($rawPCod === 'nuevo') {
                    $cod = trim($nuevosCod[$i] ?? '');
                    $nom = trim($nuevosNom[$i] ?? '');
                    $cat = trim($nuevosCat[$i] ?? 'raiz');

                    if (empty($cod) || empty($nom)) {
                        throw new Exception("Debes indicar código y nombre para el nuevo producto.");
                    }

                    $stmtNuevoProd->execute([$cod, $cat, $nom, $pv, $costo]);
                    $pCod = $cod;
                    $this->log('Inventario', 'INSERT', "Producto creado desde Compra #{$numeroFactura}: {$nom} ({$cod})");
                } else {
                    $pCod = $rawPCod;
                    if ($debeActualizar && $pv > 0) {
                        $stmtUpdPrecio->execute([$pv, $pCod]);
                    }
                }

                $margen = $costo > 0 ? round((($pv - $costo) / $costo) * 100, 2) : 30.00;

                $stmtDet->execute([
                    $rifProveedor, $numeroFactura, $linea, $pCod, $varCod,
                    $cnt, $costo, $margen, $pv, $debeActualizar, $subt
                ]);

                $stmtStock->execute([$cnt, $costo, $pCod]);

                if (!empty($varCod)) {
                    $stmtStockVar->execute([$cnt, $varCod, $pCod]);
                }

                $stmtP->execute([$pCod]);
                $stockResultante = (int)$stmtP->fetchColumn();

                $stmtSecKardex->execute([$pCod]);
                $numMov = (int)$stmtSecKardex->fetchColumn();

                $obs = "Entrada por compra Fac #{$numeroFactura}";
                if (!empty($varCod)) {
                    $obs .= " (Variante: {$varCod})";
                }

                $stmtKardex->execute([
                    $pCod, $numMov, $varCod, $cnt, $stockResultante,
                    $numeroFactura, $obs, $cedulaUsuario
                ]);

                $linea++;
            }

            $this->db->commit();
            $this->log('Compras', 'INSERT', "Registró compra Fac: {$numeroFactura} de {$rifProveedor} por $" . number_format($total, 2));

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

    public function detalle() {
        $factura = trim($_GET['factura'] ?? $_GET['id'] ?? '');
        $rif     = trim($_GET['rif'] ?? '');

        if (!empty($rif)) {
            $stmtC = $this->db->prepare("
                SELECT c.*, p.razon_social, p.rif, p.telefono, p.direccion,
                       COALESCE(u.nombre, 'Admin') AS usuario,
                       COALESCE(mp.nombre, c.codigo_metodo) AS metodo_pago
                FROM compras c
                INNER JOIN proveedores p ON c.rif_proveedor = p.rif
                LEFT JOIN usuarios u ON c.cedula_usuario = u.cedula
                LEFT JOIN metodos_pago mp ON c.codigo_metodo = mp.codigo_metodo
                WHERE c.numero_factura = ? AND c.rif_proveedor = ?
            ");
            $stmtC->execute([$factura, $rif]);
        } else {
            $stmtC = $this->db->prepare("
                SELECT c.*, p.razon_social, p.rif, p.telefono, p.direccion,
                       COALESCE(u.nombre, 'Admin') AS usuario,
                       COALESCE(mp.nombre, c.codigo_metodo) AS metodo_pago
                FROM compras c
                INNER JOIN proveedores p ON c.rif_proveedor = p.rif
                LEFT JOIN usuarios u ON c.cedula_usuario = u.cedula
                LEFT JOIN metodos_pago mp ON c.codigo_metodo = mp.codigo_metodo
                WHERE c.numero_factura = ? LIMIT 1
            ");
            $stmtC->execute([$factura]);
        }
        $compra = $stmtC->fetch(PDO::FETCH_OBJ);

        if (!$compra) {
            header("Location: index.php?action=compras");
            exit();
        }

        $stmtD = $this->db->prepare("
            SELECT dc.*, pr.nombre_producto, pr.codigo, pr.imagen, pr.precio AS precio_catalogo,
                   ROUND(((dc.precio_venta - dc.costo_unitario) / NULLIF(dc.costo_unitario, 0)) * 100, 1) AS margen_calculado,
                   ROUND((dc.precio_venta - dc.costo_unitario), 2) AS ganancia_unitaria,
                   ROUND((dc.precio_venta - dc.costo_unitario) * dc.cantidad, 2) AS ganancia_total_linea
            FROM detalle_compras dc
            INNER JOIN productos pr ON dc.codigo_producto = pr.codigo
            WHERE dc.rif_proveedor = ? AND dc.numero_factura = ?
            ORDER BY dc.linea ASC
        ");
        $stmtD->execute([$compra->rif_proveedor, $compra->numero_factura]);
        $detalles = $stmtD->fetchAll(PDO::FETCH_OBJ);

        require_once 'views/compras/detalle_compra.php';
    }

    public function anular() {
        $factura = trim($_POST['factura'] ?? $_GET['factura'] ?? $_GET['id'] ?? '');
        $rif     = trim($_POST['rif'] ?? $_GET['rif'] ?? '');
        $cedulaUsuario = $_SESSION['cedula'] ?? $_SESSION['id_usuario'] ?? 'SIN_USUARIO';

        if (!empty($factura)) {
            try {
                $this->db->beginTransaction();

                if (!empty($rif)) {
                    $stmtC = $this->db->prepare("SELECT estado, numero_factura, rif_proveedor FROM compras WHERE numero_factura = ? AND rif_proveedor = ?");
                    $stmtC->execute([$factura, $rif]);
                } else {
                    $stmtC = $this->db->prepare("SELECT estado, numero_factura, rif_proveedor FROM compras WHERE numero_factura = ? LIMIT 1");
                    $stmtC->execute([$factura]);
                }
                $compra = $stmtC->fetch(PDO::FETCH_OBJ);

                if ($compra && $compra->estado === 'recibido') {
                    $stmtD = $this->db->prepare("SELECT codigo_producto, codigo_variante, cantidad FROM detalle_compras WHERE rif_proveedor = ? AND numero_factura = ?");
                    $stmtD->execute([$compra->rif_proveedor, $compra->numero_factura]);
                    $items = $stmtD->fetchAll(PDO::FETCH_OBJ);

                    $stmtResta    = $this->db->prepare("UPDATE productos SET stock = GREATEST(0, stock - ?) WHERE codigo = ?");
                    $stmtRestaVar = $this->db->prepare("UPDATE producto_variantes SET stock = GREATEST(0, stock - ?) WHERE codigo_variante = ? AND codigo_producto = ?");
                    $stmtStk      = $this->db->prepare("SELECT stock FROM productos WHERE codigo = ?");
                    
                    $stmtSecKardex = $this->db->prepare("SELECT COALESCE(MAX(numero_movimiento), 0) + 1 FROM kardex_inventario WHERE codigo_producto = ?");
                    $stmtKardex    = $this->db->prepare("
                        INSERT INTO kardex_inventario 
                        (codigo_producto, numero_movimiento, tipo_movimiento, codigo_variante, cantidad, stock_resultante, referencia, observacion, cedula_usuario, fecha_hora)
                        VALUES (?, ?, 'anulacion_compra', ?, ?, ?, ?, ?, ?, NOW())
                    ");

                    foreach ($items as $it) {
                        $stmtResta->execute([(int)$it->cantidad, $it->codigo_producto]);

                        if (!empty($it->codigo_variante)) {
                            $stmtRestaVar->execute([(int)$it->cantidad, $it->codigo_variante, $it->codigo_producto]);
                        }

                        $stmtStk->execute([$it->codigo_producto]);
                        $actStock = (int)$stmtStk->fetchColumn();

                        $stmtSecKardex->execute([$it->codigo_producto]);
                        $numMov = (int)$stmtSecKardex->fetchColumn();

                        $stmtKardex->execute([
                            $it->codigo_producto,
                            $numMov,
                            $it->codigo_variante ?? '',
                            -(int)$it->cantidad,
                            $actStock,
                            $compra->numero_factura,
                            "Anulación de compra Fac #{$compra->numero_factura}",
                            $cedulaUsuario
                        ]);
                    }

                    $this->db->prepare("UPDATE compras SET estado = 'anulado' WHERE rif_proveedor = ? AND numero_factura = ?")
                             ->execute([$compra->rif_proveedor, $compra->numero_factura]);

                    $this->log('Compras', 'UPDATE', "Anuló compra Fac #{$compra->numero_factura} del proveedor {$compra->rif_proveedor}");
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

} // Fin de if (!class_exists('CompraController'))