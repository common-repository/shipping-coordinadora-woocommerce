<?php

class Shipping_Coordinadora_WC_Admin
{
    public function __construct()
    {
        add_action( 'admin_menu', array($this, 'shipping_coordinadora_wc_cswc_menu'));
        add_action( 'wp_ajax_shipping_coordinadora_wc_cswc',array($this,'shipping_coordinadora_wc_cswc_ajax'));
    }

    public function shipping_coordinadora_wc_cswc_menu()
    {
        add_submenu_page(
            null,
            '',
            '',
            'manage_options',
            'coordinadora-install-setp',
            array($this, 'coordinadora_install_step')
        );
    }

    public function coordinadora_install_step()
    {
        ?>
        <div class="wrap about-wrap">
            <h3><?php _e( 'Actualicemos y estaremos listos para iniciar :)' ); ?></h3>
            <button class="button-primary shipping_coordinadora_update_cities" type="button">Actualizar</button>
        </div>
        <?php
    }

    public function shipping_coordinadora_wc_cswc_ajax()
    {
        do_action('shipping_coordinadora_wc_cswc_update_cities');
        die();
    }
}