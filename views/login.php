<main class="login-wrapper">
    <link rel="stylesheet" href="public/css/login-style.css?v=<?php echo time(); ?>">
    <header>
        <h2>Bienvenido</h2>
        <p>Panel Administrativo - La Providencia</p>
    </header>
    
    <form action="index.php?action=validar_login" method="POST">
        <fieldset>
            <label for="correo">Correo Electrónico</label>
           <input type="email" name="email" placeholder="ejemplo@correo.com">
        </fieldset>
        
        <fieldset>
            <label for="password">Contraseña</label>
            <input type="password" name="password" placeholder="Contraseña">
        </fieldset>
        
        <button type="submit" class="btn-ingresar">Ingresar al Sistema</button>
        
        <footer>
            <a href="#" class="enlace-olvido">¿Olvidaste tu contraseña?</a>
        </footer>
    </form>
</main>