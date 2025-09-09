<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly.

add_action('wp_enqueue_scripts', 'eccw_enqueue_all_dynamic_css', 999999);
function eccw_enqueue_all_dynamic_css()
{

    $switcher_settings = get_option('eccw_switcher_styles', []);
    $eccw_options = get_option('eccw_currency_settings');

    $all_css = '';

    if (!empty($switcher_settings) && is_array($switcher_settings)) {
        foreach ($switcher_settings as $shortcode_id => $style) {
            $unique_class = '.eccw-switcher-design' . sanitize_html_class($shortcode_id);
            if ( isset( $style['switcher_dropdown_option']['template'] ) ) {
                unset( $style['switcher_dropdown_option']['template'] );
            }

            if (isset($style['switcher_button']['width']) && $style['switcher_button']['width'] === '0px') {
                unset($style['switcher_button']['width']);
               
            }

            $dropdown_icon_color = !empty($style['switcher_button']['color']) ? $style['switcher_button']['color'] : '';
            $switcher_border = !empty($style['switcher_button']['border_control']) ? $style['switcher_button']['border_control'] : '';
            $dropdown_border_style = !empty($style['switcher_dropdown_border_style_option_control']) ? $style['switcher_dropdown_border_style_option_control'] : '';
            $dropdown_border_wrapper = !empty($style['switcher_dropdown_border_control']) ? $style['switcher_dropdown_border_control'] : '';

            if (isset($style['switcher_button']['border_control']) ) {
                unset($style['switcher_button']['border_control']);
            }

            $style_selectors = [
                'switcher_dropdown_option'       => $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-select li',
                'switcher_button' => 
                    $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-toggle',
                'switcher_dropdown'              => $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-select',
                'switcher_dropdown_option_hover' => $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-select li:hover, ' . $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-select li.selected',
                'switcher_option_flag' => $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-toggle .flag, ' . $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-select.list li img',

            ];

            foreach ($style_selectors as $key => $selector) {
               
                if (!empty($style[$key])) {
                    $all_css .= eccw_add_dynamic_css($style[$key], $selector);
                }
            }

            if( $dropdown_icon_color ) {
                $all_css .= $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-toggle .dropdown-icon::before, ' .
                            $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-toggle .dropdown-icon::after { background-color: ' . $dropdown_icon_color . '; }';
            }

        if ( !empty($switcher_border) && is_array($switcher_border) && $switcher_border['style'] != 'default') {

            foreach (['top','right','bottom','left'] as $side) {
                if ( isset($switcher_border[$side]) && is_numeric($switcher_border[$side]) ) {
                    $switcher_border[$side] .= 'px';
                }
            }

            $all_css .= $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-toggle { ' .
                'border-top: '    . $switcher_border['top']    . ' ' . $switcher_border['style'] . ' ' . $switcher_border['color'] . '; ' .
                'border-right: '  . $switcher_border['right']  . ' ' . $switcher_border['style'] . ' ' . $switcher_border['color'] . '; ' .
                'border-bottom: ' . $switcher_border['bottom'] . ' ' . $switcher_border['style'] . ' ' . $switcher_border['color'] . '; ' .
                'border-left: '   . $switcher_border['left']   . ' ' . $switcher_border['style'] . ' ' . $switcher_border['color'] . '; ' .
            '}';
        }

        if ( !empty($dropdown_border_style) && is_array($dropdown_border_style) && $dropdown_border_style['style'] != 'default') {

            foreach (['top','right','bottom','left'] as $side) {
                if ( isset($dropdown_border_style[$side]) && is_numeric($dropdown_border_style[$side]) ) {
                    $dropdown_border_style[$side] .= 'px';
                }
            }

            $all_css .= $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-select li { ' .
                'border-top: '    . $dropdown_border_style['top']    . ' ' . $dropdown_border_style['style'] . ' ' . $dropdown_border_style['color'] . '; ' .
                'border-right: '  . $dropdown_border_style['right']  . ' ' . $dropdown_border_style['style'] . ' ' . $dropdown_border_style['color'] . '; ' .
                'border-bottom: ' . $dropdown_border_style['bottom'] . ' ' . $dropdown_border_style['style'] . ' ' . $dropdown_border_style['color'] . '; ' .
                'border-left: '   . $dropdown_border_style['left']   . ' ' . $dropdown_border_style['style'] . ' ' . $dropdown_border_style['color'] . '; ' .
            '}';
        }

        if ( !empty($dropdown_border_wrapper) && is_array($dropdown_border_wrapper) && $dropdown_border_wrapper['style'] != 'default') {

            foreach (['top','right','bottom','left'] as $side) {
                if ( isset($dropdown_border_wrapper[$side]) && is_numeric($dropdown_border_wrapper[$side]) ) {
                    $dropdown_border_wrapper[$side] .= 'px';
                }
            }

            $all_css .= $unique_class . ' .easy_currency_switcher_form .easy-currency-switcher-select.open { ' .
                'border-top: '    . $dropdown_border_wrapper['top']    . ' ' . $dropdown_border_wrapper['style'] . ' ' . $dropdown_border_wrapper['color'] . '; ' .
                'border-right: '  . $dropdown_border_wrapper['right']  . ' ' . $dropdown_border_wrapper['style'] . ' ' . $dropdown_border_wrapper['color'] . '; ' .
                'border-bottom: ' . $dropdown_border_wrapper['bottom'] . ' ' . $dropdown_border_wrapper['style'] . ' ' . $dropdown_border_wrapper['color'] . '; ' .
                'border-left: '   . $dropdown_border_wrapper['left']   . ' ' . $dropdown_border_wrapper['style'] . ' ' . $dropdown_border_wrapper['color'] . '; ' .
            '}';
        }

        }
    }

    if (!empty($all_css)) {
        wp_add_inline_style('eccw-style', $all_css);
    }
}

function eccw_add_dynamic_css($css_array, $element_class)
{
    if (!empty($css_array) && is_array($css_array)) {
        $custom_css = '';
        $properties = '';

        foreach ($css_array as $prop => $value) {
            $prop  = sanitize_key($prop);

            if (is_array($value)) {
                $value = implode(' ', array_map('wp_strip_all_tags', $value));
            } elseif (is_string($value)) {
                $value = wp_strip_all_tags($value);
            }

            $value = trim($value);

            if ($value === '' || $value === '0px') {
                continue;
            }

            if (is_numeric($value)) {
                $value = $value . 'px';
            }

            $properties .= "{$prop}: {$value};";
        }

        if ($properties !== '') {
            $custom_css = $element_class . ' {' . $properties . '}';
        }

        return $custom_css;
    }

    return '';
}