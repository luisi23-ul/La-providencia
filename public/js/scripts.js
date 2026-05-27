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