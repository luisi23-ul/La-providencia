<?php
$inicio = $panelMaster ? ['dashboard_master', 'Inicio Master', 'inicio'] : ['dashboard', 'Inicio', 'inicio'];
$grupos = $panelMaster ? [
    'Personal' => ['icono' => 'usuarios', 'items' => [
        ['gestionar_admins', 'Administradores', null, 'usuarios'],
        ['roles_permisos', 'Roles y permisos', null, 'escudo'],
    ]],
    'Control' => ['icono' => 'escudo', 'items' => [
        ['auditoria', 'Auditoría', null, 'lista'],
        ['configuracion_sistema', 'Configuración', null, 'ajuste'],
    ]],
] : [
    'Ventas' => ['icono' => 'recibo', 'items' => [
        ['punto_venta', 'Punto de venta', 'punto_venta', 'pos'],
        ['ventas', 'Ventas', 'ventas', 'recibo'],
        ['verificar_pagos', 'Verificar pagos', 'pagos', 'check'],
        ['retiro_pedidos', 'Retiro de pedidos', 'retiros', 'bolsa'],
        ['pagos_banco', 'Pagos por banco', 'pagos', 'tarjeta'],
    ]],
    'Inventario' => ['icono' => 'caja', 'items' => [
        ['listado_productos', 'Productos', 'gestionar_productos', 'caja'],
        ['categorias', 'Categorías', 'categorias', 'etiqueta'],
        ['ajustes_inventario', 'Ajustes de stock', 'ajustes_inventario', 'ajuste'],
        ['kardex', 'Movimientos', 'kardex', 'lista'],
    ]],
    'Compras' => ['icono' => 'carrito', 'items' => [
        ['proveedores', 'Proveedores', 'proveedores', 'camion'],
        ['compras', 'Compras', 'compras', 'carrito'],
    ]],
    'Gestión' => ['icono' => 'grafico', 'items' => [
        ['lista_clientes', 'Clientes', 'clientes', 'usuarios'],
        ['metodos_pago', 'Métodos de pago', 'metodos_pago', 'tarjeta'],
        ['seccion_graficos', 'Estadísticas', 'graficos', 'grafico'],
    ]],
];
$contadores = $contadores ?? [];
$nombreUsuario = $_SESSION['nombre'] ?? 'Usuario';
$iniciales = strtoupper(implode('', array_map(fn($p) => mb_substr($p, 0, 1), array_slice(preg_split('/\s+/', trim($nombreUsuario)), 0, 2))));
$roles = [1 => 'Master', 2 => 'Administrador', 4 => 'Vendedor', 5 => 'Cajero'];
?>
<aside class="lateral" aria-label="Menú del panel">
  <div class="marca"><span class="marca-logo">P</span><div><strong>La Providencia</strong><span><?= $panelMaster ? 'Master Panel' : 'Admin Panel' ?></span></div></div>
  <nav class="nav">
    <a href="index.php?action=<?= $inicio[0] ?>" class="<?= ($menu ?? '') === $inicio[0] ? 'activo' : '' ?>"><svg class="i"><use href="#i-<?= $inicio[2] ?>"/></svg><?= $inicio[1] ?></a>
    <?php foreach ($grupos as $grupo => $sec):
        $visibles = array_filter($sec['items'], fn($i) => $i[2] === null || $puede($i[2]));
        if (!$visibles) continue;
        $acciones = array_column($visibles, 0);
        $abierta = in_array($menu ?? '', $acciones, true);
        $avisos = array_sum(array_map(fn($a) => (int) ($contadores[$a] ?? 0), $acciones)); ?>
      <details class="nav-sec" name="nav" <?= $abierta ? 'open' : '' ?>>
        <summary class="<?= $abierta ? 'actual' : '' ?>"><svg class="i"><use href="#i-<?= $sec['icono'] ?>"/></svg><?= $grupo ?><?php if ($avisos): ?><span class="cuenta"><?= $avisos ?></span><?php endif; ?><svg class="i flecha" aria-hidden="true"><use href="#i-chevron"/></svg></summary>
        <div class="nav-sub">
          <?php foreach ($visibles as [$accion, $texto, , $icono]): ?>
            <a href="index.php?action=<?= $accion ?>" class="<?= ($menu ?? '') === $accion ? 'activo' : '' ?>"<?= ($menu ?? '') === $accion ? ' aria-current="page"' : '' ?>><svg class="i"><use href="#i-<?= $icono ?>"/></svg><?= $texto ?><?php if (!empty($contadores[$accion])): ?><span class="cuenta"><?= (int) $contadores[$accion] ?></span><?php endif; ?></a>
          <?php endforeach; ?>
        </div>
      </details>
    <?php endforeach; ?>
  </nav>
  <div class="lateral-pie">
    <div class="usuario"><span class="avatar"><?= htmlspecialchars($iniciales) ?></span><div><b><?= htmlspecialchars($nombreUsuario) ?></b><span><?= $roles[$rol] ?? '' ?></span></div></div>
    <nav class="nav">
      <?php if ($panelMaster): ?>
        <a href="index.php?action=dashboard" class="master" style="color:#93c5fd"><svg class="i"><use href="#i-inicio"/></svg>Ir al Panel Admin</a>
      <?php elseif ($rol == 1): ?>
        <a href="index.php?action=dashboard_master" class="master"><svg class="i"><use href="#i-escudo"/></svg>Ir al Panel Master</a>
      <?php endif; ?>
      <a href="index.php?action=inicio"><svg class="i"><use href="#i-tienda"/></svg>Ver tienda</a>
      <a href="index.php?action=cerrar_sesion" class="salir"><svg class="i"><use href="#i-salir"/></svg>Cerrar sesión</a>
    </nav>
  </div>
</aside>
