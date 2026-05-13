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
    </form>
</main>