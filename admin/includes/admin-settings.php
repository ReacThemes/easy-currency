<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly.

class ECCW_admin_settings
{

    private static $instance = null;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        add_filter('woocommerce_settings_tabs_array', array($this, 'eccw_settings_tab'), 50);
        add_action('woocommerce_settings_eccw_settings_tab', array($this, 'eccw_settings_tab_settings'));
        add_action('woocommerce_update_options_eccw_settings_tab', array($this, 'save_eccw_settings_tab_settings'));
        add_action('admin_menu', array($this, 'eccw_add_easy_currency_menu'));
					
    }

    public function eccw_settings_tab($tabs)
    {
        $tabs['eccw_settings_tab'] = __('Easy Currency', 'easy-currency'); // Add the custom tab
        return $tabs;
    }
    public function get_eccw_settings_options_tab_fields()
    {
        // Retrieve saved settings
        $saved_settings = get_option('eccw_currency_settings');
        $options = isset($saved_settings['options']) ? $saved_settings['options'] : [];

        $ECCW_CURRENCY_SERVER = new ECCW_CURRENCY_SERVER();
        $aggregators = $ECCW_CURRENCY_SERVER->eccw_get_currency_rate_live_aggregators();

        $code_currency_arr = [];
        if (!empty($saved_settings) && isset($saved_settings['eccw_currency_table']) && count($saved_settings['eccw_currency_table']) > 0) {
            $eccw_currency_table = $saved_settings['eccw_currency_table'];

            foreach ($eccw_currency_table as $currency_data) {
                if (!empty($currency_data['code'])) {
                    $code_currency_arr[$currency_data['code']] = $currency_data['code'];
                }
            }
        }

        $default_country = get_option('woocommerce_default_country', '');
        $country_code    = explode(':', $default_country)[0];

        $currency_countries2 = $ECCW_CURRENCY_SERVER->eccw_get_currency_countries();

        $currency_countries = json_decode(json_encode($currency_countries2), true);

        $matched_currency = '';

        foreach ( $currency_countries as $currency_code => $data ) {
            if ( in_array( $country_code, $data['countries'], true ) ) {
                $matched_currency = $currency_code;
                break;
            }
        }

        $eccw_global_options_settings = array(
            'section_title' => array(
                'name' => __('Change currency options from here.', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_settings_tab_section_title'
            ),
            'easy_welcome_currency' => array(
                'name' => __('Welcome Currency', 'easy-currency'),
                'type' => 'select',
                'desc' => __('The first-time visitor will see the selected "Welcome Currency" regardless of your store’s default currency.', 'easy-currency'),
                'id' => 'options[eccw_welcome_currency]',
                'options' => $code_currency_arr,
                'default' => isset($options['eccw_welcome_currency']) ? $options['eccw_welcome_currency'] : $matched_currency,
                'class' => 'eccw-welcome-currency-input', 
            ),
            'currency_aggregator' => array(
                'name' => __('Currency aggregator', 'easy-currency'),
                'type' => 'select',
                'desc' => __('Select your Currency Aggregator.', 'easy-currency'),
                'id' => 'options[currency_aggregator]',
                'options' => $aggregators,
                'default' => isset($options['currency_aggregator']) ? $options['currency_aggregator'] : 'yahoo',
                'class' => 'eccw-currency-aggregator-input', 
            ),
            'currency_aggregator_api_key' => array(
                'name' => __('Api Key', 'easy-currency'),
                'type' => 'text',
                'desc' => __('Enter aggregator api key.', 'easy-currency'),
                'id' => 'options[currency_aggregator_api_key]',
                'default' => isset($options['currency_aggregator_api_key']) ? $options['currency_aggregator_api_key'] : '',
                'class' => 'eccw-currency-aggregator-api-key-input', 
            ),
            'allow_payment_with_selected_currency' => array(
                'name' => __('Payment with selected currency', 'easy-currency'),
                'type' => 'select',
                'options' => ['yes' => 'Yes', 'no' => 'No'],
                'desc' => __('Allow selected currency for payment. User cam pay with selected currency if this yes.', 'easy-currency'),
                'id' => 'options[allow_payment_with_selected_currency]',
                'default' => isset($options['allow_payment_with_selected_currency']) ? $options['allow_payment_with_selected_currency'] : 'no',
            ),
            'section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_settings_tab_section_end'
            )
        );

        $eccw_global_options_settings = apply_filters('eccw_global_options_settings_array', $eccw_global_options_settings);

        $switcher_location_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-location-display-layout">'
            )
        );

        $single_settings = array(
            'switcher_location_title' => array(
                'name' => __('Product Single Page ', 'easy-currency'),
                'type' => 'title',
                'desc' => 'Display the currency switcher on Single product page.',
                'id' => 'eccw_settings_switcher_location_title'
            ),
            'eccw_enable_disable_location' => array(
                'title' => __('Show / Hide', 'easy-currency'),
                'id'    => 'options[eccw_show_hide_single_product_location]',
                'type'  => 'switcher',
                'default' => isset($options['eccw_show_hide_single_product_location']) ? $options['eccw_show_hide_single_product_location'] : 'yes',
                'class' => 'eccw-switcher-ui-control-show-hide',
            ),
            'eccw_shortcode_show_on_product' => array(
                'name' => __('Select Shortcode', 'easy-currency'),
                'type' => 'eccw_searchable_select',
                'desc' => __('Choose the shortcode you want to show in single product pages.', 'easy-currency'),
                'id' => 'options[eccw_shortcode_show_on_product_pages]',
                'default' => isset($options['eccw_shortcode_show_on_product_pages']) ? $options['eccw_shortcode_show_on_product_pages'] : 'List - Shortcode',
                'class' => 'eccw-searchable-select-dropdown',
            ),
            'switcher_position_in_product_single' => array(
                'name' => __('Switcher Position in Product page', 'easy-currency'),
                'type' => 'select',
                'options' => [
                    'woocommerce_after_add_to_cart_form' => 'After - Add to cart', 
                    'woocommerce_before_add_to_cart_form' => 'Before - Add to cart',
                    'woocommerce_product_meta_end' => 'After - Product Meta',
                    'woocommerce_product_meta_start' => 'Before - Product Meta',
                    'before_short_description' => 'After - After Price',
                ],
                'desc' => __('Choose the position of the switcher shrotcode in the product detail pages.', 'easy-currency'),
                'id' => 'options[eccw_shortcode_pos_product_singlepage]',
                'default' => isset($options['eccw_shortcode_pos_product_singlepage']) ? $options['eccw_shortcode_pos_product_singlepage'] : 'woocommerce_before_add_to_cart_form',
                'class' => 'eccw-switcher-single-product-hook',
            ),
            'product_single_section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_settings_tab_section_end'
            )
        );

        $switcher_location_end = array(
            array(
                'type' => 'html',
                'html' => '</div>'
            )
        );

        $all_settings = array_merge($eccw_global_options_settings, $switcher_location_start, $single_settings,$switcher_location_end );


        return $all_settings;
    }


    public function get_eccw_settings_modal_switcher_tab_fields($current_shortcodeId = null)
    {
        // Retrieve saved settings
        $saved_settings = get_option('eccw_switcher_styles');

        $design = isset($saved_settings[$current_shortcodeId]) ? $saved_settings[$current_shortcodeId] : [];

        global $wpdb;

        $template = isset( $design['switcher_dropdown_option_edit']['template'] ) ? $design['switcher_dropdown_option_edit']['template'] : 'eccw_template_1';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query(
            $wpdb->prepare("UPDATE {$wpdb->prefix}eccw_shortcodes SET template = %s WHERE id = %d", $template, $current_shortcodeId)
        );

        $cache_key = 'eccw_shortcode_' . $current_shortcodeId;

        $results = wp_cache_get($cache_key, 'eccw_shortcodes');

        if ($results === false) {
            global $wpdb;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}eccw_shortcodes WHERE id = %d ORDER BY id DESC",
                    $current_shortcodeId
                ),
                ARRAY_A
            );

            // Set results in object cache for 5 minutes
            wp_cache_set($cache_key, $results, 'eccw_shortcodes', 5 * MINUTE_IN_SECONDS);
        }

        $template_style = isset( $results['0']['template'] ) ? $results['0']['template'] : 'eccw_template_1';


        $switcher_layout_style_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-style-section eccw-layout-style-display">'
            )
        );

        $layout_style = array(
            'section_title_edit_layout_style' => array(
                'name' => '',
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_settings_tab_layout_section_title_update'
            ),
            'switcher_template_edit' => array(
                'name'     => __('Template', 'easy-currency'),
                'type'     => 'template_preview',
                'id'       => 'design[switcher_dropdown_option_edit][template]',
                'default'  => $template_style,
                'value'    => $template_style,
                'desc'     => __('Edit your currency switcher template', 'easy-currency'),
                'templates' => array(
                    'eccw_template_1' => ECCW_PL_URL . 'admin/assets/img/eccw-template-1.png',
                    'eccw_template_2' => ECCW_PL_URL . 'admin/assets/img/eccw-template-2.png',
                ),
                'class' => 'eccw-switcher-ui-control'
            ),
            'layout_section_edit_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_settings_tab_layout_section_edit_end'
            )
        );

        $switcher_layout_style_end = array(
            array(
                'type' => 'html',
                'html' => '</div>'
            )
        );

        $switcher_button_style_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-style-section eccw-button-style-display">'
            )
        );

        $settings = array(
            'section_title' => array(
                'name' => __('Switcher Toggle Style', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_settings_tab_section_title'
            ),
             'switcher_button_option_alignment' => array(
                'name'   => 'Alignment',
                'id' => 'design[switcher_button][justify-content]',
                'type'    => 'tabswitch',
                'default' => isset($design['switcher_button']['justify-content']) ? $design['switcher_button']['justify-content'] : 'center',
                'options' => array(
                    'left'   => 'Left',
                    'center' => 'Center',
                    'right'  => 'Right'
                )
            ),
            'switcher_button_width' => array(
                'name' => __('Width', 'easy-currency'),
                'type' => 'number',
                'id' => 'design[switcher_button][width]',
                'default' => isset($design['switcher_button']['width']) ? str_replace('px', '', $design['switcher_button']['width']) : '218',
                'class' => 'eccw-currency-switcher-button-width eccw-rang-input', 
                'custom_attributes' => array(
                    'unit' => 'px',
                )
            ),
            'switcher_button_bg' => array(
                'name' => __('Background Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_button][background]',
                'default' => isset($design['switcher_button']['background']) ? $design['switcher_button']['background'] : '',
                'class' => 'eccw-color-input ', 
            ),
            'switcher_style_option_color' => array(
                'name' => __('Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_button][color]',
                'default' => isset($design['switcher_button']['color']) ? $design['switcher_button']['color'] : '',
                'class' => 'eccw-color-input ', 
            ),
            'switcher_button_border_control_style' => array(
                'name' => 'Border Control',
                'type'  => 'eccw_border_control',
                'id' => 'design[switcher_button][border_control]',
                'default' => isset($design['switcher_button']['border_control']) ? $design['switcher_button']['border_control'] : '',
                'desc'  => 'Set border width for each side, style and color.'
            ),
            'switcher_button_border_radius' => array(
                'name' => __('Border Radius', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 5px',
                'id' => 'design[switcher_button][border-radius]',
                'default' => isset($design['switcher_button']['border-radius']) ? $design['switcher_button']['border-radius'] : '',
                'placeholder' => '5px',
                'class' => 'eccw-input ', 
            ),
            'switcher_button_font_size' => array(
                'name' => __('Font Size', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 15px',
                'id' => 'design[switcher_button][font-size]',
                'default' => isset($design['switcher_button']['font-size']) ? $design['switcher_button']['font-size'] : '',
                'placeholder' => '15px',
                'class' => 'eccw-input'
            ),
            'switcher_button_padding' => array(
                'name' => __('Padding', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 2px 2px 2px 2px',
                'default' => isset($design['switcher_button']['null']) ? $design['switcher_button']['null'] : '',
                'class' => 'eccw-currency-switcher-button-bg eccw-dimension-input', 
                'custom_attributes' => array(
                    'unit' => 'px',
                    'fields' => wp_json_encode(
                        array(
                            array(
                                'type' => 'text',
                                'name' => 'design[switcher_button][padding-top]',
                                'value' => isset($design['switcher_button']['padding-top']) ? $design['switcher_button']['padding-top'] : '',
                                'placeholder' => 'top'
                            ),
                            array(
                                'type' => 'text',
                                'name' => 'design[switcher_button][padding-left]',
                                'value' => isset($design['switcher_button']['padding-left']) ? $design['switcher_button']['padding-left'] : '',
                                'placeholder' => 'left'
                            ),
                            array(
                                'type' => 'text',
                                'name' => 'design[switcher_button][padding-bottom]',
                                'value' => isset($design['switcher_button']['padding-bottom']) ? $design['switcher_button']['padding-bottom'] : '',
                                'placeholder' => 'bottom'
                            ),
                            array(
                                'type' => 'text',
                                'name' => 'design[switcher_button][padding-right]',
                                'value' => isset($design['switcher_button']['padding-right']) ? $design['switcher_button']['padding-right'] : '',
                                'placeholder' => 'right'
                            ),
                        )
                    ),
                )
            ),
            'switcher_button_margin' => array(
                'name' => __('margin', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 2px 2px 2px 2px',
                'default' => isset($design['switcher_dropdown']['null']) ? $design['switcher_dropdown']['null'] : '',
                'class' => 'eccw-currency-switcher-dropdown-bg eccw-dimension-input', 
                'custom_attributes' => array(
                    'unit' => 'px',
                    'fields' => wp_json_encode(
                        array(
                            array(
                                'type' => 'text',
                                'name' => 'design[switcher_button][margin-top]',
                                'value' => isset($design['switcher_button']['margin-top']) ? $design['switcher_button']['margin-top'] : '',
                                'placeholder' => 'top'
                            ),
                            array(
                                'type' => 'text',
                                'name' => 'design[switcher_button][margin-left]',
                                'value' => isset($design['switcher_button']['margin-left']) ? $design['switcher_button']['margin-left'] : '',
                                'placeholder' => 'left'
                            ),
                            array(
                                'type' => 'text',
                                'name' => 'design[switcher_button][margin-bottom]',
                                'value' => isset($design['switcher_button']['margin-bottom']) ? $design['switcher_button']['margin-bottom'] : '',
                                'placeholder' => 'bottom'
                            ),
                            array(
                                'type' => 'text',
                                'name' => 'design[switcher_button][margin-right]',
                                'value' => isset($design['switcher_button']['margin-right']) ? $design['switcher_button']['margin-right'] : '',
                                'placeholder' => 'right'
                            ),
                        )
                    ),
                )
            ),
            'section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_settings_tab_section_end'
            )
        );


        $switcher_button_style_end = array(
            array(
                'type' => 'html',
                'html' => '</div>'
            )
        );
        $switcher_elements_style_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-style-section eccw-elements-style-display">'
            )
        );

        $settings_dropdown = array(
            'eccw_dropdown_settings_tab_section_title' => array(
                'name' => __('Switcher Dropdown Wrapper Style', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_dropdown_settings_tab_section_title'
            ),
            'switcher_dropdown_width' => array(
                'name' => __('Width', 'easy-currency'),
                'type' => 'number',
                'id' => 'design[switcher_dropdown][width]',
                'default' => isset($design['switcher_dropdown']['width']) ? str_replace('px', '', $design['switcher_dropdown']['width']) : '218',
                'class' => 'eccw-currency-switcher-dropdown-width eccw-rang-input', 
                'custom_attributes' => array(
                    'unit' => 'px',
                )
            ),
            'switcher_dropdown_bg' => array(
                'name' => __('Background Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown][background]',
                'default' => isset($design['switcher_dropdown']['background']) ? $design['switcher_dropdown']['background'] : 'transparent',
                'class' => 'eccw-color-input ', 
            ),
            'switcher_dropdown_border_color' => array(
                'name' => 'Border Control',
                'type'  => 'eccw_border_control',
                'id' => 'design[switcher_dropdown_border_control]',
                'default' => isset($design['switcher_dropdown_border_control']) ? $design['switcher_dropdown_border_control'] : '',
                'desc'  => 'Set border width for each side, style and color.'
            ),
            'switcher_dropdown_border_radius' => array(
                'name' => __('Border Radius', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 5px',
                'id' => 'design[switcher_dropdown][border-radius]',
                'default' => isset($design['switcher_dropdown']['border-radius']) ? $design['switcher_dropdown']['border-radius'] : '',
                'placeholder' => '5px',
                'class' => 'eccw-input ', 
            ),
            'switcher_dropdown_padding' => array(
                'name' => __('Padding', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 2px 2px 2px 2px',
                'default' => isset($design['switcher_dropdown']['null']) ? $design['switcher_dropdown']['null'] : '',
                'class' => 'eccw-currency-switcher-dropdown-bg eccw-dimension-input', 
                'custom_attributes' => array(
                    'unit' => 'px',
                    'fields' => wp_json_encode(
                        array(
                            array(
                                'type' => 'text',
                                'name' => 'design[switcher_dropdown][padding-top]',
                                'value' => isset($design['switcher_dropdown']['padding-top']) ? $design['switcher_dropdown']['padding-top'] : '',
                                'placeholder' => 'top'
                            ),
                            array(
                                'type' => 'text',
                                'name' => 'design[switcher_dropdown][padding-left]',
                                'value' => isset($design['switcher_dropdown']['padding-left']) ? $design['switcher_dropdown']['padding-left'] : '',
                                'placeholder' => 'left'
                            ),
                            array(
                                'type' => 'text',
                                'name' => 'design[switcher_dropdown][padding-bottom]',
                                'value' => isset($design['switcher_dropdown']['padding-bottom']) ? $design['switcher_dropdown']['padding-bottom'] : '',
                                'placeholder' => 'bottom'
                            ),
                            array(
                                'type' => 'text',
                                'name' => 'design[switcher_dropdown][padding-right]',
                                'value' => isset($design['switcher_dropdown']['padding-right']) ? $design['switcher_dropdown']['padding-right'] : '',
                                'placeholder' => 'right'
                            ),
                        )
                    ),
                )
            ),
            
            'eccw_dropdown_settings_tab_section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_dropdown_settings_tab_section_end'
            )
        );
        $switcher_elements_style_end = array(
            array(
                'type' => 'html',
                'html' => '</div>'
            )
        );

        $switcher_dropdown_ele_style_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-style-section eccw-dropdown-elements-style-display">'
            )
        );

        $settings_dropdown_option = array(
            'eccw_dropdown_option_settings_tab_section_title' => array(
                'name' => __('Switcher Dropdown Item Style', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_dropdown_option_settings_tab_section_title'
            ),
            'switcher_dropdown_option_alignment' => array(
                'name'   => 'Alignment',
                'id' => 'design[switcher_dropdown_option][justify-content]',
                'type'    => 'tabswitch',
                'default' => isset($design['switcher_dropdown_option']['justify-content']) ? $design['switcher_dropdown_option']['justify-content'] : 'center',
                'options' => array(
                    'left'   => 'Left',
                    'center' => 'Center',
                    'right'  => 'Right'
                )
            ),
            'switcher_dropdown_option_bg' => array(
                'name' => __('Background Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown_option][background]',
                'default' => isset($design['switcher_dropdown_option']['background']) ? $design['switcher_dropdown_option']['background'] : '',
                'class' => 'eccw-color-input ', 
            ),
            'switcher_dropdown_option_hover_bg' => array(
                'name' => __('Hover Background Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown_option_hover][background]',
                'default' => isset($design['switcher_dropdown_option_hover']['background']) ? $design['switcher_dropdown_option_hover']['background'] : '',
                'class' => 'eccw-color-input ', 
            ),
            'switcher_dropdown_option_color' => array(
                'name' => __('Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown_option][color]',
                'default' => isset($design['switcher_dropdown_option']['color']) ? $design['switcher_dropdown_option']['color'] : '',
                'class' => 'eccw-color-input ', 
            ),
            'switcher_dropdown_option_hover_color' => array(
                'name' => __('Hover Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown_option_hover][color]',
                'default' => isset($design['switcher_dropdown_option_hover']['color']) ? $design['switcher_dropdown_option_hover']['color'] : '',
                'class' => 'eccw-color-input ', 
            ),
            'switcher_dropdown_borderoption_style' => array(
                'name' => 'Border Control',
                'type'  => 'eccw_border_control',
                'id' => 'design[switcher_dropdown_border_style_option_control]',
                'default' => isset($design['switcher_dropdown_border_style_option_control']) ? $design['switcher_dropdown_border_style_option_control'] : '',
                'desc'  => 'Set border width for each side, style and color.'
            ),
            'switcher_dropdown_option_hover_border_color' => array(
                'name' => __('Hover Border Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown_option_hover][border-color]',
                'default' => isset($design['switcher_dropdown_option_hover']['border-color']) ? $design['switcher_dropdown_option_hover']['border-color'] : '',
                'class' => 'eccw-color-input ', 
            ),
            'switcher_dropdown_option_border_radius' => array(
                'name' => __('Border Radius', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 5px',
                'id' => 'design[switcher_dropdown_option][border-radius]',
                'default' => isset($design['switcher_dropdown_option']['border-radius']) ? $design['switcher_dropdown_option']['border-radius'] : '',
                'placeholder' => '5px',
                'class' => 'eccw-input ', 
            ),
            'switcher_dropdown_option_font_size' => array(
                'name' => __('Font Size', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 15px',
                'id' => 'design[switcher_dropdown_option][font-size]',
                'default' => isset($design['switcher_dropdown_option']['font-size']) ? $design['switcher_dropdown_option']['font-size'] : '',
                'placeholder' => '15px',
                'class' => 'eccw-input'
            ),
            'switcher_dropdown_option_padding' => array(
                'name' => __('Padding', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 2px 2px 2px 2px',
                'default' => isset($design['switcher_dropdown_option']['null']) ? $design['switcher_dropdown_option']['null'] : '',
                'class' => 'eccw-currency-switcher-dropdown_option-bg eccw-dimension-input', 
                'custom_attributes' => array(
                    'unit' => 'px',
                    'fields' => wp_json_encode(
                        array(
                            array(
                                'type' => 'text',
                                'name' => 'design[switcher_dropdown_option][padding-top]',
                                'value' => isset($design['switcher_dropdown_option']['padding-top']) ? $design['switcher_dropdown_option']['padding-top'] : '',
                                'placeholder' => 'top'
                            ),
                            array(
                                'type' => 'text',
                                'name' => 'design[switcher_dropdown_option][padding-left]',
                                'value' => isset($design['switcher_dropdown_option']['padding-left']) ? $design['switcher_dropdown_option']['padding-left'] : '',
                                'placeholder' => 'left'
                            ),
                            array(
                                'type' => 'text',
                                'name' => 'design[switcher_dropdown_option][padding-bottom]',
                                'value' => isset($design['switcher_dropdown_option']['padding-bottom']) ? $design['switcher_dropdown_option']['padding-bottom'] : '',
                                'placeholder' => 'bottom'
                            ),
                            array(
                                'type' => 'text',
                                'name' => 'design[switcher_dropdown_option][padding-right]',
                                'value' => isset($design['switcher_dropdown_option']['padding-right']) ? $design['switcher_dropdown_option']['padding-right'] : '',
                                'placeholder' => 'right'
                            ),
                        )
                    ),
                )
            ),
            'eccw_dropdown_option_settings_tab_section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_dropdown_option_settings_tab_section_end'
            )
        );

        $switcher_dropdown_ele_style_end = array(
            array(
                'type' => 'html',
                'html' => '</div>'
            )
        );

        $switcher_flag_style_wrapper_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-style-section eccw-flag-style-display">'
            )
        );

        $flag_style = array(
            'eccw_flag_style_title' => array(
                'name' => __('Switcher Flag Style', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_switcher_flag_section_title'
            ),
            'switcher_dropdown_option_flag_size' => array(
                'name' => __('Flag Size (Width)', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 35px',
                'id' => 'design[switcher_option_flag][width]',
                'default' => isset($design['switcher_option_flag']['width']) ? $design['switcher_option_flag']['width'] : '30px',
                'placeholder' => '35px',
                'class' => 'eccw-input'
            ),
            'eccw_flag_tab_section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_flag_tab_section_end'
            )
        );

        $switcher_flag_style_wrapper_end = array(
            array(
                'type' => 'html',
                'html' => '</div>'
            )
        );

        $all_settings = array_merge( $switcher_layout_style_start,$layout_style, $switcher_layout_style_end, $switcher_button_style_start, $settings, $switcher_button_style_end, $switcher_elements_style_start, $settings_dropdown, $switcher_elements_style_end, $switcher_dropdown_ele_style_start, $settings_dropdown_option, $switcher_dropdown_ele_style_end, $switcher_flag_style_wrapper_start, $flag_style, $switcher_flag_style_wrapper_end);


        return $all_settings;
    }

    public function eccw_create_switcher_shortcode_popup_field()
    {
        
        $switcher_layout_style_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-style-section eccw-layout-style-display">'
            )
        );
        $layout_style = array(
            'section_title_layout_style' => array(
                'name' => '',
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_settings_tab_layout_section_title'
            ),
            'eccw_switcher_name' => array(
                'name' => __('Switcher Name', 'easy-currency'),
                'type' => 'text',
                'id' => 'eccw_switcher_name_field',
                'placeholder' => 'Switcher Name',
                'class' => 'eccw-input eccw-switcher-ui-control'
            ),
            'switcher_template' => array(
                'name'     => __('Template', 'easy-currency'),
                'type'     => 'template_preview',
                'id'       => 'design[switcher_dropdown_option][template]',
                'default'  => 'eccw_template_1',
                'value'    => 'eccw_template_1',
                'desc'     => __('Choose your currency switcher template', 'easy-currency'),
                'templates' => array(
                    'eccw_template_1' => ECCW_PL_URL . 'admin/assets/img/eccw-template-1.png',
                    'eccw_template_2' => ECCW_PL_URL . 'admin/assets/img/eccw-template-2.png',
                ),
                'class' => 'eccw-switcher-ui-control'
            ),
            'layout_section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_settings_tab_layout_section_end'
            )
        );
        $switcher_layout_style_end = array(
            array(
                'type' => 'html',
                'html' => '</div>'
            )
        );

        $all_settings = array_merge($switcher_layout_style_start, $layout_style, $switcher_layout_style_end);


        return $all_settings;
    }

    public function get_eccw_settings_modal_switcher_display_option_fields($current_shortcodeId = null)
    {
        // Retrieve saved settings
        $saved_settings = get_option('eccw_switcher_styles');


        $design = isset($saved_settings[$current_shortcodeId]) ? $saved_settings[$current_shortcodeId] : [];

        $switcher_elements_display_wrapper_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-section eccw-elements-display">'
            )
        );
        $switcher_elements_display = array(

            'eccw_elements_style_title' => array(
                'name' => __('Display Switcher', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_switcher_elements_settings'
            ),
            'flag_visibility' => array(
                'title' => __('Enable Flag', 'easy-currency'),
                'id'    => 'design[eccw_switcher_flag_show_hide]',
                'type'  => 'switcher',
                'default' => isset($design['eccw_switcher_flag_show_hide']) ? $design['eccw_switcher_flag_show_hide'] : 'yes',
                'class' => 'eccw-switcher-ui-control',
            ),
            'eccw_switcher_ele_currency_name' => array(
                'title' => __('Enable Currency Name', 'easy-currency'),
                'id'    => 'design[eccw_switcher_currency_name_show_hide]',
                'type'  => 'switcher',
                'default' => isset($design['eccw_switcher_currency_name_show_hide']) ? $design['eccw_switcher_currency_name_show_hide'] : '',
                'class' => 'eccw-switcher-ui-control',
            ),
            'eccw_switcher_ele_currency_symbol' => array(
                'title' => __('Enable Currency Symbol', 'easy-currency'),
                'id'    => 'design[eccw_switcher_currency_symbol_show_hide]',
                'type'  => 'switcher',
                'default' => isset($design['eccw_switcher_currency_symbol_show_hide']) ? $design['eccw_switcher_currency_symbol_show_hide'] : 'yes',
                'class' => 'eccw-switcher-ui-control',
            ),
            'eccw_switcher_ele_currency_code' => array(
                'title' => __('Enable Currency Code', 'easy-currency'),
                'id'    => 'design[eccw_switcher_currency_code_show_hide]',
                'type'  => 'switcher',
                'default' => isset($design['eccw_switcher_currency_code_show_hide']) ? $design['eccw_switcher_currency_code_show_hide'] : 'yes',
                'class' => 'eccw-switcher-ui-control',
            ),
            'eccw_selements_tab_section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_switcher_elements_tab_section_end'
            )
        );

        $switcher_elements_display_wrapper_end = array(
            array(
                'type' => 'html',
                'html' => '</div>'
            )
        );

        $all_settings = array_merge($switcher_elements_display_wrapper_start, $switcher_elements_display, $switcher_elements_display_wrapper_end);

        return $all_settings;
    }

    public function get_eccw_settings_modal_custom_css_func($current_shortcodeId = null) {
        
        $saved_settings = get_option('eccw_switcher_styles');
        $design = isset($saved_settings[$current_shortcodeId]) ? $saved_settings[$current_shortcodeId] : [];

       
        $settings = apply_filters("after_eccw_modal_tab_custom_css", []);

        if (empty($settings)) {
            $settings = array(
                array(
                    'type' => 'html',
                    'html' => '
                    <div class="eccw-pro-required-msg">
                        <div class="eccw-pro-icon">⚠️</div>
                        <div class="eccw-pro-content">
                            <h3>' . __('Pro Feature Required', 'easy-currency') . '</h3>
                            <p>' . __('This feature is available in the Pro version. Please upgrade to unlock custom CSS styling options for your switcher.', 'easy-currency') . '</p>
                            <a href="https://easy-currency.themewant.com/" target="_blank" class="eccw-pro-btn">' . __('Upgrade to Pro', 'easy-currency') . '</a>
                        </div>
                    </div>'
                )
            );
        }

        return $settings;
    }

    public function eccw_switcher_sticky_field()
    {

        $saved_settings = get_option('eccw_currency_settings');

        $design = isset($saved_settings['design']) ? $saved_settings['design'] : [];

        $switcher_sticky_layout_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-sticky-layout">'
            )
        );
        $sticky_fields = array(
            'section_sticky_title_layout_style' => array(
                'name' => 'Display Side Switcher',
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_settings_sticky_layout_section_title'
            ),
            'eccw_enable_disable_side_currency' => array(
                'title' => __('Show / Hide', 'easy-currency'),
                'id'    => 'design[eccw_show_hide_side_currency]',
                'type'  => 'switcher',
                'default' => isset($design['eccw_show_hide_side_currency']) ? $design['eccw_show_hide_side_currency'] : 'no',
                'class' => 'eccw-switcher-ui-control-show-hide',
            ),
            'switcher_sticky_template' => array(
                'name'     => __('Template', 'easy-currency'),
                'type'     => 'template_preview',
                'id'       => 'design[switcher_sticky][template]',
                'default'  => isset($design['switcher_sticky']['template']) ? $design['switcher_sticky']['template'] : 'eccw_sticky_template_1',
                'value'    => isset($design['switcher_sticky']['template']) ? $design['switcher_sticky']['template'] : 'eccw_sticky_template_1',
                'desc'     => __('Choose your currency switcher template', 'easy-currency'),
                'templates' => array(
                    'eccw_sticky_template_1' => ECCW_PL_URL . 'admin/assets/img/eccw-template-3.png',
                    'eccw_sticky_template_2' => ECCW_PL_URL . 'admin/assets/img/eccw-template-4.png',
                    'eccw_sticky_template_3' => ECCW_PL_URL . 'admin/assets/img/eccw-template-5.png',
                ),
                'class' => 'eccw-switcher-ui-control',
            ),
            'sticky_show_on_pages' => array(
                'name'    => __('Show On Pages', 'easy-currency'),
                'type'    => 'select2',
                'id'      => 'eccw_sticky_show_on_pages',
                'default'  => isset($design['eccw_sticky_show_on_pages']) ? $design['eccw_sticky_show_on_pages'] : '',
                'value'    => isset($design['eccw_sticky_show_on_pages']) ? $design['eccw_sticky_show_on_pages'] : '',
                'options' => eccw_get_pages_list_for_select(),
                'desc'    => __('Select the pages where the currency switcher will be shown.', 'easy-currency'),
                'class'   => 'eccw-sticky-select2',
                'custom_attributes' => array(
                    'multiple' => 'multiple'
                ),
                'field_name' => 'design[eccw_sticky_show_on_pages][]'
            ),



            'eccw_sitcky_section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_settings_sticky_layout_section_end'
            )
        );
        $switcher_sticky_layout_end = array(
            array(
                'type' => 'html',
                'html' => '</div>'
            )
        );

        $switcher_sticky_display_wrapper_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-section eccw-sticky-elements-display">'
            )
        );
        $switcher_sticky_show_hide_display = array(

            'eccw_sticky_display_style_title' => array(
                'name' => __('Display Switcher', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_switcher_elements_settings'
            ),
            'eccw_sticky_flag_visibility' => array(
                'title' => __('Enable Flag', 'easy-currency'),
                'id'    => 'design[eccw_sticky_flag_show_hide]',
                'type'  => 'switcher',
                'default' => isset($design['eccw_sticky_flag_show_hide']) ? $design['eccw_sticky_flag_show_hide'] : 'yes',
                'class' => 'eccw-switcher-ui-control',
            ),
            'eccw_sticky_ele_currency_name' => array(
                'title' => __('Enable Currency Name', 'easy-currency'),
                'id'    => 'design[eccw_sticky_currency_name_show_hide]',
                'type'  => 'switcher',
                'default' => isset($design['eccw_sticky_currency_name_show_hide']) ? $design['eccw_sticky_currency_name_show_hide'] : 'no',
                'class' => 'eccw-switcher-ui-control',
            ),
            'eccw_sticky_ele_currency_symbol' => array(
                'title' => __('Enable Currency Symbol', 'easy-currency'),
                'id'    => 'design[eccw_switcher_currency_symbol_show_hide]',
                'type'  => 'switcher',
                'default' => isset($design['eccw_sticky_ele_currency_symbol']) ? $design['eccw_sticky_ele_currency_symbol'] : 'yes',
                'class' => 'eccw-switcher-ui-control',
            ),
            'eccw_sticky_ele_currency_code' => array(
                'title' => __('Enable Currency Code', 'easy-currency'),
                'id'    => 'design[eccw_sticky_currency_code_show_hide]',
                'type'  => 'switcher',
                'default' => isset($design['eccw_sticky_currency_code_show_hide']) ? $design['eccw_sticky_currency_code_show_hide'] : 'yes',
                'class' => 'eccw-switcher-ui-control',
            ),
            'eccw_sticky_show_hide_tab_section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_sticky_show_hide_tab_section_end'
            )
        );

        $switcher_sticky_display_wrapper_end = array(
            array(
                'type' => 'html',
                'html' => '</div>'
            )
        );


        $switcher_position_wrapper_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-section eccw-position-settings">'
            )
        );

        $switcher_position = array(
            'eccw_position_settings_title' => array(
                'title' => __('Custom Position Settings', 'easy-currency'),
                'type'  => 'title',
                'desc'  => __('Configure the custom position fields for the side currency', 'easy-currency'),
                'id'    => 'custom_position_options',
            ),
            'eccw_elemets_toggle_position' => array(
                'name'   => 'Switcher Position',
                'id'      =>  'design[eccw_position_alignment_toggle]',
                'type'    => 'tabswitch',
                'default' => isset($design['eccw_position_alignment_toggle']) ? $design['eccw_position_alignment_toggle'] : 'left',
                'options' => array(
                    'left'  => 'Left',
                    'right' => 'Right'
                ),
                'desc'    => 'Choose Left or Right alignment.',
                'class' => 'eccw-switcher-ui-control',
            ),

            'vertical' => array(
                'name'   => __('Vertical (%)', 'easy-currency'),
                'id'    => 'design[eccw_sticky_vertical]',
                'type'    => 'eccw_slider',
                'min'     => 0,
                'max'     => 100,
                'step'    => 1,
                'default' => isset($design['eccw_sticky_vertical']) ? $design['eccw_sticky_vertical'] : '',
                'desc'    => __('Set the vertical position in percentage.', 'easy-currency'),
                'class' => 'eccw-switcher-ui-control',
            ),

            'horizontal' => array(
                'name'   => __('Horizontal (PX)', 'easy-currency'),
                'id'    => 'design[eccw_sticky_horizontal]',
                'type'    => 'eccw_slider',
                'min'     => -500,
                'max'     => 500,
                'step'    => 1,
                'default' => isset($design['eccw_sticky_horizontal']) ? $design['eccw_sticky_horizontal'] : '',
                'desc'    => __('Set the horizontal position in pixels.', 'easy-currency'),
                'class' => 'eccw-switcher-ui-control',
            ),

            'horizontal_hover' => array(
                'name'   => __('Horizontal Hover (PX)', 'easy-currency'),
                'id'    => 'design[eccw_sticky_horizontal_hover]',
                'type'    => 'eccw_slider',
                'min'     => -500,
                'max'     => 500,
                'step'    => 1,
                'default' => isset($design['eccw_sticky_horizontal_hover']) ? $design['eccw_sticky_horizontal_hover'] : '',
                'desc'    => __('Set the horizontal position on hover in pixels.', 'easy-currency'),
                'class' => 'eccw-switcher-ui-control',
            ),

            'item_move_horizontal' => array(
                'name'   => __('Item Move Horizontal (PX)', 'easy-currency'),
                'id'    => 'design[eccw_sticky_item_move_horizontal]',
                'type'    => 'eccw_slider',
                'min'     => -1000,
                'max'     => 1000,
                'step'    => 1,
                'default' => isset($design['eccw_sticky_item_move_horizontal']) ? $design['eccw_sticky_item_move_horizontal'] : '',
                'desc'    => __('Set the horizontal movement of the item in pixels.', 'easy-currency'),
                'desc_tip' => '',
                'class' => 'eccw-switcher-ui-control',
            ),

            'eccw_switcher_position_tab_section_end' => array(
                'type' => 'sectionend',
                'id'   => 'eccw_switcher_position_sec_tab_section_end'
            ),

        );
        $switcher_position_wrapper_end = array(
            array(
                'type' => 'html',
                'html' => '</div>'
            )
        );

        $sticky_color_style_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-style-section eccw-sticky-color-style-display">'
            )
        );

        $sticky_color_settings = array(
            'eccw_sitcky_color_section_title' => array(
                'name' => __('Sticky Global Settings', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_color_settings_tab_section_title'
            ),
            'eccw_sticky_color_bg' => array(
                'name' => __('Background Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[sticky_option_color][background]',
                'default' => isset($design['sticky_option_color']['background']) ? $design['sticky_option_color']['background'] : '#EFEFEF',
                'class' => 'eccw-color-input ', 
            ),
            'eccw_sticky_color' => array(
                'name' => __('Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[sticky_option_color][color]',
                'default' => isset($design['sticky_option_color']['color']) ? $design['sticky_option_color']['color'] : '#000',
                'class' => 'eccw-color-input ', 
            ),
            'eccw_sticky_color_hover_bg' => array(
                'name' => __('Hover Background Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[sticky_option_hover][background]',
                'default' => isset($design['sticky_option_hover']['background']) ? $design['sticky_option_hover']['background'] : '#000',
                'class' => 'eccw-color-input ', 
            ),
            'eccw_sticky_hover_color' => array(
                'name' => __('Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[sticky_option_hover][color]',
                'default' => isset($design['sticky_option_hover']['color']) ? $design['sticky_option_hover']['color'] : '#fff',
                'class' => 'eccw-color-input ', 
            ),
            'switcher_sticky_option_flag_size' => array(
                'name' => __('Flag Size (Width)', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 15px',
                'id' => 'design[switcher_sticky_option_flag][width]',
                'default' => isset($design['switcher_sticky_option_flag']['width']) ? $design['switcher_sticky_option_flag']['width'] : '',
                'placeholder' => '15px',
                'class' => 'eccw-input'
            ),
            'eccw_color_sticky_section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_sticky_color_tab_section_end'
            )
        );
        $sticky_color_style_end = array(
            array(
                'type' => 'html',
                'html' => '</div>'
            )
        );

        $sticky_ccode_color_style_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-style-section eccw-sticky-ccode-color-style-display">'
            )
        );

        $sticky_ccode_color_settings = array(
            'eccw_sitcky_ccode_color_section_title' => array(
                'name' => __('Country Code Color', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_color_ccode_settings_title'
            ),
            'eccw_sticky_ccode_color_bg' => array(
                'name' => __('Background Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[sticky_option_ccode_color][background]',
                'default' => isset($design['sticky_option_ccode_color']['background']) ? $design['sticky_option_ccode_color']['background'] : '#EFEFEF',
                'class' => 'eccw-color-input ', 
            ),
            'eccw_sticky_ccode_color' => array(
                'name' => __('Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[sticky_option_ccode_color][color]',
                'default' => isset($design['sticky_option_ccode_color']['color']) ? $design['sticky_option_ccode_color']['color'] : '#000',
                'class' => 'eccw-color-input ', 
            ),
            'eccw_sticky_color_ccode_hover_bg' => array(
                'name' => __('Hover Background Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[sticky_option_ccode_hover][background]',
                'default' => isset($design['sticky_option_ccode_hover']['background']) ? $design['sticky_option_ccode_hover']['background'] : '#000',
                'class' => 'eccw-color-input ', 
            ),
            'eccw_sticky_ccode_hover_color' => array(
                'name' => __('Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[sticky_option_ccode_hover][color]',
                'default' => isset($design['sticky_option_ccode_hover']['color']) ? $design['sticky_option_ccode_hover']['color'] : '#fff',
                'class' => 'eccw-color-input ', 
            ),
            'eccw_color_ccode_sticky_section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_sticky_ccode_color_tab_section_end'
            )
        );

        $sticky_ccode_color_style_end = array(
            array(
                'type' => 'html',
                'html' => '</div>'
            )
        );

        $all_settings = array_merge($switcher_sticky_layout_start, $sticky_fields, $switcher_sticky_layout_end, $switcher_sticky_display_wrapper_start, $switcher_sticky_show_hide_display, $switcher_sticky_display_wrapper_end, $switcher_position_wrapper_start, $switcher_position, $switcher_position_wrapper_end,  $sticky_color_style_start,  $sticky_color_settings, $sticky_color_style_end,$sticky_ccode_color_style_start, $sticky_ccode_color_settings, $sticky_ccode_color_style_end );


        return $all_settings;
    }

    function eccw_pro_multi_get_country_by_currency() {

        $settings = get_option('eccw_currency_settings', []);

        $currency_countries = wp_remote_get(ECCW_DIR_URL . '/admin/assets/json/currency-countries.json', []);
        $currency_countries_json = json_decode($currency_countries['body'] ?? '', true) ?? [];


        $all_countries = WC()->countries->get_countries(); 

        $eccw_currency_settings = get_option('eccw_currency_settings', []);
        $pro_class = 'easy-currency-pro-feature';
        $pro_enabled = false;
        if( class_exists( 'ECCW_CURRENCY_SWITCHER_PRO' ) ) {
            $pro_class = '';
            $pro_enabled = true;
        }
       
        if (!empty($eccw_currency_settings) && isset($eccw_currency_settings['eccw_currency_table']) && count($eccw_currency_settings['eccw_currency_table']) > 0) {
            $eccw_currency_table = $eccw_currency_settings['eccw_currency_table'];

            ?>
            <div class="eccw-geo-country-table-list <?php echo esc_attr( $pro_class ); ?>">
                
                <table class="widefat striped eccw-geo-country-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Currency', 'easy-currency'); ?></th>
                            <th><?php esc_html_e('Countries', 'easy-currency'); ?></th>
                            <th><?php esc_html_e('Actions', 'easy-currency'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    foreach ($eccw_currency_table as $index => $currency_data ): 

                        $currency_code   = isset($currency_data['code']) ? $currency_data['code'] : '';
                        $currency_symbol = isset($currency_data['symbol']) ? $currency_data['symbol'] : '';
                        $currencies      = get_woocommerce_currencies(); 
                        $currency_name   = isset($currencies[$currency_code]) ? $currencies[$currency_code] : $currency_code;

                        $geo_data = get_option('eccw_currency_table_geo', []); 
                        $geo_array = isset($geo_data[$index]) ? $geo_data[$index] : [];

                        $selected_countries = isset($geo_array['countries'][$currency_code]) ? $geo_array['countries'][$currency_code] : [];

                        $countrycode =  isset( $currency_countries_json[$currency_code]['countries'][0] ) ? $currency_countries_json[$currency_code]['countries'][0] : '';

                        $country_shortname = isset( $all_countries[$countrycode] ) ? $all_countries[$countrycode] : '';

                        ?>
                        <tr>
                            <td>
                                <?php echo esc_html($currency_code); ?> - 
                                <?php echo esc_html($currency_name); ?>
                            </td>
                            <td>
                                <select name="eccw_currency_table_geo[<?php echo $index; ?>][countries][<?php echo $currency_code; ?>][]"
                                        class="eccw-searchable-country-select <?php echo !$pro_enabled ? 'pro-disabled' : ''; ?>"
                                        multiple="multiple"
                                        data-placeholder="<?php esc_attr_e('Please Select countries...', 'easy-currency'); ?>"
                                        style="width:100%;" data-eccwgeo_deault_country_code="<?php echo esc_attr( $countrycode); ?>" data-eccwgeo_deault_country_name="<?php echo esc_attr( $country_shortname); ?>">
                                    <?php 

                                    $countries = WC()->countries->get_countries();

                                    foreach ( $all_countries as $currency_codeparent => $currency_data) {
                                        ?>
                                        <option value="<?php echo esc_attr($currency_codeparent); ?>" 
                                            <?php selected(in_array($currency_codeparent, (array)$selected_countries)); ?>>
                                            <?php echo esc_html($currency_data); ?>
                                        </option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </td>
                            <td>
                                <button type="button" class="button select-all-countries"><?php esc_html_e('Select all', 'easy-currency'); ?></button>
                                <button type="button" class="button button-danger remove-all-countries"><?php esc_html_e('Remove All', 'easy-currency'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    </tbody>
                   
                </table>
                <div class="eccw-table-footer-actions-geo-country">
                    <button type="button" class="button button-primary apply-default-countries">
                        <?php esc_html_e('Apply Default Country', 'easy-currency'); ?>
                    </button>
                </div>
                <p class="eccw-currecny-wise-payment-desc">
                    Note: Based on Geo IP rules, the currency will automatically convert according to the visitor’s country.  Make sure WooCommerce and Auto select currency are enabled for this feature to work properly.a
                </p>


            </div>
            <?php
            
        }
    }

    function eccw_currency_select_in_different_payment() {

        $settings = get_option('eccw_currency_settings', []);

        $eccw_currency_settings = get_option('eccw_currency_settings', []);
        $pro_class = 'easy-currency-pro-feature';
        $pro_enabled = false;
        if( class_exists( 'ECCW_CURRENCY_SWITCHER_PRO' ) ) {
            $pro_class = '';
            $pro_enabled = true;
        }

        if (!empty($eccw_currency_settings) && isset($eccw_currency_settings['eccw_currency_table']) && count($eccw_currency_settings['eccw_currency_table']) > 0) {
            $eccw_currency_table = $eccw_currency_settings['eccw_currency_table'];

            // Get saved payment methods & status
            $payment_data = get_option('eccw_currency_payment_select', []);
            $payment_status_data = get_option('eccw_currency_payment_status', []);

            ?>
            <div class="eccw-currency-payment-table-list <?php echo esc_attr( $pro_class ); ?>">
                <table class="widefat striped eccw-currency-payment-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Currency', 'easy-currency'); ?></th>
                            <th><?php esc_html_e('Payment Methods', 'easy-currency'); ?></th>
                            <th><?php esc_html_e('Status', 'easy-currency'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    foreach ($eccw_currency_table as $index => $currency_data ): 

                        $currency_code = isset($currency_data['code']) ? $currency_data['code'] : '';
                        $currencies = get_woocommerce_currencies(); 
                        $currency_name = isset($currencies[$currency_code]) ? $currencies[$currency_code] : $currency_code;

                        $selected_methods = isset($payment_data[$index]['payment'][$currency_code]) ? (array)$payment_data[$index]['payment'][$currency_code] : [];

                        $checked_status = isset($payment_status_data[$index][$currency_code]) && $payment_status_data[$index][$currency_code] ? 1 : 0;

                        ?>
                        <tr>
                            <td>
                                <?php echo esc_html($currency_code); ?> - 
                                <?php echo esc_html($currency_name); ?>
                            </td>
                            <td>
                                <select name="eccw_currency_payment_select[<?php echo $index; ?>][payment][<?php echo $currency_code; ?>][]"
                                        class="eccw-payment-method-select <?php echo !$pro_enabled ? 'pro-disabled' : ''; ?>"
                                        multiple="multiple"
                                        data-placeholder="<?php esc_attr_e('Please select payment methods...', 'easy-currency'); ?>"
                                        style="width:100%;">
                                    <?php 
                                    $all_gateways = WC()->payment_gateways()->get_available_payment_gateways();
                                    foreach ($all_gateways as $gateway_id => $gateway_obj) {
                                        ?>
                                        <option value="<?php echo esc_attr($gateway_id); ?>" 
                                            <?php selected( in_array($gateway_id, $selected_methods) ); ?>>
                                            <?php echo esc_html($gateway_obj->get_title()); ?>
                                        </option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </td>
                            <td>
                                <label class="eccw-toggle-switch">
                                    <input type="checkbox" 
                                        name="eccw_currency_payment_status[<?php echo $index; ?>][<?php echo $currency_code; ?>]" 
                                        value="1" <?php checked($checked_status); ?> />
                                    <span class="eccw-payment-toggle"></span>
                                </label>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="eccw-currecny-wise-payment-desc">Note: All enabled payment methods will appear in the dropdown, but only those supported in the customer’s country will be shown on the frontend.</p>
            </div>
            <?php
        }
    }

    function eccw_payment_gateway_rule_currency() {

        $eccw_currency_settings = get_option('eccw_currency_settings', []);
        $pro_class = 'easy-currency-pro-feature';
        $pro_enabled = false;
        if( class_exists( 'ECCW_CURRENCY_SWITCHER_PRO' ) ) {
            $pro_class = '';
            $pro_enabled = true;
        }

        if (!empty($eccw_currency_settings) && isset($eccw_currency_settings['eccw_currency_table']) && count($eccw_currency_settings['eccw_currency_table']) > 0) {
            $eccw_currency_table = $eccw_currency_settings['eccw_currency_table'];

            $code_currency_arr = [];
            foreach ($eccw_currency_table as $currency_data) {
                if (!empty($currency_data['code'])) {
                    $code_currency_arr[] = $currency_data['code'];
                }
            }
       
            // Get saved payment methods & status
            $payment_data = get_option('eccw_payment_gateway_select', []);

            $saved_gateways_currency = get_option('eccw_gateway_currency_set', []);

            $payment_status_data = get_option('eccw_currency_payment_status', []);


            ?>
            <div class="eccw-payment-rule-bycurrency-table-list <?php echo esc_attr( $pro_class ); ?>">
                <table class="widefat striped eccw-currency--set-payment-rule-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Payment Methods', 'easy-currency'); ?></th>
                            <th><?php esc_html_e('Set Currency', 'easy-currency'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    
                        $all_gateways = WC()->payment_gateways()->get_available_payment_gateways();
                        foreach ($all_gateways as $gateway_id => $gateway_obj) {

                        ?>
                        <tr>
                            <td>
                                <?php echo esc_html($gateway_obj->get_title()); ?>
                            </td>
                           
                            <td>
                                <select name="eccw_gateway_currency_set[<?php echo $gateway_id; ?>][]"
                                        class="eccw-payment-wise-currency-set-currency <?php echo !$pro_enabled ? 'pro-disabled' : ''; ?>"
                                        data-placeholder="<?php esc_attr_e('Please select currency...', 'easy-currency'); ?>">
                                        <?php 
                                         $saved_currencies = isset($saved_gateways_currency[$gateway_id]) 
                                            ? (array) $saved_gateways_currency[$gateway_id] 
                                            : [];
                                        ?>
                                       <option value="not_set" <?php echo empty($saved_currencies) ? 'selected' : ''; ?>>
                                            <?php echo esc_html__('Not Select', 'easy-currency'); ?>
                                        </option>
                                    <?php 
                                  
                                    foreach ( $code_currency_arr as $index => $currency_code ) {
                                       
                                        ?>
                                        <option value="<?php echo esc_attr($currency_code); ?>" 
                                            <?php selected( in_array($currency_code, $saved_currencies ) ); ?>>
                                            <?php echo esc_html( $currency_code ); ?>
                                        </option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <p>
                    <strong>Notice:</strong> When using the <em>WooCommerce Cart & Checkout Block</em>, 
                    the <span style="color: red;">payment method rules are not working</span>.
                </p>
            </div>
            <?php
        }
    }

    public function eccw_advanced_settings_field()
    {

        $saved_settings = get_option('eccw_currency_settings');

        $advanced_settings = isset($saved_settings['advanced_settings']) ? $saved_settings['advanced_settings'] : [];

        $update_settings_field_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-autu-update-settings">'
            )
        );

        $update_settings_fields = array(
            'eccw_update_section_title' => array(
                'name' => __('', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_advancedupdate_settings_title'
            ),
            'eccw_enable_auto_update_exchange_rate' => array(
                'title' => __('Enable Auto Update', 'easy-currency'),
                'id'    => 'advanced_settings[eccw_enable_auto_update]',
                'type'  => 'switcher',
                'default' => isset($advanced_settings['eccw_enable_auto_update']) ? $advanced_settings['eccw_enable_auto_update'] : '',
                'class' => 'eccw-switcher-ui-control-show-hide',
                'eccw_pro' => true,
                'desc_tip' => true,
                'description' => __('Enable this option auto update exchange rate', 'easy-currency'),
            ),
            'eccw_auto_update_interval' => array(
                'name' => __('Auto Update Interval', 'easy-currency'),
                'type' => 'select',
                'options' => [
                    '5_min' => 'Every 5 Minutes', 
                    '30_min' => 'Every 30 Minutes', 
                    '1_hour' => 'Every 1 hour', 
                    '1_day' => 'Every 1 Day', 
                    '2_day' => 'Every 2 Day', 
                    '3_days' => 'Every 3 Days', 
                    '4_days' => 'Every 4 Days', 
                    '5_days' => 'Every 5 Days', 
                    '1_week' => 'Every 1 Week'
                ],
                'desc' => __('This sets the interval of update exchange rate automation', 'easy-currency'),
                'id' => 'advanced_settings[eccw_auto_update_exchange_rate]',
                'default' => isset($advanced_settings['eccw_auto_update_exchange_rate']) ? $advanced_settings['eccw_auto_update_exchange_rate'] : '',
            ),
           
            'eccw_update_settings_section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_update_settings_advanced_section_end'
            )
        );

        $advanced_settings_field_end = array(
            array(
                'type' => 'html',
                'html' => '</div>'
            )
        );

        $advanced_settings_field_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-advanced-settings">'
            )
        );

        $advanced_settings_fields = array(
            'eccw_advanced_section_title' => array(
                'name' => __('', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_advanced_settings_title'
            ),
            'eccw_indivisual_geo_ip_rule' => array(
                'title' => __('Geo Ip rule for each product', 'easy-currency'),
                'id'    => 'advanced_settings[eccw_geo_ip_rule_each_product]',
                'type'  => 'switcher',
                'default' => isset($advanced_settings['eccw_geo_ip_rule_each_product']) ? $advanced_settings['eccw_geo_ip_rule_each_product'] : 'no',
                'class' => 'eccw-switcher-ui-control-show-hide',
                'eccw_pro' => true,
                'desc_tip' => true,
                'description' => __('Enable this option geo ip rule for each product', 'easy-currency'),
            ),
           
            'eccw_autodetect_currency_geo' => array(
                'title' => __('Auto select currency by countries Geo rule', 'easy-currency'),
                'id'    => 'advanced_settings[eccw_auto_select_currency_by_country]',
                'type'  => 'switcher',
                'default' => isset($advanced_settings['eccw_auto_select_currency_by_country']) ? $advanced_settings['eccw_auto_select_currency_by_country'] : 'no',
                'class' => 'eccw-switcher-ui-control-show-hide',
                'eccw_pro' => true,
                'desc_tip' => true,
                'description' => __('Enable this to automatically select the currency based on customer’s country (GeoIP).', 'easy-currency'),
            ),
           
            'eccw_advanced_settings_section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_advanced_settings_section_ends'
            )
        );
       
        $advanced_settings_field_end = array(
            array(
                'type' => 'html',
                'html' => '</div>'
            )
        );

        $custom_advanced_settings_field_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-custom-advanced-settings">'
            )
        );

        $custom_advanced_settings_fields = array(
            'eccw_custom_advanced_section_title' => array(
                'name' => __('Custom Currency Settings', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_advanced_settings_title_country'
            ),
            'eccw_advanced_curr_settings_section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_advanced_settings_custom_section_end',
                'html' => $this->eccw_pro_multi_get_country_by_currency(),
            )
        );
       
        $advanced_custom_curr_settings_field_end = array(
            array(
                'type' => 'html',
                'html' => '</div>'
            )
        );

        $all_settings = array_merge(  $update_settings_field_start, $update_settings_fields, $advanced_settings_field_end, $advanced_settings_field_start, $advanced_settings_fields, $advanced_settings_field_end,  $custom_advanced_settings_field_start, $custom_advanced_settings_fields, $advanced_custom_curr_settings_field_end );


       return $all_settings;

    }

    public function eccw_checkout_settings_field() {

        $saved_settings = get_option('eccw_currency_settings');

        $checkout_settings = isset($saved_settings['checkout_settings']) ? $saved_settings['checkout_settings'] : [];

        $pro_active = class_exists('ECCW_CURRENCY_SWITCHER_PRO');

        $pro_missing_class = $pro_active ? '' : __('easy-currency-pro-feature', 'easy-currency');

        $checkout_currency_enable_field_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-checkout-enable-settings">'
            )
        );
        $checkout_enable_settings_fields = array(
            'eccw_custom_advanced_en_section_title' => array(
                'name' => __('', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_advanced_en_settings_title_country'
            ),
            'eccw_checkout_in_different_payment_method' => array(
                'title' => __('Enable Currency Checkout', 'easy-currency'),
                'id'    => 'checkout_settings[eccw_checkout_currency]',
                'type'  => 'switcher',
                'default' => isset($checkout_settings['eccw_checkout_currency']) ? $checkout_settings['eccw_checkout_currency'] : '',
                'class' => 'eccw-switcher-ui-control-show-hide',
                'eccw_pro' => true,
                'desc_tip' => true,
                'description' => __('Enable this option Select in different payment method in currency', 'easy-currency'),
            ),
            'eccw_curr_payment_en_section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_currency_en_section_end',
            )
        );

        $checkout_currency_enable_field_end = array(
            array(
                'type' => 'html',
                'html' => '</div>'
            )
        );

        $checkout_currency_field_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-checkout-settings">'
            )
        );

        $checkout_settings_fields = array(
            'eccw_custom_advanced_section_title' => array(
                'name' => __('', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_advanced_settings_title_country'
            ),
            'eccw_curr_payment_section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_currency_payment_section_end',
                'html' => $this->eccw_currency_select_in_different_payment(),
            )
        );
       
        $checkout_currency_field_end = array(
            array(
                'type' => 'html',
                'html' => '</div>'
            )
        );

        $payment_rule_currency_field_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-currency-by-payment-rule ' . $pro_missing_class . '">'
            )
        );

        $protext = $pro_active ? '' : __('PRO', 'easy-currency');

        $payment_gateway_rule_settings_fields = array(
            'eccw_custom_payment_gateway_section_title' => array(
                'name' => __('Currency change by payment gateway rule', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_advanced_payment_gateway_title_country'
            ),
           'eccw_currency_change_mode_payment_gateway' => array(
                'name'    => __("Currency Change Mode ({$protext})", 'easy-currency'),
                'type'    => 'select',
                'options' => [
                    'place_order'          => 'On Place Order',       
                    'instant'              => 'Instant Change',     
                ],
                'desc'    => __("Choose how currency updates at checkout. 'Instant Change' updates immediately when a payment method is selected, while 'On Place Order' updates only when the order is placed.", 'easy-currency'),
                'id'      => 'checkout_settings[eccw_currency_change_mode]',
                'default' => isset($checkout_settings['eccw_currency_change_mode']) ? $checkout_settings['eccw_currency_change_mode'] : 'place_order',
            ),


            'eccw_curr_payment_gateway_section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_currency_payment_gateway_section_end',
                'html' => $this->eccw_payment_gateway_rule_currency(),
            )
        );
       
        $payment_rule_currency_field_end = array(
            array(
                'type' => 'html',
                'html' => '</div>'
            )
        );

        $shipping_billing_field_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-currency-shipping-billing-rule ' . $pro_missing_class . '">'
            )
        );

        $shipping_billing_settings_fields = array(
            'eccw_shipping_billing_section_title' => array(
                'name' => __("Shipping & Billing Currency Settings ({$protext})", 'easy-currency'),
                'type' => 'title',
                'desc' => __('Select how the currency changes based on customer billing or shipping address.', 'easy-currency'),
                'id'   => 'eccw_shipping_billing_title'
            ),
            'eccw_shipping_billing_select_option' => array(
                'title'   => __('Currency on Billing', 'eccw'),
                'type'    => 'eccw_currency_on_billing',
                'id'      => 'checkout_settings[eccw_currency_on_billing]',
                'desc'    => '',
                'desc_tip' => 'Note: On the checkout page, the currency will automatically switch when the customer changes their billing country or selects a shipping country (if Ship to a different address is enabled). This will only work if the chosen country’s currency exists in your Currency Exchange Rate Table.', 
                'default' => isset($checkout_settings['eccw_currency_on_billing']) ? $checkout_settings['eccw_currency_on_billing'] : 'none',
            ),
            'eccw_shipping_billing_section_end' => array(
                'type' => 'sectionend',
                'id'   => 'eccw_currency_shipping_billing_section_end',
            )
        );

       
        $shipping_billing_field_end = array(
            array(
                'type' => 'html',
                'html' => '</div>'
            )
        );


        $all_settings = array_merge( $checkout_currency_enable_field_start, $checkout_enable_settings_fields,$checkout_currency_enable_field_end, $checkout_currency_field_start,  $checkout_settings_fields, $checkout_currency_field_end ,$payment_rule_currency_field_start, $payment_gateway_rule_settings_fields, $payment_rule_currency_field_end , $shipping_billing_field_start, $shipping_billing_settings_fields, $shipping_billing_field_end );
        

       return $all_settings;
    }

    public function eccw_get_settings_converter_widgets_tab_fields() {
        // Retrieve saved settings
        $saved_settings = get_option('eccw_currency_settings');
        $options = isset($saved_settings['options']) ? $saved_settings['options'] : [];
        $design = isset($saved_settings['design']) ? $saved_settings['design'] : [];
        $pro_active = class_exists('ECCW_CURRENCY_SWITCHER_PRO');
        $settings = array(
            'section_title' => array(
                'name' => __('Basic Settings', 'easy-currency') ,
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_settings_tab_section_titles',
                'classes' => 'section-title'
            ),
                    'converter_widget_style' => array(
                'name' => $pro_active 
                    ? __('Layout Style', 'easy-currency') 
                    : __('Layout Style (PRO)', 'easy-currency'),

                'type'      => 'select',
                'options'   => ['one' => 'Style One'],
                'desc_tip'  => $pro_active ? '' : __('Available in PRO', 'easy-currency'), // show only if PRO missing
                'desc'      => __('Widget position where you want to show this.', 'easy-currency'),
                'id'        => 'options[converter_widget_style]',
                'default'   => isset($options['converter_widget_style']) ? $options['converter_widget_style'] : 'one',
                'class'     => 'easy-converter-pro'
            ),
            'converter_currency_button_border_color' => array(
                'name'      => $pro_active 
                    ? __('Border Color', 'easy-currency') 
                    : __('Border Color (PRO)', 'easy-currency'),
                'type'      => 'text',
                'id'        => 'design[converter_currency_button][border-color]',
                'default'   => isset($design['converter_currency_button']['border-color']) ? $design['converter_currency_button']['border-color'] : '',
                'class'     => 'eccw-color-input',
                'desc_tip'  => $pro_active ? '' : __('Available in PRO', 'easy-currency')
            ),
            'converter_currency_button_border' => array(
                'name'      => $pro_active 
                    ? __('Border width', 'easy-currency') 
                    : __('Border width (PRO)', 'easy-currency'),
                'type'      => 'text',
                'desc'      => 'enter number with px. ex: 2px 2px 2px 2px',
                'default'   => isset($design['converter_currency_button']['null']) ? $design['converter_currency_button']['null'] : '',
                'class'     => 'eccw-currency-side-widget-bg eccw-dimension-input',
                'custom_attributes' => array(
                    'unit'   => 'px',
                    'fields' => wp_json_encode(array(
                        array('type'=>'text','name'=>'design[converter_currency_button][border-top-width]','value'=>isset($design['converter_currency_button']['border-top-width']) ? $design['converter_currency_button']['border-top-width'] : '', 'placeholder'=>'top'),
                        array('type'=>'text','name'=>'design[converter_currency_button][border-left-width]','value'=>isset($design['converter_currency_button']['border-left-width']) ? $design['converter_currency_button']['border-left-width'] : '', 'placeholder'=>'left'),
                        array('type'=>'text','name'=>'design[converter_currency_button][border-bottom-width]','value'=>isset($design['converter_currency_button']['border-bottom-width']) ? $design['converter_currency_button']['border-bottom-width'] : '', 'placeholder'=>'bottom'),
                        array('type'=>'text','name'=>'design[converter_currency_button][border-right-width]','value'=>isset($design['converter_currency_button']['border-right-width']) ? $design['converter_currency_button']['border-right-width'] : '', 'placeholder'=>'right'),
                    ))
                ),
                'desc_tip' => $pro_active ? '' : __('Available in PRO', 'easy-currency')
            ),
            'converter_currency_button_flag_size' => array(
                'name'        =>  $pro_active 
                    ? __('Flag Size (Width)', 'easy-currency') 
                    : __('Flag Size (Width) (PRO)', 'easy-currency'), 
                'type'        => 'text',
                'desc'        => 'enter number with px. ex: 15px',
                'id'          => 'design[converter_currency_button_flag][width]',
                'default'     => isset($design['converter_currency_button_flag']['width']) ? $design['converter_currency_button_flag']['width'] : '',
                'placeholder' => '15px',
                'class'       => 'eccw-input',
                'desc_tip'    => $pro_active ? '' : __('Available in PRO', 'easy-currency')
            ),
            'section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_settings_tab_section_end'
            )
        );

        $all_settings = array_merge($settings);
    
        return $all_settings;
    }

    public function eccw_settings_tab_settings()
    {

        $ECCW_CURRENCY_SERVER = new ECCW_CURRENCY_SERVER();
        $currency_countries = $ECCW_CURRENCY_SERVER->eccw_get_currency_countries();
        $eccw_currency_settings = get_option('eccw_currency_settings', []);

        $default_currency = isset($eccw_currency_settings['default_currency']) && !empty($eccw_currency_settings['default_currency']) ? $eccw_currency_settings['default_currency'] : 'USD';

        wp_nonce_field('eccw_update_settings', 'eccw_nonce');


    ?>
        <div class="eccw-settings-tabs-container">
            <div class="tabs eccw-settings-tabs">
                <div class="ajax-loader">
                    <img src="<?php echo esc_url(ECCW_PL_URL . 'admin/assets/img/ajax-loader.gif'); ?>" alt="Ajax Loader">
                </div>
                <ul id="tabs-nav">
                    <li><a href="#tab_currency">Currencies</a></li>
                    <li><a href="#tab_currency_options">Options</a></li>
                    <li><a href="#tab_currency_switcher_shortcode">Shortcode</a></li>
                    <li><a href="#tab_currency_switcher_sticky">Side</a></li>
                    <li><a href="#tab_currency_advanced_settings">Advanced</a></li>
                    <li><a href="#tab_currency_checkout_settings">Checkout</a></li>
                    <li><a href="#tab_converter_widgets">Converter</a></li>
                    <li><a href="#tab_currency_usage">Usage</a></li>
                    

                </ul>
                <div class="tab-contents-wrapper">
                    <div id="tab_currency" class="tab-content">
                        <div class="alert alert-error">
                            <p class="eccw-err-msg"></p>
                        </div>
                        <div class="content-header">
                            <h2>Currencies</h2>
                            <button type="button" class="button button-primary update-currency-rates">Update Rates</button>
                        </div>

                        <table id="eccw-repeatable-fields-table" class="widefat easy-currency-table">
                            <thead>
                                <tr>
                                    <th>Default</th>
                                    <th>Currency Code</th>
                                    <th>Exchange Rate</th>
                                    <th>Symbol Position</th>
                                    <th>Decimal</th>
                                    <th>Decimal Separator</th>
                                    <th>Thousand Separator</th>
                                    <th>Custom Symbol</th>
                                    <th>Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php

                                $default_country = get_option('woocommerce_default_country', '');
                                $country_code    = explode(':', $default_country)[0];

                                // object → array convert
                                $currency_countries = json_decode(json_encode($currency_countries), true);

                                $matched_currency = '';

                                foreach ( $currency_countries as $currency_code => $data ) {
                                    if ( in_array( $country_code, $data['countries'], true ) ) {
                                        $matched_currency = $currency_code;
                                        break;
                                    }
                                }

                                if (!empty($eccw_currency_settings) && isset($eccw_currency_settings['eccw_currency_table']) && count($eccw_currency_settings['eccw_currency_table']) > 0) {
                                    $eccw_currency_table = $eccw_currency_settings['eccw_currency_table'];

                                    foreach ( $eccw_currency_table as $index => $currency_data ) {
                                        $default = isset($currency_data['default']) ? $currency_data['default'] : '';
                                        $currency_code = isset($currency_data['code']) ? $currency_data['code'] : '';
                                        $rate = isset($currency_data['rate']) ? $currency_data['rate'] : '';
                                        $symbol_position = isset($currency_data['symbol_position']) ? $currency_data['symbol_position'] : 'left';
                                        $decimal = isset($currency_data['decimal']) ? $currency_data['decimal'] : '2';
                                        $decimal_separator = isset($currency_data['decimal_separator']) ? $currency_data['decimal_separator'] : '.';
                                        $thousand_separator = isset($currency_data['thousand_separator']) ? $currency_data['thousand_separator'] : ',';
                                        $custom_symbol = isset($currency_data['custom_symbol']) ? $currency_data['custom_symbol'] : '';
                                        $tr_class = ($currency_code === $default_currency) ? 'easy-base-currency' : '';
                                        echo '<tr class="' . esc_attr($tr_class) . '">';
                                        echo '<td><input type="radio" name="eccw_currency_table[default]" value="' . esc_attr($currency_code) . '"' . checked($currency_code, $default_currency, false) . ' /></td>';
                                        echo '<td><select name="eccw_currency_table[' . esc_attr($index) . '][code]" class="easy-currency-dropdowneccw">';

                                        foreach ($currency_countries as $key => $value) {
                                            echo '<option value="' . esc_attr($key) . '"' . selected($currency_code ?? '', $key, false) . '>' . esc_attr($key) . '</option>';
                                        }

                                        echo '</select></td>';
                                       echo '<input class="easy-base-currency-hidden-field" type="hidden" name="eccw_currency_table[' . esc_attr($index) . '][base_currency]" value="' . esc_attr($default_currency) . '" />';
                                        echo '<td><input type="text" name="eccw_currency_table[' . esc_attr($index) . '][rate]" value="' . esc_attr($rate) . '" class="currency-rate"/></td>';
                                        echo '<td><select name="eccw_currency_table[' . esc_attr($index) . '][symbol_position]">
                                            <option value="left"' . selected($symbol_position, 'left', false) . '>Left</option>
                                            <option value="right"' . selected($symbol_position, 'right', false) . '>Right</option>
                                            <option value="left_space"' . selected($symbol_position, 'left_space', false) . '>Left with space</option>
                                            <option value="right_space"' . selected($symbol_position, 'right_space', false) . '>Right with space</option>
                                        </select></td>';
                                        echo '<td><select name="eccw_currency_table[' . esc_attr($index) . '][decimal]">
                                            <option value="1"' . selected($decimal, '1', false) . '>1</option>
                                            <option value="2"' . selected($decimal, '2', false) . '>2</option>
                                            <option value="3"' . selected($decimal, '3', false) . '>3</option>
                                            <option value="4"' . selected($decimal, '4', false) . '>4</option>
                                            <option value="5"' . selected($decimal, '5', false) . '>5</option>
                                            <option value="6"' . selected($decimal, '6', false) . '>6</option>
                                            <option value="7"' . selected($decimal, '7', false) . '>7</option>
                                            <option value="8"' . selected($decimal, '8', false) . '>8</option>
                                        </select></td>';
                                        echo '<td><input type="text" name="eccw_currency_table[' . esc_attr($index) . '][decimal_separator]" value="' . esc_attr($decimal_separator) . '" /></td>';
                                        echo '<td><input type="text" name="eccw_currency_table[' . esc_attr($index) . '][thousand_separator]" value="' . esc_attr($thousand_separator) . '" /></td>';
                                        echo '<td><input type="text" name="eccw_currency_table[' . esc_attr($index) . '][custom_symbol]" value="' . esc_attr($custom_symbol) . '" placeholder="e.g. $"/></td>';
                                        echo '<td><button type="button" class="button remove-row">Remove</button></td>';
                                        echo '</tr>';
                                    }
                                } else {

                                    echo '<tr class="easy-base-currency">';
                                    echo '<td><input type="radio" name="eccw_currency_table[default]" value="'.esc_attr($matched_currency).'" checked /></td>';

                                    echo '<td><select name="eccw_currency_table[0][code]" class="easy-currency-dropdowneccw">';
                                    foreach ( $currency_countries as $currency_code => $data ) {
                                        echo '<option value="'.esc_attr($currency_code).'" '.selected($matched_currency, $currency_code, false).'>'.esc_html($currency_code).'</option>';
                                    }
                                    echo '<input type="hidden" class="easy-base-currency-hidden-field" name="eccw_currency_table[0][base_currency]" value="'.esc_attr($default_currency).'" />';
                                    echo '</select></td>';
                                    echo '<td><input type="text" name="eccw_currency_table[0][rate]" value="1" class="currency-rate" /></td>';
                                    echo '<td><select name="eccw_currency_table[0][symbol_position]">
                                                <option value="left">Left</option>
                                                <option value="right">Right</option>
                                            </select></td>';
                                    echo '<td><select name="eccw_currency_table[0][decimal]">
                                            <option value="1">1</option>
                                            <option value="2" selected>2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                            <option value="5">5</option>
                                            <option value="6">6</option>
                                            <option value="7">7</option>
                                            <option value="7">7</option>
                                            <option value="8">8</option>
                                        </select></td>';
                                    echo '<td><input type="text" name="eccw_currency_table[0][decimal_separator]" value="." /></td>';
                                    echo '<td><input type="text" name="eccw_currency_table[0][thousand_separator]" value="," /></td>';
                                    echo '<td><input type="text" name="eccw_currency_table[0][custom_symbol]" value="" placeholder="e.g. $"/></td>';
                                    echo '<td><button type="button" class="button remove-row">Remove</button></td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                        <button type="button" class="button add-currency"><?php echo esc_html__('Add Currency', 'easy-currency') ?></button>

                    </div>

                    <div id="tab_currency_options" class="tab-content" style="display: none;">
                        <?php woocommerce_admin_fields($this->get_eccw_settings_options_tab_fields()); ?>
                    </div>
                   
                    <div id="tab_currency_usage" class="tab-content">
                        <div class="alert alert-error">
                            <p class="eccw-err-msg"></p>
                        </div>
                        <h2><?php echo esc_html__('How to use this converter?', 'easy-currency') ?></h2>
                        <p>
                            <label><?php echo esc_html__('Shortcode :', 'easy-currency'); ?></label>
                            <code>[easy_currency_switcher id="1"]</code>
                        </p>

                        <p>
                            <label>
                                <?php echo esc_html__('Pro Shortcode :', 'easy-currency'); ?>
                                <span class="eccw-pro-badge-shortcode">PRO</span>
                            </label>
                            <code>[easy_currency_converter]</code>
                        </p>

                        <p>
                            <label>
                                <?php echo esc_html__('Pro Shortcode :', 'easy-currency'); ?>
                                <span class="eccw-pro-badge-shortcode">PRO</span>
                            </label>
                            <code>[easy_currency_rates_table]</code>
                        </p>

                        <p><label><?php echo esc_html__('Elementor widget :', 'easy-currency') ?> </label> Easy Currency Switcher</p>
                    </div>
                    <div id="tab_currency_switcher_shortcode" class="tab-content">
                        <div class="alert alert-error">
                            <p class="eccw-err-msg"></p>
                        </div>

                        <div class="eccw-designer-container">
                            <div class="eccw-designer-header">
                                <h2><?php echo esc_html__('Shortcode Generator', 'easy-currency') ?></h2>
                                <button class="eccw-shortcode-popup-form"><?php echo esc_html__('+ Create', 'easy-currency') ?></button>
                            </div>

                            <div class="eccw-shortcode-modal" id="eccw-shortcode-modal">
                                <span class="eccw-style-modal-switcher-close">&times;</span>
                                <h3><?php echo esc_html__('Create New Switcher', 'easy-currency'); ?></h3>
                                <div class="eccw-shortcode-form" id="eccw-shortcode-form">
                                    <?php woocommerce_admin_fields($this->eccw_create_switcher_shortcode_popup_field()); ?>
                                    <button type="submit" class="create-shortcode-submit-button button button-primary"><?php echo esc_html__('Save', 'easy-currency'); ?></button>
                                </div>
                            </div>
                            <div class="eccw-modal-overlay" id="eccw-modal-overlay"></div>
                            <div class="eccw-designer-list-header">
                                <span class="col-shortcode">Shortcode</span>
                                <span class="col-name">Name</span>
                                <span class="col-actions">Actions</span>
                            </div>

                            <div class="eccw-designer-list">
                                <?php
                                global $ECCW_Admin_Ajax;
                                $shortcodes = $ECCW_Admin_Ajax->eccw_get_all_shortcodes_cached();

                                foreach ($shortcodes as $shortcode) {

                                ?>
                                    <div class="eccw-designer-card">
                                        
                                        <div class="eccw-designer-info">
                                            <div class="eccw-shortcode-box">
                                                <input
                                                    type="text"
                                                    readonly
                                                    class="eccw-shortcode-input"
                                                    value="<?php echo esc_html($shortcode['shortcode']); ?>" />
                                                <button type="button" class="eccw-copy-btn" title="Copy shortcode">
                                                    📋
                                                </button>
                                            </div>
                                        </div>
                                        <div class="switcher-name" title="<?php echo esc_attr($shortcode['switcher_name']); ?>">
                                            <?php echo esc_html(wp_trim_words($shortcode['switcher_name'], 4, '...')); ?>
                                        </div>

                                        <div class="eccw-designer-actions">
                                            <button class="eccw-btn-edit" data-id="<?php echo esc_attr($shortcode['id']); ?>"><?php echo esc_html__('Edit', 'easy-currency') ?></button>
                                            <button class="eccw-btn-delete" data-id="<?php echo  esc_attr($shortcode['id']); ?>"><?php echo esc_html__('Delete', 'easy-currency') ?></button>
                                        </div>
                                    </div>
                                <?php }
                                ?>
                            </div>
                        </div>

                        <div id="eccw-style-modal-switcher" class="eccw-style-modal-switcher" style="display: none;">

                            <div class="eccw-style-modal-switcher-content">
                                <span class="eccw-style-modal-switcher-close">&times;</span>
                                <input type="hidden" id="eccw-style-modal-switcher-id" name="ëccw_shortcode_id" value="" />
                                <input type="hidden" id="eccw-style-modal-switcher-type" class="eccw-style-modal-switcher-type" name="ëccw_switcher_type" value="" />

                                <div class="eccw-tabs-wrapper">
                                    <button class="eccw-tab-btn active" data-tab="eccw_general_tab">General</button>
                                    <button class="eccw-tab-btn" data-tab="eccw_display_option_tab">Display Option</button>
                                    <button class="eccw-tab-btn" data-tab="eccw_display_custom_css">Custom Css</button>
                                </div>

                                <div class="eccw-style-modal-switcher-form" data-eccwtab="">
                                </div>

                                <div class="eccw-button-wrapper">
                                    <button type="submit" class="eccw-style-modal-switcher-save-closebtn" disabled><?php echo esc_html__('Save & Exit', 'easy-currency') ?></button>
                                    <button type="submit" class="eccw-style-modal-switcher-save-btn" disabled><?php echo esc_html__('Apply Now', 'easy-currency') ?></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="tab_currency_switcher_sticky" class="tab-content">
                        <?php woocommerce_admin_fields($this->eccw_switcher_sticky_field()); ?>

                    </div>
                    <div id="tab_currency_advanced_settings" class="tab-content">
                        <?php woocommerce_admin_fields($this->eccw_advanced_settings_field()); ?>
                    </div>

                    <div id="tab_currency_checkout_settings" class="tab-content">
                        <?php woocommerce_admin_fields($this->eccw_checkout_settings_field()); ?>
                    </div>

                    <div id="tab_converter_widgets" class="tab-content easy-tab-converter-widget <?php echo class_exists('ECCW_CURRENCY_SWITCHER_PRO') ? '' : 'eccw-ccpro-missing'; ?>">
                        <?php woocommerce_admin_fields($this->eccw_get_settings_converter_widgets_tab_fields()); ?>
                    </div>
                </div>
            </div>
        </div>

<?php
    }


    public function save_eccw_settings_tab_settings()
    {
        // Save repeatable fields data
        if (isset($_POST['eccw_currency_table'])) {

            check_admin_referer('eccw_update_settings', 'eccw_nonce');

            $data = map_deep(wp_unslash($_POST['eccw_currency_table']), 'sanitize_text_field') ?? [];

            $default_currency = isset($data['default']) && !empty($data['default']) ? $data['default'] : 'usd';
            $currency_settings = array(
                'default_currency' => $default_currency,
            );

            if (isset($_POST['options'])) {
                $options = map_deep(wp_unslash($_POST['options']), 'sanitize_text_field') ?? [];
                $currency_settings['options'] = $options;
            }

            if (isset($_POST['design'])) {
                $design = map_deep(wp_unslash($_POST['design']), 'sanitize_text_field') ?? [];
                $currency_settings['design'] = $design;
            }

            if (isset($_POST['advanced_settings'])) {
                $design = map_deep(wp_unslash($_POST['advanced_settings']), 'sanitize_text_field') ?? [];
                $currency_settings['advanced_settings'] = $design;
            }

            if (isset($_POST['checkout_settings'])) {
                $design = map_deep(wp_unslash($_POST['checkout_settings']), 'sanitize_text_field') ?? [];
                $currency_settings['checkout_settings'] = $design;
            }


            $filtered_data = array_filter($data, function ($row) {
                return !empty($row['code']) || !empty($row['rate']) || !empty($row['symbol_position']) || !empty($row['decimal']) || !empty($row['separator']) || !empty($row['description']);
            });


            if (isset($_POST['eccw_currency_table_geo'])) {
                $geo_data = map_deep(wp_unslash($_POST['eccw_currency_table_geo']), 'sanitize_text_field') ?? [];

                update_option('eccw_currency_table_geo', $geo_data);
            }

            if (isset($_POST['eccw_currency_payment_select'])) {
                $currency_country_data = map_deep(wp_unslash($_POST['eccw_currency_payment_select']), 'sanitize_text_field') ?? [];

                update_option('eccw_currency_payment_select', $currency_country_data );
            }

            if (isset($_POST['eccw_gateway_currency_set'])) {
                $payment_method_curcy_data = map_deep(wp_unslash($_POST['eccw_gateway_currency_set']), 'sanitize_text_field') ?? [];

                update_option('eccw_gateway_currency_set', $payment_method_curcy_data );
            }
              
            $payment_status_data = isset($_POST['eccw_currency_payment_status']) 
                ? map_deep(wp_unslash($_POST['eccw_currency_payment_status']), 'sanitize_text_field') 
                : [];
            
            $eccw_currency_settings = get_option('eccw_currency_settings', []);
            $eccw_currency_table = $eccw_currency_settings['eccw_currency_table'] ?? [];

            $checkout_settings = map_deep($_POST['checkout_settings'] ?? [], 'sanitize_text_field');
            $val = $checkout_settings['eccw_currency_on_billing'] ?? 'none';
            $val = in_array($val, ['none','billing','shipping'], true) ? $val : 'none';
            $currency_settings['checkout_settings']['eccw_currency_on_billing'] = $val;
            update_option('eccw_currency_on_billing', $val);


           
            foreach( $eccw_currency_table as $index => $currency_row ) {
                $currency_code = $currency_row['code'] ?? '';
                if (!isset($payment_status_data[$index][$currency_code])) {
                    $payment_status_data[$index][$currency_code] = 0;
                } else {
                    $payment_status_data[$index][$currency_code] = ($payment_status_data[$index][$currency_code] == '1') ? 1 : 0;
                }
            }

            update_option('eccw_currency_payment_status', $payment_status_data);

            $filtered_data = array_values($filtered_data);
            $currency_settings['eccw_currency_table'] = $filtered_data;

            update_option('eccw_currency_settings', $currency_settings);
        }
    }

    public function eccw_add_easy_currency_menu()
    {
        add_menu_page(
            'Easy Currency',
            'Easy Currency',
            'manage_woocommerce',
            'eccw-easy-currency',
            array($this, 'eccw_redirect_to_settings_tab'),
            'dashicons-money-alt',
            56
        );
    }

    public function eccw_redirect_to_settings_tab()
    {
        wp_safe_redirect(admin_url('admin.php?page=wc-settings&tab=eccw_settings_tab'));
        exit;
    }
}

ECCW_admin_settings::get_instance();
