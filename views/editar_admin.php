<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<?php
// Validar que administrador existe y no es nulo
if (!$a) {
    die("Administrador no encontrado.");
}

// Convertimos los permisos guardados en la BD (string) a un array
$permisos_actuales = explode(',', $a['permisos'] ?? '');
?>



<section class="panel-administracion">
    <?php include 'sidebar-master.php'; ?>

    <main class="main-view-container">
        
        <header class="master-action-header">
            <a href="index.php?action=gestionar_admins" class="btn-regresar-master">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </header>

        <article class="card-master-form">
            <header class="master-form-header">
                <h2><i class="fas fa-user-edit"></i> Editar:   <?php echo htmlspecialchars($a['nombre']); ?></h2>
                <p>Modifica los accesos y credenciales del administrador.</p>
                <div class="master-divider"></div>
            </header>

            <form id="formEditarAdmin" action="index.php?action=actualizar_admin" method="POST" class="master-form-grid">
                <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                
                <article class="card-formulario-master">
                    <h3 class="master-section-title"><i class="fas fa-user-cog"></i> Datos de Acceso</h3>
                    <div class="form-col-principal-card">
                        <fieldset class="grupo-control">
                            <label>Nombre Completo</label>
                            <input type="text" name="nombre" value="<?php echo htmlspecialchars($a['nombre']); ?>" class="providencia-field-master" required>
                        </fieldset>

                        <fieldset class="grupo-control">
                            <label>Correo Electrónico</label>
                            <input type="email" name="correo" value="<?php echo htmlspecialchars($a['correo']); ?>" class="providencia-field-master" required>
                        </fieldset>

                        <fieldset class="grupo-control">
                            <label>Nueva Contraseña</label>
                            <input type="password" name="clave" class="providencia-field-master" placeholder="Dejar vacío para mantener la actual">
                        </fieldset>
                    </div>
                </article>

                <article class="card-formulario-master">
                    <h3 class="master-section-title"><i class="fas fa-shield-alt"></i> Funciones Permitidas</h3>
                    <div class="master-inner-box">
                        <section class="master-permissions-grid">
                            <?php
                            $opciones = [
                                'cargar_producto' => 'Cargar Producto',
                                'gestionar_productos' => 'Gestionar Productos',
                                'graficos' => 'Estadísticas',
                                'pagos' => 'Pagos Pendientes',
                                'retiros' => 'Retiro de Pedidos',
                                'metodos_pago' => 'Métodos de Pago'
                            ];

                            foreach ($opciones as $valor => $texto) {
                                $checked = in_array($valor, $permisos_actuales) ? 'checked' : '';
                                echo "
                                <label>
                                    <input type='checkbox' name='permisos[]' value='$valor' $checked>
                                    <span>$texto</span>
                                </label>";
                            }
                            ?>
                        </section>
                    </div>
                </article>

                <footer class="master-form-footer">
                    <button type="submit" class="btn-save-master">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </footer>
            </form>
        </article>
    </main>
</section>