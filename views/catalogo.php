<link rel="stylesheet" href="public/css/catalogo.css?v=<?php echo time(); ?>">

<header class="hero-catalogo">
    <nav class="hero-top-bar">
        <span><i class="fas fa-truck"></i> Envío rápido</span>
        <span><i class="fas fa-shield-alt"></i> Calidad Providencia</span>
        <span><i class="fas fa-credit-card"></i> Pagos seguros</span>
    </nav>

    <section class="hero-content">
        <div class="hero-text">
            <h1>Nuestros Productos</h1>
            <p>La excelencia en soluciones para el hogar, ahora a un clic de distancia.</p>
        </div>

        <form id="form-busqueda" action="index.php" method="GET" class="hero-search-bar">
            <input type="hidden" name="action" value="ver_catalogo">
            
            <input type="text" name="busqueda" id="input-busqueda" 
                   placeholder="¿Qué estás buscando para tu hogar?" 
                   value="<?php echo htmlspecialchars($_GET['busqueda'] ?? ''); ?>">

            <select name="id_categoria" onchange="this.form.submit()">
                <option value="">Todas las categorías</option>
                <?php if (!empty($categorias)): foreach ($categorias as $cat): ?>
                    <option value="<?php echo $cat->id; ?>" 
                        <?php echo (isset($_GET['id_categoria']) && $_GET['id_categoria'] == $cat->id) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat->nombre_categoria); ?>
                    </option>
                <?php endforeach; endif; ?>
            </select>
        </form>
    </section>
</header>

<main class="contenedor-catalogo">
    <article class="grid-productos">
        <?php if (!empty($listaProductos)): ?>
            <?php foreach ($listaProductos as $p): ?>
                <figure class="card-producto producto-item">
                    <picture class="imagen-container">
                        <?php 
                        $ruta_archivo = "public/uploads/" . $p->imagen;
                        // Ajuste de ruta según tu estructura física
                        if (!empty($p->imagen) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/La-providencia/' . $ruta_archivo)): ?>
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
                                <input type="number" name="cantidad" value="1" min="1" max="<?php echo $p->stock; ?>" class="input-cantidad">
                                <button type="submit" class="btn-agregar">
                                    <i class="fas fa-shopping-cart"></i> <span>Agregar</span>
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="index.php?action=login_usuario" class="btn-login-comprar">
                                <i class="fas fa-sign-in-alt"></i> Iniciar sesión para comprar
                            </a>
                        <?php endif; ?>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; width: 100%; margin-top: 20px;">No se encontraron productos.</p>
        <?php endif; ?>
    </article>
</main>