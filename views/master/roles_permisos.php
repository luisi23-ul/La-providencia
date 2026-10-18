<?php
$menu = 'roles_permisos';
$tituloPagina = 'Roles y permisos';
$panelMaster = true;
$scripts = ['roles_permisos'];
$roles = $roles ?? [];
$permisos = $permisos ?? [];
$rolPermisos = $rolPermisos ?? [];
include __DIR__ . '/../admin/_abrir.php';
$roles = array_values(array_filter($roles, fn($r) => !es_rol('cliente', clave('rol', $r))));
usort($roles, fn($a, $b) => [!es_rol('master', clave('rol', $a)), clave('rol', $a)] <=> [!es_rol('master', clave('rol', $b)), clave('rol', $b)]);
$porModulo = [];
foreach ($permisos as $p) $porModulo[$p->modulo][] = $p;
$editables = array_values(array_filter($roles, fn($r) => !es_rol('master', clave('rol', $r))));
$primerRol = $editables ? clave('rol', $editables[0]) : rol_valor('master');
$iconoModulo = fn($m) => ['compras' => 'carrito', 'gestión' => 'ajuste', 'gestion' => 'ajuste', 'inicio' => 'inicio', 'inventario' => 'etiqueta', 'master' => 'escudo', 'ventas' => 'recibo'][mb_strtolower(trim($m))] ?? 'lista';
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Panel Master</small><h2>Roles y permisos</h2></div>
    <div class="cab-acc"><button type="button" class="btn btn-pri" data-abrir-modal="modal-rol"><svg class="i"><use href="#i-mas"/></svg>Nuevo rol</button></div>
  </header>

  <?php if ($roles && $permisos): ?>
  <div class="seg roles-movil" role="group" aria-label="Rol">
    <?php foreach ($roles as $r): ?>
      <button type="button" data-ver-rol="<?= $e(clave('rol', $r)) ?>" aria-pressed="<?= clave('rol', $r) === $primerRol ? 'true' : 'false' ?>"><?= $e($r->nombre_rol) ?></button>
    <?php endforeach; ?>
  </div>

  <form method="POST" action="index.php?action=guardar_permisos" id="form-permisos" class="cfg" style="max-width:none">
    <section class="tabla-caja" aria-label="Permisos por rol">
      <div class="tabla-scroll">
        <table class="matriz" id="matriz" data-rol="<?= $e($primerRol) ?>">
          <thead><tr>
            <th>Permiso</th>
            <?php foreach ($roles as $r):
                $n = (int) ($r->total_usuarios ?? 0); ?>
              <th data-r="<?= $e(clave('rol', $r)) ?>" class="rol-cab"><?= $e($r->nombre_rol) ?><small><?= es_rol('master', clave('rol', $r)) ? 'todo' : $n . ' ' . ($n === 1 ? 'persona' : 'personas') ?></small></th>
            <?php endforeach; ?>
          </tr></thead>
          <tbody>
          <?php foreach ($porModulo as $modulo => $lista): ?>
            <tr class="grupo"><td colspan="<?= count($roles) + 1 ?>"><span class="rp-grupo"><span class="rp-ic"><svg class="i"><use href="#i-<?= $iconoModulo($modulo) ?>"/></svg></span><b><?= $e($modulo) ?></b><small><?= count($lista) ?> <?= count($lista) === 1 ? 'permiso' : 'permisos' ?></small></span></td></tr>
            <?php foreach ($lista as $p): ?>
              <tr>
                <td><span class="perm"><b title="<?= $e($p->codigo ?? clave('permiso', $p)) ?>"><?= $e($p->nombre) ?></b></span></td>
                <?php foreach ($roles as $r):
                    $master = es_rol('master', clave('rol', $r));
                    $tiene = $master || in_array(clave('permiso', $p), array_map('strval', $rolPermisos[clave('rol', $r)] ?? []), true); ?>
                  <td data-r="<?= $e(clave('rol', $r)) ?>"><?php if ($master): ?><span class="rp-siempre" title="El Master siempre tiene este permiso"><svg class="i" aria-hidden="true"><use href="#i-check"/></svg><span class="sr-only">Siempre</span></span><?php else: ?><label class="rp-celda"><input type="checkbox" class="rp-casilla" name="permisos[<?= $e(clave('rol', $r)) ?>][]" value="<?= $e(clave('permiso', $p)) ?>" aria-label="<?= $e($p->nombre . ' · ' . $r->nombre_rol) ?>" <?= $tiene ? 'checked' : '' ?>></label><?php endif; ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php foreach ($roles as $r): if (!es_rol('master', clave('rol', $r))): ?><input type="hidden" name="<?= campo('rol') ?>[]" value="<?= $e(clave('rol', $r)) ?>"><?php endif; endforeach; ?>
    <div class="barra-guardar">
      <span class="sub-resumen" id="cambios-perm">Sin cambios</span>
      <div class="acciones-pago" style="flex:0 1 auto">
        <button type="reset" class="btn btn-sec" id="descartar-perm" disabled>Descartar</button>
        <button type="submit" class="btn btn-pri" id="guardar-perm" disabled>Guardar cambios</button>
      </div>
    </div>
  </form>
  <?php else: ?>
    <section class="tabla-caja"><div class="vacio-filtro">Todavía no hay permisos cargados en la base de datos.</div></section>
  <?php endif; ?>
  <p class="muted">El rol Cliente no aparece porque no entra al panel. El Master siempre tiene todos los permisos.</p>
</section>

<dialog class="modal chico" id="modal-rol" aria-labelledby="mr-titulo">
  <form method="POST" action="index.php?action=guardar_rol">
    <header class="modal-cab"><span class="modal-ic"><svg class="i"><use href="#i-escudo"/></svg></span><div><h3 id="mr-titulo">Nuevo rol</h3><p>Después marca en la tabla qué puede hacer.</p></div><button type="button" class="ic-btn" data-cerrar aria-label="Cerrar"><svg class="i"><use href="#i-x"/></svg></button></header>
    <div class="modal-cuerpo">
      <div class="campo"><label for="mr-nombre">Nombre del rol <span class="ob">*</span></label><input id="mr-nombre" name="nombre_rol" required maxlength="50" placeholder="Ej.: Almacenista"></div>
    </div>
    <footer class="modal-pie">
      <button type="button" class="btn btn-sec" data-cerrar>Cancelar</button>
      <button type="submit" class="btn btn-pri">Crear rol</button>
    </footer>
  </form>
</dialog>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>
