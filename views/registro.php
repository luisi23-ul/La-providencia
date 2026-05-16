<section class="auth-container">
    <link rel="stylesheet" href="public/css/components/forms.css?v=<?php echo time(); ?>">

    <article class="auth-card">
        <header class="auth-header">
            <h2>Crear Cuenta</h2>
            <p>Regístrate para realizar tus compras en La Providencia</p>
        </header>
        
        <form action="index.php?action=registrar_cliente" method="POST">
            <div class="two-columns"> <fieldset class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="nombre" placeholder="Tu nombre" required>
                </fieldset>

<<<<<<< Updated upstream
        <fieldset>
            <label>Teléfono</label>
            <input type="tel" name="telefono" placeholder="0412..." required>
        </fieldset>
        
        <fieldset>
            <label>Correo Electrónico</label>
            <input type="email" name="correo" placeholder="ejemplo@correo.com" required>
        </fieldset>
        
        <fieldset>
            <label>Contraseña</label>
            <input type="password" name="clave" placeholder="Crea una clave" required>
        </fieldset>
        
        <button type="submit" class="btn-ingresar">Registrarme e Iniciar</button>

        <br>
<section style="text-align: center;">
    <p>¿Ya tienes una cuenta?</p>
    <a href="index.php?action=login_usuario" style="color: #8e44ad; font-weight: bold; text-decoration: none; border: 1px solid #8e44ad; padding: 10px; border-radius: 5px;">
        Iniciar Sesión
    </a>
</section>
    </form>
</main>
=======
                <fieldset class="form-group">
                    <label>Apellido</label>
                    <input type="text" name="apellido" placeholder="Tu apellido" required>
                </fieldset>
            </div>

            <fieldset class="form-group">
                <label>Teléfono</label>
                <input type="tel" name="telefono" placeholder="0412" required>
            </fieldset>
            
            <fieldset class="form-group">
                <label>Correo Electrónico</label>
                <input type="email" name="correo" placeholder="ejemplo@correo.com" required>
            </fieldset>
            
            <fieldset class="form-group">
                <label>Contraseña</label>
                <input type="password" name="clave" placeholder="Clave" required>
            </fieldset>
            
            <button type="submit" class="btn-submit">Registrarme e Iniciar</button>
            
            <footer class="auth-footer">
                <a href="index.php?action=login">¿Ya tienes cuenta? Inicia Sesión</a>
            </footer>
        </form>
    </article>
</section>
>>>>>>> Stashed changes
