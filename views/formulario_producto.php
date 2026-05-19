<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="public/css/formulario_producto.css?v=<?php echo time(); ?>">

<article class="card-formulario">
    
    <header class="formulario-header">
        <h2>Nuevo Componente</h2>
        <p class="subtitle">Añade los detalles del producto para el catálogo.</p>
    </header>
    
    <form id="formProducto" action="index.php?action=guardar_producto" method="POST" enctype="multipart/form-data">
        
        <fieldset class="grupo-control">
            <label>Nombre del Producto</label>
            <input type="text" name="nombre_producto" class="providencia-field" placeholder="Ej: Ventilador Industrial" required>
        </fieldset>

        <fieldset class="grupo-control">
            <label>Categoría</label>
            <select name="id_categoria" class="providencia-field" required>
                <option value="">Selecciona...</option>
                <option value="1">Aluminio</option>
                <option value="2">Lencería</option>
                <option value="3">Plástico</option>
            </select>
        </fieldset>

        <fieldset class="grupo-control">
            <label>Descripción Técnica</label>
            <textarea name="descripcion" class="providencia-field" rows="4" placeholder="Especificaciones del componente..."></textarea>
        </fieldset>

        <section class="fila-dual">
            <fieldset class="grupo-control">
                <label>Precio ($)</label>
                <input type="number" step="0.01" name="precio" class="providencia-field" required placeholder="0.00">
            </fieldset>
            
            <fieldset class="grupo-control">
                <label>Stock</label>
                <input type="number" name="stock" class="providencia-field" required placeholder="0">
            </fieldset>
        </section>

        <fieldset class="grupo-control">
            <label>Imagen del Producto</label>
            <span class="custom-file-upload">
                <i class="fas fa-cloud-upload-alt"></i> Seleccionar Imagen
                <input type="file" name="imagen" class="input-file-providencia" accept="image/*" required>
            </span>
        </fieldset>

        <footer class="formulario-acciones">
            <input type="button" value="GUARDAR PRODUCTO" class="btn-providencia-save" onclick="document.getElementById('formProducto').submit();">
            
            <a href="index.php?action=ver_catalogo" class="btn-providencia-link btn-success-satin">
                <i class="fas fa-images"></i> VER CATÁLOGO
            </a>
            
            <a href="index.php?action=listado_productos" class="btn-providencia-link btn-secondary-satin">
                <i class="fas fa-boxes"></i> INVENTARIO
            </a>
        </footer>

    </form>
</article>

</main> 