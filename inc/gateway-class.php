<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * OxaPay payment gateway Class.
 */
class HAMINFO_OxaPay_Gateway extends WC_Payment_Gateway {

    /** @var bool Whether or not logging is enabled */
    public static $log_enabled = false;

    /** @var WC_Logger Logger instance */
    public static $log = false;
    protected $notify_url;
    public static $gate_id = "HAMINFO_OxaPay_Gateway";
    private $api_key, $debug, $lifetime, $is_fee_paid_by_user;
    public $oxapay_convert_rate;

    /**
     * Constructor for the gateway.
     */
    public function __construct() {
        $this->id = 'HAMINFO_OxaPay_Gateway';
        $this->has_fields = false;
        $this->order_button_text = $this->get_option('pay_btn_text', __('Pay with OxaPay', "ham3da-integration-for-oxapay-in-woocommerce"));
        $this->method_title = __('OxaPay', "ham3da-integration-for-oxapay-in-woocommerce");
        $this->method_description = __('OxaPay redirects customers to OxaPay website for payment.', "ham3da-integration-for-oxapay-in-woocommerce");
        $this->supports = array('products');

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();
        // Define user set variables.
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description', '');
        $this->lifetime = $this->get_option('lifetime', '15');
        $this->is_fee_paid_by_user = $this->get_option('is_fee_paid_by_user', '0');

        $this->icon = apply_filters('HAMINFO_OxaPay_Gateway_logo', $this->get_ico_url());
        $this->debug = ('yes' == $this->get_option('debug', 'no'));

        self::$log_enabled = $this->debug;

        $this->api_key = $this->get_option('api_key', '');

        $this->oxapay_convert_rate = $this->get_option('oxapay_convert_rate', 1);

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        add_action('woocommerce_api_' . strtolower(get_class($this)) . '', array($this, 'check_ipn_response'));

        $this->notify_url = WC()->api_request_url('HAMINFO_OxaPay_Gateway');

        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'site_init'));
    }

    /**
     * Site init hook function
     */
    function site_init() {
        //wp_enqueue_script("jquery");
    }

    function admin_enqueue_scripts() {
        
    }

    /**
     * Logging method.
     * @param string $message
     */
    public static function log($message) {
        if (self::$log_enabled) {
            if (empty(self::$log)) {
                self::$log = new WC_Logger();
            }
            self::$log->error($message, array('source' => 'oxapay_log'));
        }
    }

    /**
     * Get gateway icon.
     * @return string
     */
    public function get_icon() {
        $icon_html = '<img src="' . $this->get_ico_url() . '" alt="' . __('OxaPay', "ham3da-integration-for-oxapay-in-woocommerce") . '" />';
        return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
    }

    function get_ico_url() {
        $icon = $this->get_option('oxapay_logo', null);
        $url = HAMINFO_OxaPay_PLUGIN_URL . 'assets/images/oxapay.png';
        if (!empty($icon)) {
            $url = $icon;
        }
        return $url;
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields() {
        $icon = $this->get_ico_url();
        $site_url = get_site_url();

        $cron_url = add_query_arg('wcox_action', 'update_currencyapi_rate', $site_url);

        $c_code = get_woocommerce_currency();

        $fields = apply_filters('HAMINFO_OxaPay_Gateway_Config', array(
            'oxapay_register' => array(
                'title' => __('OxaPay Settings', "ham3da-integration-for-oxapay-in-woocommerce"),
                'description' => '<p class="description">' .
                __('Complete this form after creating an account in OxaPay.', "ham3da-integration-for-oxapay-in-woocommerce") .
                '</p>'
                . '<p><a href="https://oxapay.com/?ref=30943315" class="button" target="_blank">' .
                __('Create an account', "ham3da-integration-for-oxapay-in-woocommerce") .
                '</a></p>',
                'type' => 'title',
                'desc_tip' => false,
            ),
            'enabled' => array(
                'title' => __('Enable/Disable', "ham3da-integration-for-oxapay-in-woocommerce"),
                'type' => 'checkbox',
                'label' => __('Enable OxaPay payment method', "ham3da-integration-for-oxapay-in-woocommerce"),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title', "ham3da-integration-for-oxapay-in-woocommerce"),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', "ham3da-integration-for-oxapay-in-woocommerce"),
                'default' => __('OxaPay', "ham3da-integration-for-oxapay-in-woocommerce"),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', "ham3da-integration-for-oxapay-in-woocommerce"),
                'type' => 'text',
                'desc_tip' => true,
                'description' => __('This controls the description which the user sees during checkout.', "ham3da-integration-for-oxapay-in-woocommerce"),
                'default' => __('Secure payment with OxaPay payment gateway', "ham3da-integration-for-oxapay-in-woocommerce"),
            ),
            'pay_btn_text' => array(
                'title' => __('Order button text', "ham3da-integration-for-oxapay-in-woocommerce"),
                'type' => 'text',
                'default' => __('Pay with OxaPay', "ham3da-integration-for-oxapay-in-woocommerce"),
                'desc_tip' => true,
            ),
            'api_key' => array(
                'title' => __('Merchant API Key', "ham3da-integration-for-oxapay-in-woocommerce"),
                'type' => 'text',
                'description' => __('Please enter your OxaPay Merchant API Key.', "ham3da-integration-for-oxapay-in-woocommerce"),
                'default' => '',
                'desc_tip' => false,
            ),
            'debug' => array(
                'title' => __('Debug Log', "ham3da-integration-for-oxapay-in-woocommerce"),
                'type' => 'checkbox',
                'label' => __('Enable logging', "ham3da-integration-for-oxapay-in-woocommerce"),
                'default' => 'no',
                'description' => __('Log OxaPay events, such as payment confirmations.', "ham3da-integration-for-oxapay-in-woocommerce")
            ),
            'lifetime' => array(
                'type' => 'select',
                'title' => __('Payment expiration', "ham3da-integration-for-oxapay-in-woocommerce"),
                'description' => __('Select the payment expiration time.', "ham3da-integration-for-oxapay-in-woocommerce"),
                'options' => array(
                    '15' => __('15 mins', "ham3da-integration-for-oxapay-in-woocommerce"),
                    '30' => __('30 mins', "ham3da-integration-for-oxapay-in-woocommerce"),
                    '60' => __('60 mins', "ham3da-integration-for-oxapay-in-woocommerce"),
                    '90' => __('90 mins', "ham3da-integration-for-oxapay-in-woocommerce"),
                    '120' => __('120 mins', "ham3da-integration-for-oxapay-in-woocommerce")
                ),
                'default' => '15',
            ),
            'is_fee_paid_by_user' => array(
                'type' => 'select',
                'title' => __('Is fee paid by user?', "ham3da-integration-for-oxapay-in-woocommerce"),
                'description' => __('In this section, you determine whether the user will pay the transaction fee.', "ham3da-integration-for-oxapay-in-woocommerce"),
                'options' => array(
                    '0' => __('No', "ham3da-integration-for-oxapay-in-woocommerce"),
                    '1' => __('Yes', "ham3da-integration-for-oxapay-in-woocommerce"),
                ),
                'default' => '0',
            ),
            'oxapay_logo' => array(
                'title' => __('Gateway logo', "ham3da-integration-for-oxapay-in-woocommerce"),
                'type' => 'text',
                'desc_tip' => false,
                'description' => __('Logo URL', "ham3da-integration-for-oxapay-in-woocommerce"),
                'default' => $icon,
            ),
            'oxapay_convert_rate' => array(
                'title' => __('Conversion Rate', "ham3da-integration-for-oxapay-in-woocommerce"),
                /* translators: %s: woocommerce currency symbol */
                'description' => '<p>' . __('If your WooCommerce currency is not usd, enter the conversion rate; Otherwise enter 1.', "ham3da-integration-for-oxapay-in-woocommerce") . '<br>' . sprintf(__('Method of calculating the conversion rate: the price of one USD to %s', "ham3da-integration-for-oxapay-in-woocommerce"), get_woocommerce_currency_symbol($c_code)) . '</p>',
                'type' => 'text',
                'default' => '1',
                'desc_tip' => false,
            ),
            'is_show_usd_price' => array(
                'type' => 'select',
                'title' => __('Show total amount in USD', "ham3da-integration-for-oxapay-in-woocommerce"),
                'description' => __('Display the total amount in USD on the checkout page', "ham3da-integration-for-oxapay-in-woocommerce"),
                'options' => array(
                    '0' => __('No', "ham3da-integration-for-oxapay-in-woocommerce"),
                    '1' => __('Yes', "ham3da-integration-for-oxapay-in-woocommerce"),
                ),
                'default' => '0',
            ),
            
            'currencyapi_title' => array(
                'title' => __('Automatic conversion rate updates', "ham3da-integration-for-oxapay-in-woocommerce"),
                'type' => 'title',
                /* translators: %s: currencyapi address */
                'description' => sprintf(__('Automatic update of conversion rate from %s .', "ham3da-integration-for-oxapay-in-woocommerce"), '<a href="https://currencyapi.com">currencyapi.com</a>') .
                '<p>' . __('Use this url to automatically update the rate in Cron jobs:', "ham3da-integration-for-oxapay-in-woocommerce") . '<br><code>' . $cron_url . '</code></p>',
                'desc_tip' => false,
            ),
            'currencyapi_api_key' => array(
                'title' => __('API Key', "ham3da-integration-for-oxapay-in-woocommerce"),
                'type' => 'text',
                'desc_tip' => false,
                /* translators: %s: currencyapi address */
                'description' => sprintf(esc_html__('Get it from %s', "ham3da-integration-for-oxapay-in-woocommerce"), '<a target="_blank" href="https://currencyapi.com/">currencyapi.com</a>'),
                'default' => '',
            )
        ));

        $this->form_fields = $fields;
    }

    function check_ipn_response() {

        $error_msg = __("Unknown error", "ham3da-integration-for-oxapay-in-woocommerce");
        $auth_ok = false;
        $data = null;

        if (isset($_SERVER['HTTP_HMAC']) && !empty($_SERVER['HTTP_HMAC'])) {


            $postData = file_get_contents('php://input');

            $data = json_decode($postData, true);

            $apiSecretKey = $this->api_key;

            $hmacHeader = filter_var($_SERVER['HTTP_HMAC'], FILTER_SANITIZE_SPECIAL_CHARS);
            
            $calculatedHmac = hash_hmac('sha512', $postData, $apiSecretKey);

            self::log(var_export($data, true));

            if ($calculatedHmac === $hmacHeader) {
                $auth_ok = true;
            }
        } else {
            $error_msg = __("No HMAC signature sent.", "ham3da-integration-for-oxapay-in-woocommerce");
        }

        if ($auth_ok) {
            $valid_order_id = $orderId = $data['orderId'];
            $order = wc_get_order($valid_order_id);

            if ($order) {

                if (!$order->is_paid()) {
                    $this->set_order_status($order, $data);
                    http_response_code(200);
                } else {

                    /* translators: %s: order id */
                    $this->log(sprintf(__('Payment has already been completed.[id: %s]', "ham3da-integration-for-oxapay-in-woocommerce"), $valid_order_id));
                    http_response_code(400);
                    wp_die(esc_html__('Payment has already been completed.', "ham3da-integration-for-oxapay-in-woocommerce"), 'error');
                }
            } else {
                /* translators: %s: order id */
                $error_msg = sprintf(esc_attr__('Order not found![id: %s]', "ham3da-integration-for-oxapay-in-woocommerce"), $valid_order_id);

                self::log(print_r(['error_msg' => $error_msg], true));
                http_response_code(404);
                wp_die(esc_html($error_msg), 'error');
            }
        } else {
            self::log(print_r(['error_msg_auth' => $error_msg], true));
            http_response_code(400);
            wp_die(esc_html($error_msg), 'error');
        }

        exit();
    }

    /**
     * 
     * @param WC_Order $order 
     * @param int $order_id
     * @param array $request_data
     */
    function save_data($order, $order_id, $request_data) {

        $txID = isset($data['txID']) ? $data['txID'] : null;
        $network = isset($data['network']) ? $data['network'] : null;
        $payAmount = isset($data['payAmount']) ? $data['payAmount'] : null;
        $payCurrency = isset($data['payCurrency']) ? $data['payCurrency'] : null;
        $senderAddress = isset($data['senderAddress']) ? $data['senderAddress'] : null;

        $order->update_meta_data('_oxapay_txID', $txID);
        $order->update_meta_data('_oxapay_network', $network);
        $order->update_meta_data('_oxapay_pay_amount', $payAmount);
        $order->update_meta_data('_oxapay_senderAddress', $senderAddress);
        $order->update_meta_data('_oxapay_pay_currency', $payCurrency);
        $order->save();
    }

    public static function convert_to_wc_status($status) {
        switch ($status) {
            case 'Waiting':
                $result = 'pending';
                break;

            case 'Confirming':
                $result = 'processing';
                break;

            case 'Failed':
            case 'Expired':
                $result = 'failed';
                break;
            case 'Paid':
                $result = 'completed';
                break;
        }
        return $result;
    }

    /**
     * 

     * @param WC_Order $order
     * @param array $data
     */
    function set_order_status($order, $data) {


        $txID = isset($data['txID']) ? $data['txID'] : null;
        $network = isset($data['network']) ? $data['network'] : null;
        $payAmount = isset($data['payAmount']) ? $data['payAmount'] : null;
        $payCurrency = isset($data['payCurrency']) ? $data['payCurrency'] : null;
        $senderAddress = isset($data['senderAddress']) ? $data['senderAddress'] : null;
        $trackId = isset($data['trackId']) ? $data['trackId'] : null;
        $status = isset($data['status']) ? $data['status'] : null;

        $order_waiting = $order->get_meta("_oxapay_waiting", true);

        $pay_status = self::convert_to_wc_status($status);

        switch ($pay_status) {
            case "completed":

                if (!$order->has_status('completed')) {
                    $order->payment_complete($trackId);

                    $order->add_order_note("<p>Transaction ID: $trackId <br>"
                            . "Hash: $txID <br>"
                            . "Network : $network <br>"
                            . "Amount paid: $payAmount ($payCurrency) <br>"
                            . "Sender address: $senderAddress"
                            . "</p>");

                    $this->save_data($order, $order->get_id(), $data);
                    wc_add_notice(__('Payment was successful!', "ham3da-integration-for-oxapay-in-woocommerce"), 'error');
                }
                break;
            case "processing":
                if (!$order->has_status('processing')) {
                    $order->update_status('processing', __('Order is confirming.', "ham3da-integration-for-oxapay-in-woocommerce"));
                }
                break;
            case "failed":
                if (!$order->has_status('failed')) {
                    $order->update_status('failed', __('Order is failed.', "ham3da-integration-for-oxapay-in-woocommerce"));
                }
                break;
            case "pending":

                if (!$order->has_status('pending')) {
                    $order->update_status('pending', __('Order is pending.', "ham3da-integration-for-oxapay-in-woocommerce"));
                }

                if ($order->has_status('pending') && $order_waiting != 1) {
                    /* translators: %s: track Id */
                    $order->add_order_note(sprintf(__('Awaiting Payment. payment id: %s', "ham3da-integration-for-oxapay-in-woocommerce"), $trackId));

                    $order->update_meta_data('_oxapay_waiting', 1);
                    $order->save();
                }
                break;

            default:
                break;
        }
    }

    function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $success_redirect_url = $this->get_return_url($order);
        //$cancel_redirect_url = wc_get_checkout_url();

        $Amount = $order->get_total();
        $wc_currency_lower = strtolower($order->get_currency());

        $Amount_to_usd = HAMINFO_OxaPay_Utility::convertCurrency($Amount, $wc_currency_lower, "usd", $this->oxapay_convert_rate);
        $Amount_to_usd = HAMINFO_OxaPay_Utility::oxapay_is_money($Amount_to_usd, 2);

        if ($Amount_to_usd > 0) {

            $oreder_id = $order->get_id();

            $data = [
                'merchant' => $this->api_key,
                'amount' => $Amount_to_usd,
                'currency' => 'USD',
                'orderId' => $oreder_id,
                'returnUrl' => $success_redirect_url,
                'callbackUrl' => $this->notify_url,
                'lifeTime' => $this->lifetime,
                'feePaidByPayer' => $this->is_fee_paid_by_user,
            ];

            $error_curl = "";
            $result = array();

            $url = 'https://api.oxapay.com/merchants/request';

            $jsonData = wp_json_encode($data);

            $options = [
                'body' => $jsonData,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 20,
                'redirection' => 5,
                'httpversion' => '1.1',
                'sslverify' => false,
            ];

            $response = wp_remote_post($url, $options);

            if (is_wp_error($response)) {

                $error_message = $response->get_error_message();
                $order->add_order_note($error_message);
                wc_add_notice($error_message, 'error');

                return array(
                    'result' => 'fail',
                    'redirect' => '',
                    'errorMessage' => __('Error connecting to payment gateway', "ham3da-integration-for-oxapay-in-woocommerce"),
                );
            } else {

                $result = json_decode(wp_remote_retrieve_body($response));
            }


            if (isset($result->result) && $result->result == 100) {
                self::log(print_r($result, true));
                $trackId = $result->trackId;

                $order->update_meta_data('_oxapay_amount_pay', $Amount_to_usd);
                $order->update_meta_data('_oxapay_currency', "usd");
                $order->update_meta_data('_oxapay_trackId', $trackId);
                $order->save();

                return array(
                    'result' => 'success',
                    'redirect' => $result->payLink
                );
            } else {

                $err = __('An error has occurred.', "ham3da-integration-for-oxapay-in-woocommerce");
                wc_add_notice(__('An error has occurred. Contact site support.', "ham3da-integration-for-oxapay-in-woocommerce"), 'error');
                $order->add_order_note(print_r([$result,$jsonData], true));
                /* translators: %s: error msg */
                $Notice = sprintf(__('Error connecting to the payment gateway: %s', "ham3da-integration-for-oxapay-in-woocommerce"), $err);

                return array(
                    'result' => 'fail',
                    'redirect' => '',
                    'errorMessage' => $Notice,
                );
            }
        } else {
            $order->add_order_note(__('The payment amount is invalid!', "ham3da-integration-for-oxapay-in-woocommerce"));
            wc_add_notice(__('The payment amount is invalid!', "ham3da-integration-for-oxapay-in-woocommerce"), 'error');

            return array(
                'result' => 'fail',
                'redirect' => '',
                'errorMessage' => __('The payment amount is invalid!', "ham3da-integration-for-oxapay-in-woocommerce"),
            );
        }
    }

    /**
     * Can the order be refunded via Pm?
     * @param  WC_Order $order
     * @return bool
     */
    public function can_refund_order($order) {
        return false;
    }

    public static function details_after_order_table($order) {


        $order_id = $order->get_id();
        $order_new = new WC_Order($order_id);

        $trackId = $order_new->get_meta('_oxapay_trackId', true);
        $pay_address = $order_new->get_meta('_oxapay_senderAddress', true);

        $pay_amount = $order_new->get_meta('_oxapay_pay_amount', true);
        $network = $order_new->get_meta('_oxapay_network', true);
        $pay_currency = $order_new->get_meta('_oxapay_pay_currency', true);

        $wm_amount_pay = $order_new->get_meta('_oxapay_amount_pay', true);
        $wm_currency = $order_new->get_meta('_oxapay_currency', true);

        $wm_detail = "";
        $status = $order_new->is_paid();
        if ($order_new->get_payment_method() == self::$gate_id) {

            if (!empty($wm_amount_pay)) {
                $wm_detail .= '<tr>'
                        . '<th scope="row">'
                        . (($status) ? esc_html__('Amount paid:', "ham3da-integration-for-oxapay-in-woocommerce") : esc_html__('Amount to pay:', "ham3da-integration-for-oxapay-in-woocommerce")) .
                        '</th>'
                        . '<td>' . number_format($wm_amount_pay, 2) . ' ' . __('USD', "ham3da-integration-for-oxapay-in-woocommerce") . ' ' . (!empty($pay_amount) ? ' = ' : '') . $pay_amount . ' ' . strtoupper($pay_currency) . '</td>'
                        . '</tr>';
            }
            if (!empty($trackId)) {
                $wm_detail .= '<tr>'
                        . '<th scope="row">' . esc_html__('Track ID:', "ham3da-integration-for-oxapay-in-woocommerce") . '</th><td>' . esc_attr($trackId) . '</td>'
                        . '</tr>';
            }

            if (!empty($pay_address)) {
                $wm_detail .= '<tr>'
                        . '<th scope="row">' . esc_html__('Payer address:', "ham3da-integration-for-oxapay-in-woocommerce") . '</th><td>' . esc_attr($pay_address) . '</td>'
                        . '</tr>';
            }


            $status_labels = ActionScheduler_Store::instance()->get_status_labels();
            $status_name = isset($status_labels[$order_new->get_status()]) ? $status_labels[$order_new->get_status()] : ucfirst($order_new->get_status());

            $wm_detail .= '<tr>'
                    . '<th scope="row">' . esc_html__('Status:', "ham3da-integration-for-oxapay-in-woocommerce") . '</th><td>' . esc_attr($status_name)  . '</td>'
                    . '</tr>';

            if (!empty($wm_detail)) {
                $wm_detail2 = '<h2 class="woocommerce-order-details__title">' . esc_html__('CryptoCurrency payment', "ham3da-integration-for-oxapay-in-woocommerce") . '</h2>'
                        . '<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">'
                        . '<tfoot>'
                        . $wm_detail
                        . '</tfoot>'
                        . '</table>';

                $res_text = wpautop($wm_detail2);
                $res_text = apply_filters('oxapay_details_after_order_table', $res_text, $wm_amount_pay, $wm_currency, $order);
                echo wp_kses($res_text, 'post');
            }
        }
    }

    public static function wc_checkout_fields_def() {

        $chosen_payment_method = WC()->session->get('chosen_payment_method');
        $wc_cuurency = get_woocommerce_currency();
        $settings = get_option('woocommerce_' . self::$gate_id . '_settings');

        $wm_convert_rate2 = isset($settings['oxapay_convert_rate']) ? $settings['oxapay_convert_rate'] : '';

        $enabled = isset($settings['enabled']) ? $settings['enabled'] : 'no';

        $is_show_usd_price = isset($settings['is_show_usd_price']) ? $settings['is_show_usd_price'] : 0;
        //$wm_currency = "";

        if ($wm_convert_rate2 && $enabled == 'yes' && $is_show_usd_price == 1) {
            if (strtolower($wc_cuurency) != "usd") {
                ?>
                <tr>
                    <th>
                        <?php
                        esc_attr_e('Equal to', "ham3da-integration-for-oxapay-in-woocommerce");
                        ?> 
                    </th>
                    <td>
                        <?php
                        $Amount = WC()->cart->total;
                        $Amount_to_usd = HAMINFO_OxaPay_Utility::convertCurrency($Amount, strtolower($wc_cuurency), "usd", $wm_convert_rate2);
                        $Amount_to_usd = HAMINFO_OxaPay_Utility::oxapay_is_money($Amount_to_usd, 2);

                        /* translators: %s: amount */
                        printf(esc_html__('$ %s', "ham3da-integration-for-oxapay-in-woocommerce"), number_format($Amount_to_usd, 2));
                        ?>
                    </td>
                </tr>
                <?php
            }
        }
    }
}
