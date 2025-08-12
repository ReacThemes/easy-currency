<?php
if (!defined('ABSPATH')) die('No direct access allowed');
class ECCW_CURRENCY_VIEW  extends ECCW_CURRENCY_SWITCHER { 


    protected $ecccw_get_plugin_settings;
    protected $plugin_settings;
 
    public function __construct(){

        $this->ecccw_get_plugin_settings = new ECCW_Plugin_Settings();
        $this->plugin_settings = $this->ecccw_get_plugin_settings->ecccw_get_plugin_settings();


        add_action( 'init', [$this, 'eccw_add_currency_nonce'] );
        add_action( 'init', [$this, 'ecccw_update_currency'] );
        add_shortcode( 'eccw_currency_switcher', [$this, 'eccw_get_currency_switcher'] );

    }


    public function eccw_set_cookie($name, $value, $duration_days) {
        $expiryTime = time() + ($duration_days * 24 * 60 * 60); // 30 days in seconds
        setcookie($name, $value, $expiryTime, "/");
    }

    public function eccw_add_currency_nonce(){

        if ( ! isset( $_COOKIE['eccw_currency_nonce'] ) ) {
            $this->eccw_set_cookie('eccw_currency_nonce', wp_create_nonce( 'apply_custom_currency_rate' ), 30);
        }

    }
    

