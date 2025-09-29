<?php
if (!defined('ABSPATH')) die('No direct access allowed');
class ECCW_CURRENCY_SERVER extends ECCW_Plugin_Settings
{

    protected $ecccw_get_plugin_settings;
    protected $plugin_settings;
    public $welcome_currency;

    public function __construct()
    {
        $this->ecccw_get_plugin_settings = new ECCW_Plugin_Settings();
        $this->plugin_settings = $this->ecccw_get_plugin_settings->ecccw_get_plugin_settings();

    }

    public function eccw_get_currency_rate_live_aggregators()
    {

        $aggregators = array(
            'yahoo' => 'www.finance.yahoo.com',
            'openexchangerates' => 'Open exchange rates',
            'cryptocompare' => 'CryptoCompare',
            'ecb' => 'www.ecb.europa.eu',
            'apilayer' => 'API Layer',
            'privatbank' => 'Ukrainian Privatbank [api.privatbank.ua]',
            'mnb' => 'Magyar Nemzeti Bank',
        );

        $aggregators = apply_filters('eccw_aggregator', $aggregators);
        return $aggregators;
    }

    public function eccw_get_currency_countries()
    {

        $currency_countries = array();
        $json_data = wp_remote_get(ECCW_DIR_URL . '/admin/assets/json/currency-countries.json', []);

        if ((!is_wp_error($json_data)) && (200 === wp_remote_retrieve_response_code($json_data))) {
            $currency_countries = json_decode($json_data['body']);
        } else {
            $currency_countries = [];
        }

        return $currency_countries;
    }

    public function get_default_currency()
    {
        $currency_settings = get_option('eccw_currency_settings');
        $default_currency = $currency_settings['default_currency'];

        return $default_currency;
    }

    public function eccw_get_user_preferred_currency()
    {

        $plugin_settings = $this->plugin_settings;
        $eccw_currency_table = isset($plugin_settings['eccw_currency_table']) ? $plugin_settings['eccw_currency_table'] : [];

        $default_currency = isset($_COOKIE['user_preferred_currency']) && !empty($_COOKIE['user_preferred_currency'])
            ? sanitize_text_field(wp_unslash($_COOKIE['user_preferred_currency']))
            : (isset($plugin_settings['default_currency']) ? $plugin_settings['default_currency'] : 'USD');

        if (class_exists('EASY_GEOIP_Currency_Detection')) {
            $geo_data = EASY_GEOIP_Currency_Detection::get_instance()->eccw_set_currency_by_geoip();

            $geo = new WC_Geolocation();
            $geo_data_ip = $geo->geolocate_ip();
            $visitor_country = $geo_data_ip['country'] ?? '';

            if (is_array($geo_data) && !empty($visitor_country)) {
                foreach ($geo_data as $currency_code => $countries) {
                   
                    if (in_array($visitor_country, $countries, true)) {
                        return $currency_code; 
                    }
                }
            }
        }

        $welcome_currency = eccw_get_first_visit_currency();
        if ( !empty($welcome_currency)  && !isset($_COOKIE['user_preferred_currency']) && empty($_COOKIE['user_preferred_currency']) ) {
            $default_currency = $welcome_currency;
        }

        

        return $default_currency;
    }

