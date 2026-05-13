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

            if ($email === 'luisi@gmail.com' && $pass === 'Dios1234') {
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

    public function registrar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $datos = [
                'nombre'   => $_POST['nombre'],
                'apellido' => $_POST['apellido'],
                'telefono' => $_POST['telefono'],
                'correo'   => $_POST['correo'],
                'clave'    => $_POST['clave']
            ];

            if ($this->modelo->registrarCliente($datos)) {
                
                header("Location: index.php?action=ver_catalogo&res=exito");
                exit();
            } else {
                echo "Hubo un error al guardar en la base de datos.";
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