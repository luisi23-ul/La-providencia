<?php
$menu = 'roles_permisos';
$tituloPagina = 'Roles y permisos';
$panelMaster = true;
$scripts = ['roles_permisos'];
$roles = $roles ?? [];
$permisos = $permisos ?? [];
$rolPermisos = $rolPermisos ?? [];
include __DIR__ . '/../admin/_abrir.php';
$roles = array_values(array_filter($roles, fn($r) => (int) $r->id !== 3));
usort($roles, fn($a, $b) => (int) $a->id <=> (int) $b->id);
$porModulo = [];
foreach ($permisos as $p) $porModulo[$p->modulo][] = $p;
$editables = array_values(array_filter($roles, fn($r) => (int) $r->id !== 1));
$primerRol = $editables ? (int) $editables[0]->id : 1;
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Panel Master</small><h2>Roles y permisos</h2><small>Marca qué puede hacer cada rol. Los administradores heredan los permisos de su rol.</small></div>
    <div class="cab-acc"><button type="button" class="btn btn-pri" data-abrir-modal="modal-rol"><svg class="i"><use href="#i-mas"/></svg>Nuevo rol</button></div>
  </header>

  <?php if ($roles && $permisos): ?>
  <div class="seg roles-movil" role="group" aria-label="Rol">
    <?php foreach ($roles as $r): ?>
      <button type="button" data-ver-rol="<?= (int) $r->id ?>" aria-pressed="<?= (int) $r->id === $primerRol ? 'true' : 'false' ?>"><?= $e($r->nombre_rol) ?></button>
    <?php endforeach; ?>
  </div>

  <form method="POST" action="index.php?action=guardar_permisos" id="form-permisos" class="cfg" style="max-width:none">
    <section class="tabla-caja" aria-label="Permisos por rol">
      <div class="tabla-scroll">
        <table class="matriz" id="matriz" data-rol="<?= $primerRol ?>">
          <thead><tr>
            <th>Permiso</th>
            <?php foreach ($roles as $r):
                $n = (int) ($r->total_usuarios ?? 0); ?>
              <th data-r="<?= (int) $r->id ?>" class="rol-cab"><?= $e($r->nombre_rol) ?><small><?= (int) $r->id === 1 ? 'todo' : $n . ' ' . ($n === 1 ? 'persona' : 'personas') ?></small></th>
            <?php endforeach; ?>
          </tr></thead>
          <tbody>
          <?php foreach ($porModulo as $modulo => $lista): ?>
            <tr class="grupo"><td colspan="<?= count($roles) + 1 ?>"><?= $e($modulo) ?></td></tr>
            <?php foreach ($lista as $p): ?>
              <tr>
                <td><span class="perm"><b><?= $e($p->nombre) ?></b><code><?= $e($p->codigo) ?></code></span></td>
                <?php foreach ($roles as $r):
                    $master = (int) $r->id === 1;
                    $tiene = $master || in_array((int) $p->id, array_map('intval', $rolPermisos[$r->id] ?? []), true); ?>
                  <td data-r="<?= (int) $r->id ?>"><input type="checkbox" <?= $master ? 'disabled' : 'name="permisos[' . (int) $r->id . '][]"' ?> value="<?= (int) $p->id ?>" aria-label="<?= $e($p->nombre . ' · ' . $r->nombre_rol) ?>" <?= $tiene ? 'checked' : '' ?>></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php foreach ($roles as $r): if ((int) $r->id !== 1): ?><input type="hidden" name="roles[]" value="<?= (int) $r->id ?>"><?php endif; endforeach; ?>
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
    <header class="modal-cab"><div><h3 id="mr-titulo">Nuevo rol</h3><p>Después marca en la tabla qué puede hacer.</p></div><button type="button" class="ic-btn" data-cerrar aria-label="Cerrar"><svg class="i"><use href="#i-x"/></svg></button></header>
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
