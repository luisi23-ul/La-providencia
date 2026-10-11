(function () {
  var productos = Panel.datos('datos-compra') || [], usd = Panel.usd;
  var form = document.getElementById('form-compra'), lista = document.getElementById('renglones'), tpl = document.getElementById('tpl-renglon');
  var MARGEN = 30, n = 0;
  if (!form) return;

  function prod(id) { return productos.find(function (p) { return String(p.id) === String(id); }); }
  function destino() { return form.querySelector('input[name="destino_stock"]:checked').value; }
  function campo(li, nombre) { return li.querySelector('[name="' + nombre + '[]"]'); }

  function agregar() {
    var li = tpl.content.firstElementChild.cloneNode(true), sel = campo(li, 'id_producto');
    n++;
    sel.innerHTML = '<option value="">Elige un producto</option>' +
                    '<option value="nuevo" style="font-weight:bold; color:var(--primario, #15803d);">+ Producto nuevo</option>' +
                    productos.map(function (p) { return '<option value="' + p.id + '">' + Panel.esc(p.c + ' · ' + p.n) + '</option>'; }).join('');
    li.querySelectorAll('.campo').forEach(function (c, i) { var id = 'rg-' + n + '-' + i; c.querySelector('label').htmlFor = id; c.querySelector('input, select').id = id; });
    lista.appendChild(li);
    sel.focus();
    resumen();
  }

  function elegir(li) {
    var valor = campo(li, 'id_producto').value;
    var bloqueNuevo = li.querySelector('.rg-nuevo-prod');
    var inpCod = li.querySelector('input[name="nuevo_codigo[]"]');
    var inpNom = li.querySelector('input[name="nuevo_nombre[]"]');

    if (valor === 'nuevo') {
      if (bloqueNuevo) bloqueNuevo.style.display = 'grid';
      if (inpCod) inpCod.required = true;
      if (inpNom) inpNom.required = true;
      campo(li, 'costo_unitario').value = '';
      campo(li, 'precio_venta').value = '';
      campo(li, 'margen').value = MARGEN;
      pintar(li);
      if (inpCod) inpCod.focus();
      return;
    }

    if (bloqueNuevo) bloqueNuevo.style.display = 'none';
    if (inpCod) { inpCod.required = false; inpCod.value = ''; }
    if (inpNom) { inpNom.required = false; inpNom.value = ''; }

    var p = prod(valor);
    if (!p) { pintar(li); return; }
    campo(li, 'costo_unitario').value = p.costo ? p.costo.toFixed(2) : '';
    if (p.costo && p.precio) {
      campo(li, 'margen').value = Math.round((p.precio - p.costo) / p.costo * 100);
      campo(li, 'precio_venta').value = p.precio.toFixed(2);
      pintar(li);
    } else {
      campo(li, 'margen').value = MARGEN;
      desdeMargen(li);
    }
  }

  function desdeMargen(li) {
    var c = parseFloat(campo(li, 'costo_unitario').value), m = parseFloat(campo(li, 'margen').value);
    if (c > 0 && m >= 0) campo(li, 'precio_venta').value = (c * (1 + m / 100)).toFixed(2);
    pintar(li);
  }

  function desdePrecio(li) {
    var c = parseFloat(campo(li, 'costo_unitario').value), v = parseFloat(campo(li, 'precio_venta').value);
    campo(li, 'margen').value = c > 0 && v > 0 ? Math.round((v - c) / c * 100) : '';
    pintar(li);
  }

  function pintar(li) {
    var valor = campo(li, 'id_producto').value;
    var p = prod(valor);
    var cant = parseInt(campo(li, 'cantidad').value, 10) || 0, c = parseFloat(campo(li, 'costo_unitario').value) || 0, v = parseFloat(campo(li, 'precio_venta').value) || 0;
    li.querySelector('.rg-sub b').textContent = usd(cant * c);
    var st = li.querySelector('.rg-stock');

    if (valor === 'nuevo') {
      st.innerHTML = '<span class="chip" style="font-size:0.75rem;">Nuevo en catálogo</span>';
    } else if (p) {
      var online = destino() === 'online', actual = online ? p.o : p.t;
      st.innerHTML = (online ? 'Online' : 'Tienda') + ' <b class="cifra">' + actual + '</b> → <b class="cifra sube-v">' + (actual + cant) + '</b>';
    } else {
      st.textContent = '';
    }

    var act = li.querySelector('.rg-actualizar');
    if (valor === 'nuevo') {
      act.hidden = false;
      act.querySelector('small').textContent = ' (Se registrará en catálogo a ' + usd(v) + ')';
      act.querySelector('input[type=hidden]').value = 1;
    } else {
      var cambia = p && v > 0 && Math.abs(v - p.precio) >= 0.005;
      act.hidden = !cambia;
      act.querySelector('small').textContent = cambia ? 'Hoy ' + usd(p.precio) + ', pasa a ' + usd(v) : '';
      act.querySelector('input[type=hidden]').value = cambia && act.querySelector('input[type=checkbox]').checked ? 1 : 0;
    }

    li.classList.toggle('perdida', c > 0 && v > 0 && v < c);
    resumen();
  }

  function resumen() {
    var filas = lista.querySelectorAll('.renglon'), unid = 0, total = 0, ventaTotal = 0, precios = 0, repetido = false, vistos = {};
    filas.forEach(function (li) {
      var id = campo(li, 'id_producto').value;
      var cant = parseInt(campo(li, 'cantidad').value, 10) || 0;
      var c = parseFloat(campo(li, 'costo_unitario').value) || 0;
      var v = parseFloat(campo(li, 'precio_venta').value) || c;

      unid += cant; 
      total += cant * c;
      ventaTotal += cant * v;

      if (+li.querySelector('.rg-actualizar input[type=hidden]').value) precios++;
      if (id && id !== 'nuevo') { 
        if (vistos[id]) repetido = true; 
        vistos[id] = 1; 
      }
    });

    var gananciaTotal = ventaTotal - total;
    var margenGlobal = total > 0 ? (gananciaTotal / total) * 100 : 0;

    document.getElementById('cuenta-renglones').textContent = filas.length ? filas.length + (filas.length === 1 ? ' producto' : ' productos') : '';
    document.getElementById('r-renglones').textContent = filas.length;
    document.getElementById('r-unidades').textContent = unid;
    document.getElementById('r-precios').textContent = precios;
    document.getElementById('r-total').textContent = usd(total);
    document.getElementById('r-bs').textContent = Panel.fmtBs(total * Panel.tasa) + ' Bs';

    var elGanancia = document.getElementById('r-ganancia');
    if (elGanancia) {
      elGanancia.textContent = '+' + usd(gananciaTotal) + ' (' + margenGlobal.toFixed(1) + '%)';
    }

    var perdida = lista.querySelector('.renglon.perdida');
    var aviso = !filas.length ? 'Agrega al menos un producto.' : repetido ? 'Hay un producto repetido: súmalo en una sola línea.' : perdida ? 'Hay un precio de venta por debajo del costo.' : '';
    document.getElementById('r-aviso').textContent = aviso;
    document.getElementById('c-guardar').disabled = !filas.length || repetido;
  }

  lista.addEventListener('change', function (e) {
    var li = e.target.closest('.renglon');
    if (e.target.name === 'id_producto[]') elegir(li);
    else if (e.target.type === 'checkbox') pintar(li);
  });
  lista.addEventListener('input', function (e) {
    var li = e.target.closest('.renglon');
    if (e.target.name === 'margen[]' || e.target.name === 'costo_unitario[]') desdeMargen(li);
    else if (e.target.name === 'precio_venta[]') desdePrecio(li);
    else if (e.target.name === 'cantidad[]') pintar(li);
  });
  lista.addEventListener('click', function (e) {
    if (e.target.closest('[data-quitar]')) { e.target.closest('.renglon').remove(); resumen(); }
  });
  form.addEventListener('change', function (e) { if (e.target.name === 'destino_stock') lista.querySelectorAll('.renglon').forEach(pintar); });
  document.getElementById('agregar-renglon').addEventListener('click', agregar);
  agregar();
})();