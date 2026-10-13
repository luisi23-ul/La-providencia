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
            $email = $_POST['email'] ?? '';
            $pass = $_POST['password'] ?? '';

            $datos = array("correo" => $email);
            $objModelo = new UsuarioModels($this->db);
            $usuario = $objModelo->buscarUsuarioModel($datos);

            // Verificar si el usuario existe y la clave coincide
            if ($usuario && password_verify($pass, $usuario["clave"])) {

                if (isset($usuario["estado"]) && (int)$usuario["estado"] === 0) {
                    echo "<script>alert('Tu cuenta está desactivada. Contacta al administrador.'); window.location.href='index.php?action=login';</script>";
                    exit();
                }

                if (session_status() == PHP_SESSION_NONE) session_start();

                $_SESSION["id_usuario"] = $usuario["id"];
                $_SESSION["rol"]        = $usuario["id_rol"];
                $_SESSION["nombre"]     = $usuario["nombre"];

                // Carga dinámica de permisos según rol desde la tabla rol_permisos
                if ((int)$usuario["id_rol"] === 1) {
                    $_SESSION["permisos"] = ['*'];
                } else {
                    $sqlPermisos = "SELECT p.codigo 
                                    FROM rol_permisos rp 
                                    INNER JOIN permisos p ON rp.id_permiso = p.id 
                                    WHERE rp.id_rol = ?";
                    $stmtPerm = $this->db->prepare($sqlPermisos);
                    $stmtPerm->execute([$usuario["id_rol"]]);
                    $listaPermisos = $stmtPerm->fetchAll(PDO::FETCH_COLUMN) ?: [];

                    if (!empty($usuario["permisos"])) {
                        $extras = array_filter(array_map('trim', explode(',', $usuario["permisos"])));
                        $listaPermisos = array_unique(array_merge($listaPermisos, $extras));
                    }

                    $_SESSION["permisos"] = $listaPermisos;
                }

                // Redirección según rol
               $rolId = (int)$_SESSION['rol'];
if ($rolId === 1) {
    // Solo el Master va a la vista de control global
    header("Location: index.php?action=dashboard_master");
} elseif ($rolId !== 3) {
    // CUALQUIER ROL DE PERSONAL (Administrador, Vendedor, Cajero, Almacén, etc.) entra al Panel Admin
    header("Location: index.php?action=dashboard");
} else {
    // Únicamente los Clientes van a la tienda
    header("Location: index.php?action=inicio");
}
exit();

            } else {
                echo "<script>alert('Correo o contraseña incorrectos.'); window.location.href='index.php?action=login';</script>";
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

        // 1. Datos del formulario
        $nombre    = trim($_POST['nombre'] ?? '');
        $apellido  = trim($_POST['apellido'] ?? '');
        $cedula    = trim($_POST['cedula'] ?? $_POST['documento'] ?? '');
        $telefono  = trim($_POST['telefono'] ?? $_POST['celular'] ?? '');
        $correo    = trim($_POST['correo'] ?? $_POST['email'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $clave     = $_POST['clave'] ?? $_POST['password'] ?? '123456';
        $claveHash = password_hash($clave, PASSWORD_BCRYPT);

        // 2. Verificar si el correo ya existe
        if (!empty($correo)) {
            $stmtCheck = $this->db->prepare("SELECT id, nombre, id_rol FROM usuarios WHERE correo = ? LIMIT 1");
            $stmtCheck->execute([$correo]);
            $existente = $stmtCheck->fetch(PDO::FETCH_OBJ);

            if ($existente) {
                $_SESSION['id_usuario'] = $existente->id;
                $_SESSION['nombre']     = $existente->nombre;
                $_SESSION['rol']        = (int)$existente->id_rol;

                header("Location: index.php?action=ver_carrito");
                exit();
            }
        }

        // 3. Inserción con los nombres exactos de tu tabla (id_rol y estado)
        try {
            $sql = "INSERT INTO usuarios (cedula, nombre, apellido, telefono, direccion, correo, clave, id_rol, estado) 
                    VALUES (:cedula, :nombre, :apellido, :telefono, :direccion, :correo, :clave, 3, 1)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':cedula'    => !empty($cedula) ? $cedula : null,
                ':nombre'    => !empty($nombre) ? $nombre : 'Cliente',
                ':apellido'  => !empty($apellido) ? $apellido : null,
                ':telefono'  => !empty($telefono) ? $telefono : null,
                ':direccion' => !empty($direccion) ? $direccion : null,
                ':correo'    => $correo,
                ':clave'     => $claveHash
            ]);

            $nuevoId = (int)$this->db->lastInsertId();

            // 4. Iniciar sesión inmediata para el nuevo cliente
            $_SESSION['id_usuario'] = $nuevoId;
            $_SESSION['nombre']     = !empty($nombre) ? $nombre : 'Cliente';
            $_SESSION['rol']        = 3;

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

            $datos = array("correo" => $_POST["correo_ingreso"]);
            $objModelo = new UsuarioModels($this->db);
            $usuario = $objModelo->buscarUsuarioModel($datos);

            if ($usuario && password_verify($_POST["clave_ingreso"], $usuario["clave"])) {

                if ((int)$usuario["estado"] === 0) {
                    echo "<script>
                            alert('Tu cuenta está desactivada. Contacta al administrador.'); 
                            window.location.href = 'index.php?action=mostrarLogin_registro';
                          </script>";
                    exit();
                }

                if (session_status() == PHP_SESSION_NONE) session_start();
$_SESSION["id_usuario"] = $usuario["id"];
$_SESSION["rol"]        = $usuario["id_rol"];
$_SESSION["nombre"]     = $usuario["nombre"];

// Guardar nombre del rol para que el menú lateral lo muestre dinámicamente
try {
    $stmtR = $this->db->prepare("SELECT nombre_rol FROM roles WHERE id = ?");
    $stmtR->execute([$usuario["id_rol"]]);
    $_SESSION["nombre_rol"] = $stmtR->fetchColumn() ?: 'Personal';
} catch (Exception $e) {
    $_SESSION["nombre_rol"] = 'Personal';
}
                // Carga dinámica de permisos según rol desde la tabla rol_permisos
                if ((int)$usuario["id_rol"] === 1) {
                    $_SESSION["permisos"] = ['*'];
                } else {
                    $sqlPermisos = "SELECT p.codigo 
                                    FROM rol_permisos rp 
                                    INNER JOIN permisos p ON rp.id_permiso = p.id 
                                    WHERE rp.id_rol = ?";
                    $stmtPerm = $this->db->prepare($sqlPermisos);
                    $stmtPerm->execute([$usuario["id_rol"]]);
                    $listaPermisos = $stmtPerm->fetchAll(PDO::FETCH_COLUMN) ?: [];

                    if (!empty($usuario["permisos"])) {
                        $extras = array_filter(array_map('trim', explode(',', $usuario["permisos"])));
                        $listaPermisos = array_unique(array_merge($listaPermisos, $extras));
                    }

                    $_SESSION["permisos"] = $listaPermisos;
                }

                echo "<script>
                        alert('¡Bienvenido(a) a La Providencia, " . $usuario["nombre"] . "!');
                        window.location.href = 'index.php?action=ver_catalogo';
                      </script>";
            } else {
                echo "<script>
                        alert('Error: El correo o la contraseña no coinciden.');
                        window.location.href = 'index.php?action=inicio';
                      </script>";
            }
        }
    }

    public function verDetalle($id) {
        if (isset($id) && !empty($id)) {
            $venta = $this->modelo->obtenerVenta($id);
            $detalles = $this->modelo->obtenerDetalles($id);
            include "views/detalle_venta.php";
        } else {
            echo "<script>alert('ID de venta no válido.'); window.location.href='index.php?action=pagos_pendientes';</script>";
            exit();
        }
    }

    public function listarClientes() {
        try {
            $sql = "SELECT 
                        u.*,
                        COUNT(DISTINCT v.id) AS compras,
                        COALESCE(SUM(v.total), 0) AS total_compras
                    FROM usuarios u
                    LEFT JOIN ventas v 
                           ON v.id_usuario = u.id 
                          AND (v.estado IN ('retirado', 'pagado', 'completada', 'aprobada') OR v.estado IS NULL)
                    WHERE u.id_rol = 3
                    GROUP BY u.id
                    ORDER BY u.id DESC";

            $stmt = $this->db->query($sql);
            $clientes = $stmt->fetchAll(PDO::FETCH_OBJ);

            foreach ($clientes as $c) {
                if ((int)$c->compras === 0 && !empty($c->nombre)) {
                    $nombreCompleto = trim($c->nombre . ' ' . ($c->apellido ?? ''));
                    $sqlExtra = "SELECT COUNT(id) AS compras, COALESCE(SUM(total), 0) AS total_compras 
                                 FROM ventas 
                                 WHERE (nombre_cliente LIKE ? OR nombre_cliente LIKE ?)
                                   AND (estado IN ('retirado', 'pagado', 'completada', 'aprobada') OR estado IS NULL)";
                    $stmtEx = $this->db->prepare($sqlExtra);
                    $stmtEx->execute(['%' . $c->nombre . '%', '%' . $nombreCompleto . '%']);
                    $resEx = $stmtEx->fetch(PDO::FETCH_OBJ);
                    if ($resEx && (int)$resEx->compras > 0) {
                        $c->compras = (int)$resEx->compras;
                        $c->total_compras = (float)$resEx->total_compras;
                    }
                }
            }
        } catch (Exception $e) {
            require_once 'models/UsuarioModels.php';
            $modelo = new UsuarioModels($this->db);
            $clientes = $modelo->obtenerClientes();
        }

        require_once 'views/admin_clientes.php';
    }

    public function cambiarEstadoCliente() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $id = $_GET['id'];
            $estado = $_GET['estado']; 

            $modelo = new UsuarioModels($this->db);
            $modelo->actualizarEstadoUsuario($id, $estado);

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