<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.
if ( !class_exists('ECCW_Admin_Ajax')) {
    class ECCW_Admin_Ajax extends ECCW_CURRENCY_SERVER {

        public function __construct(){

            add_action( "wp_ajax_eccw_update_currency_rates", array ( $this, 'eccw_update_currency_rates' ) );
            add_action('wp_ajax_eccw_create_shortcode', array ( $this,  'eccw_create_shortcode') );
            add_action('wp_ajax_eccw_delete_shortcode', array ( $this,'eccw_delete_shortcode_callback'));
            add_action('wp_ajax_eccw_save_shortcode_style', array ( $this,'eccw_eccw_shortcode_save_style_callback'));
            add_action('wp_ajax_eccw_load_modal_content', array ( $this,'eccw_load_modal_content_callback'));
            add_action('wp_ajax_eccw_search_shortcode', array ( $this, 'eccw_search_shortcode_callback') );
        }

        public function eccw_update_currency_rates(){

            check_ajax_referer('eccw_nonce', 'nonce');

            $eccw_currency_table = isset($_POST['requestedCurrencies']) ? map_deep( wp_unslash($_POST['requestedCurrencies']), 'sanitize_text_field' ) : [];
            $baseCurrency = isset($_POST['baseCurrency']) ? map_deep( wp_unslash($_POST['baseCurrency']), 'sanitize_text_field' ) : 'USD';

            if (!is_array($eccw_currency_table) || empty($eccw_currency_table)) {
                wp_send_json_error(__('Invalid or empty currency data.', 'easy-currency'));
                return;
            }

           // error_log("request currency". print_r( $eccw_currency_table, true ) );

            $ECCW_CURRENCY_SERVER = new ECCW_CURRENCY_SERVER();
           
            $all_rates = [];
            $currency_rate = 1;
           
            foreach($eccw_currency_table as $key => $currency) {

                $currency_rate = $ECCW_CURRENCY_SERVER->eccw_get_currency_rate_live($baseCurrency, $currency);

                if (!empty($currency_rate['error'])) {
                    wp_send_json_error($currency_rate['error']);
                    return;
                }

                $all_rates[$currency] = $currency_rate;
            }

            // Send success response with all rates.
            wp_send_json_success($all_rates);
            
        }

        public function eccw_search_shortcode_callback() {

            check_ajax_referer('eccw_nonce', 'nonce');

            $search = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

            $cache_key = 'eccw_search_' . md5($search);
    
            $results = wp_cache_get($cache_key, 'eccw_shortcodes');

            global $wpdb;

            if ($results === false) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT id, switcher_name AS text
                        FROM {$wpdb->prefix}eccw_shortcodes
                        WHERE switcher_name LIKE %s
                        ORDER BY created_at DESC",
                        '%' . $wpdb->esc_like( $search ) . '%'
                    ),
                    ARRAY_A
                );

                wp_cache_set($cache_key, $results, 'eccw_shortcodes', MINUTE_IN_SECONDS);
            }

            wp_send_json(['items' => $results]);
        }

        public function eccw_create_shortcode() {
            check_ajax_referer( 'eccw_nonce', 'nonce' );

            if ( ! isset( $_POST['form_data'] ) || empty( $_POST['form_data'] ) ) {
                wp_send_json_error( [ 'message' => __( 'Form data is missing', 'easy-currency' ) ] );
                return;
            }

            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $form_data_raw = wp_unslash( $_POST['form_data'] );

            parse_str( $form_data_raw, $form_data );

            $form_data = array_map( 'sanitize_text_field', $form_data );

            $switcher_name = isset( $form_data['eccw_switcher_name_field'] ) ? $form_data['eccw_switcher_name_field'] : '';
            $template      = isset( $form_data['design']['switcher_dropdown_option']['template'] ) ? $form_data['design']['switcher_dropdown_option']['template'] : '';

            global $wpdb;
            $table_name = $wpdb->prefix . 'eccw_shortcodes';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->insert(
                $table_name,
                [
                    'switcher_name' => $switcher_name,
                    'shortcode'     => '[easy_currency_switcher id=1]',
                    'template'      => $template,
                ],
                ['%s', '%s', '%s']
            );

            $id = $wpdb->insert_id;
            $new_shortcode = "[easy_currency_switcher id=$id]";

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update(
                $table_name,
                ['shortcode' => $new_shortcode],
                ['id' => $id],
                ['%s'],
                ['%d']
            );

            // Clear transient & object cache
            delete_transient('eccw_shortcode_list');
            wp_cache_delete('eccw_shortcode_list', 'options');

            nocache_headers();

            wp_send_json_success([
                'id' => $id,
                'shortcode' => $new_shortcode,
                'cache_bust' => time(),
            ]);

        }

        public function eccw_get_all_shortcodes_cached() {

            $cache_key = 'eccw_shortcode_list';
            $results = wp_cache_get($cache_key, 'eccw_shortcodes');

            if ($results !== false) {
                return $results;
            }

            global $wpdb;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $results = $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}eccw_shortcodes ORDER BY id DESC",
                ARRAY_A
            );

            set_transient('eccw_shortcode_list', $results, 5 * MINUTE_IN_SECONDS);
            wp_cache_set('eccw_shortcode_list', $results, 'options', 5 * MINUTE_IN_SECONDS);

            return $results;
        }


        public function eccw_delete_shortcode_callback() {

            check_ajax_referer('eccw_nonce', 'nonce');

            if (empty($_POST['id'])) {
                wp_send_json_error('Invalid ID');
            }

            $id = absint($_POST['id']);
            global $wpdb;

            $table_name = $wpdb->prefix . 'eccw_shortcodes';

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $deleted = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}eccw_shortcodes WHERE id = %d",
                    $id
                )
            );

            if ($deleted !== false) {
                delete_transient('eccw_shortcode_list');
                wp_cache_delete('eccw_shortcode_list', 'options'); // Clear object cache
                wp_send_json_success('Shortcode deleted');
            } else {
                wp_send_json_error('Could not delete');
            }
        }

        function eccw_eccw_shortcode_save_style_callback() {

            check_ajax_referer('eccw_nonce', 'nonce');

            if( !isset( $_POST['sd_id'] ) || empty( $_POST['sd_id'] )) {
                return;
            }

            $sd_id = absint($_POST['sd_id']);

        
            if (!$sd_id) {
                wp_send_json_error('Invalid shortcode ID.');
            }

            $styles = get_option('eccw_switcher_styles', []);

            if ( isset( $_POST['design'] ) && is_array( $_POST['design'] ) ) {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                $design = wp_unslash( $_POST['design'] );

                foreach ( $design as $key => $value ) {
                    if ( is_array( $value ) ) {
                        $style_data[ $key ] = array_map( 'sanitize_text_field', $value );
                    } else {
                        $style_data[ $key ] = sanitize_textarea_field( $value );
                    }
                }
            }

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

            check_ajax_referer('eccw_nonce', 'nonce');

            $shortcode_id = isset($_POST['shortcode_id']) ? absint($_POST['shortcode_id']) : 0;
            $tab_key      = isset($_POST['tab_key']) ? sanitize_text_field(wp_unslash( $_POST['tab_key'] )) : 'eccw_general_tab';

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

            elseif ($tab_key === 'eccw_display_custom_css') { 
                woocommerce_admin_fields(
                    $admin_settings->get_eccw_settings_modal_custom_css_func($shortcode_id)
                );
            }

            $html = ob_get_clean();
            wp_send_json_success(['html' => $html]);
        }
    }

    
    
    $ECCW_Admin_Ajax = new ECCW_Admin_Ajax();
}