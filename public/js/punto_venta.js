(function () {
  var D = Panel.datos('datos-pos'), TASA = Panel.tasa, usd = Panel.usd, bs = Panel.bs, esc = Panel.esc, avisar = Panel.avisar;
  var $ = function (id) { return document.getElementById(id); };
  var productos = D.productos, metodos = D.metodos, clientes = D.clientes;
  var pv = { lineas: [], edit: -1, cliente: null, nuevoCliente: null, cat: '' };
  var r2 = function (v) { return Math.round(v * 100) / 100; };
  var prod = function (id) { return productos.filter(function (p) { return p.id === id; })[0]; };
  var precioL = function (l) { return l.p != null ? l.p : prod(l.id).precio; };
  var totalL = function (l) { return r2(precioL(l) * l.q * (1 - l.d / 100)); };
  var totalPV = function () { return r2(pv.lineas.reduce(function (s, l) { return s + totalL(l); }, 0)); };
  var cantEn = function (id) { return pv.lineas.reduce(function (s, x) { return x.id === id ? s + x.q : s; }, 0); };
  var variante = function (l) { return l.v ? (prod(l.id).v || []).filter(function (x) { return x[0] === l.v; })[0] : null; };
  var topeL = function (l) { var x = variante(l); return x ? x[3] : (l.v ? 0 : prod(l.id).t); };
  var nomVar = function (x) { return [x[1], x[2]].filter(Boolean).join(' / '); };
  var stockP = function (p) { return p.v && p.v.length ? p.v.reduce(function (s, x) { return s + x[3]; }, 0) : p.t; };
  var iniciales = function (n) { return n.split(' ').map(function (w) { return w[0]; }).slice(0, 2).join('').toUpperCase(); };

  var BANCOS_VZLA = [
    'Banesco', 'Banco de Venezuela', 'Mercantil', 'Banco Provincial',
    'BNC Nacional de Crédito', 'Bancaribe', 'Banco Exterior', 'Banco Plaza',
    'Banco del Tesoro', 'Banco Bicentenario', 'Banplus', '100% Banco'
  ];

  function guardarActual() {
    try {
      if (pv.lineas.length || pv.cliente) localStorage.setItem('pv-actual', JSON.stringify({ lineas: pv.lineas, cliente: pv.cliente, nuevoCliente: pv.nuevoCliente }));
      else localStorage.removeItem('pv-actual');
    } catch (e) {}
  }
  function recuperarActual() {
    try {
      var a = JSON.parse(localStorage.getItem('pv-actual'));
      if (!a) return false;
      pv.lineas = (a.lineas || []).filter(function (l) { return prod(l.id) && topeL(l) > 0; }).map(function (l) { l.q = Math.min(l.q, topeL(l)); return l; });
      pv.cliente = a.cliente || null; pv.nuevoCliente = a.nuevoCliente || null;
      return pv.lineas.length > 0;
    } catch (e) { return false; }
  }
  function leerEspera() { try { return JSON.parse(localStorage.getItem('pv-espera')) || []; } catch (e) { return []; } }
  function guardarEspera(l) { try { localStorage.setItem('pv-espera', JSON.stringify(l)); } catch (e) {} }

  function pintarPV() {
    var c = pv.cliente;
    $('pv-cliente').classList.toggle('con-cliente', !!c);
    $('pv-cliente').innerHTML = '<span class="avatar">' + (c ? esc(iniciales(c.n)) : 'CF') + '</span><span><b>' + (c ? esc(c.n) : 'Consumidor final') + '</b><span class="cifra">' + (c ? esc(c.doc) : 'Sin cédula ni RIF') + '</span></span><span class="cambiar">' + (c ? 'Cambiar' : 'Agregar cliente') + '</span>';
    pintarLineas(); pintarTotales(); pintarGrid(); guardarActual();
  }
  function detalleL(l) { return usd(precioL(l)) + ' c/u' + (l.p != null ? ', precio cambiado' : '') + (l.d ? ' <span class="desc">−' + l.d + '%</span>' : ''); }
  function pintarLineas() {
    var ul = $('pv-lineas');
    $('pv-vaciar').hidden = !pv.lineas.length;
    if (!pv.lineas.length) { ul.innerHTML = '<li class="pv-vacio">Escanea un código o toca un producto para empezar la venta.</li>'; return; }
    ul.innerHTML = pv.lineas.map(function (l, i) {
      var p = prod(l.id), abierta = i === pv.edit, x = variante(l), tope = topeL(l);
      return '<li class="pv-linea' + (abierta ? ' abierta' : '') + '" data-i="' + i + '">' +
        '<div class="pl-fila1"><b>' + esc(p.n) + (x ? '<small class="pl-var">' + esc(nomVar(x)) + '</small>' : '') + '</b><span class="tot" data-tot>' + usd(totalL(l)) + '</span></div>' +
        '<div class="pl-fila2"><span class="stepper"><button type="button" data-menos="' + i + '" aria-label="Uno menos">−</button><span data-q>' + l.q + '</span><button type="button" data-mas="' + i + '" aria-label="Uno más"' + (l.q >= tope ? ' disabled' : '') + '>+</button></span>' +
        '<span class="det" data-det>' + detalleL(l) + '</span>' +
        '<button type="button" class="ic-btn" data-editar="' + i + '" aria-expanded="' + abierta + '" aria-label="' + (x ? 'Editar talla, color' + (D.puedeDescuento ? ', cantidad o precio' : ' o cantidad') : (D.puedeDescuento ? 'Editar cantidad, descuento o precio' : 'Editar cantidad o quitar')) + '"><svg class="i"><use href="#i-editar"/></svg></button></div>' +
        (abierta ? '<div class="pl-editor pe">' + (x ? editorPrenda(l, x) : '') +
          '<div class="pe-campos' + (D.puedeDescuento ? '' : ' uno') + '">' +
          '<div class="pe-campo"><label class="pe-et" for="pe-q">Cantidad</label><input id="pe-q" type="number" min="1" max="' + tope + '" inputmode="numeric" data-campo="q" value="' + l.q + '"></div>' +
          (D.puedeDescuento ? '<div class="pe-campo"><label class="pe-et" for="pe-d">Desc. %</label><input id="pe-d" type="number" min="0" max="100" inputmode="numeric" data-campo="d" value="' + l.d + '"></div>' +
          '<div class="pe-campo"><label class="pe-et" for="pe-p">Precio $</label><input id="pe-p" type="number" min="0" step="0.01" inputmode="decimal" data-campo="p" value="' + precioL(l).toFixed(2) + '"></div>' : '') +
          '</div><p class="pe-nota">Quedan <b>' + tope + '</b>' + (x ? ' de ' + esc(nomVar(x)) : '') + (D.puedeDescuento ? ' · precio normal <b>' + usd(p.precio) + '</b>' : '') + '</p>' +
          '<div class="pe-acc"><button type="button" class="pe-quitar" data-quitar="' + i + '"><svg class="i"><use href="#i-basura"/></svg>Quitar</button><button type="button" class="btn btn-pri" data-listo>Listo</button></div>' +
        '</div>' : '') + '</li>';
    }).join('');
  }
  function libreLinea(o, l) { return o[3] - pv.lineas.reduce(function (s, y) { return y !== l && y.v === o[0] ? s + y.q : s; }, 0); }
  function editorPrenda(l, x) {
    var vs = prod(l.id).v, tallas = unicos(vs.map(function (o) { return o[1]; })), colores = unicos(vs.filter(function (o) { return o[1] === x[1]; }).map(function (o) { return o[2]; }));
    var fila = function (tit, ops, sel, attr, de) {
      return '<div class="pe-campo" role="group" aria-label="' + tit + '"><span class="pe-et">' + tit + '</span><span class="pe-seg">' + ops.map(function (v) {
        var q = vs.filter(function (o) { return de(o, v); }).reduce(function (s, o) { return s + libreLinea(o, l); }, 0);
        return '<button type="button" ' + attr + '="' + esc(v) + '" aria-pressed="' + (v === sel) + '"' + (q > 0 || v === sel ? '' : ' disabled') + '>' + esc(v) + '</button>';
      }).join('') + '</span></div>';
    };
    return '<div class="pe-prenda">' +
      (tallas.some(Boolean) ? fila('Talla', tallas, x[1], 'data-pe-talla', function (o, v) { return o[1] === v; }) : '') +
      (colores.some(Boolean) ? fila('Color', colores, x[2], 'data-pe-color', function (o, v) { return o[1] === x[1] && o[2] === v; }) : '') + '</div>';
  }
  function cambiarPrenda(i, talla, color) {
    var l = pv.lineas[i], vs = prod(l.id).v, x = variante(l);
    var cand = vs.filter(function (o) { return o[1] === talla && libreLinea(o, l) > 0; });
    var n = cand.filter(function (o) { return o[2] === color; })[0] || cand.filter(function (o) { return o[2] === x[2]; })[0] || cand[0];
    if (!n || n[0] === l.v) return;
    var otra = pv.lineas.filter(function (y) { return y !== l && y.v === n[0]; })[0];
    if (otra) { otra.q = Math.min(n[3], otra.q + l.q); pv.lineas.splice(i, 1); pv.edit = pv.lineas.indexOf(otra); avisar('Se juntó con la línea de ' + nomVar(n), 'info'); }
    else { var tope = libreLinea(n, l); if (l.q > tope) { l.q = tope; avisar('Solo quedan ' + tope + ' de ' + nomVar(n), 'aviso'); } l.v = n[0]; }
    pintarPV();
  }
  function pintarTotales() {
    var art = pv.lineas.reduce(function (s, l) { return s + l.q; }, 0), tot = totalPV();
    var desc = r2(pv.lineas.reduce(function (s, l) { return s + precioL(l) * l.q; }, 0) - tot);
    $('pv-art').textContent = art;
    $('pv-fila-desc').hidden = !(desc > 0);
    $('pv-desc').textContent = '−' + usd(desc);
    $('pv-total').textContent = usd(tot);
    $('pv-bs').textContent = bs(tot);
    $('pv-tab-cuenta').textContent = art ? '(' + art + ')' : '';
    $('pv-barra-art').textContent = art;
    $('pv-barra-total').textContent = usd(tot);
    $('pv-cobrar').textContent = pv.lineas.length ? 'Cobrar ' + usd(tot) : 'Cobrar';
    $('pv-cobrar').disabled = !pv.lineas.length;
    var n = leerEspera().length;
    $('pv-cuenta-espera').textContent = n; $('pv-cuenta-espera').hidden = !n;
  }
  function pintarGrid() {
    var q = $('pv-scan').value.trim().toLowerCase();
    var lista = productos.filter(function (p) { return (!pv.cat || p.cat === pv.cat) && (!q || (p.n + ' ' + p.c).toLowerCase().indexOf(q) !== -1); });
    $('pv-grid').innerHTML = lista.map(function (p) {
      var st = stockP(p), cant = cantEn(p.id), sin = st === 0, bajo = st > 0 && st < p.min;
      var tope = sin || cant >= st;
      return '<button type="button" class="pv-prod' + (tope ? ' tope' : '') + '" data-agregar="' + p.id + '"' + (tope ? ' aria-disabled="true"' : '') + ' aria-label="Agregar ' + esc(p.n) + (tope ? ', no queda más en tienda' : '') + '">' +
        (p.img ? '<img src="' + esc(p.img) + '" alt="" loading="lazy">' : '<span class="pv-sin-foto"></span>') + (cant ? '<span class="en-ticket">' + cant + '</span>' : '') +
        '<span class="info"><span class="nom">' + esc(p.n) + '</span><span class="cod">' + esc(p.c) + (p.v && p.v.length ? ' · ' + p.v.length + (p.v.length === 1 ? ' opción' : ' opciones') : '') + '</span>' +
        '<span class="pie"><span class="pp">' + usd(p.precio) + '</span><span class="ps' + (bajo || sin ? ' bajo' : '') + '">' + (sin ? 'Agotado' : 'Quedan ' + st) + '</span></span></span></button>';
    }).join('');
    $('pv-sin-productos').hidden = lista.length > 0;
  }
  function agregar(id, vid) {
    var p = prod(id), i = -1;
    if (p.v && p.v.length && vid === undefined) { abrirVariante(p); return; }
    vid = vid || null;
    var x = vid ? p.v.filter(function (o) { return o[0] === vid; })[0] : null, tope = x ? x[3] : p.t, nom = p.n + (x ? ' ' + nomVar(x) : '');
    pv.lineas.forEach(function (l, k) { if (l.id === id && (l.v || null) === vid) i = k; });
    if (i >= 0) {
      if (pv.lineas[i].q >= tope) { avisar('Solo quedan ' + tope + ' de ' + nom + ' en tienda y ya están en el ticket', 'aviso'); return; }
      pv.lineas[i].q++;
    } else {
      if (!tope) { avisar(nom + ' está agotado en tienda', 'aviso'); return; }
      pv.lineas.push({ id: id, v: vid, q: 1, d: 0, p: null });
    }
    pv.edit = -1; pintarPV(); volverAlLector();
  }
  var mv = { p: null, talla: null, color: null };
  function libres(x) { return x[3] - pv.lineas.reduce(function (s, l) { return l.v === x[0] ? s + l.q : s; }, 0); }
  function abrirVariante(p) {
    mv = { p: p, talla: null, color: null };
    var tallas = unicos(p.v.map(function (x) { return x[1]; }));
    if (tallas.length === 1) mv.talla = tallas[0];
    $('mv-titulo').textContent = p.n;
    $('mv-precio').textContent = usd(p.precio) + ' · ' + bs(p.precio);
    pintarVariante();
    $('modal-variante').showModal();
    var primero = $('modal-variante').querySelector('.mv-ops button:not(:disabled)');
    if (primero) primero.focus();
  }
  function unicos(a) { return a.filter(function (x, i) { return a.indexOf(x) === i; }); }
  function pintarVariante() {
    var p = mv.p, tallas = unicos(p.v.map(function (x) { return x[1]; })), hayTalla = tallas.some(Boolean);
    var deTalla = p.v.filter(function (x) { return !hayTalla || x[1] === mv.talla; });
    var colores = unicos(deTalla.map(function (x) { return x[2]; })), hayColor = colores.some(Boolean);
    if (mv.talla !== null || !hayTalla) { if (colores.length === 1) mv.color = colores[0]; else if (colores.indexOf(mv.color) === -1) mv.color = null; }
    $('mv-g-talla').hidden = !hayTalla;
    $('mv-tallas').innerHTML = tallas.map(function (t) {
      var quedan = p.v.filter(function (x) { return x[1] === t; }).reduce(function (s, x) { return s + libres(x); }, 0);
      return '<button type="button" class="chip-btn" data-mv-talla="' + esc(t) + '" aria-pressed="' + (t === mv.talla) + '"' + (quedan > 0 ? '' : ' disabled') + '>' + esc(t || 'Única') + '</button>';
    }).join('');
    $('mv-g-color').hidden = !hayColor;
    $('mv-colores').innerHTML = (hayTalla && mv.talla === null) ? '<span class="mv-espera">Elige primero la talla</span>' : colores.map(function (c) {
      var x = deTalla.filter(function (o) { return o[2] === c; })[0], q = libres(x);
      return '<button type="button" class="chip-btn" data-mv-color="' + esc(c) + '" aria-pressed="' + (c === mv.color) + '"' + (q > 0 ? '' : ' disabled') + '>' + esc(c || 'Sin color') + '<span class="mv-n">' + q + '</span></button>';
    }).join('');
    var sel = elegida();
    $('mv-agregar').disabled = !sel || libres(sel) <= 0;
    $('mv-quedan').textContent = sel ? (libres(sel) > 0 ? 'Quedan ' + libres(sel) + ' de ' + nomVar(sel) + (libres(sel) < sel[3] ? ' (' + (sel[3] - libres(sel)) + ' ya en el ticket)' : '') : 'Las de ' + nomVar(sel) + ' ya están todas en el ticket') : '';
  }
  function elegida() {
    var p = mv.p; if (!p) return null;
    var hayTalla = p.v.some(function (x) { return x[1]; }), hayColor = p.v.some(function (x) { return x[2]; });
    if ((hayTalla && mv.talla === null) || (hayColor && mv.color === null)) return null;
    return p.v.filter(function (x) { return (!hayTalla || x[1] === mv.talla) && (!hayColor || x[2] === mv.color); })[0] || null;
  }
  $('modal-variante').addEventListener('click', function (e) {
    var b = e.target.closest('[data-mv-talla], [data-mv-color]'); if (!b) return;
    if (b.hasAttribute('data-mv-talla')) { mv.talla = b.getAttribute('data-mv-talla'); mv.color = null; }
    else mv.color = b.getAttribute('data-mv-color');
    pintarVariante();
    var sig = b.hasAttribute('data-mv-talla') ? $('mv-colores').querySelector('[aria-pressed="true"], button:not(:disabled)') : $('mv-agregar');
    if (sig && !sig.disabled) sig.focus();
  });
  $('mv-form').addEventListener('submit', function (e) {
    e.preventDefault();
    var x = elegida(); if (!x) return;
    $('modal-variante').close();
    agregar(mv.p.id, x[0]);
  });
  $('modal-variante').addEventListener('close', volverAlLector);
  var punteroFino = window.matchMedia && window.matchMedia('(pointer: fine)').matches;
  function volverAlLector() { if (punteroFino && paso === 'venta' && !document.querySelector('dialog[open]')) $('pv-scan').focus({ preventScroll: true }); }
  document.addEventListener('keydown', function (e) {
    if (paso !== 'venta' || document.querySelector('dialog[open]') || e.ctrlKey || e.metaKey || e.altKey || e.key.length !== 1) return;
    var a = document.activeElement;
    if (a && (a.matches('input, textarea, select') || a.isContentEditable)) return;
    $('pv-scan').focus();
  });
  $('pv-vaciar').addEventListener('click', function () {
    if (!pv.lineas.length) return;
    var n = pv.lineas.length;
    Panel.confirmar({ titulo: '¿Vaciar el ticket?', texto: 'Se quitan ' + (n === 1 ? 'el producto' : 'los ' + n + ' productos') + ' y el cliente. Esta acción no se puede deshacer.', aceptar: 'Vaciar ticket' }).then(function (si) {
      if (!si) { volverAlLector(); return; }
      pv.lineas = []; pv.cliente = null; pv.nuevoCliente = null; pv.edit = -1; pintarPV(); avisar('El ticket quedó vacío', 'ok'); volverAlLector();
    });
  });
  function quitar(i) { var l = pv.lineas[i], x = variante(l), n = prod(l.id).n + (x ? ' ' + nomVar(x) : ''); pv.lineas.splice(i, 1); pv.edit = -1; pintarPV(); avisar(n + ' quitado del ticket', 'info'); }

  $('pv-lineas').addEventListener('input', function (e) {
    var campo = e.target.getAttribute('data-campo'); if (!campo) return;
    var li = e.target.closest('[data-i]'), l = pv.lineas[+li.getAttribute('data-i')], p = prod(l.id), v = parseFloat(e.target.value), tope = topeL(l);
    if (campo === 'q') { if (!(v >= 1)) return; if (v > tope) { avisar('Solo quedan ' + tope + ' en tienda', 'aviso'); v = tope; e.target.value = v; } l.q = Math.floor(v); li.querySelector('[data-q]').textContent = l.q; }
    if (campo === 'd' && D.puedeDescuento) l.d = Math.max(0, Math.min(100, v || 0));
    if (campo === 'p' && D.puedeDescuento) { if (!(v >= 0)) return; l.p = Math.abs(v - p.precio) < 0.001 ? null : v; }
    li.querySelector('[data-tot]').textContent = usd(totalL(l));
    li.querySelector('[data-det]').innerHTML = detalleL(l);
    pintarTotales(); pintarGrid(); guardarActual();
  });
  $('p-punto_venta').addEventListener('click', function (e) {
    var t = function (s) { return e.target.closest(s); };
    var b;
    if ((b = t('[data-agregar]'))) return agregar(+b.getAttribute('data-agregar'));
    if ((b = t('[data-mas]'))) { var lm = pv.lineas[+b.getAttribute('data-mas')]; return agregar(lm.id, lm.v || null); }
    if ((b = t('[data-menos]'))) { var i = +b.getAttribute('data-menos'); if (pv.lineas[i].q <= 1) quitar(i); else { pv.lineas[i].q--; pintarPV(); } return; }
    if ((b = t('[data-editar]'))) { var k = +b.getAttribute('data-editar'); pv.edit = pv.edit === k ? -1 : k; pintarLineas(); if ($('pe-q')) $('pe-q').focus(); return; }
    if ((b = t('[data-pe-talla]'))) return cambiarPrenda(pv.edit, b.getAttribute('data-pe-talla'), null);
    if ((b = t('[data-pe-color]'))) { var xe = variante(pv.lineas[pv.edit]); return cambiarPrenda(pv.edit, xe[1], b.getAttribute('data-pe-color')); }
    if ((b = t('[data-quitar]'))) return quitar(+b.getAttribute('data-quitar'));
    if (t('[data-listo]')) { pv.edit = -1; pintarPV(); return; }
    if ((b = t('[data-pv-cat]'))) { pv.cat = b.getAttribute('data-pv-cat'); document.querySelectorAll('[data-pv-cat]').forEach(function (o) { o.setAttribute('aria-pressed', String(o === b)); }); pintarGrid(); return; }
    if ((b = t('[data-pv-vista]'))) {
      var v = b.getAttribute('data-pv-vista');
      $('pv-cuerpo').setAttribute('data-vista', v);
      document.querySelectorAll('.pv-tabs [data-pv-vista]').forEach(function (o) { o.setAttribute('aria-pressed', String(o.getAttribute('data-pv-vista') === v)); });
    }
  });
  var scan = $('pv-scan');
  scan.addEventListener('input', pintarGrid);
  scan.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    var q = scan.value.trim().toLowerCase(); if (!q) return;
    var exacto = productos.filter(function (p) { return p.c.toLowerCase() === q; })[0];
    var coinc = productos.filter(function (p) { return (p.n + ' ' + p.c).toLowerCase().indexOf(q) !== -1; });
    var p = exacto || (coinc.length === 1 ? coinc[0] : null);
    if (p) { scan.value = ''; agregar(p.id); pintarGrid(); }
    else avisar(coinc.length ? 'Hay ' + coinc.length + ' productos con ese nombre: tócalo en la lista' : 'No hay ningún producto con «' + scan.value + '»', coinc.length ? 'aviso' : 'error');
  });

  $('pv-espera').addEventListener('click', function () {
    if (!pv.lineas.length) { avisar('El ticket está vacío', 'aviso'); return; }
    var l = leerEspera();
    l.push({ quien: pv.cliente ? pv.cliente.n : 'Consumidor final', hora: new Date().toLocaleTimeString('es-VE', { hour: 'numeric', minute: '2-digit' }), cliente: pv.cliente, lineas: pv.lineas });
    guardarEspera(l);
    pv.lineas = []; pv.cliente = null; pv.nuevoCliente = null; pintarPV();
    avisar('Ticket en espera. Puedes atender al siguiente cliente.', 'ok');
  });
  $('pv-ver-espera').addEventListener('click', function () {
    var l = leerEspera();
    $('mes-lista').innerHTML = l.length ? l.map(function (t, i) {
      var tot = r2(t.lineas.reduce(function (s, x) { return prod(x.id) ? s + totalL(x) : s; }, 0));
      return '<div class="pv-espera-item"><div class="t-nombre"><b>' + esc(t.quien) + '</b><span class="cifra">' + t.lineas.length + ' productos, ' + usd(tot) + ', ' + esc(t.hora) + '</span></div><button type="button" class="btn btn-pri btn-peq" data-recuperar="' + i + '">Recuperar</button></div>';
    }).join('') : '<p class="muted">No hay ventas en espera.</p>';
    $('modal-espera').showModal();
  });
  $('mes-lista').addEventListener('click', function (e) {
    var b = e.target.closest('[data-recuperar]'); if (!b) return;
    var l = leerEspera(), t = l.splice(+b.getAttribute('data-recuperar'), 1)[0];
    if (pv.lineas.length) l.push({ quien: pv.cliente ? pv.cliente.n : 'Consumidor final', hora: 'antes', cliente: pv.cliente, lineas: pv.lineas });
    guardarEspera(l);
    pv.lineas = t.lineas.filter(function (x) { return prod(x.id); }); pv.cliente = t.cliente; pv.edit = -1;
    $('modal-espera').close(); pintarPV(); avisar('Ticket de ' + t.quien + ' recuperado', 'ok');
  });

  function abrirCliente() { $('mcl-num').value = ''; $('mcl-res').innerHTML = ''; $('modal-cliente').showModal(); $('mcl-num').focus(); }
  $('pv-cliente').addEventListener('click', abrirCliente);
  $('modal-cliente').addEventListener('close', function () { if (paso === 'cobro') pintarCobro(); volverAlLector(); });
  function buscarCli() {
    var tipo = $('mcl-tipo').value, num = $('mcl-num').value.replace(/\D/g, '');
    if (!num) { $('mcl-res').innerHTML = ''; return; }
    var doc = tipo + '-' + num;
    var res = clientes.filter(function (c) { return c.doc.charAt(0) === tipo && c.doc.replace(/\D/g, '') === num; });
    $('mcl-res').innerHTML = res.length ? res.map(function (c) {
      return '<button type="button" class="pv-cli-item" data-usar="' + c.id + '"><span class="avatar">' + esc(iniciales(c.n)) + '</span><span><b>' + esc(c.n) + '</b><span>' + esc(c.doc) + (c.tel ? ', ' + esc(c.tel) : '') + '</span></span></button>';
    }).join('') : '<div class="pv-nuevo-cli"><b style="font-size:13.5px">' + doc + ' no está registrado</b>' +
      '<div class="campo"><label for="mcl-nombre">Nombre o razón social</label><input id="mcl-nombre" autocomplete="off"></div>' +
      '<div class="campo"><label for="mcl-tel">Teléfono</label><input id="mcl-tel" type="tel" inputmode="tel" placeholder="0414-0000000"></div>' +
      '<div class="campo"><label for="mcl-dir">Dirección</label><textarea id="mcl-dir" rows="2"></textarea></div>' +
      '<button type="button" class="btn btn-pri" id="mcl-registrar" data-doc="' + doc + '">Registrar y usar</button></div>';
  }
  $('mcl-buscar').addEventListener('click', buscarCli);
  $('mcl-num').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); buscarCli(); } });
  $('mcl-res').addEventListener('click', function (e) {
    var u = e.target.closest('[data-usar]'), reg = e.target.closest('#mcl-registrar');
    if (u) { pv.cliente = clientes.filter(function (c) { return String(c.id) === u.getAttribute('data-usar'); })[0]; pv.nuevoCliente = null; }
    else if (reg) {
      var n = $('mcl-nombre'); if (!n.value.trim()) { n.focus(); return; }
      pv.nuevoCliente = { cedula: reg.getAttribute('data-doc'), nombre: n.value.trim(), telefono: $('mcl-tel').value.trim(), direccion: $('mcl-dir').value.trim() };
      pv.cliente = { id: 0, doc: pv.nuevoCliente.cedula, n: pv.nuevoCliente.nombre, tel: pv.nuevoCliente.telefono };
    } else return;
    $('modal-cliente').close(); pintarPV();
  });
  $('mcl-final').addEventListener('click', function () { pv.cliente = null; pv.nuevoCliente = null; $('modal-cliente').close(); pintarPV(); });

  var met = function (id) { return metodos.filter(function (m) { return m.id === id; })[0]; };
  var metodoInicial = function () { return (metodos.filter(function (m) { return m.mon === '$' && !m.ref; })[0] || metodos[0]).id; };
  var pUsd = function (p) { return met(p.m).mon === '$' ? p.monto : p.monto / TASA; };
  var fmtMon = function (v, mon) { return mon === '$' ? usd(v) : Panel.fmtBs(v) + ' Bs'; };
  var enMoneda = function (vUsd, mon) { return mon === '$' ? Math.ceil(vUsd * 100 - 0.0001) / 100 : r2(vUsd * TASA); };
  var icono = function (m) { return /m[oó]vil/i.test(m.n) ? 'movil' : m.ref ? 'banco' : 'efectivo'; };
  
  var cobro;
  function pagado() { return cobro.pagos.reduce(function (s, p) { return s + pUsd(p); }, 0); }
  function falta() { return Math.max(0, r2(totalPV() - pagado())); }
  function actualUsd() { return met(cobro.m).mon === '$' ? cobro.monto : cobro.monto / TASA; }
  
  function nuevoCobro() {
    cobro = {
      pagos: [],
      m: metodoInicial(),
      monto: 0,
      ref: '',
      banco: '',
      titular_nombre: pv.cliente ? (pv.cliente.n || '') : '',
      titular_cedula: pv.cliente ? (pv.cliente.doc || '') : '',
      titular_telefono: pv.cliente ? (pv.cliente.tel || '') : '',
      vueltoEn: '$'
    };
    cobro.monto = enMoneda(falta(), met(cobro.m).mon);
  }

  function elegir(id) {
    cobro.m = id;
    cobro.ref = '';
    cobro.banco = '';
    cobro.titular_nombre = pv.cliente ? (pv.cliente.n || '') : '';
    cobro.titular_cedula = pv.cliente ? (pv.cliente.doc || '') : '';
    cobro.titular_telefono = pv.cliente ? (pv.cliente.tel || '') : '';
    cobro.monto = enMoneda(falta(), met(id).mon);
  }

  function rapidos(m) {
    var f = falta(), exacto = enMoneda(f, m.mon);
    if (m.mon !== '$') return [[exacto, m.ref ? 'Todo: ' + fmtMon(exacto, m.mon) : 'Exacto']];
    var lista = [[exacto, 'Exacto']];
    [5, 10, 20, 50, 100].forEach(function (b) {
      var v = Math.ceil(f / b) * b;
      if (v > exacto + 0.004 && lista.length < 4 && !lista.some(function (x) { return x[0] === v; })) lista.push([v, '$' + v]);
    });
    return lista;
  }

  function pintarCobro() {
    var m = met(cobro.m), f = falta(), tot = totalPV(), hayPagos = cobro.pagos.length > 0;
    var cab = hayPagos
      ? '<div class="pc-cab"><span>Falta por cobrar</span><b>' + usd(f) + '</b><small>' + bs(f) + '</small><p>Total ' + usd(tot) + ', ya recibido ' + usd(Math.min(pagado(), tot)) + '</p></div>'
      : '<div class="pc-cab"><span>Cobrar</span><b>' + usd(tot) + '</b><small>' + bs(tot) + '</small></div>';
    
    var recibidos = hayPagos ? '<div class="pc-recibidos"><h4>Pagos recibidos</h4>' + cobro.pagos.map(function (p, i) {
      var mp = met(p.m);
      var detExtra = p.banco ? ' (' + esc(p.banco) + ')' : '';
      return '<div class="pc-recibido"><span class="pc-ok"><svg class="i"><use href="#i-check"/></svg></span><div><b>' + esc(mp.n) + detExtra + '</b><small>' + (p.ref ? 'Ref. ' + esc(p.ref) + ', ' : '') + (mp.mon === '$' ? '= ' + bs(p.monto) : '= ' + usd(p.monto / TASA)) + '</small></div><span class="pc-m">' + fmtMon(p.monto, mp.mon) + '</span><button type="button" class="ic-btn" data-quitar-pago="' + i + '" aria-label="Quitar pago de ' + esc(mp.n) + '"><svg class="i"><use href="#i-x"/></svg></button></div>';
    }).join('') + '</div>' : '';

    var tabs = '<div class="pc-tabs" role="radiogroup" aria-label="Método de pago">' + metodos.map(function (x) {
      return '<button type="button" role="radio" class="pc-tab" data-tab="' + x.id + '" aria-checked="' + (x.id === cobro.m) + '"><svg class="i"><use href="#i-' + icono(x) + '"/></svg>' + esc(x.n) + '</button>';
    }).join('') + '</div>';

    var recibe = '<div class="pc-recibe"><label for="pc-monto">' + (m.ref ? '¿Cuánto paga por ' + esc(m.n) + '?' : '¿Cuánto te da el cliente?') + '</label>' +
      (m.mon === '$' ? '<div class="pc-monto"><span>$</span><input id="pc-monto" type="text" inputmode="decimal" autocomplete="off" value="' + cobro.monto.toFixed(2) + '"></div>'
        : '<div class="pc-montos"><div class="pc-monto"><span>Bs</span><input id="pc-monto" type="text" inputmode="decimal" autocomplete="off" value="' + cobro.monto.toFixed(2) + '"></div>' +
          '<div class="pc-monto pc-usd"><span>$</span><input id="pc-monto-usd" type="text" inputmode="decimal" autocomplete="off" aria-label="El mismo monto en dólares" value="' + (cobro.monto / TASA).toFixed(2) + '"></div></div>' +
          '<p class="pc-conv">Si el cliente te dice el monto en dólares, escríbelo a la derecha y se convierte a tasa ' + TASA.toFixed(2) + '.</p>') +
      '<div class="pc-rapidos" id="pc-rapidos">' + rapidos(m).map(function (r) { return '<button type="button" data-rapido="' + r[0] + '" aria-pressed="' + (Math.abs(r[0] - cobro.monto) < 0.004) + '">' + r[1] + '</button>'; }).join('') + '</div></div>';

    var camposBancarios = '';
    if (m.ref) {
      var opcionesBancos = BANCOS_VZLA.map(function (b) {
        return '<option value="' + esc(b) + '"' + (cobro.banco === b ? ' selected' : '') + '>' + esc(b) + '</option>';
      }).join('');

      camposBancarios = '<div class="pv-bancarios-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:12px;">' +
        '<div class="campo" style="grid-column: span 2;">' +
          '<label for="pc-banco">Banco de la operación <span class="ob">*</span></label>' +
          '<select id="pc-banco" class="selector" style="width:100%; height:40px; background:#fff;">' +
            '<option value="">Selecciona el banco</option>' + opcionesBancos + '<option value="Otro"' + (cobro.banco === 'Otro' ? ' selected' : '') + '>Otro banco</option>' +
          '</select>' +
        '</div>' +
        '<div class="campo">' +
          '<label for="pc-titular">Nombre del titular <span class="ob">*</span></label>' +
          '<input id="pc-titular" autocomplete="off" placeholder="Como aparece en el banco" value="' + esc(cobro.titular_nombre) + '">' +
        '</div>' +
        '<div class="campo">' +
          '<label for="pc-cedula">Cédula del titular <span class="ob">*</span></label>' +
          '<input id="pc-cedula" autocomplete="off" placeholder="V-12345678" value="' + esc(cobro.titular_cedula) + '">' +
        '</div>' +
        '<div class="campo">' +
          '<label for="pc-telefono">Teléfono del titular <span class="ob">*</span></label>' +
          '<input id="pc-telefono" type="tel" autocomplete="off" placeholder="0414-0000000" value="' + esc(cobro.titular_telefono) + '">' +
        '</div>' +
        '<div class="campo" style="grid-column: span 2;">' +
          '<label for="pc-ref">Referencia del pago <span class="ob">*</span></label>' +
          '<input id="pc-ref" inputmode="numeric" autocomplete="off" value="' + esc(cobro.ref) + '" placeholder="La que aparece en el comprobante del cliente">' +
        '</div>' +
      '</div>';
    }

    $('pc-cuerpo').innerHTML = cab + recibidos + tabs + recibe + camposBancarios;
    pintarPie();
  }

  function pintarPie() {
    var m = met(cobro.m), f = falta(), a = actualUsd(), cubre = a + 0.004 >= f, sobra = r2(a - f), html = '';
    if (cubre && sobra > 0.004) {
      var v = cobro.vueltoEn;
      html += '<div class="pc-vuelto"><div><span>Vuelto para el cliente' + (v === 'pago_movil' ? ', por pago móvil' : '') + '</span><b>' + (v === '$' ? usd(sobra) : bs(sobra)) + '</b><small>' + (v === '$' ? '= ' + bs(sobra) : '= ' + usd(sobra)) + '</small></div>' +
        '<div class="pc-vuelto-en" role="group" aria-label="Dar el vuelto">' + [['$', 'En $'], ['Bs', 'En Bs'], ['pago_movil', 'Pago móvil']].map(function (o) { return '<button type="button" data-vuelto="' + o[0] + '" aria-pressed="' + (v === o[0]) + '">' + o[1] + '</button>'; }).join('') + '</div></div>';
    }

    var faltanDatosBancarios = false, motivo = '';
    if (m.ref && cobro.monto > 0) {
      if (!cobro.banco) { faltanDatosBancarios = true; motivo = 'Selecciona el banco de ' + m.n + '.'; }
      else if (!cobro.titular_nombre.trim()) { faltanDatosBancarios = true; motivo = 'Escribe el nombre del titular.'; }
      else if (!cobro.titular_cedula.trim()) { faltanDatosBancarios = true; motivo = 'Escribe la cédula del titular.'; }
      else if (!cobro.titular_telefono.trim()) { faltanDatosBancarios = true; motivo = 'Escribe el teléfono del titular.'; }
      else if (!cobro.ref.trim()) { faltanDatosBancarios = true; motivo = 'Falta la referencia de ' + m.n + '.'; }
    }

    if (cubre) {
      html += '<button type="button" class="pv-accion" id="pc-registrar"' + (motivo ? ' disabled' : '') + '><svg class="i"><use href="#i-check"/></svg>Registrar venta</button>';
    } else {
      if (!(cobro.monto > 0)) motivo = 'Escribe cuánto paga el cliente.';
      html += '<button type="button" class="pv-accion parcial" id="pc-agregar"' + (motivo ? ' disabled' : '') + '><svg class="i"><use href="#i-mas"/></svg>Agregar este pago (' + usd(a) + ')</button>';
      if (!motivo) motivo = 'Después de agregarlo te queda por cobrar ' + usd(f - a) + '.';
    }
    $('pc-pie').innerHTML = html + (motivo ? '<p class="pv-nota">' + motivo + '</p>' : '');
  }
  function enfocarMonto() { var i = $('pc-monto'); if (i && punteroFino) { i.focus(); i.select(); } }

  var paso = 'venta';
  function mostrar(p) {
    paso = p;
    $('pv-cuerpo').setAttribute('data-paso', p);
    document.querySelector('.pv-tabs').hidden = p !== 'venta';
    $('pv-cobro').hidden = p !== 'cobro'; $('pv-listo').hidden = p !== 'listo';
    $('pv-tk-titulo').textContent = 'Ticket'; $('pv-tk-meta').textContent = '';
  }
  $('pv-cobrar').addEventListener('click', function () { if (!pv.lineas.length) return; nuevoCobro(); mostrar('cobro'); pintarCobro(); enfocarMonto(); });
  $('pv-seguir').addEventListener('click', function () { mostrar('venta'); pintarPV(); volverAlLector(); });
  $('pv-cobro').addEventListener('click', function (e) {
    var t = function (s) { return e.target.closest(s); }, b;
    if ((b = t('[data-tab]'))) { elegir(+b.getAttribute('data-tab')); pintarCobro(); enfocarMonto(); return; }
    if ((b = t('[data-rapido]'))) { cobro.monto = +b.getAttribute('data-rapido'); pintarCobro(); return; }
    if ((b = t('[data-vuelto]'))) { cobro.vueltoEn = b.getAttribute('data-vuelto'); pintarPie(); return; }
    if ((b = t('[data-quitar-pago]'))) { cobro.pagos.splice(+b.getAttribute('data-quitar-pago'), 1); elegir(cobro.m); pintarCobro(); return; }
    if (t('#pc-agregar')) {
      cobro.pagos.push({
        m: cobro.m,
        monto: r2(cobro.monto),
        ref: cobro.ref.trim(),
        banco: cobro.banco,
        titular_nombre: cobro.titular_nombre.trim(),
        titular_cedula: cobro.titular_cedula.trim(),
        titular_telefono: cobro.titular_telefono.trim()
      });
      avisar('Pago de ' + met(cobro.m).n + ' agregado', 'ok');
      elegir(metodoInicial()); pintarCobro(); enfocarMonto(); return;
    }
    if (t('#pc-registrar')) registrar();
  });
  $('pv-cobro').addEventListener('input', function (e) {
    var num = function (v) { return Math.max(0, parseFloat(String(v).replace(/,/g, '.').replace(/[^\d.]/g, '')) || 0); };
    if (e.target.id === 'pc-monto-usd') { cobro.monto = r2(num(e.target.value) * TASA); $('pc-monto').value = cobro.monto.toFixed(2); document.querySelectorAll('#pc-rapidos [data-rapido]').forEach(function (b) { b.setAttribute('aria-pressed', String(Math.abs(+b.getAttribute('data-rapido') - cobro.monto) < 0.004)); }); pintarPie(); return; }
    if (e.target.id === 'pc-monto') {
      cobro.monto = num(e.target.value);
      if ($('pc-monto-usd')) $('pc-monto-usd').value = (cobro.monto / TASA).toFixed(2);
      document.querySelectorAll('#pc-rapidos [data-rapido]').forEach(function (b) { b.setAttribute('aria-pressed', String(Math.abs(+b.getAttribute('data-rapido') - cobro.monto) < 0.004)); });
      pintarPie();
    }
    if (e.target.id === 'pc-ref') { cobro.ref = e.target.value; pintarPie(); }
    if (e.target.id === 'pc-titular') { cobro.titular_nombre = e.target.value; pintarPie(); }
    if (e.target.id === 'pc-cedula') { cobro.titular_cedula = e.target.value; pintarPie(); }
    if (e.target.id === 'pc-telefono') { cobro.titular_telefono = e.target.value; pintarPie(); }
  });
  $('pv-cobro').addEventListener('change', function (e) {
    if (e.target.id === 'pc-banco') { cobro.banco = e.target.value; pintarPie(); }
  });
  $('pv-cobro').addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' || !e.target.matches('#pc-monto, #pc-monto-usd, #pc-ref, #pc-cedula, #pc-telefono')) return;
    e.preventDefault();
    var b = $('pc-registrar') || $('pc-agregar'); if (b && !b.disabled) b.click();
  });

  function registrar() {
    if (cobro.monto > 0) {
      cobro.pagos.push({
        m: cobro.m,
        monto: r2(cobro.monto),
        ref: cobro.ref.trim(),
        banco: cobro.banco,
        titular_nombre: cobro.titular_nombre.trim(),
        titular_cedula: cobro.titular_cedula.trim(),
        titular_telefono: cobro.titular_telefono.trim()
      });
    }
    var f = $('form-venta'), campos = [];
    var add = function (n, v) { campos.push('<input type="hidden" name="' + n + '" value="' + esc(v) + '">'); };
    if (pv.cliente && pv.cliente.id) add('id_usuario', pv.cliente.id);
    if (pv.nuevoCliente) Object.keys(pv.nuevoCliente).forEach(function (k) { add('nuevo_cliente[' + k + ']', pv.nuevoCliente[k]); });
    pv.lineas.forEach(function (l) { add('id_producto[]', l.id); add('id_variante[]', l.v || ''); add('cantidad[]', l.q); add('precio_unitario[]', precioL(l)); add('descuento[]', l.d); });
    cobro.pagos.forEach(function (p) {
      add('id_metodo_pago[]', p.m);
      add('monto[]', p.monto);
      add('tasa_cambio[]', TASA);
      add('referencia[]', p.ref || '');
      add('banco[]', p.banco || '');
      add('titular_nombre[]', p.titular_nombre || '');
      add('titular_cedula[]', p.titular_cedula || '');
      add('titular_telefono[]', p.titular_telefono || '');
    });
    var dif = r2(pagado() - totalPV());
    add('total', totalPV());
    add('vuelto', dif > 0.004 ? dif : 0);
    add('vuelto_moneda', dif > 0.004 ? (cobro.vueltoEn === '$' ? 'USD' : cobro.vueltoEn) : '');
    f.innerHTML = campos.join('');
    var b = $('pc-registrar'); b.disabled = true; b.textContent = 'Registrando…';
    f.submit();
  }

  function textoVuelto(v) { return +v.vuelto > 0 ? (v.vuelto_moneda === 'USD' ? usd(v.vuelto) : bs(v.vuelto) + (v.vuelto_moneda === 'pago_movil' ? ' por pago móvil' : '')) : ''; }
  function mensajeTexto(v, tienda, num) {
    var lineas = (v.lineas || []).map(function (l) { return l.cantidad + ' x ' + l.nombre + ': ' + usd(l.subtotal); }).join('\n');
    var pagos = (v.pagos || []).map(function (p) { return p.metodo + (p.referencia ? ' (ref. ' + p.referencia + ')' : '') + ': ' + (p.moneda === 'USD' ? usd(p.monto) : Panel.fmtBs(p.monto) + ' Bs'); }).join('\n');
    return '*' + tienda.nombre.toUpperCase() + '*' + (tienda.rif ? '\nRIF ' + tienda.rif : '') + (tienda.direccion ? '\n' + tienda.direccion : '') + (tienda.telefono ? '\n' + tienda.telefono : '') +
      '\n\n*NOTA DE ENTREGA N.º ' + num + '*\nFecha: ' + (v.fecha || '') + '\nCliente: ' + (v.cliente || 'Consumidor final') + (v.documento ? ' (' + v.documento + ')' : '') +
      '\n\n' + lineas + '\n\n*TOTAL: ' + usd(v.total) + '*\nEquivalente: ' + bs(v.total) + ' (tasa BCV ' + TASA.toFixed(2) + ')\n\nPagos:\n' + pagos + (textoVuelto(v) ? '\nVuelto: ' + textoVuelto(v) : '') +
      (v.atendio || D.cajero ? '\nAtendido por: ' + (v.atendio || D.cajero) : '') + '\n\nDocumento no fiscal. Si necesita factura fiscal, solicítela en caja.\n¡Gracias por su compra!';
  }

  function cargarLogo(src) {
    return new Promise(function (ok) {
      var im = new Image(); im.onload = function () { ok(im); }; im.onerror = function () { ok(null); }; im.src = src;
    });
  }
  function logoVerde(im, color) {
    var c = document.createElement('canvas'), w = im.naturalWidth, h = im.naturalHeight; c.width = w; c.height = h;
    var x = c.getContext('2d'); x.drawImage(im, 0, 0);
    var d = x.getImageData(0, 0, w, h), px = d.data, t = h, b = -1, l = w, r = -1;
    var rgb = [parseInt(color.substr(1, 2), 16), parseInt(color.substr(3, 2), 16), parseInt(color.substr(5, 2), 16)];
    for (var y = 0; y < h; y++) for (var i = 0; i < w; i++) {
      var k = (y * w + i) * 4, tinta = px[k + 3] > 40 && Math.min(px[k], px[k + 1], px[k + 2]) < 200;
      if (tinta) { px[k] = rgb[0]; px[k + 1] = rgb[1]; px[k + 2] = rgb[2]; if (y < t) t = y; if (y > b) b = y; if (i < l) l = i; if (i > r) r = i; } else px[k + 3] = 0;
    }
    if (b < 0) return null;
    x.putImageData(d, 0, 0);
    var o = document.createElement('canvas'); o.width = r - l + 1; o.height = b - t + 1;
    o.getContext('2d').drawImage(c, l, t, o.width, o.height, 0, 0, o.width, o.height);
    return o;
  }
  function envolver(ctx, texto, ancho) {
    var palabras = String(texto).split(' '), lineas = [], linea = '';
    palabras.forEach(function (p) { var prueba = linea ? linea + ' ' + p : p; if (ctx.measureText(prueba).width > ancho && linea) { lineas.push(linea); linea = p; } else linea = prueba; });
    if (linea) lineas.push(linea);
    return lineas;
  }
  function recortar(ctx, texto, ancho) {
    if (ctx.measureText(texto).width <= ancho) return texto;
    while (texto.length > 1 && ctx.measureText(texto + '…').width > ancho) texto = texto.slice(0, -1);
    return texto + '…';
  }

  function dibujarTicket(v, num) {
    var T = D.tienda, W = 420, P = 26, E = 2, VERDE = '#1d6a4f', TX = '#0f172a', T2 = '#475569', T3 = '#64748b';
    var fuentes = ['700 22px Outfit', '700 15px Outfit', '700 30px Outfit', '600 16px Outfit', '400 13px "Open Sans"', '600 13px "Open Sans"', '700 13px "Open Sans"'];
    return Promise.all(fuentes.map(function (f) { return document.fonts ? document.fonts.load(f) : null; }).concat([cargarLogo(T.logo)])).then(function (res) {
      var logo = res[res.length - 1], marca = logo ? logoVerde(logo, VERDE) : null;
      var med = document.createElement('canvas').getContext('2d');
      med.font = '400 12.5px "Open Sans"';
      var datos = [T.rif ? 'RIF ' + T.rif : '', T.direccion, T.telefono].filter(Boolean).reduce(function (a, l) { return a.concat(envolver(med, l, W - P * 2)); }, []);
      med.font = '400 12px "Open Sans"';
      var nota = envolver(med, 'Documento no fiscal. Si necesita factura fiscal, solicítela en caja.', W - P * 2);
      var fecha = v.fecha || '', atendio = v.atendio || D.cajero || '';
      var meta = [['Fecha', fecha], ['Cliente', v.cliente || 'Consumidor final']];
      if (v.documento) meta.push(['C.I. / RIF', v.documento]);
      if (atendio) meta.push(['Atendió', atendio]);
      var lineas = v.lineas || [], pagos = (v.pagos || []).map(function (p) { return [p.metodo + (p.referencia ? ', ref. ' + p.referencia : ''), p.moneda === 'USD' ? usd(p.monto) : Panel.fmtBs(p.monto) + ' Bs']; });
      if (textoVuelto(v)) pagos.push(['Vuelto' + (v.vuelto_moneda === 'USD' ? ' en dólares' : v.vuelto_moneda === 'pago_movil' ? ' por pago móvil' : ' en bolívares'), v.vuelto_moneda === 'USD' ? usd(v.vuelto) : bs(v.vuelto)]);
      var H = 26 + (marca ? 64 : 0) + 30 + datos.length * 18 + 28 + 24 + meta.length * 19 + 28 + 22 + lineas.length * 36 + 28 + 58 + 28 + pagos.length * 19 + 28 + 26 + nota.length * 16 + 24;
      var c = document.createElement('canvas'); c.width = W * E; c.height = (H + 12) * E;
      var x = c.getContext('2d'); x.scale(E, E);
      x.fillStyle = '#fff'; x.beginPath(); x.moveTo(0, 0); x.lineTo(W, 0); x.lineTo(W, H);
      for (var z = W; z > 0; z -= 16) { x.lineTo(z - 8, H + 10); x.lineTo(z - 16, H); }
      x.closePath(); x.fill();
      var y = 26;
      var linea = function () { y += 14; x.strokeStyle = '#b9c4be'; x.lineWidth = 1.5; x.setLineDash([5, 4]); x.beginPath(); x.moveTo(P, y); x.lineTo(W - P, y); x.stroke(); x.setLineDash([]); y += 14; };
      var fila = function (izq, der, fi, fd, ci, cd) { x.font = fi; x.fillStyle = ci; x.textAlign = 'left'; x.fillText(recortar(x, izq, 230), P, y); x.font = fd; x.fillStyle = cd; x.textAlign = 'right'; x.fillText(der, W - P, y); x.textAlign = 'left'; };
      x.textAlign = 'center';
      if (marca) { var s = Math.min(56 / marca.height, 120 / marca.width); x.drawImage(marca, W / 2 - marca.width * s / 2, y, marca.width * s, marca.height * s); y += 64; }
      x.fillStyle = TX; x.font = '700 22px Outfit'; x.fillText(recortar(x, T.nombre.toUpperCase(), W - P * 2), W / 2, y + 20); y += 30;
      x.fillStyle = T2; x.font = '400 12.5px "Open Sans"'; datos.forEach(function (l) { x.fillText(l, W / 2, y + 12); y += 18; });
      linea();
      x.fillStyle = TX; x.font = '700 15px Outfit'; x.textAlign = 'center'; x.fillText('NOTA DE ENTREGA N.º ' + num, W / 2, y + 12); y += 24;
      meta.forEach(function (m) { y += 14; fila(m[0], recortar(x, m[1], 250), '400 13px "Open Sans"', '600 13px "Open Sans"', T3, TX); y += 5; });
      linea();
      x.font = '700 11px "Open Sans"'; x.fillStyle = T3; x.textAlign = 'left'; x.fillText('CANT. Y PRODUCTO', P, y + 10); x.textAlign = 'right'; x.fillText('TOTAL', W - P, y + 10); x.textAlign = 'left'; y += 22;
      lineas.forEach(function (l) {
        y += 14; fila(l.cantidad + ' × ' + l.nombre, usd(l.subtotal), '400 13px "Open Sans"', '400 13px "Open Sans"', TX, TX);
        x.font = '400 12px "Open Sans"'; x.fillStyle = T3; x.fillText(usd(+l.subtotal / Math.max(1, +l.cantidad)) + ' c/u' + (+l.descuento ? ', −' + (+l.descuento) + '%' : ''), P, y + 16); y += 22;
      });
      linea();
      y += 26; fila('TOTAL', usd(v.total), '600 16px Outfit', '700 30px Outfit', TX, TX);
      y += 22; fila('Equivalente a tasa BCV ' + TASA.toFixed(2), bs(v.total), '400 13px "Open Sans"', '400 13px "Open Sans"', T2, T2); y += 10;
      linea();
      pagos.forEach(function (p) { y += 14; fila(p[0], p[1], '400 13px "Open Sans"', '700 13px "Open Sans"', T2, TX); y += 5; });
      linea();
      x.textAlign = 'center'; x.fillStyle = TX; x.font = '700 16px Outfit'; x.fillText('¡Gracias por su compra!', W / 2, y + 16); y += 26;
      x.fillStyle = T3; x.font = '400 12px "Open Sans"'; nota.forEach(function (l) { x.fillText(l, W / 2, y + 12); y += 16; });
      return new Promise(function (ok) { c.toBlob(function (b) { ok({ blob: b, url: c.toDataURL('image/png') }); }, 'image/png'); });
    });
  }

  function telefonoWa(t) { var d = String(t || '').replace(/\D/g, ''); if (d.charAt(0) === '0') d = '58' + d.slice(1); return /^58\d{10}$/.test(d) ? d : ''; }
  function comprobante(v) {
    var num = String(v.id).padStart(4, '0');
    mostrar('listo');
    $('pv-tk-titulo').textContent = 'Venta #' + num;
    $('pv-tk-meta').textContent = (v.fecha || '').replace(/\.$/, '') + ((v.atendio || D.cajero) ? '. Atendió ' + (v.atendio || D.cajero) : '');
    $('pv-cliente').classList.add('con-cliente');
    $('pv-cliente').innerHTML = '<span class="avatar">' + esc(v.cliente ? iniciales(v.cliente) : 'CF') + '</span><span><b>' + esc(v.cliente || 'Consumidor final') + '</b><span class="cifra">' + esc(v.documento || (v.cliente ? '' : 'Sin cédula ni RIF')) + '</span></span>';
    $('pv-lineas').innerHTML = (v.lineas || []).map(function (l) { return '<li class="pv-linea-r"><span class="cifra">' + l.cantidad + '×</span><span>' + esc(l.nombre) + (+l.descuento ? ' <span class="desc">−' + (+l.descuento) + '%' : '') + '</span><b class="cifra">' + usd(l.subtotal) + '</b></li>'; }).join('');
    $('pv-art').textContent = (v.lineas || []).reduce(function (s, l) { return s + +l.cantidad; }, 0);
    $('pv-fila-desc').hidden = true;
    $('pv-total').textContent = usd(v.total); $('pv-bs').textContent = bs(v.total);
    $('pv-pagos-r').innerHTML = (v.pagos || []).map(function (p) { return '<div><span>' + esc(p.metodo) + (p.referencia ? ', ref. ' + esc(p.referencia) : '') + '</span><b class="cifra">' + (p.moneda === 'USD' ? usd(p.monto) : Panel.fmtBs(p.monto) + ' Bs') + '</b></div>'; }).join('') +
      (textoVuelto(v) ? '<div><span>Vuelto</span><b class="cifra">' + textoVuelto(v) + '</b></div>' : '');
    $('pl-titulo').textContent = 'Venta #' + num + ' registrada';
    $('pl-entrega').hidden = !textoVuelto(v);
    if (textoVuelto(v)) {
      $('pl-vuelto').textContent = v.vuelto_moneda === 'USD' ? usd(v.vuelto) : bs(v.vuelto);
      $('pl-vuelto-det').textContent = v.vuelto_moneda === 'USD' ? 'en efectivo, en dólares' : v.vuelto_moneda === 'pago_movil' ? 'por pago móvil, en bolívares' : 'en efectivo, en bolívares';
    }
    var msg = mensajeTexto(v, D.tienda, num), tel = $('pl-tel'), imagen = null;
    tel.value = v.telefono || '';
    var puedeCompartir = !!(navigator.canShare && window.File && navigator.canShare({ files: [new File([''], 'x.png', { type: 'image/png' })] }));
    $('pl-compartir-txt').textContent = puedeCompartir ? 'Compartir comprobante' : 'Descargar y abrir WhatsApp';
    var actualizar = function () {
      var d = telefonoWa(tel.value), crudo = tel.value.replace(/\D/g, '');
      $('pl-texto').href = 'https://wa.me/' + d + '?text=' + encodeURIComponent(msg);
      $('pl-tel-ayuda').textContent = !crudo ? 'Si lo dejas vacío, WhatsApp te deja elegir el contacto.' : d ? 'Se abrirá el chat con +' + d + '.' : 'Revisa el número: debe tener 11 dígitos, como 0414-1234567.';
    };
    tel.addEventListener('input', actualizar); actualizar();
    var lista = dibujarTicket(v, num).then(function (r) { imagen = r; $('pl-img').src = r.url; return r; });
    $('pl-compartir').addEventListener('click', function () {
      lista.then(function (r) {
        var nombre = 'comprobante-' + num + '.png', archivo = new File([r.blob], nombre, { type: 'image/png' });
        if (puedeCompartir) { navigator.share({ files: [archivo], text: D.tienda.nombre + ', nota de entrega N.º ' + num }).catch(function () {}); return; }
        var a = document.createElement('a'); a.href = r.url; a.download = nombre; document.body.appendChild(a); a.click(); a.remove();
        window.open('https://wa.me/' + telefonoWa(tel.value) + '?text=' + encodeURIComponent('Hola, te envío el comprobante de tu compra en ' + D.tienda.nombre + ' (nota de entrega N.º ' + num + ').'), '_blank', 'noopener');
        avisar('Se descargó el comprobante. Adjúntalo en el chat de WhatsApp.', 'info', 7);
      });
    });
    document.addEventListener('keydown', function (e) {
      if (paso !== 'listo' || e.key !== 'Enter' || document.querySelector('dialog[open]') || (document.activeElement && document.activeElement.matches('input, textarea, select, a, button'))) return;
      e.preventDefault(); $('pl-nueva').click();
    });
    $('pl-nueva').focus({ preventScroll: true });
  }

  if (D.venta) { try { localStorage.removeItem('pv-actual'); } catch (e) {} comprobante(D.venta); }
  else { var habia = recuperarActual(); pintarPV(); if (habia) avisar('Recuperamos el ticket que tenías abierto', 'info'); volverAlLector(); }
})();