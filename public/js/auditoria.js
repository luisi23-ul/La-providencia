(function () {
  var form = document.getElementById('filtros-aud');
  if (!form) return;
  form.addEventListener('change', function (e) {
    if (e.target.name !== 'q') form.submit();
  });
  form.q.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); form.submit(); }
  });
})();
