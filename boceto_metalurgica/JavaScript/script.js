document.addEventListener('DOMContentLoaded', () => {
    // Abrir detalles
    document.querySelectorAll('.toggle-details').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            // Cierra otros paneles abiertos si deseas que solo haya uno abierto a la vez (opcional)
            document.querySelectorAll('.service-details.active').forEach(panel => {
                panel.classList.remove('active');
            });
            
            // Abre el panel hermano del enlace clickeado
            const detailsPanel = e.target.nextElementSibling;
            if(detailsPanel) {
                detailsPanel.classList.add('active');
            }
        });
    });

    // Cerrar detalles
    document.querySelectorAll('.close-details').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.target.closest('.service-details').classList.remove('active');
        });
    });
});