<?php
require_once 'config/db.php';

class VentaModel {

    private $db;

    public function __construct() {
        // Conexión limpia y automática usando tu clase Database
        $this->db = Database::connect();
    }

    // Función principal para registrar la venta y su desglose de productos
    public function guardarVentaModel($id_usuario, $total, $carrito, $id_metodo_pago) {
    try {
        // 1. Iniciamos una transacción por seguridad
        $this->db->beginTransaction();

        // 2. Insertamos el encabezado de la venta (INCLUYENDO el id_metodo_pago)
        $sqlVenta = "INSERT INTO ventas (id_usuario, total, id_metodo_pago, fecha, estado) VALUES (?, ?, ?, NOW(), 'pendiente')";
        $stmt = $this->db->prepare($sqlVenta);
        // Pasamos los tres valores correspondientes
        $stmt->execute([$id_usuario, $total, $id_metodo_pago]);
        
        // Recuperamos el ID que la base de datos le asignó a esta venta
        $idVenta = $this->db->lastInsertId();

        // 3. Insertamos cada artículo del carrito en el detalle
        // Corregido: usando la variable $carrito que recibes como parámetro
        foreach ($carrito as $item) {
            $sqlDetalle = "INSERT INTO detalle_ventas 
                (id_venta, id_producto, cantidad, precio_unitario, subtotal) 
                VALUES (?, ?, ?, ?, ?)";
            
            $stmtDetalle = $this->db->prepare($sqlDetalle);
            
            // Calculamos el subtotal multiplicando precio por cantidad
            $subtotal = $item['precio'] * $item['cantidad'];
            
            $stmtDetalle->execute([
                $idVenta, 
                $item['id_producto'], // Asegúrate de que coincida con la clave en tu array
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
        try {
            // Modificamos el WHERE para que traiga tanto 'pendiente' como 'pagado'
            // de esta manera saldrán todos los registros en el listado general del PDF/Excel
            $sql = "SELECT v.id, u.nombre, v.fecha, v.total, v.estado 
                    FROM ventas v
                    JOIN usuarios u ON v.id_usuario = u.id
                    WHERE v.estado = 'pendiente' OR v.estado = 'pagado'
                    ORDER BY v.fecha DESC";
                    
            $stmt = $this->db->prepare($sql);
            // Ejecutamos limpio sin amarrarlo a un solo estado estricto
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            exit("Error en VentaModel::obtenerPorEstado: " . $e->getMessage());
        }
    
    
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
    // Usamos JOIN para traer el nombre del usuario y el método, igual que en las otras consultas
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
// En VentaModel.php
// En models/VentaModel.php

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
    // IMPORTANTE: Agregué el mismo alias 'AS nombre_cliente' aquí
    $sql = "SELECT v.*, u.nombre AS nombre_cliente 
            FROM ventas v 
            JOIN usuarios u ON v.id_usuario = u.id 
            WHERE v.estado = 'pagado' 
            ORDER BY v.fecha DESC";
    return $this->db->query($sql)->fetchAll(PDO::FETCH_OBJ);
}

public function confirmarEntrega($id_venta) {
    // ASEGÚRATE DE QUE SEA UN UPDATE, NO UN DELETE
    $sql = "UPDATE ventas SET estado = 'entregado' WHERE id = :id";
    $stmt = $this->db->prepare($sql);
    return $stmt->execute(['id' => $id_venta]);
}
// En models/VentaModel.php
public function obtenerVentasPorEstado($estadosArray) {
    // Convertimos el array a una cadena para el IN
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