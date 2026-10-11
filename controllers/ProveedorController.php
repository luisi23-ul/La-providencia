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
            $idUsuario = $_SESSION['id_usuario'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $this->db->prepare("INSERT INTO auditoria_logs (id_usuario, modulo, accion, descripcion, ip) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$idUsuario, $modulo, $accion, $descripcion, $ip]);
        } catch (Exception $e) {}
    }

    public function listar() {
        $sql = "SELECT id, rif_cedula, razon_social, telefono, correo, direccion, estado,
                       (SELECT COUNT(*) FROM compras c WHERE c.id_proveedor = p.id) AS total_compras
                FROM proveedores p
                ORDER BY razon_social ASC";
        $stmt = $this->db->query($sql);
        $proveedores = $stmt->fetchAll(PDO::FETCH_OBJ);

        require_once 'views/compras/proveedores.php';
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=proveedores");
            exit();
        }

        $id        = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
        $rif       = strtoupper(trim($_POST['rif_cedula'] ?? ''));
        $razon     = trim($_POST['razon_social'] ?? '');
        $telefono  = trim($_POST['telefono'] ?? '');
        $correo    = trim($_POST['correo'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $volver    = trim($_POST['volver'] ?? 'proveedores');

        if (empty($rif) || empty($razon)) {
            echo "<script>alert('El RIF/Cédula y la Razón Social son obligatorios.'); window.history.back();</script>";
            exit();
        }

        try {
            if ($id > 0) {
                $stmtChk = $this->db->prepare("SELECT id FROM proveedores WHERE rif_cedula = ? AND id != ?");
                $stmtChk->execute([$rif, $id]);
                if ($stmtChk->fetch()) {
                    echo "<script>alert('El RIF o cédula {$rif} ya pertenece a otro proveedor.'); window.history.back();</script>";
                    exit();
                }

                $stmt = $this->db->prepare("
                    UPDATE proveedores 
                    SET rif_cedula = ?, razon_social = ?, telefono = ?, correo = ?, direccion = ?
                    WHERE id = ?
                ");
                $stmt->execute([$rif, $razon, $telefono, $correo, $direccion, $id]);

                $this->log('Proveedores', 'UPDATE', "Actualizó proveedor ID {$id}: {$razon}");
            } else {
                $stmtChk = $this->db->prepare("SELECT id FROM proveedores WHERE rif_cedula = ?");
                $stmtChk->execute([$rif]);
                if ($stmtChk->fetch()) {
                    echo "<script>alert('El RIF o cédula {$rif} ya se encuentra registrado.'); window.history.back();</script>";
                    exit();
                }

                $stmt = $this->db->prepare("
                    INSERT INTO proveedores (rif_cedula, razon_social, telefono, correo, direccion, estado)
                    VALUES (?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([$rif, $razon, $telefono, $correo, $direccion]);

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
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        $nuevoEstado = isset($_POST['estado']) ? (int)$_POST['estado'] : null;

        if ($id > 0) {
            if ($nuevoEstado !== null) {
                $stmt = $this->db->prepare("UPDATE proveedores SET estado = ? WHERE id = ?");
                $stmt->execute([$nuevoEstado, $id]);
            } else {
                $stmt = $this->db->prepare("UPDATE proveedores SET estado = IF(estado=1, 0, 1) WHERE id = ?");
                $stmt->execute([$id]);
            }
            $this->log('Proveedores', 'UPDATE', "Cambió estado del proveedor ID: {$id}");
        }

        header("Location: index.php?action=proveedores");
        exit();
    }
}