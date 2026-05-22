<link rel="stylesheet" href="public/css/confirmar_pago.css">

<section class="card-pagos" style="max-width: 700px; margin: 30px auto; padding: 20px;">
    
    <header class="pagos-header" style="margin-bottom: 20px;">
        <h2>Detalle de la Venta #<?php echo $venta->id; ?></h2>
        <a href="javascript:history.back()" class="btn-regresar-panel" style="background-color: #6c757d; color: white; text-decoration: none; padding: 5px 10px; border-radius: 4px;">← Volver</a>
    </header>

    <p style="background-color: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 25px; border-left: 4px solid #17a2b8; line-height: 1.6;">
        <strong>Cliente:</strong> <?php echo htmlspecialchars($venta->nombre); ?><br>
        <strong>Fecha de Compra:</strong> <?php echo htmlspecialchars($venta->fecha); ?><br>
        <strong>Estado Actual:</strong> 
        <span class="badge" style="background-color: <?php echo ($venta->estado == 'pendiente') ? '#ffedd5' : '#d1fae5'; ?>; color: <?php echo ($venta->estado == 'pendiente') ? '#ea580c' : '#065f46'; ?>; padding: 3px 8px; border-radius: 12px; font-weight: bold; font-size: 0.85rem;">
            <?php echo htmlspecialchars($venta->estado); ?>
        </span>
    </p>

    <main class="tabla-responsiva-contenedor">
        <table class="tabla-pagos">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="text-align: center;">Cantidad</th>
                    <th style="text-align: right;">Precio Unitario</th>
                    <th style="text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($detalles)): ?>
                    <?php foreach ($detalles as $detalle): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($detalle->producto_nombre); ?></td>
                        <td style="text-align: center;"><?php echo $detalle->cantidad; ?> uni.</td>
                        <td style="text-align: right;">$<?php echo number_format($detalle->precio_unitario, 2); ?></td>
                        <td style="text-align: right; font-weight: bold;">
                            $<?php echo number_format($detalle->cantidad * $detalle->precio_unitario, 2); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 15px; color: #666;">
                            No se encontraron productos registrados en este pedido.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold; padding: 15px; font-size: 1.1rem;">Total del Pedido:</td>
                    <td style="text-align: right; font-weight: bold; color: #007bff; padding: 15px; font-size: 1.1rem;">
                        $<?php echo number_format($venta->total, 2); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </main>
    
</section>