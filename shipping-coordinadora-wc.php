<?php
/**
 * Plugin Name: Shipping Coordinadora Woocommerce
 * Description: Shipping Coordinadora Woocommerce is available for Colombia
 * Version: 3.0.30
 * Author: Saul Morales Pacheco
 * Author URI: https://saulmoralespa.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * WC tested up to: 4.8
 * WC requires at least: 4.0
 *
 * @package ShippingCoordinadora
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(!defined('SHIPPING_COORDINADORA_WC_CSWC_VERSION')){
    define('SHIPPING_COORDINADORA_WC_CSWC_VERSION', '3.0.30');
}

add_action( 'plugins_loaded', 'shipping_coordinadora_wc_cswc_init', 1 );

/**
 * Check for the conditions to initialize the plugin if the requirements are met, the plugin starts.
 */
function shipping_coordinadora_wc_cswc_init() {
    if ( !shipping_coordinadora_wc_cswc_requirements() )
        return;

    shipping_coordinadora_wc_cswc()->run_coordinadora_wc();

    if ( get_option( 'shipping_coordinadora_wc_cswc_redirect', false ) ) {
        delete_option( 'shipping_coordinadora_wc_cswc_redirect' );
        wp_redirect( admin_url( 'admin.php?page=coordinadora-install-setp' ) );
    }

}

/**
 * This function is used for showing notice messages when one
 * of the requirements for the plugin to initialize is not met.
 *
 * @param  string $notice The message to show.
 */
function shipping_coordinadora_wc_cswc_notices( $notice ) {
    ?>
    <div class="error notice is-dismissible">
        <p><?php echo $notice; ?></p>
    </div>
    <?php
}

/**
 * Check for the requirements for the plugin to initialize.
 *
 * @return boolean false if at least on of the conditions are not met, otherwise this value is true.
 */
function shipping_coordinadora_wc_cswc_requirements() {

    if ( ! function_exists( 'is_plugin_active' ) ) require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

    if ( ! extension_loaded( 'openssl' ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    shipping_coordinadora_wc_cswc_notices( 'Shipping Coordinadora Woocommerce requiere la extensión OpenSSL 1.0.1 o superior se encuentre instalada' );
                }
            );
        }
        return false;
    }

    if ( ! extension_loaded( 'soap' ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    shipping_coordinadora_wc_cswc_notices( 'Shipping Coordinadora Woocommerce requiere la extensión soap se encuentre instalada' );
                }
            );
        }
        return false;
    }

    if ( ! is_plugin_active(
        'woocommerce/woocommerce.php'
    ) )  {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    shipping_coordinadora_wc_cswc_notices( 'Shipping Coordinadora Woocommerce requiere que se encuentre instalado y activo el plugin: Woocommerce' );
                }
            );
        }
        return false;
    }

    if ( ! is_plugin_active(
        'departamentos-y-ciudades-de-colombia-para-woocommerce/departamentos-y-ciudades-de-colombia-para-woocommerce.php'
    ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    $action = 'install-plugin';
                    $slug = 'departamentos-y-ciudades-de-colombia-para-woocommerce';
                    $plugin_install_url = wp_nonce_url(
                        add_query_arg(
                            array(
                                'action' => $action,
                                'plugin' => $slug
                            ),
                            admin_url( 'update.php' )
                        ),
                        $action.'_'.$slug
                    );
                    $plugin = 'Shipping Coordinadora Woocommerce requiere que se encuentre instalado y activo el plugin: '  .
                        sprintf(
                            '%s',
                            "<a class='button button-primary' href='$plugin_install_url'>Departamentos y ciudades de Colombia para Woocommerce</a>" );
                    shipping_coordinadora_wc_cswc_notices( $plugin );
                }
            );
        }
        return false;
    }

    $woo_countries   = new WC_Countries();
    $default_country = $woo_countries->get_base_country();

    if ( ! in_array( $default_country, array( 'CO' ), true ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    $country = 'Shipping Coordinadora Woocommerce requiere que el país donde se encuentra ubicada la tienda sea Colombia '  .
                        sprintf(
                            '%s',
                            '<a href="' . admin_url() .
                        'admin.php?page=wc-settings&tab=general#s2id_woocommerce_currency">' .
                        'Click para establecer</a>' );
                    shipping_coordinadora_wc_cswc_notices( $country );
                }
            );
        }
        return false;
    }

    return true;
}

function shipping_coordinadora_wc_cswc() {
    static $plugin;
    if ( ! isset( $plugin ) ) {
        require_once 'includes/class-shipping-coordinadora-wc-plugin.php';
        $plugin = new Shipping_Coordinadora_WC_Plugin( __FILE__, SHIPPING_COORDINADORA_WC_CSWC_VERSION );
    }
    return $plugin;
}

/**
 * Activation hook function for the plugin.
 */
function activate_shipping_coordinadora_wc_cswc() {
    //update_option( 'shipping_coordinadora_wc_cswc_version', SHIPPING_COORDINADORA_WC_CSWC_VERSION );
    //add_option( 'shipping_coordinadora_wc_cswc_redirect', true );
    wp_schedule_event( time(), 'twicedaily', 'shipping_coordinadora_wc_cswc_schedule' );
}

/**
 * Deactivaction hook function for the plugin.
 */
function deactivation_shipping_coordinadora_wc_cswc() {
    wp_clear_scheduled_hook( 'shipping_coordinadora_wc_cswc_schedule' );
}

register_activation_hook( __FILE__, 'activate_shipping_coordinadora_wc_cswc' );
register_deactivation_hook( __FILE__, 'deactivation_shipping_coordinadora_wc_cswc' );
add_action( 'woocommerce_product_after_variable_attributes', array('Shipping_Coordinadora_WC_Plugin', 'variation_settings_fields'), 10, 3 );
add_action( 'woocommerce_product_options_shipping', array('Shipping_Coordinadora_WC_Plugin', 'add_custom_shipping_option_to_products'), 10);