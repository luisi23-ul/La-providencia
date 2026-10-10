<?php
$menu = 'gestionar_admins';
$tituloPagina = 'Administradores';
$panelMaster = true;
$scripts = ['administradores'];
$admins = $admins ?? [];
$roles = $roles ?? [];
include __DIR__ . '/admin/_abrir.php';
$miId = $_SESSION['id_usuario'] ?? null;
$claseRol = [4 => 'vendedor', 5 => 'cajero'];
$nombreRol = array_column(array_map(fn($r) => (array) $r, $roles), 'nombre_rol', 'id') + [1 => 'Master', 2 => 'Administrador', 4 => 'Vendedor', 5 => 'Cajero'];
$acceso = function ($fecha) {
    if (!$fecha) return 'Nunca';
    $t = strtotime($fecha);
    $hora = str_replace(['am', 'pm'], ['a. m.', 'p. m.'], date('g:i a', $t));
    if (date('Y-m-d', $t) === date('Y-m-d')) return 'Hoy, ' . $hora;
    if (date('Y-m-d', $t) === date('Y-m-d', strtotime('-1 day'))) return 'Ayer, ' . $hora;
    return date('d/m/Y', $t);
};
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Panel Master</small><h2>Administradores</h2></div>
    <div class="cab-acc"><button type="button" class="btn btn-pri" data-abrir-modal="modal-admin"><svg class="i"><use href="#i-mas"/></svg>Nueva cuenta</button></div>
  </header>

  <section class="tabla-caja" aria-label="Personal">
    <div class="tabla-scroll">
      <table class="tabla-resp">
        <thead><tr><th>Persona</th><th>Rol</th><th class="c-media">Último acceso</th><th>Cuenta</th><th class="num">Editar</th></tr></thead>
        <tbody>
        <?php foreach ($admins as $a):
            $yo = $miId !== null && (int) $a->id === (int) $miId;
            $nombre = trim(($a->nombre ?? '') . ' ' . ($a->apellido ?? ''));
            $ult = $acceso($a->ultimo_acceso ?? null); ?>
          <tr>
            <td class="t-tit"><div class="t-nombre"><b><?= $e($nombre) ?><?php if ($yo): ?> <span class="chip">Tú</span><?php endif; ?></b><span><?= $e($a->correo) ?></span><span class="si-estrecho">Último acceso: <?= $e($ult) ?></span></div></td>
            <td data-label="Rol"><span class="rol-chip <?= $claseRol[$a->id_rol] ?? '' ?>"><?= $e($nombreRol[$a->id_rol] ?? 'Sin rol') ?></span></td>
            <td data-label="Último acceso" class="c-media"><?= $e($ult) ?></td>
            <td data-label="Cuenta">
              <?php if ($yo): ?>
                <span class="muted">Tu cuenta</span>
              <?php else: ?>
                <label class="interruptor"><input type="checkbox" data-admin-estado="<?= (int) $a->id ?>" <?= $a->estado == 1 ? 'checked' : '' ?>><span><?= $a->estado == 1 ? 'Activa' : 'Desactivada' ?></span></label>
              <?php endif; ?>
            </td>
            <td class="t-acc"><div class="acc">
              <?php if (!$yo): ?>
                <button type="button" class="ic-btn" data-abrir-modal="modal-admin" data-registro="<?= $e(json_encode(['id' => (int) $a->id, 'nombre' => $a->nombre, 'apellido' => $a->apellido ?? '', 'correo' => $a->correo, 'id_rol' => (int) $a->id_rol], JSON_UNESCAPED_UNICODE)) ?>" aria-label="Editar a <?= $e($nombre) ?>"><svg class="i"><use href="#i-editar"/></svg></button>
              <?php endif; ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (!$admins): ?><div class="vacio-filtro">Todavía no hay cuentas del personal. Crea la primera con "Nueva cuenta".</div><?php endif; ?>
  </section>
  <p class="muted">Una cuenta desactivada no puede entrar al panel, pero sus ventas y movimientos se conservan.</p>
</section>

<dialog class="modal" id="modal-admin" aria-labelledby="ma-titulo">
  <form method="POST" action="index.php?action=guardar_nuevo_admin" id="form-admin" data-crear="index.php?action=guardar_nuevo_admin" data-editar="index.php?action=actualizar_admin">
    <input type="hidden" name="id">
    <header class="modal-cab"><span class="modal-ic"><svg class="i"><use href="#i-escudo"/></svg></span><div><h3 id="ma-titulo" data-si-crea="Nueva cuenta" data-si-edita="Editar cuenta">Nueva cuenta</h3><p>La persona entra al panel con este correo.</p></div><button type="button" class="ic-btn" data-cerrar aria-label="Cerrar"><svg class="i"><use href="#i-x"/></svg></button></header>
    <div class="modal-cuerpo form-grid">
      <div class="campo"><label for="ma-nombre">Nombre <span class="ob">*</span></label><input id="ma-nombre" name="nombre" required maxlength="100" autocomplete="off"></div>
      <div class="campo"><label for="ma-apellido">Apellido</label><input id="ma-apellido" name="apellido" maxlength="50" autocomplete="off"></div>
      <div class="campo"><label for="ma-correo">Correo <span class="ob">*</span></label><input id="ma-correo" name="correo" type="email" required maxlength="100" autocomplete="off"></div>
      <div class="campo"><label for="ma-rol">Rol <span class="ob">*</span></label>
        <select id="ma-rol" name="id_rol" required>
          <?php foreach ($roles ?: [(object) ['id' => 2, 'nombre_rol' => 'Administrador'], (object) ['id' => 4, 'nombre_rol' => 'Vendedor'], (object) ['id' => 5, 'nombre_rol' => 'Cajero']] as $r):
              if ((int) $r->id === 1 || (int) $r->id === 3) continue; ?>
            <option value="<?= (int) $r->id ?>"><?= $e($r->nombre_rol) ?></option>
          <?php endforeach; ?>
        </select>
        <a href="index.php?action=roles_permisos" class="enlace ayuda">Ver qué puede hacer cada rol</a>
      </div>
      <div class="campo ancho"><label for="ma-clave"><span data-si-crea="Contraseña" data-si-edita="Nueva contraseña">Contraseña</span> <span class="ob" id="ma-clave-ob">*</span></label>
        <div class="clave-caja"><input id="ma-clave" name="clave" type="password" minlength="8" autocomplete="new-password" required><button type="button" class="ver-clave" data-ver-clave="ma-clave" aria-label="Mostrar contraseña" aria-pressed="false"><svg class="i"><use href="#i-ojo"/></svg></button></div>
        <span class="ayuda" data-si-crea="Mínimo 8 caracteres." data-si-edita="Déjala vacía para no cambiarla.">Mínimo 8 caracteres.</span>
      </div>
    </div>
    <footer class="modal-pie">
      <button type="button" class="btn btn-sec" data-cerrar>Cancelar</button>
      <button type="submit" class="btn btn-pri" data-si-crea="Crear cuenta" data-si-edita="Guardar cambios">Crear cuenta</button>
    </footer>
  </form>
</dialog>
<?php include __DIR__ . '/admin/_cerrar.php'; ?>
