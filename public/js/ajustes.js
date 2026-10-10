(function () {
  var productos = Panel.datos('datos-ajustes') || [], esc = Panel.esc;
  var form = document.getElementById('form-ajuste'), sel = document.getElementById('aj-prod');
  if (!form || !productos.length) return;
  var canal = '<div class="campo"><span class="lbl">Canal</span><div class="opciones"><label class="opcion"><input type="radio" name="canal" value="tienda_fisica" checked><span>Tienda física</span></label><label class="opcion"><input type="radio" name="canal" value="online"><span>Online</span></label></div></div>';
  var campos = {
    ajuste: canal + '<div class="campo"><label for="aj-cant">Cantidad contada <span class="ob">*</span></label><input id="aj-cant" type="number" min="0" inputmode="numeric" name="cantidad_contada" required><span class="ayuda">Lo que hay de verdad en el estante.</span></div>',
    merma: canal + '<div class="campo"><label for="aj-cant">Unidades perdidas <span class="ob">*</span></label><input id="aj-cant" type="number" min="1" inputmode="numeric" name="cantidad" value="1" required></div><div class="campo ancho"><label for="aj-motivo">Motivo</label><select id="aj-motivo" name="motivo"><option>Dañado</option><option>Vencido</option><option>Extraviado</option><option>Otro</option></select></div>',
    transferencia: '<div class="campo"><span class="lbl">Dirección</span><div class="opciones"><label class="opcion"><input type="radio" name="direccion" value="a_online" checked><span>Tienda → Online</span></label><label class="opcion"><input type="radio" name="direccion" value="a_tienda"><span>Online → Tienda</span></label></div></div><div class="campo"><label for="aj-cant">Cantidad <span class="ob">*</span></label><input id="aj-cant" type="number" min="1" inputmode="numeric" name="cantidad" value="1" required></div>'
  };
  function valor(nombre) { var el = form.querySelector('input[name="' + nombre + '"]:checked'); return el ? el.value : ''; }
  function producto() { return productos.find(function (p) { return String(p.id) === sel.value; }) || productos[0]; }

  function pintarCampos() {
    var t = valor('tipo_movimiento');
    document.getElementById('aj-campos').innerHTML = campos[t];
    if (t === 'ajuste') document.getElementById('aj-cant').value = producto().t;
    vista();
  }
  function vista() {
    var p = producto(), t = valor('tipo_movimiento'), c = valor('canal'), d = valor('direccion');
    var cant = document.getElementById('aj-cant'), n = parseInt(cant && cant.value, 10) || 0, nt = p.t, no = p.o;
    if (t === 'ajuste') { if (c === 'online') no = n; else nt = n; }
    if (t === 'merma') { if (c === 'online') no = p.o - n; else nt = p.t - n; }
    if (t === 'transferencia') { if (d === 'a_online') { nt = p.t - n; no = p.o + n; } else { no = p.o - n; nt = p.t + n; } }
    document.getElementById('aj-elegido').innerHTML = (p.img ? '<img class="foto" src="' + esc(p.img) + '" alt="" width="44" height="44">' : '') + '<div><b>' + esc(p.n) + '</b><span class="cifra">Tienda ' + p.t + ', online ' + p.o + ' (mín. ' + p.min + ')</span></div>';
    function caja(lbl, antes, despues) { return '<div><span>' + lbl + '</span><b><s>' + antes + ' →</s><span class="' + (despues > antes ? 'sube-v' : despues < antes ? 'baja-v' : '') + '">' + despues + '</span></b></div>'; }
    var invalido = nt < 0 || no < 0;
    document.getElementById('aj-queda').innerHTML = caja('Tienda física', p.t, Math.max(0, nt)) + caja('Online', p.o, Math.max(0, no)) + (invalido ? '<div class="queda-error">No hay tantas unidades en ese canal.</div>' : '');
    document.getElementById('aj-guardar').disabled = invalido;
  }
  form.addEventListener('change', function (e) {
    if (e.target.name === 'tipo_movimiento' || e.target === sel) return pintarCampos();
    if (e.target.name === 'canal' && valor('tipo_movimiento') === 'ajuste') document.getElementById('aj-cant').value = e.target.value === 'online' ? producto().o : producto().t;
    vista();
  });
  form.addEventListener('input', vista);
  pintarCampos();
})();
