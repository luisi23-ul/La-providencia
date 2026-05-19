<link rel="stylesheet" href="public/css/listado_productos.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">

<section class="main-dashboard-content">

    <article class="card-inventario">
        
        <header class="inventario-header">
            <h2>Inventario Actual</h2>
            
            <nav class="inventario-header-acciones">
                <a href="index.php?action=dashboard" class="btn-regresar-panel" title="Volver al Panel Principal">
                    <i class="fas fa-home"></i> Panel Admin
                </a>
                
                <a href="index.php?action=formulario_producto" class="btn-satin-nuevo" title="Agregar un nuevo producto">
                    <i class="fas fa-plus"></i> Nuevo
                </a>
            </nav>
        </header>

        <section class="tabla-responsiva-contenedor">
            <table class="tabla-inventario">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Descripción</th>
                        <th>Categoría</th>
                        <th>Imagen</th>
                        <th class="texto-centrado">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($listaProductos as $p): ?>
                    <tr>
                        <td class="columna-producto"><?php echo $p->nombre_producto; ?></td>
                        <td class="columna-precio">$<?php echo number_format($p->precio, 2); ?></td>
                        <td class="columna-stock"><?php echo $p->stock; ?> unidades</td>
                        <td class="columna-descripcion" title="<?php echo $p->descripcion; ?>"><?php echo $p->descripcion; ?></td>
                        <td class="columna-categoria">
                            <span class="badge-categoria"><?php echo $p->id_categoria; ?></span>
                        </td>
                        <td class="columna-imagen">
                            <?php if($p->imagen): ?>
                                <img src="public/uploads/<?php echo $p->imagen; ?>" alt="Producto">
                            <?php else: ?>
                                <span class="sin-foto">Sin foto</span>
                            <?php endif; ?>
                        </td>
                        <td class="columna-acciones texto-centrado">
                            <button type="button" class="btn-accion btn-editar" title="Editar" onclick="location.href='index.php?action=editar&id=<?php echo $p->id; ?>'">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn-accion btn-eliminar" title="Eliminar" onclick="if(confirm('¿Seguro que deseas eliminar este producto?')) location.href='index.php?action=eliminar_producto&id=<?php echo $p->id; ?>'">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        
    </article>

</section>