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

    // 4. CUARTO: Evaluamos el resultado de la base de datos
    if ($resultado) {
        // AHORA SÍ: Vaciamos el carrito porque la URL de WhatsApp ya se guardó de forma segura arriba
        unset($_SESSION["carrito"]);
        
        // Lanzamos la alerta y mandamos en línea recta a la API de WhatsApp
        echo "<script type='text/javascript'>
                alert('¡Compra realizada con éxito! Conectando con WhatsApp para coordinar la entrega...');
                window.location.href = '{$urlWhatsApp}';
              </script>";
        exit(); 
    } else {
        echo "<script type='text/javascript'>
                alert('Hubo un error al procesar su compra.'); 
                window.location.href = 'index.php?action=ver_carrito';
              </script>";
        exit();
    }
}
}
?>