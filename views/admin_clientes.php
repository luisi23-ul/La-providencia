<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">

<section class="panel-administracion">
    <?php include 'sidebar.php'; ?>

    <main class="main-dashboard-content">
        <nav class="navegacion-superior">
            <a href="index.php?action=dashboard" class="btn-regresar-panel">
                <i class="fas fa-arrow-left"></i>Volver
            </a>
        </nav>

        <section class="panel-modulo-contenedor">
            <header class="pagos-header">
                <div class="header-bottom">
                    <h2><i class="fas fa-users"></i> Gestión de Clientes</h2>
                </div>
            </header>
                <div class="contenedor-clientes">
            <table class="tabla-modulo-admin">
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
                            <td>
                                <div class="celda-nombre">
                                    <strong><?php echo htmlspecialchars($c->nombre ?? 'Sin nombre'); ?></strong>
                        </div>
                                    </td>
                                    
                            <td>
                                <div class="celda-correo">
                                    <?php echo htmlspecialchars($c->correo ?? 'Sin correo'); ?>
                                </div>
                            </td>
                            <td>
                                <div class="celda-centrada">
                                <span class="badge-estado <?php echo ($c->estado == 1) ? 'estado-activo' : 'estado-inactivo'; ?>">
                                    <?php echo ($c->estado == 1) ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                           <!-- Esta celda única reemplaza las dos que tenías antes --></div>
<td>
    <div class="celda-centrada">
    <label class="switch">
        <!-- El checkbox maneja la lógica de cambio de estado -->
        <input type="checkbox" 
               <?php echo ($c->estado == 1) ? 'checked' : ''; ?> 
               onchange="window.location.href='index.php?action=cambiarEstadoCliente&id=<?php echo $c->id; ?>&estado=' + (this.checked ? '1' : '0')">
        <span class="slider"></span>
    </label>
                        </div>
</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="tabla-vacia">No se encontraron clientes.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
                    </div>
        </section>
    </main>
</section>