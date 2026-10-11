(function () {
  var panel = document.getElementById('panel');
  if (!panel) return;
  var TASA = parseFloat(panel.getAttribute('data-tasa')) || 1;
  var fmt = function (v) { return v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };

  window.Panel = {
    tasa: TASA,
    usd: function (v) { return '$' + fmt(+v || 0); },
    bs: function (v) { return fmt((+v || 0) * TASA) + ' Bs'; },
    fmtBs: function (v) { return fmt(+v || 0); },
    esc: function (s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); },
    avisar: function (txt, tipo, segundos) { avisar(txt, tipo, segundos); },
    confirmar: function (op) { return confirmar(op); },
    datos: function (id) { var el = document.getElementById(id); return el ? JSON.parse(el.textContent) : null; }
  };

  var ICONO = { ok: 'check', error: 'error', aviso: 'alerta', info: 'info' };
  var TITULO = { ok: 'Listo', error: 'No se pudo', aviso: 'Atención', info: 'Aviso' };
  var MAX = 3, GAP = 10, abierta = false;
  var pila = document.createElement('ol');
  pila.className = 'avisos'; pila.setAttribute('aria-live', 'polite'); pila.setAttribute('aria-label', 'Avisos');
  document.body.appendChild(pila);

  function ordenar() {
    var items = Array.prototype.slice.call(pila.querySelectorAll('.tst:not(.sale)')).reverse(), y = 0;
    items.forEach(function (t, i) {
      t.style.zIndex = 100 - i;
      if (!t.dataset.montado) return;
      if (abierta) { t.style.transform = 'translateY(' + y + 'px)'; y += t.offsetHeight + GAP; }
      else t.style.transform = 'translateY(' + (i * 12) + 'px) scale(' + (1 - i * .05) + ')';
      t.style.opacity = i < MAX ? 1 : 0;
      t.style.pointerEvents = i < MAX ? 'auto' : 'none';
    });
    var frente = items[0];
    pila.style.height = abierta ? Math.max(0, y - GAP) + 'px' : (frente ? frente.offsetHeight + Math.min(items.length - 1, MAX - 1) * 12 + 'px' : '0');
  }

  function avisar(txt, tipo, segundos) {
    var op = typeof txt === 'object' && txt ? txt : { texto: txt, tipo: tipo, segundos: segundos };
    tipo = ICONO[op.tipo] ? op.tipo : 'info';
    var total = (op.segundos || (tipo === 'error' ? 7 : 5)) * 1000;
    var t = document.createElement('li');
    t.className = 'tst t-' + tipo;
    t.setAttribute('role', tipo === 'error' ? 'alert' : 'status');
    t.innerHTML = '<span class="tst-ic"><svg class="i"><use href="#i-' + ICONO[tipo] + '"/></svg></span>' +
      '<div class="tst-txt"><b></b><p></p></div>' +
      '<div class="tst-lado"><button type="button" class="tst-x" aria-label="Cerrar aviso"><svg class="i"><use href="#i-x"/></svg></button><span class="tst-seg" aria-hidden="true"></span></div>' +
      '<span class="tst-barra"><i></i></span>';
    t.querySelector('b').textContent = op.titulo || TITULO[tipo];
    t.querySelector('p').textContent = op.texto || '';
    if (op.accion) {
      var a = document.createElement(op.href ? 'a' : 'button');
      a.className = 'tst-accion'; a.textContent = op.accion;
      if (op.href) a.href = op.href; else a.type = 'button';
      a.addEventListener('click', function () { if (op.alPulsar) op.alPulsar(); cerrar(); });
      t.querySelector('.tst-txt').appendChild(a);
    }
    pila.appendChild(t);
    var seg = t.querySelector('.tst-seg');
    seg.textContent = Math.ceil(total / 1000) + 's';
    var barra = t.querySelector('.tst-barra i').animate([{ transform: 'scaleX(1)' }, { transform: 'scaleX(0)' }], { duration: total, easing: 'linear', fill: 'forwards' });
    var reloj = setInterval(function () { seg.textContent = Math.max(1, Math.ceil((total - barra.currentTime) / 1000)) + 's'; }, 250);
    function cerrar() {
      if (t.classList.contains('sale')) return;
      clearInterval(reloj); barra.cancel();
      t.classList.add('sale');
      t.style.transition = 'transform 200ms ease-out, opacity 200ms ease-out';
      t.style.opacity = 0;
      t.style.transform = 'translateY(-60%) scale(.96)';
      setTimeout(function () { t.remove(); ordenar(); }, 200);
      ordenar();
    }
    barra.onfinish = cerrar;
    t.cerrar = cerrar; t.barra = barra;
    t.querySelector('.tst-x').addEventListener('click', cerrar);
    if (document.hidden) barra.pause();
    ordenar();
    requestAnimationFrame(function () { t.dataset.montado = 1; ordenar(); });
    var vivos = pila.querySelectorAll('.tst:not(.sale)');
    if (vivos.length > MAX + 1) vivos[0].cerrar();
  }

  function pausar(si) { pila.querySelectorAll('.tst:not(.sale)').forEach(function (t) { if (si) t.barra.pause(); else t.barra.play(); }); }
  pila.addEventListener('mouseenter', function () { abierta = true; ordenar(); });
  pila.addEventListener('mouseleave', function () { abierta = false; ordenar(); });
  document.addEventListener('visibilitychange', function () { pausar(document.hidden); });

  var dlg = document.createElement('dialog');
  dlg.className = 'modal chico';
  dlg.setAttribute('aria-labelledby', 'mc-titulo'); dlg.setAttribute('aria-describedby', 'mc-texto');
  dlg.innerHTML = '<div class="mc"><div class="mc-cab"><span class="mc-ic"><svg class="i"><use href="#i-basura"/></svg></span></div>' +
    '<div class="mc-cuerpo"><h3 id="mc-titulo"></h3><p id="mc-texto"></p>' +
    '<div class="mc-detalle" hidden><span><svg class="i"><use href="#i-etiqueta"/></svg></span><div><b></b><small></small></div></div></div>' +
    '<footer class="modal-pie"><button type="button" class="btn btn-sec" data-mc="no">Cancelar</button><button type="button" class="btn" data-mc="si"></button></footer></div>';
  document.body.appendChild(dlg);
  var resolver = null;
  function confirmar(op) {
    op = op || {};
    var peligro = op.tipo !== 'aviso';
    dlg.querySelector('.mc').classList.toggle('mc-aviso', !peligro);
    dlg.querySelector('.mc-ic use').setAttribute('href', '#i-' + (op.icono || (peligro ? 'basura' : 'alerta')));
    dlg.querySelector('#mc-titulo').textContent = op.titulo || '¿Seguro?';
    dlg.querySelector('#mc-texto').textContent = op.texto || 'Esta acción no se puede deshacer.';
    var det = dlg.querySelector('.mc-detalle');
    det.hidden = !op.detalle;
    if (op.detalle) {
      det.querySelector('use').setAttribute('href', '#i-' + (op.detalle.icono || 'etiqueta'));
      det.querySelector('b').textContent = op.detalle.titulo || '';
      det.querySelector('small').textContent = op.detalle.texto || '';
    }
    dlg.querySelector('[data-mc="no"]').textContent = op.cancelar || 'Cancelar';
    var si = dlg.querySelector('[data-mc="si"]');
    si.className = 'btn ' + (peligro ? 'btn-peligro-lleno' : 'btn-pri');
    si.innerHTML = peligro ? '<svg class="i"><use href="#i-' + (op.iconoBoton || 'basura') + '"/></svg>' : '';
    si.appendChild(document.createTextNode(op.aceptar || 'Aceptar'));
    dlg.showModal(); dlg.querySelector('[data-mc="no"]').focus();
    return new Promise(function (ok) { resolver = ok; });
  }
  dlg.addEventListener('click', function (e) {
    var b = e.target.closest('[data-mc]');
    if (b) { dlg.close(b.getAttribute('data-mc')); return; }
    if (e.target === dlg) dlg.close('no');
  });
  dlg.addEventListener('close', function () { if (resolver) { resolver(dlg.returnValue === 'si'); resolver = null; } dlg.returnValue = ''; });

  var abrir = document.getElementById('abrir-menu'), velo = document.getElementById('velo');
  if (abrir) abrir.addEventListener('click', function () { panel.classList.add('abierto'); });
  if (velo) velo.addEventListener('click', function () { panel.classList.remove('abierto'); });

  document.addEventListener('click', function (e) {
    var cerrar = e.target.closest('dialog [data-cerrar]');
    if (cerrar) { cerrarConPermiso(cerrar.closest('dialog')); return; }

    var abrirModal = e.target.closest('[data-abrir-modal]');
    if (abrirModal) {
      var modal = document.getElementById(abrirModal.getAttribute('data-abrir-modal'));
      var datos = abrirModal.getAttribute('data-registro');
      var form = modal.querySelector('form');
      if (form) form.reset();
      if (datos) {
        datos = JSON.parse(datos);
        Object.keys(datos).forEach(function (k) {
          var campo = modal.querySelector('[name="' + k + '"]');
          if (!campo) return;
          if (campo.type === 'checkbox') campo.checked = !!+datos[k];
          else if (campo.type === 'radio') { var r = modal.querySelector('[name="' + k + '"][value="' + datos[k] + '"]'); if (r) r.checked = true; }
          else campo.value = datos[k];
        });
      }
      modal.querySelectorAll('[data-si-edita]').forEach(function (el) { el.textContent = datos ? el.getAttribute('data-si-edita') : el.getAttribute('data-si-crea'); });
      if (Panel.refrescarSelects) Panel.refrescarSelects(modal);
      modal.showModal();
      var primero = modal.querySelector('input:not([type=hidden]), select, textarea');
      if (primero) primero.focus();
      return;
    }

    var pide = e.target.closest('[data-confirmar]');
    if (pide && pide.dataset.confirmado !== '1') {
      e.preventDefault();
      confirmar({ titulo: pide.getAttribute('data-confirmar'), texto: pide.getAttribute('data-confirmar-texto'), aceptar: pide.getAttribute('data-confirmar-boton'), tipo: pide.getAttribute('data-confirmar-tipo') }).then(function (si) {
        if (!si) return;
        if (pide.tagName === 'A') { location.href = pide.href; return; }
        var form = pide.form || pide.closest('form');
        if (form) { pide.dataset.confirmado = '1'; form.requestSubmit(pide.type === 'submit' ? pide : undefined); delete pide.dataset.confirmado; }
      });
      return;
    }

    var copiar = e.target.closest('[data-copiar]');
    if (copiar) {
      var texto = copiar.getAttribute('data-copiar');
      var etiqueta = copiar.getAttribute('aria-label'), icono = copiar.innerHTML;
      var listo = function () {
        copiar.classList.add('hecho'); copiar.setAttribute('aria-label', 'Referencia copiada'); copiar.setAttribute('data-tip', 'Copiada'); copiar.innerHTML = '<svg class="i"><use href="#i-check"/></svg>';
        setTimeout(function () { copiar.classList.remove('hecho'); copiar.setAttribute('aria-label', etiqueta); copiar.setAttribute('data-tip', 'Copiar'); copiar.innerHTML = icono; }, 1800);
      };
      if (navigator.clipboard) navigator.clipboard.writeText(texto).then(listo, function () { Panel.avisar('Selecciona el texto y cópialo con Ctrl+C', 'aviso'); });
      return;
    }

    var ver = e.target.closest('[data-ver-clave]');
    if (ver) {
      var inp = document.getElementById(ver.getAttribute('data-ver-clave')), mostrar = inp.type === 'password';
      inp.type = mostrar ? 'text' : 'password';
      ver.setAttribute('aria-pressed', String(mostrar));
      ver.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
    }
  });

  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-bloqueo]');
    if (!b) return;
    e.preventDefault();
    confirmar({ tipo: 'aviso', icono: 'etiqueta', titulo: b.getAttribute('data-bloqueo'), texto: b.getAttribute('data-bloqueo-texto'), aceptar: 'Ver sus productos', cancelar: 'Entendido' }).then(function (si) {
      var enlace = b.getAttribute('data-bloqueo-enlace');
      if (si && enlace) location.href = enlace;
    });
  });

  function filasDe(cont) { return Array.prototype.slice.call(cont.tagName === 'TABLE' ? cont.querySelectorAll('tbody > tr') : cont.children); }
  function paginar(cont, irA) {
    var porPagina = +cont.getAttribute('data-paginar') || 10;
    var filas = filasDe(cont), visibles = filas.filter(function (f) { return f.dataset.filtrado !== 'no'; });
    var paginas = Math.max(1, Math.ceil(visibles.length / porPagina));
    var pagina = Math.min(Math.max(1, irA || cont._pagina || 1), paginas);
    cont._pagina = pagina;
    filas.forEach(function (f) { f.hidden = true; });
    visibles.slice((pagina - 1) * porPagina, pagina * porPagina).forEach(function (f) { f.hidden = false; });
    var nav = cont._nav;
    if (!nav) {
      nav = cont._nav = document.createElement('nav');
      nav.className = 'paginas'; nav.setAttribute('aria-label', 'Páginas');
      var ancla = cont.closest('.tabla-scroll') || cont;
      ancla.insertAdjacentElement('afterend', nav);
      nav.addEventListener('click', function (e) {
        var p = e.target.closest('[data-pagina]');
        if (!p || p.disabled) return;
        paginar(cont, +p.getAttribute('data-pagina'));
        var caja = cont.closest('.tabla-caja') || cont;
        if (caja.getBoundingClientRect().top < 0) caja.scrollIntoView({ block: 'start', behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
      });
    }
    nav.hidden = visibles.length <= porPagina;
    if (nav.hidden) return;
    var desde = (pagina - 1) * porPagina + 1, hasta = Math.min(pagina * porPagina, visibles.length);
    var numeros = [];
    for (var n = 1; n <= paginas; n++) {
      if (n === 1 || n === paginas || Math.abs(n - pagina) <= 1) numeros.push(n);
      else if (numeros[numeros.length - 1] !== '…') numeros.push('…');
    }
    nav.innerHTML = '<span class="pag-info">Mostrando <b>' + desde + '–' + hasta + '</b> de <b>' + visibles.length + '</b></span><div class="pag-btns">' +
      '<button type="button" class="pag-btn" data-pagina="' + (pagina - 1) + '" aria-label="Página anterior"' + (pagina === 1 ? ' disabled' : '') + '><svg class="i"><use href="#i-chevron"/></svg></button>' +
      numeros.map(function (n) { return n === '…' ? '<span class="pag-sep" aria-hidden="true">…</span>' : '<button type="button" class="pag-btn" data-pagina="' + n + '"' + (n === pagina ? ' aria-current="page"' : '') + ' aria-label="Página ' + n + '">' + n + '</button>'; }).join('') +
      '<button type="button" class="pag-btn" data-pagina="' + (pagina + 1) + '" aria-label="Página siguiente"' + (pagina === paginas ? ' disabled' : '') + '><svg class="i"><use href="#i-chevron"/></svg></button></div>';
  }
  document.querySelectorAll('[data-paginar]').forEach(function (cont) { paginar(cont, 1); });

  document.querySelectorAll('[data-filtro-tabla]').forEach(function (input) {
    var tabla = document.getElementById(input.getAttribute('data-filtro-tabla'));
    var vacio = document.getElementById(input.getAttribute('data-filtro-vacio'));
    var extra = tabla && tabla.getAttribute('data-filtro-tambien') ? document.querySelector(tabla.getAttribute('data-filtro-tambien')) : null;
    input.addEventListener('input', function () {
      var q = input.value.trim().toLowerCase().replace(/[-\s.]/g, ''), visibles = 0;
      [tabla, extra].forEach(function (cont) {
        if (!cont) return;
        var n = 0;
        filasDe(cont).forEach(function (f) {
          var ok = !q || f.textContent.toLowerCase().replace(/[-\s.]/g, '').indexOf(q) !== -1;
          f.dataset.filtrado = ok ? '' : 'no';
          if (!cont.hasAttribute('data-paginar')) f.hidden = !ok;
          if (ok) n++;
        });
        if (cont.hasAttribute('data-paginar')) paginar(cont, 1);
        if (cont === tabla) visibles = n;
      });
      if (vacio) vacio.hidden = visibles > 0;
    });
  });

  var aviso = panel.getAttribute('data-aviso');
  if (aviso) Panel.avisar(aviso, panel.getAttribute('data-aviso-tipo') || 'ok');
  try {
    var pendiente = JSON.parse(sessionStorage.getItem('panel-aviso') || 'null');
    sessionStorage.removeItem('panel-aviso');
    if (pendiente && !aviso) Panel.avisar(pendiente);
  } catch (err) {}
  Panel.avisarAlVolver = function (datos) { try { sessionStorage.setItem('panel-aviso', JSON.stringify(datos)); } catch (err) {} };

  document.addEventListener('change', function (e) {
    var c = e.target.closest('[data-estado-url]');
    if (!c) return;
    var activar = c.checked, nombre = c.getAttribute('data-estado-nombre') || '';
    var ir = function () {
      Panel.avisarAlVolver({ tipo: activar ? 'ok' : 'info', titulo: activar ? 'Cuenta activada' : 'Cuenta desactivada', texto: activar ? nombre + ' ya puede entrar y comprar en la tienda.' : nombre + ' ya no puede entrar ni comprar en la tienda.' });
      location.href = c.getAttribute('data-estado-url') + (activar ? 1 : 0);
    };
    if (activar) { ir(); return; }
    c.checked = true;
    confirmar({ titulo: '¿Desactivar la cuenta de ' + nombre + '?', texto: 'No podrá iniciar sesión ni comprar en la tienda online hasta que la vuelvas a activar. Su historial de compras se conserva.', aceptar: 'Desactivar cuenta', cancelar: 'Cancelar', icono: 'candado', iconoBoton: 'candado' }).then(function (si) {
      if (!si) return;
      c.checked = false;
      ir();
    });
  });

  document.querySelectorAll('[data-auto-enviar]').forEach(function (campo) {
    campo.addEventListener('change', function () { if (campo.form) campo.form.requestSubmit ? campo.form.requestSubmit() : campo.form.submit(); });
  });

  document.querySelectorAll('.f-rango').forEach(function (r) {
    document.addEventListener('click', function (e) { if (r.open && e.composedPath().indexOf(r) === -1) r.open = false; });
    r.addEventListener('keydown', function (e) { if (e.key === 'Escape') { r.open = false; r.querySelector('summary').focus(); } });
    r.addEventListener('toggle', function () { if (r.open) { var f = r.querySelector('.cal-dia[tabindex="0"]'); if (f) f.focus(); } });
  });

  var MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
  var MES_CORTO = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
  var aIso = function (d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); };
  var deIso = function (s) { var p = (s || '').split('-'); return p.length === 3 ? new Date(+p[0], p[1] - 1, +p[2]) : null; };
  var corto = function (d) { return d.getDate() + ' ' + MES_CORTO[d.getMonth()]; };

  document.querySelectorAll('.cal').forEach(function (cal) {
    var dia = cal.dataset.modo === 'dia';
    var campoD = cal.querySelector(dia ? '[data-cal="fecha"]' : '[data-cal="desde"]'), campoH = cal.querySelector('[data-cal="hasta"]');
    var max = deIso(cal.dataset.max) || new Date(); max.setHours(0, 0, 0, 0);
    var ini = deIso(campoD.value), fin = dia ? ini : deIso(campoH && campoH.value), previo = null;
    var vista = new Date((fin || max).getFullYear(), (fin || max).getMonth(), 1), foco = fin || max;
    var cuerpo = document.createElement('div');
    cuerpo.innerHTML = '<div class="cal-cab"><span class="cal-mes" aria-live="polite"></span><div class="cal-nav"><button type="button" data-paso="-1" aria-label="Mes anterior"><svg class="i"><use href="#i-chevron"/></svg></button><button type="button" data-paso="1" aria-label="Mes siguiente"><svg class="i"><use href="#i-chevron"/></svg></button></div></div>' +
      '<div class="cal-grid" role="grid"></div>' + (dia ? '' : '<p class="cal-ayuda">Pulsa el primer día y luego el último.</p>') +
      '<div class="cal-pie"><output></output><button type="submit" class="btn btn-pri">Ver</button></div>';
    while (cuerpo.firstChild) cal.appendChild(cuerpo.firstChild);
    var grid = cal.querySelector('.cal-grid'), salida = cal.querySelector('output');

    function pintar(enfocar) {
      cal.querySelector('.cal-mes').textContent = MESES[vista.getMonth()] + ' ' + vista.getFullYear();
      cal.querySelector('[data-paso="1"]').disabled = vista.getFullYear() === max.getFullYear() && vista.getMonth() === max.getMonth();
      var html = ['L', 'M', 'X', 'J', 'V', 'S', 'D'].map(function (s) { return '<span class="cal-sem" aria-hidden="true">' + s + '</span>'; }).join('');
      var hueco = (vista.getDay() + 6) % 7, total = new Date(vista.getFullYear(), vista.getMonth() + 1, 0).getDate(), hoy = aIso(max);
      for (var i = 0; i < hueco; i++) html += '<span class="cal-dia vacio"></span>';
      var a = ini, b = fin || previo;
      if (a && b && b < a) { var t = a; a = b; b = t; }
      for (var n = 1; n <= total; n++) {
        var d = new Date(vista.getFullYear(), vista.getMonth(), n), k = aIso(d), cls = ['cal-dia'];
        if (k === hoy) cls.push('hoy');
        if (a && k === aIso(a)) cls.push('ini');
        if (b && k === aIso(b)) cls.push('fin');
        if (a && b && d >= a && d <= b && aIso(a) !== aIso(b)) cls.push('en-rango');
        var marcado = cls.indexOf('ini') > -1 || cls.indexOf('fin') > -1;
        html += '<button type="button" class="' + cls.join(' ') + '" data-dia="' + k + '" tabindex="' + (k === aIso(foco) ? 0 : -1) + '"' + (d > max ? ' disabled' : '') + ' aria-label="' + n + ' de ' + MESES[d.getMonth()] + '"' + (marcado ? ' aria-pressed="true"' : '') + '><span>' + n + '</span></button>';
      }
      grid.innerHTML = html;
      salida.innerHTML = !ini ? 'Elige un día' : dia || !fin || aIso(ini) === aIso(fin) ? '<b>' + corto(ini) + '</b>' : '<b>' + corto(ini) + '</b> – <b>' + corto(fin) + '</b>';
      cal.querySelector('.cal-pie [type=submit]').disabled = !ini;
      if (enfocar) { var f = grid.querySelector('[data-dia="' + aIso(foco) + '"]'); if (f) f.focus(); }
    }

    function elegir(d) {
      if (dia) ini = fin = d;
      else if (!ini || fin) { ini = d; fin = null; }
      else if (d < ini) { fin = ini; ini = d; }
      else fin = d;
      previo = null; foco = d;
      campoD.value = aIso(ini);
      if (campoH) campoH.value = aIso(fin || ini);
      pintar(true);
    }

    cal.addEventListener('click', function (e) {
      var paso = e.target.closest('[data-paso]');
      if (paso) { vista.setMonth(vista.getMonth() + +paso.dataset.paso); pintar(); return; }
      var b = e.target.closest('[data-dia]');
      if (b && !b.disabled) elegir(deIso(b.dataset.dia));
    });
    grid.addEventListener('mouseover', function (e) {
      var b = e.target.closest('[data-dia]');
      if (dia || !ini || fin || !b || b.disabled) return;
      var d = deIso(b.dataset.dia);
      if (!previo || aIso(previo) !== aIso(d)) { previo = d; pintar(); }
    });
    grid.addEventListener('focusin', function (e) { var b = e.target.closest('[data-dia]'); if (b) foco = deIso(b.dataset.dia); });
    grid.addEventListener('keydown', function (e) {
      var mov = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 }[e.key];
      if (!mov) return;
      e.preventDefault();
      var d = new Date(foco); d.setDate(d.getDate() + mov);
      if (d > max) return;
      foco = d;
      if (d.getMonth() !== vista.getMonth() || d.getFullYear() !== vista.getFullYear()) vista = new Date(d.getFullYear(), d.getMonth(), 1);
      pintar(true);
    });
    pintar();
  });

  var dspN = 0;
  var sinTildes = function (s) { return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); };
  function mejorarSelect(sel) {
    if (sel._dsp || sel.multiple || sel.size > 1 || sel.hasAttribute('data-nativo') || sel.closest('[data-nativo]')) return;
    var envoltura = sel.closest('.f-select');
    if (!envoltura) {
      envoltura = document.createElement('span');
      envoltura.className = 'dsp-campo';
      sel.parentNode.insertBefore(envoltura, sel);
      envoltura.appendChild(sel);
    }
    var id = 'dsp-' + (++dspN), abiertoAhora = false, actual = -1;
    envoltura.classList.add('dsp');
    sel.classList.add('dsp-oculto'); sel.tabIndex = -1; sel.setAttribute('aria-hidden', 'true');
    var etiqueta = sel.getAttribute('aria-label') || (sel.id && document.querySelector('label[for="' + sel.id + '"]') ? document.querySelector('label[for="' + sel.id + '"]').textContent.replace('*', '').trim() : '');
    if (sel.id) { var lbl = document.querySelector('label[for="' + sel.id + '"]'); if (lbl) lbl.setAttribute('for', id + '-btn'); }
    var btn = document.createElement('button');
    btn.type = 'button'; btn.className = 'dsp-btn'; btn.id = id + '-btn';
    btn.setAttribute('aria-haspopup', 'listbox'); btn.setAttribute('aria-expanded', 'false'); btn.setAttribute('aria-controls', id);
    if (etiqueta) btn.setAttribute('aria-label', etiqueta);
    btn.innerHTML = '<span></span><svg class="i" aria-hidden="true"><use href="#i-chevron"/></svg>';
    var lista = document.createElement('div');
    lista.className = 'dsp-lista'; lista.hidden = true;
    envoltura.appendChild(btn); envoltura.appendChild(lista);
    var ops, campo, vacio;

    function construir() {
      var conBuscador = sel.options.length > 7, texto = sel.getAttribute('data-buscar') || 'Buscar';
      lista.innerHTML = (conBuscador ? '<label class="dsp-buscar"><svg class="i"><use href="#i-buscar"/></svg><input type="search" aria-label="' + Panel.esc(texto) + '" placeholder="' + Panel.esc(texto) + '…"></label>' : '') +
        '<ul class="dsp-ops" role="listbox" id="' + id + '" tabindex="-1"></ul><p class="dsp-vacio" hidden>Sin resultados</p>';
      ops = lista.querySelector('ul'); campo = lista.querySelector('input'); vacio = lista.querySelector('.dsp-vacio');
      Array.prototype.forEach.call(sel.options, function (o, i) {
        var li = document.createElement('li');
        li.className = 'dsp-op'; li.setAttribute('role', 'option'); li.id = id + '-' + i; li.dataset.i = i;
        if (o.disabled) li.setAttribute('aria-disabled', 'true');
        li.innerHTML = '<span></span><svg class="i" aria-hidden="true"><use href="#i-check"/></svg>';
        li.firstChild.textContent = o.textContent;
        ops.appendChild(li);
      });
      if (campo) campo.addEventListener('input', filtrar);
      refrescar();
    }
    function refrescar() {
      var o = sel.options[sel.selectedIndex];
      btn.firstChild.textContent = o ? o.textContent : '';
      envoltura.classList.toggle('activo', sel.value !== '');
      envoltura.classList.toggle('vacio', sel.value === '');
      btn.disabled = sel.disabled;
      if (ops) Array.prototype.forEach.call(ops.children, function (li) { li.setAttribute('aria-selected', +li.dataset.i === sel.selectedIndex ? 'true' : 'false'); });
    }
    var visibles = function () { return Array.prototype.filter.call(ops.children, function (li) { return !li.hidden && li.getAttribute('aria-disabled') !== 'true'; }); };
    function marcar(li) {
      ops.querySelectorAll('.actual').forEach(function (x) { x.classList.remove('actual'); });
      if (!li) { actual = -1; ops.removeAttribute('aria-activedescendant'); return; }
      li.classList.add('actual'); actual = +li.dataset.i;
      ops.setAttribute('aria-activedescendant', li.id);
      li.scrollIntoView({ block: 'nearest' });
    }
    function filtrar() {
      var q = sinTildes(campo.value.trim());
      Array.prototype.forEach.call(ops.children, function (li) { li.hidden = !!q && sinTildes(li.textContent).indexOf(q) === -1; });
      var v = visibles(); vacio.hidden = v.length > 0; marcar(v[0] || null);
    }
    function abrir() {
      document.querySelectorAll('.dsp.abierto').forEach(function (otro) { if (otro !== envoltura && otro._cerrar) otro._cerrar(false); });
      lista.hidden = false; abiertoAhora = true; envoltura.classList.add('abierto'); btn.setAttribute('aria-expanded', 'true');
      if (campo) { campo.value = ''; filtrar(); }
      marcar(ops.children[sel.selectedIndex]);
      (campo || ops).focus();
    }
    function cerrar(devolverFoco) {
      lista.hidden = true; abiertoAhora = false; envoltura.classList.remove('abierto'); btn.setAttribute('aria-expanded', 'false');
      if (devolverFoco) btn.focus();
    }
    envoltura._cerrar = cerrar;
    function elegir(i) {
      var cambio = sel.selectedIndex !== i;
      sel.selectedIndex = i; refrescar(); cerrar(true);
      if (cambio) { sel.dispatchEvent(new Event('input', { bubbles: true })); sel.dispatchEvent(new Event('change', { bubbles: true })); }
    }
    function mover(paso) {
      var v = visibles(); if (!v.length) return;
      var pos = v.indexOf(ops.children[actual]);
      marcar(v[Math.min(v.length - 1, Math.max(0, pos + paso))]);
    }
    btn.addEventListener('click', function () { if (abiertoAhora) cerrar(true); else abrir(); });
    btn.addEventListener('keydown', function (e) { if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); abrir(); } });
    lista.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') { e.preventDefault(); mover(1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); mover(-1); }
      else if (e.key === 'Enter') { e.preventDefault(); if (actual > -1) elegir(actual); }
      else if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); cerrar(true); }
      else if (e.key === 'Tab') cerrar(false);
    });
    lista.addEventListener('mousemove', function (e) { var li = e.target.closest('.dsp-op'); if (li && li.getAttribute('aria-disabled') !== 'true' && +li.dataset.i !== actual) marcar(li); });
    lista.addEventListener('click', function (e) { var li = e.target.closest('.dsp-op'); if (li && li.getAttribute('aria-disabled') !== 'true') elegir(+li.dataset.i); });
    document.addEventListener('click', function (e) { if (abiertoAhora && e.composedPath().indexOf(envoltura) === -1) cerrar(false); });
    sel.addEventListener('change', refrescar);
    sel.addEventListener('dsp:refrescar', refrescar);
    if (sel.form) sel.form.addEventListener('reset', function () { setTimeout(refrescar, 0); });
    new MutationObserver(construir).observe(sel, { childList: true, subtree: true });
    sel._dsp = { refrescar: refrescar, construir: construir };
    construir();
  }

  if (matchMedia('(pointer: fine)').matches) {
    document.querySelectorAll('select').forEach(mejorarSelect);
    new MutationObserver(function (cambios) {
      cambios.forEach(function (c) {
        c.addedNodes.forEach(function (n) {
          if (n.nodeType !== 1) return;
          if (n.tagName === 'SELECT') mejorarSelect(n);
          else n.querySelectorAll && n.querySelectorAll('select').forEach(mejorarSelect);
        });
      });
    }).observe(document.body, { childList: true, subtree: true });
  }
  Panel.refrescarSelects = function (raiz) { (raiz || document).querySelectorAll('select').forEach(function (s) { if (s._dsp) s._dsp.refrescar(); }); };

  function mensajeError(campo) {
    var v = campo.validity;
    if (v.valueMissing) return campo.tagName === 'SELECT' ? 'Elige una opción.' : (campo.type === 'file' ? 'Elige un archivo.' : 'Este campo es obligatorio.');
    if (v.typeMismatch && campo.type === 'email') return 'Escribe un correo válido, como nombre@correo.com.';
    if (v.typeMismatch) return 'Revisa el formato.';
    if (v.rangeUnderflow) return 'Debe ser ' + campo.min + ' o más.';
    if (v.rangeOverflow) return 'Debe ser ' + campo.max + ' o menos.';
    if (v.stepMismatch) return 'Revisa los decimales.';
    if (v.tooShort) return 'Debe tener al menos ' + campo.minLength + ' caracteres.';
    if (v.tooLong) return 'Debe tener como máximo ' + campo.maxLength + ' caracteres.';
    if (v.patternMismatch) return campo.title || 'Revisa el formato.';
    if (v.badInput) return 'Escribe un número.';
    return campo.validationMessage;
  }
  function contenedorError(campo) { return campo.closest('.campo, .fpx-st, .opciones') || campo.parentElement; }
  function limpiarError(campo) {
    var c = contenedorError(campo), m = c && c.querySelector(':scope > .error-campo');
    if (m) m.remove();
    if (c) c.classList.remove('con-error');
    campo.removeAttribute('aria-invalid');
  }
  document.addEventListener('invalid', function (e) {
    var campo = e.target;
    if (!campo.closest('#panel, dialog')) return;
    e.preventDefault();
    var c = contenedorError(campo);
    limpiarError(campo);
    c.classList.add('con-error');
    campo.setAttribute('aria-invalid', 'true');
    var m = document.createElement('span');
    m.className = 'error-campo'; m.setAttribute('role', 'alert');
    m.innerHTML = '<svg class="i" aria-hidden="true"><use href="#i-error"/></svg>';
    m.appendChild(document.createTextNode(mensajeError(campo)));
    c.appendChild(m);
    var form = campo.form;
    if (form && form.querySelector('[aria-invalid="true"]') === campo) {
      var destino = campo._dsp ? campo.closest('.dsp').querySelector('.dsp-btn') : campo;
      if (campo.type === 'radio' || campo.type === 'checkbox' || campo.classList.contains('fp-file')) destino = c;
      if (campo._fch) destino = campo.closest('.fch').querySelector('.fch-btn');
      destino.scrollIntoView({ block: 'center', behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
      if (destino.focus) destino.focus({ preventScroll: true });
    }
  }, true);
  ['input', 'change'].forEach(function (tipo) {
    document.addEventListener(tipo, function (e) {
      var campo = e.target;
      if (campo.getAttribute && campo.getAttribute('aria-invalid') === 'true' && campo.checkValidity()) limpiarError(campo);
    }, true);
  });

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if ((form.getAttribute('method') || 'get').toLowerCase() !== 'post') return;
    var boton = e.submitter && e.submitter.type === 'submit' ? e.submitter : form.querySelector('[type=submit]');
    setTimeout(function () {
      if (e.defaultPrevented || !boton || boton.disabled) return;
      form._enviando = true;
      var idForm = form.getAttribute('id');
      form.querySelectorAll('[type=submit]').forEach(function (b) { b.disabled = true; });
      if (idForm) document.querySelectorAll('[type=submit][form="' + idForm + '"]').forEach(function (b) { b.disabled = true; });
      boton._texto = boton.innerHTML;
      boton.classList.add('guardando');
      boton.innerHTML = '<span class="giro" aria-hidden="true"></span>Guardando…';
    }, 0);
  });
  window.addEventListener('pageshow', function (e) {
    if (!e.persisted) return;
    document.querySelectorAll('.guardando').forEach(function (b) { b.innerHTML = b._texto || b.innerHTML; b.classList.remove('guardando'); });
    document.querySelectorAll('[type=submit]:disabled').forEach(function (b) { b.disabled = false; });
  });

  var estadoDe = function (dlg) {
    var f = dlg.querySelector('form');
    if (!f || f.getAttribute('method') === 'dialog') return null;
    var p = []; new FormData(f).forEach(function (v, k) { p.push(k + '=' + (v instanceof File ? v.name + v.size : v)); });
    return p.join('&');
  };
  var conCambios = function (dlg) { return dlg._inicial != null && !dlg.querySelector('form')._enviando && estadoDe(dlg) !== dlg._inicial; };
  function cerrarConPermiso(dlg) {
    if (!conCambios(dlg)) { dlg.close(); return; }
    confirmar({ tipo: 'aviso', icono: 'alerta', titulo: '¿Descartar los cambios?', texto: 'Lo que escribiste en este formulario se va a perder.', aceptar: 'Descartar', cancelar: 'Seguir editando' }).then(function (si) { if (si) dlg.close(); });
  }
  Panel.cerrarModal = cerrarConPermiso;
  new MutationObserver(function (cambios) {
    cambios.forEach(function (c) {
      var dlg = c.target;
      if (!dlg.classList.contains('modal') || dlg.classList.contains('modal-confirmar') || dlg.querySelector('.mc')) return;
      if (dlg.open) setTimeout(function () { dlg._inicial = estadoDe(dlg); var f = dlg.querySelector('form'); if (f) f._enviando = false; dlg.querySelectorAll('.guardando').forEach(function (x) { x.innerHTML = x._texto; x.classList.remove('guardando'); }); dlg.querySelectorAll('[type=submit]:disabled').forEach(function (x) { x.disabled = false; }); dlg.querySelectorAll('.con-error').forEach(function (x) { x.classList.remove('con-error'); }); dlg.querySelectorAll('.error-campo').forEach(function (x) { x.remove(); }); }, 0);
      else dlg._inicial = null;
    });
  }).observe(document.body, { attributes: true, attributeFilter: ['open'], subtree: true });
  document.addEventListener('cancel', function (e) {
    var dlg = e.target;
    if (dlg.tagName === 'DIALOG' && conCambios(dlg)) { e.preventDefault(); cerrarConPermiso(dlg); }
  }, true);
})();
(function () {
  var MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
  var DIAS = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
  var dos = function (n) { return (n < 10 ? '0' : '') + n; };
  var aIso = function (d) { return d.getFullYear() + '-' + dos(d.getMonth() + 1) + '-' + dos(d.getDate()); };
  var deIso = function (s) { var m = /^(\d{4})-(\d\d)-(\d\d)(?:T(\d\d):(\d\d))?/.exec(s || ''); return m ? new Date(+m[1], m[2] - 1, +m[3], +(m[4] || 0), +(m[5] || 0)) : null; };

  function mejorarFecha(inp) {
    if (inp._fch) return;
    var conHora = inp.type === 'datetime-local';
    var max = deIso(inp.max), min = deIso(inp.min);
    if (max) max.setHours(23, 59, 59, 999);
    var caja = document.createElement('div');
    caja.className = 'fch';
    inp.parentNode.insertBefore(caja, inp);
    caja.appendChild(inp);
    inp.classList.add('fch-nativo');
    inp.tabIndex = -1;
    var btn = document.createElement('button');
    btn.type = 'button'; btn.className = 'fch-btn';
    btn.setAttribute('aria-haspopup', 'dialog'); btn.setAttribute('aria-expanded', 'false');
    if (inp.id) { btn.id = inp.id + '-btn'; var lab = document.querySelector('label[for="' + inp.id + '"]'); if (lab) lab.htmlFor = btn.id; }
    btn.innerHTML = '<svg class="i" aria-hidden="true"><use href="#i-calendario"/></svg><span></span><svg class="i fch-flecha" aria-hidden="true"><use href="#i-chevron"/></svg>';
    var pop = document.createElement('div');
    pop.className = 'fch-pop'; pop.hidden = true;
    pop.setAttribute('role', 'dialog'); pop.setAttribute('aria-label', 'Elegir fecha');
    var horas = '', mins = '';
    for (var h = 1; h <= 12; h++) horas += '<option value="' + h + '">' + h + '</option>';
    for (var m = 0; m < 60; m += 5) mins += '<option value="' + m + '">' + dos(m) + '</option>';
    pop.innerHTML = '<div class="fch-atajos"><button type="button" data-atajo="0">Hoy</button><button type="button" data-atajo="-1">Ayer</button></div>' +
      '<div class="cal-cab"><span class="cal-mes" aria-live="polite"></span><div class="cal-nav"><button type="button" data-paso="-1" aria-label="Mes anterior"><svg class="i"><use href="#i-chevron"/></svg></button><button type="button" data-paso="1" aria-label="Mes siguiente"><svg class="i"><use href="#i-chevron"/></svg></button></div></div>' +
      '<div class="cal-grid" role="grid"></div>' +
      (conHora ? '<div class="fch-hora"><span class="fch-lbl">Hora</span><select class="fch-h" aria-label="Hora">' + horas + '</select><b aria-hidden="true">:</b><select class="fch-m" aria-label="Minutos">' + mins + '</select><div class="ajx-seg fch-ampm" role="radiogroup" aria-label="a. m. o p. m."><label><input type="radio" form="fch-sin-form" name="' + (inp.name || 'f') + '-ampm" value="am">a. m.</label><label><input type="radio" form="fch-sin-form" name="' + (inp.name || 'f') + '-ampm" value="pm">p. m.</label></div></div>' : '') +
      '<div class="cal-pie"><output></output><button type="button" class="btn btn-pri" data-listo>Listo</button></div>';
    caja.appendChild(btn); caja.appendChild(pop);
    var grid = pop.querySelector('.cal-grid');
    var valor = deIso(inp.value), vista, foco;

    function texto(d) {
      if (!d) return 'Elige una fecha';
      var t = DIAS[d.getDay()] + ' ' + d.getDate() + ' ' + MESES[d.getMonth()].slice(0, 3) + ' ' + d.getFullYear();
      if (conHora) { var hh = d.getHours() % 12 || 12; t += ' · ' + hh + ':' + dos(d.getMinutes()) + (d.getHours() < 12 ? ' a. m.' : ' p. m.'); }
      return t;
    }
    function guardar() {
      inp.value = !valor ? '' : aIso(valor) + (conHora ? 'T' + dos(valor.getHours()) + ':' + dos(valor.getMinutes()) : '');
      btn.querySelector('span').textContent = texto(valor);
      btn.classList.toggle('vacio', !valor);
      pop.querySelector('output').innerHTML = valor ? '<b>' + texto(valor) + '</b>' : 'Elige un día';
      inp.dispatchEvent(new Event('input', { bubbles: true }));
      inp.dispatchEvent(new Event('change', { bubbles: true }));
    }
    function fuera(d) { var x = new Date(d); x.setHours(12); return (max && x > max) || (min && x < new Date(min.getFullYear(), min.getMonth(), min.getDate())); }
    function pintar(enfocar) {
      pop.querySelector('.cal-mes').textContent = MESES[vista.getMonth()] + ' ' + vista.getFullYear();
      pop.querySelector('[data-paso="1"]').disabled = !!max && vista.getFullYear() === max.getFullYear() && vista.getMonth() === max.getMonth();
      var html = ['L', 'M', 'X', 'J', 'V', 'S', 'D'].map(function (s) { return '<span class="cal-sem" aria-hidden="true">' + s + '</span>'; }).join('');
      var hueco = (vista.getDay() + 6) % 7, total = new Date(vista.getFullYear(), vista.getMonth() + 1, 0).getDate(), hoy = aIso(new Date());
      for (var i = 0; i < hueco; i++) html += '<span class="cal-dia vacio"></span>';
      for (var n = 1; n <= total; n++) {
        var d = new Date(vista.getFullYear(), vista.getMonth(), n), k = aIso(d), cls = ['cal-dia'];
        if (k === hoy) cls.push('hoy');
        if (valor && k === aIso(valor)) cls.push('ini', 'fin');
        html += '<button type="button" class="' + cls.join(' ') + '" data-dia="' + k + '" tabindex="' + (k === aIso(foco) ? 0 : -1) + '"' + (fuera(d) ? ' disabled' : '') + ' aria-label="' + n + ' de ' + MESES[d.getMonth()] + '"' + (valor && k === aIso(valor) ? ' aria-pressed="true"' : '') + '><span>' + n + '</span></button>';
      }
      grid.innerHTML = html;
      if (conHora && valor) {
        pop.querySelector('.fch-h').value = valor.getHours() % 12 || 12;
        var mm = pop.querySelector('.fch-m');
        if (!mm.querySelector('option[value="' + valor.getMinutes() + '"]')) { var o = document.createElement('option'); o.value = valor.getMinutes(); o.textContent = dos(valor.getMinutes()); mm.appendChild(o); Array.prototype.slice.call(mm.options).sort(function (a, b) { return a.value - b.value; }).forEach(function (x) { mm.appendChild(x); }); }
        mm.value = valor.getMinutes();
        pop.querySelector('.fch-ampm input[value="' + (valor.getHours() < 12 ? 'am' : 'pm') + '"]').checked = true;
        if (window.Panel && Panel.refrescarSelects) Panel.refrescarSelects(pop);
      }
      if (enfocar) { var f = grid.querySelector('[data-dia="' + aIso(foco) + '"]'); if (f) f.focus(); }
    }
    function elegirDia(d) {
      var nuevo = new Date(d.getFullYear(), d.getMonth(), d.getDate(), valor ? valor.getHours() : 9, valor ? valor.getMinutes() : 0);
      valor = nuevo; foco = nuevo; vista = new Date(nuevo.getFullYear(), nuevo.getMonth(), 1);
      guardar(); pintar(true);
    }
    function horaDesdeCampos() {
      if (!valor) valor = new Date(foco.getFullYear(), foco.getMonth(), foco.getDate());
      var h = +pop.querySelector('.fch-h').value % 12, pm = pop.querySelector('.fch-ampm input[value=pm]').checked;
      valor.setHours(h + (pm ? 12 : 0), +pop.querySelector('.fch-m').value);
      guardar();
    }
    function abrir() {
      document.querySelectorAll('.fch-pop:not([hidden])').forEach(function (p) { if (p !== pop) p.parentNode._cerrar(); });
      foco = valor || (max && max < new Date() ? max : new Date());
      vista = new Date(foco.getFullYear(), foco.getMonth(), 1);
      pop.hidden = false; btn.setAttribute('aria-expanded', 'true'); caja.classList.add('abierto');
      pop.classList.remove('arriba');
      pintar(true);
      var r = pop.getBoundingClientRect(), b = btn.getBoundingClientRect();
      if (r.bottom > innerHeight - 8 && b.top - r.height - 8 > 8) pop.classList.add('arriba');
      else if (r.bottom > innerHeight - 8) pop.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
    function cerrar(devolver) {
      if (pop.hidden) return;
      pop.hidden = true; btn.setAttribute('aria-expanded', 'false'); caja.classList.remove('abierto');
      if (devolver) btn.focus();
    }
    caja._cerrar = cerrar;
    btn.addEventListener('click', function () { pop.hidden ? abrir() : cerrar(); });
    pop.addEventListener('click', function (e) {
      var paso = e.target.closest('[data-paso]');
      if (paso) { vista.setMonth(vista.getMonth() + +paso.dataset.paso); pintar(); return; }
      var b = e.target.closest('[data-dia]');
      if (b && !b.disabled) { elegirDia(deIso(b.dataset.dia)); return; }
      var at = e.target.closest('[data-atajo]');
      if (at) { var d = new Date(); d.setDate(d.getDate() + +at.dataset.atajo); elegirDia(d); return; }
      if (e.target.closest('[data-listo]')) cerrar(true);
    });
    pop.addEventListener('change', function (e) { if (e.target.closest('.fch-hora')) horaDesdeCampos(); });
    grid.addEventListener('keydown', function (e) {
      var pasos = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 }[e.key];
      if (pasos) { e.preventDefault(); var d = new Date(foco); d.setDate(d.getDate() + pasos); if (fuera(d)) return; foco = d; vista = new Date(d.getFullYear(), d.getMonth(), 1); pintar(true); }
    });
    pop.addEventListener('keydown', function (e) { if (e.key === 'Escape') { e.stopPropagation(); e.preventDefault(); cerrar(true); } });
    document.addEventListener('mousedown', function (e) { if (!pop.hidden && e.composedPath().indexOf(caja) === -1) cerrar(); });
    inp.addEventListener('invalid', function () { btn.classList.add('error'); });
    inp._fch = { refrescar: function () { valor = deIso(inp.value); btn.querySelector('span').textContent = texto(valor); } };
    btn.querySelector('span').textContent = texto(valor);
    pop.querySelector('output').innerHTML = valor ? '<b>' + texto(valor) + '</b>' : 'Elige un día';
  }

  if (matchMedia('(pointer: fine)').matches) document.querySelectorAll('#panel input[type=date], #panel input[type=datetime-local]').forEach(mejorarFecha);
})();
(function () {
  var vigilados = [], saliendo = false;
  var pendiente = function () { return !saliendo && vigilados.some(function (f) { return f(); }); };
  Panel.vigilarCambios = function (fn) { vigilados.push(fn); return { salir: function () { saliendo = true; } }; };
  document.addEventListener('submit', function (e) {
    if (!e.defaultPrevented && (e.target.getAttribute('method') || '').toLowerCase() === 'post') saliendo = true;
  });
  window.addEventListener('beforeunload', function (e) { if (pendiente()) { e.preventDefault(); e.returnValue = ''; } });
  document.addEventListener('click', function (e) {
    var a = e.target.closest('a[href]');
    if (!a || e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
    if ((a.target && a.target !== '_self') || a.hasAttribute('download')) return;
    var href = a.getAttribute('href');
    if (!href || href.charAt(0) === '#' || /^(javascript|mailto|tel):/i.test(href) || !pendiente()) return;
    e.preventDefault();
    Panel.confirmar({ tipo: 'aviso', icono: 'alerta', titulo: '¿Salir sin guardar?', texto: 'Tienes cambios en esta página que todavía no guardaste. Si sales, se pierden.', aceptar: 'Salir sin guardar', cancelar: 'Seguir editando' }).then(function (si) {
      if (si) { saliendo = true; location.href = a.href; }
    });
  });
})();
