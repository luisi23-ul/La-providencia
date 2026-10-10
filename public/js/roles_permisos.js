(function () {
  var form = document.getElementById('form-permisos');
  if (!form) return;
  var matriz = document.getElementById('matriz'), resumen = document.getElementById('cambios-perm');
  var guardar = document.getElementById('guardar-perm'), descartar = document.getElementById('descartar-perm');

  function contar() {
    var n = 0;
    form.querySelectorAll('input[type="checkbox"]:not(:disabled)').forEach(function (c) { if (c.checked !== c.defaultChecked) n++; });
    resumen.innerHTML = n ? '<b>' + n + '</b> ' + (n === 1 ? 'cambio' : 'cambios') + ' sin guardar' : 'Sin cambios';
    guardar.disabled = descartar.disabled = !n;
  }

  form.addEventListener('change', contar);
  form.addEventListener('reset', function () { setTimeout(contar); });

  matriz.addEventListener('click', function (e) {
    var td = e.target.closest('td[data-r]');
    if (!td || e.target.matches('input')) return;
    var cb = td.querySelector('input');
    if (cb.disabled) return;
    cb.checked = !cb.checked;
    contar();
  });

  document.querySelectorAll('[data-ver-rol]').forEach(function (b) {
    b.addEventListener('click', function () {
      matriz.setAttribute('data-rol', b.getAttribute('data-ver-rol'));
      document.querySelectorAll('[data-ver-rol]').forEach(function (o) { o.setAttribute('aria-pressed', String(o === b)); });
    });
  });

  window.addEventListener('beforeunload', function (e) {
    if (!guardar.disabled && !form.dataset.enviando) e.preventDefault();
  });
  form.addEventListener('submit', function () { form.dataset.enviando = '1'; });
})();
