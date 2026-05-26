<section class="detalle-venta-container">
    <header class="detalle-header">
        <h2>Detalle de la Venta #<?php echo $venta->id; ?></h2>
        <a href="javascript:history.back()" class="btn-volver">← Volver</a>
    </header>

    <aside class="info-cliente-banner">
        <article class="info-item">
            <span class="label">Cliente</span>
            <span class="valor"><?php echo htmlspecialchars($venta->nombre); ?></span>
        </article>
        <article class="info-item">
            <span class="label">Fecha</span>
            <span class="valor"><?php echo htmlspecialchars($venta->fecha); ?></span>
        </article>
        <article class="info-item">
            <span class="label">Estado</span>
            <span class="badge-estado <?php echo ($venta->estado == 'pendiente') ? 'pendiente' : 'listo'; ?>">
                <?php echo htmlspecialchars($venta->estado); ?>
            </span>
        </article>
    </aside>

    <main class="tabla-detalle-wrapper">
        <table class="tabla-detalle">
            </table>
        
        <footer class="total-box">
            <span>Total del Pedido</span>
            <span class="total-valor">$<?php echo number_format($venta->total, 2); ?></span>
        </footer>
    </main>
</section>