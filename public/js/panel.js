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
    avisar: function (txt, tipo, segundos) { avisar(txt, tipo, segundos); },
    confirmar: function (op) { return confirmar(op); },
    datos: function (id) { var el = document.getElementById(id); return el ? JSON.parse(el.textContent) : null; }
  };

  var ICONO = { ok: 'check', error: 'error', aviso: 'alerta', info: 'info' };
  var TITULO = { ok: 'Listo', error: 'No se pudo', aviso: 'Atención', info: 'Aviso' };
  var pila = document.createElement('section');
  pila.className = 'toasts'; pila.setAttribute('aria-live', 'polite'); pila.setAttribute('aria-label', 'Avisos');
  document.body.appendChild(pila);

  function avisar(txt, tipo, segundos) {
    tipo = ICONO[tipo] ? tipo : 'info';
    var total = segundos || (tipo === 'error' ? 6 : 4), queda = total * 1000, ultimo = Date.now(), pausa = false, reloj;
    var t = document.createElement('div');
    t.className = 'toast-item ' + tipo; t.setAttribute('role', tipo === 'error' ? 'alert' : 'status');
    t.innerHTML = '<span class="toast-ic"><svg class="i"><use href="#i-' + ICONO[tipo] + '"/></svg></span>' +
      '<div class="toast-txt"><b>' + TITULO[tipo] + '</b><span></span></div>' +
      '<span class="toast-seg" aria-hidden="true">' + total + 's</span>' +
      '<button type="button" class="toast-x" aria-label="Cerrar aviso"><svg class="i"><use href="#i-x"/></svg></button>' +
      '<i class="toast-barra" style="animation-duration:' + total + 's"></i>';
    t.querySelector('.toast-txt span').textContent = txt;
    var cerrar = function () { clearInterval(reloj); t.classList.add('sale'); setTimeout(function () { t.remove(); }, 200); };
    t.querySelector('.toast-x').addEventListener('click', cerrar);
    t.addEventListener('mouseenter', function () { pausa = true; t.classList.add('pausa'); });
    t.addEventListener('mouseleave', function () { pausa = false; ultimo = Date.now(); t.classList.remove('pausa'); });
    reloj = setInterval(function () {
      var ahora = Date.now();
      if (!pausa) queda -= ahora - ultimo;
      ultimo = ahora;
      t.querySelector('.toast-seg').textContent = Math.max(1, Math.ceil(queda / 1000)) + 's';
      if (queda <= 0) cerrar();
    }, 200);
    pila.appendChild(t);
    while (pila.children.length > 3) pila.firstChild.remove();
  }

  var dlg = document.createElement('dialog');
  dlg.className = 'modal chico modal-confirmar';
  dlg.innerHTML = '<div class="mc-cuerpo"><span class="mc-ic"><svg class="i"><use href="#i-alerta"/></svg></span><div><h3 id="mc-titulo"></h3><p id="mc-texto"></p></div></div>' +
    '<footer class="modal-pie"><button type="button" class="btn btn-sec" data-mc="no">Cancelar</button><button type="button" class="btn" data-mc="si"></button></footer>';
  dlg.setAttribute('aria-labelledby', 'mc-titulo'); dlg.setAttribute('aria-describedby', 'mc-texto');
  document.body.appendChild(dlg);
  var resolver = null;
  function confirmar(op) {
    op = op || {};
    var peligro = op.tipo !== 'aviso';
    dlg.classList.toggle('peligro', peligro);
    dlg.querySelector('.mc-ic use').setAttribute('href', '#i-' + (peligro ? 'basura' : 'alerta'));
    dlg.querySelector('#mc-titulo').textContent = op.titulo || '¿Seguro?';
    dlg.querySelector('#mc-texto').textContent = op.texto || 'Esta acción no se puede deshacer.';
    var si = dlg.querySelector('[data-mc="si"]');
    si.textContent = op.aceptar || 'Aceptar'; si.className = 'btn ' + (peligro ? 'btn-peligro-lleno' : 'btn-pri');
    dlg.showModal(); dlg.querySelector('[data-mc="no"]').focus();
    return new Promise(function (ok) { resolver = ok; });
  }
  dlg.addEventListener('click', function (e) {
    var b = e.target.closest('[data-mc]');
    if (b) { dlg.close(b.getAttribute('data-mc')); return; }
    if (e.target === dlg) dlg.close('no');
  });
  dlg.addEventListener('close', function () { if (resolver) { resolver(dlg.returnValue === 'si'); resolver = null; } dlg.returnValue = ''; });

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

    var pide = e.target.closest('[data-confirmar]');
    if (pide && pide.dataset.confirmado !== '1') {
      e.preventDefault();
      confirmar({ titulo: pide.getAttribute('data-confirmar'), texto: pide.getAttribute('data-confirmar-texto'), aceptar: pide.getAttribute('data-confirmar-boton'), tipo: pide.getAttribute('data-confirmar-tipo') }).then(function (si) {
        if (!si) return;
        if (pide.tagName === 'A') { location.href = pide.href; return; }
        var form = pide.form || pide.closest('form');
        if (form) { pide.dataset.confirmado = '1'; form.requestSubmit(pide.type === 'submit' ? pide : undefined); delete pide.dataset.confirmado; }
      });
      return;
    }

    var copiar = e.target.closest('[data-copiar]');
    if (copiar) {
      var texto = copiar.getAttribute('data-copiar');
      var listo = function () { copiar.textContent = 'Copiada'; setTimeout(function () { copiar.textContent = 'Copiar'; }, 1800); };
      if (navigator.clipboard) navigator.clipboard.writeText(texto).then(listo, function () { Panel.avisar('Selecciona el texto y cópialo con Ctrl+C', 'aviso'); });
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
  if (aviso) Panel.avisar(aviso, panel.getAttribute('data-aviso-tipo') || 'ok');
})();
