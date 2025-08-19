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

        $settings = array(
            'section_title' => array(
                'name' => __('Change currency options from here.', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_settings_tab_section_title'
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
                    'woocommerce_single_product_summary' => 'Before - Product summary',
                    'woocommerce_after_single_product_summary' => 'After - Product summary'
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

        $all_settings = array_merge($settings, $switcher_location_start, $single_settings,$switcher_location_end );


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
                'default' => isset($design['eccw_switcher_currency_name_show_hide']) ? $design['eccw_switcher_currency_name_show_hide'] : 'no',
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
                'default' => isset($design['eccw_show_hide_side_currency']) ? $design['eccw_show_hide_side_currency'] : 'yes',
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
                'default' => isset($design['eccw_sticky_currency_name_show_hide']) ? $design['eccw_sticky_currency_name_show_hide'] : 'yes',
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

    public function eccw_settings_tab_settings()
    {

        $ECCW_CURRENCY_SERVER = new ECCW_CURRENCY_SERVER();
        $currency_countries = $ECCW_CURRENCY_SERVER->eccw_get_currency_countries();
        $eccw_currency_settings = get_option('eccw_currency_settings', []);
        $default_currency = isset($eccw_currency_settings['default_currency']) && !empty($eccw_currency_settings['default_currency']) ? $eccw_currency_settings['default_currency'] : 'usd';

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
                    <li><a href="#tab_currency_switcher_sticky">Sticky Side</a></li>
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

                        <table id="eccw-repeatable-fields-table" class="widefat">
                            <thead>
                                <tr>
                                    <th>Default</th>
                                    <th>Currency Code</th>
                                    <th>Exchange Rate</th>
                                    <th>Symbol Position</th>
                                    <th>Decimal</th>
                                    <th>Decimal Separator</th>
                                    <th>Thousand Separator</th>
                                    <th>Description</th>
                                    <th>Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php

                                if (!empty($eccw_currency_settings) && isset($eccw_currency_settings['eccw_currency_table']) && count($eccw_currency_settings['eccw_currency_table']) > 0) {
                                    $eccw_currency_table = $eccw_currency_settings['eccw_currency_table'];
                                    foreach ($eccw_currency_table as $index => $currency_data) {
                                        $default = isset($currency_data['default']) ? $currency_data['default'] : '';
                                        $currency_code = isset($currency_data['code']) ? $currency_data['code'] : '';
                                        $rate = isset($currency_data['rate']) ? $currency_data['rate'] : '';
                                        $symbol_position = isset($currency_data['symbol_position']) ? $currency_data['symbol_position'] : 'left';
                                        $decimal = isset($currency_data['decimal']) ? $currency_data['decimal'] : '2';
                                        $decimal_separator = isset($currency_data['decimal_separator']) ? $currency_data['decimal_separator'] : '.';
                                        $thousand_separator = isset($currency_data['thousand_separator']) ? $currency_data['thousand_separator'] : ',';
                                        $description = isset($currency_data['description']) ? $currency_data['description'] : '';

                                        echo '<tr>';
                                        echo '<td><input type="radio" name="eccw_currency_table[default]" value="' . esc_attr($currency_code) . '"' . checked($currency_code, $default_currency, false) . ' /></td>';
                                        echo '<td><select name="eccw_currency_table[' . esc_attr($index) . '][code]">';

                                        foreach ($currency_countries as $key => $value) {
                                            echo '<option value="' . esc_attr($key) . '"' . selected($currency_code ?? '', $key, false) . '>' . esc_attr($key) . '</option>';
                                        }

                                        echo '</select></td>';
                                        echo '<td><input type="text" name="eccw_currency_table[' . esc_attr($index) . '][rate]" value="' . esc_attr($rate) . '" class="currency-rate"/></td>';
                                        echo '<td><select name="eccw_currency_table[' . esc_attr($index) . '][symbol_position]">
                                            <option value="left"' . selected($symbol_position, 'left', false) . '>Left</option>
                                            <option value="right"' . selected($symbol_position, 'right', false) . '>Right</option>
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
                                        echo '<td><input type="text" name="eccw_currency_table[' . esc_attr($index) . '][description]" value="' . esc_attr($description) . '" class="widefat"/></td>';
                                        echo '<td><button type="button" class="button remove-row">Remove</button></td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr>';
                                    echo '<td><input type="radio" name="eccw_currency_table[default]" value="" /></td>';
                                    echo '<td><select name="eccw_currency_table[0][code]">
                                            <option value="usd">USD</option>
                                            <option value="euro">EURO</option>
                                            <option value="bdt">BDT</option>
                                        </select></td>';
                                    echo '<td><input type="text" name="eccw_currency_table[0][rate]" value="" class="currency-rate" /></td>';
                                    echo '<td><select name="eccw_currency_table[0][symbol_position]">
                                                <option value="left">Left</option>
                                                <option value="right">Right</option>
                                            </select></td>';
                                    echo '<td><select name="eccw_currency_table[0][decimal]">
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                            <option value="5">5</option>
                                            <option value="6">6</option>
                                            <option value="7">7</option>
                                            <option value="7">7</option>
                                            <option value="8">8</option>
                                        </select></td>';
                                    echo '<td><input type="text" name="eccw_currency_table[0][decimal_separator]" value="" /></td>';
                                    echo '<td><input type="text" name="eccw_currency_table[0][thousand_separator]" value="" /></td>';
                                    echo '<td><input type="text" name="eccw_currency_table[0][description]" value="" class="widefat"/></td>';
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
                        <p><label><?php echo esc_html__('Shortcode :', 'easy-currency') ?> </label> [eccw_currency_switcher]</p>
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

            $filtered_data = array_filter($data, function ($row) {
                return !empty($row['code']) || !empty($row['rate']) || !empty($row['symbol_position']) || !empty($row['decimal']) || !empty($row['separator']) || !empty($row['description']);
            });

            // Re-index array to ensure sequential keys
            $filtered_data = array_values($filtered_data);
            $currency_settings['eccw_currency_table'] = $filtered_data;

            // Update the option with filtered data
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
