<?php

use Coordinadora\WebService;

class Shipping_Coordinadora_WC extends WC_Shipping_Method_Shipping_Coordinadora_WC
{

    public $coordinadora;

    public function __construct($instance_id = 0)
    {
        parent::__construct($instance_id);

        $this->coordinadora = new WebService($this->apikey, $this->password_tracings, $this->nit, $this->id_client, $this->user, $this->password_guides);
        $this->coordinadora->sandbox_mode($this->isTest);
    }

    public static function update_cities()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'shipping_coordinadora_cities';
        $sql = "DELETE FROM $table_name";
        $wpdb->query($sql);

        shipping_coordinadora_wc_cswc()->createTable();

        $message_error = '';

        try{
            $cities = WebService::Cotizador_ciudades();
            foreach ($cities->item as  $city){

                if ($city->estado == 'activo'){
                    $name = explode(' (', $city->nombre);
                    $name = ucfirst(mb_strtolower($name[0]));
                    $wpdb->insert(
                        $table_name,
                        array(
                            'nombre' => $name,
                            'codigo' => $city->codigo,
                            'nombre_departamento' => $city->nombre_departamento
                        )
                    );
                }
            }
        }catch (\Exception $exception){
            $message_error = $exception->getMessage();
            shipping_coordinadora_wc_cswc()->log($message_error);
            shipping_coordinadora_wc_cswc()->log($exception->getCode());
        }

        $instance = new self();

