<link rel="stylesheet" href="public/css/confirmar_pago.css">

<section class="card-pagos">
    
    <header class="pagos-header">
        <h2>Retiro de Pedidos (Pagados)</h2>
        <a href="index.php?action=exportar_pdf&tipo=retiros" target="_blank" class="btn-regresar-panel" style="background-color: #4b5563; margin-right: 5px;">📄 PDF Retiros</a>
        <a href="index.php?action=exportar_excel&tipo=retiros" class="btn-regresar-panel" style="background-color: #4b5563; margin-right: 5px;">📊 Excel Retiros</a>
        
        <a href="index.php?action=dashboard" class="btn-regresar-panel">← Panel Principal</a>
    </header>

    <main class="tabla-responsiva-contenedor">
        <table class="tabla-pagos">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
           <tbody>
    <?php if (!empty($ventas)): ?>
        <?php foreach ($ventas as $venta): ?>
        <tr>
            <td><?php echo htmlspecialchars($venta->nombre); ?></td>
                <td><?php echo htmlspecialchars($venta->fecha); ?></td>
                <td>$<?php echo number_format($venta->total, 2); ?></td>
                
                <td>
                    <?php if ($venta->estado == 'pagado'): ?>
                        <span class="badge" style="background-color: #d1fae5; color: #065f46; padding: 5px 10px; border-radius: 15px; font-weight: bold; font-size: 0.85rem;">Listo para Retiro</span>
                    <?php else: ?>
                        <span class="badge" style="background-color: #e0f2fe; color: #0369a1; padding: 5px 10px; border-radius: 15px; font-weight: bold; font-size: 0.85rem;">Entregado</span>
                    <?php endif; ?>
                </td>

                <td>
                    <?php if ($venta->estado == 'pagado'): ?>
                        <a href="index.php?action=procesar_retiro&id=<?php echo $venta->id; ?>" class="btn-success" style="padding: 5px 10px; text-decoration: none; border-radius: 4px; color: white; background-color: #10b981; margin-right: 5px;">Entregar Pedido</a>
                    <?php else: ?>
                        <span style="color: #0284c7; font-weight: bold; margin-right: 5px;">✓ Pedido Entregado</span>
                    <?php endif; ?>

                    <a href="index.php?action=detalle_venta&id=<?php echo $venta->id; ?>" class="btn-info" style="padding: 5px 10px; text-decoration: none; border-radius: 4px; color: white; background-color: #3b82f6;">Ver Detalle</a>
                </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="5" style="text-align: center; padding: 20px; color: #666;">No hay pedidos listos para retiro.</td>
        </tr>
    <?php endif; ?>
</tbody>
        </table>
    </main>
    
</section>