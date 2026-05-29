<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<section class="panel-administracion">
    <?php include 'sidebar-master.php'; ?>

<main class="main-view-sistema">
     <nav class="navegacion-superior">
            <a href="index.php?action=dashboard_master" class="btn-regresar-master">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </nav>

    <header class="master-form-header">
        <h2><i class="fas fa-cog"></i> Configuración del Sistema</h2>
        <p>Gestiona la identidad visual y datos de contacto de tu tienda.</p>
        <div class="master-divider"></div>
    </header>

    <form action="index.php?action=actualizar_configuracion" method="POST" enctype="multipart/form-data" class="master-form-grid">
        
        <article class="card-formulario-master">
            <h3 class="master-section-title"><i class="fas fa-store"></i> Datos Generales</h3>
            <div class="form-col-principal-card">
                <fieldset class="grupo-control">
                    <label>Título Encabezado</label>
                    <input type="text" name="titulo_hero" class="providencia-field-master" value="<?php echo htmlspecialchars($config->titulo_hero); ?>">
                </fieldset>

                <fieldset class="grupo-control">
                    <label>Subtítulo Encabezado</label>
                    <textarea name="subtitulo_hero" class="providencia-field-master" rows="3"><?php echo htmlspecialchars($config->subtitulo_hero); ?></textarea>
                </fieldset>

                <fieldset class="grupo-control">
                    <label>Dirección</label>
                    <input type="text" name="direccion" class="providencia-field-master" value="<?php echo htmlspecialchars($config->direccion); ?>">
                </fieldset>

                <fieldset class="grupo-control">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" class="providencia-field-master" value="<?php echo htmlspecialchars($config->telefono); ?>">
                </fieldset>

                <fieldset class="grupo-control">
                    <label>Logo de la tienda</label>
                    <input type="file" name="logo" class="providencia-field-master" accept="image/*">
                    <input type="hidden" name="logo_path_actual" value="<?php echo htmlspecialchars($config->logo_path); ?>">
                </fieldset>

                <fieldset class="grupo-control">
                    <label>Texto Pie de Página</label>
                    <textarea name="footer_texto" class="providencia-field-master" rows="2"><?php echo htmlspecialchars($config->footer_texto); ?></textarea>
                </fieldset>
            </div>
        </article>

        <article class="card-formulario-master">
            <h3 class="master-section-title"><i class="fas fa-palette"></i> Paleta de Colores</h3>
            <div class="master-inner-box">
                <section class="master-permissions-grid" style="grid-template-columns: 1fr;">
                    
                    <label class="color-picker-item">
                        <input type="color" name="color_primario" value="<?php echo $config->color_primario; ?>">
                        <span>Primario (Azul Marca)</span>
                    </label>

                    <label class="color-picker-item">
                        <input type="color" name="color_secundario" value="<?php echo $config->color_secundario; ?>">
                        <span>Secundario (Oscuro)</span>
                    </label>

                    <label class="color-picker-item">
                        <input type="color" name="color_terciario" value="<?php echo $config->color_terciario; ?>">
                        <span>Terciario (Footer)</span>
                    </label>

                    <label class="color-picker-item">
                        <input type="color" name="color_cuaternario" value="<?php echo $config->color_cuaternario; ?>">
                        <span>Cuaternario (Texto Muted)</span>
                    </label>

                </section>
            </div>
        </article>

        <footer class="master-form-footer">
            <button type="submit" class="btn-save-master">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
        </footer>
    </form>
</main>
</section>