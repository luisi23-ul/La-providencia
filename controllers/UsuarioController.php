<?php
class UsuarioController {
    private $modelo;

    public function __construct($db) {
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
    

    public function validarLogin() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = isset($_POST['email']) ? $_POST['email'] : '';
            $pass = isset($_POST['password']) ? $_POST['password'] : '';

            if ($email === 'luisa@gmail.com' && $pass === 'Dios1234') {
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }

                $_SESSION['admin_auth'] = 'Luisana Admin';
                
                
                header("Location: index.php?action=dashboard");
                exit();
                
                
            } else {
                echo "<script>
                    alert('Acceso Denegado: Credenciales de administrador incorrectas.');
                    window.location.href = 'index.php?action=login';
                </script>";
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
            window.location.href = 'index.php?action=ver_catalogo'; 
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

   




    public function cerrarSesion() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        header("Location: index.php?action=inicio");
        exit();
    }
}