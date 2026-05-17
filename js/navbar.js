// public/js/navbar.js
console.log("¡navbar.js cargado correctamente!");

document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenuResponsive = document.querySelector('.nav-menu-responsive');

    // Imprime en consola para verificar si JS encuentra los elementos en la pantalla actual
    console.log("Botón hamburguesa encontrado:", menuToggle);
    console.log("Menú cortina encontrado:", navMenuResponsive);

    if (menuToggle && navMenuResponsive) {
        menuToggle.addEventListener('click', (e) => {
            e.preventDefault(); // Evita cualquier comportamiento extraño del botón
            
            // Cambia el estado de las clases de CSS
            menuToggle.classList.toggle('active');
            navMenuResponsive.classList.toggle('active');
            
            console.log("¡Clic detectado! Estado activo cambiado.");
        });
    } else {
        console.warn("No se encontraron los elementos del menú en esta vista.");
    }
});