<?php


class WC_Shipping_Method_Shipping_Coordinadora_Collection_WC extends WC_Shipping_Method
{
    public function __construct($instance_id = 0)
    {
        parent::__construct($instance_id);

        $this->id                 = 'shipping_coordinadora_collection_wc';
        $this->instance_id        = absint( $instance_id );
        $this->method_title       = __( 'Coordinadora pago con recaudo' );
        $this->method_description = __( 'Pago con recaudo Coordinadora' );
        $this->title = $this->get_option('title');

        $this->supports = array(
            'settings',
            'shipping-zones'
        );

        $this->init();
    }

    public function is_available($package)
    {
        $wc_main_settings = get_option('woocommerce_shipping_coordinadora_wc_settings');
        return $this->get_option('enabled') === 'yes' &&
            $wc_main_settings['enabled'] === 'yes';
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
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Activar/Desactivar'),
                'type' => 'checkbox',
                'label' => __('Activar Coordinadora pago con recaudo'),
                'default' => 'no'
            ),
            'title'        => array(
                'title'       => __( 'Título método de envío' ),
                'type'        => 'text',
                'description' => __( 'Esto controla el título que el usuario ve durante el pago' ),
                'default'     => __( 'Coordinadora pago con recaudo' ),
                'desc_tip'    => true
            )
        );
    }

    public function admin_options()
    {
        ?>
        <h3><?php echo $this->title; ?></h3>
        <p><?php echo $this->method_description; ?></p>
        <table class="form-table">
            <?php
            $this->generate_settings_html();
            Shipping_Coordinadora_WC::test_connection_guides(true);
            ?>
        </table>
        <?php
    }

    public function calculate_shipping($package = array())
    {
        $country = $package['destination']['country'];

        if($country !== 'CO')
            return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', false, $package, $this );

        $data = Shipping_Coordinadora_WC::calculate_cost($package);

        if (empty($data))
            return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', false, $package, $this );

        $rate = array(
            'id'      => $this->id,
            'label'   => $this->title,
            'cost'    => $data->flete_total,
            'package' => $package
        );

        return $this->add_rate( $rate );
    }
}