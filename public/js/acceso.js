document.querySelector('.ver-clave')?.addEventListener('click', e => {
  const boton = e.currentTarget;
  const campo = boton.parentElement.querySelector('input');
  const mostrar = campo.type === 'password';
  campo.type = mostrar ? 'text' : 'password';
  boton.setAttribute('aria-pressed', mostrar);
  boton.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
  boton.querySelector('use').setAttribute('href', mostrar ? '#i-ojo-no' : '#i-ojo');
});

document.querySelector('.acceso-form form')?.addEventListener('submit', e => {
  const boton = e.target.querySelector('.btn-entrar');
  boton.disabled = true;
  boton.innerHTML = '<span class="giro" aria-hidden="true"></span><span>Entrando…</span>';
});

window.addEventListener('pageshow', e => {
  if (!e.persisted) return;
  const boton = document.querySelector('.btn-entrar');
  boton.disabled = false;
  boton.innerHTML = '<span>Entrar al panel</span><svg class="i"><use href="#i-entrar"/></svg>';
});
