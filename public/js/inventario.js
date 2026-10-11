(function () {
  var form = document.getElementById('form-producto');
  if (!form) return;
  var D = (window.Panel && window.Panel.datos) ? Panel.datos('datos-atributos') : null;
  if (!D) {
    var tag = document.getElementById('datos-atributos');
    D = tag ? JSON.parse(tag.textContent) : { categorias: [], valores: {} };
  }

  var cat = document.getElementById('fp-cat'), caja = document.getElementById('fp-atributos-caja'), cont = document.getElementById('fp-atributos');
  var costo = document.getElementById('fp-costo'), pct = document.getElementById('fp-margen-pct'), precio = document.getElementById('fp-precio');
  var nota = document.getElementById('fp-margen'), enBs = document.getElementById('fp-precio-bs');

  function parseOps(ops) {
    if (Array.isArray(ops)) return ops;
    if (typeof ops === 'string' && ops.trim() !== '') {
      return ops.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
    }
    return [];
  }

  function esc(s) {
    if (window.Panel && window.Panel.esc) return Panel.esc(s);
    return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function atributos() {
    if (!cat || !caja || !cont) return;
    var c = D.categorias.find(function (x) { return String(x.id) === String(cat.value); });
    var lista = c ? (c.atributos || []) : [];
    caja.hidden = !lista.length;
    
    cont.innerHTML = lista.map(function (a) {
      var v = (D.valores && D.valores[a.id]) ? String(D.valores[a.id]) : '';
      var id = 'fp-atr-' + a.id, nombre = 'atributo[' + a.id + ']';
      var ops = parseOps(a.opciones);

      var control = ops.length
        ? '<select id="' + id + '" name="' + nombre + '" style="width:100%;"><option value="">Sin indicar</option>' + ops.map(function (o) { return '<option value="' + esc(o) + '"' + (String(o).toLowerCase() === v.toLowerCase() ? ' selected' : '') + '>' + esc(o) + '</option>'; }).join('') + '</select>'
        : '<input id="' + id + '" name="' + nombre + '" value="' + esc(v) + '" placeholder="Ej.: ' + esc(a.nombre) + '">';
      return '<div class="campo"><label for="' + id + '">' + esc(a.nombre) + '</label>' + control + '</div>';
    }).join('');
  }

  function resumen() {
    var c = parseFloat(costo.value) || 0, p = parseFloat(precio.value) || 0;
    if (enBs && window.Panel && Panel.fmtBs && Panel.tasa) {
      enBs.textContent = p ? '= ' + Panel.fmtBs(p * Panel.tasa) + ' Bs' : '';
    }
    if (c && p && nota) {
      var g = p - c;
      var txtG = (window.Panel && Panel.usd) ? Panel.usd(g) : ('$' + g.toFixed(2));
      nota.textContent = 'Ganas ' + txtG + ' por unidad (' + Math.round(g / c * 100) + '% sobre el costo).';
      nota.className = 'fp-margen ancho' + (g < 0 ? ' neg' : '');
    } else if (nota) {
      nota.textContent = '';
    }
  }

  function desdeMargen() {
    var c = parseFloat(costo.value), m = parseFloat(pct.value);
    if (c > 0 && m >= 0) precio.value = (c * (1 + m / 100)).toFixed(2);
    resumen();
  }

  function desdePrecio() {
    var c = parseFloat(costo.value), p = parseFloat(precio.value);
    pct.value = c > 0 && p > 0 ? Math.round((p - c) / c * 100) : '';
    resumen();
  }

  cat.addEventListener('change', atributos);
  costo.addEventListener('input', function () { pct.value ? desdeMargen() : desdePrecio(); });
  pct.addEventListener('input', desdeMargen);
  precio.addEventListener('input', desdePrecio);

  var inpImg = document.getElementById('fp-img');
  if (inpImg) {
    inpImg.addEventListener('change', function () {
      var f = this.files[0], marco = document.getElementById('fp-foto');
      if (!f || !marco) return;
      marco.innerHTML = '<img src="' + URL.createObjectURL(f) + '" alt="Foto del producto">';
      marco.classList.add('con-foto');
    });
  }

  atributos();
  desdePrecio();
})();