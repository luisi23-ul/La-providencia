<article class="card-formulario" style="max-width: 950px;">
    <header style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Inventario Actual</h2>
        <a href="index.php?action=admin" style="text-decoration: none;">
    <button type="button" style="background-color: #0052d4; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">
        + NUEVO
    </button>
</a>
        </span>
    </header>

    <table style="width: 100%; border-collapse: collapse; margin-top: 30px;">
        <thead>
            <tr style="border-bottom: 2px solid #0052d4; color: #0052d4; text-align: left;">
                <th style="padding: 15px;">Producto</th>
                <th style="padding: 15px;">Precio</th>
                <th style="padding: 15px;">Stock</th>
                 <th style="padding: 15px;">Descripción</th>
                 <th style="padding: 15px;">Categoría</th>
                   <th style="padding: 15px;">Imagen</th>


                <th style="padding: 15px; text-align: center;">Acciones</th>
            </tr>
        </thead>
        <tbody>
    <?php foreach($listaProductos as $p): ?>
    <tr style="border-bottom: 1px solid #eee;">
        <td style="padding: 15px;"><?php echo $p->nombre_producto; ?></td>
        <td style="padding: 15px;">$<?php echo number_format($p->precio, 2); ?></td>
        <td style="padding: 15px;"><?php echo $p->stock; ?> unidades</td>
        <td style="padding: 15px;"><?php echo $p->descripcion; ?></td>
        <td style="padding: 15px;"><?php echo $p->id_categoria; ?></td>
        <td style="padding: 15px;">
            <?php if($p->imagen): ?>
                <img src="public/uploads/<?php echo $p->imagen; ?>" width="50" style="border-radius: 5px;">
            <?php else: ?>
                <span>No hay imagen</span>
            <?php endif; ?>
        </td>
        <td style="padding: 15px; text-align: center;">
            <span style="cursor:pointer; margin-right: 15px;" title="Editar" onclick="location.href='index.php?action=editar&id=<?php echo $p->id; ?>'">✏️</span>
            <span style="cursor:pointer; color: #e74c3c;" title="Eliminar" onclick="if(confirm('¿Eliminar producto?')) location.href='index.php?action=eliminar_producto&id=<?php echo $p->id; ?>'">🗑️</span>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>