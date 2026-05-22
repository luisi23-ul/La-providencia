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
            // En models/VentaModel.php dentro de guardarVentaModel
        $sqlVenta = "INSERT INTO ventas (id_usuario, total, fecha, estado) VALUES (?, ?, NOW(), 'pendiente')";
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
  public function obtenerPorEstado($estado) {
        // Buscamos las ventas que coincidan exactamente con el estado enviado
        $sql = "SELECT v.*, u.nombre 
                FROM ventas v 
                JOIN usuarios u ON v.id_usuario = u.id 
                WHERE v.estado = ? 
                ORDER BY v.fecha DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$estado]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    public function actualizarEstado($id_venta, $nuevo_estado) {
        // Usamos nombres ultra claros para no equivocarnos en el orden del array
        $sql = "UPDATE ventas SET estado = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        // El primer '?' es el estado, el segundo '?' es el ID.
        $stmt->execute([$nuevo_estado, $id_venta]);
    }
    public function descontarStockDeVenta($id) {
        // 1. Obtenemos los productos asociados a la venta utilizando la conexión del modelo ($this->db)
        $sql = "SELECT id_producto, cantidad FROM detalle_ventas WHERE id_venta = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $detalles = $stmt->fetchAll(PDO::FETCH_OBJ);

        // 2. Recorremos cada producto y restamos del inventario
        foreach ($detalles as $d) {
            $sqlUp = "UPDATE productos SET stock = stock - ? WHERE id = ?";
            $stmtUp = $this->db->prepare($sqlUp);
            $stmtUp->execute([$d->cantidad, $d->id_producto]);
        }
    }
    
    // 1. Obtener los datos generales de una venta específica (Encabezado)
    public function obtenerVenta($id) {
        $sql = "SELECT v.*, u.nombre 
                FROM ventas v 
                JOIN usuarios u ON v.id_usuario = u.id 
                WHERE v.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // 2. Obtener todos los productos asociados a esa venta (Tabla de productos)
    // 2. Obtener todos los productos asociados a esa venta (Tabla de productos)
    public function obtenerDetalles($id) {
        // Cambiamos p.nombre por p.nombre_producto para que coincida con tu tabla de productos
        $sql = "SELECT dv.*, p.nombre_producto AS producto_nombre 
                FROM detalle_ventas dv 
                JOIN productos p ON dv.id_producto = p.id 
                WHERE dv.id_venta = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
?>