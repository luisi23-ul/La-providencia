<?php
// Asegúrate de incluir la conexión
require_once 'config/db.php'; 
$db = Database::connect();

// Obtenemos la configuración de la base de datos
$stmt = $db->query("SELECT * FROM sistema WHERE id = 1");
$config = $stmt->fetch(PDO::FETCH_OBJ);

// Si no hay datos, definimos valores por defecto para que no falle
if (!$config) {
    $config = (object) ['nombre_empresa' => 'La Providencia', 'color_primario' => '#2c3e50'];
}
?>

<?php
// Esto verifica si estamos en una vista de Admin o Master
$esAdminO_Master = in_array($action, $acciones_master) || in_array($action, $acciones_admin);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?php echo htmlspecialchars($config->titulo_principal); ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="public/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="public/css/header.css?v=<?php echo time(); ?>">

    <?php if ($action == 'inicio' || $action == ''): ?>
        <link rel="stylesheet" href="public/css/home-style.css?v=<?php echo time(); ?>">
    <?php endif; ?>
</head>
<body class="<?php echo ($action == 'nosotros') ? 'page-about' : ''; ?>">

<?php if (!$esAdminO_Master && $action != 'login'): ?>
<header class="main-header">
    <nav class="nav-container">
        <section class="nav-brand">
            <a href="index.php?action=inicio">
                <img src="public/img/logo-removebg.png" alt="La Providencia" class="brand-logo">
            </a>
        </section>
        
        <ul class="nav-menu">
            <li><a href="index.php?action=inicio" class="nav-link">Inicio</a></li>
            <li><a href="index.php?action=ver_catalogo" class="nav-link">Catálogo</a></li>
            <li><a href="index.php?action=inicio#nosotros" class="nav-link">Nosotros</a></li>
            <li><a href="index.php?action=inicio#contacto" class="nav-link">Contacto</a></li>
        </ul>

        <section class="nav-actions">
            <?php 
            // Lógica del Carrito MANTENIDA
            $cantidadTotal = 0;
            if (isset($_SESSION["carrito"])) {
                foreach ($_SESSION["carrito"] as $item) {
                    $cantidadTotal += $item["cantidad"];
                }
            }
            $claseCarrito = ($cantidadTotal > 0) ? 'con-productos' : '';
            ?>

            <a href="index.php?action=ver_carrito" class="nav-carrito <?php echo $claseCarrito; ?>">
                <i class="fas fa-shopping-cart"></i>
                <span class="contador-carrito"><?php echo $cantidadTotal; ?></span>
            </a>

            <a href="index.php?action=login_usuario" class="action-link login-btn"><i class="fas fa-user"></i> Sesión</a>
            <a href="index.php?action=registro" class="register-cta-btn"><i class="fas fa-user-plus"></i> Registro</a>
        </section>
    </nav>
</header>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function mostrarAlerta(icono, titulo, mensaje, redireccion = null) {
        Swal.fire({
            icon: icono, // 'success', 'error', 'warning', 'info'
            title: titulo,
            text: mensaje,
            confirmButtonColor: '#0052d4',
            confirmButtonText: 'Aceptar'
        }).then((result) => {
            if (redireccion) {
                window.location.href = redireccion;
            }
        });
    }
</script>

 

