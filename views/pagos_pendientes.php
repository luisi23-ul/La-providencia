<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">

<section class="panel-modulo-contenedor">
    
    <header class="pagos-header">
        <h2>Pagos Pendientes</h2>
        
        <a href="index.php?action=exportar_pdf&tipo=pendientes" target="_blank" class="btn-exportar-pdf">📄 PDF Pagos</a>
        <a href="index.php?action=exportar_excel&tipo=pendientes" class="btn-exportar-excel">📊 Excel Pagos</a>
        <a href="index.php?action=dashboard" class="btn-regresar-panel">← Panel Principal</a>
    </header>

    <table class="tabla-modulo-admin">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>Método</th>
                <th>Total ($)</th>
                <th>Total (Bs)</th> 
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ventas as $venta): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($venta->nombre_cliente); ?></strong></td>
                <td><?php echo htmlspecialchars($venta->fecha); ?></td>
                <td><span class="metodo-badge"><?php echo htmlspecialchars($venta->nombre_metodo ?? 'N/A'); ?></span></td>
                <td class="monto-usd">$<?php echo number_format($venta->total, 2); ?></td>
                <td class="monto-bs"><?php echo number_format($venta->total_bs ?? 0, 2); ?> Bs</td>
                <td>
                    <?php if ($venta->estado == 'pendiente'): ?>
                        <span class="badge badge-pendiente">Pendiente</span>
                    <?php else: ?>
                        <span class="badge badge-pagado">Pagado</span>
                    <?php endif; ?>
                </td>
                <td class="acciones-tabla-flex">
                    <?php if ($venta->estado == 'pendiente'): ?>
                        <a href="index.php?action=confirmar_pago&id=<?php echo $venta->id; ?>" class="btn-tabla-success">Confirmar Pago</a>
                    <?php else: ?>
                        <span class="texto-verificado">✓ Pago Verificado</span>
                    <?php endif; ?>
                    
                    <a href="index.php?action=detalle_venta&id=<?php echo $venta->id; ?>" class="btn-tabla-info">Ver Detalle</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>