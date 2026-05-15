<section style="text-align: center; color: #8e44ad;">
    <h1>Ingreso al Sistema</h1>
    <p>Comercial La Providencia</p>
    <link rel="stylesheet" href="public/css/login_registro.css?v=<?php echo time(); ?>">

   <form method="post" action="index.php?action=valider_login_registro">
        <p>
            <label>Correo Electrónico:</label><br>
            <input type="email" name="correo_ingreso" required placeholder="ejemplo@gmail.com">
        </p>

        <p>
            <label>Contraseña:</label><br>
            <input type="password" name="clave_ingreso" required placeholder="Tu clave">
        </p>

        <p>
            <input type="submit" value="Entrar al Catálogo">
        </p>
    </form>

    <p>
        ¿No tienes cuenta? 
        <a href="index.php?action=registro">Regístrate aquí</a>
    </p>
</section>