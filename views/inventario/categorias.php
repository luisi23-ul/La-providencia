<?php
require_once __DIR__ . '/../admin/_claves.php';
$menu = 'categorias';
$tituloPagina = 'Categorías';
$scripts = ['categorias'];
$categorias = sin_relleno($categorias ?? [], 'categoria');
$categoriasPrincipales = array_filter($categorias, fn($c) => empty(ref('categoria.padre', $c)));
include __DIR__ . '/../admin/_abrir.php';
$hijas = [];
foreach ($categorias as $c) if (!empty(ref('categoria.padre', $c))) $hijas[ref('categoria.padre', $c)][] = $c;
$ordenadas = [];
foreach ($categorias as $c) {
    if (!empty(ref('categoria.padre', $c))) continue;
    $ordenadas[] = [$c, false];
    foreach ($hijas[clave('categoria', $c)] ?? [] as $h) $ordenadas[] = [$h, true];
}
$idsPadre = array_map(fn($c) => clave('categoria', $c), array_filter($categorias, fn($c) => empty(ref('categoria.padre', $c))));
foreach ($categorias as $c) if (!empty(ref('categoria.padre', $c)) && !in_array(ref('categoria.padre', $c), $idsPadre, true)) $ordenadas[] = [$c, true];
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Inventario</small><h2>Categorías</h2></div>
    <div class="cab-acc"><button type="button" class="btn btn-pri" data-categoria><svg class="i"><use href="#i-mas"/></svg>Nueva categoría</button></div>
  </header>

  <section class="tabla-caja" aria-label="Listado de categorías">
    <div class="tabla-scroll">
      <table class="tabla-resp tabla-cat">
        <thead><tr><th>Categoría</th><th>Atributos</th><th class="num">Productos</th><th class="num c-media">Unidades en stock</th><th class="num">Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($ordenadas as [$c, $esSub]):
            $vistos = [];
            $atr = [];
            foreach ($c->atributos ?? [] as $a) {
                $clave = mb_strtolower(trim($a->nombre));
                if (isset($vistos[$clave])) continue;
                $vistos[$clave] = true;
                $atr[] = ['id' => clave('atributo', $a), 'nombre' => $a->nombre, 'opciones' => $a->opciones ?? ''];
            }
            $productos = (int) ($c->total_productos ?? 0);
            $unidades = (int) ($c->total_unidades ?? 0);
            $subs = $esSub ? [] : ($hijas[clave('categoria', $c)] ?? []);
            $prodTotal = $productos + array_sum(array_map(fn($h) => (int) ($h->total_productos ?? 0), $subs));
            $uniTotal = $unidades + array_sum(array_map(fn($h) => (int) ($h->total_unidades ?? 0), $subs)); ?>
          <tr class="<?= $esSub ? 'cat-sub' : 'cat-padre' ?>">
            <td class="t-tit">
              <div class="cat-nom">
                <?php if ($esSub): ?><span class="cat-rama" aria-hidden="true"></span><?php else: ?><span class="cat-ic"><svg class="i"><use href="#i-<?= $subs ? 'lista' : 'etiqueta' ?>"/></svg></span><?php endif; ?>
                <div>
                  <b><?= $e($c->nombre_categoria) ?></b>
                  <?php if ($subs): ?><small><?= count($subs) === 1 ? '1 subcategoría' : count($subs) . ' subcategorías' ?></small><?php elseif ($esSub): ?><small class="cat-de">Subcategoría de <?= $e($c->nombre_padre ?? '') ?></small><?php endif; ?>
                </div>
              </div>
            </td>
            <td data-label="Atributos"><?php if ($atr): ?><span class="atr-chips"><?php foreach (array_slice($atr, 0, 4) as $a): ?><span class="chip" title="<?= $e($a['opciones'] ?: 'Texto libre') ?>"><?= $e($a['nombre']) ?></span><?php endforeach; ?><?php if (count($atr) > 4): $resto = array_column(array_slice($atr, 4), 'nombre'); ?><span class="chip cat-mas" title="<?= $e(implode(', ', $resto)) ?>" aria-label="<?= count($resto) ?> más: <?= $e(implode(', ', $resto)) ?>">+<?= count($resto) ?></span><?php endif; ?></span><?php else: ?><span class="muted">Sin atributos</span><?php endif; ?></td>
            <td class="num" data-label="Productos"><?= $prodTotal ?><?php if ($subs && $prodTotal !== $productos): ?><small class="cat-incl">con subcategorías</small><?php endif; ?></td>
            <td class="num c-media" data-label="Unidades en stock"><?= number_format($uniTotal, 0, ',', '.') ?></td>
            <td class="t-acc"><div class="acc">
              <button type="button" class="ic-btn" data-categoria="<?= $e(json_encode(['id' => clave('categoria', $c), 'nombre' => $c->nombre_categoria, 'id_padre' => ref('categoria.padre', $c), 'atributos' => $atr], JSON_UNESCAPED_UNICODE)) ?>" aria-label="Editar <?= $e($c->nombre_categoria) ?>"><svg class="i"><use href="#i-editar"/></svg></button>
              <form method="POST" action="index.php?action=eliminar_categoria">
                <?= ocultos('categoria', $c) ?>
                <button type="submit" class="ic-btn" aria-label="Eliminar <?= $e($c->nombre_categoria) ?>" <?= $productos ? 'data-bloqueo="No se puede eliminar ' . $e($c->nombre_categoria) . '" data-bloqueo-texto="Tiene ' . $productos . ($productos === 1 ? ' producto' : ' productos') . '. Muévelos a otra categoría antes de eliminarla." data-bloqueo-enlace="index.php?action=listado_productos&amp;categoria=' . clave('categoria', $c) . '"' : ($subs ? 'data-bloqueo="No se puede eliminar ' . $e($c->nombre_categoria) . '" data-bloqueo-texto="Tiene ' . (count($subs) === 1 ? '1 subcategoría' : count($subs) . ' subcategorías') . '. Elimínalas o cámbialas de categoría principal antes."' : 'data-confirmar="¿Eliminar la categoría ' . $e($c->nombre_categoria) . '?" data-confirmar-texto="Esta acción no se puede deshacer." data-confirmar-boton="Eliminar"') ?>><svg class="i"><use href="#i-basura"/></svg></button>
              </form>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (!$categorias): ?><div class="vacio-filtro">Todavía no hay categorías. Crea la primera con "Nueva categoría".</div><?php endif; ?>
  </section>
  <p class="muted">Una categoría con productos no se puede eliminar: primero hay que mover sus productos a otra.</p>
