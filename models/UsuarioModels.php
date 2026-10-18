<?php
require_once 'config/db.php';

class UsuarioModels {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: Database::connect();
    }

    // Buscar por clave primaria natural (cédula)
    public function obtenerUsuarioPorCedula($cedula) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE cedula = ?");
        $stmt->execute([$cedula]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Compatibilidad si algún método viejo aún pasa el identificador como ID
    public function obtenerUsuarioPorId($cedula) {
        return $this->obtenerUsuarioPorCedula($cedula);
    }

    // Buscar usuario por correo para login
    public function buscarUsuarioModel($datos) {
        $sql = "SELECT cedula, nombre, apellido, correo, clave, codigo_rol, estado 
                FROM usuarios 
                WHERE correo = :correo LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([":correo" => $datos["correo"]]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function login($correo, $clave) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE correo = ? AND estado = 1 LIMIT 1");
        $stmt->execute([$correo]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($clave, $user['clave'])) {
            return $user;
        }
        return false;
    }

    public function registrarClienteModel($datos) {
        $sql = "INSERT INTO usuarios (cedula, nombre, apellido, telefono, direccion, correo, clave, codigo_rol, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'cliente', 1)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $datos['cedula'],
            $datos['nombre'],
            $datos['apellido'] ?? '',
            $datos['telefono'] ?? '',
            $datos['direccion'] ?? '',
            $datos['correo'],
            $datos['clave']
        ]);
    }

    public function registrarUsuarioModel($datos) {
        $sql = "INSERT INTO usuarios (cedula, nombre, apellido, telefono, direccion, correo, clave, codigo_rol, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $datos['cedula'],
            $datos['nombre'],
            $datos['apellido'] ?? '',
            $datos['telefono'] ?? '',
            $datos['direccion'] ?? '',
            $datos['correo'],
            $datos['clave'],
            $datos['codigo_rol'],
            $datos['estado'] ?? 1
        ]);
    }

    public function obtenerUsuariosPorRol($codigo_rol) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE codigo_rol = ?");
        $stmt->execute([$codigo_rol]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function actualizarAdminModel($datos) {
        if (!empty($datos['clave'])) {
            $sql = "UPDATE usuarios 
                    SET nombre = ?, apellido = ?, correo = ?, codigo_rol = ?, clave = ?, estado = ? 
                    WHERE cedula = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $datos['nombre'],
                $datos['apellido'] ?? '',
                $datos['correo'],
                $datos['codigo_rol'],
                password_hash($datos['clave'], PASSWORD_DEFAULT),
                $datos['estado'] ?? 1,
                $datos['cedula']
            ]);
        } else {
            $sql = "UPDATE usuarios 
                    SET nombre = ?, apellido = ?, correo = ?, codigo_rol = ?, estado = ? 
                    WHERE cedula = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $datos['nombre'],
                $datos['apellido'] ?? '',
                $datos['correo'],
                $datos['codigo_rol'],
                $datos['estado'] ?? 1,
                $datos['cedula']
            ]);
        }
    }

    public function actualizarEstadoUsuario($cedula, $estado) {
        $sql = "UPDATE usuarios SET estado = ? WHERE cedula = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$estado, $cedula]);
    }

    public function eliminarAdminModel($cedula) {
        return $this->actualizarEstadoUsuario($cedula, 0);
    }

    public function obtenerClientes() {
        $sql = "SELECT cedula, nombre, apellido, correo, telefono, estado 
                FROM usuarios 
                WHERE codigo_rol = 'cliente' 
                ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerVenta($numero_orden) {
        $stmt = $this->db->prepare("SELECT * FROM ventas WHERE numero_orden = ?");
        $stmt->execute([$numero_orden]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function obtenerDetalles($numero_orden) {
        $sql = "SELECT dv.*, p.nombre_producto 
                FROM detalle_ventas dv
                INNER JOIN productos p ON dv.codigo_producto = p.codigo
                WHERE dv.numero_orden = ?
                ORDER BY dv.linea ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$numero_orden]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}