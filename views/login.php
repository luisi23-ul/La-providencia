<<<<<<< Updated upstream
<main class="login-wrapper">
    <link rel="stylesheet" href="public/css/login-style.css?v=<?php echo time(); ?>">
    
    <header>
        <h2>Bienvenido</h2>
        <p>Panel Administrativo - La Providencia</p>
    </header>
    
    <form action="index.php?action=validar_login" method="POST">
        
        <fieldset>
            <label for="correo">Correo Electrónico</label>
            <input type="email" name="email" id="correo" placeholder="ejemplo@correo.com" required>
        </fieldset>
        
        <fieldset>
            <label for="password">Contraseña</label>
            <input type="password" name="password" id="password" placeholder="Contraseña" required>
        </fieldset>
        
        <button type="button" class="btn-ingresar" onclick="this.closest('form').submit();">
            Ingresar al Sistema
        </button>
        
        <footer>
            <a href="#" class="enlace-olvido">¿Olvidaste tu contraseña?</a>
        </footer>
    </form>
</main>
=======
<section class="auth-container">
    <link rel="stylesheet" href="public/css/components/forms.css?v=<?php echo time(); ?>">

    <article class="auth-card">
        <header class="auth-header">
            <h2>Bienvenido</h2>
            <p>Panel Administrativo</p>
        </header>
        
        <form action="index.php?action=validar_login" method="POST">
            <fieldset class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" name="email" id="email" placeholder="ejemplo@correo.com" required>
            </fieldset>
            
            <fieldset class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" name="password" id="password" placeholder="••••••••" required>
            </fieldset>
            
            <button type="submit" class="btn-submit">Ingresar al Sistema</button>
            
            <footer class="auth-footer">
                <a href="#" class="enlace-olvido">¿Olvidaste tu contraseña?</a>
            </footer>
        </form>
    </article>
</section>
>>>>>>> Stashed changes
