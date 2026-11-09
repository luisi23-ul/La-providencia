(function () {
  function poner(enlace, clave, valor) {
    var u = new URL(enlace, location.href);
    if (valor) u.searchParams.set(clave, valor); else u.searchParams.delete(clave);
    return u.pathname.split('/').pop() + u.search;
  }

  function revisar(fila) {
    var clave = fila.getAttribute('data-requiere');
    if (!clave) return;
    var falta = !fila.querySelector('select[name="' + clave + '"]').value;
    fila.classList.toggle('rp-espera', falta);
    fila.querySelectorAll('.btn-rep').forEach(function (a) {
      if (falta) { a.setAttribute('aria-disabled', 'true'); a.tabIndex = -1; }
      else { a.removeAttribute('aria-disabled'); a.removeAttribute('tabindex'); }
    });
  }

  document.querySelectorAll('select[data-rp-filtro]').forEach(function (sel) {
    var fila = sel.closest('tr');
    sel.addEventListener('change', function () {
      var param = sel.getAttribute('data-param') || sel.name;
      fila.querySelectorAll('.btn-rep').forEach(function (a) { a.setAttribute('href', poner(a.getAttribute('href'), param, sel.value)); });
      document.querySelectorAll('.filtros a.f-op').forEach(function (a) { a.setAttribute('href', poner(a.getAttribute('href'), sel.name, sel.value)); });
      history.replaceState(null, '', poner(location.href, sel.name, sel.value));
      sel.closest('label').classList.toggle('activo', !!sel.value);
      revisar(fila);
    });
    revisar(fila);
  });

  document.addEventListener('click', function (e) {
    if (e.target.closest('.btn-rep[aria-disabled="true"]')) e.preventDefault();
  });
})();
