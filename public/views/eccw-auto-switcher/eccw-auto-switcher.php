<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly.
class ECCW_Auto_Switcher
{

    // Store the single instance
    private static $instance = null;

    public $settings = [];

    /**
     * Get the single instance of the class
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {

        $switcher_settings = get_option('eccw_currency_settings', []);

        $this->settings = array(
            'show_hide'                          => $switcher_settings['design']['eccw_show_hide_side_currency'] ?? 'yes',
            'eccw_template'                      => $switcher_settings['design']['switcher_sticky']['template'] ?? 'eccw-sticky-template-1',
            'show_pages'                         => $switcher_settings['design']['eccw_sticky_show_on_pages'] ?? array(),
            'sticky_position'                    => $switcher_settings['design']['eccw_position_alignment_toggle'] ?? 'right',
            'sticky_vertical'                    => $switcher_settings['design']['eccw_sticky_vertical'] ?? 'top',
            'sticky_horizontal'                  => $switcher_settings['design']['eccw_sticky_horizontal'] ?? '',
            'sticky_horizontal_hover'            => $switcher_settings['design']['eccw_sticky_horizontal_hover'] ?? '',
            'sticky_horizontal_item_hover'       => $switcher_settings['design']['eccw_sticky_item_move_horizontal'] ?? '',
            
            'flag_show_hide'                     => $switcher_settings['design']['eccw_sticky_flag_show_hide'] ?? 'yes',
            'currency_name_show_hide'            => $switcher_settings['design']['eccw_sticky_currency_name_show_hide'] ?? 'no',
            'symbol_show_hide'                   => $switcher_settings['design']['eccw_switcher_currency_symbol_show_hide'] ?? 'yes',
            'code_show_hide'                     => $switcher_settings['design']['eccw_sticky_currency_code_show_hide'] ?? 'yes',

            'sticky_option_bg'                   => $switcher_settings['design']['sticky_option_color']['background'] ?? '',
            'sticky_option_color'                => $switcher_settings['design']['sticky_option_color']['color'] ?? '',
            'sticky_option_hover_bg'             => $switcher_settings['design']['sticky_option_hover']['background'] ?? '',
            'sticky_option_hover_color'          => $switcher_settings['design']['sticky_option_hover']['color'] ?? '',
            'sticky_ccode_option_bg'             => $switcher_settings['design']['sticky_option_ccode_color']['background'] ?? '',
            'sticky_ccode_option_color'          => $switcher_settings['design']['sticky_option_ccode_color']['color'] ?? '',
            'sticky_ccode_option_hover_bg'       => $switcher_settings['design']['sticky_option_ccode_hover']['background'] ?? '',
            'sticky_ccode_option_hover_color'    => $switcher_settings['design']['sticky_option_ccode_hover']['color'] ?? '',
            'sticky_option_flag_size'            => $switcher_settings['design']['switcher_sticky_option_flag']['width'] ?? '',

            'sticky_option_active_bg'             => $switcher_settings['design']['sticky_option_active']['background'] ?? '',
            'sticky_option_active_color'          => $switcher_settings['design']['sticky_option_active']['color'] ?? '',
        );

        $this->settings = apply_filters('eccw_sticky_switcher_data', $this->settings);

        add_action('init', [$this, 'init_auto_switcher']);
    }

    /**
     * Example method to initialize auto switcher
     */
    public function init_auto_switcher()
    {
        add_action("wp_footer", array($this, "eccw_auto_switcher_render_raw_html"));
        add_action('wp_head', array($this, 'eccw_side_enqueue_all_dynamic_css'));
    }

    public function eccw_auto_switcher_render_raw_html()
    {

        $selected_pages = isset($this->settings['show_pages']) ? (array) $this->settings['show_pages'] : [];

        if (empty($selected_pages)) {
            $this->eccw_render_switcher_html();
            return;
        }

        foreach ($selected_pages as $page) {
            if (is_numeric($page) && is_page((int) $page)) {
                $this->eccw_render_switcher_html();
                return;
            }

            switch ($page) {
                case 'home':
                    if (is_home()) {
                        $this->eccw_render_switcher_html();
                        return;
                    }
                    break;

                case 'shop':
                    if (function_exists('is_shop') && is_shop()) {
                        $this->eccw_render_switcher_html();
                        return;
                    }
                    break;

                case 'woocommerce':
                    if (function_exists('is_woocommerce') && is_woocommerce()) {
                        $this->eccw_render_switcher_html();
                        return;
                    }
                    break;

                case 'category':
                    if (is_category()) {
                        $this->eccw_render_switcher_html();
                        return;
                    }
                    break;

                case 'front_page':
                    if (is_front_page()) {
                        $this->eccw_render_switcher_html();
                        return;
                    }
                    break;

                case 'product_category':
                    if (function_exists('is_product_category') && is_product_category()) {
                        $this->eccw_render_switcher_html();
                        return;
                    }
                    break;

                case 'cart':
                    if (function_exists('is_cart') && is_cart()) {
                        $this->eccw_render_switcher_html();
                        return;
                    }
                    break;

                case 'product':
                    if (function_exists('is_product') && is_product()) {
                        $this->eccw_render_switcher_html();
                        return;
                    }
                    break;

                case 'checkout':
                    if (function_exists('is_checkout') && is_checkout()) {
                        $this->eccw_render_switcher_html();
                        return;
                    }
                    break;

                case 'product_tag':
                    if (function_exists('is_product_tag') && is_product_tag()) {
                        $this->eccw_render_switcher_html();
                        return;
                    }
                    break;

                case 'blog':
                    if (is_home() || is_archive()) {
                        $this->eccw_render_switcher_html();
                        return;
                    }
                    break;
            }
        }
    }

