jQuery(document).ready(function ($) {
    // Manejador de clic para letras del alfabeto
    $('.alphabet-filter a').on('click', function (e) {
        e.preventDefault();
        const selectedLetter = $(this).data('letter');

               // Obtener bloque contenedor
        const clusterContainer = $(this).closest('.alphabet-filter').siblings('.cluster-cats');
        const showImages = clusterContainer.data('show-images');
        const styleGrid = clusterContainer.data('style-grid');
        const showDescription = clusterContainer.data('show-description');
        const showCount = clusterContainer.data('show-count');
        const targetBlank = clusterContainer.data('target-blank');
        const hideEmpty = clusterContainer.data('hideEmpty');

        
        // Mostrar un mensaje de carga
        clusterContainer.html('<p>Loading...</p>');

        // Solicitud AJAX
        $.ajax({
            url: phpData.ajax_url, // Define ajax_url en wp_localize_script
            type: 'POST',
            
            data: {
                action: 'filter_categories',
                letter: selectedLetter,
                showImages: showImages,
                styleGrid: styleGrid,
                showDescription: showDescription,
                showCount: showCount,
                targetBlank: targetBlank,
                hideEmpty:hideEmpty,
                security:phpData.security,
            },
            success: function (response) {

                if (response.success) {
                    
                    clusterContainer.html(response.data.html);
                } else {
                    clusterContainer.html('<p>No categories found.</p>');
                }
            },
            error: function () {
                
                clusterContainer.html('<p>Error loading categories.</p>');
            }
        });
    });
});
