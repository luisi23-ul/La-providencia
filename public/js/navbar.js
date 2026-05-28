(function() {
    // Si ya existe la bandera 'yaCorri', no volvemos a correr
    if (window.yaCorri) return;
    window.yaCorri = true;

    console.log("Navbar JS inicializado de forma segura.");

    document.addEventListener('DOMContentLoaded', () => {
        const boton = document.querySelector('.menu-toggle');
        const menu = document.querySelector('.nav-menu-responsive');

        if (!boton) {
            console.warn("No encontré el botón .menu-toggle");
            return;
        }

        boton.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation(); // Evita que otros scripts reciban el clic
            
            console.log("¡Clic procesado!");
            menu.classList.toggle('active');
            boton.classList.toggle('active');
        });
    });
})();