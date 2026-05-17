<section class="auth-page-container">
    <link rel="stylesheet" href="public/css/components/forms.css?v=<?php echo time(); ?>">

    <header class="auth-banner">
        <h2>Crea tu cuenta en La Providencia</h2>
        <p>Regístrate para realizar tus compras de forma rápida y segura</p>
    </header>

    <main class="auth-main-content">
        <article class="auth-card">
            <form action="index.php?action=registrar_cliente" method="POST">
                
                <fieldset class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" name="nombre" id="nombre" placeholder="Tu nombre completo" required>
                </fieldset>

                <fieldset class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input type="tel" name="telefono" id="telefono" placeholder="0412..." required>
                </fieldset>
                
                <fieldset class="form-group">
                    <label for="correo">Correo Electrónico</label>
                    <input type="email" name="correo" id="correo" placeholder="ejemplo@correo.com" required>
                </fieldset>
                
                <fieldset class="form-group">
                    <label for="clave">Contraseña</label>
                    <input type="password" name="clave" id="clave" placeholder="Crea una clave segura" required>
                </fieldset>
                
                <button type="submit" class="btn-submit">Registrarme e Iniciar</button>

                <footer class="auth-footer">
                    <p>¿Ya tienes una cuenta?</p>
                    <a href="index.php?action=login_usuario" class="enlace-login">Iniciar Sesión</a>
                </footer>
            </form>
        </article>
    </main>
</section>