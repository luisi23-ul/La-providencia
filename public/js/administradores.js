(function () {
  var form = document.getElementById('form-admin');
  if (!form) return;
  var id = form.querySelector('[name="id"]'), clave = document.getElementById('ma-clave'), ob = document.getElementById('ma-clave-ob');

  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-abrir-modal="modal-admin"]');
    if (!b) return;
    var edita = b.hasAttribute('data-registro');
    if (!edita) id.value = '';
    form.action = form.getAttribute(edita ? 'data-editar' : 'data-crear');
    clave.required = !edita;
    clave.value = '';
    ob.hidden = edita;
  });

  document.addEventListener('change', function (e) {
    var t = e.target.closest('[data-admin-estado]');
    if (!t) return;
    t.nextElementSibling.textContent = t.checked ? 'Activa' : 'Desactivada';
    location.href = 'index.php?action=toggle_admin&id=' + encodeURIComponent(t.getAttribute('data-admin-estado')) + '&estado=' + (t.checked ? 1 : 0);
  });
})();
