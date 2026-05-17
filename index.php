<?php
// 1. Configuración inicial y Errores
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// 2. Importación de Requerimientos
require_once 'config/db.php';
require_once 'controllers/UsuarioController.php';
require_once 'controllers/AdminControllers.php'; 
require_once 'controllers/VentasController.php';
require_once 'models/VentaModel.php';


// 3. Inicialización de la Base de Datos y Controladores
$db = Database::connect(); 
$usuarioC = new UsuarioController($db); 
$adminC = new AdminControllers(); 
$ventaC = new VentaController($db);


// 4. Captura de la acción (por defecto 'inicio')
$action = $_GET['action'] ?? 'inicio';

// --- PASO 1: Lógica de procesamiento (Acciones que redireccionan) ---
if ($action == 'registrar_cliente') {
    $usuarioC->guardarCliente();
    exit();
}

if ($action == 'valider_login_registro') {
    $usuarioC->ingresar();
    exit();
}

if ($action == 'validar_login') {
    $usuarioC->validarLogin();
    exit();
}

// --- PASO 2: Carga de la Interfaz (Vistas) ---
include 'views/layout/header.php'; 

echo '<main id="app">'; 

switch ($action) {
    // Rutas de Usuario
    case 'inicio':
        $usuarioC->mostrarInicio();
        break;

        case 'login':
        $usuarioC->mostrarLogin();
        break;

    case 'login_usuario':
        $usuarioC->mostrarLogin_registro();
        break;

        case 'validar_login':
                $usuarioC->validarLogin();
                break;

    case 'registro':
        $usuarioC->mostrarRegistro();
        break;

    // Rutas de Administración / Catálogo
    case 'ver_catalogo':
        $adminC->catalogo();
        break;

    case 'dashboard':
        $adminC->mostrarDashboard(); 
        break;
    case 'admin':
        $adminC->mostrarPanel();
        break;

        case 'listado_productos':
        $adminC->mostrarListado(); 
        break;

        case 'formulario_producto':
        include "views/formulario_producto.php";
        break;

        case 'guardar_producto':
        $adminC->agregar();
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

    

case 'ver_carrito':
        $ventaC->mostrarCarrito();
        break;

    case 'agregar_carrito':
        $ventaC->añadir();
        break;

    case 'eliminar_item':
        $ventaC->eliminarItem();
        break;

    case 'finalizar_compra':
        $ventaC->finalizarCompra();
        break;

    // EL DEFAULT SIEMPRE RIGUROSAMENTE AL FINAL
    default:
        $usuarioC->mostrarInicio();
        break;

}

echo '</main>';

// --- PASO 3: Footer (Si tienes uno) ---
// include 'views/layout/footer.php'; 
?>