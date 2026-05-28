<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">

<section class="panel-administracion">

    <?php include 'sidebar.php'; ?>

    <main class="main-dashboard-content">

        <nav class="navegacion-superior">
            <a href="index.php?action=retiro_pedidos" class="btn-regresar-panel">
                <i class="fas fa-arrow-left"></i>Volver
            </a>
        </nav>

        <section class="panel-modulo-contenedor">
            
            <header class="inv-header">
                <h2 class="inv-title">Detalle de la Venta #<?php echo $venta->id; ?></h2>
            </header>

            <aside class="info-cliente-banner">
                <article class="info-item">
                    <span class="label">Cliente</span>
                    <span class="valor"><?php echo htmlspecialchars($venta->nombre); ?></span>
                </article>
                <article class="info-item">
                    <span class="label">Fecha y Hora</span>
                    <span class="fecha-dia"><?php echo date('d/m/Y', strtotime($venta->fecha)); ?></span>
                    <span class="fecha-hora"><?php echo date('h:i A', strtotime($venta->fecha)); ?></span>
                </article>
                <article class="info-item">
                    <span class="label">Estado</span>
                    <span class="badge-estado <?php echo ($venta->estado == 'pendiente') ? 'pendiente' : 'listo'; ?>">
                        <?php echo htmlspecialchars($venta->estado); ?>
                    </span>
                </article>
            </aside>

            <table class="tabla-modulo-admin">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unit.</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($productos)): ?>
                        <?php foreach ($productos as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item->nombre); ?></td>
                            <td><?php echo $item->cantidad; ?></td>
                            <td>$<?php echo number_format($item->precio, 2); ?></td>
                            <td>$<?php echo number_format($item->subtotal, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #64748b;">No hay productos registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <footer class="total-box-premium">
                <span class="total-label">Total del Pedido</span>
                <span class="total-valor-premium">$<?php echo number_format($venta->total, 2); ?></span>
            </footer>

        </section>
    </main>
</section>