<section class="contenedor-reporte">
    <link rel="stylesheet" href="public/css/pagos.css?v=<?php echo time(); ?>">
    
    <header class="reporte-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Reporte: <?php echo htmlspecialchars($nombreMetodo ?? 'Método'); ?></h2>
        
        <div class="acciones-exportar">
        <a href="index.php?action=exportar_excel&tipo=efectivo$" class="btn">
    Descargar Reporte Efectivo
</a>

<a href="index.php?action=exportar_excel&tipo=inventario" class="btn">
    Descargar Inventario
</a>
        </div>
    </header>
    
    <article class="tarjeta-total">
        <p>Total Recaudado en Dólares: <strong>$<?php echo number_format($totalUSD ?? 0, 2); ?></strong></p>
        <p>Total Recaudado en Bolívares: <strong><?php echo number_format($totalBS ?? 0, 2); ?> Bs</strong></p>
    </article>

    <table class="tabla-ventas">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>Total ($)</th>
                <th>Total (Bs)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($ventas)): ?>
                <?php foreach ($ventas as $v): ?>
                <tr>
                    <td><?php echo htmlspecialchars($v->nombre_cliente); ?></td>
                    <td><?php echo $v->fecha; ?></td>
                    <td>$<?php echo number_format($v->total, 2); ?></td>
                    <td><?php echo number_format($v->total_bs ?? 0, 2); ?> Bs</td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4">No hay ventas registradas con este método.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>