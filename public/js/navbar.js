// public/js/navbar.js
document.addEventListener('DOMContentLoaded', () => {
    // Si estamos en el panel, nos aseguramos de que el menú no haga nada
    if (document.querySelector('.panel-administracion')) {
        return; 
    }

    const menuToggle = document.querySelector('.menu-toggle');
    if (!menuToggle) return;

    menuToggle.addEventListener('click', (e) => {
        e.preventDefault();
        const navMenuResponsive = document.querySelector('.nav-menu-responsive');
        if (navMenuResponsive) {
            menuToggle.classList.toggle('active');
            navMenuResponsive.classList.toggle('active');
        }
    });
});