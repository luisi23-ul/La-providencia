<link rel="stylesheet" href="public/css/catalogo.css?v=<?php echo time(); ?>">

<section class="contenedor-catalogo">
    <header class="encabezado-catalogo">
        <h2>Nuestros Productos</h2>
        <p>Soluciones de alto nivel diseñadas para tu hogar.</p>
    </header>

    <article class="grid-productos">
        <?php foreach ($listaProductos as $p): ?>
            <figure class="card-producto">
                <picture class="imagen-container">
    <?php 
    // Ahora $p->imagen siempre existirá gracias al IFNULL del modelo
    $ruta_archivo = "public/uploads/" . $p->imagen;
    
    // Verificamos que no esté vacío y que exista físicamente
    if (!empty($p->imagen) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/la-providencia/' . $ruta_archivo)): ?>
        <img src="<?php echo htmlspecialchars($ruta_archivo); ?>" 
             alt="<?php echo htmlspecialchars($p->nombre_producto, ENT_QUOTES, 'UTF-8'); ?>">
    <?php else: ?>
        <img src="public/img/uploads.png" alt="Imagen no disponible">
    <?php endif; ?>
</picture>
                
                <figcaption class="cuerpo-producto">
                    <h3><?php echo htmlspecialchars($p->nombre_producto, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="calidad">Calidad garantizada Providencia</p>
                    
                    <p class="stock">
                        <span>Disponibilidad:</span>
                        <strong><?php echo $p->stock; ?> unidades</strong>
                    </p>
                    
                    <span class="precio">$<?php echo number_format($p->precio, 2); ?></span>
                    
                    <?php if (isset($_SESSION["id_usuario"])): ?>
                        <form action="index.php" method="GET" class="formulario-pedido">
                            <input type="hidden" name="action" value="agregar_carrito">
                            <input type="hidden" name="id" value="<?php echo $p->id; ?>">
                            <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($p->nombre_producto, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="precio" value="<?php echo $p->precio; ?>">
                            <input type="number" name="cantidad" value="1" min="1" max="<?php echo $p->stock; ?>" class="input-cantidad" aria-label="Cantidad">
                            <button type="submit" class="btn-agregar" aria-label="Agregar al carrito">
                                <i class="fas fa-shopping-cart"></i>
                                <span>Agregar</span>
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="index.php?action=login_usuario" class="btn-login-comprar">
                            <i class="fas fa-sign-in-alt"></i> Inicio de sesión para comprar
                        </a>
                    <?php endif; ?>
                </figcaption>
            </figure>
        <?php endforeach; ?>
    </article>
</section>