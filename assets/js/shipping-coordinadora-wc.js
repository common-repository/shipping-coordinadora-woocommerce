(function($){
    $('button.shipping_coordinadora_update_cities').click(function(e){
        e.preventDefault();
        $.ajax({
            method: 'GET',
            url: ajaxurl,
            data: {action: 'shipping_coordinadora_wc_cswc'},
            dataType: 'json',
            beforeSend: () => {
                Swal.fire({
                    title: 'Actualizando',
                    onOpen: () => {
                        Swal.showLoading()
                    },
                    allowOutsideClick: false
                });
            },
            success: (res) => {
                if (res.status){
                    Swal.fire({
                        title: 'Se ha actualizado exitosamente',
                        text: 'redireccionando a configuraciones...',
                        type: 'success',
                        showConfirmButton: false
                    });
                   window.location.replace(shippingCoordinadora.urlConfig);
                }else{
                    Swal.fire({
                        type: 'error',
                        title: 'Oops...',
                        text: res.message
                    })
                }
            }
        });
    });
    $('button.generate_label').click(function (e) {
        e.preventDefault();

        $.ajax({
            data: {
                action: 'coordinadora_generate_label',
                nonce: $(this).data("nonce"),
                guide_number: $(this).data("guide")
            },
            type: 'POST',
            url: ajaxurl,
            dataType: "json",
            beforeSend : () => {
                Swal.fire({
                    title: 'Generando rótulo de la guía',
                    onOpen: () => {
                        Swal.showLoading()
                    },
                    allowOutsideClick: false
                });
            },
            success: (r) => {
                if (r.url){
                    Swal.fire({
                        icon: 'success',
                        html: `<a target="_blank" href="${r.url}">Ver rótulo</a>`,
                        allowOutsideClick: false,
                        showCloseButton: true,
                        showConfirmButton: false
                    })
                }else{
                    Swal.fire(
                        'Error',
                        r.message,
                        'error'
                    );
                }
            }
        });
    });
})(jQuery);