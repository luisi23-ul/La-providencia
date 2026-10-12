<?php

class MasterController {
    private $db;
    private $modelo;
    private $modeloConfig;

    public function __construct($db) {
        $this->db = $db;
        require_once 'models/UsuarioModels.php';
        require_once 'models/ConfiguracionModel.php';
        $this->modelo = new UsuarioModels($db);
        $this->modeloConfig = new ConfiguracionModel($db);
    }

    /* =========================================================================
       1. DASHBOARD MASTER
       ========================================================================= */
    public function mostrarDashboard_master() {
        date_default_timezone_set('America/Caracas');
        $hoy = date('Y-m-d');

        // Personal activo y total (excluyendo clientes con id_rol = 3)
        $personalActivo = 0;
        $totalCuentas = 0;
        try {
            $sqlPersonal = "SELECT 
                                COUNT(id) AS total,
                                SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END) AS activos
                            FROM usuarios 
                            WHERE id_rol != 3";
            $stmtP = $this->db->query($sqlPersonal);
            $resP = $stmtP->fetch(PDO::FETCH_ASSOC);
            if ($resP) {
                $totalCuentas = (int)$resP['total'];
                $personalActivo = (int)($resP['activos'] ?? 0);
            }
        } catch (Exception $e) {}

        // Listado de nombres de roles (usando nombre_rol)
        $roles = [];
        try {
            $stmtRoles = $this->db->query("SELECT nombre_rol FROM roles WHERE id != 3 ORDER BY id ASC");
            $roles = $stmtRoles->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (Exception $e) {}

        // Acciones registradas hoy en auditoría
        $accionesHoy = 0;
        try {
            $stmtAud = $this->db->prepare("SELECT COUNT(id) FROM auditoria_logs WHERE DATE(fecha) = ?");
            $stmtAud->execute([$hoy]);
            $accionesHoy = (int)$stmtAud->fetchColumn();
        } catch (Exception $e) {}

        // Ventas de hoy
        $ventasHoy = 0.0;
        try {
            $sqlVentas = "SELECT COALESCE(SUM(total), 0) 
                          FROM ventas 
                          WHERE (DATE(fecha) = ? OR DATE(fecha) = CURDATE())
                            AND (estado IN ('retirado', 'pagado', 'completada', 'aprobada') OR estado IS NULL)";
            $stmtV = $this->db->prepare($sqlVentas);
            $stmtV->execute([$hoy]);
            $ventasHoy = (float)$stmtV->fetchColumn();
        } catch (Exception $e) {}

        $resumen = [
            'personal_activo' => $personalActivo,
            'cuentas'         => $totalCuentas,
            'roles'           => $roles,
            'acciones_hoy'    => $accionesHoy,
            'ventas_hoy'      => $ventasHoy
        ];

        // Actividad reciente de auditoría (últimas 5)
        $actividad = [];
        try {
            $sqlAct = "SELECT a.*, COALESCE(u.nombre, 'Sistema') AS usuario
                       FROM auditoria_logs a
                       LEFT JOIN usuarios u ON a.id_usuario = u.id
                       ORDER BY a.fecha DESC
                       LIMIT 5";
            $stmtAct = $this->db->query($sqlAct);
            $actividad = $stmtAct->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {}

        include 'views/dashboard_master.php';
    }

    /* =========================================================================
       2. GESTIÓN DE ADMINISTRADORES (CRUD)
       ========================================================================= */
    public function gestionarAdmins() {
        if (!isset($_SESSION['rol']) || ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2)) {
            die("Acceso denegado: No tienes permisos para gestionar administradores.");
        }

        try {
            $sql = "SELECT u.*, r.nombre_rol AS rol_nombre 
                    FROM usuarios u
                    LEFT JOIN roles r ON u.id_rol = r.id
                    WHERE u.id_rol != 3
                    ORDER BY u.id DESC";
            $stmt = $this->db->query($sql);
            $admins = $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            $admins = $this->modelo->obtenerUsuariosPorRol(2);
        }

        include 'views/gestionar_admins.php';
    }

    public function listarAdministradores() {
        $this->gestionarAdmins();
    }

