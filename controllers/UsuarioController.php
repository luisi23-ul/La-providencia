<?php
class UsuarioController {
    private $db;
    private $modelo;

    public function __construct($db) {
        $this->db = $db;
        require_once 'models/UsuarioModels.php';
       $this->modelo = new UsuarioModels();
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
        $objModelo = new UsuarioModels();
        $usuario = $objModelo->buscarUsuarioModel($datos);

        // aca se verif que si existe el usuario y si la clave coincide
        if ($usuario && password_verify($pass, $usuario["clave"])) {
            
            if (isset($usuario["estado"]) && $usuario["estado"] == 0) {
                echo "<script>alert('Tu cuenta está desactivada. Contacta al administrador.'); window.location.href='index.php?action=login';</script>";
                exit();
            }
            if (session_status() == PHP_SESSION_NONE) session_start();
            
            $_SESSION["id_usuario"] = $usuario["id"];
            $_SESSION["rol"] = $usuario["id_rol"];
            $_SESSION["nombre"] = $usuario["nombre"];
            $_SESSION["permisos"] = explode(',', $usuario["permisos"]);
            
            // Redirección según rol
            if ($_SESSION['rol'] == 1) {
                header("Location: index.php?action=dashboard_master");
            } elseif ($_SESSION['rol'] == 2) {
                header("Location: index.php?action=dashboard"); 
            } else {
                header("Location: index.php?action=inicio");
            }
            exit();
            
        } else {
            // Error de credenciales
            echo "<script>alert('Correo o contraseña incorrectos.'); window.location.href='index.php?action=login';</script>";
        }
    }
}

public function mostrarRegistro() {
    include 'views/registro.php';
}

public function guardarCliente() {
    if (isset($_POST["nombre"])) {
        
        // guardar clave encriptada
        $encriptar = password_hash($_POST["clave"], PASSWORD_DEFAULT);

        $datos = array(
            "nombre"   => $_POST["nombre"],
            "apellido" => $_POST["apellido"],
            "telefono" => $_POST["telefono"],
            "correo"   => $_POST["correo"],
            "clave"    => $encriptar, 
            "id_rol"   => 3 
        );

        $objModelo = new UsuarioModels();
        $respuesta = $objModelo->registrarClienteModel($datos);
       if ($respuesta) {
    // Usamos el nombre que recibimos del formulario para el mensaje
    $nombreUsuario = $_POST["nombre"];

    echo "<script>
            alert('¡Bienvenido(a) a La Providencia, " . $nombreUsuario . "! Tu registro ha sido exitoso.');
            window.location.href = 'index.php?action=ver_carrito'; 
          </script>";
} else {
    echo "<script>
            alert('Hubo un error en el registro. Por favor, intenta de nuevo.');
            window.location.href = 'index.php?action=registro';
          </script>";
}
    }
}
public function mostrarLogin_registro() {
        include "views/login_registro.php";
    }

    // Función para validar el correo y la clave
public function ingresar() {
    if (isset($_POST["correo_ingreso"])) {
        
        $datos = array("correo" => $_POST["correo_ingreso"]);
        $objModelo = new UsuarioModels();
        $usuario = $objModelo->buscarUsuarioModel($datos);

        //  Verifi si existe y la clave es correcta
        if ($usuario && password_verify($_POST["clave_ingreso"], $usuario["clave"])) {
            if ((int)$usuario["estado"] === 0) {
                echo "<script>
                        alert('Tu cuenta está desactivada. Contacta al administrador.'); 
                        window.location.href = 'index.php?action=mostrarLogin_registro';
                      </script>";
                exit();
            }

            //  Iniciar sesión
            if (session_status() == PHP_SESSION_NONE) session_start();
            $_SESSION["id_usuario"] = $usuario["id"];
            $_SESSION["nombre"] = $usuario["nombre"];
            
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
// Carga  detalles pasaandole a lan de la base de datos
    public function verDetalle($id) {
        if (isset($id) && !empty($id)) {
            // Consultamos al modelo usando los métodos que acabamos de crear
            $venta = $this->model->obtenerVenta($id);
            $detalles = $this->model->obtenerDetalles($id);
            include "views/detalle_venta.php";
        } else {
            echo "<script>alert('ID de venta no válido.'); window.location.href='index.php?action=pagos_pendientes';</script>";
            exit();
        }
    }

public function listarClientes() {
        try {
            // Consulta para obtener todos los clientes (rol = 3) junto con su conteo de compras y total gastado
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

            // Respaldo: si por caja se registraron ventas sin id_usuario pero con coincidencia de nombre o cédula
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
            // Fallback por si la estructura tiene alguna particularidad
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