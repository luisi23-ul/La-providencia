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
    // --- MANTENEMOS TU LÓGICA DE LA API DEL BCV SIN TOCAR NADA ---
    $urlBcv = "https://www.bcv.org.ve";
    $tasaCambio = 526.86940000; 

    $opciones = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n",
            "timeout" => 4 
        ]
    ];
    $contexto = stream_context_create($opciones);
    $html = @file_get_contents($urlBcv, false, $contexto);

    if ($html !== false) {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);
        $nodoDolar = $xpath->query('//div[@id="dolar"]//strong');
        
        if ($nodoDolar->length > 0) {
            $textoPrecio = trim($nodoDolar->item(0)->nodeValue);
            $textoPrecio = str_replace('.', '', $textoPrecio); 
            $textoPrecio = str_replace(',', '.', $textoPrecio); 
            
            if (is_numeric($textoPrecio) && (float)$textoPrecio > 0) {
                $tasaCambio = (float)$textoPrecio;
            }
        }
    }
    // --------------------------------------------------------

    // 1. AGREGADO: Consulta para obtener los métodos de pago de tu BD
    $db = Database::connect();
    $query = $db->query("SELECT * FROM metodos_pago");
    $metodos = $query->fetchAll(PDO::FETCH_OBJ);

    // Calculamos los totales reales del carrito
    $totalFinal = 0;
    if (isset($_SESSION["carrito"])) {
        foreach ($_SESSION["carrito"] as $item) {
            $totalFinal += $item["precio"] * $item["cantidad"];
        }
    }

    $totalBolivares = $totalFinal * $tasaCambio;

    // 2. Incluimos tu vista pasando la variable $metodos para que la use el <select>
    include "views/Carrito.php";
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

    if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
        echo "<script>alert('El carrito está vacío.'); window.location.href='index.php?action=ver_catalogo';</script>";
        exit();
    }

    // 1. CAPTURAR EL MÉTODO DE PAGO (Nuevo)
    $metodo_pago_id = isset($_POST['metodo_pago']) ? $_POST['metodo_pago'] : null;
    
    if (!$metodo_pago_id) {
        echo "<script>alert('Por favor, seleccione un método de pago.'); window.location.href='index.php?action=ver_carrito';</script>";
        exit();
    }

    $id_usuario = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : (isset($_SESSION['id']) ? $_SESSION['id'] : null);
    
    if (!$id_usuario) {
        echo "<script>alert('Debes iniciar sesión.'); window.location.href='index.php?action=login';</script>";
        exit();
    }

    $totalGeneral = 0;
    foreach ($_SESSION['carrito'] as $item) {
        $totalGeneral += ($item['precio'] * $item['cantidad']);
    }

    // 2. OBTENER NOMBRE DEL MÉTODO PARA EL MENSAJE (Opcional, para que quede bonito en WhatsApp)
    $db = Database::connect();
    $stmt = $db->prepare("SELECT nombre FROM metodos_pago WHERE id = ?");
    $stmt->execute([$metodo_pago_id]);
    $metodo = $stmt->fetch(PDO::FETCH_OBJ);
    $nombreMetodo = $metodo ? $metodo->nombre : "No especificado";

    // 3. MENSAJE DE WHATSAPP (Actualizado)
    $telefono = "584127818865"; 
    $mensaje = "¡Hola! *La Providencia* 🛒\n";
    $mensaje .= "Deseo finalizar mi compra:\n\n";
    
    foreach ($_SESSION['carrito'] as $item) {
        $mensaje .= "• *{$item['nombre']}* (x{$item['cantidad']}) - \${$item['precio']}\n";
    }

    $mensaje .= "\n💰 *Total:* \${$totalGeneral}\n";
    $mensaje .= "💳 *Método de pago:* {$nombreMetodo}\n"; // Aquí se añade
    $mensaje .= "¡Quedo atento! ✨";

    $urlWhatsApp = "https://api.whatsapp.com/send?phone={$telefono}&text=" . urlencode($mensaje);

    // 4. PASAR EL MÉTODO AL MODELO
    // Nota: Tu método guardarVentaModel ahora debe aceptar este parámetro extra
    $resultado = $this->model->guardarVentaModel($id_usuario, $totalGeneral, $_SESSION['carrito'], $metodo_pago_id);

    if ($resultado) {
        unset($_SESSION["carrito"]);
        echo "<script>alert('¡Pedido procesado!'); window.location.href = '{$urlWhatsApp}';</script>";
        exit();
    } else {
        echo "<script>alert('Error en la base de datos.'); window.location.href='index.php?action=ver_carrito';</script>";
        exit();
    }
}

    // Listar pagos pendientes y pagados para el administrador
  public function pagosPendientes() {
    $ventas = $this->model->obtenerVentasPorEstado(['pendiente', 'pagado']);
    
    // Cálculo de tasa
    $tasaCambio = $this->obtenerTasaBCV();
    foreach ($ventas as $venta) {
        $venta->total_bs = $venta->total * $tasaCambio;
    }
    
    include "views/pagos_pendientes.php";
}

