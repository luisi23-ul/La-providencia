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
    // Consulta todos los productos de la base de datos
    $sql = "SELECT * FROM productos ORDER BY id DESC";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_OBJ); // Retorna una lista de objetos
}


public function obtenerTodos() {
     $sql = "SELECT id, nombre_producto, descripcion, precio, stock, imagen, id_categoria FROM productos ORDER BY id DESC";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

// Borrar de la base de datos
public function borrarProducto($id) {
    $sql = "DELETE FROM productos WHERE id = ?";
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

// 2. ESTA FUNCIÓN ES SOLO PARA GUARDAR LOS CAMBIOS
public function modificarProducto($datos) { 
    // 1. Agregamos "imagen = :imagen" a la consulta SQL
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
}



}