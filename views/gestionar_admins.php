<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


<section class="panel-administracion">


<?php include 'sidebar-master.php'; ?>


<main class="main-master-content">
    <nav class="navegacion-superior">
            <a href="index.php?action=dashboard_master" class="btn-regresar-master">
                <i class="fas fa-arrow-left"></i>Volver
            </a>
        </nav>

<article class="card-master-listado">
        

        <header class="master-header-listado">

           <h2 class="titulo-con-icono">
        <i class="fas fa-users-cog"></i> <!-- Este es el icono de administración -->
        Administradores del Sistema
    </h2>
            
            <nav class="master-header-acciones">
                
                
                <a href="index.php?action=nuevo_admin" class="btn-satin-nuevo-master" title="Registrar nuevo administrador">
                    <i class="fas fa-user-plus"></i> Agregar
                </a>
            </nav>
        </header>

        <section class="tabla-master-contenedor">
            <table class="tabla-master-listado">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Permisos</th>
                        <th>Estado</th>
                        <th class="master-texto-centrado">Acciones</th>
                    </tr>
                </thead>
               <tbody>
                    <?php foreach($admins as $a): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($a->id ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($a->nombre ?? 'Sin nombre'); ?></td>
                        <td><?php echo htmlspecialchars($a->correo ?? 'Sin correo'); ?></td>
                        
                        <td class="master-columna-permisos">
                            <?php echo htmlspecialchars($a->permisos ?? 'Ninguno'); ?>
                        </td>

                        <td>
                            <span class="badge-estado-master <?php echo ($a->estado == 1) ? 'bg-activo-master' : 'bg-inactivo-master'; ?>">
                                <?php echo ($a->estado == 1) ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </td>
                        
                        <td class="master-columna-acciones master-texto-centrado">
                            <button type="button" class="btn-accion-master btn-editar-master" 
                                    onclick="location.href='index.php?action=editar_admin&id=<?php echo $a->id; ?>'">
                                <i class="fas fa-edit"></i>
                            </button>

                            <?php if ($a->estado == 1): ?>
                                <a href="index.php?action=toggle_admin&id=<?php echo $a->id; ?>&estado=0" 
                                   class="btn-accion-master btn-desactivar-master" title="Desactivar">
                                    <i class="fas fa-toggle-off"></i>
                                </a>
                            <?php else: ?>
                                <a href="index.php?action=toggle_admin&id=<?php echo $a->id; ?>&estado=1" 
                                   class="btn-accion-master btn-activar-master" title="Activar">
                                    <i class="fas fa-toggle-on"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        
    </article>

</main>
                            </section>
                            