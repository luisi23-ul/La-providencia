<?php
require_once "models/VentaModel.php";

class VentaController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Carga la tabla con los productos seleccionados
    public function mostrarCarrito() {
        // Usamos la 'C' mayúscula si tu archivo en views se llama Carrito.php
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
    public function finalizarCompra() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // 1. PRIMERO: Si el carrito ya está vacío de entrada, detener el flujo
    if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
        echo "<script>alert('El carrito está vacío.'); window.location.href='index.php?action=ver_catalogo';</script>";
        exit();
    }

    // 2. SEGUNDO: Armamos la URL de WhatsApp MIENTRAS el carrito aún tiene los productos guardados
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

    // 3. TERCERO: Ejecutas la lógica de tu Base de Datos (Tu código actual del modelo)
    // Supongamos que aquí llamas a tu modelo: $resultado = $this->ventaModelo->guardarVenta(...);
    // (Usa la lógica que ya tienes implementada para definir $resultado)
    $resultado = true; 

    if ($resultado) {
        
        // 🚀 AQUÍ HACEMOS LA MAGIA: Recorremos el carrito para restar el stock en la BD
        require_once 'models/ProductoModel.php';
        $productoModelo = new ProductoModel(); // Instanciamos el modelo de productos

        foreach ($_SESSION['carrito'] as $item) {
            $id_producto = $item['id_producto'];
            $cantidad_comprada = $item['cantidad'];

            // Llamamos a una función en el modelo que reste: stock_actual - cantidad_comprada
            $productoModelo->descontarStock($id_producto, $cantidad_comprada);
        }

        // Una vez descontado el inventario de todos los productos, borramos la sesión
        unset($_SESSION["carrito"]);
        
        // Lanzamos la alerta y mandamos directo a WhatsApp
        echo "<script type='text/javascript'>
                alert('¡Pedido procesado con éxito! Descontando del inventario y conectando con WhatsApp...');
                window.location.href = '{$urlWhatsApp}';
              </script>";
        exit(); 
    }
}
}
?>