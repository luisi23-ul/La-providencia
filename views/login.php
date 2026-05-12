<section class="login-main">
    <article class="login-brand-side">
        <header>
            <h1>Controla, <br> gestiona y <br> avanza.</h1>
            <p>Plataforma de gestión de inventario profesional para tu hogar y negocio.</p>
        </header>
        <figure>
            <img src="public/img/logo_3d.png" alt="La Providencia Logo">
        </figure>
    </article>

    <article class="login-form-side">
        <section class="form-wrapper">
            <h2>Inicio de Sesión</h2>
            
            <form id="formAcceso" action="index.php?action=validar_login" method="POST">
                <fieldset>
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" placeholder="nombre@correo.com" required>
                </fieldset>
                
                <fieldset>
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </fieldset>

                <nav class="form-help">
                    <label><input type="checkbox"> Recordarme</label>
                    <a href="#">¿Olvidaste tu contraseña?</a>
                </nav>

                <footer>
                    <span class="btn-ingresar" onclick="document.getElementById('formAcceso').submit();">
                        ENTRAR AL SISTEMA
                    </span>
                </footer>
            </form>
        </section>
    </article>
</section>