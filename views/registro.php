<main class="login-wrapper">
    <header>
         <link rel="stylesheet" href="public/css/registro-style.css?v=<?php echo time(); ?>">
        <h2>Crear Cuenta</h2>
        <p>Regístrate para realizar tus compras en La Providencia</p>
    </header>
    
    <form action="index.php?action=registrar_cliente" method="POST">
        <fieldset>
            <label>Nombre</label>
            <input type="text" name="nombre" placeholder="Tu nombre" required>
        </fieldset>

        <fieldset>
            <label>Apellido</label>
            <input type="text" name="apellido" placeholder="Tu apellido" required>
        </fieldset>

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