</section>

<dialog class="modal" id="modal-categoria" aria-labelledby="mcat-titulo">
  <form method="POST" action="index.php?action=guardar_categoria" id="form-categoria">
    <?= ocultos('categoria', null, 'mcat-id') ?>
    <header class="modal-cab"><span class="modal-ic"><svg class="i"><use href="#i-etiqueta"/></svg></span><div><h3 id="mcat-titulo">Nueva categoría</h3></div><button type="button" class="ic-btn" data-cerrar aria-label="Cerrar"><svg class="i"><use href="#i-x"/></svg></button></header>
    <div class="modal-cuerpo" style="display:flex;flex-direction:column;gap:16px">
      <div class="campo">
        <label for="mcat-padre">Categoría principal (opcional)</label>
        <select name="<?= campo('categoria.padre') ?>" id="mcat-padre" style="width: 100%;">
          <option value="">Ninguna (Es categoría principal)</option>
          <option value="nueva_padre" style="color:#166534; font-weight:bold;">+ Crear nueva categoría padre</option>
          <?php foreach ($categoriasPrincipales as $cp): ?>
            <option value="<?= $e(clave('categoria', $cp)) ?>"><?= $e($cp->nombre_categoria) ?></option>
          <?php endforeach; ?>
        </select>
        <span class="ayuda">Si es una subcategoría (ej. Ropa → Caballeros), selecciona la categoría padre aquí.</span>
      </div>

      <div class="campo" id="campo-nueva-padre" style="display: none; background: #f0fdf4; padding: 10px; border-radius: 8px; border: 1px dashed #86efac;">
        <label for="mcat-nueva-padre" style="color: #166534; font-weight:600;">Nombre de la nueva categoría principal <span class="ob">*</span></label>
        <input id="mcat-nueva-padre" name="nueva_categoria_padre" placeholder="Ej.: Ropa, Ferretería, Calzado" autocomplete="off">
        <span class="ayuda" style="color: #166534;">Se registrará como categoría principal y la categoría de abajo quedará como su subcategoría.</span>
      </div>

      <div class="campo">
        <label for="mcat-nombre">Nombre <span class="ob">*</span></label>
        <input id="mcat-nombre" name="nombre_categoria" required maxlength="100" placeholder="Ej.: Lencería">
      </div>
      <fieldset class="atr-grupo">
        <legend>Atributos</legend>
        <p class="ayuda">Lo que cambia entre productos de esta categoría. Si escribes opciones, se eligen de una lista; si no, se escriben a mano.</p>
        <ul class="atr-lista" id="mcat-atributos"></ul>
        <button type="button" class="btn btn-sec btn-peq" id="mcat-agregar"><svg class="i"><use href="#i-mas"/></svg>Agregar atributo</button>
      </fieldset>
    </div>
    <footer class="modal-pie">
      <button type="button" class="btn btn-sec" data-cerrar>Cancelar</button>
      <button type="submit" class="btn btn-pri" id="mcat-guardar">Guardar categoría</button>
    </footer>
  </form>
</dialog>

<template id="tpl-atributo">
  <li class="atr-fila">
    <input type="hidden" name="<?= campo('atributo') ?>[]">
    <div class="campo"><label>Atributo</label><input name="atributo_nombre[]" maxlength="50" placeholder="Ej.: Talla" required></div>
    <div class="campo"><label>Opciones</label><input name="atributo_opciones[]" placeholder="Ej.: Individual, Matrimonial, Queen"></div>
    <button type="button" class="ic-btn" data-quitar-atributo aria-label="Quitar atributo"><svg class="i"><use href="#i-basura"/></svg></button>
  </li>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var selPadre = document.getElementById('mcat-padre');
  var campoNuevaPadre = document.getElementById('campo-nueva-padre');
  var inpNuevaPadre = document.getElementById('mcat-nueva-padre');

  if (selPadre && campoNuevaPadre) {
    selPadre.addEventListener('change', function () {
      var activo = this.value === 'nueva_padre';
      campoNuevaPadre.style.display = activo ? 'block' : 'none';
      if (inpNuevaPadre) {
        inpNuevaPadre.required = activo;
        if (activo) {
          inpNuevaPadre.focus();
        } else {
          inpNuevaPadre.value = '';
        }
      }
    });
  }

  document.querySelectorAll('[data-categoria]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (campoNuevaPadre) campoNuevaPadre.style.display = 'none';
      if (inpNuevaPadre) {
        inpNuevaPadre.required = false;
        inpNuevaPadre.value = '';
      }
    });
  });
});
</script>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>