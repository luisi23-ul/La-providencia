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
  <div class="filtros">
    <label class="buscar"><svg class="i"><use href="#i-buscar"/></svg><input type="search" placeholder="Buscar por RIF, cédula o razón social" aria-label="Buscar proveedor" data-filtro-tabla="tabla-prov" data-filtro-vacio="vacio-prov"></label>
    <nav class="f-grupo" aria-label="Filtrar por estado">
      <?php foreach ($tabs as $k => [$t, $n]): ?><a href="index.php?action=proveedores&estado=<?= $k ?>" class="f-op" aria-current="<?= $est === (string) $k ? 'page' : 'false' ?>"><?= $t ?><span class="f-cuenta"><?= $n ?></span></a><?php endforeach; ?>
    </nav>
  </div>
  <section class="tabla-caja" aria-label="Listado de proveedores">
    <div class="tabla-scroll">
      <table class="tabla-resp" id="tabla-prov" data-paginar="10">
        <thead>
          <tr>
            <th>RIF / Cédula</th>
            <th>Razón social</th>
            <th>Dirección</th>
            <th>Teléfono</th>
            <th class="c-media">Correo</th>
            <th class="num c-media">Compras</th>
            <th>Estado</th>
            <th class="num">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($lista as $p): ?>
          <tr>
            <td class="cod" data-label="RIF / Cédula"><?= $e(ref('proveedor.rif', $p)) ?></td>
            <td class="t-tit" data-label="Razón social"><b><?= $e($p->razon_social) ?></b></td>
            <td data-label="Dirección"><?= $e($p->direccion ?: 'Sin dirección') ?></td>
            <td data-label="Teléfono"><?= $e($p->telefono ?: 'Sin teléfono') ?></td>
            <td data-label="Correo" class="c-media"><?= $e($p->correo ?: 'Sin correo') ?></td>
            <td class="num c-media" data-label="Compras"><?= (int) ($p->total_compras ?? 0) ?></td>
            <td data-label="Estado"><span class="est <?= $p->estado ? 'ok' : 'agot' ?>"><?= $p->estado ? 'Activo' : 'Inactivo' ?></span></td>
            <td class="t-acc"><div class="acc">
              <button type="button" class="ic-btn" data-abrir-modal="modal-proveedor" data-registro="<?= $e(json_encode((base_nueva() ? [] : registro_clave('proveedor', $p)) + [campo('proveedor.rif') => ref('proveedor.rif', $p), 'razon_social' => $p->razon_social, 'telefono' => $p->telefono, 'correo' => $p->correo, 'direccion' => $p->direccion], JSON_UNESCAPED_UNICODE)) ?>" aria-label="Editar <?= $e($p->razon_social) ?>"><svg class="i"><use href="#i-editar"/></svg></button>
              <form method="POST" action="index.php?action=cambiar_estado_proveedor">
                <?= ocultos('proveedor', $p) ?><input type="hidden" name="estado" value="<?= $p->estado ? 0 : 1 ?>">
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