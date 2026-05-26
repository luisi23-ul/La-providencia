<?php
// 1. Configuración inicial y Errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start(); // Siempre arriba del todo para evitar fallos de cabeceras

// 2. Importación de Requerimientos
require_once 'config/db.php';
require_once 'controllers/UsuarioController.php';
require_once 'controllers/AdminControllers.php'; 
require_once 'controllers/VentasController.php';
require_once 'models/VentaModel.php';
require_once 'controllers/ReporteController.php';
require_once 'controllers/MasterController.php'; // Controlador de tu compañera
require_once __DIR__ . '/vendor/autoload.php';

// 3. Inicialización de la Base de Datos y Controladores
$db = Database::connect(); 
$usuarioC = new UsuarioController($db); 
$adminC = new AdminControllers(); 
$ventaC = new VentaController($db);
$reporteC = new ReporteController($db);
$masterC = new MasterController($db); // Objeto Máster listo

// 4. Captura de la acción global
$action = $_GET['action'] ?? 'inicio';

// >>> DEFINICIÓN DE ACCIONES ADMINISTRATIVAS MAESTRA (Tus rutas protegidas) <<<
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
    'metodos_pago', 
    'pagos_pendientes', 
    'retiro_pedidos',
    'procesar_retiro',
    'detalle_venta',
    'ver_reporte',        
    'exportar_excel',    
    'exportar_pdf',
    'detalle_venta'       
];

// --- PASO 1: Lógica de procesamiento (Acciones sin HTML que redireccionan) ---
if ($action == 'registrar_cliente') { $usuarioC->guardarCliente(); exit(); }
if ($action == 'valider_login_registro') { $usuarioC->ingresar(); exit(); }
if ($action == 'validar_login') { $usuarioC->validarLogin(); exit(); }

// --- PASO 2: BLOQUE DE SEGURIDAD MÁSTER (Acciones exclusivas de tu compañera) ---
$acciones_master = ['gestionar_admins', 'dashboard_master', 'nuevo_admin', 'guardar_nuevo_admin', 'editar_admin', 'actualizar_admin', 'eliminar_admin', 'configuracion_sistema', 'actualizar_configuracion'];

if (in_array($action, $acciones_master)) {
    if (isset($_SESSION['rol']) && ($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2)) {
        // Cargamos la interfaz para que a ella también se le apliquen los estilos globales
        include 'views/layout/header.php'; 
        echo '<main id="app" class="main-dashboard-content">'; 
        
        switch ($action) {
            case 'dashboard_master': $masterC->mostrarDashboard_master(); break;
            case 'gestionar_admins': $masterC->gestionarAdmins(); break;
            case 'nuevo_admin': include "views/nuevo_admin.php"; break;
            case 'guardar_nuevo_admin': $masterC->crearAdmin(); break;
            case 'editar_admin': $masterC->editarAdmin(); break;
            case 'actualizar_admin': $masterC->actualizarAdmin(); break;
            case 'eliminar_admin': $masterC->eliminarAdmin($_GET['id']); break;
            case 'configuracion_sistema': $masterC->vistaConfiguracion(); break;
            case 'actualizar_configuracion': $masterC->actualizarConfiguracion(); break;
        }
        
        echo '</main>';
        if (in_array($action, $acciones_admin) || $_SESSION['rol'] == 1) { echo '</section>'; }
        exit(); 
    } else {
        header("Location: index.php?action=login");
        exit();
    }
}

/// --- PASO 3: Carga de la Interfaz Estándar ---
$es_admin = isset($action) && in_array($action, $acciones_admin);

include 'views/layout/header.php'; 

// 🎯 Si es vista administrativa, le inyectamos la clase de tus estilos. Si es pública, entra limpia.
if ($es_admin) {
    echo '<main id="app" class="main-dashboard-content">'; 
} else {
    echo '<main id="app">'; 
}

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

    // MÓDULO ESTADÍSTICO
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

    case 'api_estadisticas':
        $adminC->cargarDatosEstadisticosJSON(); 
        break;

    case 'pagos_pendientes':
        $ventaC->pagosPendientes();
        break;

    case 'retiro_pedidos':
        $ventaC->retiroPedidos();
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

    // --- MÓDULO DE REPORTES Y EXPORTACIÓN ---
    case 'exportar_excel':
        $tipo = isset($_GET['tipo']) ? trim(str_replace('$', '', $_GET['tipo'])) : 'inventario';
        $reporteC->generarExcel($tipo); 
        exit;

    case 'exportar_pdf':
        $tipo = isset($_GET['tipo']) ? trim(str_replace('$', '', $_GET['tipo'])) : 'inventario';
        $reporteC->generarPDF($tipo);
        exit;

    case 'ver_reporte':
        $id_metodo = isset($_GET['metodo']) ? intval($_GET['metodo']) : 0;
        $ventaC->manejarReporte($id_metodo);
        break;

    // --- LOGOUT ---
    case 'logout':
        session_unset();
        session_destroy();
        header("Location: index.php?action=login");
        exit();
        break;

    default:
        $usuarioC->mostrarInicio();
        break;
}

echo '</main>';

// Si es una acción administrativa, cerramos la sección estructural del panel de forma limpia
if (isset($action) && in_array($action, $acciones_admin)) {
    echo '</section>';
}
?>