<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start(); // Mueve esto arriba del todo
require_once 'config/db.php';
require_once 'controllers/UsuarioController.php';
require_once 'controllers/AdminControllers.php'; 
require_once 'controllers/VentasController.php';
require_once 'models/VentaModel.php';
require_once 'controllers/ReporteController.php';
require_once 'controllers/MasterController.php';
require_once __DIR__ . '/vendor/autoload.php';

$db = Database::connect(); 


$usuarioC = new UsuarioController($db); 
$adminC = new AdminControllers(); 
$ventaC = new VentaController($db);
$reporteC = new ReporteController($db);
$masterC = new MasterController($db);

$action = $_GET['action'] ?? 'inicio';



// --- PASO 1: Acciones que no requieren Header ---
if ($action == 'registrar_cliente') { $usuarioC->guardarCliente(); exit(); }
if ($action == 'valider_login_registro') { $usuarioC->ingresar(); exit(); }
if ($action == 'validar_login') { $usuarioC->validarLogin(); exit(); }

// --- PASO 2: Carga de la Interfaz ---
include 'views/layout/header.php'; 
echo '<main id="app">'; 

// --- BLOQUE DE SEGURIDAD MASTER ---
$acciones_master = ['gestionar_admins', 'dashboard_master', 'nuevo_admin', 'guardar_nuevo_admin', 'editar_admin', 'actualizar_admin', 'eliminar_admin', 'configuracion_sistema', 'actualizar_configuracion'];

if (in_array($action, $acciones_master)) {
    if (isset($_SESSION['rol']) && ($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2)) {
        switch ($action) {
            case 'dashboard_master': $masterC->mostrarDashboard_master(); break;
            case 'gestionar_admins': $masterC->gestionarAdmins(); break;
            case 'nuevo_admin': include "views/nuevo_admin.php"; break;
            case 'guardar_nuevo_admin': $masterC->crearAdmin(); break;
            case 'editar_admin': $masterC->editarAdmin(); break;
            case 'actualizar_admin': $masterC->actualizarAdmin(); break;
            case 'eliminar_admin': $masterC->eliminarAdmin($_GET['id']); break;
            case 'configuracion_sistema': $masterC->vistaConfiguracion(); break;
            case 'actualizar_configuracion': 
    $masterC->actualizarConfiguracion(); 
    break;
        }
        echo '</main></main>'; // Cerrar etiquetas
        exit();
    } else {
        header("Location: index.php?action=login");
        exit();
    }
}
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
    'grafico_tortas',
    'metodos_pago' 
];


// Línea 21 aprox:
$acciones_admin = [
    'dashboard', 
    'admin', 
    // ... otros ...
    'metodos_pago',
    'ver_reporte' // <--- Agrégalo aquí para que mantenga el estilo de tu panel
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
    // Reemplaza las líneas 107 a 122 en index.php por este bloque limpio:
case 'grafico_barras':
    if (method_exists($adminC, 'grafico_barras')) {
        $adminC->grafico_barras();
    } else {
        require_once 'views/grafico_barras.php';
    }
    break;

case 'grafico_tortas':
    if (method_exists($adminC, 'grafico_torta')) {
        $adminC->grafico_torta();
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
    

        case 'api_estadisticas':
    // Ajusta '$adminC' por el nombre de la variable de tu controlador de administración
    $adminC->cargarDatosEstadisticosJSON(); 
    break;

    case 'pagos_pendientes':
            $ventaC->pagosPendientes(); // Ejecuta el controlador (él se encargará de incluir la vista)
            break;

        case 'retiro_pedidos':
            $ventaC->retiroPedidos();   // Ejecuta el controlador
            break;

        case 'confirmar_pago':
            $ventaC->confirmarPago($_GET['id']);
            break;

        case 'procesar_retiro':
            $ventaC->procesarRetiro($_GET['id']);
            break;

        case 'detalle_venta':
            $ventaC->verDetalle($_GET['id']);
            break;

        case 'metodos_pago':
        include "views/metodos_pago.php";
     break;

           // ---- CONTROL DE REPORTES GENERALES ----
   // ---- CONTROL DE REPORTES GENERALES (CORREGIDO) ----



    default:
        $usuarioC->mostrarInicio();
        break;

    // --- MÓDULO DE REPORTES Y EXPORTACIÓN ---
case 'exportar_excel':
    // Limpiamos el tipo: eliminamos caracteres como '$' por si acaso
    $tipo = isset($_GET['tipo']) ? trim(str_replace('$', '', $_GET['tipo'])) : 'inventario';
    $reporteC->generarExcel($tipo); 
    exit;

case 'exportar_pdf':
    // Hacemos lo mismo para el PDF
    $tipo = isset($_GET['tipo']) ? trim(str_replace('$', '', $_GET['tipo'])) : 'inventario';
    $reporteC->generarPDF($tipo);
    exit;

    case 'ver_reporte':
        // Capturamos el ID del método enviado por URL (ej: index.php?action=ver_reporte&metodo=1)
        $id_metodo = isset($_GET['metodo']) ? intval($_GET['metodo']) : 0;
        
        // Llamamos a la función que creamos en VentasController
        $ventaC->manejarReporte($id_metodo);
        break;

        // ==========================================
    // MÓDULO MASTER (GESTIÓN DE ADMINS Y CONFIG)
    // ==========================================
    case 'gestionar_admins':
        $masterC->gestionarAdmins();
        break;

    case 'nuevo_admin':
        include "views/nuevo_admin.php";
        break;

    case 'guardar_nuevo_admin':
        $masterC->crearAdmin();
        break;

    case 'editar_admin':
        // Necesitas un método en tu MasterController que busque al usuario
        $masterC->editarAdmin(); 
        break;

    case 'actualizar_admin':
        $masterC->actualizarAdmin();
        break;

    case 'eliminar_admin':
        $masterC->eliminarAdmin($_GET['id']);
        break;

    case 'configuracion_sistema':
        $masterC->editarConfiguracion();
        break;

    case 'actualizar_configuracion':
        $masterC->actualizarSistema();
        break;

        // --- MÓDULO DE REPORTES Y EXPORTACIÓN ---

}

echo '</main>';

// Si es una acción administrativa, cerramos la sección del panel que abrió el header
if (isset($action) && in_array($action, $acciones_admin)) {
    echo '</section>';
}

// --- PASO 3: Footer (Si tienes uno) ---
// include 'views/layout/footer.php'; 
?>