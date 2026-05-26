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

    //  administradores CRUD
   public function gestionarAdmins() {
    // Permitir rol 1 (Master) Y rol 2 (Admin)
    if ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2) {
        die("Acceso denegado: No tienes permisos para gestionar administradores.");
    }
    
    $admins = $this->modelo->obtenerUsuariosPorRol(2);
    include 'views/gestionar_admins.php';
}

   // crea nuevos adm
public function crearAdmin() {
    // 1. Depuración: Ver qué recibe el controlador
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
    // Verificar que el ID viene en la URL
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        $this->modelo->eliminarAdminModel($id);
    }
    
    // Redirigir de vuelta al listado
    header("Location: index.php?action=gestionar_admins");
    exit();
}
   
 public function actualizarAdmin() {
    // 1. Recibir datos del formulario
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

    // 3. Redirigir de vuelta al listado
    header("Location: index.php?action=gestionar_admins");
    exit();
}

// En el método que carga la vista de configuración:
public function vistaConfiguracion() {
    $config = $this->modeloConfig->obtenerConfiguracion();
    
    // Asegúrate de que esta ruta existe físicamente
    $ruta = 'views/configuracion_sistema.php';
    if (file_exists($ruta)) {
        include $ruta;
    } else {
        die("Error: No se encontró el archivo en $ruta");
    }
}

 // editamos aqui la pagina
    public function editarConfiguracion() {
        if ($_SESSION['rol'] != 1) die("Acceso denegado");
        
        // Obtener datos actuales
        $config = $this->db->query("SELECT * FROM sistema WHERE id = 1")->fetch(PDO::FETCH_OBJ);
        include 'views/configuracion_sistema.php';
    }
// Actualizar los cambios de la pagin
public function actualizarConfiguracion() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $datos = [
            'nombre_empresa'   => $_POST['nombre_empresa'],
            'titulo_principal' => $_POST['titulo_principal'],
            'color_primario'   => $_POST['color_primario'],
            'color_secundario' => $_POST['color_secundario'],
            'footer_texto'     => $_POST['footer_texto'],
            'telefono'         => $_POST['telefono'],      
            'email_contacto'   => $_POST['email_contacto'],
            'direccion'        => $_POST['direccion'],     
            'mapa_url'         => $_POST['mapa_url'],      
            'logo'             => ''
        ];
        $this->modeloConfig->actualizarConfiguracionModel($datos);
        header("Location: index.php?action=configuracion_sistema");
    }
}

}