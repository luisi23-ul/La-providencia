<?php
require_once 'config/db.php';

class ProductoModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect(); 
    }

    // -------------------------------------------------------------------------
    // MÉTODOS EXISTENTES CONSERVADOS (Para la tienda online)
    // -------------------------------------------------------------------------

    public function registrarProducto($datos) {
        $sql = "INSERT INTO productos (nombre_producto, descripcion, precio, stock, imagen, id_categoria) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $datos['nombre'], 
            $datos['desc'], 
            $datos['precio'], 
            $datos['stock'], 
            $datos['imagen'], 
            $datos['categoria']
        ]);
    }

    public function obtenerProductos() {
        $sql = "SELECT p.id, p.codigo, p.nombre_producto, p.precio, p.stock, p.stock_online,
                       IFNULL(p.imagen, '') AS imagen,
                       c.nombre_categoria AS nombre_categoria_real
                FROM productos p
                LEFT JOIN categorias c ON p.id_categoria = c.id
                WHERE p.estado = 1 AND p.deleted_at IS NULL
                ORDER BY p.id DESC";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Actualizado con los nuevos campos para listado_productos.php y POS
    public function obtenerTodos() {
        $sql = "SELECT p.id, p.codigo, p.nombre_producto, p.descripcion, p.precio, 
                       p.costo_compra, p.stock, p.stock_online, p.stock_minimo, 
                       p.imagen, p.estado, p.id_categoria, c.nombre_categoria 
                FROM productos p
                LEFT JOIN categorias c ON p.id_categoria = c.id
                WHERE p.deleted_at IS NULL 
                ORDER BY p.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Borrado lógico seguro (soft delete)
    public function borrarProducto($id) {
        $sql = "UPDATE productos SET estado = 0, deleted_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function obtenerPorId($id) {
        $sql = "SELECT * FROM productos WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function descontarStock($id, $cantidad) {
        try {
            $sql = "UPDATE productos SET stock = stock - :cantidad WHERE id = :id AND stock >= :cantidad";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':cantidad', (int)$cantidad, PDO::PARAM_INT);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function modificarProducto($datos) { 
        try {
            $sql = "UPDATE productos SET 
                        nombre_producto = :nombre, 
                        precio = :precio, 
                        stock = :stock, 
                        descripcion = :desc, 
                        imagen = :imagen 
                    WHERE id = :id"; 

            $stmt = $this->db->prepare($sql); 
            
            $stmt->bindParam(':nombre', $datos['nombre']); 
            $stmt->bindParam(':precio', $datos['precio']); 
            $stmt->bindParam(':stock', $datos['stock']); 
            $stmt->bindParam(':desc', $datos['desc']); 
            $stmt->bindParam(':imagen', $datos['imagen']); 
            $stmt->bindParam(':id', $datos['id']); 

            return $stmt->execute(); 
        } catch (PDOException $e) {
            echo "<h3>Error interno en la Base de Datos:</h3>";
            echo "<p>" . $e->getMessage() . "</p>";
            exit();
        }
    }

    public function obtenerEstadisticasGenerales() {
        try {
            $sqlInventario = "SELECT nombre_producto, stock FROM productos WHERE deleted_at IS NULL ORDER BY stock ASC";
            $stmtInventario = $this->db->query($sqlInventario);
            $inventarioCompleto = $stmtInventario->fetchAll(PDO::FETCH_ASSOC);

            $sqlVentas = "SELECT p.nombre_producto, COALESCE(SUM(dv.cantidad), 0) as total_vendido 
                          FROM productos p
                          LEFT JOIN detalle_ventas dv ON p.id = dv.id_producto 
                          WHERE p.deleted_at IS NULL
                          GROUP BY p.id 
                          ORDER BY total_vendido DESC"; 
            $stmtVentas = $this->db->query($sqlVentas);
            $ventasCompleto = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);

            return [
                'inventario' => $inventarioCompleto ? $inventarioCompleto : [],
                'ventas'     => $ventasCompleto ? $ventasCompleto : []
            ];

        } catch (PDOException $e) {
            return [
                'inventario' => [],
                'ventas'     => []
            ];
        }
    }

    public function listarProductos() {
        $sql = "SELECT p.*, c.nombre_categoria 
                FROM productos p 
                INNER JOIN categorias c ON p.id_categoria = c.id
                WHERE p.deleted_at IS NULL";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerCategorias() {
        $stmt = $this->db->query("SELECT * FROM categorias ORDER BY nombre_categoria ASC");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function buscarProductos($nombre, $id_categoria) {
        $sql = "SELECT p.* FROM productos p 
                WHERE p.estado = 1 AND p.deleted_at IS NULL"; 
        $params = [];

        if (!empty($nombre)) {
            $sql .= " AND (p.nombre_producto LIKE :nombre OR p.codigo LIKE :nombre)";
            $params[':nombre'] = "%$nombre%";
        }
        
        if (!empty($id_categoria)) {
            $sql .= " AND p.id_categoria = :id_categoria";
            $params[':id_categoria'] = $id_categoria;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // -------------------------------------------------------------------------
    // MÉTODOS NUEVOS PARA EL PANEL ADMINISTRATIVO (Atributos, Costos y Kardex)
    // -------------------------------------------------------------------------

    public function obtenerAtributosProducto($idProducto) {
        $stmt = $this->db->prepare("SELECT id_atributo, valor FROM producto_atributos WHERE id_producto = ?");
        $stmt->execute([$idProducto]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function guardarCompleto($datos, $atributos = []) {
        if (empty($datos['codigo'])) {
            $stmtNext = $this->db->query("SELECT COALESCE(MAX(id), 0) + 1 FROM productos");
            $nextId = (int)$stmtNext->fetchColumn();
            $datos['codigo'] = 'P-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        }

        $sql = "INSERT INTO productos (codigo, nombre_producto, descripcion, precio, costo_compra, 
                                       stock, stock_online, stock_minimo, id_categoria, estado, imagen)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $datos['codigo'], $datos['nombre_producto'], $datos['descripcion'], $datos['precio'], 
            $datos['costo_compra'], $datos['stock'], $datos['stock_online'], $datos['stock_minimo'], 
            $datos['id_categoria'], $datos['estado'], $datos['imagen']
        ]);
        $idProducto = $this->db->lastInsertId();

        $this->guardarAtributos($idProducto, $atributos);
        return $idProducto;
    }

    public function actualizarCompleto($id, $datos, $atributos = []) {
        $sql = "UPDATE productos 
                SET codigo = ?, nombre_producto = ?, descripcion = ?, precio = ?, 
                    costo_compra = ?, stock = ?, stock_online = ?, stock_minimo = ?, 
                    id_categoria = ?, estado = ?, imagen = COALESCE(?, imagen)
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $datos['codigo'], $datos['nombre_producto'], $datos['descripcion'], $datos['precio'], 
            $datos['costo_compra'], $datos['stock'], $datos['stock_online'], $datos['stock_minimo'], 
            $datos['id_categoria'], $datos['estado'], $datos['imagen'], $id
        ]);

        $this->guardarAtributos($id, $atributos);
        return true;
    }

    private function guardarAtributos($idProducto, $atributos) {
        $this->db->prepare("DELETE FROM producto_atributos WHERE id_producto = ?")->execute([$idProducto]);
        if (!empty($atributos)) {
            $stmtIns = $this->db->prepare("INSERT INTO producto_atributos (id_producto, id_atributo, valor) VALUES (?, ?, ?)");
            foreach ($atributos as $idAtrib => $valor) {
                if (trim($valor) !== '') {
                    $stmtIns->execute([$idProducto, (int)$idAtrib, trim($valor)]);
                }
            }
        }
    }
}