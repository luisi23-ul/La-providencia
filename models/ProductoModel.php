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
                                   pv.stock
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
            SELECT pa.*, ca.nombre
            FROM producto_atributos pa
            INNER JOIN categoria_atributos ca ON pa.id_atributo = ca.id
            WHERE pa.id_producto = ?
        ");
        $stmt->execute([(int)$idProducto]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerVariantesProducto($idProducto) {
        $stmt = $this->db->prepare("
            SELECT id, id_producto, sku, talla, color, modelo, stock, precio 
            FROM producto_variantes 
            WHERE id_producto = ? 
            ORDER BY id ASC
        ");
        $stmt->execute([(int)$idProducto]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function guardarCompleto($datos, $atributos = [], $variantes = []) {
        $sql = "INSERT INTO productos (codigo, nombre_producto, descripcion, precio, costo_compra, stock, stock_minimo, imagen, id_categoria, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $datos['codigo'] ?? null,
            $datos['nombre_producto'] ?? '',
            $datos['descripcion'] ?? '',
            $datos['precio'] ?? 0,
            $datos['costo_compra'] ?? 0,
            $datos['stock'] ?? 0,
            $datos['stock_minimo'] ?? 5,
            $datos['imagen'] ?? null,
            $datos['id_categoria'] ?? null
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

        if (!empty($variantes)) {
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

        $this->sincronizarVariantes((int)$id, (array)$variantes);

        return true;
    }

    public function sincronizarVariantes($idProducto, array $variantes) {
        $idProducto = (int)$idProducto;
        $idUsuario = $_SESSION['id_usuario'] ?? 1;

        $stmtPrev = $this->db->prepare("SELECT id, talla, color, modelo, stock FROM producto_variantes WHERE id_producto = ?");
        $stmtPrev->execute([$idProducto]);
        $previas = [];
        foreach ($stmtPrev->fetchAll(PDO::FETCH_OBJ) as $p) {
            $previas[$p->id] = $p;
        }
        $teniaVariantes = count($previas) > 0;

        $idsMantener = [];
        $totalStock = 0;

        $stmtIns = $this->db->prepare("
            INSERT INTO producto_variantes (id_producto, sku, talla, color, modelo, stock, precio)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmtUpd = $this->db->prepare("
            UPDATE producto_variantes 
            SET sku = ?, talla = ?, color = ?, modelo = ?, stock = ?, precio = ?
            WHERE id = ? AND id_producto = ?
        ");

        $stmtKardex = $this->db->prepare("
            INSERT INTO kardex_inventario 
            (id_producto, tipo_movimiento, canal, cantidad, stock_tienda_resultante, stock_online_resultante, observacion, id_usuario)
            VALUES (?, 'ajuste', 'general', ?, ?, 0, ?, ?)
        ");

        foreach ($variantes as $v) {
            $talla  = trim($v['talla'] ?? '');
            $color  = trim($v['color'] ?? '');
            $modelo = trim($v['modelo'] ?? '');
            $stk    = max(0, (int)($v['stock'] ?? 0));
            $varId  = !empty($v['id']) ? (int)$v['id'] : 0;
            $sku    = !empty($v['sku']) ? trim($v['sku']) : null;
            $precio = isset($v['precio']) && $v['precio'] !== '' ? (float)$v['precio'] : null;

            if ($talla === '' && $color === '' && $modelo === '') {
                continue;
            }

            if ($varId > 0 && isset($previas[$varId])) {
                $viejo = $previas[$varId];
                
                if ($stk !== (int)$viejo->stock) {
                    $dif = $stk - (int)$viejo->stock;
                    $desc = "Ajuste en edición: Talla {$talla}, Color {$color}, Modelo {$modelo}";
                    try {
                        $stmtKardex->execute([$idProducto, $dif, $stk, $desc, $idUsuario]);
                    } catch (\Exception $e) {}
                }

                $stmtUpd->execute([$sku, $talla, $color, $modelo, $stk, $precio, $varId, $idProducto]);
                $idsMantener[] = $varId;
            } else {
                $stmtIns->execute([$idProducto, $sku, $talla, $color, $modelo, $stk, $precio]);
                $nuevoId = (int)$this->db->lastInsertId();
                $idsMantener[] = $nuevoId;

                if ($stk > 0) {
                    $desc = "Entrada por nueva variante: Talla {$talla}, Color {$color}, Modelo {$modelo}";
                    try {
                        $stmtKardex->execute([$idProducto, $stk, $stk, $desc, $idUsuario]);
                    } catch (\Exception $e) {}
                }
            }

            $totalStock += $stk;
        }

        if (!empty($idsMantener)) {
            $inClause = implode(',', array_map('intval', $idsMantener));
            $this->db->prepare("DELETE FROM producto_variantes WHERE id_producto = ? AND id NOT IN ($inClause)")
                     ->execute([$idProducto]);
            
            $this->db->prepare("UPDATE productos SET stock = ? WHERE id = ?")
                     ->execute([$totalStock, $idProducto]);
        } else {
            if ($teniaVariantes) {
                $this->db->prepare("DELETE FROM producto_variantes WHERE id_producto = ?")
                         ->execute([$idProducto]);
                $this->db->prepare("UPDATE productos SET stock = 0 WHERE id = ?")
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
        $totalStock = $this->db->query("SELECT SUM(stock) FROM productos WHERE deleted_at IS NULL")->fetchColumn();
        return [
            'total_productos' => (int)$totalProductos,
            'total_stock'     => (int)$totalStock
        ];
    }
}