<dialog class="modal" id="modal-proveedor" aria-labelledby="mp-titulo">
  <form method="POST" action="index.php?action=guardar_proveedor" id="form-proveedor">
    <?php if (!base_nueva()) echo ocultos('proveedor', null, 'mp-id'); ?>
    <input type="hidden" name="volver" value="<?= $e($_GET['action'] ?? 'proveedores') ?>">
    <header class="modal-cab">
      <span class="modal-ic"><svg class="i"><use href="#i-camion"/></svg></span>
      <div>
        <h3 id="mp-titulo" data-si-edita="Editar proveedor" data-si-crea="Nuevo proveedor">Nuevo proveedor</h3>
        <p>Los campos con * son obligatorios.</p>
      </div>
      <button type="button" class="ic-btn" data-cerrar aria-label="Cerrar"><svg class="i"><use href="#i-x"/></svg></button>
    </header>
    <div class="modal-cuerpo form-grid">
      <div class="campo">
        <label for="mp-rif">RIF o cédula <span class="ob">*</span></label>
        <input id="mp-rif" name="<?= campo('proveedor.rif') ?>"<?= base_nueva() ? ' data-clave-fija' : '' ?> placeholder="J-30876541-2" required autocomplete="off" pattern="[JGVEjgve]-?\d{6,9}(-?\d)?">
        <span class="ayuda">Con letra y guiones: J, G, V o E.</span>
      </div>
      <div class="campo">
        <label for="mp-tel">Teléfono</label>
        <input id="mp-tel" name="telefono" type="tel" inputmode="tel" placeholder="0251-2370145">
      </div>
      <div class="campo ancho">
        <label for="mp-rs">Razón social <span class="ob">*</span></label>
        <input id="mp-rs" name="razon_social" required maxlength="150">
      </div>
      <div class="campo ancho">
        <label for="mp-correo">Correo</label>
        <input id="mp-correo" name="correo" type="email" placeholder="ventas@proveedor.com">
      </div>
      <div class="campo ancho">
        <label for="mp-dir">Dirección</label>
        <textarea id="mp-dir" name="direccion" rows="2" placeholder="Dirección completa"></textarea>
      </div>
    </div>
    <footer class="modal-pie">
      <button type="button" class="btn btn-sec" data-cerrar>Cancelar</button>
      <button type="submit" class="btn btn-pri" id="mp-btn-submit">Guardar proveedor</button>
    </footer>
  </form>
</dialog>

<script>
// Limpiar modal para nuevo proveedor
document.querySelector('[data-abrir-modal="modal-proveedor"]:not([data-registro])')?.addEventListener('click', function() {
  const f = document.getElementById('form-proveedor');
  if (f) {
    f.reset();
    document.getElementById('mp-id').value = '';
    const t = document.getElementById('mp-titulo');
    if (t) t.textContent = t.getAttribute('data-si-crea') || 'Nuevo proveedor';
    const b = document.getElementById('mp-btn-submit');
    if (b) b.textContent = 'Guardar proveedor';
  }
});

// Cargar datos en el modal para editar proveedor existente
document.addEventListener('click', function(e) {
  const btn = e.target.closest('[data-abrir-modal="modal-proveedor"][data-registro]');
  if (btn) {
    try {
      const data = JSON.parse(btn.getAttribute('data-registro'));
      const mid = document.getElementById('mp-id'), mrif = document.getElementById('mp-rif');
      if (mid) mid.value = data[mid.name] || '';
      mrif.value = data[mrif.name] || '';
      mrif.readOnly = mrif.hasAttribute('data-clave-fija');
      document.getElementById('mp-rs').value = data.razon_social || '';
      document.getElementById('mp-tel').value = data.telefono || '';
      document.getElementById('mp-correo').value = data.correo || '';
      document.getElementById('mp-dir').value = data.direccion || '';
      const t = document.getElementById('mp-titulo');
      if (t) t.textContent = t.getAttribute('data-si-edita') || 'Editar proveedor';
      const b = document.getElementById('mp-btn-submit');
      if (b) b.textContent = 'Guardar cambios';
    } catch (err) {}
  }
});
</script>