<section class="auth-wrapper auth-page-container">
    <link rel="stylesheet" href="public/css/components/forms.css?v=<?php echo time(); ?>">

    <aside class="auth-side-image"></aside>

    <header class="auth-header auth-banner">
        <h2>Bienvenido</h2>
        <p>Panel Administrativo</p>
    </header>
    
    <main class="auth-main-content">
        
        <article class="auth-side-form auth-card">
            
            <form method="post" action="index.php?action=valider_login_registro">
                
                <fieldset class="form-group">
                    <label for="correo_ingreso">Correo Electrónico:</label>
                    <input type="email" name="correo_ingreso" id="correo_ingreso" required placeholder="ejemplo@gmail.com">
                </fieldset>

                <fieldset class="form-group">
                    <label for="clave_ingreso">Contraseña:</label>
                    <input type="password" name="clave_ingreso" id="clave_ingreso" required placeholder="Tu clave">
                </fieldset>

                <button type="submit" class="btn-submit">Ingresar al Sistema</button>
                
                <footer class="auth-footer">
                    <a href="#" class="enlace-olvido">¿Olvidaste tu contraseña?</a>
                    <p>¿No tienes cuenta en La Providencia?</p>
                    <a href="index.php?action=registro" class="enlace-registro">Regístrate aquí</a>
                </footer>
            </form>

        </article>

    </main>
</section>