<section class="contenedor-carrito" style="max-width: 800px; margin: 40px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
    <h2 style="text-align: center; color: #5c2c16; margin-bottom: 20px;">Tu Pedido en La Providencia</h2>
     <link rel="stylesheet" href="public/css/Carrito.css?v=<?php echo time(); ?>">

    <?php if (isset($_SESSION["carrito"]) && !empty($_SESSION["carrito"])): ?>
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background-color: #f2f2f2; border-bottom: 2px solid #ddd;">
                    <th style="padding: 12px;">Producto</th>
                    <th style="padding: 12px;">Precio</th>
                    <th style="padding: 12px;">Cantidad</th>
                    <th style="padding: 12px;">Subtotal</th>
                    <th style="padding: 12px;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalFinal = 0;
                foreach ($_SESSION["carrito"] as $item): 
                    $subtotal = $item["precio"] * $item["cantidad"];
                    $totalFinal += $subtotal;
                ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px;"><?php echo htmlspecialchars($item["nombre"]); ?></td>
                        <td style="padding: 12px;">$<?php echo number_format($item["precio"], 2); ?></td>
                        <td style="padding: 12px;"><?php echo $item["cantidad"]; ?></td>
                        <td style="padding: 12px;">$<?php echo number_format($subtotal, 2); ?></td>
                        <td style="padding: 12px;">
                            <a href="index.php?action=eliminar_item&id=<?php echo $item['id_producto']; ?>" style="background: #ff4d4d; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 14px;">Quitar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="text-align: right; margin-top: 20px; font-size: 18px; font-weight: bold;">
            Total Final: $<?php echo number_format($totalFinal, 2); ?>
        </div>

        <div style="margin-top: 30px; display: flex; justify-content: space-between; align-items: center;">
            <a href="index.php?action=ver_catalogo" style="color: #5c2c16; text-decoration: none; font-weight: bold;">← Seguir comprando</a>
            <a href="index.php?action=finalizar_compra" style="background: #27ae60; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold;">Finalizar Compra</a>
        </div>

    <?php else: ?>
        <div style="text-align: center; padding: 40px;">
            <p style="font-size: 18px; color: #666;">El carrito está vacío actualmente.</p>
            <a href="index.php?action=ver_catalogo" style="background: #5c2c16; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin-top: 15px;">Ir al Catálogo</a>
        </div>
    <?php endif; ?>
</section>