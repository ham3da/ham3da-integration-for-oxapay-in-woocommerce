<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/*
 * Created by Javad Ehteshami(ham3da.ir)
 */

class HAMINFO_OxaPay_Utility {

    public function __construct() {
        
    }

    /**
     * 
     * @param type $amount
     * @param type $wc_currency woocommerce currency code
     * @param type $oxapay_currency usd currency code
     * @param type $convert_rate
     * @return flot amount
     */
    public static function convertCurrency($amount, $wc_currency, $oxapay_currency, $convert_rate) {

        $Amount = $amount;
        if ($wc_currency != $oxapay_currency) {
            $new_amount = ($Amount / $convert_rate);
            $Amount = $new_amount;
        }

        return apply_filters('haminfo_oxapay_convert_currency', $Amount, $wc_currency, $oxapay_currency, $convert_rate);
    }

    public static function is_isset($where, $look) {
        if (is_array($where)) {
            if (isset($where[$look])) {
                return $where[$look];
            }
        } elseif (is_object($where)) {
            if (isset($where->$look)) {
                return $where->$look;
            }
        }
        return '';
    }

    public static function oxapay_is_string($text) {
        $text = (string) $text;
        $text = trim($text);
        return $text;
    }

    /**
     * Money with signs
     * 
     * 
     * @param var $sum sum of amount
     * @param int $cs decimal place default is 6
     * @param string $mode  mode<br>
     * <b>Half_up</b> : Rounds the val from zero to precision decimal if the next character is in the middle.<br>
     * <b>Half_down</b> : Rounds the val in the smaller side to zero to the precision of decimal places, if the next character is in the middle.
     * @return var Money with signs
     */
    public static function oxapay_is_money($sum, $cs = 12, $mode = 'half_up') {
        $sum = self::oxapay_is_string($sum);
        $sum = str_replace(',', '.', $sum);
        $cs = apply_filters('haminfo_oxapay_is_money_cs', $cs);
        $cs = intval($cs);
        if ($cs < 0) {
            $cs = 0;
        }
        if ($sum) {
            if (strstr($sum, 'E')) {
                $sum = sprintf("%0.20F", $sum);
                $sum = rtrim($sum, '0');
            }
            $s_arr = explode('.', $sum);
            $s_ceil = trim(self::is_isset($s_arr, 0));
            $s_double = trim(self::is_isset($s_arr, 1));
            $cs_now = mb_strlen($s_double);

            if ($cs > $cs_now) {
                $cs = $cs_now;
            }

            $new_sum = sprintf("%0.{$cs}F", $sum);
            if (strstr($new_sum, '.')) {
                $new_sum = rtrim($new_sum, '0');
                $new_sum = rtrim($new_sum, '.');
            }
            return apply_filters('haminfo_oxapay_is_money', $new_sum, $sum, $cs, $mode);
        } else {
            return 0;
        }

        return $sum;
    }

    public static function getHost() {
        $host = wp_parse_url(get_site_url(), PHP_URL_HOST);
        $host = preg_replace('/:\d+$/', '', $host);
        $host = str_ireplace('www.', '', $host);
        return trim($host);
    }

    static function get_last_rate_currency($api_key = "", $currency = "EUR") {

        $from = strtoupper($currency);
        $to = 'USD';

        $result = false;
        $error_curl = "";

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
        ));

        $response = wp_remote_get("https://api.currencyapi.com/v3/latest?apikey=$api_key&base_currency=$to&currencies=$from", $args);
        if (is_wp_error($response)) {
            $error_curl = $response->get_error_message();
        } else {
            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body);
        }

        return array('res' => $result, 'err' => $error_curl);
    }

    public static function update_currencyapi_rate() {

        $settings = get_option('woocommerce_' . HAMINFO_OxaPay_GID . '_settings');
        $currencyapi_api_key = isset($settings['currencyapi_api_key']) ? $settings['currencyapi_api_key'] : '';
        if (empty($currencyapi_api_key)) {
            wp_die('Process error[1].', 'Error');
        }

        $currency = get_woocommerce_currency();
        $res = self::get_last_rate_currency($currencyapi_api_key, $currency);

        if (empty($res['err'])) {
            $res_obj = $res['res'];
            $message = "";

            // access the conversion result
            if (isset($res_obj->data)) {
                $price = self::is_isset($res_obj->data, $currency);

                if (isset($price->value)) {
                    $settings['oxapay_convert_rate'] = sanitize_text_field($price->value) ;
                    update_option('woocommerce_' . HAMINFO_OxaPay_GID . '_settings', $settings);
                }
                $message = "Successfully updated.";
            } else {
                $message = $res_obj->message ?? "";
            }
        } else {
            $message = $res['err'];
        }
        wp_die('Process done.<br>' . esc_html($message), 'Done');
    }
}
