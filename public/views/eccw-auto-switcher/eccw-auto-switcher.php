<?php 
class ECCW_Auto_Switcher {

    // Store the single instance
    private static $instance = null;

    public $settings = [];

    /**
     * Get the single instance of the class
     */
    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {

        $switcher_settings = get_option('eccw_currency_settings', []);

        $this->settings = array(
            'show_hide'                  => $switcher_settings['design']['eccw_show_hide_side_currency'],
            'eccw_template'              => $switcher_settings['design']['switcher_sticky']['template'],
            'show_pages'                 => $switcher_settings['design']['eccw_sticky_show_on_pages'],
            'sticky_position'            => $switcher_settings['design']['eccw_position_alignment_toggle'],
            'sticky_vertical'            => $switcher_settings['design']['eccw_switcher_elements_vertical'],
            'sticky_horizontal'          => $switcher_settings['design']['eccw_switcher_elements_horizontal'],
            'sticky_vertical_hover'      => $switcher_settings['design']['eccw_switcher_elements_horizontal_hover'],
            'sticky_horizontal_hover'    => $switcher_settings['design']['eccw_switcher_elements_item_move_horizontal'],
            'flag_show_hide'             => $switcher_settings['design']['eccw_sticky_flag_show_hide'],
            'currency_name_show_hide'    => $switcher_settings['design']['eccw_sticky_currency_name_show_hide'],
            'symbol_show_hide'           => $switcher_settings['design']['eccw_switcher_currency_symbol_show_hide'],
            'code_show_hide'             => $switcher_settings['design']['eccw_sticky_currency_code_show_hide'],
        );

        $this->settings = apply_filters('eccw_sticky_switcher_data', $this->settings);
       
        add_action('init', [ $this, 'init_auto_switcher' ]);
    }

    /**
     * Example method to initialize auto switcher
     */
    public function init_auto_switcher() {
        add_action("wp_footer", array( $this, "eccw_auto_switcher_render_raw_html") );
        add_action('wp_enqueue_scripts', array($this, 'eccw_side_enqueue_all_dynamic_css') );
    }

    public function eccw_auto_switcher_render_raw_html() {
   
        $selected_pages = isset($this->settings['show_pages']) ? (array) $this->settings['show_pages'] : [];

        if (empty($selected_pages)) {
            return;
        }

        foreach ($selected_pages as $page) {
            
            if (is_numeric($page) && is_page((int) $page)) {
                $this->eccw_render_switcher_html();
                return;
            }

            switch ($page) {
                case 'home':
                    if (is_home()) { $this->eccw_render_switcher_html(); return; }
                    break;

                case 'shop':
                    if (function_exists('is_shop') && is_shop()) { $this->eccw_render_switcher_html(); return; }
                    break;

                case 'woocommerce':
                    if (function_exists('is_woocommerce') && is_woocommerce()) { $this->eccw_render_switcher_html(); return; }
                    break;

                case 'category':
                    if (is_category()) { $this->eccw_render_switcher_html(); return; }
                    break;

                case 'front_page':
                    if (is_front_page()) { $this->eccw_render_switcher_html(); return; }
                    break;

                case 'product_category':
                    if (function_exists('is_product_category') && is_product_category()) { $this->eccw_render_switcher_html(); return; }
                    break;

                case 'cart':
                    if (function_exists('is_cart') && is_cart()) { $this->eccw_render_switcher_html(); return; }
                    break;

                case 'product':
                    if (function_exists('is_product') && is_product()) { $this->eccw_render_switcher_html(); return; }
                    break;

                case 'checkout':
                    if (function_exists('is_checkout') && is_checkout()) { $this->eccw_render_switcher_html(); return; }
                    break;

                case 'product_tag':
                    if (function_exists('is_product_tag') && is_product_tag()) { $this->eccw_render_switcher_html(); return; }
                    break;

                case 'blog':
                    if (is_home() || is_archive()) { $this->eccw_render_switcher_html(); return; }
                    break;
            }
        }
    }

    private function eccw_render_switcher_html() {
        include ECCW_PL_PATH . 'public/views/eccw-auto-switcher/eccw-switcher-template.php';
    }


    public function eccw_side_enqueue_all_dynamic_css() {
       
        $eccw_options = get_option('eccw_currency_settings');
        
        $all_css = '';

        if (!empty($eccw_options['design'])) {
            $design_selectors = [
                'switcher_dropdown_option_hover' => '.easy-currency-switcher .easy_currency_switcher_form .easy-currency-switcher-select li:hover, .easy-currency-switcher .easy_currency_switcher_form .easy-currency-switcher-select li.selected',
                'switcher_dropdown_option_flag'  => '.easy-currency-switcher .easy_currency_switcher_form .easy-currency-switcher .flag',
            ];

            foreach ($design_selectors as $key => $selector) {
                if (!empty($eccw_options['design'][$key])) {
                    $all_css .= eccw_add_dynamic_css($eccw_options['design'][$key], $selector);
                }
            }
        }

        if (!empty($all_css)) {
            wp_add_inline_style('eccw-style', $all_css);
        }
    }
}

$switcher_settings = get_option('eccw_currency_settings', []);

$show_hide = $switcher_settings['design']['eccw_show_hide_side_currency'] ?? 'no';

if ( $show_hide === 'yes' || $show_hide === '1') {
    // Initialize the singleton
    ECCW_Auto_Switcher::get_instance();
}

