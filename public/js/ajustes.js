(function () {
  var productos = Panel.datos('datos-ajustes') || [], esc = Panel.esc;
  var form = document.getElementById('form-ajuste'), sel = document.getElementById('aj-prod');
  if (!form || !productos.length) return;

  var seg = function (nombre, opciones) {
    return '<div class="ajx-seg" role="radiogroup">' + opciones.map(function (o, i) {
      return '<label><input type="radio" name="' + nombre + '" value="' + o[0] + '"' + (i === 0 ? ' checked' : '') + '><svg class="i"><use href="#i-' + o[1] + '"/></svg>' + o[2] + '</label>';
    }).join('') + '</div>';
  };
  var cantidad = function (etiqueta, nombre, min, valor, ayuda) {
    return '<div class="campo"><label for="aj-cant">' + etiqueta + ' <span class="ob">*</span></label><div class="ajx-cant"><button type="button" data-paso="-1" aria-label="Restar uno">−</button><input id="aj-cant" type="number" min="' + min + '" inputmode="numeric" name="' + nombre + '" value="' + valor + '" required><button type="button" data-paso="1" aria-label="Sumar uno">+</button></div>' + (ayuda ? '<span class="ayuda">' + ayuda + '</span>' : '') + '</div>';
  };
  var campos = {
    ajuste: { sub: 'Las unidades se suman al stock.', html: cantidad('Unidades a sumar', 'cantidad', 1, 1, 'Escribe cuántas entran, no el total.') },
    merma: { sub: 'Lo que se dañó, venció o se perdió.', html: cantidad('Unidades perdidas', 'cantidad', 1, 1, '') + '<div class="campo"><label for="aj-motivo">Motivo</label><select id="aj-motivo" name="motivo"><option>Dañado</option><option>Vencido</option><option>Extraviado</option><option>Otro</option></select></div>' },
  };
  function valor(nombre) { var el = form.querySelector('input[name="' + nombre + '"]:checked'); return el ? el.value : ''; }
  function producto() { return productos.find(function (p) { return String(p.id) === sel.value; }) || productos[0]; }

  function pintarCampos() {
    var t = valor('tipo_movimiento');
    document.getElementById('aj-campos').innerHTML = campos[t].html;
    document.getElementById('aj-t-prod-sub').textContent = campos[t].sub;
    vista();
  }

  function tarjeta(nombre, icono, antes, despues) {
    var d = despues - antes, clase = d < 0 ? 'baja' : d > 0 ? 'sube' : 'igual';
    var dif = d === 0 ? 'sin cambio' : (d > 0 ? '+' : '−') + Math.abs(d);
    var cifra = d === 0 ? String(despues) : '<s>' + antes + '</s><em>→</em>' + despues;
    return '<div class="ajx-canal ' + clase + '"><span class="ic"><svg class="i"><use href="#i-' + icono + '"/></svg></span><span>' + nombre + '</span><span class="dif">' + dif + '</span><b>' + cifra + '</b></div>';
  }

  function vista() {
    var p = producto(), t = valor('tipo_movimiento');
    var cant = document.getElementById('aj-cant'), n = parseInt(cant && cant.value, 10) || 0;
    var nt = t === 'merma' ? p.t - n : p.t + n;
    document.getElementById('aj-elegido').innerHTML = (p.img ? '<img src="' + esc(p.img) + '" alt="" width="64" height="64">' : '<span class="sin-foto"><svg class="i"><use href="#i-caja"/></svg></span>') +
      '<b>' + esc(p.n) + '</b><span class="dv-chip neutro">Mínimo ' + p.min + '</span><small>Stock ' + p.t + '</small>';
    var invalido = nt < 0;
    document.getElementById('aj-queda').innerHTML = tarjeta('Stock', 'caja', p.t, Math.max(0, nt)) +
      (invalido ? '<p class="ajx-error"><svg class="i"><use href="#i-alerta"/></svg>No hay tantas unidades en stock.</p>' : '');
    document.getElementById('aj-guardar').disabled = invalido;
  }

  form.addEventListener('click', function (e) {
    var b = e.target.closest('[data-paso]');
    if (!b) return;
    var cant = document.getElementById('aj-cant'), min = +cant.min || 0;
    cant.value = Math.max(min, (parseInt(cant.value, 10) || 0) + +b.dataset.paso);
    vista();
  });
  form.addEventListener('change', function (e) {
    if (e.target.name === 'tipo_movimiento' || e.target === sel) return pintarCampos();
    vista();
  });
  form.addEventListener('input', vista);
  pintarCampos();
})();
