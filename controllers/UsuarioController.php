<?php
class UsuarioController {
    private $db;
    private $modelo;

    public function __construct($db) {
        $this->db = $db;
        require_once 'models/UsuarioModels.php';
        $this->modelo = new UsuarioModels($db);
    }

    public function mostrarLogin() {
        include 'views/login.php';
    }

    public function mostrarInicio() {
        include 'views/inicio.php';
    }

    public function mostrarDashboard() {
        include 'views/dashboard.php';
    }

    public function mostrarDashboard_master() {
        include 'views/dashboard_master.php';
    }

    public function validarLogin() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = trim($_POST['email'] ?? '');
            $pass  = trim($_POST['password'] ?? '');

            $datos = array("correo" => $email);
            $usuario = $this->modelo->buscarUsuarioModel($datos);

            $claveValida = false;
            if ($usuario) {
                // 1. Coincide con texto plano
                if ($pass === $usuario["clave"]) {
                    $claveValida = true;
                    // Genera hash real y lo guarda
                    $nuevoHash = password_hash($pass, PASSWORD_BCRYPT);
                    $stmtUpdate = $this->db->prepare("UPDATE usuarios SET clave = ? WHERE cedula = ?");
                    $stmtUpdate->execute([$nuevoHash, $usuario["cedula"]]);
                } 
                // 2. Coincide con hash Bcrypt
                elseif (password_verify($pass, $usuario["clave"])) {
                    $claveValida = true;
                }
            }

