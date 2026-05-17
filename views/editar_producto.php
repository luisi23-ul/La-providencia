<?php 
//views/editar_producto.php 
if(isset($p) && $p): 
?>
<article class="contenedor-editar">
     <link rel="stylesheet" href="public/css/editar_producto.css?v=<?php echo time(); ?>">
    <header>
        <h2 class="titulo-editar">Actualizar Producto</h2>
    </header>
    
    <form id="formEditar" action="index.php?action=actualizar_producto" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $p->id; ?>">

        <p>
            <label class="etiqueta-formulario">Nombre del Producto:</label><br>
            <input type="text" name="nombre" value="<?php echo $p->nombre_producto; ?>" class="input-formulario" required>
        </p>

        <p>
            <label class="etiqueta-formulario">Descripción:</label><br>
            <textarea name="desc" class="textarea-formulario" required><?php echo $p->descripcion; ?></textarea>
        </p>

        <p>
            <label class="etiqueta-formulario">Precio Unitario ($):</label><br>
            <input type="number" step="0.01" name="precio" value="<?php echo $p->precio; ?>" class="input-formulario" required>
        </p>

        <p>
            <label class="etiqueta-formulario">Stock en Inventario:</label><br>
            <input type="number" name="stock" value="<?php echo $p->stock; ?>" class="input-formulario" required>
        </p>

        <p>
            <label class="etiqueta-formulario">Imagen del Producto:</label><br>
            <?php if(!empty($p->imagen)): ?>
                <img src="public/uploads/<?php echo $p->imagen; ?>" width="150" class="imagen-actual">
                <br>
                <small>Archivo: <?php echo $p->imagen; ?></small>
            <?php else: ?>
                <span class="texto-error-imagen">No hay imagen cargada actualmente.</span>
            <?php endif; ?>
            <br><br>
            <input type="file" name="imagen" accept="image/*">
        </p>

        <p class="p-boton-guardar">
            <input type="button" value="GUARDAR CAMBIOS" onclick="document.getElementById('formEditar').submit();" class="boton-guardar">
        </p>

        <footer class="pie-formulario">
            <a href="index.php?action=listado_productos" class="enlace-volver">← Volver al Listado</a>
        </footer>
    </form>
</article>
<?php else: ?>
    <section class="seccion-error">
        <p class="mensaje-error">Error: No se pudo encontrar la información del producto para editar.</p>
        <a href="index.php?action=listado_productos" class="enlace-volver">Regresar al Listado</a>
    </section>
<?php endif; ?>