jQuery(document).ready(function ($) {
    // Modal al abierto apenas lo encuentre
    $("#wcma-modal").dialog({
        autoOpen: false, 
        modal: true,
        width: 'auto',
        height: 'auto',
        title: $("#wcma-modal").attr("title"),
        closeText: "Cerrar",
        show: {
            effect: "fadeIn",
            duration: 500 
        }
    });

    // Delay del modaml
    setTimeout(function() {
        $("#wcma-modal").dialog("open");
    }, 200); 

    // Controlador de eventos para el botón "Añadir al carrito"
    $('.wcma-add-to-cart').on('click', function () {
        var product_id = $(this).data('product_id');
        var button = $(this);

        $.ajax({
            url: wcma_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcma_add_products_to_cart',
                product_id: product_id
            },
            beforeSend: function () {
                button.prop('disabled', true).text('Agregando...');
            },
            success: function (response) {
                if (response.success) {
                    $(document.body).trigger('wc_fragment_refresh');
                    button.text('Agregado').css('background-color', 'green');

                    if ($('body').hasClass('woocommerce-checkout')) {
                        $('body').trigger('update_checkout');
                    }
                } else {
                    button.text('Error').css('background-color', 'red');
                }
            },
            complete: function () {
                setTimeout(function () {
                    button.prop('disabled', false).text('Añadir otro').css('background-color', '');
                }, 2300);
            }
        });
    });
});
