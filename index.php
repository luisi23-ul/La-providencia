<?php
ob_start();
session_start(); 
date_default_timezone_set('America/Caracas');

require_once 'config/db.php';
require_once 'controllers/UsuarioController.php';
require_once 'controllers/AdminControllers.php'; 
require_once 'controllers/VentasController.php';
require_once 'models/VentaModel.php';
require_once 'controllers/ReporteController.php';
require_once 'controllers/MasterController.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once 'controllers/ProveedorController.php';
require_once 'controllers/CompraController.php';

$db = Database::connect(); 

$usuarioC = new UsuarioController($db); 
$adminC   = new AdminControllers(); 
$ventaC   = new VentaController($db);
$reporteC = new ReporteController($db);
$masterC  = new MasterController($db);
$provC    = new ProveedorController($db);
$compraC  = new CompraController($db);
$action   = $_GET['action'] ?? 'inicio';

// -----------------------------------------------------------------------------
// LISTA DE ACCIONES POR PANEL
// -----------------------------------------------------------------------------
$acciones_master = [
    'dashboard_master', 'gestionar_admins', 'nuevo_admin', 'guardar_nuevo_admin', 
    'editar_admin', 'actualizar_admin', 'eliminar_admin', 'listado_administradores',
    'toggle_admin', 'configuracion_sistema', 'actualizar_configuracion',
    'roles_permisos', 'guardar_permisos', 'guardar_rol', 'auditoria'
];

$acciones_admin = [
    'dashboard', 'admin', 'listado_productos', 'formulario_producto', 
    'guardar_producto', 'editar', 'actualizar_producto', 'eliminar_producto',
    'seccion_graficos', 'grafico_barras', 'grafico_tortas',
    'metodos_pago', 'guardar_metodo_pago', 'cambiar_estado_metodo',
    'punto_venta', 'guardar_venta_tienda', 'ventas', 'verificar_pagos', 
    'aprobar_pago', 'rechazar_pago', 'deshacer_pago', 'pagos_banco', 'pagos_pendientes', 
    'retiro_pedidos', 'procesar_retiro', 'detalle_venta', 'ver_reporte',
    'categorias', 'guardar_categoria', 'eliminar_categoria', 
    'ajustes_inventario', 'guardar_ajuste', 'kardex',
    'proveedores', 'guardar_proveedor', 'cambiar_estado_proveedor',
    'compras', 'nueva_compra', 'guardar_compra', 'detalle_compra', 'anular_compra',
    'lista_clientes', 'guardar_cliente', 'cambiarEstadoCliente'
];

// -----------------------------------------------------------------------------
// ACCIONES PURAS DE API / PROCESAMIENTO (Sin Header HTML)
// -----------------------------------------------------------------------------
if ($action == 'registrar_cliente')       { $usuarioC->guardarCliente(); exit(); }
if ($action == 'valider_login_registro')  { $usuarioC->ingresar(); exit(); }
if ($action == 'validar_login')           { $usuarioC->validarLogin(); exit(); }
if ($action == 'cerrar_sesion')           { $usuarioC->cerrarSesion(); exit(); }

// -----------------------------------------------------------------------------
// BLOQUE DE SEGURIDAD MASTER (Solo Rol 1 y 2)
// -----------------------------------------------------------------------------
if (in_array($action, $acciones_master)) {
    if (isset($_SESSION['rol']) && ($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2)) {
        switch ($action) {
            case 'dashboard_master':         $masterC->mostrarDashboard_master(); break;
            case 'gestionar_admins':          $masterC->gestionarAdmins(); break;
            case 'nuevo_admin':               include "views/nuevo_admin.php"; break;
            case 'guardar_nuevo_admin':       $masterC->crearAdmin(); break;
            case 'editar_admin':              $masterC->editarAdmin(); break;
            case 'actualizar_admin':          $masterC->actualizarAdmin(); break;
            case 'eliminar_admin':            $masterC->eliminarAdmin($_GET['id'] ?? null); break;
            case 'listado_administradores':   $masterC->listarAdministradores(); break;
            case 'toggle_admin':              $masterC->toggleAdmin(); break;
            case 'configuracion_sistema':     $masterC->vistaConfiguracion(); break;
            case 'actualizar_configuracion':  $masterC->actualizarConfiguracion(); break;
            case 'roles_permisos':            include 'views/master/roles_permisos.php'; break;
            case 'auditoria':                 include 'views/master/auditoria.php'; break;
        }
        ob_end_flush();
        exit();
    } else {
        header("Location: index.php?action=login");
        exit();
    }
}

// -----------------------------------------------------------------------------
// RENDERIZADO DEL ENCABEZADO TIENDA ONLINE
// (Solo para vistas de clientes, no interfiere en el panel admin)
// -----------------------------------------------------------------------------
$es_panel = in_array($action, $acciones_admin) || in_array($action, $acciones_master);

if (!$es_panel && $action != 'login') {
    include 'views/layout/header.php';
    echo '<main id="app">';
}

