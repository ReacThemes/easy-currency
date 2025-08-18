<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.
if ( !class_exists('ECCW_Admin_Ajax')) {
    class ECCW_Admin_Ajax extends ECCW_CURRENCY_SERVER {

        public function __construct(){

            add_action( "wp_ajax_eccw_update_currency_rates", array ( $this, 'eccw_update_currency_rates' ) );
            add_action('wp_ajax_eccw_create_shortcode', array ( $this,  'eccw_create_shortcode') );
            add_action('wp_ajax_eccw_delete_shortcode', array ( $this,'eccw_delete_shortcode_callback'));
            add_action('wp_ajax_eccw_save_shortcode_style', array ( $this,'eccw_save_shortcode_style_callback'));
            add_action('wp_ajax_eccw_load_modal_content', array ( $this,'eccw_load_modal_content_callback'));
            add_action('wp_ajax_eccw_search_shortcode', array ( $this, 'eccw_search_shortcode_callback') );

        }

        

        public function eccw_search_shortcode_callback() {
            check_ajax_referer('eccw_nonce', 'nonce');
            
            $search = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';

            global $wpdb;
            $table = $wpdb->prefix . 'eccw_shortcodes';

            if (empty($search)) {
               
                $results = $wpdb->get_results(
                    "SELECT id, switcher_name AS text FROM $table ORDER BY created_at DESC", 
                    ARRAY_A
                );
            } else {
               
                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT id, switcher_name AS text 
                        FROM $table 
                        WHERE switcher_name LIKE %s
                        ORDER BY created_at DESC",
                        '%' . $wpdb->esc_like($search) . '%'
                    ),
                    ARRAY_A
                );
            }

            wp_send_json(['items' => $results]);
        }


        public function eccw_update_currency_rates(){

            check_ajax_referer('eccw_nonce', 'nonce');

            $eccw_currency_table = isset($_POST['requestedCurrencies']) ? map_deep( wp_unslash($_POST['requestedCurrencies']), 'sanitize_text_field' ) : [];

            if (!is_array($eccw_currency_table) || empty($eccw_currency_table)) {
                wp_send_json_error(__('Invalid or empty currency data.', 'easy-currency'));
                return;
            }

            $ECCW_CURRENCY_SERVER = new ECCW_CURRENCY_SERVER();
            $woocommerce_currency = 'USD';
            $all_rates = [];
            $currency_rate = 1;
           
            foreach($eccw_currency_table as $key => $currency) {

                $currency_rate = $ECCW_CURRENCY_SERVER->eccw_get_currency_rate_live($woocommerce_currency, $currency);

                if (!empty($currency_rate['error'])) {
                    wp_send_json_error($currency_rate['error']);
                    return;
                }

                $all_rates[$currency] = $currency_rate;
            }

            // Send success response with all rates.
            wp_send_json_success($all_rates);
            
        }

        public function eccw_create_shortcode() {
            check_ajax_referer('eccw_nonce', 'nonce');
            parse_str($_POST['form_data'], $form_data);

            $switcher_name = $form_data['eccw_switcher_name_field'];
            $template = $form_data['design']['switcher_dropdown_option']['template'];

            global $wpdb;
            $table = $wpdb->prefix . 'eccw_shortcodes';
            $shortcode = '[eccw_currency_switcher id=1]';
            $wpdb->query(
                $wpdb->prepare("INSERT INTO $table (switcher_name, shortcode,template ) VALUES (%s, %s, %s )", $switcher_name ,$shortcode, $template )
            );

            $id = $wpdb->insert_id;

            $new_shortcode = "[eccw_currency_switcher id=$id]";
            $wpdb->query(
                $wpdb->prepare("UPDATE $table SET shortcode = %s WHERE id = %d", $new_shortcode, $id)
            );

            delete_transient('eccw_shortcode_list');

            nocache_headers();

            wp_send_json_success([
                'id' => $id,
                'shortcode' => $new_shortcode,
                'cache_bust' => time()
            ]);
        }

        public function eccw_get_all_shortcodes_cached() {
            $cached = get_transient('eccw_shortcode_list');

            if ($cached !== false) {
                return $cached;
            }

            global $wpdb;
            $table = $wpdb->prefix . 'eccw_shortcodes';

            $results = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC", ARRAY_A);

            set_transient('eccw_shortcode_list', $results, 5 * MINUTE_IN_SECONDS);

            return $results;
        }

        public function eccw_delete_shortcode_callback() {
            check_ajax_referer('eccw_nonce', 'nonce');

            if (empty($_POST['id'])) {
                wp_send_json_error('Invalid ID');
            }

            $id = absint($_POST['id']);
            global $wpdb;
            $table = $wpdb->prefix . 'eccw_shortcodes';

            $deleted = $wpdb->delete($table, ['id' => $id], ['%d']);

            if ($deleted !== false) {
                delete_transient('eccw_shortcode_list'); 
                wp_send_json_success('Shortcode deleted');
            } else {
                wp_send_json_error('Could not delete');
            }
        }

        function eccw_save_shortcode_style_callback() {
            check_ajax_referer('eccw_nonce', 'nonce');

            $sd_id = absint($_POST['sd_id']);
            if (!$sd_id) {
                wp_send_json_error('Invalid shortcode ID.');
            }

            $styles = get_option('eccw_switcher_styles', []);

            $style_data = isset($_POST['design']) ? $_POST['design'] : [];

            if (!isset($styles[$sd_id])) {
                $styles[$sd_id] = [];
            }

            $styles[$sd_id] = array_merge($styles[$sd_id], $style_data);

            update_option('eccw_switcher_styles', $styles);

            wp_send_json_success();
        }



        // shortcode Dynamic load modal content

        public function eccw_load_modal_content_callback() {
            if (!current_user_can('manage_woocommerce')) {
                wp_send_json_error(['message' => 'Unauthorized']);
            }

            $shortcode_id = isset($_POST['shortcode_id']) ? absint($_POST['shortcode_id']) : 0;
            $tab_key      = isset($_POST['tab_key']) ? sanitize_text_field($_POST['tab_key']) : 'eccw_general_tab';

            ob_start();
            $admin_settings = ECCW_admin_settings::get_instance();

            if ($tab_key === 'eccw_general_tab') {
                woocommerce_admin_fields(
                    $admin_settings->get_eccw_settings_modal_switcher_tab_fields($shortcode_id)
                );
            } elseif ($tab_key === 'eccw_display_option_tab') { 
                woocommerce_admin_fields(
                    $admin_settings->get_eccw_settings_modal_switcher_display_option_fields($shortcode_id)
                );
            }

            $html = ob_get_clean();
            wp_send_json_success(['html' => $html]);
        }




    }
    
    $ECCW_Admin_Ajax = new ECCW_Admin_Ajax();
}
