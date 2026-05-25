
<article class="card-formulario">
    <link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="public/css/formulario_producto.css?v=<?php echo time(); ?>">
    
    <header class="formulario-header">
        <h2>Configuración Global</h2>
        <p class="subtitle">Personaliza la identidad, contactos y ubicación del sistema.</p>
    </header>
    
    <form id="formConfig" action="index.php?action=actualizar_configuracion" method="POST" enctype="multipart/form-data">
        
        <section class="fila-dual">
            <fieldset class="grupo-control">
                <label>Nombre de la Empresa</label>
                <input type="text" name="nombre_empresa" class="providencia-field" value="<?php echo $config->nombre_empresa; ?>" required>
            </fieldset>
            <fieldset class="grupo-control">
                <label>Título Principal</label>
                <input type="text" name="titulo_principal" class="providencia-field" value="<?php echo $config->titulo_principal; ?>" required>
            </fieldset>
        </section>

        <section class="fila-dual">
            <fieldset class="grupo-control">
                <label>Teléfono de Contacto</label>
                <input type="text" name="telefono" class="providencia-field" value="<?php echo $config->telefono ?? ''; ?>">
            </fieldset>
            <fieldset class="grupo-control">
                <label>Correo Electrónico</label>
                <input type="email" name="email_contacto" class="providencia-field" value="<?php echo $config->email_contacto ?? ''; ?>">
            </fieldset>
        </section>

        <fieldset class="grupo-control">
            <label>Dirección Física</label>
            <textarea name="direccion" class="providencia-field" rows="2"><?php echo $config->direccion ?? ''; ?></textarea>
        </fieldset>

        <fieldset class="grupo-control">
            <label>Enlace de Google Maps</label>
            <input type="url" name="mapa_url" class="providencia-field" value="<?php echo $config->mapa_url ?? ''; ?>">
        </fieldset>

        <section class="fila-dual">
            <fieldset class="grupo-control">
                <label>Color Primario</label>
                <input type="color" name="color_primario" class="providencia-field" value="<?php echo $config->color_primario; ?>">
            </fieldset>
            <fieldset class="grupo-control">
                <label>Color Secundario</label>
                <input type="color" name="color_secundario" class="providencia-field" value="<?php echo $config->color_secundario; ?>">
            </fieldset>
        </section>

        <fieldset class="grupo-control">
            <label>Texto del Footer</label>
            <textarea name="footer_texto" class="providencia-field" rows="2"><?php echo $config->footer_texto; ?></textarea>
        </fieldset>

        <fieldset class="grupo-control">
            <label>Logo del Sistema</label>
            <span class="custom-file-upload">
                <i class="fas fa-image"></i> Cambiar Logo
                <input type="file" name="logo" class="input-file-providencia" accept="image/*">
            </span>
        </fieldset>

        <footer class="formulario-acciones">
            <button type="submit" class="btn-providencia-save" style="border:none; cursor:pointer; width: 100%; text-align: center;">
                GUARDAR CAMBIOS
            </button>
            
            <a href="index.php?action=dashboard_master" class="btn-providencia-link btn-secondary-satin">
                <i class="fas fa-home"></i> VOLVER AL PANEL
            </a>
        </footer>

    </form>
</article>