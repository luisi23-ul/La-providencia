<?php
// views/carrito.php
?>
<section class="contenedor-carrito">
    <header>
        <h2 class="titulo-carrito">Tu Pedido en La Providencia</h2>
    </header>
    <link class="estilo-carrito" rel="stylesheet" href="public/css/Carrito.css?v=<?php echo time(); ?>">

    <?php if (isset($_SESSION["carrito"]) && !empty($_SESSION["carrito"])): ?>
        <table class="tabla-carrito">
            <thead>
                <tr class="fila-cabecera">
                    <th class="celda-cabecera">Producto</th>
                    <th class="celda-cabecera">Precio</th>
                    <th class="celda-cabecera">Cantidad</th>
                    <th class="celda-cabecera">Subtotal</th>
                    <th class="celda-cabecera">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalFinal = 0;
                foreach ($_SESSION["carrito"] as $item): 
                    $subtotal = $item["precio"] * $item["cantidad"];
                    $totalFinal += $subtotal;
                    
                    // CALCULOS EN BOLÍVARES POR PRODUCTO (Usando la tasa del controlador)
                    $precioBsUnitario = $item["precio"] * $tasaCambio;
                    $subtotalBs = $subtotal * $tasaCambio;
                ?>
                    <tr class="fila-producto">
                        <td class="celda-producto">
                            <?php echo htmlspecialchars($item["nombre"]); ?>
                        </td>
                        
                        <td class="celda-producto">
                            <span style="display: block; font-weight: 600;">$<?php echo number_format($item["precio"], 2); ?></span>
                            <span style="display: block; font-size: 0.82rem; color: #64748b;">Bs. <?php echo number_format($precioBsUnitario, 2, ',', '.'); ?></span>
                        </td>
                        
                        <td class="celda-producto"><?php echo $item["cantidad"]; ?></td>
                        
                        <td class="celda-producto">
                            <span style="display: block; font-weight: 600;">$<?php echo number_format($subtotal, 2); ?></span>
                            <span style="display: block; font-size: 0.82rem; color: #0284c7; font-weight: 600;">Bs. <?php echo number_format($subtotalBs, 2, ',', '.'); ?></span>
                        </td>
                        
                        <td class="celda-producto">
                            <a href="index.php?action=eliminar_item&id=<?php echo $item['id_producto']; ?>" class="boton-quitar">Quitar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="tasa-bcv" style="text-align: right; font-size: 0.9rem; color: #64748b; margin-top: 20px; font-family: sans-serif; margin-bottom: 5px;">
            Tasa Oficial BCV: <strong>Bs. <?php echo number_format($tasaCambio, 2, ',', '.'); ?> / USD</strong>
        </p>

        <div style="text-align: right; margin-top: 10px; margin-bottom: 25px; font-family: sans-serif;">
            <p style="font-size: 1.2rem; color: #1e293b; font-weight: 700; margin: 0; padding: 5px 0;">
                Total en Dólares: <span style="color: #1e293b; font-size: 1.35rem;">$<?php echo number_format($totalFinal, 2); ?></span>
            </p>
            
            <p style="font-size: 1.4rem; color: #0284c7; font-weight: 800; margin: 0; padding: 5px 0;">
                Total en Bs: <span>Bs. <?php echo number_format($totalBolivares, 2, ',', '.'); ?></span>
            </p>
        </div>

        <footer class="acciones-carrito">
            <p class="p-volver">
                <a href="index.php?action=ver_catalogo" class="enlace-seguir">← Seguir comprando</a>
            </p>
            <p class="p-finalizar">
                <a href="index.php?action=finalizar_compra" class="boton-finalizar">
                    🟢 Finalizar Compra por WhatsApp
                </a>
            </p>
        </footer>

    <?php else: ?>
        <aside class="carrito-vacio">
            <p class="texto-vacio">El carrito está vacío actualmente.</p>
            <p class="p-ir-catalogo">
                <a href="index.php?action=ver_catalogo" class="boton-catalogo">Ir al Catálogo</a>
            </p>
        </aside>
    <?php endif; ?>
</section>