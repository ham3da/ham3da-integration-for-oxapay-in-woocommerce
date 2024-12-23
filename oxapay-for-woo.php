<?php

/**
 * Plugin Name: OxaPay for Woo
 * Plugin URI: https://wordpress.org/plugins/oxapay-for-woo
 * Description: With OxaPay for Woo, your WooCommerce store can process cryptocurrency payments.
 * Author: ham3da
 * Author URI: https://ham3da.ir
 * Version: 1.0
 * Text Domain: oxapay-for-woo
 * Domain Path: /lang
 * WC requires at least: 6.9
 * WC tested up to: 9.4.2
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */
define('OxaPay_PLUGIN_VER', '1.0');

define('OxaPay_PLUGIN_FILE', __FILE__);
define('OxaPay_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OxaPay_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OxaPay_HAM3DA', 0);

define('OxaPay_GID', 'WC_OxaPay_Gateway');

add_action('plugins_loaded', function () {
    load_plugin_textdomain('oxapay-for-woo', false, basename(dirname(__FILE__)) . '/lang');
});


add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

function oxapay_ad_gateway($methods)
{
    $methods[] = 'WC_OxaPay_Gateway';
    return $methods;
}

add_action('plugins_loaded', function () {
    if (class_exists('WC_Payment_Gateway'))
    {
        require_once OxaPay_PLUGIN_DIR . 'inc/gateway-class.php';
        add_filter('woocommerce_payment_gateways', 'oxapay_ad_gateway');
    }
    
    add_action('woocommerce_order_details_after_order_table',  'WC_OxaPay_Gateway::details_after_order_table' , 1, 1);
}, 0);

function oxapay_woo_blocks_support() {
    if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        add_action('woocommerce_blocks_payment_method_type_registration',
                function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                    require_once OxaPay_PLUGIN_DIR . 'inc/woo-block.php';
                    $payment_method_registry->register(new WC_OxaPay_Payments_Block());
                }
        );
    }
}
add_action('woocommerce_blocks_loaded', 'oxapay_woo_blocks_support');

require_once OxaPay_PLUGIN_DIR . 'inc/functions.php';

//add_action('woocommerce_review_order_after_order_total', function(){
//    WC_OxaPay_Gateway::wc_checkout_fields_def();
//});

add_action('wp_ajax_oxapay_check_register', 'WC_OxaPay_Utility::ajax_check_register_plugin');

add_action('init', function () {
     $action = filter_input(INPUT_GET, 'wcox_action');
    if ($action == 'update_currencyapi_rate')
    {
        WC_OxaPay_Utility::update_currencyapi_rate();
    }
});


