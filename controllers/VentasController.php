<?php
require_once "models/VentaModel.php";

class VentaController {
    private $db;
    private $model;

    public function __construct($db) {
        $this->db = $db;
        $this->model = new VentaModel($this->db);
    }

    // Carga la tabla con los productos seleccionados
    public function mostrarCarrito() {
      // --- EXTRACCIÓN DIRECTA Y REAL DESDE LA PÁGINA DEL BCV ---
$urlBcv = "https://www.bcv.org.ve";
$tasaCambio = 526.86940000; // Colocamos la tasa real que ves en pantalla como respaldo principal

// Configuramos la conexión simulando un navegador para que el BCV permita la lectura segura
$opciones = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n",
        "timeout" => 4 // Tiempo máximo de espera: 4 segundos
    ]
];
$contexto = stream_context_create($opciones);
$html = @file_get_contents($urlBcv, false, $contexto);

if ($html !== false) {
    // Desactivamos errores temporales de HTML mal estructurado para procesar limpiamente
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($doc);
    
    // Buscamos directamente el contenedor exacto del Dólar ($ USD) en la web del BCV
    $nodoDolar = $xpath->query('//div[@id="dolar"]//strong');
    
    if ($nodoDolar->length > 0) {
        // Extraemos el texto, limpiamos espacios y cambiamos la coma decimal por punto para PHP
        $textoPrecio = trim($nodoDolar->item(0)->nodeValue);
        $textoPrecio = str_replace('.', '', $textoPrecio); // Quitamos puntos de miles si los hay
        $textoPrecio = str_replace(',', '.', $textoPrecio); // Convertimos coma a punto decimal
        
        if (is_numeric($textoPrecio) && (float)$textoPrecio > 0) {
            $tasaCambio = (float)$textoPrecio;
        }
    }
}
// --------------------------------------------------------

// Calculamos los totales reales del carrito
$totalFinal = 0;
if (isset($_SESSION["carrito"])) {
    foreach ($_SESSION["carrito"] as $item) {
        $totalFinal += $item["precio"] * $item["cantidad"];
    }
}

// Ahora sí, la multiplicación usará los 526.86 o la tasa exacta del día del BCV
$totalBolivares = $totalFinal * $tasaCambio;

