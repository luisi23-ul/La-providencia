<link rel="stylesheet" href="public/css/listado_productos.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="public/css/dashboard.css?v=<?php echo time(); ?>">
<style>
    .tabla-inventario tbody td {
        color: #1e293b !important; /* Un azul muy oscuro, casi negro, para máximo contraste */
        font-weight: 500 !important; /* Un poco de grosor para mejorar legibilidad */
        vertical-align: middle;
    }
    .columna-correo {
        color: #3b82f6 !important; /* Azul satinado para el correo, lo hace ver como enlace */
        font-weight: 400 !important;
    }
    .tabla-inventario thead th {
        background-color: #f1f5f9;
        color: #0f172a;
    }
</style>

<section class="main-dashboard-content">

    <article class="card-inventario">
        
        <header class="inventario-header">
            <h2>Administradores del Sistema</h2>
            
            <nav class="inventario-header-acciones">
                <a href="index.php?action=dashboard_master" class="btn-regresar-panel" title="Volver al Panel Master">
                    <i class="fas fa-home"></i> Panel Master
                </a>
                
                <a href="index.php?action=nuevo_admin" class="btn-satin-nuevo" title="Registrar nuevo administrador">
                    <i class="fas fa-user-plus"></i> Nuevo Admin
                </a>
            </nav>
        </header>

        <section class="tabla-responsiva-contenedor">
            <table class="tabla-inventario">
                <thead>
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Correo</th>
        <th>Permisos</th> <th>Estado</th>
        <th class="texto-centrado">Acciones</th>
    </tr>
</thead>
<tbody>
    <?php foreach($admins as $a): ?>
    <tr>
        <td><?php echo htmlspecialchars($a->id ?? 'N/A'); ?></td>
        <td><?php echo htmlspecialchars($a->nombre ?? 'Sin nombre'); ?></td>
        <td><?php echo htmlspecialchars($a->correo ?? 'Sin correo'); ?></td>
        
        <td class="columna-permisos">
            <?php echo htmlspecialchars($a->permisos ?? 'Ninguno'); ?>
        </td>

        <td>
            <span class="badge-estado <?php echo ($a->estado == 1) ? 'bg-activo' : 'bg-inactivo'; ?>">
                <?php echo ($a->estado == 1) ? 'Activo' : 'Inactivo'; ?>
            </span>
        </td>
        
       <td class="columna-acciones texto-centrado">
    <button type="button" class="btn-accion btn-editar" onclick="location.href='index.php?action=editar_admin&id=<?php echo $a->id; ?>'">
        <i class="fas fa-edit"></i>
    </button>

    <?php if ($a->estado == 1): ?>
        <a href="index.php?action=toggle_admin&id=<?php echo $a->id; ?>&estado=0" class="btn-accion btn-desactivar" title="Desactivar">
            <i class="fas fa-toggle-off"></i>
        </a>
    <?php else: ?>
        <a href="index.php?action=toggle_admin&id=<?php echo $a->id; ?>&estado=1" class="btn-accion btn-activar" title="Activar">
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

</section>