private function obtenerTasaBCV() {
    $tasaCambio = 526.86940000; // Valor de respaldo
    $urlBcv = "https://www.bcv.org.ve";
    $opciones = ["http" => ["method" => "GET", "header" => "User-Agent: Mozilla/5.0", "timeout" => 4]];
    $contexto = stream_context_create($opciones);
    $html = @file_get_contents($urlBcv, false, $contexto);

    if ($html !== false) {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML($html);
        $xpath = new DOMXPath($doc);
        $nodoDolar = $xpath->query('//div[@id="dolar"]//strong');
        if ($nodoDolar->length > 0) {
            $textoPrecio = str_replace([".", ","], ["", "."], trim($nodoDolar->item(0)->nodeValue));
            if (is_numeric($textoPrecio)) $tasaCambio = (float)$textoPrecio;
        }
    }
    return $tasaCambio;
}
    // Listar retiros (solo los que ya fueron pagados)
   // Listar retiros (solo los que ya fueron pagados)
    // Listar retiros (los que ya fueron pagados y los ya entregados)
   public function retiroPedidos() {
    // ... tu código ...
    $sql = "SELECT v.*, u.nombre AS nombre_cliente, mp.nombre AS nombre_metodo
            FROM ventas v 
            JOIN usuarios u ON v.id_usuario = u.id 
            LEFT JOIN metodos_pago mp ON v.id_metodo_pago = mp.id
            WHERE v.estado IN ('pagado', 'retirado', 'entregado') 
            ORDER BY v.fecha DESC";
    // ... resto del código ...

            
    $stmt = $this->db->query($sql);
    $ventas = $stmt->fetchAll(PDO::FETCH_OBJ);

    // Calcular el total en Bs
   $tasaCambio = $this->obtenerTasaBCV();
    foreach ($ventas as $venta) {
        $venta->total_bs = $venta->total * $tasaCambio;
    }
    
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

 public function obtenerVentasPorMetodo($id_metodo) {
    $db = Database::connect();

    // 1. REPLICAMOS LA LÓGICA DEL BCV PARA TENER LA TASA EXACTA
    $tasaCambio = 526.86940000; // Valor por defecto
    $urlBcv = "https://www.bcv.org.ve";
    $opciones = ["http" => ["method" => "GET", "header" => "User-Agent: Mozilla/5.0", "timeout" => 4]];
    $contexto = stream_context_create($opciones);
    $html = @file_get_contents($urlBcv, false, $contexto);

    if ($html !== false) {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML($html);
        $xpath = new DOMXPath($doc);
        $nodoDolar = $xpath->query('//div[@id="dolar"]//strong');
        if ($nodoDolar->length > 0) {
            $textoPrecio = str_replace([".", ","], ["", "."], trim($nodoDolar->item(0)->nodeValue));
            if (is_numeric($textoPrecio)) $tasaCambio = (float)$textoPrecio;
        }
    }

    // 2. CONSULTA SQL (Ahora usamos la $tasaCambio real)
    $sql = "SELECT v.*, u.nombre AS nombre_cliente 
            FROM ventas v 
            JOIN usuarios u ON v.id_usuario = u.id 
            WHERE v.id_metodo_pago = ? 
            AND v.estado = 'pagado' 
            ORDER BY v.fecha DESC";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([$id_metodo]);
    $ventas = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    $totalUSD = 0;
    $totalBS = 0;

    foreach ($ventas as $v) {
        $totalUSD += $v->total;
        $v->total_bs = $v->total * $tasaCambio; // <--- USAMOS LA TASA DINÁMICA
        $totalBS += $v->total_bs;
    }
    
    return [
        'ventas' => $ventas, 
        'totalUSD' => $totalUSD, 
        'totalBS' => $totalBS
    ];
}

public function manejarReporte($id_metodo) {
    $datos = $this->obtenerVentasPorMetodo($id_metodo);
    
    // ESTAS VARIABLES SON LAS QUE DEBEN LLEGAR A LA VISTA
    $ventas = $datos['ventas'];
    $totalUSD = $datos['totalUSD'];
    $totalBS = $datos['totalBS'];
    // 3. Eliges la vista según el ID
    switch($id_metodo) {
        case 1: include "views/efectivobs.php"; break;
        case 2: include "views/efectivo$.php"; break;
        case 3: include  "views/trasferencia.php";break;
        case 4: include "views/pago_movil.php";; break;
        default: echo "Método no encontrado";
    }
}

public function confirmarEntrega() {
    $id = $_POST['id_venta'] ?? null;
    
    if ($id) {
        $modelo = new VentaModel($this->db);
        if ($modelo->confirmarEntrega($id)) {
            // Redirige de vuelta o muestra éxito sin haber borrado nada
            header("Location: index.php?action=retiro_pedidos&status=success");
        }
    }
}


}
?>