// Incluimos tu vista limpia
include "views/carrito.php";
}
    // Añade el producto a la sesión y salta de una vez a la vista del carrito
    public function añadir() {
        if (isset($_GET["id"]) && isset($_GET["nombre"])) {
            $id = $_GET["id"];
            $cant = isset($_GET["cantidad"]) ? intval($_GET["cantidad"]) : 1;
            
            if (!isset($_SESSION["carrito"])) {
                $_SESSION["carrito"] = array();
            }

            // Guardamos o actualizamos el producto con los datos limpios de la URL
            $_SESSION["carrito"][$id] = array(
                "id_producto" => $id,
                "nombre" => $_GET["nombre"],
                "precio" => $_GET["precio"],
                "cantidad" => $cant
            );
        }
        
        // ¡LA ORDEN DIRECTA! Después de agregar, salta directo a mostrar el carrito
        echo "<script>window.location.href = 'index.php?action=ver_carrito';</script>";
        exit();
    }

    // Elimina un producto específico del carrito
    public function eliminarItem() {
        if (isset($_GET["id"])) {
            $id = $_GET["id"];
            if (isset($_SESSION["carrito"][$id])) {
                unset($_SESSION["carrito"][$id]);
            }
        }
        echo "<script>window.location.href = 'index.php?action=ver_carrito';</script>";
        exit();
    }

    // Procesa el guardado en la base de datos
    // Procesa el guardado en la base de datos
    public function finalizarCompra() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // 1. PRIMERO: Si el carrito está vacío, detener el flujo
        if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
            echo "<script>alert('El carrito está vacío.'); window.location.href='index.php?action=ver_catalogo';</script>";
            exit();
        }

        // 2. SEGUNDO: Recuperar datos esenciales del usuario logueado
        // Asegúrate de que al loguearse guardes el ID en $_SESSION['id_usuario'] o $_SESSION['id']
        $id_usuario = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : (isset($_SESSION['id']) ? $_SESSION['id'] : null);
        
        if (!$id_usuario) {
            echo "<script>alert('Debes iniciar sesión para finalizar la compra.'); window.location.href='index.php?action=login';</script>";
            exit();
        }

        // 3. TERCERO: Armamos el mensaje de WhatsApp mientras leemos el carrito
        $telefono = "584127818865"; 
        $mensaje = "¡Hola! *La Providencia* 🛒\n";
        $mensaje .= "Deseo finalizar mi compra con los siguientes productos:\n\n";
        
        $totalGeneral = 0;
        foreach ($_SESSION['carrito'] as $item) {
            $nombre = isset($item['nombre']) ? $item['nombre'] : 'Producto';
            $precio = isset($item['precio']) ? $item['precio'] : 0;
            $cantidad = isset($item['cantidad']) ? $item['cantidad'] : 1;
            
            $subtotal = $precio * $cantidad;
            $totalGeneral += $subtotal;

            $mensaje .= "• *{$nombre}* (x{$cantidad}) - \${$precio}\n";
        }

        $mensaje .= "\n💰 *Total a pagar:* \${$totalGeneral}\n";
        $mensaje .= "Forma de pago: A convenir\n";
        $mensaje .= "¡Quedo atento para coordinar la entrega! ✨";

        $mensajeURL = urlencode($mensaje);
        $urlWhatsApp = "https://api.whatsapp.com/send?phone={$telefono}&text={$mensajeURL}";

        /* ==========================================================================
           CONEXIÓN REAL CON EL MODELO - AQUÍ SE GUARDA EN LA BASE DE DATOS
           ========================================================================== */
        // Llamamos al método de tu VentaModel pasando las variables reales
        $resultado = $this->model->guardarVentaModel($id_usuario, $totalGeneral, $_SESSION['carrito']);

        if ($resultado) {
            // Quitamos el descuento de stock de aquí porque tu modelo YA LO HACE en procesarRetiro()
            
            // Una vez guardado con éxito en la base de datos, vaciamos el carrito
            unset($_SESSION["carrito"]);
            
            // Lanzamos la alerta y redireccionamos a WhatsApp
            echo "<script type='text/javascript'>
                    alert('¡Pedido procesado con éxito! Registrado en el sistema. Conectando con WhatsApp...');
                    window.location.href = '{$urlWhatsApp}';
                  </script>";
            exit(); 
        } else {
            // Por si ocurre un error inesperado en la transacción de la BD
            echo "<script>alert('Hubo un problema al registrar tu pedido en la base de datos. Inténtalo de nuevo.'); window.location.href='index.php?action=ver_carrito';</script>";
            exit();
        }
    }

    // Listar pagos pendientes y pagados para el administrador
    public function pagosPendientes() {
        // 1. Traemos los pendientes y los pagados por separado usando tu método existente
        $pendientes = $this->model->obtenerPorEstado('pendiente');
        $pagados = $this->model->obtenerPorEstado('pagado');
        
        // 2. Los unimos en un solo arreglo para que tu vista los recorra juntos
        $ventas = array_merge($pendientes, $pagados);
        
        // 3. Cargamos la vista pasándole todos los datos
        include "views/pagos_pendientes.php";
    }
    // Listar retiros (solo los que ya fueron pagados)
   // Listar retiros (solo los que ya fueron pagados)
    // Listar retiros (los que ya fueron pagados y los ya entregados)
    public function retiroPedidos() {
        // 1. Traemos los pedidos pagados y los ya retirados por separado usando tu método
        $pagados = $this->model->obtenerPorEstado('pagado');
        $retirados = $this->model->obtenerPorEstado('retirado');
        
        // 2. Los unimos en un solo arreglo para que la vista los recorra juntos
        $ventas = array_merge($pagados, $retirados);
        
        include "views/retiro_pedidos.php";
    }

    // Cambiar estado a pagado
    public function confirmarPago($id) {
        $this->model->actualizarEstado($id, 'pagado');
        header("Location: index.php?action=pagos_pendientes");
        exit();
    }

    // Cambiar a retirado y descontar stock
   // Cambiar a retirado y descontar stock
    public function procesarRetiro($id) {
        if (isset($id) && !empty($id)) {
            // 1. El modelo actualiza el estado a 'retirado' en la base de datos
            $this->model->actualizarEstado($id, 'retirado');
            
            // 2. El modelo busca los productos de la venta y resta el stock de forma segura
            $this->model->descontarStockDeVenta($id);
        }
        
        // Redireccionamos limpiamente de vuelta a la vista de retiros
        header("Location: index.php?action=retiro_pedidos");
        exit();
    }
    public function verDetalle($id) {
        if (isset($id) && !empty($id)) {
            // Consultamos al modelo usando los métodos que acabamos de crear
            $venta = $this->model->obtenerVenta($id);
            $detalles = $this->model->obtenerDetalles($id);
            
            // Incluimos la vista limpia dentro de la carpeta views
            include "views/detalle_venta.php";
        } else {
            echo "<script>alert('ID de venta no válido.'); window.location.href='index.php?action=pagos_pendientes';</script>";
            exit();
        }
    }
}
?>