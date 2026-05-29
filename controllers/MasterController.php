<?php

class MasterController {
    private $db;
    private $modelo;
    private $modeloConfig;

    public function __construct($db) {
        $this->db = $db;
        require_once 'models/UsuarioModels.php';
        require_once 'models/ConfiguracionModel.php';
      $this->modelo = new UsuarioModels();
      $this->modeloConfig = new ConfiguracionModel($db);
    }

    public function mostrarDashboard_master() {
    include 'views/dashboard_master.php';
}

    //  administradores CRUDD
  public function gestionarAdmins() {
    if ($this->modelo === null) {
        require_once 'models/UsuarioModels.php';
        $this->modelo = new UsuarioModels();
    }

    //  Control de acceso
    if ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2) {
        die("Acceso denegado: No tienes permisos para gestionar administradores.");
    }
    
    $admins = $this->modelo->obtenerUsuariosPorRol(2);
    
    include 'views/gestionar_admins.php';
}

   // crea nuevos adm
public function crearAdmin() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (empty($_POST['nombre'])) {
            die("ERROR: El formulario no envió el campo 'nombre'. Verifica los 'name' de tus inputs.");
        }

        // Verifica modelo
        if (!$this->modelo) {
            die("ERROR: El modelo es NULL en el controlador.");
        }

        $permisos = isset($_POST['permisos']) ? implode(',', $_POST['permisos']) : '';
        
        $datos = [
            "nombre" => $_POST['nombre'],
            "correo" => $_POST['correo'],
            "clave"  => password_hash($_POST['clave'], PASSWORD_DEFAULT),
            "rol"    => 2,
            "permisos" => $permisos
        ];

        //  Ejecucuta
        $this->modelo->registrarUsuarioModel($datos);
        header("Location: index.php?action=gestionar_admins");
        exit();
    }
}
  //editamos los administradores
  public function editarAdmin() {
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
        die("Acceso denegado. Tu rol actual es: " . ($_SESSION['rol'] ?? 'No definido'));
    }

    $id = $_GET['id'];
    $a = $this->modelo->obtenerUsuarioPorId($id); 
    
    if (!$a) {
        die("Error: No se encontró el usuario con ID $id");
    }

    include 'views/editar_admin.php'; 
}

   public function eliminarAdmin() {
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        $this->modelo->eliminarAdminModel($id);
    }
    
    header("Location: index.php?action=gestionar_admins");
    exit();
}
   
 public function actualizarAdmin() {
    //  datos del formulario
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $clave = $_POST['clave'] ?? ''; // La contraseña es opcional
    $permisos = isset($_POST['permisos']) ? implode(',', $_POST['permisos']) : '';

    // 2. Llamar al modelo para guardar
    $datos = [
        'id' => $id, 
        'nombre' => $nombre, 
        'correo' => $correo, 
        'clave' => $clave, 
        'permisos' => $permisos
    ];

    $this->modelo->actualizarAdminModel($datos);

    header("Location: index.php?action=gestionar_admins");
    exit();
}


public function listarAdministradores() {
        // 1. Verificación de seguridad: Inicializar el modelo si es null
        if (!isset($this->modelo) || $this->modelo === null) {
            require_once 'models/UsuarioModels.php';
            $this->modelo = new UsuarioModels();
        }

        $admins = $this->modelo->obtenerUsuariosPorRol(2); 
        
        include "views/listado_administradores.php";
    }

    public function toggleAdmin() {
    //  Verificación de seguridad 
    if (!isset($_GET['id']) || !isset($_GET['estado'])) {
        die("Parámetros faltantes.");
    }

    $id = intval($_GET['id']);
    $estado = intval($_GET['estado']); // 1 para activo, 0 para inactivo

   
    $this->modelo->actualizarEstadoUsuario($id, $estado);

    header("Location: index.php?action=gestionar_admins");
    exit();
}


// En el método que carga la vista de configuración:
public function vistaConfiguracion() {
    $config = $this->modeloConfig->obtener();
    
    $ruta = 'views/configuracion_sistema.php';
    if (file_exists($ruta)) {
        include $ruta;
    } else {
        die("Error: No se encontró el archivo en $ruta");
    }
}

 public function editarConfiguracion() {
    require_once 'models/ConfiguracionModel.php';
    $modelo = new ConfiguracionModel($this->db);
    $config = $modelo->obtener();
    require_once 'views/configuracion_sistema.php';
}

public function actualizarConfiguracion() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo "<pre>"; print_r($_POST); echo "</pre>";
        $datos = $_POST;

        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
            $nombreArchivo = 'logo_' . time() . '.png';
            $rutaDestino = 'public/img/' . $nombreArchivo;
            move_uploaded_file($_FILES['logo']['tmp_name'], $rutaDestino);
            $datos['logo_path'] = $rutaDestino; // Pasamos la nueva ruta al modelo
        } else {
            $datos['logo_path'] = $_POST['logo_path_actual']; 
        }

        $modelo = new ConfiguracionModel($this->db);
        $modelo->actualizar($datos);
        header("Location: index.php?action=configuracion_sistema&status=ok");
        exit();
    }
}
}