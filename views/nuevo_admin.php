<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<section class="panel-administracion">


<?php include 'sidebar-master.php'; ?>

<main class="main-view-container">
    
    <header class="master-action-header">
        <a href="index.php?action=gestionar_admins" class="btn-regresar-master">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </header>

    <article class="card-master-form">
        <header class="master-form-header">
            <h2><i class="fas fa-user-plus"></i> Nuevo Administrador</h2>
            <p>Define los accesos y credenciales del sistema.</p>
            <div class="master-divider"></div>
        </header>

        <form id="formAdmin" action="index.php?action=guardar_nuevo_admin" method="POST" class="master-form-grid">
            
        <article class="card-formulario-master">
            <h3 class="master-section-title"><i class="fas fa-user-edit"></i> Datos de Acceso</h3>
            <div class="form-col-principal form-col-principal-card">
                <fieldset class="grupo-control">
                    <label>Nombre Completo</label>
                    <input type="text" name="nombre" class="providencia-field-master" placeholder="Ej: Juan Pérez" required>
                </fieldset>

                <fieldset class="grupo-control">
                    <label>Correo Electrónico</label>
                    <input type="email" name="correo" class="providencia-field-master" placeholder="ejemplo@providencia.com" required>
                </fieldset>

                <fieldset class="grupo-control">
                    <label>Contraseña de Acceso</label>
                    <input type="password" name="clave" class="providencia-field-master" placeholder="********" required>
                </fieldset>
            </div>
        </article>

        <article class="card-formulario-master">
    <h3 class="master-section-title"><i class="fas fa-shield-alt"></i> Funciones Permitidas</h3>
    
    <div class="master-inner-box">
        <section class="master-permissions-grid">
            <label><input type="checkbox" name="permisos[]" value="cargar_producto"> Cargar Producto</label>
            <label><input type="checkbox" name="permisos[]" value="gestionar_productos"> Gestionar Productos</label>
            <label><input type="checkbox" name="permisos[]" value="graficos"> Estadísticas </label>
            <label><input type="checkbox" name="permisos[]" value="pagos"> Pagos Pendientes</label>
            <label><input type="checkbox" name="permisos[]" value="retiros"> Retiro de Pedidos</label>
            <label><input type="checkbox" name="permisos[]" value="metodos_pago"> Métodos de Pago</label>
        </section>
    </div>
</article>

            <footer class="master-form-footer">
                <button type="submit" class="btn-save-master">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </footer>

        </form>
    </article>
</main>
</section>