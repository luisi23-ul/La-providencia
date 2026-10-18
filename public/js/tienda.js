(function () {
  var cuerpo = document.body, CLAVE = 'aura-pedido' + (window.CLAVES && CLAVES.url.producto !== 'id' ? '-' + CLAVES.url.producto : '');
  var tasa = parseFloat(cuerpo.dataset.tasa) || 0, wa = cuerpo.dataset.wa || '', tienda = cuerpo.dataset.tienda || 'la tienda';
  var usd = function (n) { return '$' + Number(n).toFixed(2); };
  var bs = function (n) { return (n * tasa).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' Bs'; };

  function leer() { try { return JSON.parse(localStorage.getItem(CLAVE)) || []; } catch (e) { return []; } }
  function guardar(items) { try { localStorage.setItem(CLAVE, JSON.stringify(items)); } catch (e) {} contador(); }
  function contador() {
    var n = leer().reduce(function (s, i) { return s + i.cantidad; }, 0);
    document.querySelectorAll('[data-contador]').forEach(function (a) {
      var c = a.querySelector('.cuenta');
      if (c) { c.textContent = n; c.hidden = !n; }
      a.setAttribute('aria-label', n ? 'Ver mi pedido, ' + n + (n === 1 ? ' prenda' : ' prendas') : 'Ver mi pedido');
    });
  }
  function avisar(html) {
    var t = document.querySelector('.toast-tienda');
    if (t) t.remove();
    t = document.createElement('div');
    t.className = 'toast-tienda'; t.setAttribute('role', 'status');
    t.innerHTML = html;
    document.body.appendChild(t);
    setTimeout(function () { t.remove(); }, 4000);
  }

  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('[data-agregar-rapido]');
    if (!b) return;
    var id = String(b.dataset.id), stock = parseInt(b.dataset.stock, 10) || 0, items = leer(), clave = id + '|';
    var enCarrito = items.filter(function (i) { return String(i.id) === id; }).reduce(function (s, i) { return s + i.cantidad; }, 0);
    if (stock && enCarrito >= stock) { avisar('<i class="ti ti-alert-circle" aria-hidden="true"></i>Solo quedan ' + stock + ' y ya están en tu pedido.'); return; }
    var it = items.find(function (i) { return i.clave === clave; });
    if (it) it.cantidad += 1;
    else items.push({ clave: clave, id: id, nombre: b.dataset.nombre, precio: parseFloat(b.dataset.precio), imagen: b.dataset.imagen, variante: '', cantidad: 1, stock: stock });
    guardar(items);
    b.classList.add('listo');
    b.innerHTML = '<i class="ti ti-check" aria-hidden="true"></i>Agregado';
    setTimeout(function () { b.classList.remove('listo'); b.innerHTML = '<i class="ti ti-shopping-bag-plus" aria-hidden="true"></i>Agregar'; }, 1600);
    avisar('<i class="ti ti-circle-check" aria-hidden="true"></i>Agregado a tu pedido. <a href="index.php?action=ver_carrito">Ver pedido</a>');
  });
  document.querySelectorAll('[data-ver-clave]').forEach(function (b) {
    b.addEventListener('click', function () {
      var i = document.getElementById(b.dataset.verClave), ver = i.type === 'password';
      i.type = ver ? 'text' : 'password';
      b.querySelector('i').className = 'ti ' + (ver ? 'ti-eye-off' : 'ti-eye');
      b.setAttribute('aria-label', ver ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
  });
  var pestanas = document.querySelectorAll('[data-pestana]');
  function irPestana(b) {
    pestanas.forEach(function (x) { var si = x === b; x.setAttribute('aria-selected', si); document.getElementById(x.dataset.pestana).hidden = !si; });
    var campo = document.getElementById(b.dataset.pestana).querySelector('input:not([type=hidden])');
    if (campo) campo.focus();
  }
  pestanas.forEach(function (b) { b.addEventListener('click', function () { irPestana(b); }); });
  document.querySelectorAll('[data-ir-pestana]').forEach(function (b) { b.addEventListener('click', function () { irPestana(document.getElementById(b.dataset.irPestana)); }); });

  var ficha = document.getElementById('f-producto');
  if (ficha) {
    var stock = parseInt(ficha.dataset.stock, 10) || 0, cantEl = ficha.querySelector('[data-cantidad]');
    var resumen = document.querySelector('[data-resumen]'), error = ficha.querySelector('[data-error]');
    var elegidos = function () {
      var partes = [], falta = '';
      ficha.querySelectorAll('[data-atributo]').forEach(function (g) {
        var b = g.querySelector('[aria-checked=true]');
        if (b) partes.push(g.dataset.atributo + ' ' + b.dataset.valor); else if (!falta) falta = g.dataset.atributo.toLowerCase();
      });
      return { texto: partes.join(' - '), falta: falta };
    };
    var pintarResumen = function () { if (resumen) { var el = elegidos(); resumen.textContent = el.falta ? 'Elige tu ' + el.falta : el.texto; } };
    ficha.querySelectorAll('[data-atributo]').forEach(function (g) {
      g.addEventListener('click', function (ev) {
        var b = ev.target.closest('[data-valor]');
        if (!b) return;
        g.querySelectorAll('[data-valor]').forEach(function (x) { x.setAttribute('aria-checked', x === b); });
        g.querySelector('[data-elegido]').textContent = b.dataset.valor;
        error.hidden = true;
        pintarResumen();
      });
    });
    ficha.addEventListener('click', function (ev) {
      var p = ev.target.closest('[data-paso]');
      if (!p) return;
      cantEl.textContent = Math.min(Math.max(1, stock), Math.max(1, (parseInt(cantEl.textContent, 10) || 1) + +p.dataset.paso));
    });
    ficha.addEventListener('submit', function (ev) {
      ev.preventDefault();
      var el = elegidos();
      if (el.falta) { error.querySelector('span').textContent = 'Elige tu ' + el.falta + ' antes de agregarla.'; error.hidden = false; error.scrollIntoView({ block: 'center', behavior: 'smooth' }); return; }
      var cant = parseInt(cantEl.textContent, 10) || 1, items = leer(), clave = ficha.dataset.id + '|' + el.texto;
      var it = items.find(function (i) { return i.clave === clave; });
      var enCarrito = items.filter(function (i) { return String(i.id) === ficha.dataset.id; }).reduce(function (s, i) { return s + i.cantidad; }, 0);
      if (stock && enCarrito + cant > stock) { error.querySelector('span').textContent = 'Solo quedan ' + stock + ' y ya tienes ' + enCarrito + ' en tu pedido.'; error.hidden = false; return; }
      if (it) it.cantidad += cant;
      else items.push({ clave: clave, id: ficha.dataset.id, nombre: ficha.dataset.nombre, precio: parseFloat(ficha.dataset.precio), imagen: ficha.dataset.imagen, variante: el.texto, cantidad: cant, stock: stock });
      guardar(items);
      avisar('<i class="ti ti-circle-check" aria-hidden="true"></i>Agregado a tu pedido. <a href="index.php?action=ver_carrito">Ver pedido</a>');
    });
    pintarResumen();
  }

  var carro = document.getElementById('carro');
  if (carro) {
    var lista = document.getElementById('carro-lista'), errorCarro = carro.querySelector('[data-error]');
    var vista = function (v) { carro.querySelectorAll('[data-vista]').forEach(function (x) { x.hidden = x.dataset.vista !== v; }); var b = document.querySelector('[data-barra-pedido]'); if (b) b.hidden = v !== 'lista'; };
    var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };
    var pintar = function () {
      var items = leer();
      if (!items.length) { vista('vacio'); return; }
      vista('lista');
      lista.innerHTML = items.map(function (i, k) {
        return '<li class="carro-it"><a href="index.php?action=ver_producto&amp;' + urlClave('producto', i.id) + '">' + (i.imagen ? '<img src="' + esc(i.imagen) + '" alt="" width="96" height="128">' : '<span class="sin-foto"><i class="ti ti-hanger"></i></span>') + '</a>' +
          '<div class="carro-txt"><h2><a href="index.php?action=ver_producto&amp;' + urlClave('producto', i.id) + '">' + esc(i.nombre) + '</a></h2><p>' + esc(i.variante || '') + '</p>' +
          '<div class="cant"><button type="button" data-cambiar="-1" data-k="' + k + '" aria-label="Quitar una"><i class="ti ti-minus"></i></button><span>' + i.cantidad + '</span><button type="button" data-cambiar="1" data-k="' + k + '" aria-label="Agregar una"><i class="ti ti-plus"></i></button></div></div>' +
          '<div class="carro-precio"><b>' + usd(i.precio * i.cantidad) + '</b>' + (i.cantidad > 1 ? '<small>' + usd(i.precio) + ' c/u</small>' : '') + '</div>' +
          '<button type="button" class="ic carro-quitar" data-quitar="' + k + '" aria-label="Quitar ' + esc(i.nombre) + ' del pedido"><i class="ti ti-trash"></i></button></li>';
      }).join('');
      var total = items.reduce(function (s, i) { return s + i.precio * i.cantidad; }, 0), prendas = items.reduce(function (s, i) { return s + i.cantidad; }, 0);
      document.querySelectorAll('[data-total]').forEach(function (x) { x.textContent = usd(total); });
      document.querySelectorAll('[data-total-bs]').forEach(function (x) { x.textContent = bs(total); });
      carro.querySelector('[data-prendas]').textContent = prendas;
    };
    lista.addEventListener('click', function (ev) {
      var items = leer(), c = ev.target.closest('[data-cambiar]'), q = ev.target.closest('[data-quitar]');
      if (c) { var it = items[+c.dataset.k], n = it.cantidad + +c.dataset.cambiar; if (n < 1) return; if (it.stock && n > it.stock) { avisar('<i class="ti ti-info-circle" aria-hidden="true"></i>Solo quedan ' + it.stock + ' de esta prenda.'); return; } it.cantidad = n; }
      else if (q) items.splice(+q.dataset.quitar, 1);
      else return;
      guardar(items); pintar();
    });

    var mensaje = function (items, datos) {
      var total = items.reduce(function (s, i) { return s + i.precio * i.cantidad; }, 0);
      var l = ['Hola, ' + tienda + '. Quiero hacer este pedido' + (datos && datos.pedido ? ' (#' + datos.pedido + ')' : '') + ':', ''];
      items.forEach(function (i) { l.push('• ' + i.cantidad + ' x ' + i.nombre + (i.variante ? ' (' + i.variante + ')' : '') + ': ' + usd(i.precio * i.cantidad)); });
      l.push('', 'Total: ' + usd(datos && datos.total ? datos.total : total) + ' (' + bs(datos && datos.total ? datos.total : total) + ')');
      if (datos && datos.cliente) l.push('A nombre de: ' + datos.cliente.nombre + (datos.cliente.telefono ? ', ' + datos.cliente.telefono : ''));
      return l.join('\n');
    };
    var mostrarError = function (txt) { errorCarro.querySelector('span').textContent = txt; errorCarro.hidden = false; };
    var pedir = function () {
      var items = leer();
      if (!items.length) return;
      if (carro.dataset.sesion !== '1') { location.href = 'index.php?action=login_usuario&volver=carrito'; return; }
      var numero = wa;
      if (!numero) { mostrarError('La tienda todavía no tiene un WhatsApp configurado.'); return; }
      errorCarro.hidden = true;
      var botones = document.querySelectorAll('[data-pedir]');
      botones.forEach(function (b) { b.disabled = true; b.dataset.texto = b.innerHTML; b.innerHTML = 'Enviando…'; });
      var ventana = window.open('', '_blank');
      var terminar = function (datos) {
        var url = 'https://wa.me/' + (datos && datos.whatsapp ? String(datos.whatsapp).replace(/\D/g, '') : numero) + '?text=' + encodeURIComponent(mensaje(items, datos));
        if (ventana) ventana.location = url; else location.href = url;
        guardar([]);
        carro.querySelector('[data-wa-otra]').href = url;
        if (datos && datos.pedido) carro.querySelector('[data-enviado-txt]').textContent = 'Tu pedido #' + datos.pedido + ' quedó registrado. Te confirmamos disponibilidad y forma de pago por WhatsApp.';
        vista('enviado');
      };
      var fallo = function (txt) {
        if (ventana) ventana.close();
        botones.forEach(function (b) { b.disabled = false; b.innerHTML = b.dataset.texto; });
        mostrarError(txt);
      };
      fetch('index.php?action=crear_pedido', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, credentials: 'same-origin', body: JSON.stringify({ items: items.map(function (i) { return { id: i.id, cantidad: i.cantidad, variante: i.variante }; }) }) })
        .then(function (r) { return r.text(); })
        .then(function (t) {
          var datos = null;
          try { datos = JSON.parse(t); } catch (e) { terminar(null); return; }
          if (datos.ok) { terminar(datos); return; }
          if (datos.error === 'sesion') { if (ventana) ventana.close(); location.href = 'index.php?action=login_usuario&volver=carrito'; return; }
          if (datos.error === 'stock') { var it = items.find(function (i) { return String(i.id) === String(datos.id); }); fallo((it ? it.nombre : 'Una prenda') + ': solo quedan ' + (datos.disponible || 0) + '. Ajusta la cantidad y vuelve a intentar.'); return; }
          fallo('No pudimos registrar tu pedido. Inténtalo otra vez en un momento.');
        })
        .catch(function () { terminar(null); });
    };
    document.querySelectorAll('[data-pedir]').forEach(function (b) { b.addEventListener('click', pedir); });
    pintar();
  }

  contador();
  window.addEventListener('storage', function (ev) { if (ev.key === CLAVE) contador(); });
})();
