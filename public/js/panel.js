(function () {
  var panel = document.getElementById('panel');
  if (!panel) return;
  var TASA = parseFloat(panel.getAttribute('data-tasa')) || 1;
  var fmt = function (v) { return v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };

  window.Panel = {
    tasa: TASA,
    usd: function (v) { return '$' + fmt(+v || 0); },
    bs: function (v) { return fmt((+v || 0) * TASA) + ' Bs'; },
    fmtBs: function (v) { return fmt(+v || 0); },
    esc: function (s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); },
    avisar: function (txt) {
      var t = document.getElementById('toast');
      t.textContent = txt; t.hidden = false;
      clearTimeout(t._t); t._t = setTimeout(function () { t.hidden = true; }, 2600);
    },
    datos: function (id) { var el = document.getElementById(id); return el ? JSON.parse(el.textContent) : null; }
  };

  var abrir = document.getElementById('abrir-menu'), velo = document.getElementById('velo');
  if (abrir) abrir.addEventListener('click', function () { panel.classList.add('abierto'); });
  if (velo) velo.addEventListener('click', function () { panel.classList.remove('abierto'); });

  document.addEventListener('click', function (e) {
    var cerrar = e.target.closest('dialog [data-cerrar]');
    if (cerrar) { cerrar.closest('dialog').close(); return; }

    var abrirModal = e.target.closest('[data-abrir-modal]');
    if (abrirModal) {
      var modal = document.getElementById(abrirModal.getAttribute('data-abrir-modal'));
      var datos = abrirModal.getAttribute('data-registro');
      var form = modal.querySelector('form');
      if (form) form.reset();
      if (datos) {
        datos = JSON.parse(datos);
        Object.keys(datos).forEach(function (k) {
          var campo = modal.querySelector('[name="' + k + '"]');
          if (!campo) return;
          if (campo.type === 'checkbox') campo.checked = !!+datos[k];
          else if (campo.type === 'radio') { var r = modal.querySelector('[name="' + k + '"][value="' + datos[k] + '"]'); if (r) r.checked = true; }
          else campo.value = datos[k];
        });
      }
      modal.querySelectorAll('[data-si-edita]').forEach(function (el) { el.textContent = datos ? el.getAttribute('data-si-edita') : el.getAttribute('data-si-crea'); });
      modal.showModal();
      var primero = modal.querySelector('input:not([type=hidden]), select, textarea');
      if (primero) primero.focus();
      return;
    }

    var confirmar = e.target.closest('[data-confirmar]');
    if (confirmar && !window.confirm(confirmar.getAttribute('data-confirmar'))) { e.preventDefault(); return; }

    var copiar = e.target.closest('[data-copiar]');
    if (copiar) {
      var texto = copiar.getAttribute('data-copiar');
      var listo = function () { copiar.textContent = 'Copiada'; setTimeout(function () { copiar.textContent = 'Copiar'; }, 1800); };
      if (navigator.clipboard) navigator.clipboard.writeText(texto).then(listo, function () { Panel.avisar('Selecciona el texto y cópialo con Ctrl+C'); });
      return;
    }

    var ver = e.target.closest('[data-ver-clave]');
    if (ver) {
      var inp = document.getElementById(ver.getAttribute('data-ver-clave')), mostrar = inp.type === 'password';
      inp.type = mostrar ? 'text' : 'password';
      ver.setAttribute('aria-pressed', String(mostrar));
      ver.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
    }
  });

  document.querySelectorAll('[data-filtro-tabla]').forEach(function (input) {
    var tabla = document.getElementById(input.getAttribute('data-filtro-tabla'));
    var vacio = document.getElementById(input.getAttribute('data-filtro-vacio'));
    input.addEventListener('input', function () {
      var q = input.value.trim().toLowerCase().replace(/[-\s.]/g, ''), visibles = 0;
      tabla.querySelectorAll('tbody tr').forEach(function (tr) {
        var ok = !q || tr.textContent.toLowerCase().replace(/[-\s.]/g, '').indexOf(q) !== -1;
        tr.hidden = !ok; if (ok) visibles++;
      });
      if (vacio) vacio.hidden = visibles > 0;
    });
  });

  var aviso = panel.getAttribute('data-aviso');
  if (aviso) Panel.avisar(aviso);
})();