    private function eccw_render_switcher_html()
    {
        include ECCW_PL_PATH . 'public/views/eccw-auto-switcher/eccw-switcher-template.php';
    }

    /**
     * Add custom CSS to the front-end
     */
    public function eccw_side_enqueue_all_dynamic_css()
    {
        $unit_fields = [
            'sticky_horizontal',
            'sticky_horizontal_hover',
            'sticky_horizontal_item_hover',
            'sticky_option_flag_size',
        ];

        foreach ($unit_fields as $field) {
            if (isset($this->settings[$field]) && is_numeric($this->settings[$field])) {
                $this->settings[$field] .= 'px';
            }
        }

        $template = $this->settings['eccw_template'];

        $template = str_replace('_', '-', $template);
        $pos = $this->settings['sticky_position']; 

        $styles = [];

        if( $pos == 'left') {
            $styles[".{$template}.easy-currency-switcher-auto-select.eccw-position-{$pos}"] = [
                'top'  => $this->settings['sticky_vertical'] . '%',
                'left' => $this->settings['sticky_horizontal'],
            ];
        } else {
            $styles[".{$template}.easy-currency-switcher-auto-select.eccw-position-{$pos}"] = [
                'top'  => $this->settings['sticky_vertical'] . '%',
                'right' => $this->settings['sticky_horizontal'],
            ];
        }
        
        $styles[".{$template}.easy-currency-switcher-auto-select.eccw-position-{$pos} .easy-currency-switcher-select.list li"] = [
            'transform' => "translateX(" . $this->settings['sticky_horizontal_hover'] . ")",
        ];
        $styles[".{$template}.easy-currency-switcher-auto-select.eccw-position-{$pos} .easy-currency-switcher-select.list li:hover, 
        ..{$template}.easy-currency-switcher-auto-select.eccw-position-{$pos} .easy-currency-switcher-select.list li.selected"] = [
            'transform' => "translateX(" . $this->settings['sticky_horizontal_item_hover'] . ")",
        ];
        

        $styles[".easy-currency-switcher-auto-select .easy-currency-switcher-select.list li img"] = [
            'width' => $this->settings['sticky_option_flag_size'],
        ];

        $styles[".{$template}.easy-currency-switcher-auto-select.eccw-position-{$pos} .easy-currency-switcher-select.list li"] = [
            'background' => $this->settings['sticky_option_bg'] ?? '',
        ];

        $styles[".{$template}.easy-currency-switcher-auto-select.eccw-position-{$pos} .easy-currency-switcher-select.list li span"] = [
            'color'      => $this->settings['sticky_option_color'] ?? '',
        ];

        $styles[".{$template}.easy-currency-switcher-auto-select.eccw-position-{$pos} .easy-currency-switcher-select.list li:hover"] = [
            'background' => $this->settings['sticky_option_hover_bg'] ?? '',
        ];

        $styles[".{$template}.easy-currency-switcher-auto-select.eccw-position-{$pos} .easy-currency-switcher-select.list li.selected"] = [
            'background' => $this->settings['sticky_option_active_bg'] ?? '',
        ];

        $styles[".{$template}.easy-currency-switcher-auto-select.eccw-position-{$pos} .easy-currency-switcher-select.list li:hover span"] = [
            'color'      => $this->settings['sticky_option_hover_color'] ?? '',
        ];

        $styles[".{$template}.easy-currency-switcher-auto-select.eccw-position-{$pos} .easy-currency-switcher-select.list li.selected span"] = [
            'color'      => $this->settings['sticky_option_active_color'] ?? '',
        ];

       if( $template == 'eccw-sticky-template-2') {
            $styles[".{$template}.easy-currency-switcher-auto-select.eccw-position-{$pos} .easy-currency-switcher-select.list li span.eccw-side-country-code"] = [
                'background' => $this->settings['sticky_ccode_option_bg'] ?? '',
                'color' => $this->settings['sticky_ccode_option_color'] ?? '',
            ];
             $styles[".{$template}.easy-currency-switcher-auto-select.eccw-position-{$pos} .easy-currency-switcher-select.list li:hover span.eccw-side-country-code, 
                .{$template}.easy-currency-switcher-auto-select.eccw-position-{$pos} .easy-currency-switcher-select.list li.selected span.eccw-side-country-code"] = [
                'background' => $this->settings['sticky_ccode_option_hover_bg'] ?? '',
                'color' => $this->settings['sticky_ccode_option_hover_color'] ?? '',
            ];
        }

        // Generate CSS
        $custom_style = eccw_generate_css($styles);

        if (!empty($custom_style)) {
            wp_register_style('eccw-sticky-switcher-side', false, array(), ECCW_VERSION);
            wp_enqueue_style('eccw-sticky-switcher-side');
            wp_add_inline_style('eccw-sticky-switcher-side', $custom_style);
        }
    }

}

$switcher_settings = get_option('eccw_currency_settings', []);

$show_hide = $switcher_settings['design']['eccw_show_hide_side_currency'] ?? 'no';

if ($show_hide === 'yes' || $show_hide === '1') {
    // Initialize the singleton
    ECCW_Auto_Switcher::get_instance();
}