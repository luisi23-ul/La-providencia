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
  var cantEn = function (id) { var l = pv.lineas.filter(function (x) { return x.id === id; })[0]; return l ? l.q : 0; };
  var iniciales = function (n) { return n.split(' ').map(function (w) { return w[0]; }).slice(0, 2).join('').toUpperCase(); };

  function leerEspera() { try { return JSON.parse(localStorage.getItem('pv-espera')) || []; } catch (e) { return []; } }
  function guardarEspera(l) { try { localStorage.setItem('pv-espera', JSON.stringify(l)); } catch (e) {} }

  function pintarPV() {
    var c = pv.cliente;
    $('pv-cliente').innerHTML = '<span class="avatar">' + (c ? esc(iniciales(c.n)) : 'CF') + '</span><span><b>' + (c ? esc(c.n) : 'Consumidor final') + '</b><span class="cifra">' + (c ? esc(c.doc) : 'Sin cédula ni RIF') + '</span></span><span class="cambiar">' + (c ? 'Cambiar' : 'Agregar cliente') + '</span>';
    pintarLineas(); pintarTotales(); pintarGrid();
  }
  function detalleL(l) { return usd(precioL(l)) + ' c/u' + (l.p != null ? ', precio cambiado' : '') + (l.d ? ' <span class="desc">−' + l.d + '%</span>' : ''); }
  function pintarLineas() {
    var ul = $('pv-lineas');
    if (!pv.lineas.length) { ul.innerHTML = '<li class="pv-vacio">Escanea un código o toca un producto para empezar la venta.</li>'; return; }
    ul.innerHTML = pv.lineas.map(function (l, i) {
      var p = prod(l.id), abierta = i === pv.edit;
      return '<li class="pv-linea' + (abierta ? ' abierta' : '') + '" data-i="' + i + '">' +
        '<div class="pl-fila1"><b>' + esc(p.n) + '</b><span class="tot" data-tot>' + usd(totalL(l)) + '</span></div>' +
        '<div class="pl-fila2"><span class="stepper"><button type="button" data-menos="' + i + '" aria-label="Uno menos">−</button><span data-q>' + l.q + '</span><button type="button" data-mas="' + i + '" aria-label="Uno más"' + (l.q >= p.t ? ' disabled' : '') + '>+</button></span>' +
        '<span class="det" data-det>' + detalleL(l) + '</span>' +
        '<button type="button" class="ic-btn" data-editar="' + i + '" aria-expanded="' + abierta + '" aria-label="Editar cantidad, descuento o precio"><svg class="i"><use href="#i-editar"/></svg></button></div>' +
        (abierta ? '<div class="pl-editor">' +
          '<div class="campo"><label for="pe-q">Cantidad</label><input id="pe-q" type="number" min="1" max="' + p.t + '" inputmode="numeric" data-campo="q" value="' + l.q + '"><span class="ayuda">Quedan ' + p.t + '</span></div>' +
          '<div class="campo"><label for="pe-d">Descuento %</label><input id="pe-d" type="number" min="0" max="100" inputmode="numeric" data-campo="d" value="' + l.d + '"></div>' +
          '<div class="campo"><label for="pe-p">Precio $</label><input id="pe-p" type="number" min="0" step="0.01" inputmode="decimal" data-campo="p" value="' + precioL(l).toFixed(2) + '"><span class="ayuda">Normal ' + usd(p.precio) + '</span></div>' +
          '<div class="pl-editor-acc"><button type="button" class="btn btn-peligro btn-peq" data-quitar="' + i + '">Quitar del ticket</button><button type="button" class="btn btn-pri btn-peq" data-listo>Listo</button></div>' +
        '</div>' : '') + '</li>';
    }).join('');
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
      var cant = cantEn(p.id), sin = p.t === 0, bajo = p.t > 0 && p.t < p.min;
      return '<button type="button" class="pv-prod" data-agregar="' + p.id + '"' + (sin || cant >= p.t ? ' disabled' : '') + ' aria-label="Agregar ' + esc(p.n) + '">' +
        (p.img ? '<img src="' + esc(p.img) + '" alt="" loading="lazy">' : '<span class="pv-sin-foto"></span>') + (cant ? '<span class="en-ticket">' + cant + '</span>' : '') +
        '<span class="info"><span class="nom">' + esc(p.n) + '</span><span class="cod">' + esc(p.c) + '</span>' +
        '<span class="pie"><span class="pp">' + usd(p.precio) + '</span><span class="ps' + (bajo || sin ? ' bajo' : '') + '">' + (sin ? 'Agotado' : 'Quedan ' + p.t) + '</span></span></span></button>';
    }).join('');
    $('pv-sin-productos').hidden = lista.length > 0;
  }
  function agregar(id) {
    var p = prod(id), i = -1;
    pv.lineas.forEach(function (l, k) { if (l.id === id) i = k; });
    if (i >= 0) {
      if (pv.lineas[i].q >= p.t) { avisar('Solo quedan ' + p.t + ' ' + p.n + ' en tienda'); return; }
      pv.lineas[i].q++;
    } else {
      if (!p.t) { avisar(p.n + ' está agotado en tienda'); return; }
      pv.lineas.push({ id: id, q: 1, d: 0, p: null });
    }
    pv.edit = -1; pintarPV();
  }
  function quitar(i) { var n = prod(pv.lineas[i].id).n; pv.lineas.splice(i, 1); pv.edit = -1; pintarPV(); avisar(n + ' quitado del ticket'); }

  $('pv-lineas').addEventListener('input', function (e) {
    var campo = e.target.getAttribute('data-campo'); if (!campo) return;
    var li = e.target.closest('[data-i]'), l = pv.lineas[+li.getAttribute('data-i')], p = prod(l.id), v = parseFloat(e.target.value);
    if (campo === 'q') { if (!(v >= 1)) return; if (v > p.t) { avisar('Solo quedan ' + p.t + ' en tienda'); v = p.t; e.target.value = v; } l.q = Math.floor(v); li.querySelector('[data-q]').textContent = l.q; }
    if (campo === 'd') l.d = Math.max(0, Math.min(100, v || 0));
    if (campo === 'p') { if (!(v >= 0)) return; l.p = Math.abs(v - p.precio) < 0.001 ? null : v; }
    li.querySelector('[data-tot]').textContent = usd(totalL(l));
    li.querySelector('[data-det]').innerHTML = detalleL(l);
    pintarTotales(); pintarGrid();
  });
  $('p-punto_venta').addEventListener('click', function (e) {
    var t = function (s) { return e.target.closest(s); };
    var b;
    if ((b = t('[data-agregar]'))) return agregar(+b.getAttribute('data-agregar'));
    if ((b = t('[data-mas]'))) return agregar(pv.lineas[+b.getAttribute('data-mas')].id);
    if ((b = t('[data-menos]'))) { var i = +b.getAttribute('data-menos'); if (pv.lineas[i].q <= 1) quitar(i); else { pv.lineas[i].q--; pintarPV(); } return; }
    if ((b = t('[data-editar]'))) { var k = +b.getAttribute('data-editar'); pv.edit = pv.edit === k ? -1 : k; pintarLineas(); if ($('pe-q')) $('pe-q').focus(); return; }
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
    if (p) { agregar(p.id); scan.value = ''; pintarGrid(); }
    else avisar(coinc.length ? 'Hay ' + coinc.length + ' productos con ese nombre: tócalo en la lista' : 'No hay ningún producto con «' + scan.value + '»');
  });

  $('pv-espera').addEventListener('click', function () {
    if (!pv.lineas.length) { avisar('El ticket está vacío'); return; }
    var l = leerEspera();
    l.push({ quien: pv.cliente ? pv.cliente.n : 'Consumidor final', hora: new Date().toLocaleTimeString('es-VE', { hour: 'numeric', minute: '2-digit' }), cliente: pv.cliente, lineas: pv.lineas });
    guardarEspera(l);
    pv.lineas = []; pv.cliente = null; pv.nuevoCliente = null; pintarPV();
    avisar('Ticket en espera. Puedes atender al siguiente cliente.');
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
    $('modal-espera').close(); pintarPV(); avisar('Ticket de ' + t.quien + ' recuperado');
  });

  $('pv-cliente').addEventListener('click', function () { $('mcl-num').value = ''; $('mcl-res').innerHTML = ''; $('modal-cliente').showModal(); $('mcl-num').focus(); });
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

  var cobro = { pagos: [], abierto: -1, vueltoEn: '$', enfocar: false };
  var met = function (id) { return metodos.filter(function (m) { return m.id === id; })[0]; };
  var pUsd = function (p) { return met(p.m).mon === '$' ? p.monto : p.monto / TASA; };
  var pagado = function () { return cobro.pagos.reduce(function (s, p) { return s + pUsd(p); }, 0); };
  var saldo = function () { return r2(pagado() - totalPV()); };
  var fmtMon = function (v, mon) { return mon === '$' ? usd(v) : Panel.fmtBs(v) + ' Bs'; };
  var enMoneda = function (vUsd, mon) { return mon === '$' ? Math.ceil(vUsd * 100 - 0.0001) / 100 : r2(vUsd * TASA); };
  var faltaSin = function (i) { var otros = cobro.pagos.reduce(function (s, p, k) { return k === i ? s : s + pUsd(p); }, 0); return Math.max(0, totalPV() - otros); };
  var equivalente = function (p) { return met(p.m).mon === '$' ? '= ' + bs(p.monto) : '= ' + usd(p.monto / TASA); };
  function ayuda(i) {
    var p = cobro.pagos[i], m = met(p.m), falta = enMoneda(faltaSin(i), m.mon), html = '<span class="cifra">' + equivalente(p) + '</span>';
    if (Math.abs(falta - p.monto) > 0.004) html += '<span>Para completar el pago: <b class="cifra">' + fmtMon(falta, m.mon) + '</b></span><button type="button" class="pc-link" data-todo="' + i + '">Cobrar todo con este método</button>';
    return html;
  }
  function tarjeta(p, i) {
    var m = met(p.m);
    if (i !== cobro.abierto) {
      var faltaRef = m.ref && !p.ref.trim();
      return '<article class="pc-pago cerrado"><span class="ok-mini' + (faltaRef ? ' pend' : '') + '"><svg class="i"><use href="#i-' + (faltaRef ? 'alerta' : 'check') + '"/></svg></span>' +
        '<div class="que"><b>' + esc(m.n) + '</b><small>' + equivalente(p) + (m.ref ? (p.ref ? ', ref. ' + esc(p.ref) : ' <span class="falta-ref">falta la referencia</span>') : '') + '</small></div>' +
        '<span class="mc">' + fmtMon(p.monto, m.mon) + '</span><button type="button" class="btn btn-sec btn-peq" data-p-editar="' + i + '">Editar</button>' +
        '<button type="button" class="ic-btn" data-p-quitar="' + i + '" aria-label="Quitar pago"><svg class="i"><use href="#i-x"/></svg></button></article>';
    }
    var billetes = m.mon === '$' && !m.ref ? '<div class="pc-billetes"><span class="muted">Si te da un billete, tócalo y te digo el vuelto:</span>' + [1, 5, 10, 20, 50, 100].map(function (b) { return '<button type="button" data-billete="' + b + '" aria-pressed="' + (p.monto === b) + '">$' + b + '</button>'; }).join('') + '</div>' : '';
    var ref = m.ref ? '<div class="campo pc-ref"><label for="pc-ref-' + i + '">Referencia <span class="ob">*</span></label><input id="pc-ref-' + i + '" data-p-ref="' + i + '" inputmode="numeric" autocomplete="off" value="' + esc(p.ref) + '" class="' + (p.ref.trim() ? '' : 'falta') + '" placeholder="La que aparece en el comprobante del cliente"></div>' : '';
    return '<article class="pc-pago abierto"><header><b>' + esc(m.n) + '</b><span>en ' + (m.mon === '$' ? 'dólares' : 'bolívares') + '</span><button type="button" class="ic-btn" data-p-quitar="' + i + '" aria-label="Quitar pago"><svg class="i"><use href="#i-x"/></svg></button></header>' +
      '<label class="pc-lbl" for="pc-monto-' + i + '">¿Cuánto paga con ' + esc(m.n) + '?</label>' +
      '<div class="pc-monto"><input id="pc-monto-' + i + '" data-p-monto="' + i + '" type="text" inputmode="decimal" autocomplete="off" value="' + p.monto.toFixed(2) + '"><span>' + m.mon + '</span></div>' +
      '<div class="pc-ayuda" data-ayuda>' + ayuda(i) + '</div>' + billetes + ref +
      '<div class="pc-card-acc"><button type="button" class="btn btn-sec btn-peq" data-p-listo>Listo</button></div></article>';
  }
  function pintarCobro() {
    var tot = totalPV();
    $('pc-total').textContent = usd(tot);
    $('pc-bs').textContent = bs(tot) + ' a tasa ' + TASA.toFixed(2);
    $('pc-cli').textContent = pv.cliente ? pv.cliente.n + ' ' + pv.cliente.doc : 'Consumidor final';
    $('pc-pagos').innerHTML = cobro.pagos.map(tarjeta).join('');
    $('pc-metodos').innerHTML = metodos.map(function (m) { return '<button type="button" class="pv-metodo" data-metodo="' + m.id + '"><b>' + esc(m.n) + '</b><span>En ' + (m.mon === '$' ? 'dólares' : 'bolívares') + '</span></button>'; }).join('');
    estado();
    if (cobro.enfocar) { cobro.enfocar = false; var inp = $('pc-monto-' + cobro.abierto); if (inp) { inp.focus(); inp.select(); } }
  }
  function estado() {
    var tot = totalPV(), pag = pagado(), dif = saldo(), caja = $('pc-saldo');
    $('pc-prog').style.width = Math.min(100, tot ? pag / tot * 100 : 0) + '%';
    $('pc-prog-txt').textContent = 'Pagado ' + usd(Math.min(pag, tot)) + ' de ' + usd(tot);
    $('pc-elegir-t').textContent = !cobro.pagos.length ? '¿Cómo paga el cliente?' : dif < -0.004 ? 'Falta ' + usd(-dif) + '. ¿Con qué método paga el resto?' : '¿Paga una parte con otro método? Agrégalo aquí';
    if (!cobro.pagos.length || dif < -0.004) {
      var f = cobro.pagos.length ? -dif : tot;
      caja.className = 'pc-saldo falta';
      caja.innerHTML = '<span>Falta por cobrar</span><b>' + usd(f) + '</b><small>' + bs(f) + '</small>';
    } else if (dif > 0.004) {
      caja.className = 'pc-saldo vuelto';
      caja.innerHTML = '<span>Vuelto para el cliente' + (cobro.vueltoEn === 'pago_movil' ? ' (por pago móvil)' : '') + '</span><b>' + (cobro.vueltoEn === '$' ? usd(dif) : bs(dif)) + '</b><small>' + (cobro.vueltoEn === '$' ? '= ' + bs(dif) : '= ' + usd(dif)) + '</small>' +
        '<div class="pv-vuelto-en" role="group" aria-label="Dar el vuelto en"><button type="button" data-vuelto="$" aria-pressed="' + (cobro.vueltoEn === '$') + '">Dar en $</button><button type="button" data-vuelto="Bs" aria-pressed="' + (cobro.vueltoEn === 'Bs') + '">Dar en Bs</button><button type="button" data-vuelto="pago_movil" aria-pressed="' + (cobro.vueltoEn === 'pago_movil') + '">Por pago móvil</button></div>';
    } else { caja.className = 'pc-saldo ok'; caja.innerHTML = '<span>Pago completo</span><b>Sin vuelto</b>'; }
    var faltan = cobro.pagos.filter(function (p) { return met(p.m).ref && !p.ref.trim(); });
    var motivo = !cobro.pagos.length ? 'Primero elige cómo paga el cliente.' : dif < -0.004 ? 'Falta cobrar ' + usd(-dif) + '.' : faltan.length ? 'Falta la referencia de ' + met(faltan[0].m).n + '.' : '';
    $('pc-aviso').textContent = motivo;
    $('pc-validar').disabled = !!motivo;
  }
  function mostrar(paso) {
    $('pv-cuerpo').hidden = paso !== 'venta'; document.querySelector('.pv-tabs').hidden = paso !== 'venta';
    $('pv-cobro').hidden = paso !== 'cobro'; $('pv-listo').hidden = paso !== 'listo';
  }
  $('pv-cobrar').addEventListener('click', function () { cobro = { pagos: [], abierto: -1, vueltoEn: '$', enfocar: false }; mostrar('cobro'); pintarCobro(); });
  $('pc-volver').addEventListener('click', function () { mostrar('venta'); });
  $('pv-cobro').addEventListener('click', function (e) {
    var t = function (s) { return e.target.closest(s); }, b;
    if ((b = t('[data-metodo]'))) { var m = met(+b.getAttribute('data-metodo')); cobro.pagos.push({ m: m.id, monto: enMoneda(Math.max(0, -saldo()), m.mon), ref: '' }); cobro.abierto = cobro.pagos.length - 1; cobro.enfocar = true; pintarCobro(); return; }
    if ((b = t('[data-p-quitar]'))) { cobro.pagos.splice(+b.getAttribute('data-p-quitar'), 1); cobro.abierto = -1; pintarCobro(); return; }
    if ((b = t('[data-p-editar]'))) { cobro.abierto = +b.getAttribute('data-p-editar'); cobro.enfocar = true; pintarCobro(); return; }
    if (t('[data-p-listo]')) { cobro.abierto = -1; pintarCobro(); return; }
    if ((b = t('[data-todo]'))) { var i = +b.getAttribute('data-todo'), p = cobro.pagos[i]; p.monto = enMoneda(faltaSin(i), met(p.m).mon); pintarCobro(); return; }
    if ((b = t('[data-billete]'))) { cobro.pagos[cobro.abierto].monto = +b.getAttribute('data-billete'); pintarCobro(); return; }
    if ((b = t('[data-vuelto]'))) { cobro.vueltoEn = b.getAttribute('data-vuelto'); estado(); }
  });
  $('pc-pagos').addEventListener('input', function (e) {
    var im = e.target.getAttribute('data-p-monto'), ir = e.target.getAttribute('data-p-ref');
    if (im !== null) {
      var p = cobro.pagos[+im];
      p.monto = Math.max(0, parseFloat(e.target.value.replace(',', '.')) || 0);
      var art = e.target.closest('.pc-pago');
      art.querySelector('[data-ayuda]').innerHTML = ayuda(+im);
      art.querySelectorAll('[data-billete]').forEach(function (b) { b.setAttribute('aria-pressed', String(+b.getAttribute('data-billete') === p.monto)); });
    }
    if (ir !== null) { cobro.pagos[+ir].ref = e.target.value; e.target.classList.toggle('falta', !e.target.value.trim()); }
    estado();
  });

  $('pc-validar').addEventListener('click', function () {
    var f = $('form-venta'), campos = [];
    var add = function (n, v) { campos.push('<input type="hidden" name="' + n + '" value="' + esc(v) + '">'); };
    if (pv.cliente && pv.cliente.id) add('id_usuario', pv.cliente.id);
    if (pv.nuevoCliente) Object.keys(pv.nuevoCliente).forEach(function (k) { add('nuevo_cliente[' + k + ']', pv.nuevoCliente[k]); });
    pv.lineas.forEach(function (l) { add('id_producto[]', l.id); add('cantidad[]', l.q); add('precio_unitario[]', precioL(l)); add('descuento[]', l.d); });
    cobro.pagos.forEach(function (p) { add('id_metodo_pago[]', p.m); add('monto[]', p.monto); add('tasa_cambio[]', TASA); add('referencia[]', p.ref); });
    var dif = saldo();
    add('total', totalPV());
    add('vuelto', dif > 0.004 ? dif : 0);
    add('vuelto_moneda', dif > 0.004 ? (cobro.vueltoEn === '$' ? 'USD' : cobro.vueltoEn) : '');
    f.innerHTML = campos.join('');
    $('pc-validar').disabled = true; $('pc-validar').textContent = 'Guardando…';
    f.submit();
  });

  function comprobante(v) {
    mostrar('listo');
    var vuelto = +v.vuelto > 0 ? (v.vuelto_moneda === 'USD' ? usd(v.vuelto) : bs(v.vuelto) + (v.vuelto_moneda === 'pago_movil' ? ' por pago móvil' : '')) : '';
    $('pl-titulo').textContent = 'Venta #' + String(v.id).padStart(4, '0') + ' registrada';
    $('pl-detalle').textContent = usd(v.total) + ', ' + (v.cliente || 'Consumidor final');
    $('pl-vuelto').textContent = vuelto ? 'Vuelto: ' + vuelto : '';
    var msg = '*La Providencia* - Comprobante de compra\nVenta #' + String(v.id).padStart(4, '0') + ', ' + (v.fecha || '') + '\nCliente: ' + (v.cliente || 'Consumidor final') + '\n\n' +
      (v.lineas || []).map(function (l) { return '• ' + l.nombre + ' x' + l.cantidad + (+l.descuento ? ' (−' + (+l.descuento) + '%)' : '') + ': ' + usd(l.subtotal); }).join('\n') +
      '\n\n*Total: ' + usd(v.total) + '* (' + bs(v.total) + ', tasa ' + TASA.toFixed(2) + ')\nPagó con: ' + (v.pagos || []).map(function (p) { return p.metodo + ' ' + (p.moneda === 'USD' ? usd(p.monto) : Panel.fmtBs(p.monto) + ' Bs') + (p.referencia ? ' (ref. ' + p.referencia + ')' : ''); }).join(' + ') +
      (vuelto ? '\nVuelto: ' + vuelto : '') + '\n\nEste comprobante no es una factura fiscal.\n¡Gracias por su compra!';
    $('pl-wa-msg').textContent = msg;
    var tel = $('pl-tel');
    tel.value = v.telefono || '';
    var actualizar = function () {
      var d = tel.value.replace(/\D/g, '');
      if (d.charAt(0) === '0') d = '58' + d.slice(1);
      var ok = /^58\d{10}$/.test(d);
      $('pl-wa').href = 'https://wa.me/' + (ok ? d : '') + '?text=' + encodeURIComponent(msg);
      $('pl-wa-txt').textContent = ok ? 'Enviar por WhatsApp' : 'Elegir contacto en WhatsApp';
      $('pl-tel-ayuda').textContent = !d ? 'Si lo dejas vacío, WhatsApp te deja elegir el contacto.' : ok ? 'Se abrirá el chat con +' + d + '.' : 'Revisa el número: debe tener 11 dígitos, como 0414-5550123.';
    };
    tel.addEventListener('input', actualizar); actualizar();
  }

  if (D.venta) comprobante(D.venta); else pintarPV();
})();
