(function () {
  var pagos = Panel.datos('datos-pagos') || [], usd = Panel.usd, esc = Panel.esc, fmtBs = Panel.fmtBs;
  var det = document.getElementById('det-pago'), verif = document.getElementById('verif');
  if (!pagos.length) { det.innerHTML = '<div class="vacio-filtro">Elige un pago de la lista.</div>'; return; }

  function montoTxt(p) { return p.moneda === 'USD' ? usd(p.monto) : fmtBs(p.monto) + ' Bs'; }
  function mostrar(i) {
    var p = pagos[i];
    document.querySelectorAll('[data-pago]').forEach(function (b) { b.setAttribute('aria-current', String(+b.getAttribute('data-pago') === i)); });
    var usdPago = p.moneda === 'USD' ? p.monto : p.monto / p.tasa, falta = Math.max(0, p.total - p.previos);
    var coincide = Math.abs(usdPago - falta) < 0.01, dif = falta - usdPago, difBs = dif * p.tasa;
    var res = coincide ? '<p class="vp-res"><svg class="i"><use href="#i-check"/></svg><span>Este pago cubre exactamente lo que falta. Al aprobarlo, la venta queda pagada.</span></p>'
      : dif > 0 ? '<p class="vp-res"><svg class="i"><use href="#i-alerta"/></svg><span>Se queda corto por <b class="cifra">' + fmtBs(difBs) + ' Bs</b> (' + usd(dif) + '). Si lo apruebas, la venta queda con ese saldo pendiente.</span></p>'
      : '<p class="vp-res"><svg class="i"><use href="#i-alerta"/></svg><span>Pagó <b class="cifra">' + fmtBs(-difBs) + ' Bs</b> de más. Habría que devolverle esa diferencia.</span></p>';
    var acciones = p.estado === 'pendiente'
      ? '<footer class="vp-acciones"><button type="button" class="btn btn-peligro" id="btn-rechazar"><svg class="i"><use href="#i-x"/></svg>Rechazar</button><button type="button" class="btn btn-ok" id="btn-aprobar"><svg class="i"><use href="#i-check"/></svg>Aprobar pago</button></footer>'
      : '<footer class="vp-acciones"><div class="resultado-pago ' + (p.estado === 'aprobado' ? 'ok' : 'no') + '"><b>' + (p.estado === 'aprobado' ? 'Aprobado' : 'Rechazado') + '</b>' + (p.por ? ' por ' + esc(p.por) : '') + (p.obs ? '<br>Motivo: ' + esc(p.obs) : '') + '</div></footer>';
    det.innerHTML = '<button type="button" class="volver volver-cola" id="volver-cola"><svg class="i"><use href="#i-volver"/></svg>Volver a la lista</button>' +
      '<div class="vp"><header class="vp-cab"><div><span class="vp-sub">Venta #' + String(p.venta).padStart(4, '0') + (p.fecha ? ', ' + esc(p.fecha) : '') + '</span><h3>' + esc(p.cliente) + '</h3></div><span class="chip">' + esc(p.metodo) + '</span></header>' +
      '<div class="vp-cuerpo"><figure class="comprobante">' + (p.img ? '<a href="' + esc(p.img) + '" target="_blank" rel="noopener" aria-label="Ver comprobante en grande"><img src="' + esc(p.img) + '" alt="Comprobante enviado por el cliente"></a>' : '<div class="vacio-filtro">El cliente no subió captura.</div>') + '<figcaption>Captura que subió el cliente</figcaption></figure>' +
      '<div class="vp-datos"><section class="vp-banco"><h4>Búscalo en tu banco</h4><p class="vp-hint">Abre la cuenta del banco y confirma que llegó un pago con estos datos.</p><dl class="vp-filas">' +
      '<div class="vp-fila destacada"><dt>Monto</dt><dd><b class="cifra">' + montoTxt(p) + '</b><small>= ' + (p.moneda === 'USD' ? fmtBs(p.monto * p.tasa) + ' Bs' : usd(usdPago)) + ' a tasa ' + p.tasa.toFixed(2) + '</small></dd></div>' +
      '<div class="vp-fila"><dt>Referencia</dt><dd class="vp-ref-caja"><b class="vp-ref">' + esc(p.ref || 'Sin referencia') + '</b>' + (p.ref ? '<button type="button" class="vp-copiar" data-copiar="' + esc(p.ref) + '">Copiar</button>' : '') + '</dd></div>' +
      '<div class="vp-fila"><dt>Fecha y hora</dt><dd>' + esc(p.fecha) + '</dd></div>' +
      '<div class="vp-fila"><dt>Pagado desde</dt><dd>' + esc(p.banco || 'Banco no indicado') + (p.origen ? '<small>' + esc(p.origen) + '</small>' : '') + '</dd></div></dl></section>' +
      '<section class="vp-cubre ' + (coincide ? 'si' : 'no') + '"><h4>¿Cubre la venta #' + String(p.venta).padStart(4, '0') + '?</h4>' +
      '<div class="vp-cuenta"><span>Total <b>' + usd(p.total) + '</b></span>' + (p.previos ? '<span>− ya pagado <b>' + usd(p.previos) + '</b></span>' : '') + '<span>= falta <b>' + usd(falta) + '</b></span></div>' + res +
      '<a href="index.php?action=detalle_venta&id=' + p.venta + '" class="pc-link">Ver la venta completa</a></section></div></div>' + acciones + '</div>';
    det.setAttribute('data-actual', i);
  }
  mostrar(0);
  document.getElementById('p-verificar_pagos').addEventListener('click', function (e) {
    var it = e.target.closest('[data-pago]');
    if (it) { mostrar(+it.getAttribute('data-pago')); verif.classList.add('viendo'); return; }
    if (e.target.closest('#volver-cola')) { verif.classList.remove('viendo'); return; }
    var p = pagos[+det.getAttribute('data-actual')];
    if (e.target.closest('#btn-aprobar')) { document.getElementById('ap-id').value = p.id; document.getElementById('form-aprobar').submit(); return; }
    if (e.target.closest('#btn-rechazar')) { document.getElementById('mr-id').value = p.id; document.getElementById('mr-obs').value = ''; document.getElementById('modal-rechazo').showModal(); }
  });
})();
