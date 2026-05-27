<style>
    .contenedor-reporte { background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin: 20px; }
    .tabla-admin { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .tabla-admin th { background: #f8f9fa; padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6; }
    .tabla-admin td { padding: 15px; border-bottom: 1px solid #eee; }
    
    .btn-activar, .btn-desactivar {
        padding: 8px 15px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 0.9rem;
        color: white;
        transition: 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-activar { background: #28a745; }
    .btn-activar:hover { background: #218838; }
    .btn-desactivar { background: #dc3545; }
    .btn-desactivar:hover { background: #c82333; }
    
    .estado-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
    .bg-activo { background: #d4edda; color: #155724; }
    .bg-inactivo { background: #f8d7da; color: #721c24; }

    /* Forzar visibilidad del texto en la tabla */
.tabla-admin {
    color: #333333 !important; /* Gris oscuro, muy legible sobre fondo blanco */
}

.tabla-admin th {
    color: #000000 !important; /* Títulos en negro puro */
    font-weight: bold;
}

.tabla-admin td {
    color: #444444 !important; /* Texto de filas en gris oscuro */
}

/* Asegurar que el texto dentro de los badges sea visible */
.estado-badge {
    color: #ffffff !important; /* Texto blanco sobre fondos de color */
    text-shadow: 0 1px 1px rgba(0,0,0,0.2); /* Sombra suave para que resalte */
}
</style>


<section class="contenedor-reporte">
    <h2><i class="fas fa-users-cog"></i> Gestión de Administradores</h2>
    
    <table class="tabla-admin">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($admins)): ?>
                <?php foreach ($admins as $admin): ?>
                <?php if ($admin !== null): // Validación extra de seguridad ?>
                <tr>
                    <td><?php echo htmlspecialchars($admin->nombre ?? 'Sin nombre'); ?></td>
                    <td>
                        <span class="estado-badge <?php echo (isset($admin->estado) && $admin->estado == 1) ? 'bg-activo' : 'bg-inactivo'; ?>">
                            <?php echo (isset($admin->estado) && $admin->estado == 1) ? 'Activo' : 'Inactivo'; ?>
                        </span>
                    </td>
                    <td>
                        <?php if (isset($admin->estado) && $admin->estado == 1): ?>
                            <a href="index.php?action=toggle_admin&id=<?php echo $admin->id ?? 0; ?>&estado=0" class="btn-desactivar">
                                <i class="fas fa-toggle-off"></i> Desactivar
                            </a>
                        <?php else: ?>
                            <a href="index.php?action=toggle_admin&id=<?php echo $admin->id ?? 0; ?>&estado=1" class="btn-activar">
                                <i class="fas fa-toggle-on"></i> Activar
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">No se encontraron administradores.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>