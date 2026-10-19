(function () {
  var pagos = Panel.datos('datos-pagos') || [], usd = Panel.usd, esc = Panel.esc, fmtBs = Panel.fmtBs;
  var det = document.getElementById('det-pago'), verif = document.getElementById('verif');
  if (!det) return;
  if (!pagos.length) { det.innerHTML = '<p class="vacio-filtro">Elige un pago de la lista.</p>'; return; }

  var iniciales = function (n) { return String(n || '?').trim().split(/\s+/).slice(0, 2).map(function (w) { return w.charAt(0); }).join('').toUpperCase(); };
  var venta = function (p) { return /^\d+$/.test(String(p.venta)) ? '#' + String(p.venta).padStart(4, '0') : String(p.venta); };
  var enUsd = function (p) { return p.moneda === 'USD' ? p.monto : p.monto / p.tasa; };
  var enBs = function (p) { return p.moneda === 'USD' ? p.monto * p.tasa : p.monto; };
  var icono = function (p) { return p.es_efectivo ? 'efectivo' : (/transfer/i.test(p.metodo) ? 'banco' : 'movil'); };

  function montos(p) {
    return '<dl class="vf-montos">' +
      '<div class="vf-monto"><span class="vf-moneda">Bs</span><dt>En bolívares</dt><dd>' + fmtBs(enBs(p)) + ' Bs</dd></div>' +
      '<div class="vf-monto usd"><span class="vf-moneda">$</span><dt>En dólares</dt><dd>' + usd(enUsd(p)) + '</dd></div>' +
      '<p class="vf-tasa"><svg class="i"><use href="#i-dolar"/></svg>Tasa cobrada <b>' + fmtBs(p.tasa) + ' Bs por $</b></p></dl>';
  }

  function datos(p) {
    if (p.es_efectivo) return '';
    var t = p.titular || {}, html = '<dl class="vf-datos">';
    html += '<div class="vf-dato"><span class="ic"><svg class="i"><use href="#i-recibo"/></svg></span><dt>Referencia' +
      (p.ref ? '<button type="button" class="vf-copiar" data-tip="Copiar" data-ref="' + esc(p.ref) + '" aria-label="Copiar referencia"><svg class="i"><use href="#i-copiar"/></svg></button>' : '') +
      '</dt><dd class="vf-ref">' + esc(p.ref || 'Sin referencia') + '</dd></div>';
    html += '<div class="vf-dato"><span class="ic"><svg class="i"><use href="#i-banco"/></svg></span><dt>Banco</dt><dd>' + esc(p.banco || 'No indicado') + (p.origen ? '<small>' + esc(p.origen) + '</small>' : '') + '</dd></div>';
    var principal = t.nombre || t.doc || 'Sin datos', detalle = [t.nombre ? t.doc : '', t.tel].filter(Boolean).join(' · ');
    html += '<div class="vf-dato"><span class="ic"><svg class="i"><use href="#i-usuarios"/></svg></span><dt>Titular</dt><dd>' + esc(principal) + (detalle ? '<small>' + esc(detalle) + '</small>' : '') + '</dd></div>';
    if (p.img) html += '<div class="vf-dato vf-captura"><span class="ic"><svg class="i"><use href="#i-archivo"/></svg></span><dt>Captura del cliente</dt><dd><a href="' + esc(p.img) + '" target="_blank" rel="noopener"><img src="' + esc(p.img) + '" alt="Comprobante enviado por el cliente"></a></dd></div>';
    return html + '</dl>';
  }

  function cuenta(p) {
    var falta = Math.max(0, p.total - p.previos), este = enUsd(p), queda = falta - este;
    var res = Math.abs(queda) < 0.01 ? '<div class="res"><b><svg class="i"><use href="#i-check"/></svg>Queda saldada</b></div>'
      : queda > 0 ? '<div class="res corto"><b><svg class="i"><use href="#i-alerta"/></svg>Faltarían ' + usd(queda) + '</b></div>'
      : '<div class="res corto"><b><svg class="i"><use href="#i-alerta"/></svg>Sobran ' + usd(-queda) + '</b><span>Hay que devolverlos</span></div>';
    return '<section class="vf-sec"><h4><span class="n">2</span>Cómo queda la venta ' + venta(p) + '</h4>' +
      '<div class="vf-cubre"><div class="lin"><div><span>Total de la venta</span><b>' + usd(p.total) + '</b></div><div><span>Ya pagado</span><b>' + usd(p.previos) + '</b></div><div><span>Este pago</span><b>− ' + usd(este) + '</b></div><div class="f"><span>Quedaría por cobrar</span><span>' + usd(Math.max(0, queda)) + '</span></div></div>' + res + '</div></section>';
  }

  function mostrar(i) {
    var p = pagos[i];
    document.querySelectorAll('[data-pago]').forEach(function (b) { b.setAttribute('aria-current', String(+b.getAttribute('data-pago') === i)); });
    var cab = '<button type="button" class="volver volver-cola" id="volver-cola"><svg class="i"><use href="#i-volver"/></svg>Volver a la lista</button>' +
      '<header class="vf-cab"><div class="dv-cliente"><span class="dv-av" aria-hidden="true">' + esc(iniciales(p.cliente)) + '</span><b>' + esc(p.cliente) + '</b><small>Cliente</small></div>' +
      (p.estado === 'pendiente' ? '<button type="button" class="btn vf-editar" id="btn-editar-pago"><svg class="i"><use href="#i-editar"/></svg>Editar pago</button>' : '') +
      '<dl class="dc-dl dc-dl-fila vf-cab-datos">' +
        '<div><dt><svg class="i" aria-hidden="true"><use href="#i-' + icono(p) + '"/></svg>Método</dt><dd>' + esc(p.metodo) + '</dd></div>' +
        '<div><dt><svg class="i" aria-hidden="true"><use href="#i-recibo"/></svg>Venta</dt><dd><a href="index.php?action=detalle_venta&amp;' + urlClave('venta', p.venta) + '">' + venta(p) + '</a></dd></div>' +
        '<div><dt><svg class="i" aria-hidden="true"><use href="#i-calendario"/></svg>Fecha</dt><dd>' + esc(p.fecha || 'Sin fecha') + '</dd></div>' +
      '</dl></header>';
    var cuerpo;
    if (p.estado === 'pendiente') {
      cuerpo = '<div class="vf-cuerpo"><section class="vf-sec"><h4><span class="n">1</span>' + (p.es_efectivo ? 'Confirma el efectivo recibido' : 'Búscalo en tu banco') + '</h4>' +
        '<p>' + (p.es_efectivo ? 'Cuenta el dinero y confirma que coincide.' : 'Confirma que llegó un pago con estos datos.') + '</p>' + montos(p) + datos(p) + '</section>' + cuenta(p) + '</div>' +
        '<footer class="vf-acc"><button type="button" class="btn btn-rech" id="btn-rechazar"><svg class="i"><use href="#i-x"/></svg>Rechazar</button><button type="button" class="btn btn-ok" id="btn-aprobar"><svg class="i"><use href="#i-check"/></svg>Aprobar pago</button></footer>';
    } else {
      var ok = p.estado === 'aprobado', quien = [p.por ? 'por ' + p.por : (p.es_efectivo && p.registrado_por ? 'por ' + p.registrado_por : ''), p.cuando || p.fecha].filter(Boolean).join(' · ');
      var titulo = ok ? (p.es_efectivo ? 'Cobrado en caja' : 'Pago aprobado') : 'Pago rechazado';
      cuerpo = '<div class="vf-cuerpo"><div class="vf-final' + (ok ? '' : ' rech') + '"><span class="ic"><svg class="i"><use href="#i-' + (ok ? (p.es_efectivo ? 'efectivo' : 'check') : 'x') + '"/></svg></span><b>' + titulo + '</b><span>' + esc(quien) + '</span>' +
        (!ok && p.motivo ? '<p><b>Motivo:</b> ' + esc(p.motivo) + '</p>' : '') + '</div>' + montos(p) + datos(p) + '</div>';
    }
    det.innerHTML = cab + cuerpo;
    det.setAttribute('data-actual', i);
  }
  mostrar(0);

  document.getElementById('p-verificar_pagos').addEventListener('click', function (e) {
    var it = e.target.closest('[data-pago]');
    if (it) { mostrar(+it.getAttribute('data-pago')); verif.classList.add('viendo'); return; }
    if (e.target.closest('#volver-cola')) { verif.classList.remove('viendo'); return; }
    var copiar = e.target.closest('.vf-copiar');
    if (copiar) {
      var listo = function () { copiar.classList.add('hecho'); copiar.setAttribute('aria-label', 'Referencia copiada'); copiar.setAttribute('data-tip', 'Copiada'); copiar.innerHTML = '<svg class="i"><use href="#i-check"/></svg>'; setTimeout(function () { copiar.classList.remove('hecho'); copiar.setAttribute('aria-label', 'Copiar referencia'); copiar.setAttribute('data-tip', 'Copiar'); copiar.innerHTML = '<svg class="i"><use href="#i-copiar"/></svg>'; }, 1600); };
      if (navigator.clipboard) navigator.clipboard.writeText(copiar.getAttribute('data-ref')).then(listo, function () { Panel.avisar('Selecciona la referencia y cópiala con Ctrl+C', 'aviso'); });
      return;
    }
    var p = pagos[+det.getAttribute('data-actual')];
    if (e.target.closest('#btn-aprobar')) {
      var monto = p.moneda === 'USD' ? usd(p.monto) : fmtBs(p.monto) + ' Bs';
      Panel.confirmar({ tipo: 'aviso', icono: 'check', titulo: '¿Aprobar este pago?', texto: 'Confirma que ya lo viste en el banco. Después de aprobarlo no se puede deshacer.', detalle: { icono: 'recibo', titulo: venta(p) + ' · ' + monto, texto: (p.cliente || '') + (p.metodo ? ' · ' + p.metodo : '') + (p.ref ? ' · Ref. ' + p.ref : '') }, aceptar: 'Aprobar pago' }).then(function (si) {
        if (!si) return;
        ponerClave(document.getElementById('form-aprobar'), 'pago', p.id);
        document.getElementById('form-aprobar').submit();
      });
      return;
    }
    if (e.target.closest('#btn-rechazar')) { ponerClave(document.querySelector('#modal-rechazo form'), 'pago', p.id); document.getElementById('mr-obs').value = ''; document.getElementById('modal-rechazo').showModal(); return; }
    if (e.target.closest('#btn-editar-pago')) abrirEditar(p);
  });

  var meLista = document.getElementById('me-lista-prods');
  function fijarSelect(sel, valor, texto) {
    var op = [].filter.call(sel.options, function (o) { return o.value == valor || (texto && o.text.toLowerCase().trim() === texto.toLowerCase().trim()); })[0];
    if (!op && texto) { op = new Option(texto, texto); sel.add(op); }
    sel.value = op ? op.value : '';
    if (sel._dsp) sel._dsp.refrescar();
  }
  function totalEditar() {
    var t = 0;
    meLista.querySelectorAll('li').forEach(function (li) {
      var q = Math.max(0, parseInt(li.querySelector('input').value, 10) || 0), sub = q * +li.getAttribute('data-precio');
      t += sub;
      li.classList.toggle('fuera', q === 0);
      li.querySelector('.me-sub').textContent = q ? usd(sub) : 'Se quita';
      li.querySelector('[data-me-menos]').disabled = q === 0;
    });
    document.getElementById('me-total').textContent = meLista.children.length ? usd(t) + ' · ' + fmtBs(t * Panel.tasa) + ' Bs' : '—';
  }
  function abrirEditar(p) {
    ponerClave(document.getElementById('me-form'), 'pago.ref', p.id);
    if (document.getElementById('me-id-venta')) document.getElementById('me-id-venta').value = p.venta;
    document.getElementById('me-subtitulo').textContent = 'Venta ' + venta(p) + ' · ' + p.cliente;
    fijarSelect(document.getElementById('me-metodo'), p.id_metodo, p.metodo);
    fijarSelect(document.getElementById('me-banco'), '', p.banco && !/^no indicado$/i.test(p.banco) ? p.banco : '');
    document.getElementById('me-ref').value = (p.ref || '').replace(/^Online\s*-\s*/i, '');
    meLista.innerHTML = '<li class="me-cargando">Cargando productos…</li>';
    document.getElementById('me-total').textContent = '—';
    document.getElementById('modal-editar-pago').showModal();
    fetch('index.php?action=detalle_venta&' + urlClave('venta', p.venta) + '&formato=json')
      .then(function (r) { return r.json(); })
      .then(function (res) {
        var d = res && res.detalles ? res.detalles : [];
        if (!d.length) { meLista.innerHTML = '<li class="me-cargando">Este pedido no tiene productos.</li>'; return; }
        meLista.innerHTML = d.map(function (x) { var cod = x[campoClave('producto.ref')]; return filaProd(cod, x.producto_nombre || 'Producto ' + cod, +x.precio_unitario, +x.cantidad); }).join('');
        totalEditar();
      })
      .catch(function () { meLista.innerHTML = '<li class="me-cargando">No se pudieron cargar los productos. Puedes guardar solo los datos del pago.</li>'; });
  }
  meLista.addEventListener('click', function (e) {
    var b = e.target.closest('[data-me-menos], [data-me-mas]'); if (!b) return;
    var inp = b.parentNode.querySelector('input');
    inp.value = Math.max(0, (parseInt(inp.value, 10) || 0) + (b.hasAttribute('data-me-mas') ? 1 : -1));
    totalEditar();
  });
  meLista.addEventListener('input', totalEditar);
  function filaProd(id, nombre, pr, cant) {
    return '<li data-precio="' + pr + '" data-id="' + esc(id) + '"><div class="me-nom"><b>' + esc(nombre) + '</b><span>' + usd(pr) + ' c/u</span></div>' +
      '<span class="stepper me-step"><button type="button" data-me-menos aria-label="Uno menos">−</button><input type="number" name="productos[' + esc(id) + ']" value="' + cant + '" min="0" inputmode="numeric" aria-label="Cantidad de ' + esc(nombre) + '"><button type="button" data-me-mas aria-label="Uno más">+</button></span>' +
      '<span class="me-sub"></span></li>';
  }
  var meNuevo = document.getElementById('me-nuevo'), meAgregar = document.getElementById('me-agregar');
  if (meNuevo && meAgregar) meAgregar.addEventListener('click', function () {
    var op = meNuevo.options[meNuevo.selectedIndex];
    if (!op || !op.value) { Panel.avisar('Elige el producto que quieres agregar', 'aviso'); return; }
    var ya = meLista.querySelector('li[data-id="' + CSS.escape(op.value) + '"] input');
    if (ya) ya.value = (parseInt(ya.value, 10) || 0) + 1;
    else {
      var vacio = meLista.querySelector('.me-cargando'); if (vacio) vacio.remove();
      meLista.insertAdjacentHTML('beforeend', filaProd(op.value, op.dataset.nombre, +op.dataset.precio, 1));
      meLista.lastElementChild.scrollIntoView({ block: 'nearest' });
    }
    meNuevo.value = ''; if (meNuevo._dsp) meNuevo._dsp.refrescar();
    totalEditar();
    Panel.avisar(op.dataset.nombre + ' agregado al pedido', 'ok');
  });
})();