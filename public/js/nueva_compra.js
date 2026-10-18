(function () {
  var productos = Panel.datos('datos-compra') || [], usd = Panel.usd;
  var form = document.getElementById('form-compra'), lista = document.getElementById('renglones'), tpl = document.getElementById('tpl-renglon');
  var MARGEN = 30, n = 0;
  if (!form) return;
  var unDecimal = function (x) { return String(Math.round(x * 10) / 10); };

  function prod(id) { return productos.find(function (p) { return String(p.id) === String(id); }); }
  function destino() { 
    var el = form.querySelector('input[name="destino_stock"]');
    return el ? el.value : 'tienda_fisica'; 
  }
  function campo(li, nombre) { return li.querySelector('[name="' + nombre + '[]"]'); }

  function agregar(abrir) {
    var li = tpl.content.firstElementChild.cloneNode(true), sel = campo(li, campoClave('producto.ref'));
    n++;
    sel.innerHTML = '<option value="">Elige un producto</option><option value="nuevo">+ Producto nuevo (no está en el inventario)</option>' + productos.map(function (p) { return '<option value="' + Panel.esc(p.id) + '">' + Panel.esc(p.c + ' · ' + p.n) + '</option>'; }).join('');
    li.querySelectorAll('.cpx-rg-num .campo').forEach(function (c, i) { var id = 'rg-' + n + '-' + i; c.querySelector('label').htmlFor = id; c.querySelector('input').id = id; });
    lista.appendChild(li);
    resumen();
    if (!abrir) return;
    li.scrollIntoView({ block: 'center', behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    setTimeout(function () { var b = li.querySelector('.dsp-btn'); if (b) { b.focus(); b.click(); } else sel.focus(); }, 320);
  }

  function elegir(li) {
    var p = prod(campo(li, campoClave('producto.ref')).value), cab = li.querySelector('.cpx-rg-cab'), foto = cab.querySelector('.sin-foto, img');
    if (p && p.img) { var img = document.createElement('img'); img.src = p.img; img.alt = ''; img.width = 52; img.height = 52; foto.replaceWith(img); }
    else if (foto.tagName === 'IMG') { var s = document.createElement('span'); s.className = 'sin-foto'; s.innerHTML = '<svg class="i"><use href="#i-caja"/></svg>'; foto.replaceWith(s); }
    
    var esNuevo = campo(li, campoClave('producto.ref')).value === 'nuevo', bloque = li.querySelector('.cpx-nuevo-prod');
    bloque.hidden = !esNuevo;
    bloque.querySelectorAll('input, select').forEach(function (i) { i.required = esNuevo; if (!esNuevo) i.value = ''; });
    Panel.refrescarSelects(bloque);

    // Manejo de variantes en el renglón
    var selVar = li.querySelector('.cpx-sel-variante');
    if (selVar) {
      if (p && p.variantes && p.variantes.length > 0) {
        selVar.innerHTML = '<option value="0">Elige variante...</option>' + p.variantes.map(function (v) {
          var label = [v.talla, v.color, v.modelo].filter(Boolean).join(' - ') || ('Variante #' + v.id);
          return '<option value="' + Panel.esc(v.id) + '">' + Panel.esc(label + ' (Stock: ' + v.stock + ')') + '</option>';
        }).join('');
        selVar.style.display = 'inline-block';
      } else {
        selVar.innerHTML = '<option value="0">Variante principal / Única</option>';
        selVar.style.display = 'none';
      }
    }

    li.querySelector('.cpx-hoy').textContent = p ? 'Hoy se vende a ' + usd(p.precio) : esNuevo ? 'Producto nuevo: escribe su código y nombre' : 'Elige qué llegó en la factura';
    if (esNuevo) { campo(li, 'costo_unitario').value = ''; campo(li, 'margen').value = MARGEN; campo(li, 'precio_venta').value = ''; pintar(li); bloque.querySelector('input').focus(); return; }
    if (!p) { pintar(li); return; }
    campo(li, 'costo_unitario').value = p.costo ? p.costo.toFixed(2) : '';
    if (p.costo && p.precio) {
      campo(li, 'margen').value = unDecimal((p.precio - p.costo) / p.costo * 100);
      campo(li, 'precio_venta').value = p.precio.toFixed(2);
      pintar(li);
    } else {
      campo(li, 'margen').value = MARGEN;
      desdeMargen(li);
    }
    if (!p.costo) campo(li, 'costo_unitario').focus();
  }

  function desdeMargen(li) {
    var c = parseFloat(campo(li, 'costo_unitario').value), m = parseFloat(campo(li, 'margen').value);
    if (c > 0 && !isNaN(m)) campo(li, 'precio_venta').value = Math.max(0, c * (1 + m / 100)).toFixed(2);
    pintar(li);
  }

  function desdePrecio(li) {
    var c = parseFloat(campo(li, 'costo_unitario').value), v = parseFloat(campo(li, 'precio_venta').value);
    campo(li, 'margen').value = c > 0 && v > 0 ? unDecimal((v - c) / c * 100) : '';
    pintar(li);
  }

  function pintar(li) {
    var p = prod(campo(li, campoClave('producto.ref')).value);
    var cant = parseInt(campo(li, 'cantidad').value, 10) || 0, c = parseFloat(campo(li, 'costo_unitario').value) || 0, v = parseFloat(campo(li, 'precio_venta').value) || 0;
    li.querySelector('.cpx-sub b').textContent = usd(cant * c);
    var st = li.querySelector('.cpx-stock');
    st.hidden = !p;
    if (p) {
      var actual = p.t || 0;
      st.innerHTML = 'Inventario <s>' + actual + '</s> → ' + (actual + cant);
    }
    var perdida = c > 0 && v > 0 && v < c, act = li.querySelector('.cpx-precio'), cambia = !!p && v > 0 && Math.abs(v - p.precio) >= 0.005;
    act.hidden = !cambia;
    act.querySelector('b').textContent = cambia ? usd(p.precio) + ' → ' + usd(v) : '';
    act.querySelector('input[type=hidden]').value = cambia && act.querySelector('input[type=checkbox]').checked ? 1 : 0;
    var aviso = li.querySelector('.cpx-perdida');
    aviso.hidden = !perdida;
    aviso.querySelector('span').textContent = perdida ? 'Se vende por debajo del costo: pierdes ' + usd(c - v) + ' por unidad' : '';
    li.classList.toggle('perdida', perdida);
    resumen();
  }

  function resumen() {
    var filas = lista.querySelectorAll('.cpx-rg'), elegidos = 0, unid = 0, total = 0, precios = 0, repetido = false, vistos = {};
    filas.forEach(function (li) {
      var id = campo(li, campoClave('producto.ref')).value;
      if (!id) return;
      elegidos++;
      var cant = parseInt(campo(li, 'cantidad').value, 10) || 0, c = parseFloat(campo(li, 'costo_unitario').value) || 0;
      unid += cant; total += cant * c;
      if (+li.querySelector('.cpx-precio input[type=hidden]').value) precios++;
      
      // Permitir mismo producto si tiene variante distinta
      var vId = (campo(li, campoClave('variante.ref')) ? campo(li, campoClave('variante.ref')).value : '') || '0';
      var claveUnica = id + '_' + vId;
      if (id && id !== 'nuevo') { 
        if (vistos[claveUnica]) repetido = true; 
        vistos[claveUnica] = 1; 
      }
    });
    var cuenta = document.getElementById('cuenta-renglones');
    cuenta.textContent = elegidos + (elegidos === 1 ? ' producto' : ' productos');
    cuenta.hidden = !elegidos;
    document.getElementById('r-renglones').textContent = elegidos;
    document.getElementById('r-unidades').textContent = unid;
    document.getElementById('r-precios').textContent = precios;
    document.getElementById('r-total').textContent = usd(total);
    document.getElementById('r-bs').textContent = Panel.fmtBs(total * Panel.tasa) + ' Bs';
    document.getElementById('r-destino').textContent = 'Al guardar, las unidades se suman al inventario.';
    var aviso = !filas.length ? 'Agrega al menos un producto.' : !elegidos ? 'Elige qué producto llegó.' : repetido ? 'Hay un producto/variante repetido: súmalo en una sola tarjeta.' : lista.querySelector('.cpx-rg.perdida') ? 'Hay un precio de venta por debajo del costo.' : '';
    var r = document.getElementById('r-aviso');
    r.textContent = aviso; r.hidden = !aviso;
    document.querySelectorAll('#form-compra [type=submit], [type=submit][form="form-compra"]').forEach(function (b) { if (!b.classList.contains('guardando')) b.disabled = !filas.length || repetido; });
  }

  lista.addEventListener('change', function (e) {
    var li = e.target.closest('.cpx-rg');
    if (e.target.name === campoClave('producto.ref') + '[]') elegir(li);
    else if (e.target.name === campoClave('variante.ref') + '[]') resumen();
    else if (e.target.type === 'checkbox') pintar(li);
  });
  lista.addEventListener('input', function (e) {
    var li = e.target.closest('.cpx-rg');
    if (e.target.name === 'margen[]' || e.target.name === 'costo_unitario[]') desdeMargen(li);
    else if (e.target.name === 'precio_venta[]') desdePrecio(li);
    else if (e.target.name === 'cantidad[]') pintar(li);
  });

  var barra = document.getElementById('cp-barra'), inicial = '';
  var estadoForm = function () { var p = []; new FormData(form).forEach(function (v, k) { p.push(k + '=' + v); }); return p.join('&'); };
  var hayCambios = function () { return estadoForm() !== inicial; };
  function revisar() { barra.hidden = !hayCambios(); }

  lista.addEventListener('click', function (e) {
    var paso = e.target.closest('[data-paso]');
    if (paso) {
      var li = paso.closest('.cpx-rg'), cant = campo(li, 'cantidad');
      cant.value = Math.max(1, (parseInt(cant.value, 10) || 0) + +paso.dataset.paso);
      pintar(li); revisar(); return;
    }
    if (e.target.closest('[data-quitar]')) { e.target.closest('.cpx-rg').remove(); resumen(); revisar(); }
  });

  form.addEventListener('change', function (e) { 
    if (e.target.name === 'destino_stock') { 
      lista.querySelectorAll('.cpx-rg').forEach(pintar); 
      resumen(); 
    } 
  });
  document.getElementById('agregar-renglon').addEventListener('click', function () { agregar(true); revisar(); });
  agregar(false);

  inicial = estadoForm();
  form.addEventListener('input', revisar);
  form.addEventListener('change', revisar);
  var vigia = Panel.vigilarCambios(hayCambios);
  document.getElementById('cp-descartar').addEventListener('click', function () { vigia.salir(); location.reload(); });
})();