    public function nuevoAdmin() {
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
            die("Acceso denegado.");
        }
        $roles = $this->db->query("SELECT * FROM roles WHERE id != 3")->fetchAll(PDO::FETCH_OBJ);
        include 'views/nuevo_admin.php';
    }

    public function crearAdmin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($_POST['nombre'])) {
                die("ERROR: El formulario no envió el campo 'nombre'.");
            }

            $permisos = isset($_POST['permisos']) && is_array($_POST['permisos']) 
                ? implode(',', $_POST['permisos']) 
                : ($_POST['permisos'] ?? '');

            $datos = [
                "nombre"   => trim($_POST['nombre']),
                "correo"   => trim($_POST['correo']),
                "clave"    => password_hash($_POST['clave'], PASSWORD_DEFAULT),
                "rol"      => $_POST['id_rol'] ?? 2,
                "permisos" => $permisos
            ];

            $this->modelo->registrarUsuarioModel($datos);
            $this->registrarAuditoria("Creación de usuario administrador: {$datos['correo']}", "Personal");
            
            header("Location: index.php?action=gestionar_admins");
            exit();
        }
    }

    public function editarAdmin() {
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
            die("Acceso denegado.");
        }

        $id = $_GET['id'] ?? null;
        $a = $this->modelo->obtenerUsuarioPorId($id); 

        if (!$a) {
            die("Error: No se encontró el usuario con ID $id");
        }

        $roles = $this->db->query("SELECT * FROM roles WHERE id != 3")->fetchAll(PDO::FETCH_OBJ);
        include 'views/editar_admin.php'; 
    }

    public function actualizarAdmin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $nombre = trim($_POST['nombre']);
            $correo = trim($_POST['correo']);
            $clave = $_POST['clave'] ?? '';
            $permisos = isset($_POST['permisos']) && is_array($_POST['permisos']) 
                ? implode(',', $_POST['permisos']) 
                : ($_POST['permisos'] ?? '');

            $datos = [
                'id'       => $id, 
                'nombre'   => $nombre, 
                'correo'   => $correo, 
                'clave'    => $clave, 
                'permisos' => $permisos
            ];

            $this->modelo->actualizarAdminModel($datos);
            $this->registrarAuditoria("Actualización de datos para el administrador: {$correo}", "Personal");

            header("Location: index.php?action=gestionar_admins");
            exit();
        }
    }

    public function toggleAdmin() {
        if (!isset($_GET['id']) || !isset($_GET['estado'])) {
            die("Parámetros faltantes.");
        }

        $id = intval($_GET['id']);
        $estado = intval($_GET['estado']);

        $this->modelo->actualizarEstadoUsuario($id, $estado);
        $this->registrarAuditoria("Cambio de estado del usuario ID {$id} a {$estado}", "Personal");

        header("Location: index.php?action=gestionar_admins");
        exit();
    }

    public function eliminarAdmin() {
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $this->modelo->eliminarAdminModel($id);
            $this->registrarAuditoria("Eliminación del administrador ID {$id}", "Personal");
        }
        
        header("Location: index.php?action=gestionar_admins");
        exit();
    }

    /* =========================================================================
       3. CONFIGURACIÓN DEL SISTEMA
       ========================================================================= */
    public function vistaConfiguracion() {
        $config = $this->modeloConfig->obtener();
        include 'views/configuracion_sistema.php';
    }

    public function editarConfiguracion() {
        $this->vistaConfiguracion();
    }

    public function actualizarConfiguracion() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once 'models/ConfiguracionModel.php';
            $modeloConfig = new ConfiguracionModel($this->db);

            $datos = $_POST;

            // Procesar modo de tasa y valor manual
            $datos['tasa_modo'] = $_POST['tasa_modo'] ?? 'bcv';
            $datos['tasa_manual'] = (float) str_replace(',', '.', $_POST['tasa_manual'] ?? 0);

            // Manejo de la subida de logo si se seleccionó uno nuevo
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $nombreArchivo = 'logo_' . time() . '.png';
                $rutaDestino = 'public/img/' . $nombreArchivo;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $rutaDestino)) {
                    $datos['logo_path'] = $rutaDestino;
                }
            } else {
                $datos['logo_path'] = $_POST['logo_path_actual'] ?? '';
            }

            // Si se eligió modo automático BCV, actualizar tasa inmediatamente
            if ($datos['tasa_modo'] === 'bcv') {
                require_once 'models/BcvService.php';
                $bcvService = new BcvService($this->db);
                $nuevaTasa = $bcvService->consultarApiBcv();
                if ($nuevaTasa > 0) {
                    $bcvService->actualizarTasaBcvEnBD($nuevaTasa);
                }
            }

            // Guardar en la tabla sistema
            $modeloConfig->actualizar($datos);

            // Registrar en auditoría
            $modoTexto = ($datos['tasa_modo'] === 'manual') ? "manual ({$datos['tasa_manual']} Bs)" : "automática (BCV)";
            $this->registrarAuditoria("Actualizó la configuración del sistema (Tasa: {$modoTexto})", "Configuración", "UPDATE");

            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['aviso'] = ['Configuración guardada correctamente.', 'ok'];

            header("Location: index.php?action=configuracion_sistema");
            exit();
        }
    }
    /* =========================================================================
       4. ROLES Y PERMISOS
       ========================================================================= */
    public function rolesPermisos() {
        $roles = [];
        $permisos = [];
        $rolPermisos = [];

        try {
            // 1. Roles con su conteo de usuarios
            $sqlRoles = "SELECT r.id, r.nombre_rol, COUNT(u.id) AS total_usuarios 
                         FROM roles r
                         LEFT JOIN usuarios u ON u.id_rol = r.id AND u.estado = 1
                         WHERE r.id != 3
                         GROUP BY r.id, r.nombre_rol
                         ORDER BY r.id ASC";
            $roles = $this->db->query($sqlRoles)->fetchAll(PDO::FETCH_OBJ);

            // 2. Catálogo de permisos agrupables por módulo
            $permisos = $this->db->query("SELECT * FROM permisos ORDER BY modulo ASC, id ASC")->fetchAll(PDO::FETCH_OBJ);

            // 3. Matriz de rol_permisos para activar checkboxes
            $stmtRP = $this->db->query("SELECT id_rol, id_permiso FROM rol_permisos");
            $asignaciones = $stmtRP->fetchAll(PDO::FETCH_OBJ);
            foreach ($asignaciones as $row) {
                $rolPermisos[$row->id_rol][] = (int)$row->id_permiso;
            }
        } catch (Exception $e) {}

        include 'views/master/roles_permisos.php';
    }

    public function guardarRol() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombreRol = trim($_POST['nombre_rol'] ?? '');
            if (!empty($nombreRol)) {
                $stmt = $this->db->prepare("INSERT INTO roles (nombre_rol) VALUES (?)");
                $stmt->execute([$nombreRol]);
                $this->registrarAuditoria("Creación del rol: {$nombreRol}", "Roles y Permisos");
            }
            header("Location: index.php?action=roles_permisos");
            exit();
        }
    }

    public function guardarPermisos() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rolesModificados = $_POST['roles'] ?? [];
            $permisosRecibidos = $_POST['permisos'] ?? [];

            foreach ($rolesModificados as $idRol) {
                $idRol = (int)$idRol;
                if ($idRol === 1 || $idRol === 3) continue; // Master y Cliente fijos

                // Limpiar permisos anteriores
                $stmtDelete = $this->db->prepare("DELETE FROM rol_permisos WHERE id_rol = ?");
                $stmtDelete->execute([$idRol]);

                // Insertar los permisos marcados
                if (isset($permisosRecibidos[$idRol]) && is_array($permisosRecibidos[$idRol])) {
                    $stmtInsert = $this->db->prepare("INSERT INTO rol_permisos (id_rol, id_permiso) VALUES (?, ?)");
                    foreach ($permisosRecibidos[$idRol] as $idPermiso) {
                        $stmtInsert->execute([$idRol, (int)$idPermiso]);
                    }
                }
            }

            $this->registrarAuditoria("Actualización de la matriz de permisos por roles", "Roles y Permisos");
            header("Location: index.php?action=roles_permisos");
            exit();
        }
    }

    /* =========================================================================
       5. AUDITORÍA DEL SISTEMA
       ========================================================================= */
    public function auditoria() {
        date_default_timezone_set('America/Caracas');
        
        $q             = trim($_GET['q'] ?? '');
        $usuarioFiltro = trim($_GET['usuario'] ?? '');
        $moduloFiltro  = trim($_GET['modulo'] ?? '');
        $desde         = $_GET['desde'] ?? date('Y-m-d', strtotime('-6 days'));
        $hasta         = $_GET['hasta'] ?? date('Y-m-d');

        // 1. Cargar usuarios del personal para el selector de filtro
        $usuarios = [];
        try {
            $stmtU = $this->db->query("SELECT id, nombre, apellido FROM usuarios WHERE id_rol != 3 ORDER BY nombre ASC");
            $usuarios = $stmtU->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {}

        // 2. Módulos disponibles en el sistema
        $modulos = ['Ventas', 'Pagos', 'Inventario', 'Compras', 'Personal', 'Configuración', 'Roles y Permisos', 'Sesión'];

        // 3. Consultar los registros con filtros dinámicos
        $registros = [];
        try {
            $sql = "SELECT a.*, COALESCE(u.nombre, 'Sistema') AS usuario 
                    FROM auditoria_logs a
                    LEFT JOIN usuarios u ON a.id_usuario = u.id
                    WHERE DATE(a.fecha) >= ? AND DATE(a.fecha) <= ?";
            $params = [$desde, $hasta];

            if ($q !== '') {
                $sql .= " AND a.descripcion LIKE ?";
                $params[] = '%' . $q . '%';
            }

            if ($usuarioFiltro !== '') {
                $sql .= " AND a.id_usuario = ?";
                $params[] = (int)$usuarioFiltro;
            }

            if ($moduloFiltro !== '') {
                $sql .= " AND a.modulo = ?";
                $params[] = $moduloFiltro;
            }

            $sql .= " ORDER BY a.fecha DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $registros = $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {}

        include 'views/master/auditoria.php';
    }
    /**
     * Registra un evento en la tabla auditoria_logs
     */
    private function registrarAuditoria($descripcion, $modulo = 'General', $accion = 'UPDATE') {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $idUsuario = $_SESSION['id_usuario'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            if ($ip === '::1') {
                $ip = '127.0.0.1';
            }

            $sql = "INSERT INTO auditoria_logs (id_usuario, modulo, accion, descripcion, ip, fecha) 
                    VALUES (:id_usuario, :modulo, :accion, :descripcion, :ip, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_usuario'   => $idUsuario,
                ':modulo'       => $modulo,
                ':accion'       => $accion,
                ':descripcion'  => $descripcion,
                ':ip'           => $ip
            ]);
        } catch (Exception $e) {
            // Evitar interrumpir la ejecución si falla el log
        }
    }
}
