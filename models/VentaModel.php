<?php
require_once 'config/db.php';

class VentaModel {

    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    // Función principal para registrar la venta y su desglose de productos
    // aqui tampoco tocar nada edgar
    public function guardarVentaModel($id_usuario, $total, $carrito, $id_metodo_pago) {
    try {
        $this->db->beginTransaction();

        $sqlVenta = "INSERT INTO ventas (id_usuario, total, id_metodo_pago, fecha, estado) VALUES (?, ?, ?, NOW(), 'pendiente')";
        $stmt = $this->db->prepare($sqlVenta);
        // Pasamos los tres valores correespondientes
        $stmt->execute([$id_usuario, $total, $id_metodo_pago]);
        
        // Recuperamm el ID que la base de datos le asignó a esta ventan
        $idVenta = $this->db->lastInsertId();

        foreach ($carrito as $item) {
            $sqlDetalle = "INSERT INTO detalle_ventas 
                (id_venta, id_producto, cantidad, precio_unitario, subtotal) 
                VALUES (?, ?, ?, ?, ?)";
            
            $stmtDetalle = $this->db->prepare($sqlDetalle);
            
            // Calculamos el subtotal multiplicando precio por cantidadp
            $subtotal = $item['precio'] * $item['cantidad'];
            
            $stmtDetalle->execute([
                $idVenta, 
                $item['id_producto'], 
                $item['cantidad'], 
                $item['precio'], 
                $subtotal
            ]);
        }

        $this->db->commit();
        return true;

    } catch (Exception $e) {
        $this->db->rollBack();
        return false;
    }
}
  public function obtenerPorEstado($estado) {
        try {
            // para poder extraer los archivoos de pdf exel
            $sql = "SELECT v.id, u.nombre, v.fecha, v.total, v.estado 
                    FROM ventas v
                    JOIN usuarios u ON v.id_usuario = u.id
                    WHERE v.estado = 'pendiente' OR v.estado = 'pagado'
                    ORDER BY v.fecha DESC";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            exit("Error en VentaModel::obtenerPorEstado: " . $e->getMessage());
        }
    
    
}
    public function actualizarEstado($id_venta, $nuevo_estado) {
        $sql = "UPDATE ventas SET estado = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        $stmt->execute([$nuevo_estado, $id_venta]);
    }

    // edgar aca yo modifique con la correcion de la prof para el descuento derl stock
    public function descontarStockDeVenta($id) {
       
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
    
    // obtienee los datos generales de una venta específica 
    public function obtenerVenta($id) {
        $sql = "SELECT v.*, u.nombre 
                FROM ventas v 
                JOIN usuarios u ON v.id_usuario = u.id 
                WHERE v.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // 2. Obttener todos los productos asociados a esa venta (Tabla de productos)
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

   public function obtenerPorMetodo($metodo) {
    // Usamos JOIN para traer el nombre del usuario y el meetodo igual que en las otras consultas
    $sql = "SELECT v.*, u.nombre 
            FROM ventas v
            JOIN usuarios u ON v.id_usuario = u.id
            WHERE v.id_metodo_pago = (SELECT id FROM metodos_pago WHERE nombre = ?)
            ORDER BY v.fecha DESC";
            
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$metodo]);
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

// Para traer solo los pendientes

public function obtenerPendientes() {
    // Usamos el alias 'nombre_cliente' para que sea consistente
    $sql = "SELECT v.*, u.nombre AS nombre_cliente 
            FROM ventas v 
            JOIN usuarios u ON v.id_usuario = u.id 
            WHERE v.estado = 'pendiente' 
            ORDER BY v.fecha DESC";
    return $this->db->query($sql)->fetchAll(PDO::FETCH_OBJ);
}

public function obtenerRetiros() {
    $sql = "SELECT v.*, u.nombre AS nombre_cliente 
            FROM ventas v 
            JOIN usuarios u ON v.id_usuario = u.id 
            WHERE v.estado = 'pagado' 
            ORDER BY v.fecha DESC";
    return $this->db->query($sql)->fetchAll(PDO::FETCH_OBJ);
}

public function confirmarEntrega($id_venta) {
    $sql = "UPDATE ventas SET estado = 'entregado' WHERE id = :id";
    $stmt = $this->db->prepare($sql);
    return $stmt->execute(['id' => $id_venta]);
}
// tenemos las ventas por es estado
public function obtenerVentasPorEstado($estadosArray) {
    $in = "'" . implode("','", $estadosArray) . "'";
    
    $sql = "SELECT v.*, u.nombre AS nombre_cliente, mp.nombre AS nombre_metodo
            FROM ventas v 
            JOIN usuarios u ON v.id_usuario = u.id 
            LEFT JOIN metodos_pago mp ON v.id_metodo_pago = mp.id
            WHERE v.estado IN ($in) 
            ORDER BY v.fecha DESC";
            
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

}
?>