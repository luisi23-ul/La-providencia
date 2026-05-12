<?php
class AdminControllers {
    private $productoModelo;

    public function __construct() {
        // Instanciamos el modelo para usar despues
        require_once 'models/ProductoModel.php';
        $this->productoModelo = new ProductoModel();
    }

    
    public function mostrarDashboard() {
        include 'views/admin_dashboard.php';
    }

    public function mostrarPanel() {
        include 'views/formulario_producto.php';
    }

    //carga la tabla con todos los productos ---
    public function mostrarListado() {
        // Le pedimos al modelo todos los productos para la tabla
        $listaProductos = $this->productoModelo->obtenerTodos();
        include 'views/listado_productos.php';
    }

    public function catalogo() {
    $listaProductos = $this->productoModelo->obtenerProductos();
    
    //Carga la vista del catálogo
    require_once 'views/catalogo.php';
}

    public function agregar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $datos = [
                'nombre'    => $_POST['nombre_producto'],
                'desc'      => $_POST['descripcion'],
                'precio'    => $_POST['precio'],
                'stock'     => $_POST['stock'],
                'categoria' => $_POST['id_categoria'],
                'imagen'    => $_FILES['imagen']['name']
            ];

            // Subida física de la imagen
            $ruta_temp = $_FILES['imagen']['tmp_name'];
            $destino = "public/uploads/" . $datos['imagen'];

            if ($this->productoModelo->registrarProducto($datos)) {
                move_uploaded_file($ruta_temp, $destino);
    
                echo "<script>alert('¡Componente Publicado!'); window.location='index.php?action=dashboard';</script>";
            }
        }
    }

    // Función para eliminar
public function eliminar() {
    $id = $_GET['id'];
    $this->productoModelo->borrarProducto($id);
    header("Location: index.php?action=listado");
}

// Función para abrir el formulario de edición
public function editar() {
    $id = $_GET['id'];
    
    $p = $this->productoModelo->obtenerPorId($id);
    require_once 'views/editar_producto.php';
}

public function actualizar() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $id = $_POST['id'];

        // 1. Buscamos el producto actual para recuperar el nombre de la imagen vieja
        $p = $this->productoModelo->obtenerPorId($id);
        $nombreImagen = $p->imagen; 

        // nueva imagen si el usuario seleccionó una
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
            // Generamos un nombre único para evitar que archivos con el mismo nombre se borren
           // Línea 86 (Ajustada para limpiar espacios)
        $nombreLimpio = str_replace(' ', '_', $_FILES['imagen']['name']); 
        $nombreImagen = time() . "_" . $nombreLimpio;
            
           
        $rutaDestino = $_SERVER['DOCUMENT_ROOT'] . "/La-providencia/public/uploads/" . $nombreImagen;
            
            // Movemos el archivo desde la carpeta temporal de PHP a tu carpeta del proyecto
            move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino);
        }

        // Prepara el arreglo de datos con los nombres que espera el Modelo
        $datos = [
            'id'     => $id,
            'nombre' => $_POST['nombre'],
            'precio' => $_POST['precio'],
            'stock'  => $_POST['stock'],
            'desc'   => $_POST['desc'],
            'imagen' => $nombreImagen // Se envía la nueva o la que ya existía
        ];

        // 4. Ejecuta la actualización y redireccionamos al listado
        if ($this->productoModelo->modificarProducto($datos)) {
            header("Location: index.php?action=listado");
            exit(); 
        } else {
            echo "Error al intentar actualizar el producto.";
        }
    }
}
}