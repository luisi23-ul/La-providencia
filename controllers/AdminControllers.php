<?php
class AdminControllers {
    private $productoModelo;

    public function __construct() {
        // Instancia el modelo para usar despues
        require_once 'models/ProductoModel.php';
        $this->productoModelo = new ProductoModel();
    }

    public function mostrarDashboard() {
        // Busca exactamente el archivo views/dashboard.php
        include "views/dashboard.php"; 
    }

    public function mostrarPanel() {
        include 'views/formulario_producto.php';
    }

    //carga la tabla con todos los producto
    public function mostrarListado() {
        // Le pedimos al modelo todos los productos para la tabla
        $listaProductos = $this->productoModelo->obtenerTodos();
        include 'views/listado_productos.php';
    }

    public function catalogo() {
    $listaProductos = $this->productoModelo->obtenerProductos();
    
    //Carga el catálogo
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

            // Subida física de la image
            $ruta_temp = $_FILES['imagen']['tmp_name'];
            $destino = "public/uploads/" . $datos['imagen'];

            if ($this->productoModelo->registrarProducto($datos)) {
                move_uploaded_file($ruta_temp, $destino);
    
                echo "<script>alert('¡Componente Publicado!'); window.location='index.php?action=dashboard';</script>";
            }
        }
    }

    //  para eliminar
public function eliminar() {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        $this->productoModelo->borrarProducto($id);
    }
    
    // Redirecciona limpio a la tabla de productos activa
    header("Location: index.php?action=listado_productos");
    exit();
}

//  para abrir el formulario de edición
public function editar() {
    $id = $_GET['id'];
    
    $p = $this->productoModelo->obtenerPorId($id);
    require_once 'views/editar_producto.php';
}

public function actualizar() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $id = $_POST['id'];

        //  se busca el producto actual para recuperar el nombre de la imagen vieja
        $p = $this->productoModelo->obtenerPorId($id);
        $nombreImagen = $p->imagen; 

        // nueva imagen si el usuario seleccionó una
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
            // Generamos un nombre único para evitar que archivos con el mismo nombre se borren
           // Línea 86 (Ajustada para limpiar espacios)
        $nombreLimpio = str_replace(' ', '_', $_FILES['imagen']['name']); 
        $nombreImagen = time() . "_" . $nombreLimpio;
            
           
        $rutaDestino = $_SERVER['DOCUMENT_ROOT'] . "/La-providencia/public/uploads/" . $nombreImagen;
            
            // Movemos el archivo desde la carpeta temporal de PHP a la carpeta del proyecto
            move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino);
        }

        // Prepara el arreglo de datos con los nombres que se coneta con el modelo
        $datos = [
            'id'     => $id,
            'nombre' => $_POST['nombre'],
            'precio' => $_POST['precio'],
            'stock'  => $_POST['stock'],
            'desc'   => $_POST['desc'],
            'imagen' => $nombreImagen 
        ];

        //  la actualización y se redirecciona al listado
        if ($this->productoModelo->modificarproducto($datos)) {
            header("Location: index.php?action=listado_productos");
            exit(); 
        } else {
            echo "Error al intentar actualizar el producto.";
        }
    }
}
 public function grafico_barras() {
    require_once 'models/ProductoModel.php';
    $productoModelo = new ProductoModel();

    // Traemos de la base de datos el array estructurado con el inventario crítico y ventas
    // Asegurándonos de que 'inventario' contenga los 5 con MENOS stock
    $datosEstadisticas = $productoModelo->obtenerEstadisticasGenerales();

    require_once 'views/grafico_barras.php';
}

public function grafico_torta() {
    require_once 'models/ProductoModel.php';
    $productoModelo = new ProductoModel();

    // Llamamos a la misma estructura para que el JSON reciba exactamente el mismo array
    $datosEstadisticas = $productoModelo->obtenerEstadisticasGenerales();

    require_once 'views/grafico_tortas.php';
}
}