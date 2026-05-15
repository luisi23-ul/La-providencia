<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/db.php';
require_once 'controllers/UsuarioController.php';
require_once 'controllers/AdminControllers.php'; 

$db = Database::connect(); 
$usuarioC = new UsuarioController($db); 
$adminC = new AdminControllers(); 

$action = $_GET['action'] ?? 'inicio';

// --- PASO 1: Lógica de procesamiento (ACCIONES QUE NO TIENEN VISTA) ---
// Ejecutamos primero las funciones que guardan o validan para que el redirect funcione bien
if ($action == 'registrar_cliente') {
    $usuarioC->guardarCliente();
    exit(); // Detenemos la ejecución para que no cargue el header si hay redirección
}

if ($action == 'valider_login_registro') {
    $usuarioC->ingresar();
    exit();
}

// --- PASO 2: Carga de Interfaz (VISTAS) ---
include 'views/layout/header.php'; 

echo '<main id="app">'; 

switch ($action) {
    case 'inicio':
        $usuarioC->mostrarInicio();
        break;

    case 'login_usuario':
        $usuarioC->mostrarLogin_registro();
        break;

    case 'registro':
        $usuarioC->mostrarRegistro();
        break;

    case 'ver_catalogo':
        $adminC->catalogo();
        break;

    case 'dashboard':
        $adminC->mostrarDashboard();
        break;

    case 'admin':
        $adminC->mostrarPanel();
        break;

    default:
        $usuarioC->mostrarInicio();
        break;
}

echo '</main>';
?>