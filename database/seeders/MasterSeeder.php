<?php

class MasterSeeder {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function run() {
        // 1. Semilla para la tabla 'sistema' (Configuración de diseño)
        $checkSistema = $this->db->query("SELECT id FROM sistema WHERE id = 1");
        if ($checkSistema->rowCount() == 0) {
            $sqlSistema = "INSERT INTO sistema (id, nombre_empresa, titulo_principal, color_primario, color_secundario, footer_texto) 
                           VALUES (1, 'La Providencia', 'Bienvenido a La Providencia', '#0f172a', '#3b82f6', '© 2026 La Providencia - Todos los derechos reservados')";
            $this->db->query($sqlSistema);
        }

        // 2. Semilla para el usuario Master (id_rol = 1)
        $checkMaster = $this->db->query("SELECT id FROM usuarios WHERE id_rol = 1");
        if ($checkMaster->rowCount() == 0) {
            $pass = password_hash('Master2026', PASSWORD_DEFAULT);
            $sqlMaster = "INSERT INTO usuarios (nombre, apellido, correo, clave, id_rol) 
                          VALUES ('Admin', 'Master', 'master@providencia.com', '$pass', 1)";
            $this->db->query($sqlMaster);
        }
    }

    public function actualizarAdmin() {
    if ($_POST) {
        $id = $_POST['id'];
        $permisos = isset($_POST['permisos']) ? implode(',', $_POST['permisos']) : '';
        
        $sql = "UPDATE usuarios SET nombre = ?, correo = ?, permisos = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$_POST['nombre'], $_POST['correo'], $permisos, $id]);
        
        header("Location: index.php?action=gestionar_admins");
    }
}
}