// -----------------------------------------------------------------------------
// ENRUTADOR PRINCIPAL
// -----------------------------------------------------------------------------
switch ($action) {
    // Tienda Online / Clientes
    case 'inicio':           $usuarioC->mostrarInicio(); break;
    case 'login':            $usuarioC->mostrarLogin(); break;
    case 'login_usuario':    $usuarioC->mostrarLogin_registro(); break;
    case 'registro':         $usuarioC->mostrarRegistro(); break;
    case 'ver_catalogo':     $adminC->catalogo(); break;
    case 'ver_carrito':      $ventaC->mostrarCarrito(); break;
    case 'agregar_carrito':  $ventaC->añadir(); break;
    case 'eliminar_item':    $ventaC->eliminarItem(); break;
    case 'finalizar_compra': $ventaC->finalizarCompra(); break;

    // Panel Admin: Dashboard
    case 'dashboard':        $adminC->mostrarDashboard(); break;
    case 'admin':            $adminC->mostrarPanel(); break;

    // Panel Admin: Módulo de Inventario
    case 'listado_productos':   $adminC->mostrarListado(); break;
    case 'formulario_producto': $adminC->mostrarPanel(); break;
    case 'guardar_producto':    $adminC->agregar(); break;
    case 'editar':              $adminC->editar(); break;
    case 'actualizar_producto': $adminC->actualizar(); break;
    case 'eliminar_producto':   $adminC->eliminar(); break;
    case 'categorias':          $adminC->categorias(); break;
    case 'guardar_categoria':   $adminC->guardarCategoria(); break;
    case 'eliminar_categoria':  $adminC->eliminarCategoria(); break;
    case 'ajustes_inventario':  $adminC->ajustesInventario(); break;
    case 'guardar_ajuste':      $adminC->guardarAjuste(); break;
    case 'kardex':              $adminC->kardex(); break;

    // Gráficos y Estadísticas
    case 'seccion_graficos':    include "views/seccion_graficos.php"; break;
    case 'grafico_barras':      $adminC->grafico_barras(); break;
    case 'grafico_tortas':      $adminC->grafico_torta(); break;

    // Panel Admin: Ventas, POS y Pagos
    case 'punto_venta':          $ventaC->puntoVenta(); break;
    case 'guardar_venta_tienda': $ventaC->guardarVentaTienda(); break;
    case 'ventas':               $ventaC->listarVentas(); break;
    case 'verificar_pagos':      $ventaC->verificarPagos(); break;
    case 'aprobar_pago':         $ventaC->aprobarPago(); break;
    case 'rechazar_pago':        $ventaC->rechazarPago(); break;
    case 'deshacer_pago':        $ventaC->deshacerPago(); break;
    case 'pagos_banco':          $ventaC->pagosBanco(); break;
    case 'pagos_pendientes':     $ventaC->pagosPendientes(); break;
    case 'retiro_pedidos':       $ventaC->retiroPedidos(); break;
    case 'confirmar_pago':       $ventaC->confirmarPago($_GET['id'] ?? null); break;
    case 'procesar_retiro':      $ventaC->procesarRetiro($_GET['id'] ?? null); break;
    case 'detalle_venta':        $ventaC->verDetalle($_GET['id'] ?? null); break;
    case 'ver_reporte':
        $id_metodo = isset($_GET['metodo']) ? intval($_GET['metodo']) : 0;
        $ventaC->manejarReporte($id_metodo);
        break;

    // Panel Admin: Compras y Proveedores
    case 'proveedores':              $provC->listar(); break;
    case 'guardar_proveedor':        $provC->guardar(); break;
    case 'cambiar_estado_proveedor': $provC->cambiarEstado(); break;
    case 'compras':                  $compraC->listar(); break;
    case 'nueva_compra':             $compraC->nuevaCompra(); break;
    case 'guardar_compra':           $compraC->guardar(); break;
    case 'detalle_compra':           $compraC->detalle(); break;
    case 'anular_compra':            $compraC->anular(); break;

    // Panel Admin: Clientes y Métodos de Pago
    case 'metodos_pago':         include "views/metodos_pago.php"; break;
    case 'lista_clientes':       $usuarioC->listarClientes(); break;
    case 'cambiarEstadoCliente': $usuarioC->cambiarEstadoCliente(); break;

    // Reportes de Exportación
    case 'exportar_excel':
        $tipo = isset($_GET['tipo']) ? trim(str_replace('$', '', $_GET['tipo'])) : 'inventario';
        $reporteC->generarExcel($tipo); 
        exit;
    case 'exportar_pdf':
        $tipo = isset($_GET['tipo']) ? trim(str_replace('$', '', $_GET['tipo'])) : 'inventario';
        $reporteC->generarPDF($tipo);
        exit;

    default:
        $usuarioC->mostrarInicio();
        break;
}

if (!$es_panel && $action != 'login') {
    echo '</main>';
}

ob_end_flush();
?>