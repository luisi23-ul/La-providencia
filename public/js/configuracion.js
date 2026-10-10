(function () {
  var form = document.getElementById('form-config');
  if (!form) return;
  var estado = document.getElementById('cfg-estado'), guardar = document.getElementById('cfg-guardar'), tasa = document.getElementById('cfg-tasa-manual');

  function cambio() {
    estado.innerHTML = '<b>Cambios sin guardar</b>';
    guardar.disabled = false;
    tasa.hidden = form.querySelector('input[name="tasa_modo"]:checked').value !== 'manual';
  }

  form.addEventListener('input', cambio);
  form.addEventListener('change', cambio);
  form.addEventListener('submit', function () { form.dataset.enviando = '1'; guardar.disabled = true; guardar.textContent = 'Guardando…'; });
  window.addEventListener('beforeunload', function (e) {
    if (!guardar.disabled && !form.dataset.enviando) e.preventDefault();
  });
})();
