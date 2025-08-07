<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

add_action( 'wp_enqueue_scripts', 'eccw_dynamic_css' );
function eccw_dynamic_css() {

    $eccw_options = get_option('eccw_currency_settings');
    $design = isset($eccw_options['design']) && !empty($eccw_options['design']) ? $eccw_options['design'] : '';

    if (!empty($design)) {

        $custom_css = '';

        function eccw_add_dynamic_css($css_array, $element_class){
            if (!empty($css_array) && is_array($css_array) && count($css_array) > 0) {
                $custom_css = $element_class . ' {';
    
                foreach ($css_array as $css_property => $value) {
                    // Sanitize the CSS property and value to prevent injection
                    $css_property = sanitize_text_field($css_property);
                    $value = sanitize_text_field($value);
    
                    if (!empty($css_property) && !empty($value)) {
                        $custom_css .= "{$css_property}: {$value};";
                    }
                }
    
                $custom_css .= '}';

                return $custom_css;
            }
        }

        $switcher_btn_css = isset($design['switcher_button']) && !empty($design['switcher_button']) ? $design['switcher_button'] : '';
        $switcher_btn_class = '.easy-currency-switcher .easy_currency_switcher_form .easy-currency-switcher-toggle';
        $custom_css .= eccw_add_dynamic_css($switcher_btn_css, $switcher_btn_class);
        

        $switcher_dropdown_css = isset($design['switcher_dropdown']) && !empty($design['switcher_dropdown']) ? $design['switcher_dropdown'] : '';
        $switcher_dropdown_class = '.easy-currency-switcher .easy_currency_switcher_form .easy-currency-switcher-select';
        $custom_css .= eccw_add_dynamic_css($switcher_dropdown_css, $switcher_dropdown_class);


        $switcher_dropdown_option_css = isset($design['switcher_dropdown_option']) && !empty($design['switcher_dropdown_option']) ? $design['switcher_dropdown_option'] : '';
        $switcher_dropdown_option_class = '.easy-currency-switcher .easy_currency_switcher_form .easy-currency-switcher-select li';
        $custom_css .= eccw_add_dynamic_css($switcher_dropdown_option_css, $switcher_dropdown_option_class);
       

        $switcher_dropdown_option_hover_css = isset($design['switcher_dropdown_option_hover']) && !empty($design['switcher_dropdown_option_hover']) ? $design['switcher_dropdown_option_hover'] : '';
        $switcher_dropdown_option_hover_class = '.easy-currency-switcher .easy_currency_switcher_form .easy-currency-switcher-select li:hover, .easy-currency-switcher .easy_currency_switcher_form .easy-currency-switcher-select li.selected';
        $custom_css .= eccw_add_dynamic_css($switcher_dropdown_option_hover_css, $switcher_dropdown_option_hover_class);


        $flag_css = isset($design['switcher_dropdown_option_flag']) && !empty($design['switcher_dropdown_option_flag']) ? $design['switcher_dropdown_option_flag'] : '';
        $flag_class = '.easy-currency-switcher .easy_currency_switcher_form .easy-currency-switcher .flag';
        $custom_css .= eccw_add_dynamic_css($flag_css, $flag_class);

        // switcher sticky css

        $sticky_position_css = isset($design['switcher_side_sticky_css']) && !empty($design['switcher_side_sticky_css']) ? $design['switcher_side_sticky_css'] : '';
        $sticky_pos_class = '.easy-currency-side-switcher .easy-currency-switcher-select';
        $custom_css .= eccw_add_dynamic_css($sticky_position_css, $sticky_pos_class);

        $sticky_position_hover_css = isset($design['switcher_side_sticky_css_hover']) && !empty($design['switcher_side_sticky_css_hover']) ? $design['switcher_side_sticky_css_hover'] : '';
        $sticky_pos_hover_class = '.easy-currency-side-switcher .easy-currency-switcher-select:hover,.easy-currency-side-switcher .easy-currency-switcher-select option.selected';
        $custom_css .= eccw_add_dynamic_css($sticky_position_hover_css, $sticky_pos_hover_class);

        


        if (!empty($custom_css)) {
            wp_add_inline_style('eccw-style', $custom_css);
        }
    }


    
}



