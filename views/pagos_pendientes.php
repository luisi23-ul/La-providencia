<link rel="stylesheet" href="public/css/confirmar_pago.css">

<section class="card-pagos">
    
    <header class="pagos-header">
        <h2>Pagos Pendientes</h2>
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
                <?php foreach ($ventas as $venta): ?>
                <tr>
                    <td><?php echo htmlspecialchars($venta->nombre); ?></td>
                    <td><?php echo htmlspecialchars($venta->fecha); ?></td>
                    <td>$<?php echo number_format($venta->total, 2); ?></td>
                    <td>
                        <?php if ($venta->estado == 'pendiente'): ?>
                            <span class="badge" style="background-color: #ffedd5; color: #ea580c; padding: 5px 10px; border-radius: 15px; font-weight: bold;">Pendiente</span>
                        <?php else: ?>
                            <span class="badge" style="background-color: #d1fae5; color: #065f46; padding: 5px 10px; border-radius: 15px; font-weight: bold;">Pagado</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($venta->estado == 'pendiente'): ?>
                            <a href="index.php?action=confirmar_pago&id=<?php echo $venta->id; ?>" class="btn-success" style="padding: 5px 10px; background-color: #28a745; color: white; border-radius: 4px; text-decoration: none;">Confirmar Pago</a>
                        <?php else: ?>
                            <span style="color: #10b981; font-weight: bold;">✓ Pago Verificado</span>
                        <?php endif; ?>
                        
                        <a href="index.php?action=detalle_venta&id=<?php echo $venta->id; ?>" class="btn-info" style="padding: 5px 10px; background-color: #17a2b8; color: white; border-radius: 4px; text-decoration: none; margin-left: 5px;">Ver Detalle</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
    
</section>