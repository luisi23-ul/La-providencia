
<link class="estilo-carrito" rel="stylesheet" href="public/css/Carrito.css?v=<?php echo time(); ?>">
<?php
// views/carrito.php
?>

<section class="contenedor-carrito">
    <header>
        <h2 class="titulo-carrito">Tu Pedido en La Providencia</h2>
    </header>
    

    <?php if (isset($_SESSION["carrito"]) && !empty($_SESSION["carrito"])): ?>
       
       <div class="tabla-wrapper"> <table class="tabla-carrito">
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
                            <span>$<?php echo number_format($item["precio"], 2); ?></span>
                            <span>Bs. <?php echo number_format($precioBsUnitario, 2, ',', '.'); ?></span>
                        </td>
                        
                        <td class="celda-producto"><?php echo $item["cantidad"]; ?></td>
                        
                        <td class="celda-producto">
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                            <span>Bs. <?php echo number_format($subtotalBs, 2, ',', '.'); ?></span>
                        </td>
                        
                        <td class="celda-producto">
                            <a href="index.php?action=eliminar_item&id=<?php echo $item['id_producto']; ?>" class="boton-quitar">Quitar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>      
<div class="contenedor-inferior">
        <div class="resumen-total">
        <p class="tasa-bcv">
            Tasa Oficial BCV: <strong>Bs. <?php echo number_format($tasaCambio, 2, ',', '.'); ?> / USD</strong>
        </p>

            <p>
                Total en Dólares: <span>$<?php echo number_format($totalFinal, 2); ?></span>
            </p>
            
            <p>
                Total en Bs: <span>Bs. <?php echo number_format($totalBolivares, 2, ',', '.'); ?></span>
            </p>
        </div>

    <div class="acciones-carrito">
    

    <div class="caja-derecha">
        <form action="index.php?action=finalizar_compra" method="POST" class="formulario-pago">
            <label for="metodo_pago" class="label-pago">Método de pago:</label>
            <div class="selector-container">
                <select name="metodo_pago" id="metodo_pago" required>
                    <option value="">-- Seleccione una opción --</option>
                    <?php foreach ($metodos as $metodo): ?>
                        <option value="<?php echo $metodo->id; ?>"><?php echo htmlspecialchars($metodo->nombre); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="boton-finalizar">
                Finalizar Compra por WhatsApp
            </button>
        </form>
    </div>
                    </div>
</div>
    <?php else: ?>
        <aside class="carrito-vacio">
            <p class="texto-vacio">El carrito está vacío actualmente.</p>
            <p class="p-ir-catalogo">
                <a href="index.php?action=ver_catalogo" class="boton-catalogo">Ir al Catálogo</a>
            </p>
        </aside>
    <?php endif; ?>
</section>