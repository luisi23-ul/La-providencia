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
}
?>