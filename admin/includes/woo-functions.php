<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class ECCW_WOO_FUNCTIONS extends ECCW_Plugin_Settings { 

    protected $ecccw_get_plugin_settings;
    protected $plugin_settings;
    protected $currency_server;    

    public function __construct(){
       
        $this->currency_server = new ECCW_CURRENCY_SERVER();
        $this->ecccw_get_plugin_settings = new ECCW_Plugin_Settings();
        $this->plugin_settings = $this->ecccw_get_plugin_settings->ecccw_get_plugin_settings();

        if (!is_admin()) {
 
            add_filter('woocommerce_product_get_price', array($this, 'apply_custom_currency_rate'), 10, 2);
            
            add_filter('woocommerce_product_get_regular_price', array($this, 'apply_custom_currency_rate'), 10, 2);
            add_filter('woocommerce_product_get_sale_price', array($this, 'apply_custom_currency_rate'), 10, 2);

            // NEW filters for variable products
            add_filter('woocommerce_variable_price_html', array($this, 'eccw_convert_variable_price_html'), 10, 2);
            add_filter('woocommerce_variable_sale_price_html', array($this, 'eccw_convert_variable_price_html'), 10, 2);
            add_filter('woocommerce_get_price_html', array($this, 'eccw_convert_variable_price_html'), 20, 2);

            // variation price 

            add_filter('woocommerce_product_variation_get_price', array($this, 'eccw_convert_variation_price'), 10, 2);
            add_filter('woocommerce_product_variation_get_regular_price', array($this, 'eccw_convert_variation_price'), 10, 2);

           
            add_filter('woocommerce_currency_symbol', array($this, 'change_currency_symbol'), 10, 2);
            
            add_filter('wc_price_args', array($this, 'eccw_wc_price_format'));

            add_filter('woocommerce_price_format', array($this,'eccw_dynamic_currency_position'), 9999999, 2 );

            add_action('wp_loaded', array($this,'eccw_set_first_visit_currency_session') );
        }

    }

    function eccw_dynamic_currency_position($format, $currency) {

        $plugin_settings = $this->plugin_settings;
        $allow_payment_with_selected_currency = isset($plugin_settings['options']['allow_payment_with_selected_currency']) && !empty($plugin_settings['options']['allow_payment_with_selected_currency']) ? $plugin_settings['options']['allow_payment_with_selected_currency'] : 'no';

        if( $this->eccw_should_skip_currency_conversion() ) {
            return $format;
        }

       
        $eccw_get_user_preferred_currency_data = $this->currency_server->eccw_get_user_preferred_currency_data();
        if (!empty($eccw_get_user_preferred_currency_data['symbol_position'])) {
            $symbol_position = $eccw_get_user_preferred_currency_data['symbol_position'];

            if ($symbol_position === 'right') {
                $format = '%2$s%1$s'; 
            } elseif ($symbol_position === 'right_space') {
                $format = '%2$s %1$s'; 
            } elseif ($symbol_position === 'left_space') {
                $format = '%1$s %2$s'; 
            } else {
                $format = '%1$s%2$s'; 
            }
        }

        return $format;
    }

    public function apply_custom_currency_rate_order($price, $item) {
    
        return $price; 
    }


    public function eccw_wc_price_format($args) {
        

        $plugin_settings = $this->plugin_settings;
        $allow_payment_with_selected_currency = isset($plugin_settings['options']['allow_payment_with_selected_currency']) && !empty($plugin_settings['options']['allow_payment_with_selected_currency']) ? $plugin_settings['options']['allow_payment_with_selected_currency'] : 'no';

        if( $this->eccw_should_skip_currency_conversion() ) {
            return $args;
        }

        $user_preferred_currency_data = $this->currency_server->eccw_get_user_preferred_currency_data();
        // Customize these settings
        $args['decimal_separator'] = isset($user_preferred_currency_data['decimal_separator']) && !empty($user_preferred_currency_data['decimal_separator']) ? $user_preferred_currency_data['decimal_separator'] : '.';  // Decimal separator (e.g., '.', ',')
        $args['thousand_separator'] = isset($user_preferred_currency_data['thousand_separator']) && !empty($user_preferred_currency_data['thousand_separator']) ? $user_preferred_currency_data['thousand_separator'] : ','; // Thousand separator (e.g., ',', ' ')
        $args['decimals'] = isset($user_preferred_currency_data['decimal']) && !empty($user_preferred_currency_data['decimal']) ? $user_preferred_currency_data['decimal'] : 2;

        return $args;
    }


    public function apply_custom_currency_rate($price, $product) {

        if (!$product instanceof WC_Product) {
            $product = wc_get_product($product);
            if (!$product) return $price;
        }

        $currency_rate = $this->currency_server->eccw_get_currency_rate() ?: 1;

        $geo_prices = apply_filters('eccw_pricing_geo_rules', [], $product);

        $geo_regular_price = isset($geo_prices['easy_geo_regular_price']) 
            ? (float) $geo_prices['easy_geo_regular_price'] 
            : (float) get_post_meta($product->get_id(), '_regular_price', true);

        $geo_sale_price = isset($geo_prices['easy_geo_sale_price']) 
            ? (float) $geo_prices['easy_geo_sale_price'] 
            : (float) get_post_meta($product->get_id(), '_sale_price', true);


        $current_filter = current_filter();

        if ($current_filter === 'woocommerce_product_get_regular_price') {
            $final_price = $geo_regular_price;
        } elseif ($current_filter === 'woocommerce_product_get_sale_price') {
          
            $final_price = !empty($geo_sale_price) && $geo_sale_price > 0 
                ? $geo_sale_price 
                : $geo_regular_price;
        } elseif ($current_filter === 'woocommerce_product_get_price') {
           
            $final_price = !empty($geo_sale_price) && $geo_sale_price > 0
                ? $geo_sale_price
                : $geo_regular_price;
        } else {
            $final_price = $price;
        }

        if (!$this->eccw_should_skip_currency_conversion()) {
            $final_price = (float) $final_price * $currency_rate;
        }

        return $final_price;
    }

    public function eccw_convert_variable_price_html($price_html, $product) {

        if( $this->eccw_should_skip_currency_conversion() ) {
            return $price_html;
        }

        if ( ! $product->is_type('variable') ) {
            return $price_html; 
        }

        $currency_rate = $this->currency_server->eccw_get_currency_rate();
        $currency_symbol = get_woocommerce_currency_symbol();
        $decimals = wc_get_price_decimals();

        if ($currency_rate ) {
            $min_price = (float) $product->get_variation_price('min', true);
            $max_price = (float) $product->get_variation_price('max', true);

            $converted_min = $min_price * $currency_rate;
            $converted_max = $max_price * $currency_rate;

            $converted_min = wc_price($converted_min);
            $converted_max = wc_price($converted_max);

            if ($min_price !== $max_price) {
                $price_html = $converted_min . ' – ' . $converted_max;
            } else {
                $price_html = $converted_min;
            }
        } else {
            return $price_html;
        }

        return $price_html;
    }

    public function eccw_convert_variation_price($price, $product) {
      
        $plugin_settings = $this->plugin_settings;
        $allow_payment_with_selected_currency = $plugin_settings['options']['allow_payment_with_selected_currency'] ?? 'no';
        
        if( $this->eccw_should_skip_currency_conversion() ) {
            return $price;
        }
        
        // Get currency rate and convert
        $currency_rate = $this->currency_server->eccw_get_currency_rate();
        if ($currency_rate && $price > 0) {
            return (float)$price * $currency_rate;
        }
        
        return $price;
    }

    public function change_currency_symbol($currency_symbol, $currency) {

        if ( $this->eccw_should_skip_currency_conversion() ) {
            return $currency_symbol;
        }

        $preferred_currency_data = $this->currency_server->eccw_get_user_preferred_currency_data();
        
        if ( ! empty($preferred_currency_data['custom_symbol']) ) {
            return $preferred_currency_data['custom_symbol'];
        }

        if ( ! empty($preferred_currency_data['code']) ) {
            $all_symbols = get_woocommerce_currency_symbols(); 
            if ( isset($all_symbols[ $preferred_currency_data['code'] ]) ) {
                return $all_symbols[ $preferred_currency_data['code'] ];
            }
        }

        return $currency_symbol;
    }

    /**
     * Check if currency conversion should be skipped for current request.
     *
     * @return bool
     */
    public function eccw_should_skip_currency_conversion() {
        $plugin_settings = $this->plugin_settings; 
        $allow_payment_with_selected_currency = isset($plugin_settings['options']['allow_payment_with_selected_currency']) && !empty($plugin_settings['options']['allow_payment_with_selected_currency'])
            ? $plugin_settings['options']['allow_payment_with_selected_currency']
            : 'no';

        if ($allow_payment_with_selected_currency !== 'no') {
            return false;
        }

        if ((function_exists('is_checkout') && is_checkout() && !is_wc_endpoint_url('order-pay')) ||
            (function_exists('is_wc_endpoint_url') && (is_wc_endpoint_url('order-received') || is_wc_endpoint_url('order-pay')))) {
            return true;
        }

        if (defined('DOING_AJAX') && DOING_AJAX && isset($_REQUEST['wc-ajax'])) {
            $ajax_action = sanitize_text_field($_REQUEST['wc-ajax']);
            $checkout_ajax_actions = array(
                'update_order_review',
                'checkout',
                'get_refreshed_fragments',
                'woocommerce_checkout',
                'add_to_cart',
                'apply_coupon',
                'remove_coupon',
                'update_shipping_method'
            );
            if (in_array($ajax_action, $checkout_ajax_actions)) {
                return true;
            }
            
            if ($ajax_action === 'checkout' && isset($_POST['woocommerce-process-checkout-nonce'])) {
                return true;
            }
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            return true;
        }

        return false;
    }

    function eccw_set_first_visit_currency_session() {

        if ( ! class_exists('WooCommerce') || ! WC()->session ) {
            return;
        }

        $saved_settings = get_option('eccw_currency_settings', []);
        $options = isset($saved_settings['options']) ? $saved_settings['options'] : [];
        $welcome_currency = isset($options['eccw_welcome_currency']) ? $options['eccw_welcome_currency'] : '';

        $already_set = WC()->session->get('eccw_firstvisit_client_currency');

        if ( empty($already_set) ) {
            WC()->session->set('eccw_firstvisit_client_currency', $welcome_currency);
        }
    }
}

new ECCW_WOO_FUNCTIONS();


add_action( 'wp_footer', function(){

});






