
<?php
// Validar que administrador existe y no es nulo
if (!$a) {
    die("Administrador no encontrado.");
}

// Convertimos los permisos guardados en la BD (string) a un array
$permisos_actuales = explode(',', $a['permisos'] ?? '');
?>
<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="public/css/formulario_producto.css?v=<?php echo time(); ?>">

<article class="card-formulario">
    <header class="formulario-header">
        <h2>Configurar Permisos: <?php echo htmlspecialchars($a['nombre']); ?></h2>
        <p class="subtitle">Selecciona las funciones activas para este administrador.</p>
    </header>
    
    <form id="formEditarAdmin" action="index.php?action=actualizar_admin" method="POST">
        <input type="hidden" name="id" value="<?php echo $a['id']; ?>">

        <fieldset class="grupo-control">
            <label>Nombre Completo</label>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($a['nombre']); ?>" class="providencia-field" required>
        </fieldset>

        <fieldset class="grupo-control">
            <label>Correo</label>
            <input type="email" name="correo" value="<?php echo htmlspecialchars($a['correo']); ?>" class="providencia-field" required>
        </fieldset>

        <fieldset class="grupo-control">
            <label>Nueva Clave</label>
            <input type="password" name="clave" class="providencia-field" placeholder="Dejar vacío para mantener la actual">
        </fieldset>

        <fieldset class="grupo-control">
            <label>Funciones Habilitadas</label>
            <?php
            $opciones = [
                'cargar_producto' => 'Cargar Producto',
                'gestionar_productos' => 'Gestionar Productos',
                'graficos' => 'Gráficos Estadísticos',
                'pagos_pendientes' => 'Pagos Pendientes',
                'retiro_pedidos' => 'Retiro de Pedidos',
                'metodos_pago' => 'Métodos de Pago'
            ];

            foreach ($opciones as $valor => $texto) {
                // Comparamos el valor con el array de permisos actuales para marcar el checkbox
                $checked = in_array($valor, $permisos_actuales) ? 'checked' : '';
                echo "<label><input type='checkbox' name='permisos[]' value='$valor' $checked> $texto</label>";
            }
            ?>
        </fieldset>

        <footer class="formulario-acciones">
            <button type="submit" class="btn-providencia-save">GUARDAR CAMBIOS</button>
            <a href="index.php?action=gestionar_admins" class="btn-providencia-link btn-secondary-satin">VOLVER</a>
        </footer>
    </form>
</article>