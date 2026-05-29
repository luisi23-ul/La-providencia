<?php
class AdminControllers {
    private $productoModelo;

    public function __construct() {
        // Instancia el modeloo
        require_once 'models/ProductoModel.php';
        $this->productoModelo = new ProductoModel();
        
    }

    public function mostrarDashboard() {
        include "views/dashboard.php"; 
    }

    public function mostrarPanel() {
        include 'views/formulario_producto.php';
    }

    // la tabla con todos los producto
    public function mostrarListado() {
        $listaProductos = $this->productoModelo->obtenerTodos();
        include 'views/listado_productos.php';
    }

public function catalogo() {
    // 1. Inicializar modelo si no lo has hecho en el constructor
    require_once 'models/ProductoModel.php';
    $this->productoModelo = new ProductoModel();

    // Capturar filtros
    $nombre = $_GET['busqueda'] ?? '';
    $id_cat = $_GET['id_categoria'] ?? '';

    //  Obtener datos
    $categorias = $this->productoModelo->obtenerCategorias();
    $listaProductos = $this->productoModelo->buscarProductos($nombre, $id_cat);

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

//  para abrir el formulario de edicio
public function editar() {
    $id = $_GET['id'];
    
    $p = $this->productoModelo->obtenerPorId($id);
    require_once 'views/editar_producto.php';
}
// actualiza elk producto
public function actualizar() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $id = $_POST['id'];

        //  se busca el producto actual para recuperar el nombre de la imagen vieja
        $p = $this->productoModelo->obtenerPorId($id);
        $nombreImagen = $p->imagen; 

        // nueva imagen si se coloca una
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $nombreLimpio = str_replace(' ', '_', $_FILES['imagen']['name']); 
        $nombreImagen = time() . "_" . $nombreLimpio;
            
           
        $rutaDestino = $_SERVER['DOCUMENT_ROOT'] . "/La-providencia/public/uploads/" . $nombreImagen;
            
            // Movemos el archivo desde la carpeta temporal de PHP a la carpeta de proyect
            move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino);
        }

        // Prepara el arreglo de datos con los nombres que se coneta con e modelo
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
    $datosEstadisticas = $productoModelo->obtenerEstadisticasGenerales();

    require_once 'views/grafico_barras.php';
}

public function grafico_torta() {
    require_once 'models/ProductoModel.php';
    $productoModelo = new ProductoModel();
    $datosEstadisticas = $productoModelo->obtenerEstadisticasGenerales();

    require_once 'views/grafico_tortas.php';
}
}