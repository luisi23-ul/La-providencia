<?php
$menu = 'dashboard_master';
$tituloPagina = 'Inicio Master';
$panelMaster = true;
$resumen = $resumen ?? [];
$actividad = $actividad ?? [];

$tasa = $tasa ?? 526.87;
$usd  = $usd ?? fn($v) => '$' . number_format((float) $v, 2);
$bs   = $bs ?? fn($v) => number_format((float) $v * (float) $tasa, 2, ',', '.') . ' Bs';

include __DIR__ . '/admin/_abrir.php';
$activos = (int) ($resumen['personal_activo'] ?? 0);
$cuentas = (int) ($resumen['cuentas'] ?? 0);
$inactivas = max(0, $cuentas - $activos);
$nombresRoles = $resumen['roles'] ?? [];
$ultimoRol = array_pop($nombresRoles);
$listaRoles = $nombresRoles ? implode(', ', $nombresRoles) . ' y ' . $ultimoRol : (string) $ultimoRol;
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Panel Master</small><h2>Resumen del sistema</h2></div>
    <div class="cab-acc"><a href="index.php?action=dashboard" class="btn btn-ir-admin"><svg class="i"><use href="#i-inicio"/></svg>Ir al Panel Admin</a></div>
  </header>

  <section class="mini-kpis" aria-label="Indicadores">
    <div><span class="mk-ic"><svg class="i"><use href="#i-usuarios"/></svg></span><span>Personal activo</span><b><?= $activos ?></b><small>de <?= $cuentas ?> <?= $cuentas === 1 ? 'cuenta' : 'cuentas' ?><?= $inactivas > 0 ? ' · ' . $inactivas . ($inactivas === 1 ? ' desactivada' : ' desactivadas') : '' ?></small></div>
    <div class="mk-oscuro"><span class="mk-ic"><svg class="i"><use href="#i-escudo"/></svg></span><span>Roles</span><b><?= count($resumen['roles'] ?? []) ?></b><small><?= $e($listaRoles) ?></small></div>
    <div class="mk-claro"><span class="mk-ic"><svg class="i"><use href="#i-lista"/></svg></span><span>Acciones hoy</span><b><?= (int) ($resumen['acciones_hoy'] ?? 0) ?></b><small>registradas en la auditoría</small></div>
    <div class="mk-ambar"><span class="mk-ic"><svg class="i"><use href="#i-dolar"/></svg></span><span>Ventas de hoy</span><b><?= $usd($resumen['ventas_hoy'] ?? 0) ?></b><small><?= $bs($resumen['ventas_hoy'] ?? 0) ?></small></div>
  </section>

  <div class="rejilla-2">
    <section class="caja" aria-labelledby="mi-act">
      <header class="caja-cab"><h3 id="mi-act"><span class="fpx-ic"><svg class="i"><use href="#i-historial"/></svg></span>Actividad reciente</h3><a href="index.php?action=auditoria">Ver auditoría</a></header>
      <?php if ($actividad): ?>
        <ul class="lista mi-act">
          <?php foreach ($actividad as $a):
              $t = strtotime($a->fecha); ?>
            <li>
              <span class="mi-av" aria-hidden="true"><?= $e(mb_strtoupper(mb_substr($a->usuario ?? 'S', 0, 1))) ?></span>
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
      <header class="caja-cab"><h3 id="mi-acc"><span class="fpx-ic"><svg class="i"><use href="#i-ajuste"/></svg></span>Accesos</h3></header>
      <nav class="mi-accesos" aria-labelledby="mi-acc">
        <a href="index.php?action=gestionar_admins"><span class="mi-acc-ic"><svg class="i"><use href="#i-usuarios"/></svg></span><span><b>Administradores</b><small>Crear cuentas, cambiar roles, desactivar</small></span></a>
        <a href="index.php?action=roles_permisos"><span class="mi-acc-ic"><svg class="i"><use href="#i-escudo"/></svg></span><span><b>Roles y permisos</b><small>Qué puede hacer cada rol</small></span></a>
        <a href="index.php?action=configuracion_sistema"><span class="mi-acc-ic"><svg class="i"><use href="#i-ajuste"/></svg></span><span><b>Configuración</b><small>Datos de la tienda, tasa, WhatsApp y página web</small></span></a>
      </nav>
    </section>
  </div>
</section>
<?php include __DIR__ . '/admin/_cerrar.php'; ?>