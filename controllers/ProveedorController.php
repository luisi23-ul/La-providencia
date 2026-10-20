<?php
class ProveedorController {
    private $db;

    public function __construct($db = null) {
        if ($db) {
            $this->db = $db;
        } else {
            require_once 'config/db.php';
            $this->db = Database::connect();
        }
    }

    private function log($modulo, $accion, $descripcion) {
        try {
            $idUsuario = $_SESSION['cedula'] ?? $_SESSION['id_usuario'] ?? 'SIN_USUARIO';
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $this->db->prepare("INSERT INTO auditoria_logs (id_usuario, modulo, accion, descripcion, ip) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$idUsuario, $modulo, $accion, $descripcion, $ip]);
        } catch (Exception $e) {}
    }

    public function listar() {
        // Se mapea rif como id para total compatibilidad con las vistas existentes
        $sql = "SELECT p.rif, 
                       p.rif AS id, 
                       p.rif AS rif_cedula,
                       p.razon_social, 
                       p.contacto,
                       p.telefono, 
                       p.correo, 
                       p.direccion, 
                       p.estado,
                       (SELECT COUNT(*) FROM compras c WHERE c.rif_proveedor = p.rif) AS total_compras
                FROM proveedores p
                ORDER BY p.razon_social ASC";
        
        try {
            $stmt = $this->db->query($sql);
            $proveedores = $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            // Fallback por si la relación en compras usa otra columna o no existe compras
            $sqlSimple = "SELECT p.rif, p.rif AS id, p.rif AS rif_cedula, p.razon_social, p.contacto, p.telefono, p.correo, p.direccion, p.estado, 0 AS total_compras FROM proveedores p ORDER BY p.razon_social ASC";
            $proveedores = $this->db->query($sqlSimple)->fetchAll(PDO::FETCH_OBJ);
        }

        $rutas = [
            'views/compras/proveedores.php',
            'views/proveedores.php',
            'views/admin/proveedores.php'
        ];

        foreach ($rutas as $vista) {
            if (file_exists($vista)) {
                require_once $vista;
                return;
            }
        }
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=proveedores");
            exit();
        }

        $rifOriginal = strtoupper(trim((string)($_POST['rif_original'] ?? $_POST['id'] ?? '')));
        $rif         = strtoupper(trim((string)($_POST['rif'] ?? $_POST['rif_cedula'] ?? '')));
        $razon       = trim((string)($_POST['razon_social'] ?? ''));
        $contacto    = trim((string)($_POST['contacto'] ?? ''));
        $telefono    = trim((string)($_POST['telefono'] ?? ''));
        $correo      = trim((string)($_POST['correo'] ?? ''));
        $direccion   = trim((string)($_POST['direccion'] ?? ''));
        $volver      = trim((string)($_POST['volver'] ?? 'proveedores'));

        if (empty($rif) || empty($razon)) {
            echo "<script>alert('El RIF y la Razón Social son obligatorios.'); window.history.back();</script>";
            exit();
        }

        try {
            // Si viene rifOriginal, es una edición
            if (!empty($rifOriginal)) {
                if ($rif !== $rifOriginal) {
                    $stmtChk = $this->db->prepare("SELECT rif FROM proveedores WHERE rif = ? AND rif != ?");
                    $stmtChk->execute([$rif, $rifOriginal]);
                    if ($stmtChk->fetch()) {
                        echo "<script>alert('El RIF {$rif} ya pertenece a otro proveedor.'); window.history.back();</script>";
                        exit();
                    }
                }

                $stmt = $this->db->prepare("
                    UPDATE proveedores 
                    SET rif = ?, razon_social = ?, contacto = ?, telefono = ?, correo = ?, direccion = ?
                    WHERE rif = ?
                ");
                $stmt->execute([$rif, $razon, $contacto, $telefono, $correo, $direccion, $rifOriginal]);

                $this->log('Proveedores', 'UPDATE', "Actualizó proveedor: {$razon} ({$rif})");
            } else {
                // Nuevo registro
                $stmtChk = $this->db->prepare("SELECT rif FROM proveedores WHERE rif = ?");
                $stmtChk->execute([$rif]);
                if ($stmtChk->fetch()) {
                    echo "<script>alert('El RIF {$rif} ya se encuentra registrado.'); window.history.back();</script>";
                    exit();
                }

                $stmt = $this->db->prepare("
                    INSERT INTO proveedores (rif, razon_social, contacto, telefono, correo, direccion, estado)
                    VALUES (?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([$rif, $razon, $contacto, $telefono, $correo, $direccion]);

                $this->log('Proveedores', 'INSERT', "Registró nuevo proveedor: {$razon} ({$rif})");
            }

            header("Location: index.php?action=" . urlencode($volver));
            exit();
        } catch (Exception $e) {
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
            exit();
        }
    }

    public function cambiarEstado() {
        $rif = trim((string)($_POST['rif'] ?? $_POST['id'] ?? $_GET['rif'] ?? $_GET['id'] ?? ''));
        $nuevoEstado = isset($_POST['estado']) ? (int)$_POST['estado'] : null;

        if (!empty($rif)) {
            if ($nuevoEstado !== null) {
                $stmt = $this->db->prepare("UPDATE proveedores SET estado = ? WHERE rif = ?");
                $stmt->execute([$nuevoEstado, $rif]);
            } else {
                $stmt = $this->db->prepare("UPDATE proveedores SET estado = IF(estado=1, 0, 1) WHERE rif = ?");
                $stmt->execute([$rif]);
            }
            $this->log('Proveedores', 'UPDATE', "Cambió estado del proveedor RIF: {$rif}");
        }

        header("Location: index.php?action=proveedores");
        exit();
    }
}