<<<<<<< HEAD
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
=======
<?php
// views/login.php
// Login exclusivo para el Administrador
?>
<section style="max-width: 450px; margin: 60px auto; border: 2px solid #0052d4; padding: 40px; background: #f0f7ff; border-radius: 10px; font-family: Arial, sans-serif; box-shadow: 0px 4px 15px rgba(0,0,0,0.1);">
    <header style="text-align: center; margin-bottom: 30px;">
        <h2 style="color: #0052d4; text-transform: uppercase; margin: 0; font-size: 1.8em; letter-spacing: 1px;">Panel Administrativo</h2>
        <p style="color: #555; margin-top: 5px;">Inicia sesión para gestionar el inventario</p>
    </header>

    <form action="index.php?action=validar_login" method="POST">
        
        <p style="margin-bottom: 20px;">
            <label style="color: #003a94; font-weight: bold; display: block; margin-bottom: 8px;">Correo Electrónico:</label>
           <input type="email" name="email" placeholder="ejemplo@correo.com" style="width: 100%; padding: 12px; border: 1px solid #0052d4; border-radius: 6px; box-sizing: border-box;" required>
        </p>

        <p style="margin-bottom: 25px;">
            <label style="color: #003a94; font-weight: bold; display: block; margin-bottom: 8px;">Contraseña:</label>
            <input type="password" name="password" placeholder="••••••••" style="width: 100%; padding: 12px; border: 1px solid #0052d4; border-radius: 6px; box-sizing: border-box;" required>
        </p>

        <p style="text-align: center; margin-top: 30px;">
            <button type="submit" style="background: #0052d4; color: white; padding: 15px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; font-size: 1.1em; text-transform: uppercase; transition: background 0.3s;">
                Ingresar al Panel
            </button>
        </p>

        <footer style="text-align: center; margin-top: 25px; border-top: 1px solid #bce0ff; padding-top: 15px;">
            <a href="index.php?action=inicio" style="color: #0052d4; text-decoration: none; font-weight: bold; font-size: 0.95em;">← Volver a la Tienda</a>
        </footer>
    </form>
>>>>>>> 9255eb7799ecb1cb534a423941fe995b971f2f43
</section>