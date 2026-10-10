<?php
$menu = 'proveedores';
$tituloPagina = 'Proveedores';
$proveedores = $proveedores ?? [];
include __DIR__ . '/../admin/_abrir.php';
$est = $_GET['estado'] ?? '1';
$activos = count(array_filter($proveedores, fn($p) => $p->estado));
$lista = $est === 'todos' ? $proveedores : array_filter($proveedores, fn($p) => (string) (int) $p->estado === $est);
$tabs = ['1' => ['Activos', $activos], '0' => ['Inactivos', count($proveedores) - $activos], 'todos' => ['Todos', count($proveedores)]];
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Compras</small><h2>Proveedores</h2></div>
    <div class="cab-acc"><button type="button" class="btn btn-pri" data-abrir-modal="modal-proveedor"><svg class="i"><use href="#i-mas"/></svg>Nuevo proveedor</button></div>
  </header>
  <div class="herr">
    <label class="buscar"><svg class="i"><use href="#i-buscar"/></svg><input type="search" placeholder="Buscar por RIF, cédula o razón social" aria-label="Buscar proveedor" data-filtro-tabla="tabla-prov" data-filtro-vacio="vacio-prov"></label>
    <div class="seg" role="group" aria-label="Filtrar por estado">
      <?php foreach ($tabs as $k => [$t, $n]): ?><a href="index.php?action=proveedores&estado=<?= $k ?>" class="seg-link" aria-current="<?= $est === (string) $k ? 'page' : 'false' ?>"><?= $t ?><span><?= $n ?></span></a><?php endforeach; ?>
    </div>
  </div>
  <section class="tabla-caja" aria-label="Listado de proveedores">
    <div class="tabla-scroll">
      <table class="tabla-resp" id="tabla-prov">
        <thead><tr><th>RIF / Cédula</th><th>Razón social</th><th>Teléfono</th><th class="c-media">Correo</th><th class="num c-media">Compras</th><th>Estado</th><th class="num">Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($lista as $p): ?>
          <tr>
            <td class="cod" data-label="RIF / Cédula"><?= $e($p->rif_cedula) ?></td>
            <td class="t-tit"><div class="t-nombre"><b><?= $e($p->razon_social) ?></b><?php if (!empty($p->direccion)): ?><span><?= $e($p->direccion) ?></span><?php endif; ?></div></td>
            <td data-label="Teléfono"><?= $e($p->telefono ?: 'Sin teléfono') ?></td>
            <td data-label="Correo" class="c-media"><?= $e($p->correo ?: 'Sin correo') ?></td>
            <td class="num c-media" data-label="Compras"><?= (int) ($p->total_compras ?? 0) ?></td>
            <td data-label="Estado"><span class="est <?= $p->estado ? 'ok' : 'agot' ?>"><?= $p->estado ? 'Activo' : 'Inactivo' ?></span></td>
            <td class="t-acc"><div class="acc">
              <button type="button" class="ic-btn" data-abrir-modal="modal-proveedor" data-registro="<?= $e(json_encode(['id' => $p->id, 'rif_cedula' => $p->rif_cedula, 'razon_social' => $p->razon_social, 'telefono' => $p->telefono, 'correo' => $p->correo, 'direccion' => $p->direccion], JSON_UNESCAPED_UNICODE)) ?>" aria-label="Editar <?= $e($p->razon_social) ?>"><svg class="i"><use href="#i-editar"/></svg></button>
              <form method="POST" action="index.php?action=cambiar_estado_proveedor">
                <input type="hidden" name="id" value="<?= (int) $p->id ?>"><input type="hidden" name="estado" value="<?= $p->estado ? 0 : 1 ?>">
                <button type="submit" class="ic-btn" aria-label="<?= $p->estado ? 'Desactivar' : 'Activar' ?> <?= $e($p->razon_social) ?>" <?= $p->estado ? 'data-confirmar="¿Desactivar a ' . $e($p->razon_social) . '?" data-confirmar-texto="Ya no saldrá al registrar compras. Puedes activarlo de nuevo cuando quieras." data-confirmar-boton="Desactivar" data-confirmar-tipo="aviso"' : '' ?>><svg class="i"><use href="#i-power"/></svg></button>
              </form>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="vacio-filtro" id="vacio-prov" <?= $lista ? 'hidden' : '' ?>>Ningún proveedor coincide con la búsqueda.</div>
  </section>
</section>
<?php include __DIR__ . '/_modal_proveedor.php'; ?>
<?php include __DIR__ . '/../admin/_cerrar.php'; ?>
