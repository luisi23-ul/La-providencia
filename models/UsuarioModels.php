<?php
require_once 'config/db.php';

class UsuarioModels {
    private $db;

    // Recibimos $db como argumento
    public function __construct() {
        $this->db = Database::connect(); 
    }

    //  solo usuarios de un rol específico (rol 2 para admins)
   public function obtenerUsuarioPorId($id) {
    $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

    // Registra un nuevo Admin parecido al  cliente pero con rol fijo
public function registrarUsuarioModel($datos) {
    $sql = "INSERT INTO usuarios (nombre, correo, clave, id_rol, permisos) VALUES (?, ?, ?, ?, ?)";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$datos['nombre'], $datos['correo'], $datos['clave'], $datos['rol'], $datos['permisos']]);
}

    // Eliminar cualquier usuario 2 o 3
  public function eliminarAdminModel($id) {
    $sql = "UPDATE usuarios SET estado = 0 WHERE id = ?";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$id]);
}

    public function login($correo, $clave) {
    // Cambiamos a la lógica de verificación de password_verify
    $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE correo = ? AND estado = 1 LIMIT 1");
    $stmt->execute([$correo]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($clave, $user['clave'])) {
        return $user;
    }
    return false;
}
// registra solos los clientes
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
    // Usamos id_rol (como en tu BD) y quitamos el filtro de estado para traer todos
    $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id_rol = ?");
    $stmt->execute([$rol]);
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

public function actualizarAdminModel($datos) {
    if (!empty($datos['clave'])) {
        // Actualizar con nueva clave hasheada
        // 
$sql = "UPDATE usuarios SET nombre = ?, correo = ?, permisos = ?, estado = ? WHERE id = ?";
// ...
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

public function listarAdministradores() {
        // Usamos la función que YA tienes y que SÍ funciona (la de rol 2)
        // Esto evita errores de "función no encontrada"
        $admins = $this->modelo->obtenerUsuariosPorRol(2); 
        
        include "views/listado_administradores.php";
    }

public function actualizarEstadoUsuario($id, $estado) {
    $sql = "UPDATE usuarios SET estado = ? WHERE id = ?";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$estado, $id]);
}

public function obtenerClientes() {
    // Usamos 'correo' en lugar de 'email' y 'id_rol' en lugar de 'rol'
    $sql = "SELECT id, nombre, correo, estado FROM usuarios WHERE id_rol = 3";
    return $this->db->query($sql)->fetchAll(PDO::FETCH_OBJ);
}
}