    public function eccw_get_user_preferred_currency_data()
    {
        $plugin_settings = $this->plugin_settings;

        $default_currency = '';

        $eccw_currency_table = isset($plugin_settings['eccw_currency_table']) ? $plugin_settings['eccw_currency_table'] : [];

        $saved_settings = get_option('eccw_currency_settings');

       $is_checkout_context = is_checkout() || is_order_received_page();

        // Ignore nonce verification warning because this is read-only GET param used safely
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (defined('DOING_AJAX') && DOING_AJAX && isset($_GET['wc-ajax'])) {
            // Ignore nonce verification warning because this is read-only GET param used safely
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $wc_ajax = sanitize_text_field(wp_unslash($_GET['wc-ajax']));
            if ($wc_ajax) {
                $is_checkout_context = true;
            }
        }


        $checkout_currency_payenable = isset($saved_settings['checkout_settings']['eccw_checkout_currency']) 
        ? $saved_settings['checkout_settings']['eccw_checkout_currency'] 
        : '';

        if ($is_checkout_context && WC()->session && WC()->session->get('eccw_checkout_currency') && in_array(  $checkout_currency_payenable, [ 1,'yes'] ) ) {
            $default_currency = sanitize_text_field(WC()->session->get('eccw_checkout_currency'));
        } else {
            
            if (isset($_COOKIE['user_preferred_currency']) && !empty($_COOKIE['user_preferred_currency'])) {
                $default_currency = sanitize_text_field(wp_unslash($_COOKIE['user_preferred_currency']));
            } elseif (isset($plugin_settings['default_currency'])) {
                $default_currency = $plugin_settings['default_currency'];
            }
        }

        if ($is_checkout_context && WC()->session && WC()->session->get('eccw_checkout_currency') && in_array(  $checkout_currency_payenable, [ 1,'yes'] ) ) {
            $default_currency = sanitize_text_field(WC()->session->get('eccw_checkout_currency'));
        } else {

            if (class_exists('EASY_GEOIP_Currency_Detection')) {

                $geo_data = EASY_GEOIP_Currency_Detection::get_instance()->eccw_set_currency_by_geoip();
                $geo = new WC_Geolocation();
                $geo_data_ip = $geo->geolocate_ip();
                $visitor_country = $geo_data_ip['country'] ?? '';

            if (is_array($geo_data) && !empty($visitor_country)) {
                    foreach ( $geo_data as $currency_code => $countries ) {
                        
                        if (in_array($visitor_country, $countries, true)) {
                            $default_currency = $currency_code;
                            break;
                        }
                    }
                }
            }
        }

        $welcome_currency = eccw_get_first_visit_currency();

        if ( !empty($welcome_currency) && !isset($_COOKIE['user_preferred_currency']) && empty($_COOKIE['user_preferred_currency']) ) {
            $default_currency = $welcome_currency;
        }

        $result = array_filter($eccw_currency_table, function ($item) use ($default_currency) {
            return $item["code"] === $default_currency;
        });

        $result = reset($result);
        return $result;
    }


    public function eccw_get_currency_rate()
    {
        $eccw_get_user_preferred_currency_data = $this->eccw_get_user_preferred_currency_data();
        if (isset($eccw_get_user_preferred_currency_data['rate'])) {
            return $eccw_get_user_preferred_currency_data['rate'];
        }
        return 1;
    }

    public function eccw_make_request($url, $args = [])
    {
        $args = wp_parse_args($args, [
            'timeout' => 3,
        ]);

        if (function_exists('wp_remote_get')) {
            $response = wp_remote_get($url, $args);

            if (is_wp_error($response)) {
                return false;
            }

            return wp_remote_retrieve_body($response);
        }

        return false;
    }

