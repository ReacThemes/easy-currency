<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class ECCW_admin_settings {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct(){   
        add_filter('woocommerce_settings_tabs_array', array($this, 'eccw_settings_tab'), 50);
        add_action('woocommerce_settings_eccw_settings_tab', array($this, 'eccw_settings_tab_settings'));
        add_action('woocommerce_update_options_eccw_settings_tab', array($this, 'save_eccw_settings_tab_settings'));
        add_action('admin_menu', array( $this, 'eccw_add_easy_currency_submenu_redirect') );
        add_action('woocommerce_admin_field_template_preview', array( $this, 'eccw_template_preview_field'));
        add_action('woocommerce_admin_field_switcher',array( $this, 'eccw_admin_field_switcher_show_hide'));
        add_action('woocommerce_admin_field_slider', array($this, 'eccw_admin_field_custom_slider'));
        add_action('woocommerce_admin_field_html', array($this, 'eccw_admin_field_switcher_html_start_end'));

    }  

    public function eccw_admin_field_switcher_html_start_end( $value ) {
        if (!empty($value['html'])) {
            echo $value['html'];
        }
    }

    public function eccw_admin_field_custom_slider( $field ) {
        $id = esc_attr( $field['id'] );
        $desc = esc_attr( $field['desc'] );
        $name = esc_attr( $field['id'] );
        $min = isset( $field['min'] ) ? intval( $field['min'] ) : 0;
        $max = isset( $field['max'] ) ? intval( $field['max'] ) : 100;
        $step = isset( $field['step'] ) ? intval( $field['step'] ) : 1;
        $default = get_option( $field['id'], isset($field['default']) ? $field['default'] : $min );

        echo '<tr valign="top">';
        echo '<th scope="row" class="titledesc">';
        echo esc_html( $field['name'] );
        echo '</th>';

        echo '<td class="forminp">';

        // Slider input
        echo '<input class="eccw-slider-range" type="range" id="' . $id . '" name="' . $name . '" min="' . $min . '" max="' . $max . '" step="' . $step . '" value="' . esc_attr($default) . '" style="vertical-align: middle; width: 150px; margin-right: 10px;">';

        echo '<input class="eccw-slider-range-value" type="text" id="' . $id . '_value" value="' . esc_attr($default) . '" readonly style="width: 50px; text-align: center;">';

        echo '</td>';
        echo '</tr>';
    }

    public function eccw_template_preview_field($field) {
        $value = isset($field['value']) ? $field['value'] : '';

        $name  = esc_attr($field['id']);

        $templates = array(
            'eccw_template_1' => ECCW_PL_URL . 'admin/assets/img/eccw-template-1.png',
            'eccw_template_2' => ECCW_PL_URL . 'admin/assets/img/eccw-template-2.png',
            'eccw_template_3' => ECCW_PL_URL . 'admin/assets/img/eccw-template-3.png',
            'eccw_template_4' => ECCW_PL_URL . 'admin/assets/img/eccw-template-4.png',
            'eccw_template_5' => ECCW_PL_URL . 'admin/assets/img/eccw-template-5.png',
        );

        echo '<tr valign="top"><th scope="row" class="titledesc"><label>' . esc_html($field['name']) . '</label></th><td class="forminp">';

        echo '<div class="eccw-template-preview-wrapper">';
        $count = 1;
        $count2 = 1;
        $reset_done = false;
        foreach ($templates as $key => $img_url) {
            $dropdown_class = ( $count == '1' || $count == '2')  ? 'dropdown-template' : 'side-template';
            $checked = $value == $key ? 'checked' : '';
           
            echo '
            <label  class="eccw-template '.$dropdown_class.'">
                <input type="radio" name="' . $name . '" value="' . $key . '" ' . $checked . ' />
                <div  class="eccw-template-preview-image">
                    <img src="' . esc_url($img_url) . '" alt="layout image"/>
                    
                </div>
                <p class="eccw-template-count">' . ucfirst( "Template" . " - " . $count2) . '</p>
            </label>
            ';
       $count++; $count2++; 
       if ($count2 > 2 && !$reset_done) {
            $count2 = 1;
            $reset_done = true;
        }
    }

        echo '</div>';

        if (!empty($field['desc'])) {
            echo '<p class="description">' . esc_html($field['desc']) . '</p>';
        }

        echo '</td></tr>';
    }

    public function eccw_admin_field_switcher_show_hide($field) {  

        $value = get_option( $field['id'], $field['default'] ?? '' );
        $desc = ! empty( $field['desc'] ) ? $field['desc'] : '';

        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <?php echo esc_html( $field['title'] ); ?>
            </th>
            <td class="forminp forminp-checkbox">
                <label class="eccw-switch">
                    <input type="hidden" name="<?php echo esc_attr( $field['id'] ); ?>" value="no" />
                    <input type="checkbox" name="<?php echo esc_attr( $field['id'] ); ?>" value="yes" <?php checked( $value, 'yes' ); ?> />
                    <span class="eccw-slider"></span>
                </label>
            </td>
        </tr>

    <?php 
    }


    public function eccw_settings_tab($tabs){
        $tabs['eccw_settings_tab'] = __('Easy Currency', 'easy-currency'); // Add the custom tab
        return $tabs;
    }
    public function get_eccw_settings_options_tab_fields() {
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
                'class' => 'eccw-currency-aggregator-input', // Add custom class here
            ),
            'currency_aggregator_api_key' => array(
                'name' => __('Api Key', 'easy-currency'),
                'type' => 'text',
                'desc' => __('Enter aggregator api key.', 'easy-currency'),
                'id' => 'options[currency_aggregator_api_key]',
                'default' => isset($options['currency_aggregator_api_key']) ? $options['currency_aggregator_api_key'] : '',
                'class' => 'eccw-currency-aggregator-api-key-input', // Add custom class here

            ),
            'allow_payment_with_selected_currency' => array(
                'name' => __('Payment with selected currency', 'easy-currency'),
                'type' => 'select',
                'options' => ['yes' => 'Yes', 'no' => 'No'],
                'desc' => __('Allow selected currency for payment. User cam pay with selected currency if this yes.', 'easy-currency'),
                'id' => 'options[allow_payment_with_selected_currency]',
                    'default' => isset($options['allow_payment_with_selected_currency']) ? $options['allow_payment_with_selected_currency'] : 'no',
            ),
            'flag_visibility' => array(
                'name' => __('Show Flag', 'easy-currency'),
                'type' => 'select',
                'options' => ['yes' => 'Yes', 'no' => 'No'],
                'desc' => __('Show flag on the currency switcher.', 'easy-currency'),
                'id' => 'options[flag_visibility]',
                    'default' => isset($options['flag_visibility']) ? $options['flag_visibility'] : 'yes',
            ),
            'section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_settings_tab_section_end'
            )
        );
        return $settings;
    }
    
    public function get_eccw_settings_design_tab_fields() {
        // Retrieve saved settings
        $saved_settings = get_option('eccw_currency_settings');

        $design = isset($saved_settings['design']) ? $saved_settings['design'] : [];
        // echo '<pre>';
        // print_r($design);
        // echo '</pre>';

        $settings = array(
            'section_title' => array(
                'name' => __('Switcher Button', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_settings_tab_section_title'
            ),
            'switcher_button_width' => array(
                'name' => __('Width', 'easy-currency'),
                'type' => 'number',
                'id' => 'design[switcher_button][width]',
                'default' => isset($design['switcher_button']['width']) ? str_replace('px', '', $design['switcher_button']['width']) : '52',
                'class' => 'eccw-currency-switcher-button-width eccw-rang-input', // Add custom class here
                'custom_attributes' => array(
                    'unit' => 'px',
                )
            ),
            'switcher_button_bg' => array(
                'name' => __('Background Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_button][background]',
                'default' => isset($design['switcher_button']['background']) ? $design['switcher_button']['background'] : '',
                'class' => 'eccw-color-input ', // Add custom class here
            ),
            'switcher_button_border_color' => array(
                'name' => __('Border Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_button][border-color]',
                'default' => isset($design['switcher_button']['border-color']) ? $design['switcher_button']['border-color'] : '',
                'class' => 'eccw-color-input ', // Add custom class here
            ),
            'switcher_button_border' => array(
                'name' => __('Border Width', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 2px 2px 2px 2px',
                'default' => isset($design['switcher_button']['null']) ? $design['switcher_button']['null'] : '',
                'class' => 'eccw-currency-switcher-button-bg eccw-dimension-input', // Add custom class here
                'custom_attributes' => array(
                    'unit' => 'px',
                    'fields' => wp_json_encode(
                        array(
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_button][border-top-width]', 
                                'value' => isset($design['switcher_button']['border-top-width']) ? $design['switcher_button']['border-top-width'] : '', 
                                'placeholder' => 'top'
                            ), 
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_button][border-left-width]', 
                                'value' => isset($design['switcher_button']['border-left-width']) ? $design['switcher_button']['border-left-width'] : '', 
                                'placeholder' => 'left'
                            ), 
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_button][border-bottom-width]', 
                                'value' => isset($design['switcher_button']['border-bottom-width']) ? $design['switcher_button']['border-bottom-width'] : '', 
                                'placeholder' => 'bottom'
                            ), 
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_button][border-right-width]', 
                                'value' => isset($design['switcher_button']['border-right-width']) ? $design['switcher_button']['border-right-width'] : '', 
                                'placeholder' => 'right'
                            ), 
                        )
                    ),
                )
            ),
            'switcher_button_border_radius' => array(
                'name' => __('Border Radius', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 5px',
                'id' => 'design[switcher_button][border-radius]',
                'default' => isset($design['switcher_button']['border-radius']) ? $design['switcher_button']['border-radius'] : '',
                'placeholder' => '5px',
                'class' => 'eccw-input ', // Add custom class here
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
                'class' => 'eccw-currency-switcher-button-bg eccw-dimension-input', // Add custom class here
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
            'section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_settings_tab_section_end'
            )
        );

        $settings_dropdown = array(
            'eccw_dropdown_settings_tab_section_title' => array(
                'name' => __('Switcher Dropdown', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_dropdown_settings_tab_section_title'
            ),
            'switcher_dropdown_width' => array(
                'name' => __('Width', 'easy-currency'),
                'type' => 'number',
                'id' => 'design[switcher_dropdown][width]',
                'default' => isset($design['switcher_dropdown']['width']) ? str_replace('px', '', $design['switcher_dropdown']['width']) : '180',
                'class' => 'eccw-currency-switcher-dropdown-width eccw-rang-input', // Add custom class here
                'custom_attributes' => array(
                    'unit' => 'px',
                )
            ),
            'switcher_dropdown_bg' => array(
                'name' => __('Background Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown][background]',
                'default' => isset($design['switcher_dropdown']['background']) ? $design['switcher_dropdown']['background'] : 'transparent',
                'class' => 'eccw-color-input ', // Add custom class here
            ),
            'switcher_dropdown_border_color' => array(
                'name' => __('Border Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown][border-color]',
                'default' => isset($design['switcher_dropdown']['border-color']) ? $design['switcher_dropdown']['border-color'] : '',
                'class' => 'eccw-color-input ', // Add custom class here
            ),
            'switcher_dropdown_border' => array(
                'name' => __('Border Width', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 2px 2px 2px 2px',
                'default' => isset($design['switcher_dropdown']['null']) ? $design['switcher_dropdown']['null'] : '',
                'class' => 'eccw-currency-switcher-dropdown-bg eccw-dimension-input', // Add custom class here
                'custom_attributes' => array(
                    'unit' => 'px',
                    'fields' => wp_json_encode(
                        array(
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_dropdown][border-top-width]', 
                                'value' => isset($design['switcher_dropdown']['border-top-width']) ? $design['switcher_dropdown']['border-top-width'] : '', 
                                'placeholder' => 'top'
                            ), 
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_dropdown][border-left-width]', 
                                'value' => isset($design['switcher_dropdown']['border-left-width']) ? $design['switcher_dropdown']['border-left-width'] : '', 
                                'placeholder' => 'left'
                            ), 
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_dropdown][border-bottom-width]', 
                                'value' => isset($design['switcher_dropdown']['border-bottom-width']) ? $design['switcher_dropdown']['border-bottom-width'] : '', 
                                'placeholder' => 'bottom'
                            ), 
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_dropdown][border-right-width]', 
                                'value' => isset($design['switcher_dropdown']['border-right-width']) ? $design['switcher_dropdown']['border-right-width'] : '', 
                                'placeholder' => 'right'
                            ), 
                        )
                    ),
                )
            ),
            'switcher_dropdown_border_radius' => array(
                'name' => __('Border Radius', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 5px',
                'id' => 'design[switcher_dropdown][border-radius]',
                'default' => isset($design['switcher_dropdown']['border-radius']) ? $design['switcher_dropdown']['border-radius'] : '',
                'placeholder' => '5px',
                'class' => 'eccw-input ', // Add custom class here
            ),
            'switcher_dropdown_padding' => array(
                'name' => __('Padding', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 2px 2px 2px 2px',
                'default' => isset($design['switcher_dropdown']['null']) ? $design['switcher_dropdown']['null'] : '',
                'class' => 'eccw-currency-switcher-dropdown-bg eccw-dimension-input', // Add custom class here
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

        $settings_dropdown_option = array(
            'eccw_dropdown_option_settings_tab_section_title' => array(
                'name' => __('Switcher Dropdown Item', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_dropdown_option_settings_tab_section_title'
            ),
            'switcher_dropdown_option_alignment' => array(
                'name' => __('Alignment', 'easy-currency'),
                'type' => 'select',
                'id' => 'design[switcher_dropdown_option][justify-content]',
                'options' => array(
                    'start' => 'Left',
                    'center' => 'Center',
                    'right' => 'Right'
                ),
                'default' => isset($design['switcher_dropdown_option']['justify-content']) ? $design['switcher_dropdown_option']['justify-content'] : 'center',
                'class' => 'eccw-text-input ', // Add custom class here
            ),
            'switcher_dropdown_option_bg' => array(
                'name' => __('Background Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown_option][background]',
                'default' => isset($design['switcher_dropdown_option']['background']) ? $design['switcher_dropdown_option']['background'] : '',
                'class' => 'eccw-color-input ', // Add custom class here
            ),
            'switcher_dropdown_option_hover_bg' => array(
                'name' => __('Hover Background Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown_option_hover][background]',
                'default' => isset($design['switcher_dropdown_option_hover']['background']) ? $design['switcher_dropdown_option_hover']['background'] : '',
                'class' => 'eccw-color-input ', // Add custom class here
            ),
            'switcher_dropdown_option_color' => array(
                'name' => __('Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown_option][color]',
                'default' => isset($design['switcher_dropdown_option']['color']) ? $design['switcher_dropdown_option']['color'] : '',
                'class' => 'eccw-color-input ', // Add custom class here
            ),
            'switcher_dropdown_option_hover_color' => array(
                'name' => __('Hover Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown_option_hover][color]',
                'default' => isset($design['switcher_dropdown_option_hover']['color']) ? $design['switcher_dropdown_option_hover']['color'] : '',
                'class' => 'eccw-color-input ', // Add custom class here
            ),
            'switcher_dropdown_option_border_color' => array(
                'name' => __('Border Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown_option][border-color]',
                'default' => isset($design['switcher_dropdown_option']['border-color']) ? $design['switcher_dropdown_option']['border-color'] : '',
                'class' => 'eccw-color-input ', // Add custom class here
            ),
            'switcher_dropdown_option_hover_border_color' => array(
                'name' => __('Hover Border Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown_option_hover][border-color]',
                'default' => isset($design['switcher_dropdown_option_hover']['border-color']) ? $design['switcher_dropdown_option_hover']['border-color'] : '',
                'class' => 'eccw-color-input ', // Add custom class here
            ),
            'switcher_dropdown_option_border' => array(
                'name' => __('Border Width', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 2px 2px 2px 2px',
                'default' => isset($design['switcher_dropdown_option']['null']) ? $design['switcher_dropdown_option']['null'] : '',
                'class' => 'eccw-currency-switcher-dropdown_option-bg eccw-dimension-input', // Add custom class here
                'custom_attributes' => array(
                    'unit' => 'px',
                    'fields' => wp_json_encode(
                        array(
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_dropdown_option][border-top-width]', 
                                'value' => isset($design['switcher_dropdown_option']['border-top-width']) ? $design['switcher_dropdown_option']['border-top-width'] : '', 
                                'placeholder' => 'top'
                            ), 
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_dropdown_option][border-left-width]', 
                                'value' => isset($design['switcher_dropdown_option']['border-left-width']) ? $design['switcher_dropdown_option']['border-left-width'] : '', 
                                'placeholder' => 'left'
                            ), 
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_dropdown_option][border-bottom-width]', 
                                'value' => isset($design['switcher_dropdown_option']['border-bottom-width']) ? $design['switcher_dropdown_option']['border-bottom-width'] : '', 
                                'placeholder' => 'bottom'
                            ), 
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_dropdown_option][border-right-width]', 
                                'value' => isset($design['switcher_dropdown_option']['border-right-width']) ? $design['switcher_dropdown_option']['border-right-width'] : '', 
                                'placeholder' => 'right'
                            ), 
                        )
                    ),
                )
            ),
            'switcher_dropdown_option_border_radius' => array(
                'name' => __('Border Radius', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 5px',
                'id' => 'design[switcher_dropdown_option][border-radius]',
                'default' => isset($design['switcher_dropdown_option']['border-radius']) ? $design['switcher_dropdown_option']['border-radius'] : '',
                'placeholder' => '5px',
                'class' => 'eccw-input ', // Add custom class here
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
                'class' => 'eccw-currency-switcher-dropdown_option-bg eccw-dimension-input', // Add custom class here
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
            'switcher_dropdown_option_flag_size' => array(
                'name' => __('Flag Size (Width)', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 15px',
                'id' => 'design[switcher_dropdown_option_flag][width]',
                'default' => isset($design['switcher_dropdown_option_flag']['width']) ? $design['switcher_dropdown_option_flag']['width'] : '',
                'placeholder' => '15px',
                'class' => 'eccw-input'
            ),
            'eccw_dropdown_option_settings_tab_section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_dropdown_option_settings_tab_section_end'
            )
        );


        $all_settings = array_merge($settings, $settings_dropdown, $settings_dropdown_option );
        

        return $all_settings;

    }

    public function get_eccw_settings_modal_switcher_tab_fields( $current_shortcodeId = null ) {
        // Retrieve saved settings
        $saved_settings = get_option('eccw_switcher_styles');

        $design = isset($saved_settings[$current_shortcodeId]) ? $saved_settings[$current_shortcodeId] : [];

        $layout_style = array(
            'section_title_layout_style' => array(
                'name' => __('Switcher Layout Style', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_settings_tab_layout_section_title'
            ),
            'switcher_layout_style' => array(
                'name' => __('Switcher Display View', 'easy-currency'),
                'type' => 'select',
                'id' => 'design[switcher_layout_view_option][layout_style]',
                'options' => array(
                    'dropdown' => 'Dropdown',
                    'side' => 'Side',
                ),
                'default' => isset($design['switcher_layout_view_option']['layout_style']) ? $design['switcher_layout_view_option']['layout_style'] : 'dropdown',
                'class' => 'eccw-text-input ', // Add custom class here
            ),
            'switcher_template' => array(
                'name'     => __('Template', 'easy-currency'),
                'type'     => 'template_preview',
                'id'       => 'design[switcher_dropdown_option][template]',
                'desc'     => __('Choose your currency switcher template', 'easy-currency'),
                'default'  => isset($design['switcher_dropdown_option']['template']) ? $design['switcher_dropdown_option']['template'] : 'eccw_template_1',
                'value'    => isset($design['switcher_dropdown_option']['template']) ? $design['switcher_dropdown_option']['template'] : 'eccw_template_1',
            ),
            'layout_section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_settings_tab_layout_section_end'
            )
        );

        $settings = array(
            'section_title' => array(
                'name' => __('Switcher Button', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_settings_tab_section_title'
            ),
            'switcher_button_width' => array(
                'name' => __('Width', 'easy-currency'),
                'type' => 'number',
                'id' => 'design[switcher_button][width]',
                'default' => isset($design['switcher_button']['width']) ? str_replace('px', '', $design['switcher_button']['width']) : '52',
                'class' => 'eccw-currency-switcher-button-width eccw-rang-input', // Add custom class here
                'custom_attributes' => array(
                    'unit' => 'px',
                )
            ),
            'switcher_button_bg' => array(
                'name' => __('Background Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_button][background]',
                'default' => isset($design['switcher_button']['background']) ? $design['switcher_button']['background'] : '',
                'class' => 'eccw-color-input ', // Add custom class here
            ),
            'switcher_button_border_color' => array(
                'name' => __('Border Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_button][border-color]',
                'default' => isset($design['switcher_button']['border-color']) ? $design['switcher_button']['border-color'] : '',
                'class' => 'eccw-color-input ', // Add custom class here
            ),
            'switcher_button_border' => array(
                'name' => __('Border Width', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 2px 2px 2px 2px',
                'default' => isset($design['switcher_button']['null']) ? $design['switcher_button']['null'] : '',
                'class' => 'eccw-currency-switcher-button-bg eccw-dimension-input', // Add custom class here
                'custom_attributes' => array(
                    'unit' => 'px',
                    'fields' => wp_json_encode(
                        array(
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_button][border-top-width]', 
                                'value' => isset($design['switcher_button']['border-top-width']) ? $design['switcher_button']['border-top-width'] : '', 
                                'placeholder' => 'top'
                            ), 
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_button][border-left-width]', 
                                'value' => isset($design['switcher_button']['border-left-width']) ? $design['switcher_button']['border-left-width'] : '', 
                                'placeholder' => 'left'
                            ), 
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_button][border-bottom-width]', 
                                'value' => isset($design['switcher_button']['border-bottom-width']) ? $design['switcher_button']['border-bottom-width'] : '', 
                                'placeholder' => 'bottom'
                            ), 
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_button][border-right-width]', 
                                'value' => isset($design['switcher_button']['border-right-width']) ? $design['switcher_button']['border-right-width'] : '', 
                                'placeholder' => 'right'
                            ), 
                        )
                    ),
                )
            ),
            'switcher_button_border_radius' => array(
                'name' => __('Border Radius', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 5px',
                'id' => 'design[switcher_button][border-radius]',
                'default' => isset($design['switcher_button']['border-radius']) ? $design['switcher_button']['border-radius'] : '',
                'placeholder' => '5px',
                'class' => 'eccw-input ', // Add custom class here
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
                'class' => 'eccw-currency-switcher-button-bg eccw-dimension-input', // Add custom class here
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
            'section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_settings_tab_section_end'
            )
        );

        $settings_dropdown = array(
            'eccw_dropdown_settings_tab_section_title' => array(
                'name' => __('Switcher Dropdown', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_dropdown_settings_tab_section_title'
            ),
            'switcher_dropdown_width' => array(
                'name' => __('Width', 'easy-currency'),
                'type' => 'number',
                'id' => 'design[switcher_dropdown][width]',
                'default' => isset($design['switcher_dropdown']['width']) ? str_replace('px', '', $design['switcher_dropdown']['width']) : '180',
                'class' => 'eccw-currency-switcher-dropdown-width eccw-rang-input', // Add custom class here
                'custom_attributes' => array(
                    'unit' => 'px',
                )
            ),
            'switcher_dropdown_bg' => array(
                'name' => __('Background Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown][background]',
                'default' => isset($design['switcher_dropdown']['background']) ? $design['switcher_dropdown']['background'] : 'transparent',
                'class' => 'eccw-color-input ', // Add custom class here
            ),
            'switcher_dropdown_border_color' => array(
                'name' => __('Border Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown][border-color]',
                'default' => isset($design['switcher_dropdown']['border-color']) ? $design['switcher_dropdown']['border-color'] : '',
                'class' => 'eccw-color-input ', // Add custom class here
            ),
            'switcher_dropdown_border' => array(
                'name' => __('Border Width', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 2px 2px 2px 2px',
                'default' => isset($design['switcher_dropdown']['null']) ? $design['switcher_dropdown']['null'] : '',
                'class' => 'eccw-currency-switcher-dropdown-bg eccw-dimension-input', // Add custom class here
                'custom_attributes' => array(
                    'unit' => 'px',
                    'fields' => wp_json_encode(
                        array(
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_dropdown][border-top-width]', 
                                'value' => isset($design['switcher_dropdown']['border-top-width']) ? $design['switcher_dropdown']['border-top-width'] : '', 
                                'placeholder' => 'top'
                            ), 
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_dropdown][border-left-width]', 
                                'value' => isset($design['switcher_dropdown']['border-left-width']) ? $design['switcher_dropdown']['border-left-width'] : '', 
                                'placeholder' => 'left'
                            ), 
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_dropdown][border-bottom-width]', 
                                'value' => isset($design['switcher_dropdown']['border-bottom-width']) ? $design['switcher_dropdown']['border-bottom-width'] : '', 
                                'placeholder' => 'bottom'
                            ), 
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_dropdown][border-right-width]', 
                                'value' => isset($design['switcher_dropdown']['border-right-width']) ? $design['switcher_dropdown']['border-right-width'] : '', 
                                'placeholder' => 'right'
                            ), 
                        )
                    ),
                )
            ),
            'switcher_dropdown_border_radius' => array(
                'name' => __('Border Radius', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 5px',
                'id' => 'design[switcher_dropdown][border-radius]',
                'default' => isset($design['switcher_dropdown']['border-radius']) ? $design['switcher_dropdown']['border-radius'] : '',
                'placeholder' => '5px',
                'class' => 'eccw-input ', // Add custom class here
            ),
            'switcher_dropdown_padding' => array(
                'name' => __('Padding', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 2px 2px 2px 2px',
                'default' => isset($design['switcher_dropdown']['null']) ? $design['switcher_dropdown']['null'] : '',
                'class' => 'eccw-currency-switcher-dropdown-bg eccw-dimension-input', // Add custom class here
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

        $settings_dropdown_option = array(
            'eccw_dropdown_option_settings_tab_section_title' => array(
                'name' => __('Switcher Dropdown Item', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_dropdown_option_settings_tab_section_title'
            ),
            'switcher_dropdown_option_alignment' => array(
                'name' => __('Alignment', 'easy-currency'),
                'type' => 'select',
                'id' => 'design[switcher_dropdown_option][justify-content]',
                'options' => array(
                    'start' => 'Left',
                    'center' => 'Center',
                    'right' => 'Right'
                ),
                'default' => isset($design['switcher_dropdown_option']['justify-content']) ? $design['switcher_dropdown_option']['justify-content'] : 'center',
                'class' => 'eccw-text-input ', // Add custom class here
            ),
            'switcher_dropdown_option_bg' => array(
                'name' => __('Background Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown_option][background]',
                'default' => isset($design['switcher_dropdown_option']['background']) ? $design['switcher_dropdown_option']['background'] : '',
                'class' => 'eccw-color-input ', // Add custom class here
            ),
            'switcher_dropdown_option_hover_bg' => array(
                'name' => __('Hover Background Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown_option_hover][background]',
                'default' => isset($design['switcher_dropdown_option_hover']['background']) ? $design['switcher_dropdown_option_hover']['background'] : '',
                'class' => 'eccw-color-input ', // Add custom class here
            ),
            'switcher_dropdown_option_color' => array(
                'name' => __('Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown_option][color]',
                'default' => isset($design['switcher_dropdown_option']['color']) ? $design['switcher_dropdown_option']['color'] : '',
                'class' => 'eccw-color-input ', // Add custom class here
            ),
            'switcher_dropdown_option_hover_color' => array(
                'name' => __('Hover Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown_option_hover][color]',
                'default' => isset($design['switcher_dropdown_option_hover']['color']) ? $design['switcher_dropdown_option_hover']['color'] : '',
                'class' => 'eccw-color-input ', // Add custom class here
            ),
            'switcher_dropdown_option_border_color' => array(
                'name' => __('Border Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown_option][border-color]',
                'default' => isset($design['switcher_dropdown_option']['border-color']) ? $design['switcher_dropdown_option']['border-color'] : '',
                'class' => 'eccw-color-input ', // Add custom class here
            ),
            'switcher_dropdown_option_hover_border_color' => array(
                'name' => __('Hover Border Color', 'easy-currency'),
                'type' => 'text',
                'id' => 'design[switcher_dropdown_option_hover][border-color]',
                'default' => isset($design['switcher_dropdown_option_hover']['border-color']) ? $design['switcher_dropdown_option_hover']['border-color'] : '',
                'class' => 'eccw-color-input ', // Add custom class here
            ),
            'switcher_dropdown_option_border' => array(
                'name' => __('Border Width', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 2px 2px 2px 2px',
                'default' => isset($design['switcher_dropdown_option']['null']) ? $design['switcher_dropdown_option']['null'] : '',
                'class' => 'eccw-currency-switcher-dropdown_option-bg eccw-dimension-input', // Add custom class here
                'custom_attributes' => array(
                    'unit' => 'px',
                    'fields' => wp_json_encode(
                        array(
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_dropdown_option][border-top-width]', 
                                'value' => isset($design['switcher_dropdown_option']['border-top-width']) ? $design['switcher_dropdown_option']['border-top-width'] : '', 
                                'placeholder' => 'top'
                            ), 
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_dropdown_option][border-left-width]', 
                                'value' => isset($design['switcher_dropdown_option']['border-left-width']) ? $design['switcher_dropdown_option']['border-left-width'] : '', 
                                'placeholder' => 'left'
                            ), 
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_dropdown_option][border-bottom-width]', 
                                'value' => isset($design['switcher_dropdown_option']['border-bottom-width']) ? $design['switcher_dropdown_option']['border-bottom-width'] : '', 
                                'placeholder' => 'bottom'
                            ), 
                            array(
                                'type' => 'text', 
                                'name' => 'design[switcher_dropdown_option][border-right-width]', 
                                'value' => isset($design['switcher_dropdown_option']['border-right-width']) ? $design['switcher_dropdown_option']['border-right-width'] : '', 
                                'placeholder' => 'right'
                            ), 
                        )
                    ),
                )
            ),
            'switcher_dropdown_option_border_radius' => array(
                'name' => __('Border Radius', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 5px',
                'id' => 'design[switcher_dropdown_option][border-radius]',
                'default' => isset($design['switcher_dropdown_option']['border-radius']) ? $design['switcher_dropdown_option']['border-radius'] : '',
                'placeholder' => '5px',
                'class' => 'eccw-input ', // Add custom class here
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
                'class' => 'eccw-currency-switcher-dropdown_option-bg eccw-dimension-input', // Add custom class here
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
                'desc' => 'enter number with px. ex: 15px',
                'id' => 'design[switcher_option_flag][width]',
                'default' => isset($design['switcher_option_flag']['width']) ? $design['switcher_option_flag']['width'] : '',
                'placeholder' => '15px',
                'class' => 'eccw-input'
            ),
            'switcher_dropdown_option_flag_size_height' => array(
                'name' => __('Flag Size (Height)', 'easy-currency'),
                'type' => 'text',
                'desc' => 'enter number with px. ex: 15px',
                'id' => 'design[switcher_option_flag][height]',
                'default' => isset($design['switcher_option_flag']['height']) ? $design['switcher_option_flag']['height'] : '',
                'placeholder' => '15px',
                'class' => 'eccw-input'
            ),
            'eccw_flag_tab_section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_flag_tab_section_end'
            )
        );

        $all_settings = array_merge($layout_style, $settings, $settings_dropdown, $settings_dropdown_option, $flag_style);
        

        return $all_settings;

    }

    public function get_eccw_settings_modal_switcher_display_option_fields( $current_shortcodeId = null ) {
        // Retrieve saved settings
        $saved_settings = get_option('eccw_switcher_styles');

        $design = isset($saved_settings[$current_shortcodeId]) ? $saved_settings[$current_shortcodeId] : [];

        error_log(print_r( $design, true ) );

        $switcher_elements_display_wrapper_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-section eccw-elements-display">'
            )
        );
        $switcher_elements_display = array(
            'eccw_elements_style_title' => array(
                'name' => __('Switcher Elements Settings', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_switcher_elements_settings'
            ),
            'flag_visibility' => array(
                'title' => __('Enable Flag', 'easy-currency'),
                'id'    => 'design[eccw_switcher_flag_show_hide]',
                'type'  => 'switcher',
                'default' => isset($design['eccw_switcher_flag_show_hide']) ? $design['eccw_switcher_flag_show_hide'] : 'yes',
                'desc'  => __('Show flag on the currency switcher.', 'easy-currency'),
            ),
            'eccw_switcher_ele_currency_name' => array(
                'title' => __('Enable Currency Name', 'easy-currency'),
                'id'    => 'design[eccw_switcher_currency_name_show_hide]',
                'type'  => 'switcher',
                'default' => isset($design['eccw_switcher_currency_name_show_hide']) ? $design['eccw_switcher_currency_name_show_hide'] : 'yes',
                'desc'  => __('Show Currency Name on the currency switcher.', 'easy-currency'),
            ),
            'eccw_switcher_ele_currency_symbol' => array(
                'title' => __('Enable Currency Symbol', 'easy-currency'),
                'id'    => 'design[eccw_switcher_currency_symbol_show_hide]',
                'type'  => 'switcher',
                'default' => isset($design['eccw_switcher_currency_symbol_show_hide']) ? $design['eccw_switcher_currency_symbol_show_hide'] : 'yes',
                'desc'  => __('Show Currency Symbol on the currency switcher.', 'easy-currency'),
            ),
            'eccw_switcher_ele_currency_code' => array(
                'title' => __('Enable Currency Code', 'easy-currency'),
                'id'    => 'design[eccw_switcher_currency_code_show_hide]',
                'type'  => 'switcher',
                'default' => isset($design['eccw_switcher_currency_code_show_hide']) ? $design['eccw_switcher_currency_code_show_hide'] : 'yes',
                'desc'  => __('Show Currency Code on the currency switcher.', 'easy-currency'),
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

        $switcher_dropdown_display_wrapper_start = array(
            array(
                'type' => 'html',
                'html' => '<div class="eccw-section eccw-dropdown-display">'
            )
        );

        $switcher_dropdown_display = array(
            'eccw_switcher_dropdown_style_title' => array(
                'name' => __('Switcher Dropdown Elements Settings', 'easy-currency'),
                'type' => 'title',
                'desc' => '',
                'id' => 'eccw_switcher_dropdown_elements_settings'
            ),
            'eccw_dropdown_flag_visibility' => array(
                'title' => __('Enable Flag', 'easy-currency'),
                'id'    => 'design[eccw_switcher_dropdown_flag_show_hide]',
                'type'  => 'switcher',
                'default' => isset($design['eccw_switcher_dropdown_flag_show_hide']) ? $design['eccw_switcher_dropdown_flag_show_hide'] : 'yes',
                'desc'  => __('Show flag on the currency switcher.', 'easy-currency'),
                
            ),
            'eccw_switcher_dropdown_ele_currency_name' => array(
                'title' => __('Enable Currency Name', 'easy-currency'),
                'id'    => 'design[eccw_switcher_dropdown_currency_name_show_hide]',
                'type'  => 'switcher',
                'default' => isset($design['eccw_switcher_dropdown_currency_name_show_hide']) ? $design['eccw_switcher_dropdown_currency_name_show_hide'] : 'yes',
                'desc'  => __('Show Currency Name on the currency switcher.', 'easy-currency'),
            ),
            'eccw_switcher_dropdown_ele_currency_symbol' => array(
                'title' => __('Enable Currency Symbol', 'easy-currency'),
                'id'    => 'design[eccw_switcher_dropdown_currency_symbol_show_hide]',
                'type'  => 'switcher',
                'default' => isset($design['eccw_switcher_dropdown_currency_symbol_show_hide']) ? $design['eccw_switcher_dropdown_currency_symbol_show_hide'] : 'yes',
                'desc'  => __('Show Currency Symbol on the currency switcher.', 'easy-currency'),
            ),
            'eccw_switcher_dropdown_ele_currency_code' => array(
                'title' => __('Enable Currency Code', 'easy-currency'),
                'id'    => 'design[eccw_switcher_dropdown_currency_code_show_hide]',
                'type'  => 'switcher',
                'default' => isset($design['eccw_switcher_dropdown_currency_code_show_hide']) ? $design['eccw_switcher_dropdown_currency_code_show_hide'] : 'yes',
                'desc'  => __('Show Currency Code on the currency switcher.', 'easy-currency'),
            ),

            'eccw_switcher_dropdown_selements_tab_section_end' => array(
                'type' => 'sectionend',
                'id' => 'eccw_switcher_dropdown_elements_tab_section_end'
            )
        );

         $switcher_dropdown_display_wrapper_end = array(
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
                'title' => __('Custom Position Settings', 'wc-custom-fields'),
                'type'  => 'title',
                'desc'  => __('Configure the custom position fields for your products.', 'wc-custom-fields'),
                'id'    => 'custom_position_options',
            ),

            'vertical' => array(
                'name'   => __('Vertical (%)', 'wc-custom-fields'),
                'id'    => 'design[eccw_switcher_elements_vertical]',
                'type'    => 'slider',
                'min'     => -1000,
                'max'     => 1000,
                'step'    => 1,
                'default' => isset($design['eccw_switcher_elements_vertical']) ? $design['eccw_switcher_elements_vertical'] : '50',
                'desc'    => __('Set the vertical position in percentage.', 'wc-custom-fields'),
                
            ),

            'horizontal' => array(
                'name'   => __('Horizontal (PX)', 'wc-custom-fields'),
                'id'    => 'design[eccw_switcher_elements_horizontal]',
                'type'    => 'slider',
                'min'     => -1000,
                'max'     => 1000,
                'step'    => 1,
                'default' => isset($design['eccw_switcher_elements_horizontal']) ? $design['eccw_switcher_elements_horizontal'] : 0,
                'desc'    => __('Set the horizontal position in pixels.', 'wc-custom-fields'),
            ),

            'horizontal_hover' => array(
                'name'   => __('Horizontal Hover (PX)', 'wc-custom-fields'),
                'id'    => 'design[eccw_switcher_elements_horizontal_hover]',
                'type'    => 'slider',
                'min'     => -1000,
                'max'     => 1000,
                'step'    => 1,
                'default' => isset($design['eccw_switcher_elements_horizontal_hover']) ? $design['eccw_switcher_elements_horizontal_hover'] : 0,
                'desc'    => __('Set the horizontal position on hover in pixels.', 'wc-custom-fields'),
            ),

            'item_move_horizontal' => array(
                'name'   => __('Item Move Horizontal (PX)', 'wc-custom-fields'),
                'id'    => 'design[eccw_switcher_elements_item_move_horizontal]',
                'type'    => 'slider',
                'min'     => -1000,
                'max'     => 1000,
                'step'    => 1,
                'default' => isset($design['eccw_switcher_elements_item_move_horizontal']) ? $design['eccw_switcher_elements_item_move_horizontal'] : 0,
                'desc'    => __('Set the horizontal movement of the item in pixels.', 'wc-custom-fields'),
                'desc_tip' => 'hello faridmia'
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

        $all_settings = array_merge( $switcher_elements_display_wrapper_start, $switcher_elements_display,$switcher_elements_display_wrapper_end,  $switcher_dropdown_display_wrapper_start, $switcher_dropdown_display,$switcher_dropdown_display_wrapper_end, $switcher_position_wrapper_start, $switcher_position,$switcher_position_wrapper_end );
        

        return $all_settings;

    }


    public function eccw_settings_tab_settings() {

        $ECCW_CURRENCY_SERVER = new ECCW_CURRENCY_SERVER();
        $currency_countries = $ECCW_CURRENCY_SERVER->eccw_get_currency_countries();
        $eccw_currency_settings = get_option('eccw_currency_settings', []);
        $default_currency = isset($eccw_currency_settings['default_currency']) && !empty($eccw_currency_settings['default_currency']) ? $eccw_currency_settings['default_currency'] : 'usd';

        wp_nonce_field( 'eccw_update_settings', 'eccw_nonce');

        ?>
        <div class="eccw-settings-tabs-container">
            <div class="tabs eccw-settings-tabs">
                <div class="ajax-loader">
                    <img src="<?php echo esc_url(ECCW_PL_URL.'admin/assets/img/ajax-loader.gif'); ?>" alt="Ajax Loader">
                </div>
                <ul id="tabs-nav">
                    <li><a href="#tab_currency">Currencies</a></li>
                    <li><a href="#tab_currency_options">Options</a></li>
                    <li><a href="#tab_currency_design">Design</a></li>
                    
                    <li><a href="#tab_currency_usage">Usage</a></li>
                    <li><a href="#tab_currency_switcher_shortcode">Switcher Shortcode</a></li>
                </ul>
                <div class="tab-contents-wrapper">
                    <div id="tab_currency" class="tab-content">
                        <div class="alert alert-error"><p class="eccw-err-msg"></p></div>
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
                                        $decimal_separator = isset($currency_data['decimal_separator']) ? $currency_data['decimal_separator'] : '';
                                        $thousand_separator = isset($currency_data['thousand_separator']) ? $currency_data['thousand_separator'] : '';
                                        $description = isset($currency_data['description']) ? $currency_data['description'] : '';
                                
                                        echo '<tr>';
                                        echo '<td><input type="radio" name="eccw_currency_table[default]" value="' . esc_attr($currency_code) . '"' . checked($currency_code, $default_currency, false) . ' /></td>';
                                        echo '<td><select name="eccw_currency_table[' . esc_attr($index) . '][code]">';
                                            
                                            foreach ($currency_countries as $key => $value) {
                                                echo '<option value="'. esc_attr($key) .'"' . selected($currency_code ?? '', $key, false) . '>'. esc_attr($key) .'</option>';
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
                                }
                                    else {
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
                    <div id="tab_currency_design" class="tab-content" style="display: none;">
                        <?php woocommerce_admin_fields($this->get_eccw_settings_design_tab_fields()); ?>
                    </div>
                    <div id="tab_currency_usage" class="tab-content">
                        <div class="alert alert-error"><p class="eccw-err-msg"></p></div>
                        <h2><?php echo esc_html__('How to use this converter?', 'easy-currency') ?></h2>
                        <p><label><?php echo esc_html__('Shortcode :', 'easy-currency') ?> </label> [eccw_currency_switcher]</p>
                        <p><label><?php echo esc_html__('Shortcode in php :', 'easy-currency') ?> </label> echo do_shortcode('[eccw_currency_switcher]')</p>
                        <p><label><?php echo esc_html__('Shortcode in php :', 'easy-currency') ?> </label> echo do_shortcode("[eccw_currency_switcher eccw_position='left']")</p>
                        <p><label><?php echo esc_html__('Shortcode in php :', 'easy-currency') ?> </label> echo do_shortcode("[eccw_currency_switcher eccw_position='right']")</p>
                        <p><label><?php echo esc_html__('Elementor widget :', 'easy-currency') ?> </label> Easy Currency Switcher</p>
                    </div>
                    <div id="tab_currency_switcher_shortcode" class="tab-content">
                        <div class="alert alert-error"><p class="eccw-err-msg"></p></div>
                                
                        <div class="eccw-designer-container">
                            <div class="eccw-designer-header">
                                <h2><?php echo esc_html__('Switcher Generator', 'easy-currency') ?></h2>
                                <button ><?php echo esc_html__('+ Create', 'easy-currency') ?></button>
                            </div>

                            <div class="eccw-designer-list">
                                <?php 
                                    global $ECCW_Admin_Ajax;
                                    $shortcodes = $ECCW_Admin_Ajax->eccw_get_all_shortcodes_cached();

                                    foreach ($shortcodes as $shortcode) { ?>
                                        <div class="eccw-designer-card">
                                            <div class="eccw-designer-info">
                                                <div class="eccw-shortcode-box">
                                                    <input 
                                                        type="text" 
                                                        readonly 
                                                        class="eccw-shortcode-input" 
                                                        value="<?php echo esc_html($shortcode['shortcode']); ?>" 
                                                    />
                                                    <button type="button" class="eccw-copy-btn" title="Copy shortcode">
                                                        📋
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="eccw-designer-actions">
                                                <button class="eccw-btn-edit" data-id="<?php echo esc_attr($shortcode['id']); ?>"><?php echo esc_html__('Edit', 'easy-currency') ?></button>
                                                <button class="eccw-btn-delete" data-id="<?echo  esc_attr($shortcode['id']); ?>"><?php echo esc_html__('Delete', 'easy-currency') ?></button>
                                            </div>
                                        </div>
                                    <?php }
                                ?>
                            </div>
                        </div>

                        <div id="eccw-style-modal-switcher" class="eccw-style-modal-switcher" style="display: none;">

                            <div class="eccw-style-modal-switcher-content">
                                <span class="eccw-style-modal-switcher-close">&times;</span>
                                <input type="hidden" id="eccw-style-modal-switcher-id" name="ëccw_shortcode_id" value=""/>

                                <div class="eccw-tabs-wrapper">
                                    <button class="eccw-tab-btn active" data-tab="eccw_general_tab">General</button>
                                    <button class="eccw-tab-btn" data-tab="eccw_display_option_tab">Display Option</button>
                                </div>
                                
                                <div  class="eccw-style-modal-switcher-form" data-eccwtab="">
                                </div>
                                
                                <div class="eccw-button-wrapper">
                                    <button type="submit" class="eccw-style-modal-switcher-save-closebtn" disabled><?php echo esc_html__('Save & Close', 'easy-currency') ?></button>
                                    <button type="submit" class="eccw-style-modal-switcher-save-btn" disabled><?php echo esc_html__('Apply Now', 'easy-currency') ?></button>
                                </div> 
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
    }
    

    public function save_eccw_settings_tab_settings() {
        // Save repeatable fields data
        if (isset($_POST['eccw_currency_table'])) {
            
            check_admin_referer( 'eccw_update_settings', 'eccw_nonce' );

            $data = map_deep( wp_unslash($_POST['eccw_currency_table']), 'sanitize_text_field' ) ?? [];
            
            $default_currency = isset($data['default']) && !empty($data['default']) ? $data['default'] : 'usd';
            $currency_settings = array(
                'default_currency' => $default_currency,
            ); 

            if(isset($_POST['options'])){
                $options = map_deep( wp_unslash($_POST['options']), 'sanitize_text_field' ) ?? [];
                $currency_settings['options'] = $options;
            }

            if(isset($_POST['design'])){
                $design = map_deep( wp_unslash($_POST['design']), 'sanitize_text_field' ) ?? [];
                $currency_settings['design'] = $design;
            }

            $filtered_data = array_filter($data, function($row) {
                return !empty($row['code']) || !empty($row['rate']) || !empty($row['symbol_position']) || !empty($row['decimal']) || !empty($row['separator']) || !empty($row['description']);
            });
            
            // Re-index array to ensure sequential keys
            $filtered_data = array_values($filtered_data);
            $currency_settings['eccw_currency_table'] = $filtered_data;

            // Update the option with filtered data
            update_option('eccw_currency_settings', $currency_settings);

            
        }
    }

    public function eccw_add_easy_currency_submenu_redirect() {
        add_submenu_page(
            'woocommerce',
            'Easy Currency',
            'Easy Currency',
            'manage_woocommerce',
            'eccw-easy-currency',
            array( $this, 'eccw_redirect_to_settings_tab')
        );
    }

    public function eccw_redirect_to_settings_tab() {
        wp_safe_redirect(admin_url('admin.php?page=wc-settings&tab=eccw_settings_tab'));
        exit;
    }
        
}

ECCW_admin_settings::get_instance();

