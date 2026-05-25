<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="public/css/formulario_producto.css?v=<?php echo time(); ?>">

<article class="card-formulario">
    
    <header class="formulario-header">
        <h2>Nuevo Administrador</h2>
        <p class="subtitle">Registra los datos de acceso y asigna permisos iniciales.</p>
    </header>
    
    <form id="formAdmin" action="index.php?action=guardar_nuevo_admin" method="POST">
        
        <fieldset class="grupo-control">
            <label>Nombre Completo</label>
            <input type="text" name="nombre" class="providencia-field" placeholder="Ej: Juan Pérez" required>
        </fieldset>

        <fieldset class="grupo-control">
            <label>Correo Electrónico</label>
            <input type="email" name="correo" class="providencia-field" placeholder="ejemplo@providencia.com" required>
        </fieldset>

        <fieldset class="grupo-control">
            <label>Contraseña de Acceso</label>
            <input type="password" name="clave" class="providencia-field" placeholder="********" required>
        </fieldset>

        <fieldset class="grupo-control" style="background: #f8fafc; padding: 15px; border-radius: 10px; border: 1px solid #e2e8f0;">
            <label style="margin-bottom: 10px; display: block; font-weight: bold;">Funciones Permitidas</label>
            <section style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <label><input type="checkbox" name="permisos[]" value="cargar_producto"> Cargar Producto</label>
                <label><input type="checkbox" name="permisos[]" value="gestionar_productos"> Gestionar Productos</label>
                <label><input type="checkbox" name="permisos[]" value="graficos"> Gráficos Estadísticos</label>
                <label><input type="checkbox" name="permisos[]" value="pagos"> Pagos Pendientes</label>
                <label><input type="checkbox" name="permisos[]" value="retiros"> Retiro de Pedidos</label>
                <label><input type="checkbox" name="permisos[]" value="metodos_pago"> Métodos de Pago</label>
            </section>
        </fieldset>

        <footer class="formulario-acciones">
            <a href="#" class="btn-providencia-save" onclick="document.getElementById('formAdmin').submit(); return false;">
                GUARDAR ADMINISTRADOR
            </a>
            
            <a href="index.php?action=gestionar_admins" class="btn-providencia-link btn-secondary-satin">
                <i class="fas fa-users-cog"></i> VOLVER AL LISTADO
            </a>
        </footer>

    </form>
</article>