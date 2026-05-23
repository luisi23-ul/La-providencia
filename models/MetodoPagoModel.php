<?php
class MetodoPagoModel {
    private $db;

    public function __construct() {
        // Asumiendo que tienes una clase de conexión a la BD
        $this->db = Database::connect(); 
    }

    // Método para llenar el select del carrito
    public function obtenerTodos() {
        $stmt = $this->db->query("SELECT * FROM metodos_pago WHERE activo = 1");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Método para obtener el nombre del método por su ID (útil para reportes)
    public function obtenerPorId($id) {
        $stmt = $this->db->prepare("SELECT nombre FROM metodos_pago WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
}