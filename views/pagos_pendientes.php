
<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">

<section class="panel-administracion">
    <?php include 'sidebar.php'; ?>

    <main class="main-dashboard-content">

        <nav class="navegacion-superior">
            <a href="index.php?action=dashboard" class="btn-regresar-panel">
                <i class="fas fa-arrow-left"></i>Volver
            </a>
        </nav>

        <section class="panel-modulo-contenedor">
            
            <header class="pagos-header">
                
            <section class="header-bottom">
                <h2>Pagos Pendientes</h2>
                
                <nav class="acciones-exportar">
                    <a href="index.php?action=exportar_pdf&tipo=pendientes" target="_blank" class="btn-exportar-pdf">
                        <i class="far fa-file-pdf"></i> PDF
                    </a>
                    <a href="index.php?action=exportar_excel&tipo=pendientes" class="btn-exportar-excel">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                </nav>
            </section>
            </header>

            <div class="contenedor-pagos-especifico">
            <table class="tabla-modulo-admin">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Fecha / Hora</th>
                        <th>Método</th>
                        <th>Total ($)</th>
                        <th>Total (Bs)</th>
                        <th>Estado</th>
                        <th>Gestión</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($ventas)): ?>
                        <?php foreach ($ventas as $venta): ?>
                        <tr>
                            <td class="cliente-nombre"><strong><?php echo htmlspecialchars($venta->nombre_cliente); ?></strong></td>
                            <td>
                                <span class="fecha-dia"><?php echo date('d/m/Y', strtotime($venta->fecha)); ?></span>
                                <span class="fecha-hora"><?php echo date('h:i A', strtotime($venta->fecha)); ?></span>
                            </td>
                            <td>
                                <span class="metodo-badge"><?php echo htmlspecialchars($venta->nombre_metodo ?? 'N/A'); ?></span>
                            </td>
                            <td class="monto-usd">$<?php echo number_format($venta->total, 2); ?></td>
                            <td class="monto-bs"><?php echo number_format($venta->total_bs ?? 0, 2); ?> Bs</td>
                            <td>
                                <?php if ($venta->estado == 'pendiente'): ?>
                                    <span class="badge badge-pendiente">Pendiente</span>
                                <?php else: ?>
                                    <span class="badge badge-pagado">Pagado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($venta->estado == 'pendiente'): ?>
                                    <a href="index.php?action=confirmar_pago&id=<?php echo $venta->id; ?>" class="btn-tabla-success">
                                        <i class="fas fa-check"></i> Confirmar
                                    </a>
                                <?php else: ?>
                                    <span class="texto-verificado"><i class="fas fa-check-double"></i> Pago Verificado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="index.php?action=detalle_venta&id=<?php echo $venta->id; ?>" class="btn-tabla-info">
                                    <i class="fas fa-eye"></i> Ver Detalle
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="tabla-vacia">No hay pagos pendientes.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
                    </div>
        </section>
    </main>
</section>