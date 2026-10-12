<?php
class ProductoModel {
    private $db;

    public function __construct() {
        require_once 'config/db.php';
        $this->db = Database::connect();
    }

    public function getDb() {
        return $this->db;
    }

    public function existeCodigo($codigo, $idIgnorar = 0) {
        if (empty($codigo)) return false;
        $sql = "SELECT id FROM productos WHERE codigo = ? AND id != ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$codigo, (int)$idIgnorar]);
        return (bool)$stmt->fetch();
    }

    // PUNTO E: Uso de IFNULL para garantizar siempre las 5 partes
    public function obtenerTodos() {
        $sql = "SELECT p.*, 
                       c.nombre_categoria, 
                       padre.nombre_categoria AS nombre_categoria_padre,
                       (
                           SELECT GROUP_CONCAT(
                               CONCAT_WS(':', 
                                   IFNULL(pv.talla, ''), 
                                   IFNULL(pv.color, ''), 
                                   IFNULL(pv.modelo, ''), 
                                   pv.stock, 
                                   pv.stock_online
                               ) 
                               SEPARATOR '|'
                           )
                           FROM producto_variantes pv 
                           WHERE pv.id_producto = p.id
                       ) AS resumen_variantes
                FROM productos p
                LEFT JOIN categorias c ON p.id_categoria = c.id
                LEFT JOIN categorias padre ON c.id_padre = padre.id
                WHERE p.deleted_at IS NULL
                ORDER BY p.id DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerPorId($id) {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   c.nombre_categoria, 
                   padre.nombre_categoria AS nombre_categoria_padre
            FROM productos p
            LEFT JOIN categorias c ON p.id_categoria = c.id
            LEFT JOIN categorias padre ON c.id_padre = padre.id
            WHERE p.id = ? AND p.deleted_at IS NULL
        ");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function buscarProductos($nombre = '', $id_cat = '') {
        $where = ["p.deleted_at IS NULL"];
        $params = [];

        if (!empty($nombre)) {
            $where[] = "(p.nombre_producto LIKE ? OR p.codigo LIKE ?)";
            $params[] = "%$nombre%";
            $params[] = "%$nombre%";
        }

        if (!empty($id_cat)) {
            $where[] = "(p.id_categoria = ? OR c.id_padre = ?)";
            $params[] = $id_cat;
            $params[] = $id_cat;
        }

        $sql = "SELECT p.*, 
                       c.nombre_categoria, 
                       padre.nombre_categoria AS nombre_categoria_padre
                FROM productos p
                LEFT JOIN categorias c ON p.id_categoria = c.id
                LEFT JOIN categorias padre ON c.id_padre = padre.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY p.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerCategorias() {
        $stmt = $this->db->query("SELECT * FROM categorias ORDER BY nombre_categoria ASC");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerAtributosProducto($idProducto) {
        $stmt = $this->db->prepare("
            SELECT pa.*, ca.nombre, ca.opciones
            FROM producto_atributos pa
            INNER JOIN categoria_atributos ca ON pa.id_atributo = ca.id
            WHERE pa.id_producto = ?
        ");
        $stmt->execute([(int)$idProducto]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerVariantesProducto($idProducto) {
        $stmt = $this->db->prepare("SELECT * FROM producto_variantes WHERE id_producto = ? ORDER BY id ASC");
        $stmt->execute([(int)$idProducto]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function guardarCompleto($datos, $atributos = [], $variantes = []) {
        $sql = "INSERT INTO productos (codigo, nombre_producto, descripcion, precio, costo_compra, stock, stock_online, stock_minimo, id_categoria, estado, imagen) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $datos['codigo'],
            $datos['nombre_producto'],
            $datos['descripcion'],
            $datos['precio'],
            $datos['costo_compra'],
            $datos['stock'],
            $datos['stock_online'],
            $datos['stock_minimo'],
            $datos['id_categoria'],
            $datos['estado'],
            $datos['imagen']
        ]);
        $idProducto = (int)$this->db->lastInsertId();

        if (!empty($atributos) && is_array($atributos)) {
            $stmtAtr = $this->db->prepare("INSERT INTO producto_atributos (id_producto, id_atributo, valor) VALUES (?, ?, ?)");
            foreach ($atributos as $idAtr => $valor) {
                if (trim($valor) !== '') {
                    $stmtAtr->execute([$idProducto, (int)$idAtr, trim($valor)]);
                }
            }
        }

        if (!empty($variantes) && is_array($variantes)) {
            $this->sincronizarVariantes($idProducto, $variantes);
        }

        return $idProducto;
    }

    public function actualizarCompleto($id, $datos, $atributos = [], $variantes = []) {
        $sql = "UPDATE productos SET 
                    codigo = ?, 
                    nombre_producto = ?, 
                    descripcion = ?, 
                    precio = ?, 
                    costo_compra = ?, 
                    stock_minimo = ?, 
                    id_categoria = ?, 
                    estado = ?, 
                    imagen = ?
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $datos['codigo'],
            $datos['nombre_producto'],
            $datos['descripcion'],
            $datos['precio'],
            $datos['costo_compra'],
            $datos['stock_minimo'],
            $datos['id_categoria'],
            $datos['estado'],
            $datos['imagen'],
            (int)$id
        ]);

        $this->db->prepare("DELETE FROM producto_atributos WHERE id_producto = ?")->execute([(int)$id]);

        if (!empty($atributos) && is_array($atributos)) {
            $stmtAtr = $this->db->prepare("INSERT INTO producto_atributos (id_producto, id_atributo, valor) VALUES (?, ?, ?)");
            foreach ($atributos as $idAtr => $valor) {
                if (trim($valor) !== '') {
                    $stmtAtr->execute([(int)$id, (int)$idAtr, trim($valor)]);
                }
            }
        }

        // Siempre evaluar la sincronización de variantes al actualizar
        $this->sincronizarVariantes((int)$id, (array)$variantes);

        return true;
    }

    // PUNTO A y PUNTO B: Sincronización blindada con Kardex y corrección en vacíos
    public function sincronizarVariantes($idProducto, array $variantes) {
        $idProducto = (int)$idProducto;
        $idUsuario = $_SESSION['id_usuario'] ?? 1;

        // Verificar si el producto tenía variantes previamente
        $stmtPrev = $this->db->prepare("SELECT id, talla, color, modelo, stock, stock_online FROM producto_variantes WHERE id_producto = ?");
        $stmtPrev->execute([$idProducto]);
        $previas = [];
        foreach ($stmtPrev->fetchAll(PDO::FETCH_OBJ) as $p) {
            $previas[$p->id] = $p;
        }
        $teniaVariantes = count($previas) > 0;

        $idsMantener = [];
        $totalStock = 0;
        $totalStockOnline = 0;

        $stmtIns = $this->db->prepare("
            INSERT INTO producto_variantes (id_producto, sku, talla, color, modelo, stock, stock_online, precio)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmtUpd = $this->db->prepare("
            UPDATE producto_variantes 
            SET sku = ?, talla = ?, color = ?, modelo = ?, stock = ?, stock_online = ?, precio = ?
            WHERE id = ? AND id_producto = ?
        ");

        $stmtKardex = $this->db->prepare("
            INSERT INTO kardex_inventario 
            (id_producto, tipo_movimiento, canal, cantidad, stock_tienda_resultante, stock_online_resultante, observacion, id_usuario)
            VALUES (?, 'ajuste', ?, ?, ?, ?, ?, ?)
        ");

        foreach ($variantes as $v) {
            $talla  = trim($v['talla'] ?? '');
            $color  = trim($v['color'] ?? '');
            $modelo = trim($v['modelo'] ?? '');
            $stk    = max(0, (int)($v['stock'] ?? 0));
            $stkOn  = max(0, (int)($v['stock_online'] ?? 0));
            $varId  = !empty($v['id']) ? (int)$v['id'] : 0;
            $sku    = !empty($v['sku']) ? trim($v['sku']) : null;
            $precio = isset($v['precio']) && $v['precio'] !== '' ? (float)$v['precio'] : null;

            if ($talla === '' && $color === '' && $modelo === '') {
                continue;
            }

            if ($varId > 0 && isset($previas[$varId])) {
                $viejo = $previas[$varId];
                
                // PUNTO B: Auditoría Kardex si hubo cambio en stock tienda
                if ($stk !== (int)$viejo->stock) {
                    $dif = $stk - (int)$viejo->stock;
                    $desc = "Ajuste en edición: Talla {$talla}, Color {$color}, Modelo {$modelo}";
                    $stmtKardex->execute([$idProducto, 'tienda', $dif, $stk, $stkOn, $desc, $idUsuario]);
                }

                // PUNTO B: Auditoría Kardex si hubo cambio en stock online
                if ($stkOn !== (int)$viejo->stock_online) {
                    $difOn = $stkOn - (int)$viejo->stock_online;
                    $desc = "Ajuste online en edición: Talla {$talla}, Color {$color}, Modelo {$modelo}";
                    $stmtKardex->execute([$idProducto, 'online', $difOn, $stk, $stkOn, $desc, $idUsuario]);
                }

                $stmtUpd->execute([$sku, $talla, $color, $modelo, $stk, $stkOn, $precio, $varId, $idProducto]);
                $idsMantener[] = $varId;
            } else {
                $stmtIns->execute([$idProducto, $sku, $talla, $color, $modelo, $stk, $stkOn, $precio]);
                $nuevoId = (int)$this->db->lastInsertId();
                $idsMantener[] = $nuevoId;

                // Si se agrega una nueva variante con existencias iniciales, registrar entrada
                if ($stk > 0) {
                    $desc = "Entrada por nueva variante: Talla {$talla}, Color {$color}, Modelo {$modelo}";
                    $stmtKardex->execute([$idProducto, 'tienda', $stk, $stk, $stkOn, $desc, $idUsuario]);
                }
            }

            $totalStock += $stk;
            $totalStockOnline += $stkOn;
        }

        if (!empty($idsMantener)) {
            $inClause = implode(',', array_map('intval', $idsMantener));
            $this->db->prepare("DELETE FROM producto_variantes WHERE id_producto = ? AND id NOT IN ($inClause)")
                     ->execute([$idProducto]);
            
            // Actualizar el stock total sumado
            $this->db->prepare("UPDATE productos SET stock = ?, stock_online = ? WHERE id = ?")
                     ->execute([$totalStock, $totalStockOnline, $idProducto]);
        } else {
            // PUNTO A: Si se borraron todas y el producto TENÍA variantes, poner a 0
            if ($teniaVariantes) {
                $this->db->prepare("DELETE FROM producto_variantes WHERE id_producto = ?")
                         ->execute([$idProducto]);
                $this->db->prepare("UPDATE productos SET stock = 0, stock_online = 0 WHERE id = ?")
                         ->execute([$idProducto]);
            }
        }
    }

    public function borrarProducto($id) {
        $stmt = $this->db->prepare("UPDATE productos SET deleted_at = NOW() WHERE id = ?");
        return $stmt->execute([(int)$id]);
    }

    public function obtenerEstadisticasGenerales() {
        $totalProductos = $this->db->query("SELECT COUNT(*) FROM productos WHERE deleted_at IS NULL")->fetchColumn();
        $totalStock = $this->db->query("SELECT SUM(stock + stock_online) FROM productos WHERE deleted_at IS NULL")->fetchColumn();
        return [
            'total_productos' => (int)$totalProductos,
            'total_stock'     => (int)$totalStock
        ];
    }
}