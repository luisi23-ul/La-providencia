// public/js/navbar.js
console.log("¡navbar.js cargado correctamente!");

document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('.menu-toggle');
    
    // Intentamos buscar la cortina por su clase o por su etiqueta si usas una común
    let navMenuResponsive = document.querySelector('.nav-menu-responsive');

    console.log("Botón hamburguesa encontrado:", menuToggle);
    console.log("Menú cortina encontrado:", navMenuResponsive);

    if (menuToggle) {
        menuToggle.addEventListener('click', (e) => {
            e.preventDefault(); 
            
            // Si la cortina dio null al cargar, la volvemos a buscar en caliente al hacer clic
            if (!navMenuResponsive) {
                navMenuResponsive = document.querySelector('.nav-menu-responsive') || document.querySelector('nav[class*="responsive"]');
            }

            if (navMenuResponsive) {
                // Cambia el estado de las clases de CSS
                menuToggle.classList.toggle('active');
                navMenuResponsive.classList.toggle('active');
                console.log("¡Clic detectado! Menú responsivo desplegado.");
            } else {
                console.error("Error crítico: El botón existe pero no se encuentra la etiqueta de la cortina (.nav-menu-responsive) en el HTML.");
            }
        });
    } else {
        console.warn("No se encontró el botón hamburguesa (.menu-toggle) en esta pantalla.");
    }

    
});