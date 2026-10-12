(function () {
  var pagos = Panel.datos('datos-pagos') || [], usd = Panel.usd, esc = Panel.esc, fmtBs = Panel.fmtBs;
  var det = document.getElementById('det-pago'), verif = document.getElementById('verif');
  var alerta = Panel.datos('alerta-pago');
  if (alerta) Panel.avisar({ tipo: alerta.tipo, titulo: alerta.titulo, texto: alerta.texto, accion: alerta.deshacer ? 'Deshacer' : '', href: alerta.deshacer, segundos: alerta.deshacer ? 9 : 5 });
  if (!det) return;
  if (!pagos.length) { det.innerHTML = '<p class="vacio-filtro">Elige un pago de la lista.</p>'; return; }

  var venta = function (p) { return '#' + String(p.venta).padStart(4, '0'); };
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
      '<header class="vf-cab"><div><small>Venta <a href="index.php?action=detalle_venta&amp;id=' + p.venta + '">' + venta(p) + '</a>' + (p.fecha ? ' · ' + esc(p.fecha) : '') + '</small><h3>' + esc(p.cliente) + '</h3></div>' +
      '<span class="dv-chip neutro"><svg class="i"><use href="#i-' + icono(p) + '"/></svg>' + esc(p.metodo) + '</span></header>';
    var cuerpo;
    if (p.estado === 'pendiente') {
      cuerpo = '<div class="vf-cuerpo"><section class="vf-sec"><h4><span class="n">1</span>' + (p.es_efectivo ? 'Confirma el efectivo recibido' : 'Búscalo en tu banco') + '</h4>' +
        '<p>' + (p.es_efectivo ? 'Cuenta el dinero y confirma que coincide.' : 'Confirma que llegó un pago con estos datos.') + '</p>' + montos(p) + datos(p) + '</section>' + cuenta(p) + '</div>' +
        '<footer class="vf-acc">' +
          '<button type="button" class="btn btn-sec" id="btn-editar-pago" style="margin-right:auto;"><svg class="i"><use href="#i-editar"/></svg>Editar datos</button>' +
          '<button type="button" class="btn btn-rech" id="btn-rechazar"><svg class="i"><use href="#i-x"/></svg>Rechazar</button>' +
          '<button type="button" class="btn btn-ok" id="btn-aprobar"><svg class="i"><use href="#i-check"/></svg>Aprobar pago</button>' +
        '</footer>';
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
    if (e.target.closest('#btn-aprobar')) { document.getElementById('ap-id').value = p.id; document.getElementById('form-aprobar').submit(); return; }
    if (e.target.closest('#btn-rechazar')) { document.getElementById('mr-id').value = p.id; document.getElementById('mr-obs').value = ''; document.getElementById('modal-rechazo').showModal(); return; }
    if (e.target.closest('#btn-editar-pago')) {
      var modalEd = document.getElementById('modal-editar-pago');
      document.getElementById('me-id-pago').value = p.id;
      document.getElementById('me-id-venta').value = p.venta;
      document.getElementById('me-subtitulo').textContent = 'Venta ' + venta(p) + ' — ' + p.cliente;
      document.getElementById('me-banco').value = (p.banco && p.banco !== 'No Indicado') ? p.banco : '';
      document.getElementById('me-ref').value = (p.ref || '').replace(/^Online\s*-\s*/i, '');
      var selMet = document.getElementById('me-metodo');
      for (var o = 0; o < selMet.options.length; o++) {
        if (selMet.options[o].text.toLowerCase().trim() === (p.metodo || '').toLowerCase().trim() || selMet.options[o].value == p.id_metodo) {
          selMet.selectedIndex = o; break;
        }
      }
      var cProds = document.getElementById('me-lista-prods');
      cProds.innerHTML = '<small style="color:#777;">Cargando productos...</small>';
      fetch('index.php?action=detalle_venta&id=' + p.venta + '&formato=json')
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (!res || !res.detalles || !res.detalles.length) {
            cProds.innerHTML = '<small style="color:#888;">No hay productos para modificar.</small>';
            return;
          }
          var h = '';
          res.detalles.forEach(function (d) {
            h += '<div style="display:flex;justify-content:space-between;align-items:center;background:#fff;padding:6px 10px;border-radius:4px;border:1px solid #ddd;">' +
              '<span style="font-size:13px;">' + esc(d.producto_nombre || 'Producto #' + d.id_producto) + ' <small>($' + parseFloat(d.precio_unitario).toFixed(2) + ')</small></span>' +
              '<div style="display:flex;align-items:center;gap:4px;"><label style="font-size:12px;">Cant:</label><input type="number" name="productos[' + d.id_producto + ']' + '" value="' + d.cantidad + '" min="0" style="width:50px;padding:2px 4px;text-align:center;"></div>' +
              '</div>';
          });
          cProds.innerHTML = h;
        })
        .catch(function () {
          cProds.innerHTML = '<small style="color:#888;">Artículos originales fijados.</small>';
        });
      modalEd.showModal();
    }
  });

  document.querySelectorAll('dialog [data-cerrar]').forEach(function (btn) {
    btn.addEventListener('click', function () { btn.closest('dialog').close(); });
  });
})();