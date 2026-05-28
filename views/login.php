<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<section class="admin-split-page">
    <link rel="stylesheet" href="public/css/admin-login.css?v=<?php echo time(); ?>">

    <main class="split-left-form">
        <article class="auth-form-wrapper">
            
            <header class="admin-card-header">
                <h2>Panel de Control</h2>
                <p>Ingresa al sistema administrativo</p>
            </header>

            <form action="index.php?action=validar_login" method="POST">
                
                <fieldset class="form-group">
                    <label for="admin_email">Correo Electrónico</label>
                    <span class="input-icon-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" id="admin_email" required placeholder="ejemplo@correo.com">
                    </span>
                </fieldset>

                <fieldset class="form-group">
                    <label for="admin_password">Contraseña</label>
                    <span class="input-icon-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" id="admin_password" required placeholder="••••••••">
                    </span>
                </fieldset>

                <button type="submit" class="admin-btn-vibrante">Iniciar Sesión</button>
                
                <footer class="admin-card-footer">
                    <a href="index.php?action=inicio" class="btn-back-store">
                        <i class="fas fa-arrow-left"></i> Volver a la Tienda
                    </a>
                </footer>
            </form>

        </article>
    </main>

    <aside class="split-right-art">
        <figure class="satin-sphere sphere-one"></figure>
        <figure class="satin-sphere sphere-two"></figure>
        <figure class="satin-sphere sphere-three"></figure>
        <figure class="satin-sphere sphere-four"></figure>
        
        <header class="branding-content">
            <span class="branding-icon-box">
                <i class="fas fa-shield-alt"></i>
            </span>
            <h1>La Providencia</h1>
            <p>Gestión & Control Digital</p>
        </header>
    </aside>
</section>