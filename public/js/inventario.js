(function () {
  var form = document.getElementById('form-producto');
  if (!form) return;
  var D = Panel.datos('datos-atributos') || { categorias: [], valores: {} };
  var cat = document.getElementById('fp-cat'), caja = document.getElementById('fp-atributos-caja'), cont = document.getElementById('fp-atributos');
  var costo = document.getElementById('fp-costo'), pct = document.getElementById('fp-margen-pct'), precio = document.getElementById('fp-precio');
  var nota = document.getElementById('fp-margen'), enBs = document.getElementById('fp-precio-bs');

  function atributos() {
    var c = D.categorias.find(function (x) { return String(x.id) === cat.value; });
    var lista = c ? c.atributos : [];
    caja.hidden = !lista.length;
    cont.innerHTML = lista.map(function (a) {
      var v = D.valores[a.id] || '', id = 'fp-atr-' + a.id, nombre = 'atributo[' + a.id + ']';
      var control = a.opciones.length
        ? '<select id="' + id + '" name="' + nombre + '"><option value="">Sin indicar</option>' + a.opciones.map(function (o) { return '<option' + (o === v ? ' selected' : '') + '>' + Panel.esc(o) + '</option>'; }).join('') + '</select>'
        : '<input id="' + id + '" name="' + nombre + '" value="' + Panel.esc(v) + '">';
      return '<div class="campo"><label for="' + id + '">' + Panel.esc(a.nombre) + '</label>' + control + '</div>';
    }).join('');
  }

  function resumen() {
    var c = parseFloat(costo.value) || 0, p = parseFloat(precio.value) || 0;
    enBs.textContent = p ? '= ' + Panel.fmtBs(p * Panel.tasa) + ' Bs' : '';
    if (c && p) {
      var g = p - c;
      nota.textContent = 'Ganas ' + Panel.usd(g) + ' por unidad (' + Math.round(g / c * 100) + '% sobre el costo).';
      nota.className = 'fp-margen ancho' + (g < 0 ? ' neg' : '');
    } else nota.textContent = '';
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

  document.getElementById('fp-img').addEventListener('change', function () {
    var f = this.files[0], marco = document.getElementById('fp-foto');
    if (!f) return;
    marco.innerHTML = '<img src="' + URL.createObjectURL(f) + '" alt="Foto del producto">';
    marco.classList.add('con-foto');
  });

  atributos();
  desdePrecio();
})();
