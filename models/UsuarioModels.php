<?php
require_once 'config/db.php';
class UsuarioModels {
    private $db;

    public function __construct() {
      $this->db = Database::connect(); 
    }

    public function login($correo, $clave) {
        $sql = "SELECT * FROM usuarios WHERE correo = ? AND clave = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$correo, $clave]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    


    // Si no tienes un constructor (__construct), usa la conexión estática directamente
    public function registrarClienteModel($datos) {
        $sql = "INSERT INTO usuarios (nombre, apellido, telefono, correo, clave, id_rol) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        // Usamos Conexion::conectar() para obtener el objeto PDO
         $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $datos['nombre'], 
            $datos['apellido'], 
            $datos['telefono'], 
            $datos['correo'], 
            $datos['clave'], 
            $datos['id_rol']
        ]);
    }

    public function buscarUsuarioModel($datos) {
    // CAMBIO AQUÍ: Usamos $this->db en lugar de Conexion::conectar()
    $stmt = $this->db->prepare("SELECT id, nombre, correo, clave FROM usuarios WHERE correo = :correo");
    
    $stmt->bindParam(":correo", $datos["correo"], PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->fetch();
}



























    public function cerrarSesion() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        header("Location: index.php?action=inicio");
        exit();
    }

    
    
}