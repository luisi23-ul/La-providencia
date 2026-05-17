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
                ?>
                    <tr class="fila-producto">
                        <td class="celda-producto"><?php echo htmlspecialchars($item["nombre"]); ?></td>
                        <td class="celda-producto">$<?php echo number_format($item["precio"], 2); ?></td>
                        <td class="celda-producto"><?php echo $item["cantidad"]; ?></td>
                        <td class="celda-producto">$<?php echo number_format($subtotal, 2); ?></td>
                        <td class="celda-producto">
                            <a href="index.php?action=eliminar_item&id=<?php echo $item['id_producto']; ?>" class="boton-quitar">Quitar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="total-pedido">
            Total Final: $<?php echo number_format($totalFinal, 2); ?>
        </p>

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