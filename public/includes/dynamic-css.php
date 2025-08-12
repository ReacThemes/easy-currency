<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

add_action('wp_enqueue_scripts', 'eccw_enqueue_all_dynamic_css');
function eccw_enqueue_all_dynamic_css() {
    $switcher_settings = get_option('eccw_switcher_styles', []);
    $eccw_options = get_option('eccw_currency_settings');
    
    $all_css = '';

    // if (!empty($eccw_options['design'])) {
    //     $design_selectors = [
    //         'switcher_button'                => '.easy-currency-switcher .easy_currency_switcher_form .easy-currency-switcher-toggle',
    //         'switcher_dropdown'              => '.easy-currency-switcher .easy_currency_switcher_form .easy-currency-switcher-select',
    //         'switcher_dropdown_option'       => '.easy-currency-switcher .easy_currency_switcher_form .easy-currency-switcher-select li',
    //         'switcher_dropdown_option_hover' => '.easy-currency-switcher .easy_currency_switcher_form .easy-currency-switcher-select li:hover, .easy-currency-switcher .easy_currency_switcher_form .easy-currency-switcher-select li.selected',
    //         'switcher_dropdown_option_flag'  => '.easy-currency-switcher .easy_currency_switcher_form .easy-currency-switcher .flag',
    //     ];

    //     foreach ($design_selectors as $key => $selector) {
    //         if (!empty($eccw_options['design'][$key])) {
    //             $all_css .= eccw_add_dynamic_css($eccw_options['design'][$key], $selector);
    //         }
    //     }
    // }

    
    if (!empty($switcher_settings) && is_array($switcher_settings)) {
        foreach ($switcher_settings as $shortcode_id => $style) {
            $unique_class = '.eccw-switcher-design' . sanitize_html_class($shortcode_id);

            $style_selectors = [
                'switcher_dropdown_option'       => $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-select li',
                'switcher_button'                => $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-toggle',
                'switcher_dropdown'              => $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-select',
                'switcher_dropdown_option_hover' => $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-select li:hover, ' . $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-select li.selected',
                'switcher_option_flag'           => $unique_class . ' .flag',
            ];

            foreach ($style_selectors as $key => $selector) {
                if (!empty($style[$key])) {
                    $all_css .= eccw_add_dynamic_css($style[$key], $selector);
                }
            }
        }
    }

    if (!empty($all_css)) {
        wp_add_inline_style('eccw-style', $all_css);
    }
}

function eccw_add_dynamic_css($css_array, $element_class){
    if (!empty($css_array) && is_array($css_array)) {
        $custom_css = $element_class . ' {';
        foreach ($css_array as $prop => $value) {
            $prop  = sanitize_key($prop); 
            $value = wp_strip_all_tags($value); 
            if ($prop && $value) {
                $custom_css .= "{$prop}: {$value};";
            }
        }
        $custom_css .= '}';
        return $custom_css;
    }
    return '';
}