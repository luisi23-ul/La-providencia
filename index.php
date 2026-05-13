<?php
ini_set('display_errors', 1);
ini_set('display_reporting', E_ALL);

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/db.php';
require_once 'controllers/UsuarioController.php';
require_once 'controllers/AdminControllers.php'; 

$db = Database::connect(); 
$usuarioC = new UsuarioController($db); 
$adminC = new AdminControllers(); 

// Agarramos la acción. Si no hay, enviamos a 'inicio'
$action = $_GET['action'] ?? 'inicio';

// --- 1. CARGAMOS EL HEADER (Trae <html>, <head> y <nav>) ---
include 'views/layout/header.php'; 

echo '<main id="app">'; 

switch ($action) {
    // --- VISTAS DE USUARIO / INICIO ---
    case 'inicio':
        $usuarioC->mostrarInicio();
        break;

    case 'login':
        $usuarioC->mostrarLogin();
        break;

    case 'validar_login':
        $usuarioC->validarLogin();
        break;

   

    
    case 'dashboard':
        $adminC->mostrarDashboard();
        break;
        
        
case 'registro':
    $usuarioC->mostrarRegistro(); // Para ver el formulario
    break;

case 'registrar_cliente':
    $usuarioC->guardarCliente(); // Para procesar los datos y guardarlos
    break;
    case 'admin':
        $adminC->mostrarPanel();
        break;

    case 'listado':
        $adminC->mostrarListado();
        break;

    case 'ver_catalogo':
        $adminC->catalogo();
        break;

    case 'editar':
        $adminC->editar();
        break;

    case 'guardar_producto':
        $adminC->agregar();
        break;

    case 'eliminar_producto':
        $adminC->eliminar();
        break;

    case 'actualizar_producto':
        $adminC->actualizar();
        break;

    default:
        $usuarioC->mostrarInicio();
        break;
}

echo '</main>';

?>