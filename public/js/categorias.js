(function () {
  var modal = document.getElementById('modal-categoria'), lista = document.getElementById('mcat-atributos');
  var tpl = document.getElementById('tpl-atributo');
  var selPadre = document.getElementById('mcat-padre');
  var campoNuevaPadre = document.getElementById('campo-nueva-padre');
  var inpNuevaPadre = document.getElementById('mcat-nueva-padre');

  function toggleNuevaPadre() {
    if (selPadre && campoNuevaPadre) {
      var esNueva = selPadre.value === 'nueva_padre';
      campoNuevaPadre.style.display = esNueva ? 'block' : 'none';
      if (inpNuevaPadre) {
        inpNuevaPadre.required = esNueva;
        if (esNueva) inpNuevaPadre.focus();
        else inpNuevaPadre.value = '';
      }
    }
  }

  if (selPadre) {
    selPadre.addEventListener('change', toggleNuevaPadre);
  }

  function agregar(a) {
    var fila = tpl.content.firstElementChild.cloneNode(true), n = lista.children.length;
    var campos = fila.querySelectorAll('input');
    campos[0].value = a ? a.id : '';
    campos[1].value = a ? a.nombre : '';
    campos[2].value = a ? a.opciones : '';
    fila.querySelectorAll('.campo').forEach(function (c, i) {
      var id = 'mcat-atr-' + n + '-' + i;
      c.querySelector('label').htmlFor = id;
      c.querySelector('input').id = id;
    });
    lista.appendChild(fila);
    return fila;
  }

  document.addEventListener('click', function (e) {
    var abrir = e.target.closest('[data-categoria]');
    if (abrir) {
      var c = abrir.getAttribute('data-categoria') ? JSON.parse(abrir.getAttribute('data-categoria')) : null;
      document.getElementById('mcat-titulo').textContent = c ? 'Editar ' + c.nombre : 'Nueva categoría';
      document.getElementById('mcat-id').value = c ? c.id : '';
      document.getElementById('mcat-nombre').value = c ? c.nombre : '';
      
      // Manejo de categoría padre / subcategoría
      if (selPadre) {
        selPadre.value = (c && c.id_padre) ? c.id_padre : '';
        // Ocultar o deshabilitar la opción de sí misma para que no sea padre de sí misma
        Array.from(selPadre.options).forEach(function (opt) {
          opt.disabled = Boolean(c && String(opt.value) === String(c.id));
        });
      }
      toggleNuevaPadre();

      lista.innerHTML = '';
      (c ? c.atributos : []).forEach(agregar);
      modal.showModal();
      document.getElementById('mcat-nombre').focus();
      return;
    }
    if (e.target.closest('#mcat-agregar')) { agregar().querySelector('[name="atributo_nombre[]"]').focus(); return; }
    var quitar = e.target.closest('[data-quitar-atributo]');
    if (quitar) quitar.closest('.atr-fila').remove();
  });
})();