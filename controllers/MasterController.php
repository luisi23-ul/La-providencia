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

        // Personal activo y total (excluyendo clientes)
        $personalActivo = 0;
        $totalCuentas = 0;
        try {
            $sqlPersonal = "SELECT 
                                COUNT(cedula) AS total,
                                SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END) AS activos
                            FROM usuarios 
                            WHERE codigo_rol != 'cliente' AND cedula != 'SIN_USUARIO'";
            $stmtP = $this->db->query($sqlPersonal);
            $resP = $stmtP->fetch(PDO::FETCH_ASSOC);
            if ($resP) {
                $totalCuentas = (int)$resP['total'];
                $personalActivo = (int)($resP['activos'] ?? 0);
            }
        } catch (Exception $e) {}

        // Listado de nombres de roles de personal
        $roles = [];
        try {
            $stmtRoles = $this->db->query("SELECT nombre_rol FROM roles WHERE codigo_rol != 'cliente' ORDER BY nombre_rol ASC");
            $roles = $stmtRoles->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (Exception $e) {}

        // Acciones registradas hoy en auditoría
        $accionesHoy = 0;
        try {
            $stmtAud = $this->db->prepare("SELECT COUNT(*) FROM auditoria_logs WHERE DATE(fecha_hora) = ?");
            $stmtAud->execute([$hoy]);
            $accionesHoy = (int)$stmtAud->fetchColumn();
        } catch (Exception $e) {}

        // Ventas de hoy
        $ventasHoy = 0.0;
        try {
            $sqlVentas = "SELECT COALESCE(SUM(total), 0) 
                          FROM ventas 
                          WHERE DATE(fecha) = ? 
                            AND estado IN ('retirado', 'pagado', 'completada', 'completado', 'aprobada')";
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
            $sqlAct = "SELECT a.cedula, a.numero_registro, a.modulo, a.accion, 
                              a.detalle_cambio AS descripcion, a.ip_terminal AS ip, 
                              a.fecha_hora AS fecha,
                              COALESCE(u.nombre, 'Sistema') AS usuario
                       FROM auditoria_logs a
                       LEFT JOIN usuarios u ON a.cedula = u.cedula
                       ORDER BY a.fecha_hora DESC
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
        $rolActual = strtolower($_SESSION['codigo_rol'] ?? $_SESSION['rol'] ?? '');
        if ($rolActual !== 'master' && $rolActual !== 'administrador') {
            die("Acceso denegado: No tienes permisos para gestionar administradores.");
        }

        try {
            $sql = "SELECT u.*, u.cedula AS id, r.nombre_rol AS rol_nombre 
                    FROM usuarios u
                    LEFT JOIN roles r ON u.codigo_rol = r.codigo_rol
                    WHERE u.codigo_rol != 'cliente' AND u.cedula != 'SIN_USUARIO'
                    ORDER BY u.nombre ASC";
            $stmt = $this->db->query($sql);
            $admins = $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            $admins = $this->modelo->obtenerUsuariosPorRol('administrador');
        }

        include 'views/gestionar_admins.php';
    }

    public function listarAdministradores() {
        $this->gestionarAdmins();
    }

    public function nuevoAdmin() {
        $rolActual = strtolower($_SESSION['codigo_rol'] ?? $_SESSION['rol'] ?? '');
        if ($rolActual !== 'master') {
            die("Acceso denegado.");
        }
        $roles = $this->db->query("SELECT codigo_rol AS id, codigo_rol, nombre_rol FROM roles WHERE codigo_rol != 'cliente'")->fetchAll(PDO::FETCH_OBJ);
        include 'views/nuevo_admin.php';
    }

    public function crearAdmin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre   = trim($_POST['nombre'] ?? '');
            $apellido = trim($_POST['apellido'] ?? '');
            $correo   = trim($_POST['correo'] ?? '');
            $cedula   = trim($_POST['cedula'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $codigoRol= trim($_POST['codigo_rol'] ?? $_POST['id_rol'] ?? 'administrador');
            $clave    = $_POST['clave'] ?? '123456';

            if (empty($nombre) || empty($correo)) {
                die("ERROR: Nombre y correo son requeridos.");
            }

            if (empty($cedula)) {
                $cedula = 'V-' . rand(10000000, 99999999);
            }

            $datos = [
                "cedula"     => $cedula,
                "nombre"     => $nombre,
                "apellido"   => $apellido,
                "telefono"   => $telefono,
                "direccion"  => '',
                "correo"     => $correo,
                "clave"      => password_hash($clave, PASSWORD_BCRYPT),
                "codigo_rol" => $codigoRol,
                "estado"     => 1
            ];

            $this->modelo->registrarUsuarioModel($datos);

            // Permisos específicos asignados directamente al usuario
            if (!empty($_POST['permisos']) && is_array($_POST['permisos'])) {
                $stmtUP = $this->db->prepare("INSERT INTO usuario_permisos (cedula, codigo_permiso) VALUES (?, ?) ON DUPLICATE KEY UPDATE codigo_permiso = codigo_permiso");
                foreach ($_POST['permisos'] as $perm) {
                    $stmtUP->execute([$cedula, trim($perm)]);
                }
            }

            $this->registrarAuditoria("Creación de usuario administrador: {$correo}", "Personal", "INSERT");
            
            header("Location: index.php?action=gestionar_admins");
            exit();
        }
    }

    public function editarAdmin() {
        $rolActual = strtolower($_SESSION['codigo_rol'] ?? $_SESSION['rol'] ?? '');
        if ($rolActual !== 'master') {
            die("Acceso denegado.");
        }

        $cedula = $_GET['id'] ?? $_GET['cedula'] ?? '';
        $a = $this->modelo->obtenerUsuarioPorCedula($cedula); 

        if (!$a) {
            die("Error: No se encontró el usuario con cédula/ID: " . htmlspecialchars($cedula));
        }

        $a['id'] = $a['cedula'];
        $a['id_rol'] = $a['codigo_rol'];

        $roles = $this->db->query("SELECT codigo_rol AS id, codigo_rol, nombre_rol FROM roles WHERE codigo_rol != 'cliente'")->fetchAll(PDO::FETCH_OBJ);
        include 'views/editar_admin.php'; 
    }

    public function actualizarAdmin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cedula    = trim($_POST['cedula'] ?? $_POST['id'] ?? '');
            $nombre    = trim($_POST['nombre'] ?? '');
            $apellido  = trim($_POST['apellido'] ?? '');
            $correo    = trim($_POST['correo'] ?? '');
            $codigoRol = trim($_POST['codigo_rol'] ?? $_POST['id_rol'] ?? 'administrador');
            $clave     = $_POST['clave'] ?? '';

            $datos = [
                'cedula'     => $cedula,
                'nombre'     => $nombre,
                'apellido'   => $apellido,
                'correo'     => $correo,
                'codigo_rol' => $codigoRol,
                'clave'      => $clave,
                'estado'     => 1
            ];

            $this->modelo->actualizarAdminModel($datos);

            // Actualizar permisos directos del usuario
            if (isset($_POST['permisos']) && is_array($_POST['permisos'])) {
                $this->db->prepare("DELETE FROM usuario_permisos WHERE cedula = ?")->execute([$cedula]);
                $stmtUP = $this->db->prepare("INSERT INTO usuario_permisos (cedula, codigo_permiso) VALUES (?, ?)");
                foreach ($_POST['permisos'] as $perm) {
                    $stmtUP->execute([$cedula, trim($perm)]);
                }
            }

            $this->registrarAuditoria("Actualización de datos para el usuario: {$correo}", "Personal", "UPDATE");

            header("Location: index.php?action=gestionar_admins");
            exit();
        }
    }

    public function toggleAdmin() {
        $cedula = $_GET['id'] ?? $_GET['cedula'] ?? null;
        if (!$cedula || !isset($_GET['estado'])) {
            die("Parámetros faltantes.");
        }

        $estado = intval($_GET['estado']);

        $this->modelo->actualizarEstadoUsuario($cedula, $estado);
        $this->registrarAuditoria("Cambio de estado del usuario {$cedula} a {$estado}", "Personal", "UPDATE");

        header("Location: index.php?action=gestionar_admins");
        exit();
    }

    public function eliminarAdmin($cedula = null) {
        $cedula = $cedula ?? $_GET['id'] ?? $_GET['cedula'] ?? null;
        if ($cedula) {
            $this->modelo->eliminarAdminModel($cedula);
            $this->registrarAuditoria("Desactivación del administrador {$cedula}", "Personal", "DELETE");
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
            $datos = $_POST;

            $datos['tasa_modo'] = $_POST['tasa_modo'] ?? 'bcv';
            $datos['tasa_manual'] = (float) str_replace(',', '.', $_POST['tasa_manual'] ?? 0);

            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $nombreArchivo = 'logo_' . time() . '.png';
                $rutaDestino = 'public/img/' . $nombreArchivo;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $rutaDestino)) {
                    $datos['logo_path'] = $rutaDestino;
                }
            } else {
                $datos['logo_path'] = $_POST['logo_path_actual'] ?? '';
            }

            if ($datos['tasa_modo'] === 'bcv') {
                if (file_exists('models/BcvService.php')) {
                    require_once 'models/BcvService.php';
                    $bcvService = new BcvService($this->db);
                    $nuevaTasa = $bcvService->consultarApiBcv();
                    if ($nuevaTasa > 0) {
                        $bcvService->actualizarTasaBcvEnBD($nuevaTasa);
                    }
                }
            }

            $this->modeloConfig->actualizar($datos);

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
            // Roles con su conteo de usuarios activos
            $sqlRoles = "SELECT r.codigo_rol, r.codigo_rol AS id, r.nombre_rol, COUNT(u.cedula) AS total_usuarios 
                         FROM roles r
                         LEFT JOIN usuarios u ON u.codigo_rol = r.codigo_rol AND u.estado = 1
                         WHERE r.codigo_rol != 'cliente'
                         GROUP BY r.codigo_rol, r.nombre_rol
                         ORDER BY r.nombre_rol ASC";
            $roles = $this->db->query($sqlRoles)->fetchAll(PDO::FETCH_OBJ);

            // Catálogo de permisos
            $permisos = $this->db->query("SELECT codigo_permiso AS id, codigo_permiso, nombre, modulo FROM permisos ORDER BY modulo ASC, codigo_permiso ASC")->fetchAll(PDO::FETCH_OBJ);

            // Matriz de rol_permisos
            $stmtRP = $this->db->query("SELECT codigo_rol, codigo_permiso FROM rol_permisos");
            $asignaciones = $stmtRP->fetchAll(PDO::FETCH_OBJ);
            foreach ($asignaciones as $row) {
                $rolPermisos[$row->codigo_rol][] = $row->codigo_permiso;
            }
        } catch (Exception $e) {}

        include 'views/master/roles_permisos.php';
    }

    public function guardarRol() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombreRol = trim($_POST['nombre_rol'] ?? '');
            if (!empty($nombreRol)) {
                $codigoRol = strtolower(trim(preg_replace('/[^A-Za-z0-9_]+/', '_', $nombreRol), '_'));
                $stmt = $this->db->prepare("INSERT INTO roles (codigo_rol, nombre_rol, descripcion) VALUES (?, ?, ?)");
                $stmt->execute([$codigoRol, $nombreRol, "Perfil de {$nombreRol}"]);
                $this->registrarAuditoria("Creación del rol: {$nombreRol}", "Roles y Permisos", "INSERT");
            }
            header("Location: index.php?action=roles_permisos");
            exit();
        }
    }

    public function guardarPermisos() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rolesModificados = $_POST['roles'] ?? [];
            $permisosRecibidos = $_POST['permisos'] ?? [];

            foreach ($rolesModificados as $codigoRol) {
                $codigoRol = trim($codigoRol);
                if ($codigoRol === 'master' || $codigoRol === 'cliente') continue;

                $stmtDelete = $this->db->prepare("DELETE FROM rol_permisos WHERE codigo_rol = ?");
                $stmtDelete->execute([$codigoRol]);

                if (isset($permisosRecibidos[$codigoRol]) && is_array($permisosRecibidos[$codigoRol])) {
                    $stmtInsert = $this->db->prepare("INSERT INTO rol_permisos (codigo_rol, codigo_permiso) VALUES (?, ?)");
                    foreach ($permisosRecibidos[$codigoRol] as $codigoPermiso) {
                        $stmtInsert->execute([$codigoRol, trim($codigoPermiso)]);
                    }
                }
            }

            $this->registrarAuditoria("Actualización de la matriz de permisos por roles", "Roles y Permisos", "UPDATE");
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

        // Personal para el filtro
        $usuarios = [];
        try {
            $stmtU = $this->db->query("SELECT cedula AS id, cedula, nombre, apellido FROM usuarios WHERE codigo_rol != 'cliente' AND cedula != 'SIN_USUARIO' ORDER BY nombre ASC");
            $usuarios = $stmtU->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {}

        $modulos = ['Ventas', 'Pagos', 'Inventario', 'Compras', 'Personal', 'Configuración', 'Roles y Permisos', 'Sesión'];

        // Consultar logs usando las columnas exactas del esquema (cedula, numero_registro, modulo, accion, detalle_cambio, ip_terminal, fecha_hora)
        $registros = [];
        try {
            $sql = "SELECT a.cedula, a.numero_registro, a.modulo, a.accion,
                           a.detalle_cambio AS descripcion, a.detalle_cambio,
                           a.ip_terminal AS ip, a.ip_terminal,
                           a.fecha_hora AS fecha, a.fecha_hora,
                           COALESCE(u.nombre, 'Sistema') AS usuario 
                    FROM auditoria_logs a
                    LEFT JOIN usuarios u ON a.cedula = u.cedula
                    WHERE DATE(a.fecha_hora) >= ? AND DATE(a.fecha_hora) <= ?";
            $params = [$desde, $hasta];

            if ($q !== '') {
                $sql .= " AND (a.detalle_cambio LIKE ? OR a.cedula LIKE ?)";
                $params[] = '%' . $q . '%';
                $params[] = '%' . $q . '%';
            }

            if ($usuarioFiltro !== '') {
                $sql .= " AND a.cedula = ?";
                $params[] = $usuarioFiltro;
            }

            if ($moduloFiltro !== '') {
                $sql .= " AND a.modulo = ?";
                $params[] = $moduloFiltro;
            }

            $sql .= " ORDER BY a.fecha_hora DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $registros = $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {}

        include 'views/master/auditoria.php';
    }

    /**
     * Registra un evento formal en auditoria_logs (100% NOT NULL)
     */
    private function registrarAuditoria($descripcion, $modulo = 'General', $accion = 'UPDATE') {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $cedula = $_SESSION['cedula'] ?? $_SESSION['id_usuario'] ?? 'SIN_USUARIO';
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            if ($ip === '::1') {
                $ip = '127.0.0.1';
            }

            // Calcular número correlativo por cédula
            $stmtSec = $this->db->prepare("SELECT COALESCE(MAX(numero_registro), 0) + 1 FROM auditoria_logs WHERE cedula = ?");
            $stmtSec->execute([$cedula]);
            $numeroRegistro = (int)$stmtSec->fetchColumn();

            $sql = "INSERT INTO auditoria_logs (cedula, numero_registro, modulo, accion, detalle_cambio, ip_terminal, fecha_hora) 
                    VALUES (:cedula, :numero_registro, :modulo, :accion, :detalle_cambio, :ip_terminal, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':cedula'          => $cedula,
                ':numero_registro' => $numeroRegistro,
                ':modulo'          => $modulo,
                ':accion'          => $accion,
                ':detalle_cambio'  => $descripcion,
                ':ip_terminal'     => $ip
            ]);
        } catch (Exception $e) {}
    }
}