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
        if (!isset($_SESSION["id_usuario"])) {
            echo "<script>alert('Debe iniciar sesión para finalizar la compra'); window.location.href = 'index.php?action=login_usuario';</script>";
            return;
        }

        if (!isset($_SESSION["carrito"]) || empty($_SESSION["carrito"])) {
            echo "<script>alert('El carrito está vacío'); window.location.href = 'index.php?action=ver_catalogo';</script>";
            return;
        }

        $id_usuario = $_SESSION["id_usuario"];
        $total = 0;
        foreach ($_SESSION["carrito"] as $item) {
            $total += $item['precio'] * $item['cantidad'];
        }

        $objVentaModel = new VentaModel();
        $resultado = $objVentaModel->guardarVentaModel($id_usuario, $total, $_SESSION["carrito"]);

        if ($resultado) {
            unset($_SESSION["carrito"]);
            echo "<script>alert('¡Compra realizada con éxito! Gracias por preferir La Providencia.'); window.location.href = 'index.php?action=inicio';</script>";
        } else {
            echo "<script>alert('Hubo un error al procesar su compra.'); window.location.href = 'index.php?action=ver_carrito';</script>";
        }
    }
}
?>