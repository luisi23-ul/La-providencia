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
(function () {
  var inp = document.getElementById('cfg-logo'); if (!inp) return;
  inp.addEventListener('change', function () {
    var f = inp.files && inp.files[0]; if (!f) return;
    document.getElementById('cfg-logo-nombre').textContent = f.name;
    document.getElementById('cfg-logo-prev').innerHTML = '<img src="' + URL.createObjectURL(f) + '" alt="Logo nuevo">';
  });
})();
(function () {
  var prev = document.getElementById('cfg-prev'); if (!prev) return;
  document.querySelectorAll('[name="paleta_tienda"]').forEach(function (r) {
    r.addEventListener('change', function () {
      document.getElementById('cfg-cp').value = r.dataset.p;
      document.getElementById('cfg-cf').value = r.dataset.f;
      document.getElementById('cfg-ct').value = r.dataset.t;
      prev.style.setProperty('--p', r.dataset.p); prev.style.setProperty('--f', r.dataset.f); prev.style.setProperty('--t', r.dataset.t);
    });
  });
})();
(function () {
  var esc = function (s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
  var val = function (id) { var el = document.getElementById(id); return el ? (el.value.trim() || el.placeholder) : ''; };
  function textos() {
    var t = val('cfg-hero').split(/\s+/), ult = t.pop();
    document.querySelectorAll('[data-vp="titulo"]').forEach(function (b) { b.innerHTML = esc(t.join(' ')) + ' <em>' + esc(ult) + '</em>'; });
    document.querySelectorAll('[data-vp="sub"]').forEach(function (s) { s.textContent = val('cfg-sub'); });
    document.querySelectorAll('[data-vp="pie"]').forEach(function (s) { s.textContent = val('cfg-pie'); });
    var n = (document.getElementById('cfg-nombre').value.trim() || 'Aura Boutique').toUpperCase().split(/\s+/);
    document.querySelectorAll('[data-vp="logo"]').forEach(function (l) { l.innerHTML = esc(n[0]) + '<small>' + esc(n.slice(1).join(' ')) + '</small>'; });
  }
  ['cfg-hero', 'cfg-sub', 'cfg-pie', 'cfg-nombre'].forEach(function (id) { var el = document.getElementById(id); if (el) el.addEventListener('input', textos); });
})();
