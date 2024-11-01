(function($){
    $('button.tracking-coordinadora').click(function(e){
        e.preventDefault();

        $.ajax({
            data: {
                action: 'coordinadora_tracking',
                nonce: $(this).data("nonce"),
                guide_number: $(this).data("guide")
            },
            type: 'POST',
            url: wc_add_to_cart_params.ajax_url,
            dataType: "json",
            beforeSend : () => {
                Swal.fire({
                    title: 'Consultando...',
                    onOpen: () => {
                        Swal.showLoading()
                    },
                    allowOutsideClick: false
                });
            },
            success: (r) => {
                if (r[0] && r[0].descripcion_estado.length){
                    let status = r[0].descripcion_estado
                    Swal.fire({
                        icon: 'info',
                        title: status,
                        allowOutsideClick: false,
                        showCloseButton: true,
                        showConfirmButton: false
                    })
                }else{
                    Swal.fire(
                        'Error',
                        'No se puede consultar el estado de esta guía',
                        'error'
                    );
                }
            }
        });
    })
})(jQuery);