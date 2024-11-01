<?php
/**
 * @package ShippingCoordinadora
 *
 * Shipping Method for Coordinadora
 */
class WC_Shipping_Method_Shipping_Coordinadora_WC extends WC_Shipping_Method
{
    /**
     * Initializes the class variables
     *
     * @param integer $instance_id Instance ID of the class
     */
    public function __construct( $instance_id = 0 )
    {

        parent::__construct( $instance_id );

        $this->id                 = 'shipping_coordinadora_wc';
        $this->instance_id        = absint( $instance_id );
        $this->method_title       = __( 'Coordinadora' );
        $this->method_description = __( 'Coordinadora empresa transportadora de Colombia' );
        $this->title = $this->get_option('title');
        $this->enabled = $this->get_option('enabled');

        $this->supports = array(
            'settings',
            'shipping-zones'
        );

        $this->init();

        $this->debug = $this->get_option( 'debug' );
        $this->isTest = (bool)$this->get_option( 'environment' );
        $this->guide_free_shipping =  $this->get_option( 'guide_free_shipping' );
        $this->collection = $this->get_option( 'collection' );

        if ($this->isTest){
            $this->apikey = $this->get_option( 'sandbox_api_key' );
            $this->password_tracings = $this->get_option( 'sandbox_password_tracings' );
            $this->nit = $this->get_option( 'sandbox_nit' );

            $this->id_client = $this->get_option( 'sandbox_id_client' );
            $this->user = $this->get_option( 'sandbox_user' );
            $this->password_guides = $this->get_option('sandbox_password_guides');
            $this->code_account = $this->get_option('sandbox_code_account');

        }else{
            $this->apikey = $this->get_option( 'api_key' );
            $this->password_tracings = $this->get_option( 'password_tracing' );
            $this->nit = $this->get_option( 'nit' );

            $this->id_client = $this->get_option( 'id_client' );
            $this->user = $this->get_option( 'user' );
            $this->password_guides = $this->get_option('password_guides');
            $this->code_account = $this->get_option('code_account');
        }

        $this->div = $this->get_option('div');
        $this->sender_name = $this->get_option('sender_name');
        $this->city_sender = $this->get_option('city_sender');
        $this->phone_sender = $this->get_option('phone_sender');
        $this->weight_max = $this->get_option('weight_max');

        $this->license_key = $this->get_option('license_key');
    }

    public function is_available($package)
    {
        return $this->enabled === 'yes' &&
            !empty($this->apikey) &&
            !empty($this->nit) &&
            !empty($this->password_tracings);
    }

    /**
     * Init the class settings
     */
    public function init()
    {
        // Load the settings API.
        $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings.
        $this->init_settings(); // This is part of the settings API. Loads settings you previously init.
        // Save settings in admin if you have any defined.
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    /**
     * Init the form fields for this shipping method
     */
    public function init_form_fields()
    {
        $this->form_fields = include(dirname(__FILE__) . '/admin/settings.php');
    }

    public function admin_options()
    {
        ?>
        <h3><?php echo $this->title; ?></h3>
        <p><?php echo $this->method_description; ?></p>
        <table class="form-table">
            <?php
            if (!empty($this->apikey) && !empty($this->nit) && !empty($this->password_tracings))
                Shipping_Coordinadora_WC::test_connection_tracing();
            if (!empty($this->id_client) && !empty($this->user) && !empty($this->password_guides) && !empty($this->phone_sender))
                Shipping_Coordinadora_WC::test_connection_guides();
            $this->generate_settings_html();
            ?>
        </table>
        <?php
    }


    public function validate_text_field($key, $value)
    {
        $value = trim($value);

        if ($this->get_option( 'environment' ) == 0){
            if ($key === 'api_key' && strpos($value, '-') === false){
                WC_Admin_Settings::add_error("API Key  es requerida");
            }
            if ($key === 'nit' && $value == ''){
                WC_Admin_Settings::add_error("NIT es requerido");
            }
            if ($key === 'id_client' && $value == ''){
                WC_Admin_Settings::add_error("id_cliente es requerido");
            }
            if ($key === 'user' && $value == ''){
                WC_Admin_Settings::add_error("Usuario es requerido");
            }
        }elseif ($this->get_option( 'environment' ) == 1){
            if ($key === 'sandbox_api_key' && strpos($value, '-') === false){
                WC_Admin_Settings::add_error("API Key  es requerida");
            }
            if ($key === 'sandbox_nit' && $value == ''){
                WC_Admin_Settings::add_error("NIT es requerido");
            }
            if ($key === 'sandbox_id_client' && $value == ''){
                WC_Admin_Settings::add_error("id_cliente es requerido");
            }
            if ($key === 'sandbox_user' && $value == ''){
                WC_Admin_Settings::add_error("Usuario es requerido");
            }
        }

        return $value;
    }

    public function validate_password_field($key, $value)
    {

        $value = trim($value);

        if ($this->get_option( 'environment' ) == 0){
            if ($key === 'password_tracing' && $value == ''){
                WC_Admin_Settings::add_error("La contraseña de seguimiento de guías es requerido");
            }
            if ($key === 'password_guides' && $value == ''){
                WC_Admin_Settings::add_error("La contraseña es requerida");
            }
        }elseif ($this->get_option( 'environment' ) == 1){
            if ($key === 'sandbox_password_tracings' && $value == ''){
                WC_Admin_Settings::add_error("Las contraseña de seguimiento de guías es requerido");
            }
            if ($key === 'sandbox_password_guides' && $value == ''){
                WC_Admin_Settings::add_error("La contraseña es requerida");
            }
        }

        if ($key === 'license_key' && $value){
            $value = Shipping_Coordinadora_WC::upgrade_working_plugin($value);
        }

        return $value;
    }

    /**
     * Calculate the rates for this shipping method.
     *
     * @access public
     * @param mixed $package Array containing the cart packages. To see more about these packages see the 'calculate_shipping' method in this file: woocommerce/includes/class-wc-cart.php.
     */
    public function calculate_shipping( $package = array() ): void
    {
        $country = $package['destination']['country'];

        if($country !== 'CO') return;

        $data = Shipping_Coordinadora_WC::calculate_cost($package);

        if(!isset($data)) return;

        $rate = array(
            'id'      => $this->id,
            'label'   => $this->title,
            'cost'    => $data->flete_total,
            'package' => $package
        );

        $this->add_rate( $rate );

    }
}