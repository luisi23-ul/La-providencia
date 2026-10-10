<?php
$menu = 'auditoria';
$tituloPagina = 'Auditoría';
$panelMaster = true;
$scripts = ['auditoria'];
$registros = $registros ?? [];
$usuarios = $usuarios ?? [];
$modulos = $modulos ?? ['Ventas', 'Pagos', 'Inventario', 'Compras', 'Usuarios', 'Configuración', 'Sesión'];
include __DIR__ . '/../admin/_abrir.php';
$f = [
    'q' => $_GET['q'] ?? '',
    'usuario' => $_GET['usuario'] ?? '',
    'modulo' => $_GET['modulo'] ?? '',
    'desde' => $_GET['desde'] ?? date('Y-m-d', strtotime('-6 days')),
    'hasta' => $_GET['hasta'] ?? date('Y-m-d'),
];
$acciones = ['INSERT' => ['ins', 'Creó'], 'UPDATE' => ['upd', 'Modificó'], 'DELETE' => ['del', 'Eliminó'], 'LOGIN' => ['log', 'Inició sesión'], 'VERIFICACION' => ['ver', 'Verificó']];
$fecha = fn($v) => date('d/m', strtotime($v)) . ' · ' . str_replace(['am', 'pm'], ['a. m.', 'p. m.'], date('g:i a', strtotime($v)));
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Panel Master</small><h2>Auditoría</h2><small>Lo que hizo cada persona en el sistema. No se puede editar ni borrar.</small></div>
    <div class="cab-acc"><a href="index.php?<?= $e(http_build_query(['action' => 'exportar_auditoria'] + $f)) ?>" class="btn btn-sec"><svg class="i"><use href="#i-archivo"/></svg>Excel</a></div>
  </header>

  <form class="herr" method="GET" action="index.php" id="filtros-aud">
    <input type="hidden" name="action" value="auditoria">
    <label class="buscar"><svg class="i"><use href="#i-buscar"/></svg><input type="search" name="q" value="<?= $e($f['q']) ?>" placeholder="Buscar en la descripción" aria-label="Buscar en la descripción"></label>
    <select class="selector" name="usuario" aria-label="Usuario">
      <option value="">Todos los usuarios</option>
      <?php foreach ($usuarios as $u): ?>
        <option value="<?= (int) $u->id ?>" <?= (string) $u->id === (string) $f['usuario'] ? 'selected' : '' ?>><?= $e(trim($u->nombre . ' ' . ($u->apellido ?? ''))) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="selector" name="modulo" aria-label="Módulo">
      <option value="">Todos los módulos</option>
      <?php foreach ($modulos as $m): ?>
        <option <?= $m === $f['modulo'] ? 'selected' : '' ?>><?= $e($m) ?></option>
      <?php endforeach; ?>
    </select>
    <span class="fecha-rango">Desde <input type="date" name="desde" value="<?= $e($f['desde']) ?>" aria-label="Desde"> hasta <input type="date" name="hasta" value="<?= $e($f['hasta']) ?>" aria-label="Hasta"></span>
    <noscript><button type="submit" class="btn btn-sec">Filtrar</button></noscript>
  </form>

  <section class="tabla-caja" aria-label="Registro de auditoría">
    <div class="tabla-scroll">
      <table class="tabla-resp">
        <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Módulo</th><th>Descripción</th><th class="c-media">IP</th></tr></thead>
        <tbody>
        <?php foreach ($registros as $r):
            $ac = $acciones[$r->accion] ?? ['log', $r->accion]; ?>
          <tr>
            <td data-label="Fecha" class="nums" style="white-space:nowrap"><time datetime="<?= $e(date('c', strtotime($r->fecha))) ?>"><?= $fecha($r->fecha) ?></time></td>
            <td class="t-tit"><b><?= $e($r->usuario ?? 'Sistema') ?></b></td>
            <td data-label="Acción"><span class="accion <?= $ac[0] ?>"><?= $ac[1] ?></span></td>
            <td data-label="Módulo"><?= $e($r->modulo) ?></td>
            <td data-label="Descripción"><div class="t-nombre"><span style="color:var(--texto)"><?= $e($r->descripcion) ?></span><?php if (!empty($r->ip)): ?><span class="si-estrecho cifra">IP <?= $e($r->ip) ?></span><?php endif; ?></div></td>
            <td data-label="IP" class="cod c-media"><?= $e($r->ip ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (!$registros): ?><div class="vacio-filtro">No hay registros con estos filtros.</div><?php endif; ?>
  </section>
</section>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>
