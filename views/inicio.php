<section class="pantalla-principal">
    <nav class="top-nav">
        <h1>La <span>Providencia</span></h1>
        <ul>
            <li><a href="index.php?action=registro" style="text-decoration: none; color: #0052d4; font-weight: bold;">
    ACCEDER
</a></li>
            <li><a href="index.php?action=ver_catalogo" class="btn-catalogo">Catálogo</a></li>
            <li><a href="index.php?action=salir">Cerrar Sesión</a></li>
        </ul>
    </nav>

    <main class="hero-section">
        <article class="hero-info">
            <h2>Tecnología y Estilo <mark>para tu Hogar</mark></h2>
            <p>Descubre la nueva forma de gestionar tus productos con nuestra plataforma avanzada.</p>
           <a href="index.php?action=login" class="btn-glow">Empezar Ahora</a>
        </article>

        <figure class="hero-image">
           <img src="public/img/logo.png" alt="Logo La Providencia" style="width: 150px; height: auto;">
        </figure>

        <section class="productos-container">
    <?php if (isset($productos) && count($productos) > 0): ?>
        <?php foreach ($productos as $p): ?>
            <div class="card-producto">
                <img src="/La-providencia/public/img/<?php echo $p['imagen']; ?>" alt="Producto">
                <h3><?php echo $p['nombre_producto']; ?></h3>
                <p><?php echo $p['descripcion']; ?></p>
                <span class="precio">$<?php echo $p['precio']; ?></span>
                <p>Disponibles: <?php echo $p['stock']; ?> unidades</p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No hay productos disponibles por ahora.</p>
    <?php endif; ?>
</section>
    </main>
</section>
