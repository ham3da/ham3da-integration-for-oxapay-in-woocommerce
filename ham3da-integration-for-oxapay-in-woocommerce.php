<?php

/**
 * Plugin Name: ham3da integration for OxaPay
 * Plugin URI: https://wordpress.org/plugins/ham3da-integration-for-oxapay-in-woocommerce
 * Description: Accept cryptocurrency payments on your WooCommerce store.
 * Author: ham3da
 * Author URI: https://ham3da.ir
 * Version: 1.1.3
 * Text Domain: ham3da-integration-for-oxapay-in-woocommerce
 * Domain Path: /lang
 * Requires Plugins: woocommerce
 * WC requires at least: 6.9
 * WC tested up to: 9.8
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('HAM3INFO_OxaPay_PLUGIN_VER', '1.1.3');

define('HAM3INFO_OxaPay_PLUGIN_FILE', __FILE__);
define('HAM3INFO_OxaPay_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HAM3INFO_OxaPay_PLUGIN_URL', plugin_dir_url(__FILE__));

define('HAM3INFO_OxaPay_GID', 'HAM3INFO_OxaPay_Gateway');



add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

function ham3info_oxapay_ad_gateway($methods)
{
    $methods[] = 'HAM3INFO_OxaPay_Gateway';
    return $methods;
}

add_action('plugins_loaded', function () {
    if (class_exists('WC_Payment_Gateway'))
    {
        require_once HAM3INFO_OxaPay_PLUGIN_DIR . 'inc/gateway-class.php';
        add_filter('woocommerce_payment_gateways', 'ham3info_oxapay_ad_gateway');
    }
    
    add_action('woocommerce_order_details_after_order_table',  'HAM3INFO_OxaPay_Gateway::details_after_order_table' , 1, 1);
}, 0);

function ham3info_oxapay_woo_blocks_support() {
    if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        add_action('woocommerce_blocks_payment_method_type_registration',
                function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                    require_once HAM3INFO_OxaPay_PLUGIN_DIR . 'inc/woo-block.php';
                    $payment_method_registry->register(new HAM3INFO_OxaPay_Payments_Block());
                }
        );
    }
}
add_action('woocommerce_blocks_loaded', 'ham3info_oxapay_woo_blocks_support');

require_once HAM3INFO_OxaPay_PLUGIN_DIR . 'inc/functions.php';

add_action('woocommerce_review_order_after_order_total', function(){
    HAM3INFO_OxaPay_Gateway::wc_checkout_fields_def();
});