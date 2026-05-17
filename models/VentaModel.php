<?php
require_once 'config/db.php';

class VentaModel {

    private $db;

    public function __construct() {
        // Conexión limpia y automática usando tu clase Database
        $this->db = Database::connect();
    }

    // Función principal para registrar la venta y su desglose de productos
    public function guardarVentaModel($id_usuario, $total, $productosCarrito) {
        try {
            // 1. Iniciamos una transacción por seguridad
            // Si un producto falla al guardarse, no se registra nada en la base de datos
            $this->db->beginTransaction();

            // 2. Insertamos el encabezado de la venta
            $sqlVenta = "INSERT INTO ventas (id_usuario, total, fecha) VALUES (?, ?, NOW())";
            $stmt = $this->db->prepare($sqlVenta);
            $stmt->execute([$id_usuario, $total]);
            
            // Recuperamos el ID que la base de datos le asignó a esta venta
            $idVenta = $this->db->lastInsertId();

            // 3. Insertamos cada artículo del carrito en el detalle
            foreach ($productosCarrito as $item) {
                $sqlDetalle = "INSERT INTO detalle_ventas 
                    (id_venta, id_producto, cantidad, precio_unitario, subtotal) 
                    VALUES (?, ?, ?, ?, ?)";
                
                $stmtDetalle = $this->db->prepare($sqlDetalle);
                
                // Calculamos el subtotal multiplicando precio por cantidad
                $subtotal = $item['precio'] * $item['cantidad'];
                
                $stmtDetalle->execute([
                    $idVenta, 
                    $item['id_producto'], 
                    $item['cantidad'], 
                    $item['precio'], 
                    $subtotal
                ]);
            }

            // Si todo salió bien, guardamos los cambios definitivamente
            $this->db->commit();
            return true;

        } catch (Exception $e) {
            // Si algo falla en el camino, deshacemos todo para no dejar datos corruptos
            $this->db->rollBack();
            return false;
        }
    }
    public function finalizarCompra() {
    // 1. Validar que el carrito tenga productos
    if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
        echo "<script>window.location.href='index.php?action=ver_catalogo';</script>";
        exit();
    }

    $telefono = "584127818865"; 
    $mensaje = "¡Hola! *La Providencia* 🛒\n";
    $mensaje .= "Deseo finalizar mi compra con los siguientes productos:\n\n";
    
    $totalGeneral = 0;

    // 2. Recorrer el carrito para armar el texto
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

    // 3. Codificar el mensaje de forma segura para la URL
    $mensajeURL = urlencode($mensaje);
    
    // API Nativa y gratuita de WhatsApp
    $urlWhatsApp = "https://api.whatsapp.com/send?phone={$telefono}&text={$mensajeURL}";

    // 4. LA SOLUCIÓN: Forzar la apertura con JavaScript saltando bloqueos de cabecera
    echo "<script type='text/javascript'>
            window.location.href = '{$urlWhatsApp}';
          </script>";
    exit();
}
}
?>