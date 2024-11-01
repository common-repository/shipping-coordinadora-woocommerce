<?php

wc_enqueue_js( "
    jQuery( function( $ ) {
	
	let shipping_coordinadora_live_tracing_fields = '#woocommerce_shipping_coordinadora_wc_api_key, #woocommerce_shipping_coordinadora_wc_password_tracing, #woocommerce_shipping_coordinadora_wc_nit';
	let shipping_coordinadora_live_guides_fields = '#woocommerce_shipping_coordinadora_wc_id_client, #woocommerce_shipping_coordinadora_wc_user, #woocommerce_shipping_coordinadora_wc_password_guides, #woocommerce_shipping_coordinadora_wc_code_account';
	
	let shipping_coordinadora_sandbox_tracing_fields = '#woocommerce_shipping_coordinadora_wc_sandbox_api_key, #woocommerce_shipping_coordinadora_wc_sandbox_password_tracings, #woocommerce_shipping_coordinadora_wc_sandbox_nit';
	let shipping_coordinadora_sandbox_guides_fields = '#woocommerce_shipping_coordinadora_wc_sandbox_id_client, #woocommerce_shipping_coordinadora_wc_sandbox_user, #woocommerce_shipping_coordinadora_wc_sandbox_password_guides, #woocommerce_shipping_coordinadora_wc_sandbox_code_account';

	$( '#woocommerce_shipping_coordinadora_wc_environment' ).change(function(){

		$( shipping_coordinadora_sandbox_tracing_fields + ',' + shipping_coordinadora_live_tracing_fields ).closest( 'tr' ).hide();
		$( shipping_coordinadora_sandbox_guides_fields + ',' + shipping_coordinadora_live_guides_fields ).closest( 'tr' ).hide();

		if ( '0' === $( this ).val() ) {
		    $( '#woocommerce_shipping_coordinadora_wc_dispatches, #woocommerce_shipping_coordinadora_wc_dispatches + p' ).show();
		    $( '#woocommerce_shipping_coordinadora_wc_guides, #woocommerce_shipping_coordinadora_wc_guides + p' ).show();
			$( '#woocommerce_shipping_coordinadora_wc_sandbox_dispatches, #woocommerce_shipping_coordinadora_wc_sandbox_dispatches + p' ).hide();
			$( '#woocommerce_shipping_coordinadora_wc_sandbox_guides, #woocommerce_shipping_coordinadora_wc_sandbox_guides + p' ).hide();
			$( shipping_coordinadora_live_tracing_fields ).closest( 'tr' ).show();
			$( shipping_coordinadora_live_guides_fields ).closest( 'tr' ).show();
			
		}else{
		   $( '#woocommerce_shipping_coordinadora_wc_dispatches, #woocommerce_shipping_coordinadora_wc_dispatches + p' ).hide();
		   $( '#woocommerce_shipping_coordinadora_wc_guides, #woocommerce_shipping_coordinadora_wc_guides + p' ).hide();
		   $( '#woocommerce_shipping_coordinadora_wc_sandbox_dispatches, #woocommerce_shipping_coordinadora_wc_sandbox_dispatches + p' ).show();
		   $( '#woocommerce_shipping_coordinadora_wc_sandbox_guides, #woocommerce_shipping_coordinadora_wc_sandbox_guides + p' ).show();
		   $( shipping_coordinadora_sandbox_tracing_fields ).closest( 'tr' ).show();
		   $( shipping_coordinadora_sandbox_guides_fields ).closest( 'tr' ).show();
		}
	}).change();	
});	
");

global $wpdb;
$table_name = $wpdb->prefix . 'shipping_coordinadora_cities';
$query = "SELECT * FROM $table_name";
$cities = $wpdb->get_results(
    $query
);
$sending_cities = array();
if (!empty($cities)){
    foreach ($cities as $city){
        $sending_cities[$city->codigo] = "$city->nombre, $city->nombre_departamento";
    }
}

$docs_url = '<a target="_blank" href="https://shop.saulmoralespa.com/shipping-coordinadora-woocommerce/">' . __( 'Ver documentación completa del plugin') . '</a>';
$cities_not_loaded = '<a href="' . esc_url(admin_url( 'admin.php?page=coordinadora-install-setp' )) . '">' . __( 'Para cargar las ciudades, clic aquí') . '</a>';
$license_key_not_loaded = '<a target="_blank" href="' . esc_url('https://shop.saulmoralespa.com/producto/plugin-shipping-coordinadora-woocommerce/') . '">' . __( 'Obtener una licencia desde aquí') . '</a>';
$collection_url = '<a href="' . esc_url(admin_url( 'admin.php?page=wc-settings&tab=shipping&section=shipping_coordinadora_collection_wc' )) . '">' . __( 'Habilitar pago con recaudo') . '</a>';

$docs = array(
    'docs'  => array(
        'title' => __( 'Documentación' ),
        'type'  => 'title',
        'description' => $docs_url
    )
);

if (empty($this->get_option( 'license_key' ))){
    $license_key_title = array(
        'license_key_title' => array(
            'title'       => __( 'Se require una licencia para uso completo'),
            'type'        => 'title',
            'description' => $license_key_not_loaded
        )
    );
}else{
    $license_key_title = array();
}

$license_key = array(
    'license_key'  => array(
        'title' => __( 'Licencia' ),
        'type'  => 'password',
        'description' => __( 'La licencia para su uso, según la cantidad de sitios por la cual la haya adquirido' ),
        'desc_tip' => true
    )
);

if (empty($sending_cities)){
    $sending_cities_select = array(
        'shipping_cities_not_select' => array(
            'title'       => __( 'Las ciudades no estan cargadas!!!'),
            'type'        => 'title',
            'description' => $cities_not_loaded
        )
    );
}else{
    $sending_cities_select = array(
        'city_sender' => array(
            'title' => __('Ciudad del remitente (donde se encuentra ubicada la tienda)'),
            'type'        => 'select',
            'class'       => 'wc-enhanced-select',
            'description' => __('Se recomienda selecionar ciudadades centrales'),
            'desc_tip' => true,
            'default' => true,
            'options'     => $sending_cities
        )
    );
}

$collection_title = array(
    'collection_title' => array(
        'title'       => __( 'Pago con recaudo'),
        'type'        => 'title',
        'description' => $collection_url
    )
);

return apply_filters(
    'coordinadora_shipping_settings',
    array_merge(
    $docs,
    array(
        'enabled' => array(
            'title' => __('Activar/Desactivar'),
            'type' => 'checkbox',
            'label' => __('Activar  Coordinadora'),
            'default' => 'no'
        ),
        'title'        => array(
            'title'       => __( 'Título método de envío' ),
            'type'        => 'text',
            'description' => __( 'Esto controla el título que el usuario ve durante el pago' ),
            'default'     => __( 'Coordinadora' ),
            'desc_tip'    => true
        ),
        'debug'        => array(
            'title'       => __( 'Depurador' ),
            'label'       => __( 'Habilitar el modo de desarrollador' ),
            'type'        => 'checkbox',
            'default'     => 'no',
            'description' => __( 'Enable debug mode to show debugging information on your cart/checkout.' ),
            'desc_tip' => true
        ),
        'environment' => array(
            'title' => __('Entorno'),
            'type'        => 'select',
            'class'       => 'wc-enhanced-select',
            'description' => __('Entorno de pruebas o producción'),
            'desc_tip' => true,
            'default' => '1',
            'options'     => array(
                '0'    => __( 'Producción'),
                '1' => __( 'Pruebas')
            ),
        ),
        'div' => array(
            'title' => __('Div asociado a un acuerdo Coordinadora Mercantil'),
            'type'        => 'select',
            'class'       => 'wc-enhanced-select',
            'description' => __('Seleccione Si tiene un acuerdo de pago con Coordinadora Mercantil <br/> 
            <em style="color:orange;">En modo pruebas intente con No </em>'),
            'desc_tip' => false,
            'default' => '00',
            'options'     => array(
                '00'    => __( 'No'),
                '01' => __( 'Si')
            )
        ),
        'weight_max' => array(
            'title' => __( 'Peso máximo de kilos según acuerdo con Coordinadora' ),
            'type'  => 'select',
            'class' => 'wc-enhanced-select',
            'description' => __( 'El mínimo es 5 kilos' ),
            'desc_tip' => true,
            'default' => 5,
            'options'     => array(
                5 => __( '5 kilos'),
                30 => __( '30 kilos')
            )
        ),
        'sender'  => array(
            'title' => __( 'Remitente' ),
            'type'  => 'title',
            'description' => __( 'Información requerida del remitente' )
        ),
        'sender_name' => array(
            'title'       => __( 'Nombre remitente' ),
            'type'        => 'text',
            'description' => __( 'Debe ir la razon social o el nombre comercial' ),
            'default'     => get_bloginfo('name'),
            'desc_tip'    => true
        ),
        'phone_sender'      => array(
            'title' => __( 'Teléfono del remitente' ),
            'type'  => 'text',
            'description' => __( 'Necesario para la generación de guías' ),
            'desc_tip' => true
        )
    ),
    $license_key_title,
    $sending_cities_select,
    $license_key,
    array(
       'guide_free_shipping' => array(
           'title'       => __( 'Generar guías cuando el envío es gratuito' ),
           'label'       => __( 'Habilitar la generación de guías para envíos gratuitos' ),
           'type'        => 'checkbox',
           'default'     => 'no',
           'description' => __( 'Permite la generación de guías cuando el envío es gratuito' ),
           'desc_tip' => true
       )
    ),
    $collection_title,
    array(
        'dispatches'          => array(
            'title'       => __( 'Seguimiento de despachos' ),
            'type'        => 'title',
            'description' => __( 'Apikey, contraseña y el NIT asociado para el entorno de producción' )
        ),
        'api_key'      => array(
            'title' => __( 'API Key' ),
            'type'  => 'text',
            'description' => __( 'Api key provisto por Coordinadora' ),
            'desc_tip' => true
        ),
        'password_tracing' => array(
            'title' => __( 'Contraseña' ),
            'type'  => 'password',
            'description' => __( 'La clave del webservice para seguimiento de envios' ),
            'desc_tip' => true
        ),
        'nit' => array(
            'title' => __( 'NIT' ),
            'type'  => 'number',
            'description' => __( 'Nit asociado a un acuerdo Coordinadora Mercantil' ),
            'desc_tip' => true
        ),
        'sandbox_dispatches'          => array(
            'title'       => __( 'Seguimiento de despachos (pruebas)' ),
            'type'        => 'title',
            'description' => __( 'Apikey, contraseña y el NIT asociado para el entorno de pruebas' ),
        ),
        'sandbox_api_key'      => array(
            'title' => __( 'API Key' ),
            'type'  => 'text',
            'description' => __( 'Api key provisto por Coordinadora' ),
            'desc_tip' => true
        ),

        'sandbox_password_tracings' => array(
            'title' => __( 'Contraseña' ),
            'type'  => 'password',
            'description' => __( 'La clave del webservice para seguimiento de envios' ),
            'desc_tip' => true
        ),
        'sandbox_nit' => array(
            'title' => __( 'NIT' ),
            'type'  => 'number',
            'description' => __( 'Nit asociado a un acuerdo Coordinadora Mercantil' ),
            'desc_tip' => true
        ),
        'guides'          => array(
            'title'       => __( 'Generación de guías' ),
            'type'        => 'title',
            'description' => __( 'id_cliente, usuario y contraseña para el entorno de producción' ),
        ),
        'id_client' => array(
            'title' => __( 'id_cliente' ),
            'type'  => 'number',
            'description' => __( 'id_cliente indica el acuerdo con que se va a liquidar' ),
            'desc_tip' => true
        ),
        'user' => array(
            'title' => __( 'Usuario' ),
            'type'  => 'text',
            'description' => __( 'Usuario asignado' ),
            'desc_tip' => true
        ),
        'password_guides' => array(
            'title' => __( 'Contraseña' ),
            'type'  => 'password',
            'description' => __( 'No confunda con la de seguimiento de despachos' ),
            'desc_tip' => true
        ),
        'code_account' => array(
            'title' => __( 'Acuerdo de pago' ),
            'type'        => 'select',
            'class'       => 'wc-enhanced-select',
            'description' => __( 'El acuerdo de pago Cuenta Corriente, Acuerdo Semanal, Flete Pago' ),
            'desc_tip' => false,
            'default' => 1,
            'options'     => array(
                1    => __( 'Cuenta Corriente'),
                2    => __( 'Acuerdo Semanal'),
                3    => __( 'Flete Pago'),
                6    => __( 'Flete Contra Entrega')
            )
        ),
        'sandbox_guides'          => array(
            'title'       => __( 'Generación de guias (pruebas)' ),
            'type'        => 'title',
            'description' => __( 'id_cliente, usuario y contraseña para el entorno de pruebas' )
        ),
        'sandbox_id_client' => array(
            'title' => __( 'id_cliente' ),
            'type'  => 'number',
            'description' => __( 'id_cliente indica el acuerdo con que se va a liquidar' ),
            'desc_tip' => true
        ),
        'sandbox_user' => array(
            'title' => __( 'Usuario' ),
            'type'  => 'text',
            'description' => __( 'Usuario asignado' ),
            'desc_tip' => true
        ),
        'sandbox_password_guides' => array(
            'title' => __( 'Contraseña' ),
            'type'  => 'password',
            'description' => __( 'No confunda con la de seguimiento de despachos' ),
            'desc_tip' => true
        ),
        'sandbox_code_account' => array(
            'title' => __( 'Acuerdo de pago' ),
            'type'        => 'select',
            'class'       => 'wc-enhanced-select',
            'description' => __( 'El acuerdo de pago Cuenta Corriente, Acuerdo Semanal, Flete Pago' ),
            'desc_tip' => false,
            'default' => 1,
            'options'     => array(
                1    => __( 'Cuenta Corriente'),
                2    => __( 'Acuerdo Semanal'),
                3    => __( 'Flete Pago'),
                6    => __( 'Flete Contra Entrega')
            )
        )
    )
)
);