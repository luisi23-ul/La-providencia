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
  var canal = '<div class="campo"><span class="lbl">Canal</span>' + seg('canal', [['tienda_fisica', 'tienda', 'Tienda física'], ['online', 'globo', 'Online']]) + '</div>';
  var campos = {
    ajuste: { sub: 'Lo que hay de verdad en el estante.', html: canal + cantidad('Cantidad contada', 'cantidad_contada', 0, 0, 'Escribe lo que contaste, no la diferencia.') },
    merma: { sub: 'Lo que se dañó, venció o se perdió.', html: canal + cantidad('Unidades perdidas', 'cantidad', 1, 1, '') + '<div class="campo"><label for="aj-motivo">Motivo</label><select id="aj-motivo" name="motivo"><option>Dañado</option><option>Vencido</option><option>Extraviado</option><option>Otro</option></select></div>' },
    transferencia: { sub: 'Mueve unidades de un canal al otro.', html: '<div class="campo"><span class="lbl">Dirección</span>' + seg('direccion', [['a_online', 'globo', 'Tienda → Online'], ['a_tienda', 'tienda', 'Online → Tienda']]) + '</div>' + cantidad('Cantidad', 'cantidad', 1, 1, '') }
  };
  function valor(nombre) { var el = form.querySelector('input[name="' + nombre + '"]:checked'); return el ? el.value : ''; }
  function producto() { return productos.find(function (p) { return String(p.id) === sel.value; }) || productos[0]; }

  function pintarCampos() {
    var t = valor('tipo_movimiento');
    document.getElementById('aj-campos').innerHTML = campos[t].html;
    document.getElementById('aj-t-prod-sub').textContent = campos[t].sub;
    if (t === 'ajuste') document.getElementById('aj-cant').value = producto().t;
    vista();
  }

  function tarjeta(nombre, icono, antes, despues) {
    var d = despues - antes, clase = d < 0 ? 'baja' : d > 0 ? 'sube' : 'igual';
    var dif = d === 0 ? 'sin cambio' : (d > 0 ? '+' : '−') + Math.abs(d);
    var cifra = d === 0 ? String(despues) : '<s>' + antes + '</s><em>→</em>' + despues;
    return '<div class="ajx-canal ' + clase + '"><span class="ic"><svg class="i"><use href="#i-' + icono + '"/></svg></span><span>' + nombre + '</span><span class="dif">' + dif + '</span><b>' + cifra + '</b></div>';
  }

  function vista() {
    var p = producto(), t = valor('tipo_movimiento'), c = valor('canal'), d = valor('direccion');
    var cant = document.getElementById('aj-cant'), n = parseInt(cant && cant.value, 10) || 0, nt = p.t, no = p.o;
    if (t === 'ajuste') { if (c === 'online') no = n; else nt = n; }
    if (t === 'merma') { if (c === 'online') no = p.o - n; else nt = p.t - n; }
    if (t === 'transferencia') { if (d === 'a_online') { nt = p.t - n; no = p.o + n; } else { no = p.o - n; nt = p.t + n; } }
    document.getElementById('aj-elegido').innerHTML = (p.img ? '<img src="' + esc(p.img) + '" alt="" width="64" height="64">' : '<span class="sin-foto"><svg class="i"><use href="#i-caja"/></svg></span>') +
      '<b>' + esc(p.n) + '</b><span class="dv-chip neutro">Mínimo ' + p.min + '</span><small>Tienda ' + p.t + ' · Online ' + p.o + '</small>';
    var invalido = nt < 0 || no < 0;
    document.getElementById('aj-queda').innerHTML = tarjeta('Tienda física', 'tienda', p.t, Math.max(0, nt)) + tarjeta('Online', 'globo', p.o, Math.max(0, no)) +
      (invalido ? '<p class="ajx-error"><svg class="i"><use href="#i-alerta"/></svg>No hay tantas unidades en ese canal.</p>' : '');
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
    if (e.target.name === 'canal' && valor('tipo_movimiento') === 'ajuste') document.getElementById('aj-cant').value = e.target.value === 'online' ? producto().o : producto().t;
    vista();
  });
  form.addEventListener('input', vista);
  pintarCampos();
})();
