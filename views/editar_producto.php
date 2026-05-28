<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="public/css/formulario_producto.css?v=<?php echo time(); ?>">

<section class="panel-administracion">
    <?php include 'sidebar.php'; ?>

    <main class="main-dashboard-content">
    <nav class="navegacion-superior">
        <a href="index.php?action=listado_productos" class="btn-regresar-panel">
            <i class="fas fa-arrow-left"></i>Volver
        </a>
    </nav>
    
        <?php if(isset($p) && $p): ?>
        <article class="card-formulario">
            <header class="formulario-header">
                <h2>Actualizar Producto</h2>
                <p class="subtitle">Modifica los detalles del producto en el catálogo.</p>
            </header>
            
            <form id="formEditar" action="index.php?action=actualizar_producto" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $p->id; ?>">

                <fieldset class="grupo-control">
                    <label>Nombre del Producto</label>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($p->nombre_producto); ?>" class="providencia-field" required>
                </fieldset>

                <fieldset class="grupo-control">
                    <label>Categoría</label>
                    <select name="id_categoria" class="providencia-field" required>
                        <option value="1" <?php echo ($p->id_categoria == 1) ? 'selected' : ''; ?>>Aluminio</option>
                        <option value="2" <?php echo ($p->id_categoria == 2) ? 'selected' : ''; ?>>Lencería</option>
                        <option value="3" <?php echo ($p->id_categoria == 3) ? 'selected' : ''; ?>>Plástico</option>
                    </select>
                </fieldset>

                <fieldset class="grupo-control">
                    <label>Descripción Técnica</label>
                    <textarea name="desc" class="providencia-field" rows="4" required><?php echo htmlspecialchars($p->descripcion); ?></textarea>
                </fieldset>

                <section class="fila-dual">
                    <fieldset class="grupo-control">
                        <label>Precio ($)</label>
                        <input type="number" step="0.01" name="precio" value="<?php echo $p->precio; ?>" class="providencia-field" required>
                    </fieldset>
                    
                    <fieldset class="grupo-control">
                        <label>Stock</label>
                        <input type="number" name="stock" value="<?php echo $p->stock; ?>" class="providencia-field" required>
                    </fieldset>
                </section>

                <fieldset class="grupo-control">
                    <label>Imagen del Producto</label>
                    <section class="fila-dual" style="align-items: center; gap: 20px; border: 1px solid #e2e8f0; padding: 15px; border-radius: 10px; background: #f8fafc;">
                        <?php if(!empty($p->imagen)): ?>
                            <figure style="margin: 0; text-align: center;">
                                <img src="public/uploads/<?php echo $p->imagen; ?>" width="70" height="70" style="border-radius: 8px; object-fit: cover;">
                            </figure>
                        <?php endif; ?>
                        
                        <span class="custom-file-upload" style="flex: 1; margin: 0;">
                            <i class="fas fa-cloud-upload-alt"></i> Reemplazar Imagen
                            <input type="file" name="imagen" class="input-file-providencia" accept="image/*">
                        </span>
                    </section>
                </fieldset>

                <footer class="formulario-acciones">
                    <input type="button" value="GUARDAR CAMBIOS" onclick="document.getElementById('formEditar').submit();" class="btn-providencia-save">
                    
                </footer>
            </form>
        </article>
        <?php else: ?>
            <article class="card-formulario" style="text-align: center; padding: 40px;">
                <p>No se pudo encontrar la información para editar.</p>
                <a href="index.php?action=listado_productos">Regresar al Listado</a>
            </article>
        <?php endif; ?>

    </main>
</section>