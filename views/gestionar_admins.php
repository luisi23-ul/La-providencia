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
                        <th>Rol</th>
                        <th class="texto-centrado">Acciones</th>
                    </tr>
                </thead>
               <tbody>
    <?php foreach($admins as $a): ?>
    <tr>
        <td class="columna-id"><?php echo htmlspecialchars($a['id']); ?></td>
        <td class="columna-nombre"><?php echo htmlspecialchars($a['nombre']); ?></td>
        <td class="columna-correo"><?php echo htmlspecialchars($a['correo']); ?></td>
        
        <td class="columna-apellido"><?php echo htmlspecialchars($a['apellido'] ?? 'N/A'); ?></td>
        <td class="columna-permisos"><?php echo htmlspecialchars($a['permisos'] ?? 'Ninguno'); ?></td>
        
        <td class="columna-rol">
            <span class="badge-categoria">
                <?php echo ($a['id_rol'] == 1) ? 'Master' : 'Administrador'; ?>
            </span>
        </td>

        <td class="columna-acciones texto-centrado">
            <button type="button" class="btn-accion btn-editar" onclick="location.href='index.php?action=editar_admin&id=<?php echo $a['id']; ?>'">
                <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="btn-accion btn-eliminar" onclick="if(confirm('¿Eliminar?')) location.href='index.php?action=eliminar_admin&id=<?php echo $a['id']; ?>'">
                <i class="fas fa-trash-alt"></i>
            </button>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
            </table>
        </section>
        
    </article>

</section>