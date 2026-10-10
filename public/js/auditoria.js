(function () {
  var form = document.getElementById('filtros-aud');
  if (!form) return;
  form.q.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); form.submit(); }
  });
})();
