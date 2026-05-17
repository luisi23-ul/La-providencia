<?php
require_once 'config/db.php';

class ProductoModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect(); 
    }

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
    $sql = "SELECT * FROM productos WHERE estado = 1 ORDER BY id DESC";
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
        // CORREGIDO: 'id' es el nombre exacto de tu columna en phpMyAdmin
        $sql = "UPDATE productos SET stock = stock - :cantidad WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        
        // Aseguramos que pasen como números enteros limpios
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



}