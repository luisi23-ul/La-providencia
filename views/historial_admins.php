<article class="card-formulario">
    <header class="formulario-header">
        <h2>Historial de Administradores</h2>
        <p>Gestiona el acceso de los administradores del sistema.</p>
    </header>

    <table class="table-providencia">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($admins as $admin): ?>
            <tr>
                <td><?php echo $admin['nombre']; ?></td>
                <td><?php echo $admin['correo']; ?></td>
                <td>
                    <span class="badge <?php echo $admin['estado'] == 1 ? 'active' : 'inactive'; ?>">
                        <?php echo $admin['estado'] == 1 ? 'Activo' : 'Inactivo'; ?>
                    </span>
                </td>
                <td>
                    <input type="checkbox" 
                           onchange="window.location.href='index.php?action=cambiar_estado&id=<?php echo $admin['id']; ?>&estado=<?php echo ($admin['estado'] == 1 ? 0 : 1); ?>'" 
                           <?php echo ($admin['estado'] == 1 ? 'checked' : ''); ?>>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <footer class="formulario-acciones">
        <a href="index.php?action=gestionar_admins" class="btn-providencia-link">Volver a Activos</a>
    </footer>
</article>