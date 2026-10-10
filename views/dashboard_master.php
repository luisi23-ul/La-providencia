<?php
$menu = 'dashboard_master';
$tituloPagina = 'Inicio Master';
$panelMaster = true;
$resumen = $resumen ?? [];
$actividad = $actividad ?? [];
include __DIR__ . '/admin/_abrir.php';
$activos = (int) ($resumen['personal_activo'] ?? 0);
$cuentas = (int) ($resumen['cuentas'] ?? 0);
$inactivas = $cuentas - $activos;
$nombresRoles = $resumen['roles'] ?? [];
$ultimoRol = array_pop($nombresRoles);
$listaRoles = $nombresRoles ? implode(', ', $nombresRoles) . ' y ' . $ultimoRol : (string) $ultimoRol;
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Panel Master</small><h2>Resumen del sistema</h2></div>
    <div class="cab-acc"><a href="index.php?action=dashboard" class="btn btn-sec"><svg class="i"><use href="#i-inicio"/></svg>Ir al Panel Admin</a></div>
  </header>

  <section class="kpis" aria-label="Indicadores">
    <a href="index.php?action=gestionar_admins" class="kpi">
      <span class="kpi-cab">Personal activo<span class="kpi-ic vi"><svg class="i"><use href="#i-usuarios"/></svg></span></span>
      <span class="kpi-val"><?= $activos ?></span>
      <span class="kpi-sub">de <?= $cuentas ?> <?= $cuentas === 1 ? 'cuenta' : 'cuentas' ?></span>
      <?php if ($inactivas > 0): ?><span class="kpi-pie"><?= $inactivas ?> <?= $inactivas === 1 ? 'cuenta desactivada' : 'cuentas desactivadas' ?></span><?php endif; ?>
    </a>
    <a href="index.php?action=roles_permisos" class="kpi">
      <span class="kpi-cab">Roles<span class="kpi-ic az"><svg class="i"><use href="#i-escudo"/></svg></span></span>
      <span class="kpi-val"><?= count($resumen['roles'] ?? []) ?></span>
      <span class="kpi-sub"><?= $e($listaRoles) ?></span>
    </a>
    <a href="index.php?action=auditoria" class="kpi">
      <span class="kpi-cab">Acciones registradas hoy<span class="kpi-ic am"><svg class="i"><use href="#i-lista"/></svg></span></span>
      <span class="kpi-val"><?= (int) ($resumen['acciones_hoy'] ?? 0) ?></span>
      <span class="kpi-sub">en la auditoría</span>
    </a>
    <a href="index.php?action=dashboard" class="kpi">
      <span class="kpi-cab">Ventas de hoy<span class="kpi-ic az"><svg class="i"><use href="#i-dolar"/></svg></span></span>
      <span class="kpi-val"><?= $usd($resumen['ventas_hoy'] ?? 0) ?></span>
      <span class="kpi-sub"><?= $bs($resumen['ventas_hoy'] ?? 0) ?></span>
    </a>
  </section>

  <div class="rejilla-2">
    <section class="caja" aria-labelledby="mi-act">
      <header class="caja-cab"><h3 id="mi-act">Actividad reciente</h3><a href="index.php?action=auditoria">Ver auditoría</a></header>
      <?php if ($actividad): ?>
        <ul class="lista">
          <?php foreach ($actividad as $a):
              $t = strtotime($a->fecha); ?>
            <li>
              <div class="quien"><b><?= $e($a->usuario ?? 'Sistema') ?></b><span><?= $e($a->descripcion) ?></span></div>
              <time class="muted" datetime="<?= date('c', $t) ?>" style="white-space:nowrap"><?= date('Y-m-d', $t) === date('Y-m-d') ? str_replace(['am', 'pm'], ['a. m.', 'p. m.'], date('g:i a', $t)) : date('d/m', $t) ?></time>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="vacio-filtro">Todavía no hay acciones registradas.</p>
      <?php endif; ?>
    </section>
    <section class="caja" aria-labelledby="mi-acc">
      <header class="caja-cab"><h3 id="mi-acc">Accesos</h3></header>
      <nav class="mi-accesos" aria-labelledby="mi-acc">
        <a href="index.php?action=gestionar_admins"><svg class="i"><use href="#i-usuarios"/></svg><span><b>Administradores</b><small>Crear cuentas, cambiar roles, desactivar</small></span></a>
        <a href="index.php?action=roles_permisos"><svg class="i"><use href="#i-escudo"/></svg><span><b>Roles y permisos</b><small>Qué puede hacer cada rol</small></span></a>
        <a href="index.php?action=configuracion_sistema"><svg class="i"><use href="#i-ajuste"/></svg><span><b>Configuración</b><small>Datos de la tienda, tasa, WhatsApp y página web</small></span></a>
      </nav>
    </section>
  </div>
</section>
<?php include __DIR__ . '/admin/_cerrar.php'; ?>