            if ($usuario && $claveValida) {
                if (isset($usuario["estado"]) && (int)$usuario["estado"] === 0) {
                    echo "<script>alert('Tu cuenta está desactivada.'); window.location.href='index.php?action=login';</script>";
                    exit();
                }

                if (session_status() == PHP_SESSION_NONE) session_start();

                $_SESSION["cedula"]     = $usuario["cedula"];
                $_SESSION["id_usuario"] = $usuario["cedula"]; 
                $_SESSION["codigo_rol"] = $usuario["codigo_rol"];
                $_SESSION["rol"]        = $usuario["codigo_rol"]; 
                $_SESSION["nombre"]     = $usuario["nombre"];
                $_SESSION["apellido"]   = $usuario["apellido"] ?? '';

                try {
                    $stmtR = $this->db->prepare("SELECT nombre_rol FROM roles WHERE codigo_rol = ?");
                    $stmtR->execute([$usuario["codigo_rol"]]);
                    $_SESSION["nombre_rol"] = $stmtR->fetchColumn() ?: ucfirst($usuario["codigo_rol"]);
                } catch (Exception $e) {
                    $_SESSION["nombre_rol"] = ucfirst($usuario["codigo_rol"]);
                }

                if (strtolower($usuario["codigo_rol"]) === 'master') {
                    $_SESSION["permisos"] = ['*'];
                } else {
                    $sqlPermisos = "SELECT codigo_permiso FROM rol_permisos WHERE codigo_rol = ?";
                    $stmtPerm = $this->db->prepare($sqlPermisos);
                    $stmtPerm->execute([$usuario["codigo_rol"]]);
                    $listaPermisos = $stmtPerm->fetchAll(PDO::FETCH_COLUMN) ?: [];

                    $sqlUserPerm = "SELECT codigo_permiso FROM usuario_permisos WHERE cedula = ?";
                    $stmtUP = $this->db->prepare($sqlUserPerm);
                    $stmtUP->execute([$usuario["cedula"]]);
                    $permisosExtras = $stmtUP->fetchAll(PDO::FETCH_COLUMN) ?: [];

                    $_SESSION["permisos"] = array_unique(array_merge($listaPermisos, $permisosExtras));
                }

                $rol = strtolower(trim($usuario['codigo_rol']));
                if ($rol === 'master') {
                    header("Location: index.php?action=dashboard_master");
                } elseif ($rol !== 'cliente') {
                    header("Location: index.php?action=dashboard");
                } else {
                    header("Location: index.php?action=inicio");
                }
                exit();

            } else {
                echo "<script>alert('Correo o contraseña incorrectos.'); window.location.href='index.php?action=login';</script>";
                exit();
            }
        }
    }

    public function mostrarRegistro() {
        include 'views/registro.php';
    }

    public function guardarCliente() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=login_usuario");
            exit();
        }

        $nombre    = trim($_POST['nombre'] ?? 'Cliente');
        $apellido  = trim($_POST['apellido'] ?? '');
        $cedula    = trim($_POST['cedula'] ?? $_POST['documento'] ?? '');
        $telefono  = trim($_POST['telefono'] ?? $_POST['celular'] ?? '');
        $correo    = trim($_POST['correo'] ?? $_POST['email'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $clave     = $_POST['clave'] ?? $_POST['password'] ?? '123456';
        $claveHash = password_hash($clave, PASSWORD_BCRYPT);

        if (empty($cedula)) {
            $cedula = 'V-' . time();
        }

        // Verificar si el usuario ya existe por correo o cédula
        if (!empty($correo)) {
            $stmtCheck = $this->db->prepare("SELECT cedula, nombre, codigo_rol FROM usuarios WHERE correo = ? OR cedula = ? LIMIT 1");
            $stmtCheck->execute([$correo, $cedula]);
            $existente = $stmtCheck->fetch(PDO::FETCH_OBJ);

            if ($existente) {
                if (session_status() == PHP_SESSION_NONE) session_start();
                $_SESSION['cedula']     = $existente->cedula;
                $_SESSION['id_usuario'] = $existente->cedula;
                $_SESSION['nombre']     = $existente->nombre;
                $_SESSION['rol']        = $existente->codigo_rol;
                $_SESSION['codigo_rol'] = $existente->codigo_rol;

                header("Location: index.php?action=ver_carrito");
                exit();
            }
        }

        try {
            // Inserción en usuarios con 100% NOT NULL (cadenas vacías por defecto)
            $sql = "INSERT INTO usuarios (cedula, nombre, apellido, telefono, direccion, correo, clave, codigo_rol, estado) 
                    VALUES (:cedula, :nombre, :apellido, :telefono, :direccion, :correo, :clave, 'cliente', 1)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':cedula'    => $cedula,
                ':nombre'    => $nombre,
                ':apellido'  => $apellido,
                ':telefono'  => $telefono,
                ':direccion' => $direccion,
                ':correo'    => $correo,
                ':clave'     => $claveHash
            ]);

            // Sincronizar simultáneamente en la tabla clientes
            $sqlCliente = "INSERT INTO clientes (cedula, nombre_razon_social, telefono, correo, direccion, cedula_usuario, estado)
                           VALUES (?, ?, ?, ?, ?, ?, 1)
                           ON DUPLICATE KEY UPDATE nombre_razon_social = VALUES(nombre_razon_social), telefono = VALUES(telefono)";
            $stmtCli = $this->db->prepare($sqlCliente);
            $stmtCli->execute([
                $cedula,
                trim($nombre . ' ' . $apellido),
                $telefono,
                $correo,
                $direccion,
                $cedula
            ]);

            if (session_status() == PHP_SESSION_NONE) session_start();
            $_SESSION['cedula']     = $cedula;
            $_SESSION['id_usuario'] = $cedula;
            $_SESSION['nombre']     = $nombre;
            $_SESSION['rol']        = 'cliente';
            $_SESSION['codigo_rol'] = 'cliente';

            header("Location: index.php?action=ver_carrito");
            exit();

        } catch (PDOException $e) {
            if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "<script>
                        alert('El correo o cédula ya se encuentra registrado. Por favor inicia sesión.');
                        window.location.href = 'index.php?action=login_usuario';
                      </script>";
                exit();
            }

            die("Error al registrar cliente: " . $e->getMessage());
        }
    }

    public function mostrarLogin_registro() {
        include "views/login_registro.php";
    }

   public function ingresar() {
        if (isset($_POST["correo_ingreso"])) {
            $correo = trim($_POST["correo_ingreso"]);
            $pass   = trim($_POST["clave_ingreso"] ?? '');

            $datos = array("correo" => $correo);
            $usuario = $this->modelo->buscarUsuarioModel($datos);

            $claveValida = false;
            if ($usuario) {
                if (password_verify($pass, $usuario["clave"])) {
                    $claveValida = true;
                } elseif ($pass === $usuario["clave"]) {
                    $claveValida = true;
                    $nuevoHash = password_hash($pass, PASSWORD_BCRYPT);
                    $stmtUpdate = $this->db->prepare("UPDATE usuarios SET clave = ? WHERE cedula = ?");
                    $stmtUpdate->execute([$nuevoHash, $usuario["cedula"]]);
                }
            }

            if ($usuario && $claveValida) {

                if ((int)$usuario["estado"] === 0) {
                    echo "<script>
                            alert('Tu cuenta está desactivada. Contacta al administrador.'); 
                            window.location.href = 'index.php?action=mostrarLogin_registro';
                          </script>";
                    exit();
                }

                if (session_status() == PHP_SESSION_NONE) session_start();
                $_SESSION["cedula"]     = $usuario["cedula"];
                $_SESSION["id_usuario"] = $usuario["cedula"];
                $_SESSION["codigo_rol"] = $usuario["codigo_rol"];
                $_SESSION["rol"]        = $usuario["codigo_rol"];
                $_SESSION["nombre"]     = $usuario["nombre"];

                try {
                    $stmtR = $this->db->prepare("SELECT nombre_rol FROM roles WHERE codigo_rol = ?");
                    $stmtR->execute([$usuario["codigo_rol"]]);
                    $_SESSION["nombre_rol"] = $stmtR->fetchColumn() ?: ucfirst($usuario["codigo_rol"]);
                } catch (Exception $e) {
                    $_SESSION["nombre_rol"] = ucfirst($usuario["codigo_rol"]);
                }

                if (strtolower($usuario["codigo_rol"]) === 'master') {
                    $_SESSION["permisos"] = ['*'];
                } else {
                    $sqlPermisos = "SELECT codigo_permiso FROM rol_permisos WHERE codigo_rol = ?";
                    $stmtPerm = $this->db->prepare($sqlPermisos);
                    $stmtPerm->execute([$usuario["codigo_rol"]]);
                    $listaPermisos = $stmtPerm->fetchAll(PDO::FETCH_COLUMN) ?: [];

                    $sqlUserPerm = "SELECT codigo_permiso FROM usuario_permisos WHERE cedula = ?";
                    $stmtUP = $this->db->prepare($sqlUserPerm);
                    $stmtUP->execute([$usuario["cedula"]]);
                    $permisosExtras = $stmtUP->fetchAll(PDO::FETCH_COLUMN) ?: [];

                    $_SESSION["permisos"] = array_unique(array_merge($listaPermisos, $permisosExtras));
                }

                echo "<script>
                        alert('¡Bienvenido(a) a La Providencia, " . $usuario["nombre"] . "!');
                        window.location.href = 'index.php?action=ver_catalogo';
                      </script>";
                exit();
            } else {
                echo "<script>
                        alert('Error: El correo o la contraseña no coinciden.');
                        window.location.href = 'index.php?action=inicio';
                      </script>";
                exit();
            }
        }
    }

    public function verDetalle($numero_orden) {
        if (!empty($numero_orden)) {
            $venta = $this->modelo->obtenerVenta($numero_orden);
            $detalles = $this->modelo->obtenerDetalles($numero_orden);
            include "views/detalle_venta.php";
        } else {
            echo "<script>alert('Número de orden no válido.'); window.location.href='index.php?action=pagos_pendientes';</script>";
            exit();
        }
    }

    public function listarClientes() {
        try {
            $sql = "SELECT 
                        u.*,
                        COUNT(DISTINCT v.numero_orden) AS compras,
                        COALESCE(SUM(v.total), 0) AS total_compras
                    FROM usuarios u
                    LEFT JOIN ventas v 
                           ON (v.cedula_cliente = u.cedula OR v.cedula_usuario = u.cedula)
                          AND (v.estado IN ('retirado', 'pagado', 'completada', 'completado', 'aprobada'))
                    WHERE u.codigo_rol = 'cliente'
                    GROUP BY u.cedula
                    ORDER BY u.nombre ASC";

            $stmt = $this->db->query($sql);
            $clientes = $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            $clientes = $this->modelo->obtenerClientes();
        }

        require_once 'views/admin_clientes.php';
    }

    public function cambiarEstadoCliente() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $cedula = $_GET['id'] ?? $_GET['cedula'] ?? '';
            $estado = $_GET['estado'] ?? 1; 

            $this->modelo->actualizarEstadoUsuario($cedula, $estado);

            header("Location: index.php?action=lista_clientes");
            exit();
        }
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