    public function eccw_get_currency_rate_live($from_currency, $to_currency)
    {

        $currency_settings = get_option('eccw_currency_settings');
        $selected_server = isset($currency_settings['options']['currency_aggregator']) ? $currency_settings['options']['currency_aggregator'] : 'apilayer';
        $api_key = $currency_settings['options']['currency_aggregator_api_key'] ?? '';

        $from_currency = urlencode($from_currency);
        $to_currency = urlencode($to_currency);

        $invalid_api_msg = 'Invalid API Credentials. Update Valid API Credentials and try again';

        $rate = 1;
        $response_data = [
            'success' => true,
            'error' => false,
        ];

        $rate = 0;

        switch ($selected_server) {
            case 'yahoo':
                $current_time = current_time('timestamp', true);
                $query_url = sprintf(
                    'https://query1.finance.yahoo.com/v8/finance/chart/%s%s=X?symbol=%s%s=X&period1=%d&period2=%d&interval=1d&includePrePost=false&lang=en-US&region=US',
                    $from_currency,
                    $to_currency,
                    $from_currency,
                    $to_currency,
                    $current_time - 60 * 86400,
                    $current_time
                );
                $response = $this->eccw_make_request($query_url);
                $data = json_decode($response, true);

                $result = [];
                if (!empty($data['chart']['result'][0]['indicators']['quote'][0]['open'])) {
                    $result = $data['chart']['result'][0]['indicators']['quote'][0]['open'];
                } elseif (!empty($data['chart']['result'][0]['meta']['previousClose'])) {
                    $result = [$data['chart']['result'][0]['meta']['previousClose']];
                }
                $rate = is_array($result) && count($result) ? end($result) : 1;


                break;

            case 'cryptocompare':
                $query_url = sprintf("https://min-api.cryptocompare.com/data/price?fsym=%s&tsyms=%s", $from_currency, $to_currency);
                $response = $this->eccw_make_request($query_url);
                $data = json_decode($response, true);
                $rate = $data[$to_currency] ?? $rate;

                break;

            case 'ecb':
                $response = $this->eccw_make_request('http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml');
                $data = simplexml_load_string($response);
                $rates = [];
                foreach ($data->Cube->Cube->Cube ?? [] as $xml) {
                    $attr = (array)$xml->attributes();
                    $rates[$attr['@attributes']['currency']] = (float)$attr['@attributes']['rate'];
                }
                $rate = $rates[$to_currency] ?? $rate;
                break;

            case 'apilayer':
                if (!empty($api_key)) {

                    $api_base = 'USD';

                    $query_url = sprintf(
                        "https://api.apilayer.com/exchangerates_data/latest?symbols=%s,%s&base=%s",
                        $from_currency,
                        $to_currency,
                        $api_base
                    );

                    $response = $this->eccw_make_request($query_url, [
                        'headers' => ['apikey' => $api_key]
                    ]);

                    $data = json_decode($response, true);

                    if (
                        isset($data['rates'][$from_currency]) &&
                        isset($data['rates'][$to_currency])
                    ) {

                        $rate = $data['rates'][$to_currency] / $data['rates'][$from_currency];
                    } elseif (isset($data['message'])) {
                        $response_data['success'] = false;
                        $response_data['error'] = $data['message'];
                    }
                }
                break;

            case 'privatbank':
                $response = $this->eccw_make_request('https://api.privatbank.ua/p24api/pubinfo?json&exchange&coursid=5');
                $data = json_decode($response, true);
                $rates = [];
                foreach ($data ?? [] as $currency) {
                    if ($currency['base_ccy'] === 'UAH') {
                        $rates[$currency['ccy']] = (float)$currency['sale'];
                    }
                }
                if ($from_currency !== 'UAH' && $to_currency !== 'UAH') {
                    $rate = $rates[$to_currency] ? ($rates[$from_currency] / $rates[$to_currency]) : $rate;
                } elseif ($to_currency === 'UAH') {
                    $rate = 1 / $rates[$from_currency] ?? $rate;
                } elseif ($from_currency === 'UAH') {
                    $rate = 1 / $rates[$to_currency] ?? $rate;
                }
                break;

            case 'mnb':
                $soap_client = new SoapClient('http://www.mnb.hu/arfolyamok.asmx?wsdl');
                $response = $soap_client->GetCurrentExchangeRates(null)->GetCurrentExchangeRatesResult;
                $data = simplexml_load_string($response);
                $rate_base = $rate_current = 0;

                foreach ($data->Day->Rate ?? [] as $rate_xml) {
                    $attributes = $rate_xml->attributes();
                    if ((string)$attributes->curr === $from_currency && $from_currency !== 'HUF') {
                        $rate_base = (int)$attributes->unit / (float)str_replace(',', '.', $rate_xml);
                    }
                    if ((string)$attributes->curr === $to_currency && $to_currency !== 'HUF') {
                        $rate_current = (int)$attributes->unit / (float)str_replace(',', '.', $rate_xml);
                    }
                }
                $rate = $from_currency === 'HUF' ? $rate_current : (($rate_current / $rate_base) ?: $rate);
                break;

            case 'openexchangerates':

                if (!empty($api_key)) {
                    $query_url = sprintf(
                        "https://openexchangerates.org/api/latest.json?app_id=%s&symbols=%s,%s",
                        $api_key,
                        $from_currency,
                        $to_currency
                    );

                    $response = $this->eccw_make_request($query_url);
                    $data = json_decode($response, true);

                    if (isset($data['rates'][$from_currency]) && isset($data['rates'][$to_currency])) {
                        $rate = $data['rates'][$to_currency] / $data['rates'][$from_currency];
                    } else {
                        $response_data['success'] = false;
                        $response_data['error'] = 'Currency not available in OXR response';
                    }
                } else {
                    $response_data['success'] = false;
                    $response_data['error'] = $invalid_api_msg;
                }
                break;

            default:
                $response_data['error'] = 'Invalid currency server selected';
                $response_data['success'] = false;
                break;
        }

        $response_data['rate'] = $rate;

        if (empty($rate) || $rate == 1) {
            $usd = 'USD';

            if ($from_currency !== $usd && $to_currency !== $usd) {

                $from_to_usd = $this->eccw_get_currency_rate_live($from_currency, $usd);
                $from_usd_rate = $from_to_usd['rate'] ?? 0;

                $to_to_usd = $this->eccw_get_currency_rate_live($to_currency, $usd);
                $to_usd_rate = $to_to_usd['rate'] ?? 0;

                if ($from_usd_rate && $to_usd_rate) {
                    $rate = $from_usd_rate / $to_usd_rate;
                    $response_data['rate'] = $rate;
                }
            }
        }

        return $response_data;
    }
}