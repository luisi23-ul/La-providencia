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

  function verRol(r) {
    matriz.setAttribute('data-rol', r);
    matriz.querySelectorAll('[data-r]').forEach(function (c) { c.classList.toggle('ver', c.getAttribute('data-r') === r); });
  }
  verRol(matriz.getAttribute('data-rol'));

  document.querySelectorAll('[data-ver-rol]').forEach(function (b) {
    b.addEventListener('click', function () {
      verRol(b.getAttribute('data-ver-rol'));
      document.querySelectorAll('[data-ver-rol]').forEach(function (o) { o.setAttribute('aria-pressed', String(o === b)); });
    });
  });

  window.addEventListener('beforeunload', function (e) {
    if (!guardar.disabled && !form.dataset.enviando) e.preventDefault();
  });
  form.addEventListener('submit', function () { form.dataset.enviando = '1'; });
})();
document.addEventListener('change', function (e) {
  var c = e.target.closest('.rp-casilla'); if (!c) return;
  c.closest('.rp-celda').classList.toggle('cambiada', c.checked !== c.defaultChecked);
});
document.addEventListener('reset', function (e) { if (e.target.id !== 'form-permisos') return; setTimeout(function () { document.querySelectorAll('.rp-celda.cambiada').forEach(function (l) { l.classList.remove('cambiada'); }); }); });
