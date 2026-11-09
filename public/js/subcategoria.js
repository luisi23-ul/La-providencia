(function () {
  var padre = document.getElementById('fp-cat-padre');
  var sub = document.getElementById('fp-subcat');
  var valor = document.getElementById('fp-cat');
  var ayuda = document.getElementById('fp-subcat-ayuda');
  var plantilla = document.getElementById('fp-subcats');
  if (!padre || !sub || !valor || !plantilla) return;

  var hijas = Array.prototype.map.call(plantilla.content.querySelectorAll('option'), function (o) {
    return { id: o.value, padre: o.getAttribute('data-padre'), nombre: o.textContent };
  });

  function opcion(v, texto) {
    var o = document.createElement('option');
    o.value = v;
    o.textContent = texto;
    return o;
  }

  function pintar(elegida) {
    var p = padre.value;
    var nombre = p ? padre.options[padre.selectedIndex].text : '';
    var lista = hijas.filter(function (h) { return h.padre === p; });
    while (sub.firstChild) sub.removeChild(sub.firstChild);
    ayuda.hidden = true;
    if (!p) {
      sub.appendChild(opcion('', 'Elige primero una categoría'));
      sub.disabled = true;
    } else if (!lista.length) {
      sub.appendChild(opcion('', 'Sin subcategorías'));
      sub.disabled = true;
      ayuda.textContent = nombre + ' no tiene subcategorías. El producto queda en ' + nombre + '.';
      ayuda.hidden = false;
    } else {
      sub.appendChild(opcion('', 'Ninguna (solo ' + nombre + ')'));
      lista.forEach(function (h) { sub.appendChild(opcion(h.id, h.nombre)); });
      sub.disabled = false;
      sub.value = elegida || '';
    }
  }

  function guardar() {
    var v = sub.value || padre.value;
    if (valor.value === v) return;
    valor.value = v;
    valor.dispatchEvent(new Event('change', { bubbles: true }));
  }

  padre.addEventListener('change', function () { pintar(''); guardar(); });
  sub.addEventListener('change', guardar);
  pintar(sub.getAttribute('data-elegida'));
})();
