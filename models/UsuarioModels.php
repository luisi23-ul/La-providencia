<?php
require_once 'config/db.php';

class UsuarioModels {
    private $db;

    // Recibimos $db como argumento
    public function __construct() {
        $this->db = Database::connect(); 
    }

    // --- MÉTODOS PARA EL MASTER (Gestión de Admins) ---

    // Obtener solo usuarios de un rol específico (rol 2 para admins)
   public function obtenerUsuarioPorId($id) {
    $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
    // Registrar un nuevo Admin (similar al de cliente pero con rol fijo)
    // En UsuarioModels.php
public function registrarUsuarioModel($datos) {
    $sql = "INSERT INTO usuarios (nombre, correo, clave, id_rol, permisos) VALUES (?, ?, ?, ?, ?)";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$datos['nombre'], $datos['correo'], $datos['clave'], $datos['rol'], $datos['permisos']]);
}

    // Eliminar cualquier usuario (Admin o Cliente)
   public function eliminarAdminModel($id) {
    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$id]);
}
    // --- MÉTODOS QUE YA TENÍAS (No los borres) ---

    public function login($correo, $clave) {
        $sql = "SELECT * FROM usuarios WHERE correo = ? AND clave = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$correo, $clave]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function registrarClienteModel($datos) {
        $sql = "INSERT INTO usuarios (nombre, apellido, telefono, correo, clave, id_rol) 
                VALUES (?, ?, ?, ?, ?, ?)";
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
    // Asegúrate de traer 'clave' y 'id_rol' para poder verificar el hash y el permiso
    $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE correo = :correo");
    $stmt->execute([":correo" => $datos["correo"]]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function obtenerUsuariosPorRol($rol) {
    $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id_rol = ?");
    $stmt->execute([$rol]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function actualizarAdminModel($datos) {
    if (!empty($datos['clave'])) {
        // Actualizar con nueva clave hasheada
        $sql = "UPDATE usuarios SET nombre = ?, correo = ?, clave = ?, permisos = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $datos['nombre'], 
            $datos['correo'], 
            password_hash($datos['clave'], PASSWORD_DEFAULT), 
            $datos['permisos'], 
            $datos['id']
        ]);
    } else {
        // Actualizar sin tocar la clave
        $sql = "UPDATE usuarios SET nombre = ?, correo = ?, permisos = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $datos['nombre'], 
            $datos['correo'], 
            $datos['permisos'], 
            $datos['id']
        ]);
    }
}
}