<style>
    /* Usamos exactamente tus mismos estilos para mantener la consistencia */
    .contenedor-reporte { background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin: 20px; }
    .tabla-admin { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .tabla-admin th { background: #f8f9fa; padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6; color: #000000 !important; font-weight: bold; }
    .tabla-admin td { padding: 15px; border-bottom: 1px solid #eee; color: #444444 !important; }
    
    .btn-activar, .btn-desactivar {
        padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 0.9rem; color: white;
        transition: 0.3s; display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-activar { background: #28a745; }
    .btn-activar:hover { background: #218838; }
    .btn-desactivar { background: #dc3545; }
    .btn-desactivar:hover { background: #c82333; }
    
    .estado-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; color: #ffffff !important; }
    .bg-activo { background: #28a745; }
    .bg-inactivo { background: #dc3545; }
</style>

<section class="contenedor-reporte">
    <h2><i class="fas fa-users"></i> Gestión de Clientes (Rol 3)</h2>
    
    <table class="tabla-admin">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($clientes)): ?>
                <?php foreach ($clientes as $c): ?>
                <tr>
                    <td><?php echo htmlspecialchars($c->nombre ?? 'Sin nombre'); ?></td>
                    <td><?php echo htmlspecialchars($c->correo ?? 'Sin correo'); ?></td>
                    <td>
                        <span class="estado-badge <?php echo ($c->estado == 1) ? 'bg-activo' : 'bg-inactivo'; ?>">
                            <?php echo ($c->estado == 1) ? 'Activo' : 'Inactivo'; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($c->estado == 1): ?>
                           <a href="index.php?action=cambiarEstadoCliente&id=<?php echo $c->id; ?>&estado=<?php echo ($c->estado == 1) ? '0' : '1'; ?>" 
   class="btn-accion <?php echo ($c->estado == 1) ? 'btn-desactivar' : 'btn-activar'; ?>">
    <i class="fas fa-toggle-<?php echo ($c->estado == 1) ? 'off' : 'on'; ?>"></i>
    <?php echo ($c->estado == 1) ? 'Desactivar' : 'Activar'; ?>
</a>
                        <?php else: ?>
                            <a href="index.php?action=cambiarEstadoCliente&id=<?php echo $c->id; ?>&estado=1" class="btn-activar">
                                <i class="fas fa-toggle-on"></i> Activar
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No se encontraron clientes registrados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>