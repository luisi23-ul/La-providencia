<?php
class UsuarioModels {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function login($correo, $clave) {
        $sql = "SELECT * FROM usuarios WHERE correo = ? AND clave = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$correo, $clave]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function registrarCliente($datos) {
        // Usamos id_rol = 3 que es el de cliente
        $sql = "INSERT INTO usuarios (nombre, apellido, correo, telefono, clave, id_rol) 
                VALUES (:nombre, :apellido, :correo, :telefono, :clave, 3)";
        
        $stmt = $this->db->prepare($sql);
        
        // Encriptar clave por seguridad
        $password_hash = password_hash($datos['clave'], PASSWORD_DEFAULT);

        $stmt->bindParam(':nombre', $datos['nombre']);
        $stmt->bindParam(':apellido', $datos['apellido']);
        $stmt->bindParam(':correo', $datos['correo']);
        $stmt->bindParam(':telefono', $datos['telefono']);
        $stmt->bindParam(':clave', $password_hash);

        return $stmt->execute();
    }

    
}