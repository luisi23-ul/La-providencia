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

// --- 1. CARGAMOS EL HEADER ---
// Esto reemplaza las etiquetas <html>, <head> y el <nav> manual que tenías antes
include 'views/layout/header.php'; 

// --- 2. CONTENIDO DINÁMICO ---
echo '<main id="app">'; // Mantenemos tu ID para no romper tus estilos actuales

// Aquí solo ejecutamos la lógica, los controladores decidirán qué vista mostrar
switch ($action) {
    case 'dashboard':
        $adminC->mostrarDashboard();
        break;
    case 'admin':
        $adminC->mostrarPanel();
        break;
    case 'listado':
        $adminC->mostrarListado();
        break;
    case 'guardar_producto':
        $adminC->agregar();
        break;
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
        $usuarioC->registrar(); // Corregido para usar la instancia ya creada
        break;
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

echo '</main>'; 