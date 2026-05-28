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

        // 1. Verificamos si existe el usuario y si la clave coincide
        if ($usuario && password_verify($pass, $usuario["clave"])) {
            
            // 2. NUEVA VALIDACIÓN: Comprobamos si el estado es 1 (activo)
            // Asumiendo que tu columna en la base de datos se llama 'estado'
            if (isset($usuario["estado"]) && $usuario["estado"] == 0) {
                echo "<script>alert('Tu cuenta está desactivada. Contacta al administrador.'); window.location.href='index.php?action=login';</script>";
                exit();
            }

            // 3. Si el estado es 1, procedemos a iniciar sesión
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
                // Opcional: Redirección para clientes (rol 3)
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
    // En UsuarioController.php
public function ingresar() {
    if (isset($_POST["correo_ingreso"])) {
        
        $datos = array("correo" => $_POST["correo_ingreso"]);
        $objModelo = new UsuarioModels();
        $usuario = $objModelo->buscarUsuarioModel($datos);

        // 1. Verificar si existe y la clave es correcta
        if ($usuario && password_verify($_POST["clave_ingreso"], $usuario["clave"])) {
            
            // 2. FILTRO CRÍTICO: Verificar estado
            if ((int)$usuario["estado"] === 0) {
                echo "<script>
                        alert('Tu cuenta está desactivada. Contacta al administrador.'); 
                        window.location.href = 'index.php?action=mostrarLogin_registro';
                      </script>";
                exit();
            }

            // 3. Iniciar sesión
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
                    window.location.href = 'index.php?action=mostrarlogin_registro';
                  </script>";
        }
    }
}
// Carga  detalles pasándole la información de la base de datos
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
   // Dentro de tu archivo UsuarioController.php

public function listarClientes() {
    // 1. Instanciamos el modelo de usuarios (el que ya tienes)
    $modelo = new UsuarioModels($this->db);
    
    // 2. Obtenemos los clientes (rol 3)
    $clientes = $modelo->obtenerClientes(); 
    
    // 3. Cargamos la vista de administración
    require_once 'views/admin_clientes.php';
}

public function cambiarEstadoCliente() {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') { // O 'POST', depende de cómo lo envíes
        $id = $_GET['id'];
        $estado = $_GET['estado']; // El nuevo estado que quieres aplicar (0 o 1)

        $modelo = new UsuarioModels($this->db);
        $modelo->actualizarEstadoUsuario($id, $estado); // Usamos la función correcta
        
        // ¡CRÍTICO! Redirigir de vuelta a la lista para que no se quede en blanco
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