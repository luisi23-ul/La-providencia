<?php 
// views/editar_producto.php 
if(isset($p) && $p): 
?>
<article class="card-formulario">
    <link rel="stylesheet" href="public/css/formulario_producto.css?v=<?php echo time(); ?>">

    <header class="formulario-header">
        <h2>Actualizar Producto</h2>
        <p class="subtitle">Modifica los detalles del producto seleccionado en el catálogo de La Providencia.</p>
    </header>
    
    <form id="formEditar" action="index.php?action=actualizar_producto" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $p->id; ?>">

        <fieldset class="grupo-control">
            <label class="etiqueta-formulario">Nombre del Producto:</label>
            <input type="text" name="nombre" value="<?php echo $p->nombre_producto; ?>" class="providencia-field" required>
        </fieldset>

        <fieldset class="grupo-control">
            <label class="etiqueta-formulario">Descripción:</label>
            <textarea name="desc" class="providencia-field" rows="4" required><?php echo $p->descripcion; ?></textarea>
        </fieldset>

        <section class="fila-dual">
            <fieldset class="grupo-control">
                <label class="etiqueta-formulario">Precio Unitario ($):</label>
                <input type="number" step="0.01" name="precio" value="<?php echo $p->precio; ?>" class="providencia-field" required>
            </fieldset>
            
            <fieldset class="grupo-control">
                <label class="etiqueta-formulario">Stock en Inventario:</label>
                <input type="number" name="stock" value="<?php echo $p->stock; ?>" class="providencia-field" required>
            </fieldset>
        </section>

        <fieldset class="grupo-control">
            <label class="etiqueta-formulario">Imagen del Producto:</label>
            <section class="fila-dual" style="align-items: center; gap: 20px; border: 1px solid #e2e8f0; padding: 15px; border-radius: 10px; background: #f8fafc;">
                <?php if(!empty($p->imagen)): ?>
                    <figure style="margin: 0; text-align: center;">
                        <img src="public/uploads/<?php echo $p->imagen; ?>" width="70" height="70" style="border-radius: 8px; object-fit: cover; border: 2px solid #cbd5e1; display: block; margin: 0 auto 5px;">
                        <small style="color: #64748b; font-size: 0.75rem; display: block; max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo $p->imagen; ?></small>
                    </figure>
                <?php else: ?>
                    <span class="sin-foto" style="color: #64748b; font-size: 0.85rem; font-style: italic;">No hay imagen cargada actualmente.</span>
                <?php endif; ?>
                
                <span class="custom-file-upload" style="flex: 1; margin: 0;">
                    <i class="fas fa-cloud-upload-alt"></i> Reemplazar Imagen
                    <input type="file" name="imagen" class="input-file-providencia" accept="image/*">
                </span>
            </section>
        </fieldset>

        <footer class="formulario-acciones">
            <input type="button" value="GUARDAR CAMBIOS" onclick="document.getElementById('formEditar').submit();" class="btn-providencia-save">
            
            <a href="index.php?action=listado_productos" class="btn-providencia-link btn-secondary-satin">
                <i class="fas fa-boxes"></i>VOLVER
            </a>
        </footer>
    </form>
</article>

<?php else: ?>
    <section class="card-formulario" style="text-align: center; max-width: 500px; margin: 80px auto; padding: 40px;">
        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #ef4444; margin-bottom: 20px;"></i>
        <p style="color: #0f172a; font-weight: 700; font-size: 1.2rem; margin: 0 0 10px;">¡Ups! Algo salió mal</p>
        <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 25px;">No se pudo encontrar la información del producto para editar.</p>
        <a href="index.php?action=listado_productos" class="btn-satin-nuevo" style="text-decoration: none; display: inline-flex; justify-content: center;">
            Regresar al Listado
        </a>
    </section>
<?php endif; ?>