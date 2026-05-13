<?php
// Clase encargada de la gestión de usuarios y autenticación
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
        $datos = array(
            "nombre"   => $_POST["nombre"],
            "apellido" => $_POST["apellido"],
            "telefono" => $_POST["telefono"],
            "correo"   => $_POST["correo"],
            "clave"    => $_POST["clave"],
            "id_rol"   => 3 // Rol de cliente
        );

        // INSTANCIAMOS igual que haces con productos
        $modelo = new UsuarioModels(); 
        $respuesta = $modelo->registrarClienteModel($datos);

        if ($respuesta) {
            echo "<script>
                    alert('¡Bienvenido! Tu registro en La Providencia fue exitoso.');
                    window.location.href = 'index.php?action=inicio';
                  </script>";
        } else {
            echo "<script>alert('Error al guardar. Verifica los campos.');</script>";
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