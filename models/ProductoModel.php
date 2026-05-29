<?php
require_once 'config/db.php';

class ProductoModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect(); 
    }
// edgar es mejor que no toques nada de aqui primeroo preguntameeeee
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
    $sql = "SELECT p.id, p.nombre_producto, p.precio, p.stock, 
                   IFNULL(p.imagen, '') AS imagen,
                   c.nombre_categoria AS nombre_categoria_real
            FROM productos p
            LEFT JOIN categorias c ON p.id_categoria = c.id
            WHERE p.estado = 1
            ORDER BY p.id DESC";
            
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}



public function obtenerTodos() {
    $sql = "SELECT id, nombre_producto, descripcion, precio, stock, imagen, id_categoria 
            FROM productos 
            WHERE estado = 1 
            ORDER BY id DESC";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

// Borra de la base de datos
public function borrarProducto($id) {
    $sql = "UPDATE productos SET estado = 0 WHERE id = ?";
    $stmt = $this->db->prepare($sql);
    return $stmt->execute([$id]);
}

// Buscar UN solo producto por su ID
public function obtenerPorId($id) {
    $sql = "SELECT * FROM productos WHERE id = :id";
    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_OBJ);
}

public function descontarStock($id, $cantidad) {
    try {
        $sql = "UPDATE productos SET stock = stock - :cantidad WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cantidad', (int)$cantidad, PDO::PARAM_INT);
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        return false;
    }
}

//  ES SOLO PARA GUARDAR LOS CAMBIOS
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
        // Si la base de datos truena, esto nos pintará el error real en la pantalla
        echo "<h3>Error interno en la Base de Datos:</h3>";
        echo "<p>" . $e->getMessage() . "</p>";
        exit();
    }
}

public function obtenerEstadisticasGenerales() {
        try {
        
            $sqlInventario = "SELECT nombre_producto, stock FROM productos ORDER BY stock ASC";
            $stmtInventario = $this->db->query($sqlInventario);
            $inventarioCompleto = $stmtInventario->fetchAll(PDO::FETCH_ASSOC);

        
            $sqlVentas = "SELECT p.nombre_producto, COALESCE(SUM(dv.cantidad), 0) as total_vendido 
                          FROM productos p
                          LEFT JOIN detalle_ventas dv ON p.id = dv.id_producto 
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

    // para obtener los productos
public function listarProductos() {
    // Usamos un JOIN para traer el nombre_categoria
    $sql = "SELECT p.*, c.nombre_categoria 
            FROM productos p 
            INNER JOIN categorias c ON p.id_categoria = c.id";
            
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

public function obtenerCategorias() {
    $stmt = $this->db->query("SELECT * FROM categorias");
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

public function buscarProductos($nombre, $id_categoria) {
    $sql = "SELECT p.* FROM productos p 
            WHERE p.estado = 1"; 
    $params = [];

    if (!empty($nombre)) {
        $sql .= " AND p.nombre_producto LIKE :nombre";
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
}


