<article class="card-formulario">
    <h2>Nuevo Componente</h2>
    <p class="subtitle">Añade los detalles del producto para el catálogo.</p>
    
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
            <textarea name="descripcion" class="providencia-field" rows="4"></textarea>
        </fieldset>

        <section class="fila-dual">
            <fieldset class="grupo-control">
                <label>Precio ($)</label>
                <input type="number" step="0.01" name="precio" class="providencia-field" required>
            </fieldset>
            <fieldset class="grupo-control">
                <label>stock</label>
                <input type="number" name="stock" class="providencia-field" required>
            </fieldset>
        </section>

        <fieldset class="grupo-control">
            <label>Imagen del Producto</label>
            <input type="file" name="imagen" class="input-file-providencia" accept="image/*" required>
        </fieldset>
<p style="text-align: center; margin-top: 30px;">
    <input type="button" value="GUARDAR PRODUCTO" onclick="document.getElementById('formAgregar').submit();" style="background: #0052d4; color: white; padding: 15px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; font-size: 1.1em;">
</p>

<p style="text-align: center; margin-top: 15px;">
    <a href="index.php?action=ver_catalogo" style="display: block; background: #28a745; color: white; padding: 12px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; font-size: 1.1em; text-decoration: none; box-sizing: border-box;">
        VER CATÁLOGO
    </a>
</p>

<p style="text-align: center; margin-top: 15px;">
    <a href="index.php?action=listado" style="display: block; background: #6c757d; color: white; padding: 12px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; font-size: 1.1em; text-decoration: none; box-sizing: border-box;">
        VOLVER AL INVENTARIO
    </a>
</p>