    public function ecccw_update_currency(){

        // Verify the nonce
        if ( ! isset( $_POST['eccw_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['eccw_nonce'] ) ), 'eccw_currency_update_nonce' ) ) {
            // Nonce verification failed
           return;
        }
        
        if(isset($_REQUEST['easy_currency']) && !empty($_REQUEST['easy_currency'])){

            $default_currency = sanitize_text_field( wp_unslash( $_REQUEST['easy_currency'] ) );

            $this->eccw_set_cookie('user_preferred_currency', $default_currency, 30);
            
            if (!isset($_COOKIE['eccw_has_redirected'])) {
                // Set a cookie that expires in 1 hour to prevent multiple redirects
                setcookie('eccw_has_redirected', '1', time() + 3600, '/'); 

                // Perform the redirect
                if(isset($_SERVER['REQUEST_URI'])){
                    wp_redirect( sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) );
                    exit;
                }
            }
            
            // Optional: Clear the cookie after redirect happens again (if you want to allow redirect after session ends)
            if (isset($_COOKIE['eccw_has_redirected'])) {
                setcookie('eccw_has_redirected', '', time() - 3600, '/'); // Delete the cookie after one reload
            }

        } 

    }

    public function eccw_get_currency_common_settings() {

        $admin_settings = new ECCW_admin_settings();
        $currency_settings = $this->plugin_settings;
        $eccw_currency_table = isset($currency_settings['eccw_currency_table']) ? $currency_settings['eccw_currency_table'] : []; 
        $default_currency = isset($_COOKIE['user_preferred_currency']) && !empty($_COOKIE['user_preferred_currency'])
    ? sanitize_text_field(wp_unslash($_COOKIE['user_preferred_currency']))
    : ( isset($currency_settings['default_currency']) ? $currency_settings['default_currency'] : 'USD' ); 

        $options = isset($currency_settings['options']) ? $currency_settings['options'] : [];
        $flag_visibility = isset($options['flag_visibility']) && !empty($options['flag_visibility']) ? $options['flag_visibility'] : 'no';

        $currency_countries = wp_remote_get( ECCW_DIR_URL . '/admin/assets/json/currency-countries.json', [] );

        return [
            'eccw_currency_table' => $eccw_currency_table,
            'default_currency' => $default_currency,
            'flag_visibility' => $flag_visibility,
            'currency_countries' => $currency_countries,
        ];
    }

 
    public function eccw_render_currency_switcher ( $args = [] ) {
        
        $settings = $this->eccw_get_currency_common_settings();

        $eccw_currency_table = $settings['eccw_currency_table'];
        $default_currency    = $settings['default_currency'];
        $flag_visibility     = $settings['flag_visibility'];
        $currency_countries  = $settings['currency_countries'];
        $shortcode_id = $args['shortcode_id'] ?? 'default';
        $switcher_type = $args['switcher_type'] ?? '';
        $side_template_position = $args['side_template_position'] ?? '';

        $show_toggle   = $args['show_toggle'] ?? false;
        $style_options   = $args['style_options'] ?? [];
        $wrapper_class = $args['wrapper_class'] ?? 'switcher-list-content';
        $unique_class = 'eccw-switcher-design' . sanitize_html_class($shortcode_id);

        $side_template_position = ( $switcher_type == 'side' && !empty( $side_template_position ) ) ? 'position-'.$side_template_position : '';

         error_log( print_r($style_options, true ));
        $template_style     = $style_options['switcher_dropdown_option']['template'];

        $classes = [
            $unique_class,
            $template_style,
            $switcher_type,
            $side_template_position,
        ];

        $classes = array_map(function($class) {
            if (!empty($class)) {
                return esc_attr(str_replace('_', '-', $class));
            }
            return '';
        }, $classes);

        $classes = array_filter($classes);

        $wrapper_class .= ' ' . implode(' ', $classes);

        try {
            $currency_countries_json = json_decode( $currency_countries['body'], true );
        } catch ( Exception $ex ) {
            $currency_countries_json = null; 
        }

        ob_start();

        $flag_show        = $style_options['eccw_switcher_flag_show_hide'];
        $cur_name_show    = $style_options['eccw_switcher_currency_name_show_hide'];
        $cur_symbol_show  = $style_options['eccw_switcher_currency_symbol_show_hide'];
        $cur_code_show    = $style_options['eccw_switcher_currency_code_show_hide'];
        $dd_flag_show     = $style_options['eccw_switcher_dropdown_flag_show_hide'];
        $dd_name_show     = $style_options['eccw_switcher_dropdown_currency_name_show_hide'];
        $dd_symbol_show   = $style_options['eccw_switcher_dropdown_currency_symbol_show_hide'];
        $dd_code_show     = $style_options['eccw_switcher_dropdown_currency_code_show_hide'];
       


        ?>
            <div class="easy-currency-switcher <?php echo esc_attr( $wrapper_class ); ?>">  
                <form method="post" action="#" id="easy_currency_switcher_form" class="easy_currency_switcher_form">
                    <?php wp_nonce_field( 'eccw_currency_update_nonce', 'eccw_nonce'); ?>
                    <input type="hidden" name="easy_currency">
                    <?php 
                        if( $show_toggle == true ) {
                            $country = $currency_countries_json[$default_currency]['countries'][0];
                            $symbol = $currency_countries_json[$default_currency]['symbol'];
                            $name = $currency_countries_json[$default_currency]['name'];
                            $flag_url = 'https://flagcdn.com/24x18/' . strtolower($country).'.png';
                           
                    ?>
                    <button type="button" class="easy-currency-switcher-toggle">
                        
                        <div class="easy-currency-elements">
                            <?php 
                                if( $flag_show == 'yes') {
                            ?>
                            <img src="<?php echo esc_url( $flag_url );?>" alt="flag" class="flag">
                            <?php } ?>
                            <?php 
                                if(  $cur_code_show == 'yes') {
                            ?>
                            <span class="easy-country-code"><?php echo esc_attr($default_currency); ?> </span>
                            <?php } 
                            if(  $cur_name_show == 'yes') {
                            ?>
                            <span class="easy-country-name"><?php echo esc_attr($name); ?> </span>
                            <?php }
                                if( $cur_symbol_show == 'yes') {
                            ?>
                            <span class="easy-country-symbol">(<?php echo esc_attr($symbol); ?>)</span>
                            <?php } ?>
                        </div>
                        <span class="dropdown-icon"></span>
                    </button>
                    <?php } ?>
                    <ul class="easy-currency-switcher-select list <?php echo $dd_flag_show == 'yes' ? 'has-flag' : '' ?>">
                        <?php 

                            try {
                                $currency_countries_json = json_decode( $currency_countries['body'], true );
                            } catch ( Exception $ex ) {
                                $currency_countries_json = null; 
                            }

                        if(is_array($eccw_currency_table) && count($eccw_currency_table) > 0){
                            foreach ($eccw_currency_table as $key => $currency) {

                                $currency_code = $currency['code'];
                                $country = $currency_countries_json[$currency_code]['countries'][0];
                                $symbol = $currency_countries_json[$currency_code]['symbol'];
                                $name = $currency_countries_json[$currency_code]['name'];
                                $flag_url = 'https://flagcdn.com/24x18/' . strtolower($country).'.png';

                                ?>
                                    <li data-value="<?php echo esc_attr($currency_code) ?>" class="option <?php echo $default_currency == $currency_code ? 'selected' : ''; ?>">
                                        <?php 
                                            if( $dd_flag_show == 'yes') {
                                        ?>
                                        <img src="<?php echo esc_url( $flag_url )?>" alt="<?php echo esc_attr($currency_code)?> flag" class="flag" data-value="<?php echo esc_attr($currency_code) ?>">
                                        <?php } 
                                         
                                            if( $dd_code_show == 'yes') {
                                        ?>
                                        <span class="eccw-dropdown-country-code"><?php echo esc_html($currency_code); ?></span>
                                        <?php } 
                                        if( $dd_name_show == 'yes') {
                                        ?>
                                        
                                         <span class="eccw-dropdown-country-name"><?php echo esc_html($name); ?></span>
                                        <?php }
                                            if( $dd_symbol_show == 'yes') {
                                        ?>
                                        <span class="eccw-dropdown-symbol-code">(<?php echo esc_html($symbol); ?>)</span> 
                                        <?php } ?>
                                    </li>
                                <?php
                            } 
                        }
                        ?>  
                    </ul>
                </form>
            </div>
        <?php
        return ob_get_clean();
    }


    public function eccw_get_currency_switcher($atts = []) {
        $atts = shortcode_atts([
            'show_toggle'   => 'true',
            'wrapper_class' => 'switcher-list-content',
            'shortcode_id'  => isset($atts['id']) ? sanitize_text_field($atts['id']) : '', 
        ], $atts, 'eccw_currency_switcher');

        $show_toggle   = filter_var($atts['show_toggle'], FILTER_VALIDATE_BOOLEAN);
        $wrapper_class = sanitize_text_field($atts['wrapper_class']);
        $shortcode_id  = $atts['shortcode_id'];

        $switcher_settings = get_option('eccw_switcher_styles', []);

        if (!empty($shortcode_id) && isset($switcher_settings[$shortcode_id]) && is_array($switcher_settings[$shortcode_id])) {
            $get_switcher_settings = $switcher_settings[$shortcode_id];

            $switcher_type = $get_switcher_settings['switcher_layout_view_option']['layout_style'] ?? 'dropdown';
            $template_style = $get_switcher_settings['switcher_dropdown_option']['template'] ?? 'default_template';
            $side_template_position = $get_switcher_settings['eccw_position_alignment_toggle'] ?? 'left';

        } else {
            $get_switcher_settings = [];
            $switcher_type = 'dropdown';
            $template_style = 'eccw_template_1';
            $side_template_position = 'left';
        }

        return $this->eccw_render_currency_switcher([
            'show_toggle'    => $show_toggle,
            'wrapper_class'  => $wrapper_class,
            'shortcode_id'   => $shortcode_id,
            'switcher_type'  => $switcher_type,
            'template_style' => $template_style,
            'style_options'  => $get_switcher_settings, 
            'side_template_position'  => $side_template_position, 
        ]);
    }

}

new ECCW_CURRENCY_VIEW();