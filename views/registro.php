<section class="auth-page-container">
    <link rel="stylesheet" href="public/css/components/forms.css?v=<?php echo time(); ?>">
     <link rel="stylesheet" href="public/css/components/header.css?v=<?php echo time(); ?>">

    <header class="auth-banner">
        <h2>Crea tu cuenta en La Providencia</h2>
        <p>Regístrate para realizar tus compras de forma rápida y segura</p>
    </header>

    <main class="auth-main-content">
        <article class="auth-card">
            <form action="index.php?action=registrar_cliente" method="POST">
                
                <fieldset class="form-group">
                    <label for="nombre">Nombre</label>
                    <span class="input-icon-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="nombre" id="nombre" placeholder="Tu nombre completo" required>
                    </span>
                </fieldset>

                <fieldset class="form-group">
                    <label for="telefono">Teléfono</label>
                    <span class="input-icon-wrapper">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="tel" name="telefono" id="telefono" placeholder="0412..." required>
                    </span>
                </fieldset>
                
                <fieldset class="form-group">
                    <label for="correo">Correo Electrónico</label>
                    <span class="input-icon-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="correo" id="correo" placeholder="ejemplo@correo.com" required>
                    </span>
                </fieldset>
                
                <fieldset class="form-group">
                    <label for="clave">Contraseña</label>
                    <span class="input-icon-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="clave" id="clave" placeholder="Crea una clave segura" required>
                    </span>
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


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="/La-providencia/public/js/scripts.js"></script>s