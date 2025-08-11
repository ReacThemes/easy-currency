<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

add_action( 'wp_enqueue_scripts', 'eccw_dynamic_css' );
function eccw_dynamic_css( $shortcode_id ) {

    $eccw_options = get_option('eccw_currency_settings');
    $design = isset($eccw_options['design']) && !empty($eccw_options['design']) ? $eccw_options['design'] : '';

    if (!empty($design)) {

        $custom_css = '';

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
        if (!empty($custom_css)) {
            wp_add_inline_style('eccw-style', $custom_css);
        }
    }

    $switcher_settings = get_option('eccw_switcher_styles', []);
    if (empty($switcher_settings[$shortcode_id])) {
        return '';
    }
    $style = $switcher_settings[$shortcode_id];

    error_log( print_r( $style, true ));

    $custom_css = '';
    $unique_class = '.eccw-switcher-design' . sanitize_html_class($shortcode_id);

    if (!empty($style['switcher_dropdown_option']) && is_array($style['switcher_dropdown_option'])) {
        $custom_css .= eccw_add_dynamic_css($style['switcher_dropdown_option'], $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-select li');
    }

    if (!empty($style['switcher_button']) && is_array($style['switcher_button'])) {
        $custom_css .= eccw_add_dynamic_css($style['switcher_button'], $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-toggle');
    }

    if (!empty($style['switcher_dropdown']) && is_array($style['switcher_dropdown'])) {
        $custom_css .= eccw_add_dynamic_css($style['switcher_dropdown'], $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-select');
    }

    if (!empty($style['switcher_dropdown_option_hover']) && is_array($style['switcher_dropdown_option_hover'])) {
        $custom_css .= eccw_add_dynamic_css($style['switcher_dropdown_option_hover'], $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-select li:hover, ' . $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-select li.selected');
    }

    if (!empty($style['switcher_option_flag']) && is_array($style['switcher_option_flag'])) {
        $custom_css .= eccw_add_dynamic_css($style['switcher_option_flag'], $unique_class . ' .flag');
    }

    return $custom_css;


}

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


// function eccw_generate_dynamic_css_for_shortcode($shortcode_id) {
//     $switcher_settings = get_option('eccw_switcher_styles', []);
//     if (empty($switcher_settings[$shortcode_id])) {
//         return '';
//     }
//     $style = $switcher_settings[$shortcode_id];

//     error_log( print_r( $style, true ));

//     $custom_css = '';
//     $unique_class = '.eccw-switcher-design' . sanitize_html_class($shortcode_id);

//     if (!empty($style['switcher_dropdown_option']) && is_array($style['switcher_dropdown_option'])) {
//         $custom_css .= eccw_add_dynamic_css($style['switcher_dropdown_option'], $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-select li');
//     }

//     if (!empty($style['switcher_button']) && is_array($style['switcher_button'])) {
//         $custom_css .= eccw_add_dynamic_css($style['switcher_button'], $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-toggle');
//     }

//     if (!empty($style['switcher_dropdown']) && is_array($style['switcher_dropdown'])) {
//         $custom_css .= eccw_add_dynamic_css($style['switcher_dropdown'], $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-select');
//     }

//     if (!empty($style['switcher_dropdown_option_hover']) && is_array($style['switcher_dropdown_option_hover'])) {
//         $custom_css .= eccw_add_dynamic_css($style['switcher_dropdown_option_hover'], $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-select li:hover, ' . $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-select li.selected');
//     }

//     if (!empty($style['switcher_option_flag']) && is_array($style['switcher_option_flag'])) {
//         $custom_css .= eccw_add_dynamic_css($style['switcher_option_flag'], $unique_class . ' .flag');
//     }


//     return $custom_css;
// }


add_action('wp_enqueue_scripts', 'eccw_enqueue_all_dynamic_css');
function eccw_enqueue_all_dynamic_css() {
    $switcher_settings = get_option('eccw_switcher_styles', []);
    if (empty($switcher_settings) || !is_array($switcher_settings)) {
        return;
    }

    $all_css = '';
    foreach ($switcher_settings as $shortcode_id => $style) {
        $all_css .= eccw_dynamic_css($shortcode_id);
    }

    if (!empty($all_css)) {
        wp_add_inline_style('eccw-style', $all_css); 
    }
}