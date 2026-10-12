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
    'editar_pago_verificacion', 'aprobar_pago', 'rechazar_pago', 'deshacer_pago', 
    'pagos_banco', 'pagos_pendientes', 'retiro_pedidos', 'procesar_retiro', 
    'detalle_venta', 'ver_reporte', 'categorias', 'guardar_categoria', 
    'eliminar_categoria', 'ajustes_inventario', 'guardar_ajuste', 'kardex',
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

if ($action == 'crear_pedido') { 
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (!$input) {
        echo json_encode(['ok' => false, 'error' => 'Petición vacía']);
        exit();
    }

    $items        = $input['items'] ?? [];
    $nombreMetodo = !empty($input['metodo_pago']) ? trim($input['metodo_pago']) : 'Pago Móvil';
    $banco        = !empty($input['banco']) ? trim($input['banco']) : 'No indicado';
    $idCliente    = $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? null;

    if (empty($items)) {
        echo json_encode(['ok' => false, 'error' => 'El carrito está vacío']);
        exit();
    }

    try {
        // Verificar usuario existente en la BD para evitar fallo de FK
        $stmtCli = $db->prepare("SELECT id, nombre, telefono FROM usuarios WHERE id = ? LIMIT 1");
        $stmtCli->execute([$idCliente]);
        $cliente = $stmtCli->fetch(PDO::FETCH_OBJ);

        if (!$cliente) {
            $stmtFallback = $db->query("SELECT id, nombre, telefono FROM usuarios ORDER BY id ASC LIMIT 1");
            $cliente = $stmtFallback->fetch(PDO::FETCH_OBJ);
            $idCliente = $cliente ? $cliente->id : 1;
        }

        $db->beginTransaction();

        // 1. Obtener precio real de cada producto desde la BD
        $totalUsd = 0;
        $itemsProcesados = [];
        $stmtP = $db->prepare("SELECT id, precio FROM productos WHERE id = ? LIMIT 1");

        foreach ($items as $it) {
            $idProd = (int)($it['id'] ?? $it['id_producto'] ?? 0);
            $cant   = max(1, (int)($it['cantidad'] ?? 1));

            $stmtP->execute([$idProd]);
            $prodBd = $stmtP->fetch(PDO::FETCH_OBJ);

            $precio = $prodBd ? (float)$prodBd->precio : (float)($it['precio'] ?? 0);
            $sub    = round($precio * $cant, 2);
            $totalUsd += $sub;

            $itemsProcesados[] = [
                'id'       => $idProd,
                'cantidad' => $cant,
                'precio'   => $precio,
                'subtotal' => $sub
            ];
        }

        // 2. Tasa activa BCV
        $tasaActual = 875.65;
        if (class_exists('BcvService')) {
            try {
                $bcvSvc = new BcvService();
                $tasaActual = (float)($bcvSvc->obtenerTasaActiva() ?: 875.65);
            } catch (\Throwable $t) {
                $tasaActual = 875.65;
            }
        }
        $totalBs = round($totalUsd * $tasaActual, 2);

        // 3. ID de Método de pago
        $stmtMP = $db->prepare("SELECT id FROM metodos_pago WHERE nombre LIKE ? LIMIT 1");
        $stmtMP->execute(['%' . $nombreMetodo . '%']);
        $idMetodoPago = $stmtMP->fetchColumn();
        if (!$idMetodoPago) {
            $idMetodoPago = $db->query("SELECT id FROM metodos_pago LIMIT 1")->fetchColumn() ?: 1;
        }

        // 4. Inserción en ventas
        $stmtV = $db->prepare("
            INSERT INTO ventas (id_usuario, total, fecha, estado) 
            VALUES (?, ?, NOW(), 'pendiente')
        ");
        $stmtV->execute([$idCliente, $totalUsd]);
        $idVenta = $db->lastInsertId();

        // 5. Inserción en detalle_ventas
        $stmtD = $db->prepare("
            INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario, subtotal) 
            VALUES (?, ?, ?, ?, ?)
        ");
        foreach ($itemsProcesados as $it) {
            $stmtD->execute([$idVenta, $it['id'], $it['cantidad'], $it['precio'], $it['subtotal']]);
        }

        // 6. Inserción en pagos_registro
        $esEfectivo = stripos($nombreMetodo, 'efectivo') !== false;
        $moneda = ($esEfectivo && stripos($nombreMetodo, '$') !== false) ? 'USD' : 'VES';
        $montoPago = ($moneda === 'USD') ? $totalUsd : $totalBs;
        $referenciaTexto = $esEfectivo ? 'Efectivo en tienda' : 'Online - ' . $banco;

        $stmtPR = $db->prepare("
            INSERT INTO pagos_registro (
                id_venta, 
                id_metodo_pago, 
                moneda, 
                monto, 
                tasa_cambio, 
                referencia, 
                banco, 
                estado_verificacion, 
                fecha_pago
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())
        ");
        $stmtPR->execute([
            $idVenta,
            $idMetodoPago,
            $moneda,
            $montoPago,
            $tasaActual,
            $referenciaTexto,
            $banco
        ]);

        $db->commit();

        echo json_encode([
            'ok' => true,
            'pedido' => str_pad($idVenta, 4, '0', STR_PAD_LEFT),
            'whatsapp' => '584127818865',
            'total' => $totalUsd,
            'cliente' => [
                'nombre' => $cliente->nombre ?? 'Cliente',
                'telefono' => $cliente->telefono ?? ''
            ]
        ]);
        exit();

    } catch (\Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo json_encode(['ok' => false, 'error' => 'Error: ' . $e->getMessage()]);
        exit();
    }
}

// -----------------------------------------------------------------------------
// BLOQUE DE SEGURIDAD MASTER (Solo Rol 1 y 2)
// -----------------------------------------------------------------------------
if (in_array($action, $acciones_master)) {
    if (isset($_SESSION['rol']) && ($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2)) {
       switch ($action) {
            case 'dashboard_master':         $masterC->mostrarDashboard_master(); break;
            case 'gestionar_admins':         $masterC->gestionarAdmins(); break;
            case 'nuevo_admin':              $masterC->nuevoAdmin(); break;
            case 'guardar_nuevo_admin':      $masterC->crearAdmin(); break;
            case 'editar_admin':             $masterC->editarAdmin(); break;
            case 'actualizar_admin':         $masterC->actualizarAdmin(); break;
            case 'eliminar_admin':           $masterC->eliminarAdmin($_GET['id'] ?? null); break;
            case 'listado_administradores':  $masterC->listarAdministradores(); break;
            case 'toggle_admin':             $masterC->toggleAdmin(); break;
            case 'configuracion_sistema':    $masterC->vistaConfiguracion(); break;
            case 'actualizar_configuracion': $masterC->actualizarConfiguracion(); break;
            case 'roles_permisos':           $masterC->rolesPermisos(); break;
            case 'guardar_permisos':         $masterC->guardarPermisos(); break;
            case 'guardar_rol':              $masterC->guardarRol(); break;
            case 'auditoria':                $masterC->auditoria(); break;
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
    case 'ver_producto':     
        $id = (int)($_GET['id'] ?? 0);
        $stmtP = $db->prepare("SELECT p.*, c.nombre_categoria FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id WHERE p.id = ?");
        $stmtP->execute([$id]);
        $producto = $stmtP->fetch(PDO::FETCH_OBJ);

        $stmtA = $db->prepare("
            SELECT ca.nombre, GROUP_CONCAT(DISTINCT cao.valor SEPARATOR ',') AS opciones, pa.valor
            FROM categoria_atributos ca
            LEFT JOIN categoria_atributo_opciones cao ON ca.id = cao.id_categoria_atributo
            LEFT JOIN producto_atributos pa ON pa.id_atributo = ca.id AND pa.id_producto = ?
            WHERE ca.id_categoria = ?
            GROUP BY ca.id
        ");
        $stmtA->execute([$id, $producto->id_categoria ?? 0]);
        $atributos = $stmtA->fetchAll(PDO::FETCH_OBJ);

        $stmtR = $db->prepare("SELECT * FROM productos WHERE id_categoria = ? AND id != ? AND estado = 1 LIMIT 4");
        $stmtR->execute([$producto->id_categoria ?? 0, $id]);
        $relacionados = $stmtR->fetchAll(PDO::FETCH_OBJ);
        require_once 'views/producto.php';
        break;

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
    case 'seccion_graficos':    $adminC->mostrarEstadisticas(); break;
    case 'grafico_barras':      $adminC->grafico_barras(); break;
    case 'grafico_tortas':      $adminC->grafico_torta(); break;

    // Panel Admin: Ventas, POS y Pagos
    case 'punto_venta':              $ventaC->puntoVenta(); break;
    case 'guardar_venta_tienda':     $ventaC->guardarVentaTienda(); break;
    case 'ventas':                   $ventaC->listarVentas(); break;
    case 'verificar_pagos':          $ventaC->verificarPagos(); break;
    case 'editar_pago_verificacion': $ventaC->editarPagoVerificacion(); break;
    case 'aprobar_pago':             $ventaC->aprobarPago(); break;
    case 'rechazar_pago':            $ventaC->rechazarPago(); break;
    case 'deshacer_pago':            $ventaC->deshacerPago(); break;
    case 'pagos_banco':              $ventaC->pagosBanco(); break;
    case 'pagos_pendientes':         $ventaC->pagosPendientes(); break;
    case 'retiro_pedidos':           $ventaC->retiroPedidos(); break;
    case 'confirmar_pago':           $ventaC->confirmarPago($_GET['id'] ?? null); break;
    case 'procesar_retiro':          $ventaC->procesarRetiro($_GET['id'] ?? null); break;
    case 'detalle_venta':            $ventaC->verDetalle($_GET['id'] ?? null); break;
    case 'ver_reporte':              $adminC->verReporte(); break;

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