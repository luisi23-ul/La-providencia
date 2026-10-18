(function () {
  var form = document.getElementById('form-producto');
  if (!form) return;
  var D = Panel.datos('datos-atributos') || { categorias: [], variantes: [] };
  var cat = document.getElementById('fp-cat');
  var costo = document.getElementById('fp-costo'), pct = document.getElementById('fp-margen-pct'), precio = document.getElementById('fp-precio');
  var enBs = document.getElementById('fp-precio-bs'), gana = document.getElementById('fp-gana');
  var unDecimal = function (n) { return String(Math.round(n * 10) / 10); };
  var esc = Panel.esc;

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
  costo.addEventListener('input', desdePrecio);
  pct.addEventListener('input', desdeMargen);
  precio.addEventListener('input', desdePrecio);

  var seccionVar = document.getElementById('fp-variantes'), listaVar = document.getElementById('fp-var-lista');
  var stTienda = document.getElementById('fp-st');
  var notaStock = document.getElementById('fp-stock-nota'), ajuste = document.getElementById('fp-ajuste');
  var notaOriginal = notaStock.textContent, n = 0;
  var COLUMNAS = [['talla', 'Talla'], ['color', 'Color'], ['modelo', 'Modelo']];

  function atributosCat() {
    var c = D.categorias.find(function (x) { return String(x.id) === String(cat.value); });
    return c ? (c.atributos || []) : [];
  }
  function control(clave, etiqueta, valor, i) {
    var atr = atributosCat().find(function (a) { return a.nombre === clave; });
    var nombre = 'variantes[' + i + '][' + clave + ']', id = 'fp-v-' + i + '-' + clave;
    var campo = atr && atr.opciones.length
      ? '<select id="' + id + '" name="' + nombre + '"><option value="">' + etiqueta + '</option>' + atr.opciones.map(function (o) { return '<option' + (String(o).toLowerCase() === String(valor || '').toLowerCase() ? ' selected' : '') + '>' + esc(o) + '</option>'; }).join('') + '</select>'
      : '<input id="' + id + '" name="' + nombre + '" value="' + esc(valor || '') + '" placeholder="' + etiqueta + '" autocomplete="off">';
    return '<div class="fpx-var-campo"><label for="' + id + '">' + etiqueta + '</label>' + campo + '</div>';
  }
  function fila(v) {
    var i = n++, li = document.createElement('li');
    li.className = 'fpx-var-fila';
    li.innerHTML = '<input type="hidden" name="variantes[' + i + '][' + campoClave('variante') + ']" value="' + esc(v && v.id ? v.id : '') + '">' +
      '<input type="hidden" name="variantes[' + i + '][precio]" value="' + esc(v && v.precio != null ? v.precio : '') + '">' +
      COLUMNAS.map(function (c) { return control(c[0], c[1], v ? v[c[0]] : '', i); }).join('') +
      '<div class="fpx-var-campo"><label for="fp-v-' + i + '-sku">SKU</label><input id="fp-v-' + i + '-sku" name="variantes[' + i + '][sku]" value="' + esc(v && v.sku ? v.sku : '') + '" placeholder="Opcional" autocomplete="off"></div>' +
      '<div class="fpx-var-campo"><label for="fp-v-' + i + '-st">Stock</label><input id="fp-v-' + i + '-st" class="var-st" name="variantes[' + i + '][stock]" type="number" min="0" inputmode="numeric" value="' + (v ? v.stock : 0) + '"></div>' +
      '<button type="button" class="ic-btn" data-quitar-var aria-label="Quitar esta combinación"><svg class="i"><use href="#i-basura"/></svg></button>';
    listaVar.appendChild(li);
    return li;
  }
  function totales() {
    if (seccionVar.hidden) return;
    var t = 0;
    listaVar.querySelectorAll('.fpx-var-fila').forEach(function (li) {
      t += parseInt(li.querySelector('.var-st').value, 10) || 0;
    });
    stTienda.value = t;
  }
  function vistaVariantes(inicial) {
    var conVariantes = atributosCat().length > 0;
    seccionVar.hidden = !conVariantes;
    if (conVariantes) {
      if (inicial && D.variantes.length) D.variantes.forEach(fila);
      else {
        var previas = [].map.call(listaVar.querySelectorAll('.fpx-var-fila'), function (li) {
          var g = function (s) { var x = li.querySelector(s); return x ? x.value : ''; };
          return { id: g('[name$="[' + campoClave('variante') + ']"]'), talla: g('[name$="[talla]"]'), color: g('[name$="[color]"]'), modelo: g('[name$="[modelo]"]'), sku: g('[name$="[sku]"]'), precio: g('[name$="[precio]"]'), stock: g('.var-st') };
        });
        listaVar.innerHTML = ''; n = 0;
        (previas.length ? previas : [null]).forEach(fila);
      }
    } else { listaVar.innerHTML = ''; n = 0; }
    stTienda.readOnly = conVariantes || !!D.editando;
    notaStock.textContent = conVariantes ? 'El stock total es la suma de las combinaciones.' : notaOriginal;
    if (ajuste) ajuste.hidden = conVariantes;
    totales();
  }
  cat.addEventListener('change', function () { vistaVariantes(false); });
  listaVar.addEventListener('input', totales);
  listaVar.addEventListener('click', function (e) {
    var q = e.target.closest('[data-quitar-var]');
    if (!q) return;
    q.closest('.fpx-var-fila').remove();
    if (!listaVar.children.length) fila(null);
    totales(); revisar();
  });
  document.getElementById('fp-var-mas').addEventListener('click', function () {
    var li = fila(null);
    li.querySelector('select, input:not([type=hidden])').focus();
    revisar();
  });

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

  vistaVariantes(true);
  desdePrecio();

  var barra = document.getElementById('fp-barra');
  var foto = function () { var f = form.querySelector('[name=imagen]').files[0]; return f ? f.name + f.size : ''; };
  var estadoForm = function () { var p = []; new FormData(form).forEach(function (v, k) { if (k !== 'imagen') p.push(k + '=' + v); }); return p.join('&') + '|' + foto(); };
  var inicial = estadoForm();
  var hayCambios = function () { return estadoForm() !== inicial; };
  function revisar() { barra.hidden = !hayCambios(); }
  form.addEventListener('input', revisar);
  form.addEventListener('change', revisar);
  var vigia = Panel.vigilarCambios(hayCambios);
  document.getElementById('fp-descartar').addEventListener('click', function () { vigia.salir(); location.reload(); });
})();