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
        <h2>Retiro de Pedidos</h2>
        
        <nav class="acciones-exportar">
            <a href="index.php?action=exportar_pdf&tipo=retiros" target="_blank" class="btn-exportar-pdf">
                <i class="far fa-file-pdf"></i> PDF
            </a>
            <a href="index.php?action=exportar_excel&tipo=retiros" class="btn-exportar-excel">
                <i class="fas fa-file-excel"></i> Excel
            </a>
        </nav>
    </section>
</header>

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
                            <td class="cliente-nombre">
                                <strong><?php echo htmlspecialchars($venta->nombre_cliente); ?></strong>
                            </td> 
                            
                            <td>
                                <span class="fecha-dia">
                                    <?php echo date('d/m/Y', strtotime($venta->fecha)); ?>
                                </span>
                                <span class="fecha-hora">
                                    <?php echo date('h:i A', strtotime($venta->fecha)); ?>
                                </span>
                            </td>
                            
                            <td>
                                <?php 
                                    $metodo_lowercase = strtolower($venta->nombre_metodo ?? '');
                                    $clase_metodo = 'metodo-na';
                                    
                                    if (strpos($metodo_lowercase, 'móvil') !== false || strpos($metodo_lowercase, 'movil') !== false) {
                                        $clase_metodo = 'metodo-pago-movil';
                                    } elseif (strpos($metodo_lowercase, 'transferencia') !== false) {
                                        $clase_metodo = 'metodo-transferencia';
                                    } elseif (strpos($metodo_lowercase, 'efectivo $') !== false || strpos($metodo_lowercase, 'efectivo usd') !== false) {
                                        $clase_metodo = 'metodo-efectivo-usd'; 
                                    } elseif (strpos($metodo_lowercase, 'efectivo') !== false) {
                                        $clase_metodo = 'metodo-efectivo-bs';  
                                    }
                                ?>
                                <span class="metodo-badge <?php echo $clase_metodo; ?>">
                                    <?php echo htmlspecialchars($venta->nombre_metodo ?? 'N/A'); ?>
                                </span>
                            </td> 

                            <td class="monto-usd">$<?php echo number_format($venta->total, 2); ?></td>
                            
                            <td class="monto-bs"><?php echo number_format($venta->total_bs ?? 0, 2); ?> Bs</td>
                            
                            <td>
                                <?php if ($venta->estado == 'pagado'): ?>
                                    <span class="badge badge-listo">Listo para Retiro</span>
                                <?php else: ?>
                                    <span class="badge badge-entregado">Entregado</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($venta->estado == 'pagado'): ?>
                                    <a href="index.php?action=procesar_retiro&id=<?php echo $venta->id; ?>" class="btn-tabla-success">
                                        <i class="fas fa-box-open"></i> Entregar Pedido
                                    </a>
                                <?php else: ?>
                                    <span class="texto-verificado"><i class="fas fa-check-double"></i> Entregado</span>
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
                        <tr>
                            <td colspan="8" class="tabla-vacia">No hay pedidos listos para retiro.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
        </section>
    </main>
</section>