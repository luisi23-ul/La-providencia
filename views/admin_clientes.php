<?php
$menu = 'lista_clientes';
$tituloPagina = 'Clientes';
$clientes = $clientes ?? [];
include __DIR__ . '/admin/_abrir.php';
$est = $_GET['estado'] ?? '1';
$activos = count(array_filter($clientes, fn($c) => $c->estado == 1));
$lista = $est === 'todos' ? $clientes : array_filter($clientes, fn($c) => (string) (int) $c->estado === $est);
$tabs = ['1' => ['Activos', $activos], '0' => ['Desactivados', count($clientes) - $activos], 'todos' => ['Todos', count($clientes)]];
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Gestión</small><h2>Clientes</h2></div>
    <div class="cab-acc"><button type="button" class="btn btn-pri" data-abrir-modal="modal-cliente"><svg class="i"><use href="#i-mas"/></svg>Nuevo cliente</button></div>
  </header>
  <div class="herr">
    <label class="buscar"><svg class="i"><use href="#i-buscar"/></svg><input type="search" placeholder="Buscar por nombre, cédula o teléfono" aria-label="Buscar cliente" data-filtro-tabla="tabla-cli" data-filtro-vacio="vacio-cli"></label>
    <div class="seg" role="group" aria-label="Estado">
      <?php foreach ($tabs as $k => [$t, $n]): ?><a href="index.php?action=lista_clientes&estado=<?= $k ?>" class="seg-link" aria-current="<?= $est === (string) $k ? 'page' : 'false' ?>"><?= $t ?><span><?= $n ?></span></a><?php endforeach; ?>
    </div>
  </div>
  <section class="tabla-caja" aria-label="Listado de clientes">
    <div class="tabla-scroll">
      <table class="tabla-resp" id="tabla-cli">
        <thead><tr><th>Cliente</th><th>Teléfono</th><th class="c-media">Correo</th><th class="num">Compras</th><th>Cuenta</th><th class="num"></th></tr></thead>
        <tbody>
        <?php foreach ($lista as $c):
            $nombre = trim(($c->nombre ?? '') . ' ' . ($c->apellido ?? '')) ?: 'Sin nombre';
            $sub = implode(', ', array_filter([$c->cedula ?? '', $c->direccion ?? ''])); ?>
          <tr>
            <td class="t-tit"><div class="t-nombre"><b><?= $e($nombre) ?></b><?php if ($sub): ?><span><?= $e($sub) ?></span><?php endif; ?><?php if (!empty($c->correo)): ?><span class="si-estrecho"><?= $e($c->correo) ?></span><?php endif; ?></div></td>
            <td data-label="Teléfono" class="tel-cli"><?= $e(!empty($c->telefono) ? $c->telefono : 'Sin teléfono') ?></td>
            <td data-label="Correo" class="c-media"><?= $e(!empty($c->correo) ? $c->correo : 'Sin correo') ?></td>
            <td class="num" data-label="Compras"><b><?= (int) ($c->compras ?? 0) ?></b><?php if (!empty($c->total_compras)): ?><br><span class="muted"><?= $usd($c->total_compras) ?></span><?php endif; ?></td>
            <td data-label="Cuenta">
              <label class="interruptor"><input type="checkbox" <?= $c->estado == 1 ? 'checked' : '' ?> onchange="location.href='index.php?action=cambiarEstadoCliente&id=<?= (int) $c->id ?>&estado=' + (this.checked ? 1 : 0)" aria-label="Cuenta de <?= $e($nombre) ?>"><span><?= $c->estado == 1 ? 'Activa' : 'Desactivada' ?></span></label>
            </td>
            <td class="t-acc"><div class="acc">
              <?php if (!empty($c->telefono)): ?><a href="https://wa.me/58<?= $e(ltrim(preg_replace('/\D/', '', $c->telefono), '0')) ?>" target="_blank" rel="noopener" class="ic-btn" aria-label="Escribir a <?= $e($nombre) ?> por WhatsApp"><svg class="i"><use href="#i-wa"/></svg></a><?php endif; ?>
              <button type="button" class="ic-btn" data-abrir-modal="modal-cliente" data-registro="<?= $e(json_encode(['id' => $c->id, 'cedula' => $c->cedula ?? '', 'nombre' => $c->nombre ?? '', 'apellido' => $c->apellido ?? '', 'telefono' => $c->telefono ?? '', 'correo' => $c->correo ?? '', 'direccion' => $c->direccion ?? ''], JSON_UNESCAPED_UNICODE)) ?>" aria-label="Editar <?= $e($nombre) ?>"><svg class="i"><use href="#i-editar"/></svg></button>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="vacio-filtro" id="vacio-cli" <?= $lista ? 'hidden' : '' ?>>Ningún cliente coincide con la búsqueda.</div>
  </section>
  <p class="muted">Desactivar una cuenta impide que el cliente entre a la tienda online. Sus compras se conservan.</p>
</section>

<dialog class="modal" id="modal-cliente" aria-labelledby="mcl-titulo">
  <form method="POST" action="index.php?action=guardar_cliente">
    <input type="hidden" name="id">
    <header class="modal-cab"><div><h3 id="mcl-titulo" data-si-edita="Editar cliente" data-si-crea="Nuevo cliente">Nuevo cliente</h3><p>Los campos con * son obligatorios.</p></div><button type="button" class="ic-btn" data-cerrar aria-label="Cerrar"><svg class="i"><use href="#i-x"/></svg></button></header>
    <div class="modal-cuerpo form-grid">
      <div class="campo"><label for="mcl-ced">Cédula o RIF <span class="ob">*</span></label><input id="mcl-ced" name="cedula" required placeholder="V-12345678" autocomplete="off"></div>
      <div class="campo"><label for="mcl-tel">Teléfono</label><input id="mcl-tel" name="telefono" type="tel" inputmode="tel" placeholder="0414-5550123"></div>
      <div class="campo"><label for="mcl-nom">Nombre <span class="ob">*</span></label><input id="mcl-nom" name="nombre" required></div>
      <div class="campo"><label for="mcl-ape">Apellido</label><input id="mcl-ape" name="apellido"></div>
      <div class="campo ancho"><label for="mcl-correo">Correo</label><input id="mcl-correo" name="correo" type="email"><span class="ayuda">Solo si va a comprar en la tienda online.</span></div>
      <div class="campo ancho"><label for="mcl-dir">Dirección</label><textarea id="mcl-dir" name="direccion" rows="2"></textarea></div>
    </div>
    <footer class="modal-pie"><button type="button" class="btn btn-sec" data-cerrar>Cancelar</button><button type="submit" class="btn btn-pri">Guardar cliente</button></footer>
  </form>
</dialog>
<?php include __DIR__ . '/admin/_cerrar.php'; ?>
