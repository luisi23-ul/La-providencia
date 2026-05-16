<section class="contenedor-catalogo">
    <header class="encabezado-catalogo">
        <link rel="stylesheet" href="public/css/catalogo.css?v=<?php echo time(); ?>">
        <h2>Nuestros Productos</h2>
        <p>Soluciones de alto nivel diseñadas para tu hogar.</p>
    </header>

    <article class="grid-productos">
        <?php foreach ($listaProductos as $p): ?>
            <figure class="card-producto">
                <picture class="imagen-container">
                    <img src="public/uploads/<?php echo $p->imagen; ?>" alt="<?php echo $p->nombre_producto; ?>">
                </picture>
                
                <figcaption>
                    <h3><?php echo $p->nombre_producto; ?></h3>
                    <p class="calidad">Calidad garantizada Providencia</p>
                    
                    <p class="stock">Disponibles: <strong><?php echo $p->stock; ?> unidades</strong></p>
                    
                    <section class="info-compra">
                        <span class="precio">$<?php echo number_format($p->precio, 2); ?></span>
                        
                        <?php if (isset($_SESSION["id_usuario"])): ?>
                            <form action="index.php" method="GET" style="margin-top: 10px; display: inline-block;">
                                <input type="hidden" name="action" value="agregar_carrito">
                                <input type="hidden" name="id" value="<?php echo $p->id; ?>">
                                <input type="hidden" name="nombre" value="<?php echo $p->nombre_producto; ?>">
                                <input type="hidden" name="precio" value="<?php echo $p->precio; ?>">
                                
                                <input type="number" name="cantidad" value="1" min="1" max="<?php echo $p->stock; ?>" 
                                       style="width: 45px; height: 35px; text-align: center; border: 1px solid #ccc; border-radius: 4px; vertical-align: middle;">
                                
                                <button type="submit" class="btn-agregar" style="vertical-align: middle; cursor: pointer; border: none;">
                                    <span class="icon-carito">🛒</span> Agregar
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="index.php?action=login_usuario" class="btn-login-comprar">
                                Inicia sesión para comprar
                            </a>
                        <?php endif; ?>
                    </section>
                </figcaption>
            </figure>
        <?php endforeach; ?>
    </article>
</section>