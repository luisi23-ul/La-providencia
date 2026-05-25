<?php
class UsuarioController {
    private $db;
    private $modelo;

    public function __construct($db) {
        $this->db = $db;
        require_once 'models/UsuarioModels.php';
        // AQUÍ ESTABA EL ERROR: Necesitas pasarle $db al modelo
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

        

        // Verificamos si existe el usuario y si la clave coincide con el hash
        if ($usuario && password_verify($pass, $usuario["clave"])) {
            
            if (session_status() == PHP_SESSION_NONE) session_start();
           // En UsuarioController.php, dentro de validarLogin(), después de obtener el $usuario
    $_SESSION["id_usuario"] = $usuario["id"];
    $_SESSION["rol"] = $usuario["id_rol"];
    $_SESSION["nombre"] = $usuario["nombre"];
   $_SESSION["permisos"] = explode(',', $usuario["permisos"]); // Convierte "a,b" a ["a", "b"]
                // Redirección centralizada
           if ($_SESSION['rol'] == 1) {
    header("Location: index.php?action=dashboard_master");
} elseif ($_SESSION['rol'] == 2) {
    header("Location: index.php?action=dashboard"); // O la acción que corresponda a tu dashboard de admin
}
            exit();
        } else {
            echo "<script>alert('Correo o contraseña incorrectos.'); window.location.href='index.php?action=login';</script>";
        }
    }
}
    // Dentro de UsuarioController.php

public function mostrarRegistro() {
    include 'views/registro.php';
}

public function guardarCliente() {
    if (isset($_POST["nombre"])) {
        
        // Esta es la línea mágica para la seguridad:
        $encriptar = password_hash($_POST["clave"], PASSWORD_DEFAULT);

        $datos = array(
            "nombre"   => $_POST["nombre"],
            "apellido" => $_POST["apellido"],
            "telefono" => $_POST["telefono"],
            "correo"   => $_POST["correo"],
            "clave"    => $encriptar, // <-- Aquí guardamos la clave ya protegida
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
            
            $datos = array(
                "correo" => $_POST["correo_ingreso"],
                "clave"  => $_POST["clave_ingreso"]
            );

            $objModelo = new UsuarioModels();
            $respuesta = $objModelo->buscarUsuarioModel($datos);

            // Comparamos la clave escrita con el hash de la base de datos
            if ($respuesta && password_verify($datos["clave"], $respuesta["clave"])) {
    $_SESSION["id_usuario"] = $respuesta["id"]; 
    // ------------------------------

    echo "<script>
            alert('¡Bienvenido(a) a La Providencia, " . $respuesta["nombre"] . "!');
            window.location.href = 'index.php?action=ver_catalogo';
          </script>";
            } else {
                echo "<script>
                        alert('Error: El correo o la contraseña no coinciden.');
                        window.location.href = 'index.php?action=mostrarLogin_registro';
                      </script>";
            }
        }
    }
// Carga la vista de detalles pasándole la información de la base de datos
    public function verDetalle($id) {
        if (isset($id) && !empty($id)) {
            // Consultamos al modelo usando los métodos que acabamos de crear
            $venta = $this->model->obtenerVenta($id);
            $detalles = $this->model->obtenerDetalles($id);
            
            // Incluimos la vista limpia dentro de la carpeta views
            include "views/detalle_venta.php";
        } else {
            echo "<script>alert('ID de venta no válido.'); window.location.href='index.php?action=pagos_pendientes';</script>";
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