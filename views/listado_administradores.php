<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<section class="panel-administracion">
    <?php include 'sidebar-master.php'; ?>

    <main class="main-master-content">
        <nav class="navegacion-superior">
            <a href="index.php?action=dashboard_master" class="btn-regresar-master">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </nav>

        <article class="card-master-listado">
            <header class="master-header-listado">
                <h2 class="titulo-con-icono">
                    <i class="fas fa-users-cog"></i> Gestión de Administradores
                </h2>
            </header>

            <section class="tabla-master-contenedor">
                <table class="tabla-admin-master">
    <thead>
        <tr>
            <th class="col-nombre">Nombre</th>
            <th class="col-estado">Estado</th>
            <th class="col-accion">Acción</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($admins as $admin): ?>
        <tr>
            <td class="col-nombre"><?php echo htmlspecialchars($admin->nombre); ?>
            <span class="separador-vertical"></span>
        </td>
            <td class="col-estado">
                <span class="badge-estado-master <?php echo ($admin->estado == 1) ? 'bg-activo-master' : 'bg-inactivo-master'; ?>">
                    <?php echo ($admin->estado == 1) ? 'ACTIVO' : 'INACTIVO'; ?>
                </span>
                <span class="separador-vertical"></span>
            </td>
            <td class="col-accion">
                <div class="acciones-container">
                    <?php if ($admin->estado == 1): ?>
                        <a href="index.php?action=toggle_admin&id=<?php echo $admin->id; ?>&estado=0" class="btn-toggle-mini btn-desactivar">
                            <i class="fas fa-toggle-off"></i> Desactivar
                        </a>
                    <?php else: ?>
                        <a href="index.php?action=toggle_admin&id=<?php echo $admin->id; ?>&estado=1" class="btn-toggle-mini btn-activar">
                            <i class="fas fa-toggle-on"></i> Activar
                        </a>
                    <?php endif; ?>
                    <span class="separador-vertical"></span>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
            </section>
        </article>
    </main>
</section>