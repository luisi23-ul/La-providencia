<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">

<section class="panel-administracion">

    <?php include 'sidebar.php'; ?>

    <main class="main-dashboard-content">

        <a href="index.php?action=metodos_pago" class="btn-regresar-panel">
            <i class="fas fa-arrow-left"></i> Volver
        </a>

        <section class="contenedor-reporte">
            
            <header class="pagos-header">
                <div class="header-bottom">
                    <h2>Reporte: <?php echo htmlspecialchars($nombreMetodo ?? 'Efectivo $'); ?></h2>

                    <nav class="acciones-exportar">
                        <a href="index.php?action=exportar_excel&tipo=efectivo$" class="btn-exportar-excel">
                            <i class="fas fa-file-excel"></i> Excel
                        </a>
                        <a href="index.php?action=exportar_pdf&tipo=efectivo$" class="btn-exportar-pdf">
                            <i class="far fa-file-pdf"></i> PDF
                        </a>
                    </nav>
                </div>
            </header>
            
            <article class="tarjeta-total">
                <p>Total Recaudado en Dólares: <strong>$<?php echo number_format($totalUSD ?? 0, 2); ?></strong></p>
                <p>Total Recaudado en Bolívares: <strong><?php echo number_format($totalBS ?? 0, 2); ?> Bs</strong></p>
            </article>

            <table class="tabla-modulo-admin">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Fecha / Hora</th>
                        <th>Total ($)</th>
                        <th>Total (Bs)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($ventas)): ?>
                        <?php foreach ($ventas as $v): ?>
                        <tr>
                            <td class="cliente-nombre"><strong><?php echo htmlspecialchars($v->nombre_cliente); ?></strong></td>
                            <td>
                                <span class="fecha-dia"><?php echo date('d/m/Y', strtotime($v->fecha)); ?></span>
                                <span class="fecha-hora"><?php echo date('h:i A', strtotime($v->fecha)); ?></span>
                            </td>
                            <td class="monto-usd">$<?php echo number_format($v->total, 2); ?></td>
                            <td class="monto-bs"><?php echo number_format($v->total_bs ?? 0, 2); ?> Bs</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="tabla-vacia">No hay ventas registradas con este método.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

    </main>
</section>