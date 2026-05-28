<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">

<section class="panel-administracion">
    <?php include 'sidebar.php'; ?>

    <main class="main-dashboard-content">
        <nav class="navegacion-superior">
            <a href="index.php?action=dashboard" class="btn-regresar-panel">
                <i class="fas fa-arrow-left"></i>Volver
            </a>
        </nav>

        <section class="inv-container">
            
            <header class="inv-header">
                <h2>Inventario Actual</h2>
                <nav class="inv-actions">
                    <a href="index.php?action=formulario_producto" class="inv-btn-add">
                        <i class="fas fa-plus"></i> Nuevo
                    </a>
                    <a href="index.php?action=exportar_pdf&tipo=inventario" target="_blank" class="inv-btn-pdf">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <a href="index.php?action=exportar_excel&tipo=inventario" class="inv-btn-excel">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                </nav>
            </header>

            <table class="inv-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Descripción</th>
                        <th>Categoría</th>
                        <th>Imagen</th>
                        <th class="inv-text-center">Acciones</th>
                    </tr>
                </thead>
               <tbody>
    <?php foreach($listaProductos as $p): ?>
    <tr>
        <td><strong><?php echo htmlspecialchars($p->nombre_producto); ?></strong></td>
        
        <td class="precio-destacado">$<?php echo number_format($p->precio, 2); ?></td>
        
        <td class="<?php echo ($p->stock < 5) ? 'stock-alerta' : ''; ?>">
            <?php echo $p->stock; ?>
        </td>
        
        <td><?php echo htmlspecialchars($p->descripcion); ?></td>
        
        <td>
            <?php 
            // Definimos clases según el ID (ajusta según tus IDs reales)
            $badgeClass = ($p->id_categoria == 1) ? 'badge-cocina' : 'badge-hogar';
            ?>
            <span class="badge <?php echo $badgeClass; ?>">
                <?php echo htmlspecialchars($p->id_categoria); ?>
            </span>
        </td>
        
        <td>
            <?php if($p->imagen): ?>
                <img src="public/uploads/<?php echo $p->imagen; ?>" class="inv-img">
            <?php else: ?>
                <span class="inv-sin-foto">Sin foto</span>
            <?php endif; ?>
        </td>
        
        <td class="inv-text-center">
            <button class="inv-btn-edit" onclick="location.href='index.php?action=editar&id=<?php echo $p->id; ?>'">
                <i class="fas fa-edit"></i>
            </button>
            <button class="inv-btn-del" type="button" onclick="confirmarEliminar(<?php echo $p->id; ?>)">
    <i class="fas fa-trash-alt"></i>
</button>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
            </table>
        </section>
    </main>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="public/js/scripts.js"></script>