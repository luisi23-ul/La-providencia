function confirmarEliminar(id) {
    Swal.fire({
        title: '¿Eliminar producto?',
        text: "Esta acción borrará el producto permanentemente del inventario.",
        icon: 'warning',
        showCancelButton: true,
        
        // Colores claros y directos
        confirmButtonColor: '#e11d48', // Un rojo vibrante (marca de peligro)
        cancelButtonColor: '#64748b',   // Un gris neutro (marca de seguridad)
        
        // Textos claros y en español
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        
        // Estilo visual
        reverseButtons: true, // Ponemos el botón importante a la derecha
        buttonsStyling: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'index.php?action=eliminar_producto&id=' + id;
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const btnBuscar = document.getElementById('btn-buscar');
    const inputBusqueda = document.getElementById('input-busqueda');
    const formBusqueda = document.getElementById('form-busqueda');

    if (btnBuscar && formBusqueda) {
        // Al hacer clic en el botón
        btnBuscar.addEventListener('click', function() {
            formBusqueda.submit();
        });

        // Al presionar Enter en el input
        inputBusqueda.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                formBusqueda.submit();
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const inputBusqueda = document.getElementById('input-busqueda');
    const tarjetas = document.querySelectorAll('.producto-item');
    const btnBuscar = document.getElementById('btn-buscar');

    function filtrarProductos() {
        const termino = inputBusqueda.value.toLowerCase();

        tarjetas.forEach(tarjeta => {
            // Comparamos el nombre guardado en data-nombre con lo que escribe el usuario
            const nombreProducto = tarjeta.getAttribute('data-nombre');
            
            if (nombreProducto.includes(termino)) {
                tarjeta.style.display = ''; // Lo muestra
            } else {
                tarjeta.style.display = 'none'; // Lo oculta
            }
        });
    }

    // Filtra al escribir
    inputBusqueda.addEventListener('keyup', filtrarProductos);
    
    // Filtra al dar clic al botón
    if(btnBuscar) {
        btnBuscar.addEventListener('click', filtrarProductos);
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const registroForm = document.querySelector('form[action*="registrar_cliente"]');

    if (registroForm) {
        registroForm.addEventListener('submit', function(e) {
            const clave = document.querySelector('input[name="clave"]').value;
            const confirmClave = document.querySelector('input[name="confirmar_clave"]'); // Asegúrate que tu input tenga este name o ID
            
            // 1. Validación de coincidencia de contraseñas
            if (confirmClave && clave !== confirmClave.value) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: '¡Espera!',
                    text: 'Las contraseñas no coinciden.',
                    confirmButtonColor: '#0052d4'
                });
                return;
            }

            // 2. Opcional: Alerta de procesando registro
            Swal.fire({
                title: 'Registrando...',
                text: 'Por favor, espera un momento.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        });
    }
});

window.alert = function(mensaje) {
    let titulo = "Información";
    let icono = "info";

    // Detectamos qué dice el mensaje para personalizar la alerta
    if (mensaje.includes("exitoso") || mensaje.includes("Bienvenido")) {
        titulo = "¡Éxito!";
        icono = "success";
    } else if (mensaje.includes("error") || mensaje.includes("incorrecto")) {
        titulo = "¡Ups!";
        icono = "error";
    }

    Swal.fire({
        icon: icono,
        title: titulo,
        text: mensaje,
        confirmButtonColor: '#0052d4',
        confirmButtonText: 'Aceptar'
    });
};