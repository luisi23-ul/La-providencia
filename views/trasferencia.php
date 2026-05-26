<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="public/css/pagos.css?v=<?php echo time(); ?>">

<section class="panel-administracion">

    <?php include 'sidebar.php'; ?>

    <main class="main-dashboard-content">

        <section class="contenedor-reporte">
            <h2>Reporte: Transferencia</h2>
            
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

    </main>
</section>