<section class="auth-page-container">
    <link rel="stylesheet" href="public/css/components/forms.css?v=<?php echo time(); ?>">

    <header class="auth-banner">
        <h1>BIENVENIDO</h1>
    </header>
    
    <main class="auth-main-content">
        
        <article class="auth-card">
            
           <form action="index.php?action=valider_login_registro" method="POST">
    
    <fieldset class="form-group">
        <label for="correo_ingreso">Correo Electrónico:</label>
        <span class="input-icon-wrapper">
            <i class="fas fa-envelope input-icon"></i>
            <input type="email" name="correo_ingreso" id="correo_ingreso" placeholder="ejemplo@gmail.com" required>
        </span>
    </fieldset>

    <fieldset class="form-group">
        <label for="clave_ingreso">Contraseña:</label>
        <span class="input-icon-wrapper">
            <i class="fas fa-lock input-icon"></i>
            <input type="password" name="clave_ingreso" id="clave_ingreso" placeholder="Tu clave" required>
        </span>
    </fieldset>
    
    <button type="submit" class="btn-submit">Iniciar Sesión</button>

    <footer class="auth-footer">
        <p>¿No tienes cuenta?</p>
        <a href="index.php?action=registro" class="enlace-registro">Regístrate aquí</a>
    </footer>
</form>
            

        </article>

    </main>
</section>