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
            'show_pages'                 => $switcher_settings['design']['eccw_sticky_show_on_pages'],
            'sticky_position'            => $switcher_settings['design']['eccw_position_alignment_toggle'],
            'sticky_vertical'            => $switcher_settings['design']['eccw_switcher_elements_vertical'],
            'sticky_horizontal'          => $switcher_settings['design']['eccw_switcher_elements_horizontal'],
            'sticky_vertical_hover'      => $switcher_settings['design']['eccw_switcher_elements_horizontal_hover'],
            'sticky_horizontal_hover'    => $switcher_settings['design']['eccw_switcher_elements_item_move_horizontal'],

        );

        $this->settings = apply_filters('eccw_sticky_switcher_data', $this->settings);
       
        add_action('init', [ $this, 'init_auto_switcher' ]);
        
    }

    /**
     * Example method to initialize auto switcher
     */
    public function init_auto_switcher() {
        add_action("wp_footer", array( $this, "eccw_auto_switcher_render_raw_html") );
    }

    public function eccw_auto_switcher_render_raw_html() {

        include ECCW_PL_PATH . 'public/views/eccw-auto-switcher/eccw-switcher-template.php';
    }
}

$switcher_settings = get_option('eccw_currency_settings', []);

$show_hide = $switcher_settings['design']['eccw_show_hide_side_currency'] ?? 'no';

if ( $show_hide === 'yes' || $show_hide === '1') {
    // Initialize the singleton
    ECCW_Auto_Switcher::get_instance();
}

