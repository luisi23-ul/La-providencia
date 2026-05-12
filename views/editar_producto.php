<?php if(isset($p) && $p): ?>
<article style="max-width: 650px; margin: 20px auto; border: 2px solid #0052d4; padding: 30px; background: #f0f7ff; border-radius: 10px; font-family: Arial, sans-serif;">
    <header>
        <h2 style="color: #0052d4; text-align: center; margin-bottom: 25px; text-transform: uppercase;">Actualizar Producto</h2>
    </header>
    
    <form id="formEditar" action="index.php?action=actualizar_producto" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $p->id; ?>">

        <p>
            <label style="color: #003a94; font-weight: bold;">Nombre del Producto:</label><br>
            <input type="text" name="nombre" value="<?php echo $p->nombre_producto; ?>" style="width: 100%; padding: 12px; border: 1px solid #0052d4; border-radius: 5px;" required>
        </p>

        <p>
    <label style="color: #003a94; font-weight: bold;">Descripción:</label><br>
    <textarea name="desc" style="width: 100%; padding: 12px; border: 1px solid #0052d4; border-radius: 5px; height: 100px;" required><?php echo $p->descripcion; ?></textarea>
</p>

        <p>
            <label style="color: #003a94; font-weight: bold;">Precio Unitario ($):</label><br>
            <input type="number" step="0.01" name="precio" value="<?php echo $p->precio; ?>" style="width: 100%; padding: 12px; border: 1px solid #0052d4; border-radius: 5px;" required>
        </p>

        <p>
            <label style="color: #003a94; font-weight: bold;">Stock en Inventario:</label><br>
            <input type="number" name="stock" value="<?php echo $p->stock; ?>" style="width: 100%; padding: 12px; border: 1px solid #0052d4; border-radius: 5px;" required>
        </p>

        <p>
    <label style="color: #003a94; font-weight: bold;">Imagen del Producto:</label><br>
    <?php if(!empty($p->imagen)): ?>
        <img src="public/uploads/<?php echo $p->imagen; ?>" width="150" style="border: 2px solid #0052d4; border-radius: 8px;">
        <br>
        <small>Archivo: <?php echo $p->imagen; ?></small>
    <?php else: ?>
        <p>No hay imagen cargada actualmente.</p>
    <?php endif; ?>
    <br>
    <input type="file" name="imagen" accept="image/*">
</p>

        <p style="text-align: center; margin-top: 30px;">
            <input type="button" value="GUARDAR CAMBIOS" onclick="document.getElementById('formEditar').submit();" style="background: #0052d4; color: white; padding: 15px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; font-size: 1.1em;">
        </p>

        <footer style="text-align: center; margin-top: 20px;">
            <a href="index.php?action=listado" style="color: #0052d4; text-decoration: none; font-weight: bold;">← Volver al Listado</a>
        </footer>
    </form>
</article>
<?php else: ?>
    <section style="text-align: center; margin-top: 50px;">
        <p style="color: red; font-weight: bold;">Error: No se pudo encontrar la información del producto para editar.</p>
        <a href="index.php?action=listado" style="color: #0052d4;">Regresar</a>
    </section>
<?php endif; ?>