<?php
$menu = 'categorias';
$tituloPagina = 'Categorías';
$scripts = ['categorias'];
$categorias = $categorias ?? [];
include __DIR__ . '/../admin/_abrir.php';
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Inventario</small><h2>Categorías</h2></div>
    <div class="cab-acc"><button type="button" class="btn btn-pri" data-categoria><svg class="i"><use href="#i-mas"/></svg>Nueva categoría</button></div>
  </header>

  <section class="tabla-caja" aria-label="Listado de categorías">
    <div class="tabla-scroll">
      <table class="tabla-resp">
        <thead><tr><th>Categoría</th><th>Atributos</th><th class="num">Productos</th><th class="num c-media">Unidades en stock</th><th class="num">Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($categorias as $c):
            $atr = array_map(fn($a) => ['id' => (int) $a->id, 'nombre' => $a->nombre, 'opciones' => $a->opciones ?? ''], $c->atributos ?? []);
            $productos = (int) ($c->total_productos ?? 0); ?>
          <tr>
            <td class="t-tit"><b><?= $e($c->nombre_categoria) ?></b></td>
            <td data-label="Atributos"><?php if ($atr): ?><span class="atr-chips"><?php foreach ($atr as $a): ?><span class="chip" title="<?= $e($a['opciones'] ?: 'Texto libre') ?>"><?= $e($a['nombre']) ?></span><?php endforeach; ?></span><?php else: ?><span class="muted">Sin atributos</span><?php endif; ?></td>
            <td class="num" data-label="Productos"><?= $productos ?></td>
            <td class="num c-media" data-label="Unidades en stock"><?= (int) ($c->total_unidades ?? 0) ?></td>
            <td class="t-acc"><div class="acc">
              <button type="button" class="ic-btn" data-categoria="<?= $e(json_encode(['id' => (int) $c->id, 'nombre' => $c->nombre_categoria, 'atributos' => $atr], JSON_UNESCAPED_UNICODE)) ?>" aria-label="Editar <?= $e($c->nombre_categoria) ?>"><svg class="i"><use href="#i-editar"/></svg></button>
              <form method="POST" action="index.php?action=eliminar_categoria">
                <input type="hidden" name="id" value="<?= (int) $c->id ?>">
                <button type="submit" class="ic-btn" aria-label="Eliminar <?= $e($c->nombre_categoria) ?>" <?= $productos ? 'data-bloqueo="No se puede eliminar ' . $e($c->nombre_categoria) . '" data-bloqueo-texto="Tiene ' . $productos . ($productos === 1 ? ' producto' : ' productos') . '. Muévelos a otra categoría antes de eliminarla." data-bloqueo-enlace="index.php?action=listado_productos&amp;categoria=' . (int) $c->id . '"' : 'data-confirmar="¿Eliminar la categoría ' . $e($c->nombre_categoria) . '?" data-confirmar-texto="Esta acción no se puede deshacer." data-confirmar-boton="Eliminar"' ?>><svg class="i"><use href="#i-basura"/></svg></button>
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
    <input type="hidden" name="id" id="mcat-id">
    <header class="modal-cab"><span class="modal-ic"><svg class="i"><use href="#i-etiqueta"/></svg></span><div><h3 id="mcat-titulo">Nueva categoría</h3></div><button type="button" class="ic-btn" data-cerrar aria-label="Cerrar"><svg class="i"><use href="#i-x"/></svg></button></header>
    <div class="modal-cuerpo" style="display:flex;flex-direction:column;gap:16px">
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
    <input type="hidden" name="atributo_id[]">
    <div class="campo"><label>Atributo</label><input name="atributo_nombre[]" maxlength="50" placeholder="Ej.: Talla" required></div>
    <div class="campo"><label>Opciones</label><input name="atributo_opciones[]" placeholder="Ej.: Individual, Matrimonial, Queen"></div>
    <button type="button" class="ic-btn" data-quitar-atributo aria-label="Quitar atributo"><svg class="i"><use href="#i-basura"/></svg></button>
  </li>
</template>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>
