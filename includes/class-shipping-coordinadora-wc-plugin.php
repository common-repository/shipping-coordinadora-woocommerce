<?php

class Shipping_Coordinadora_WC_Plugin
{
    /**
     * Filepath of main plugin file.
     *
     * @var string
     */
    public $file;
    /**
     * Plugin version.
     *
     * @var string
     */
    public $version;
    /**
     * Absolute plugin path.
     *
     * @var string
     */
    public $plugin_path;
    /**
     * Absolute plugin URL.
     *
     * @var string
     */
    public $plugin_url;
    /**
     * Absolute path to plugin includes dir.
     *
     * @var string
     */
    public $includes_path;
    /**
     * Absolute path to plugin lib dir
     *
     * @var string
     */
    public $lib_path;
    /**
     * @var bool
     */
    private $_bootstrapped = false;

    public function __construct($file, $version)
    {
        $this->file = $file;
        $this->version = $version;

        $this->plugin_path   = trailingslashit( plugin_dir_path( $this->file ) );
        $this->plugin_url    = trailingslashit( plugin_dir_url( $this->file ) );
        $this->includes_path = $this->plugin_path . trailingslashit( 'includes' );
        $this->lib_path = $this->plugin_path . trailingslashit( 'lib' );
    }

    public function run_coordinadora_wc()
    {
        try{
            if ($this->_bootstrapped){
                throw new Exception( 'Coordinadora shipping can only be called once');
            }
            $this->_run();
            $this->_bootstrapped = true;
        }catch (Exception $e){
            if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
                add_action('admin_notices', function() use($e) {
                    shipping_coordinadora_wc_cswc_notices($e->getMessage());
                });
            }
        }
    }

    protected function _run()
    {

        if (!class_exists('\WebService\Coordinadora'))
            require_once ($this->lib_path . 'coordinadora-webservice-php/src/WebService.php');
        require_once ($this->includes_path . 'class-shipping-coordinadora-wc-admin.php');
        require_once ($this->includes_path . 'class-method-shipping-coordinadora-wc.php');
        require_once ($this->includes_path . 'class-method-shipping-coordinadora-collection-wc.php');
        require_once ($this->includes_path . 'class-shipping-coordinadora-wc.php');
        $this->admin = new Shipping_Coordinadora_WC_Admin();

        add_filter( 'plugin_action_links_' . plugin_basename( $this->file), array( $this, 'plugin_action_links' ) );
        add_action( 'shipping_coordinadora_wc_cswc_schedule', array('Shipping_Coordinadora_WC', 'upgrade_working_plugin'));
        add_action( 'shipping_coordinadora_wc_cswc_update_cities', array('Shipping_Coordinadora_WC', 'update_cities'));
        add_filter( 'woocommerce_shipping_methods', array( $this, 'shipping_coordinadora_wc_add_method') );
        add_filter( 'manage_edit-shop_order_columns', array($this, 'print_label'), 20 );
        add_action( 'woocommerce_order_status_changed', array('Shipping_Coordinadora_WC', 'generate_guide_dispath'), 100, 4 );
        add_action( 'woocommerce_process_product_meta', array($this, 'save_custom_shipping_option_to_products'), 10 );
        add_action( 'woocommerce_save_product_variation', array($this, 'save_variation_settings_fields'), 10, 2 );
        add_action( 'manage_shop_order_posts_custom_column', array($this, 'content_column_print_label'), 2 );
        add_action( 'wp_ajax_coordinadora_generate_label', array($this, 'coordinadora_generate_label'));
        add_action( 'wp_ajax_coordinadora_tracking', array($this, 'coordinadora_tracking'));
        add_action( 'wp_ajax_nopriv_coordinadora_tracking', array($this, 'coordinadora_tracking'));
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts_admin') );
        add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts') );
        add_action( 'woocommerce_order_details_after_order_table', array($this, 'button_get_status_shipping'), 10, 1 );

        $settings = get_option('woocommerce_shipping_coordinadora_wc_settings' );

        if (empty($settings['license_key'])){
            $country = 'Coordinadora shipping Woocommerce: Requiere una licencia para su completo funcionamiento '  .
                sprintf(
                    '%s',
                    '<a target="_blank" class="button button-primary"  href="https://shop.saulmoralespa.com/producto/plugin-shipping-coordinadora-woocommerce/">' .
                    'Obtener Licencia</a>' );
            add_action(
                'admin_notices',
                function() use($country) {
                    shipping_coordinadora_wc_cswc_notices( $country );
                }
            );
        }

        if (!wp_next_scheduled('shipping_coordinadora_wc_cswc_schedule')) {
            wp_schedule_event( time(), 'twicedaily', 'shipping_coordinadora_wc_cswc_schedule' );
        }
    }

    public function plugin_action_links($links)
    {
        $plugin_links = array();
        $plugin_links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=shipping_coordinadora_wc') . '">' . 'Configuraciones' . '</a>';
        $plugin_links[] = '<a target="_blank" href="https://shop.saulmoralespa.com/shipping-coordinadora-woocommerce/">' . 'Documentación' . '</a>';
        return array_merge( $plugin_links, $links );
    }

    public function shipping_coordinadora_wc_add_method( $methods ) {
        $methods['shipping_coordinadora_wc'] = 'WC_Shipping_Method_Shipping_Coordinadora_WC';
        $methods['shipping_coordinadora_collection_wc'] = 'WC_Shipping_Method_Shipping_Coordinadora_Collection_WC';
        return $methods;
    }

    public function print_label($columns)
    {
        $wc_main_settings = get_option('woocommerce_shipping_coordinadora_wc_settings');

        if(isset($wc_main_settings['license_key']) && !empty($wc_main_settings['license_key']))
            $columns['generate_label'] = 'Generar rótulo Coordinadora';
        return $columns;
    }

    public function content_column_print_label($column)
    {
        global $post;

        $order = new WC_Order($post->ID);

        $guide_coordinadora = get_post_meta($order->get_id(), 'codigo_remision_guide_coordinadora', true);
        $upload_dir = wp_upload_dir();
        $label_file = $upload_dir['basedir'] . '/coordinadora-labels/' . "$guide_coordinadora.pdf";
        $label_url = $upload_dir['baseurl'] . '/coordinadora-labels/' . "$guide_coordinadora.pdf";

        if(!file_exists($label_file) && !empty($guide_coordinadora) && $column == 'generate_label' ){
            echo "<button class='button-secondary generate_label' data-guide='".$guide_coordinadora."' data-nonce='".wp_create_nonce( "shipping_coordinadora_generate_label") ."'>Generar rótulo</button>";
        }elseif (file_exists($label_file) && !empty($guide_coordinadora) && $column == 'generate_label'){
            echo "<a target='_blank' class='button-primary' href='$label_url'>Ver rótulo</a>";
        }
    }

    public function log($message)
    {
        if (is_array($message) || is_object($message))
            $message = print_r($message, true);
        $logger = new WC_Logger();
        $logger->add('shipping-coordinadora', $message);
    }

    public function createTable()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'shipping_coordinadora_cities';

        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name )
            return;

        $sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		nombre VARCHAR(60) NOT NULL,
		codigo VARCHAR(8) NOT NULL,
		nombre_departamento VARCHAR(60) NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function add_custom_shipping_option_to_products(): void
    {
        global $post;
        global $shipping_custom_price_product_smp_loaded;

        if (!isset($shipping_custom_price_product_smp_loaded)) {
            $shipping_custom_price_product_smp_loaded = false;
        }

        if($shipping_custom_price_product_smp_loaded) return;

        woocommerce_wp_text_input( [
            'id'          => '_shipping_custom_price_product_smp[' . $post->ID . ']',
            'label'       => __( 'Valor declarado del producto'),
            'placeholder' => 'Valor declarado del envío',
            'desc_tip'    => true,
            'description' => __( 'El valor que desea declarar para el envío'),
            'value'       => get_post_meta( $post->ID, '_shipping_custom_price_product_smp', true ),
        ] );

        $shipping_custom_price_product_smp_loaded = true;
    }

    public static function variation_settings_fields($loop, $variation_data, $variation): void
    {
        woocommerce_wp_text_input(
            array(
                'id'          => '_shipping_custom_price_product_smp[' . $variation->ID . ']',
                'label'       => __( 'Valor declarado del producto'),
                'placeholder' => 'Valor declarado del envío',
                'desc_tip'    => true,
                'description' => __( 'El valor que desea declarar para el envío'),
                'value'       => get_post_meta( $variation->ID, '_shipping_custom_price_product_smp', true )
            )
        );
    }

    public function save_custom_shipping_option_to_products($post_id)
    {
        $custom_price_product = esc_attr($_POST['_shipping_custom_price_product_smp'][ $post_id ]);

        if( isset( $custom_price_product ) )
            update_post_meta( $post_id, '_shipping_custom_price_product_smp', $custom_price_product );
    }

    public function save_variation_settings_fields($post_id)
    {
        $custom_variation_price_product = esc_attr($_POST['_shipping_custom_price_product_smp'][ $post_id ]);
        if( ! empty( $custom_variation_price_product ) ) {
            update_post_meta( $post_id, '_shipping_custom_price_product_smp', $custom_variation_price_product );
        }
    }

    public function coordinadora_generate_label()
    {
        if ( ! wp_verify_nonce(  $_REQUEST['nonce'], 'shipping_coordinadora_generate_label' ) )
            return;

        $guide_number = $_REQUEST['guide_number'];

        $params = [
            'id_rotulo' => '55',
            'codigos_remisiones' => array(
                $_REQUEST['guide_number']
            )
        ];

        $label_url = '';

        try{
            $data = Shipping_Coordinadora_WC::print_rotulos($params);
            if($data->error)
                throw new \Exception($data->errorMessage);

            $bin = base64_decode($data->rotulos, true);
            if (strpos($bin, '%PDF') !== 0)
                throw new \Exception('Missing the PDF file signature');

            $upload_dir = wp_upload_dir();
            $dir = $upload_dir['basedir'] . '/coordinadora-labels/';

            if (!is_dir($dir))
                mkdir($dir,0755);
            $label_file = file_put_contents("{$dir}$guide_number.pdf", $bin);
            if ($label_file)
                $label_url = $upload_dir['baseurl'] . '/coordinadora-labels/' . "$guide_number.pdf";

        }catch (\Exception $exception){
            $this->log($exception->getMessage());
        }

        wp_send_json(['url' => $label_url]);

    }

    public function coordinadora_tracking()
    {
        if ( ! wp_verify_nonce(  $_REQUEST['nonce'], 'shipping_coordinadora_tracking' ) )
            return;

        $guide_number = $_REQUEST['guide_number'];

        $params = [
            'codigos_remision' => [
                $guide_number
            ]
        ];

        $data = new stdClass;

        try {
            $data = Shipping_Coordinadora_WC::tracking_guide($params);
        }catch (\Exception $exception){
            $this->log($exception->getMessage());
        }


        wp_send_json($data);
    }

    public function enqueue_scripts_admin($hook)
    {
        if ($hook === 'woocommerce_page_wc-settings' || $hook === 'edit.php' || $hook === 'admin_page_coordinadora-install-setp'){
            wp_enqueue_script('sweetalert_shipping_coordinadora_wc_cswc', $this->plugin_url . 'assets/js/sweetalert2-min.js', array( 'jquery' ), $this->version, true );
            wp_enqueue_script( 'shipping_coordinadora_wc_cswc', $this->plugin_url . 'assets/js/shipping-coordinadora-wc.js', array( 'jquery' ), $this->version, true );
            wp_localize_script( 'shipping_coordinadora_wc_cswc', 'shippingCoordinadora', array(
                'urlConfig' => admin_url( 'admin.php?page=wc-settings&tab=shipping&section=shipping_coordinadora_wc')
            ) );
        }
    }

    public function enqueue_scripts(): void
    {
        if(is_view_order_page()){
            wp_enqueue_script('sweetalert_shipping_coordinadora_wc_cswc', $this->plugin_url . 'assets/js/sweetalert2-min.js', array( 'jquery' ), $this->version, true );
            wp_enqueue_script( 'view_order_shipping_coordinadora_wc_cswc', $this->plugin_url . 'assets/js/view-order.js', array( 'jquery' ), $this->version, true );
        }
    }

    public function button_get_status_shipping($order)
    {
        $order_id_origin = $order->get_parent_id() > 0 ? $order->get_parent_id() : $order->get_id();
        $number_guide = get_post_meta($order_id_origin, 'codigo_remision_guide_coordinadora', true);

        if ($number_guide){
            echo "<p>Envío delegado a <a href='https://www.coordinadora.com/portafolio-de-servicios/servicios-en-linea/rastrear-guias/' target='_blank'>Coordinadora</a> con código de seguimiento: $number_guide</p>  <button class='button-secondary wp-caption tracking-coordinadora' data-guide='".$number_guide."' data-nonce='".wp_create_nonce( "shipping_coordinadora_tracking") ."'>Seguimiento en línea</button>";
        }
    }
}