        if (wp_doing_ajax())
            wp_die($instance->ajax_response_update_cities($message_error));

    }

    public function ajax_response_update_cities($message_error)
    {
       $res = array();
       $res['status'] = false;
       $res['message'] = $message_error;

       if (empty($message_error))
           $res['status'] = true;

       return json_encode($res);

    }

    public static function test_connection_tracing()
    {

        $instance = new self();

        $cart_prods = array(
            'ubl'      => '0',
            'alto'     => '70',
            'ancho'    => '10',
            'largo'    => '50',
            'peso'     => '1',
            'unidades' => '1',
        );

        $params = array(
            'div'            => $instance->div,
            'cuenta'         => $instance->code_account,
            'producto'       => '0',
            'origen'         => "13001000",
            'destino'        => '25175000',
            'valoracion'     => '50000',
            'nivel_servicio' => array(0),
            'detalle'        => array(
                'item' => $cart_prods,
            )
        );

        try {
            $instance->coordinadora->Cotizador_cotizar($params);
        } catch ( \Exception $ex ) {
            $message = $ex->getMessage();
            $message_expected_nit = "Error, El Nit $instance->nit no tiene un acuerdo válido o está vencido.";
            if ($message_expected_nit === $message)
                $message .= " Alterne cambiando opción en Div asociado a un acuerdo Coordinadora Mercantil";
            shipping_coordinadora_wc_cswc_notices( $message );
        }

    }

    public static function test_connection_guides($payment_collection = false)
    {

        $instance = new self();

        $cart_prods = array();

        $cart_prods[] = (object)array(
            'ubl' => '0',
            'alto' => '70',
            'ancho' => '10',
            'largo' => '20',
            'peso' => '1',
            'unidades' => '1',
            'referencia' => 'referencepacket',
            'nombre_empaque' => 'name packet'
        );

        $collection = [];
        $level_service = 1;

        if($payment_collection){
            $level_service = 22;
            $collection[] = (object)[
                'referencia' => '5445',
                'valor' => 50000,
                'valor_base_iva' => '0',
                'valor_iva' => '0',
                'forma_pago' => '1'
            ];
        }

        $params = array(
            'codigo_remision' => "",
            'fecha' => '',
            'id_remitente' => 0,
            'nit_remitente' => '',
            'nombre_remitente' => $instance->sender_name ? $instance->sender_name :  get_bloginfo('name'),
            'direccion_remitente' => 'calle 45 2-23',
            'telefono_remitente' => $instance->phone_sender,
            'ciudad_remitente' => '05001000',
            'nit_destinatario' => '0',
            'div_destinatario' => '0',
            'nombre_destinatario' => 'Prueba no despachar',
            'direccion_destinatario' => 'calle 40 20-40',
            'ciudad_destinatario' => '05001000',
            'telefono_destinatario' => '3189023450',
            'valor_declarado' => '90000',
            'codigo_cuenta' => $instance->code_account, //change manageable
            'codigo_producto' => 0,
            'nivel_servicio' => $level_service,
            'linea' => '',
            'contenido' => 'name packet',
            'referencia' => '5445',
            'observaciones' => '',
            'estado' => 'IMPRESO', //recomendado para la generación del pdf
            'detalle' => $cart_prods,
            'cuenta_contable' => '',
            'centro_costos' => '',
            'recaudos' => $collection,
            'margen_izquierdo' => '',
            'margen_superior' => '',
            'id_rotulo' => 0,
            'usuario_vmi' => '',
            'formato_impresion' => '',
            'atributo1_nombre' => '',
            'atributo1_valor' => '',
            'notificaciones' => (object)array(
                'tipo_medio' => '1',
                'destino_notificacion' => 'example@gmail.com'
            ),
            'atributos_retorno' => (object)array(
                'nit' => '',
                'div' => '',
                'nombre' => '',
                'direccion' => '',
                'codigo_ciudad' => '',
                'telefono' => ''
            ),
            'nro_doc_radicados' => '',
            'nro_sobre' => ''
        );

        try{
            $instance = new self();
            $guide = $instance->coordinadora->Guias_generarGuia($params);
            $instance->coordinadora->Guias_anularGuia([
                'codigo_remision' => $guide->codigo_remision
            ]);

        }
        catch (\Exception $exception){
            $message = $exception->getMessage();
            $message_expected_code_account = "Error, El código cuenta $instance->code_account para la ubl 1 y el id cliente $instance->id_client no existe";
            if ($message_expected_code_account === $message){
                $message .=" <strong>Asegúrese de que el acuerdo de pago que está intentando utilizar esté habilitado.</strong>";
                shipping_coordinadora_wc_cswc_notices( $message );
            }
            if ($instance->debug === 'yes')
                shipping_coordinadora_wc_cswc()->log($message);
        }

    }

    public static function cotizar($params)
    {
        $res = new stdClass();

        try{
            $instance = new self();
            return $instance->coordinadora->Cotizador_cotizar($params);
        }catch (\Exception $exception){
            shipping_coordinadora_wc_cswc()->log($exception->getMessage());
        }

        return $res;
    }

    public static function calculate_cost($package)
    {
        $instance = new self();
        $state_destination = $package['destination']['state'];
        $city_destination  = $package['destination']['city'];
        $items = $package['contents'];
        $count = 0;
        $total_valorization = 0;
        $height = 0;
        $quantityItems = count($items);
        $cart_prods = [];

        foreach ( $items as $item) {
            /**
             * @var  $product WC_Product
             */
            $product = $item['data'];
            $quantity = $item['quantity'];

            if ($item['variation_id'] > 0 && in_array($item['variation_id'], $product->get_children()))
                $product = wc_get_product($item['variation_id']);

            if (!is_numeric($product->get_weight()) || !is_numeric($product->get_length())
                || !is_numeric($product->get_width()) || !is_numeric($product->get_height()))
                break;

            $custom_price_product = get_post_meta($product->get_id(), '_shipping_custom_price_product_smp', true);
            $price = $custom_price_product ?: $product->get_price();
            $total_valorization += $price * $quantity;

            $height += $product->get_height() * $quantity;
            $length = $product->get_length();
            $weight =+ floatval( $product->get_weight() ) * floatval( $quantity );
            $width =  $product->get_width();

            $count++;

            if ($count === $quantityItems || ceil($weight) === $instance->weight_max){

                $cart_prods[] = [
                    'ubl'      => '0',
                    'alto'     => $height,
                    'ancho'    => $width,
                    'largo'    => $length,
                    'peso'     => ceil($weight),
                    'unidades' => 1
                ];

                $height = 0;
            }
        }

        $result_destination = self::code_city($state_destination, $city_destination);
        list($city_sender) = self::get_sender($items);

        if ( empty( $result_destination ) ){
            $city_destination = self::clean_string($city_destination);
            $city_destination = self::clean_city($city_destination);
            $result_destination = self::code_city($state_destination, $city_destination);
        }

        if ( empty( $result_destination ) ) return null;

        $params = array(
            'div'            => $instance->div,
            'cuenta'         => $instance->code_account,
            'producto'       => '0',
            'origen'         => $city_sender,
            'destino'        => $result_destination->codigo,
            'valoracion'     => $total_valorization,
            'nivel_servicio' => array( 0 ),
            'detalle'        => array(
                'item' => $cart_prods
            )
        );

        if ($instance->debug === 'yes')
            shipping_coordinadora_wc_cswc()->log($params);

        $data = self::cotizar($params);

        if ($instance->debug === 'yes')
            shipping_coordinadora_wc_cswc()->log($data);

        if ( !isset($data->flete_total) ) return null;

        $data->flete_total = ceil($data->flete_total);

        return apply_filters( 'coordinadora_shipping_calculate_cost', $data, $package );
    }

    public static function generate_guide_dispath($order_id, $old_status, $new_status, WC_Order $order): void
    {

        $instance = new self();

        $codigo_remision = get_post_meta($order_id, 'codigo_remision_guide_coordinadora', true);

        if (empty($codigo_remision) &&
            !empty($instance->license_key) &&
            $new_status === 'processing' &&
            ($order->has_shipping_method($instance->id) ||
                $order->has_shipping_method('shipping_coordinadora_collection_wc') ||
                $order->has_shipping_method('free_shipping') &&
                $order->get_shipping_total() == 0 &&
                $instance->guide_free_shipping === 'yes')){

            $guide = $instance->generate_guide($order);

            if ($guide == new stdClass())
                return;

            $guide_number = $guide->codigo_remision;

            if ( in_array(
                'woo-advanced-shipment-tracking/woocommerce-advanced-shipment-tracking.php',
                apply_filters( 'active_plugins', get_option( 'active_plugins' ) ),
                true
            ) ) {
                if (class_exists('WC_Advanced_Shipment_Tracking_Actions')){
                    $ast  = new WC_Advanced_Shipment_Tracking_Actions;
                    $args = array(
                        'tracking_provider'        => 'coordinadora',
                        'tracking_number'          => $guide_number,
                        'date_shipped'             => date('Y-m-d')
                    );

                    $ast->add_tracking_item($order_id, $args);
                }
            }

            $note = sprintf( __( 'Número de guía: %d' ), $guide_number );
            update_post_meta($order_id, 'codigo_remision_guide_coordinadora', $guide_number);
            $order->add_order_note($note);
        }
    }

    public function generate_guide(WC_Order $order)
    {
        $instance = new self();

        $direccion_remitente = get_option( 'woocommerce_store_address' ) .
            " " .  get_option( 'woocommerce_store_address_2' ) .
            " " . get_option( 'woocommerce_store_city' );
        $nombre_destinatario = $order->get_shipping_first_name() ? $order->get_shipping_first_name() .
            " " . $order->get_shipping_last_name() : $order->get_billing_first_name() .
            " " . $order->get_billing_last_name();
        $direccion_destinatario = $order->get_shipping_address_1() ? $order->get_shipping_address_1() .
            " " . $order->get_shipping_address_2() : $order->get_billing_address_1() .
            " " . $order->get_billing_address_2();
        $state = $order->get_shipping_state() ? $order->get_shipping_state() : $order->get_billing_state();
        $city = $order->get_shipping_city() ? $order->get_shipping_city() : $order->get_billing_city();
        $city = self::clean_string($city);
        $city = self::clean_city($city);
        $ciudad_destinatario = self::code_city($state, $city);

        $items = $order->get_items();
        list($city_sender, $phone_sender, $sender_name) = self::get_sender($items);

        $namesProducts = [];

        $total_valorization = 0;
        $count = 0;
        $height = 0;
        $length = 0;
        $weight = 0;
        $width = 0;
        $quantityItems = count($items);
        $products = [];
        $skus_quantitys = [];

        foreach ( $items as $item ) {
            $_product = wc_get_product( $item['product_id'] );
            if ( $item['variation_id'] > 0 &&
                in_array( $item['variation_id'], $_product->get_children() ) &&
                wc_get_product( $item['variation_id'] )->get_weight() &&
                wc_get_product( $item['variation_id'] )->get_length() &&
                wc_get_product( $item['variation_id'] )->get_width() &&
                wc_get_product( $item['variation_id'] )->get_height())
                $_product = wc_get_product( $item['variation_id'] );

            $quantity = $item->get_quantity();

            $namesProducts[] = $_product->get_name();
            $skus_quantitys[] = $_product->get_sku() . " " . $quantity;

            $custom_price_product = get_post_meta($_product->get_id(), '_shipping_custom_price_product_smp', true);
            $total_valorization += $custom_price_product > 0 ? wc_format_decimal($custom_price_product, 0) : wc_format_decimal($_product->get_price(), 0);

            $height += $_product->get_height() * $quantity;
            $length = $_product->get_length() > $length ? $_product->get_length() : $length;
            $weight += $_product->get_weight() * $quantity;
            $width =  $_product->get_width() > $width ? $_product->get_width() : $width;

            $count++;

            if ($count === $quantityItems || ceil($weight) === $this->weight_max){

                $products[] = (object)array(
                    'ubl'      => '0',
                    'alto'     => $height,
                    'ancho'    => $width,
                    'largo'    => $length,
                    'peso'     => $order->has_shipping_method('shipping_coordinadora_collection_wc') && ceil($weight) < 3 ? 3 : ceil($weight),
                    'unidades' => 1,
                    'referencia' => !empty($_product->get_sku()) ? $_product->get_sku() : $_product->get_slug(),
                    'nombre_empaque' => $_product->get_name()
                );

                $height = 0;
                $length = 0;
                $weight = 0;
                $width = 0;
            }

        }

        $namesProducts = implode(",",  $namesProducts);
        $skus_quantitys = implode(",",  $skus_quantitys);

        $collection = [];
        $code_account = $instance->code_account;
        $level_service = 1;

        if ($order->has_shipping_method('shipping_coordinadora_collection_wc')){

            $level_service = 22;

            $collection[] = (object)[
                'referencia' => (string)$order->get_id(),
                'valor' => $order->get_total(),
                'valor_base_iva' => '0',
                'valor_iva' => '0',
                'forma_pago' => '1'
            ];
        }

        if ($instance->debug === 'yes')
            shipping_coordinadora_wc_cswc()->log($collection);

        $params = array(
            'codigo_remision' => '',
            'fecha' => '',
            'id_remitente' => 0,
            'nit_remitente' => '',
            'nombre_remitente' => $sender_name,
            'direccion_remitente' => $direccion_remitente,
            'telefono_remitente' => $phone_sender,
            'ciudad_remitente' => $city_sender,
            'nit_destinatario' => '0',
            'div_destinatario' => '0',
            'nombre_destinatario' => $nombre_destinatario,
            'direccion_destinatario' => $direccion_destinatario,
            'ciudad_destinatario' => $ciudad_destinatario->codigo,
            'telefono_destinatario' => $order->get_billing_phone(),
            'valor_declarado' => (string)$total_valorization,
            'codigo_cuenta' => $code_account,
            'codigo_producto' => 0,
            'nivel_servicio' => $level_service,
            'linea' => '',
            'contenido' => $namesProducts,
            'referencia' => (string)$order->get_id(),
            'observaciones' => $skus_quantitys,
            'estado' => 'IMPRESO', //recomendado para la generación del pdf
            'detalle' => $products,
            'cuenta_contable' => '',
            'centro_costos' => '',
            'recaudos' => $collection,
            'margen_izquierdo' => '',
            'margen_superior' => '',
            'id_rotulo' => 0,
            'usuario_vmi' => '',
            'formato_impresion' => '',
            'atributo1_nombre' => '',
            'atributo1_valor' => '',
            'notificaciones' => [(object)array(
                'tipo_medio' => '1',
                'destino_notificacion' => $order->get_billing_email()
            )],
            'atributos_retorno' => [(object)array(
                'nit' => '',
                'div' => '',
                'nombre' => '',
                'direccion' => '',
                'codigo_ciudad' => '',
                'telefono' => ''
            )],
            'nro_doc_radicados' => '',
            'nro_sobre' => ''
        );

        $data = new stdClass;

        try{
            $data = $this->coordinadora->Guias_generarGuia($params);
        }
        catch (\Exception $exception){
            shipping_coordinadora_wc_cswc()->log($params);
            shipping_coordinadora_wc_cswc()->log($exception->getMessage());

        }

        return $data;
    }

    public static function print_rotulos(array $params)
    {
        $instance = new self();
        $data = new stdClass;

        try{
            $data = $instance->coordinadora->Guias_imprimirRotulos($params);
        }catch (\Exception $exception){
            shipping_coordinadora_wc_cswc()->log($params);
            shipping_coordinadora_wc_cswc()->log($exception->getMessage());
        }

        return $data;
    }

    public static function tracking_guide(array $params)
    {
        $instance = new self();
        $data = new stdClass;

        try {
            $data = $instance->coordinadora->Guias_rastreoExtendido($params);
        }catch (\Exception $exception){

        }

        return $data;
    }


    public static function code_city($state, $city)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'shipping_coordinadora_cities';

        $countries_obj = new WC_Countries();
        $country_states_array = $countries_obj->get_states();
        $state_name = $country_states_array['CO'][ $state ];
        $state_name = self::short_name_location($state_name);
        $state_name = $city === 'Bogota' ? 'Cundinamarca' : $state_name;

        $query = "SELECT codigo FROM $table_name WHERE nombre_departamento='$state_name' AND nombre='$city'";

        return $wpdb->get_row( $query );

    }

    public static function short_name_location($name_location)
    {
        if ( 'Valle del Cauca' === $name_location )
            $name_location =  'Valle';
        return $name_location;
    }

    public static function clean_city($city)
    {
        Return $city == 'Bogota D.C' ? 'Bogota' : $city;
    }

    public static function clean_string($string)
    {
        $not_permitted = array ("á","é","í","ó","ú","Á","É","Í",
            "Ó","Ú","ñ");
        $permitted = array ("a","e","i","o","u","A","E","I","O",
            "U","n");
        $text = str_replace($not_permitted, $permitted, $string);
        return $text;
    }

    public function dateCurrent()
    {
        $dateCurrent = date('Y-m-d', current_time( 'timestamp' ));

        return $dateCurrent;
    }

    public static function get_shop($product_id)
    {
        $id = get_post_field( 'post_author', $product_id );
        $store = function_exists('dokan_get_store_info') && dokan_get_store_info($id) ? dokan_get_store_info($id) : null;

        return apply_filters('shipping_coordinadora_get_shop', $store, $product_id);
    }

    public static function upgrade_working_plugin($license = null)
    {
        $instance = new self();

        $secret_key = '5c88321cdb0dc9.43606608';

        $settings = get_option('woocommerce_shipping_coordinadora_wc_settings');

        if (!$license){
            $license = empty($settings['license_key']) ? '' : $settings['license_key'];
        }

        if (empty($license)) return $license;

        $api_params = array(
            'slm_action' => 'slm_check',
            'secret_key' => $secret_key,
            'license_key' => $license,
        );

        $siteGet = 'https://shop.saulmoralespa.com';

        $response = wp_remote_get(
            add_query_arg($api_params, $siteGet),
            array('timeout' => 60,
                'sslverify' => true
            )
        );

        if (is_wp_error($response)){
            shipping_coordinadora_wc_cswc_notices( $response->get_error_message() );
            exit();
        }

        $data = json_decode(wp_remote_retrieve_body($response));


        //max_allowed_domains

        //registered_domains  array() registered_domain

        if (isset($data->result) && $data->result === 'error') return '';

        if ($data->status === 'expired'){
            $instance->update_option('license_key', '');
            $license = '';
        }elseif ($data->result === 'success' && $data->status === 'pending'){

            $api_params = array(
                'slm_action' => 'slm_activate',
                'secret_key' => $secret_key,
                'license_key' => $license,
                'registered_domain' => get_bloginfo( 'url' ),
                'item_reference' => urlencode($instance->id),
            );

            $query = esc_url_raw(add_query_arg($api_params, $siteGet));
            $response = wp_remote_get($query,
                array('timeout' => 60,
                    'sslverify' => true
                )
            );

            if (is_wp_error($response)){
                shipping_coordinadora_wc_cswc_notices( $response->get_error_message() );
                return '';
            }

            $data = json_decode(wp_remote_retrieve_body($response));

            if($data->result === 'error'){
                $instance->update_option('license_key', '');
                $license = '';
            }

        }

        return  $license;

    }

    public static function get_sender(array $items) : array
    {
        $instance = new self();
        $item = end($items);
        $product_id = $item['product_id'];
        $seller = self::get_shop($product_id);

        if (isset($seller['address']) &&
            !empty($seller['address']['city']) &&
            !empty($seller['address']['state']) &&
            !empty($seller['store_name']) &&
            !empty($seller['phone'])) {
            $city_sender = $seller['address']['city'];
            $city_sender = self::clean_string($city_sender);
            $city_sender = self::clean_city($city_sender);
            $state_sender = $seller['address']['state'];

            $city_sender = self::code_city($state_sender, $city_sender);
            $city_sender = $city_sender->codigo ?? '';
            $phone_sender = $seller['phone'];
            $sender_name = $seller['store_name'];

        } else {
            $city_sender = $instance->city_sender;
            $phone_sender = $instance->phone_sender;
            $sender_name = $instance->sender_name ?: get_bloginfo('name');
        }
        return array($city_sender, $phone_sender, $sender_name);
    }
}