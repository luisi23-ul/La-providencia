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
                        <span class="icon-carito">🛒</span>
                    </section>
                </figcaption>
            </figure>
        <?php endforeach; ?>
    </article>
</section>