<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config/db.php';
require_once 'controllers/UsuarioController.php';
require_once 'controllers/AdminControllers.php'; 

// Conexión estática a la base de datos
$db = Database::connect(); 

$usuarioC = new UsuarioController($db); 
$adminC = new AdminControllers(); 

// Captura la acción  Si no hay acción,  enviamos a 'inicio'
$action = $_GET['action'] ?? 'inicio';

?> 

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>La Providencia - Panel Administrativo</title>
    <link rel="stylesheet" href="/La-providencia/public/estilos.css?v=<?php echo time(); ?>">
</head>

<body>
    <main id="app">
        <?php 
        switch ($action) {
            // Esta es la vista principal con las dos tarjetas (Cargar / Gestionar)
            case 'dashboard':
                $adminC->mostrarDashboard();
                break;

            //  FORMULARIO: Solo aparece cuando el admin hace click en "Cargar Producto"
            case 'admin':
                $adminC->mostrarPanel();
                break;

            //  GESTIÓN: Aparece cuando el admin hace click en "Gestionar Inventario"
            case 'listado':
                $adminC->mostrarListado();
                break;

            //  (No muestran vista, solo ejecutan y redirigen)
            case 'guardar_producto':
                $adminC->agregar();
                break;

                // Acción para mostrar el catálogo 
            case 'ver_catalogo':
                $adminC->catalogo();
                break;

            
            case 'eliminar_producto':
                $adminC->eliminar();
                break;

                case 'editar':
             $adminC->editar();
             break;

             case 'actualizar_producto':
            $adminC->actualizar();
            break;
            case "registro":
        require_once "controllers/UsuarioController.php";
         $Usuariocontroller = new UsuarioController($db);
        $Usuariocontroller->registrar();
        break;

            

            // SECCIÓN DE USUARIOS
            case 'login':
                $usuarioC->mostrarLogin();
                break;
            
            case 'validar_login':
                $usuarioC->validarLogin();
                break;

            case 'inicio':
            default:
                $usuarioC->mostrarInicio();
                break;
        }
        ?>
    </main>
</body>
</html>