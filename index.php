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

// >>> REGLA DE ORO SENIOR: Definimos el array de admin ANTES de cargar el Header <<<
$acciones_admin = [
    'dashboard', 
    'admin', 
    'listado_productos', 
    'formulario_producto', 
    'guardar_producto',
    'editar', 
    'actualizar_producto',
    'eliminar_producto',
    'seccion_graficos', 
    'grafico_barras', 
    'grafico_tortas'  
];

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
$es_admin = isset($action) && in_array($action, $acciones_admin);

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
 
    case 'seccion_graficos':
        include "views/seccion_graficos.php";
        break;

    // ==========================================================================
    // MÓDULO ESTADÍSTICO - TOTALMENTE ENLAZADO AL OBJETO GLOBAL $adminC provicional
    // ==========================================================================
    case 'grafico_barras':
        // Si  ya creaste el método en el backend, corre el real automáticamente
        if (method_exists($adminC, 'grafico_barras')) {
            $adminC->grafico_barras();
        } else {
            // Si no, cargamos los datos de prueba
            require_once 'views/grafico_barras.php'; 
        }
        break;

    case 'grafico_tortas':
        if (method_exists($adminC, 'grafico_tortas')) {
            $adminC->grafico_tortas();
        } else {
            require_once 'views/grafico_tortas.php';
        }
        break;
    // ==========================================================================

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

    // Rutas de Ventas / Carrito
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

// Si es una acción administrativa, cerramos la sección del panel que abrió el header
if (isset($action) && in_array($action, $acciones_admin)) {
    echo '</section>';
}

// --- PASO 3: Footer (Si tienes uno) ---
// include 'views/layout/footer.php'; 
?>