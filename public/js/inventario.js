(function () {
  var form = document.getElementById('form-producto');
  if (!form) return;
  var D = Panel.datos('datos-atributos') || { categorias: [], valores: {} };
  var cat = document.getElementById('fp-cat'), caja = document.getElementById('fp-atributos-caja'), cont = document.getElementById('fp-atributos');
  var costo = document.getElementById('fp-costo'), pct = document.getElementById('fp-margen-pct'), precio = document.getElementById('fp-precio');
  var enBs = document.getElementById('fp-precio-bs'), gana = document.getElementById('fp-gana');
  var unDecimal = function (n) { return String(Math.round(n * 10) / 10); };

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
    gana.hidden = !(c > 0 && p > 0);
    if (gana.hidden) return;
    var g = p - c;
    gana.classList.toggle('neg', g < 0);
    document.getElementById('fp-g-tit').textContent = g < 0 ? 'Pierdes por unidad' : 'Ganas por unidad';
    document.getElementById('fp-g-usd').textContent = Panel.usd(Math.abs(g));
    document.getElementById('fp-g-costo').textContent = unDecimal(g / c * 100) + ' %';
    document.getElementById('fp-g-precio').textContent = unDecimal(g / p * 100) + ' %';
  }

  function desdeMargen() {
    var c = parseFloat(costo.value), m = parseFloat(pct.value);
    if (c > 0 && m >= 0) precio.value = (c * (1 + m / 100)).toFixed(2);
    resumen();
  }

  function desdePrecio() {
    var c = parseFloat(costo.value), p = parseFloat(precio.value);
    pct.value = c > 0 && p > 0 ? unDecimal((p - c) / c * 100) : '';
    resumen();
  }

  cat.addEventListener('change', atributos);
  costo.addEventListener('input', desdePrecio);
  pct.addEventListener('input', desdeMargen);
  precio.addEventListener('input', desdePrecio);

  var vis = document.querySelector('#fp-vis input[type=checkbox]');
  vis.addEventListener('change', function () {
    var si = vis.checked, chip = document.getElementById('fp-chip-vis');
    document.getElementById('fp-vis').classList.toggle('oculto', !si);
    document.getElementById('fp-vis-t').textContent = si ? 'Visible en la tienda' : 'Oculto en la tienda';
    document.getElementById('fp-vis-s').textContent = si ? 'Los clientes lo ven y lo pueden comprar.' : 'Los clientes no lo ven.';
    if (chip) { chip.textContent = si ? 'Visible en la tienda' : 'Oculto en la tienda'; chip.classList.toggle('pend', !si); }
  });

  document.getElementById('fp-img').addEventListener('change', function () {
    var f = this.files[0], marco = document.getElementById('fp-foto');
    if (!f) return;
    marco.innerHTML = '<img src="' + URL.createObjectURL(f) + '" alt="Foto del producto">';
    marco.classList.add('con-foto');
    document.getElementById('fp-foto-btn').textContent = 'Cambiar foto';
  });

  atributos();
  desdePrecio();

  var barra = document.getElementById('fp-barra');
  var foto = function () { var f = form.querySelector('[name=imagen]').files[0]; return f ? f.name + f.size : ''; };
  var estadoForm = function () { var p = []; new FormData(form).forEach(function (v, k) { if (k !== 'imagen') p.push(k + '=' + v); }); return p.join('&') + '|' + foto(); };
  var inicial = estadoForm();
  var hayCambios = function () { return estadoForm() !== inicial; };
  var revisar = function () { barra.hidden = !hayCambios(); };
  form.addEventListener('input', revisar);
  form.addEventListener('change', revisar);
  var vigia = Panel.vigilarCambios(hayCambios);
  document.getElementById('fp-descartar').addEventListener('click', function () { vigia.salir(); location.reload(); });
})();
