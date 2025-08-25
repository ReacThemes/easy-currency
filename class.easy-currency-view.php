<?php
if (!defined('ABSPATH')) die('No direct access allowed');
class ECCW_CURRENCY_VIEW  extends ECCW_CURRENCY_SWITCHER { 

    protected $ecccw_get_plugin_settings;
    protected $plugin_settings;
    public $shortcode_id_proudct_page;
    protected $single_shortcode_onoff;
 
    public function __construct(){

        $this->ecccw_get_plugin_settings = new ECCW_Plugin_Settings();
        $this->plugin_settings = $this->ecccw_get_plugin_settings->ecccw_get_plugin_settings();

        $saved_settings = get_option('eccw_currency_settings');
        $options = isset($saved_settings['options']) ? $saved_settings['options'] : [];
        $switcher_pos = isset( $options['eccw_shortcode_pos_product_singlepage'] ) ? $options['eccw_shortcode_pos_product_singlepage'] : '';

        $this->shortcode_id_proudct_page = isset( $options['eccw_shortcode_show_on_product_pages'] ) ? $options['eccw_shortcode_show_on_product_pages'] : '';
        $this->single_shortcode_onoff = ( ! empty( $options['eccw_show_hide_single_product_location'] ) ) ? 1 : 0;

        

        if( $switcher_pos == 'before_short_description') {
            add_action( "woocommerce_single_product_summary", [$this, 'eccw_single_shortcode_position'], 19 );
        }  else {
           add_action( $switcher_pos, [$this, 'eccw_single_shortcode_position']);
        }

        add_action( 'init', [$this, 'eccw_add_currency_nonce'] );
        add_action( 'init', [$this, 'ecccw_update_currency'] );
        add_shortcode( 'easy_currency_switcher', [$this, 'eccw_get_currency_switcher'] );

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
 
    public function eccw_render_currency_switcher($args = []) {
       
        $settings            = $this->eccw_get_currency_common_settings();
        $eccw_currency_table = $settings['eccw_currency_table'];
        $default_currency    = $settings['default_currency'];
        $currency_countries  = $settings['currency_countries'];

       
        $shortcode_id  = $args['shortcode_id'] ?? 'default';
        $style_options = $args['style_options'] ?? [];
        $wrapper_class = $args['wrapper_class'] ?? 'switcher-list-content';

        $unique_class   = 'eccw-switcher-design' . sanitize_html_class($shortcode_id);
        $template_style = $style_options['switcher_dropdown_option_edit']['template'] ?? 'eccw-template-1';

        $classes        = array_filter([$unique_class, $template_style]);
        $classes        = array_map(fn($c) => esc_attr(str_replace('_', '-', $c)), $classes);
        $wrapper_class .= ' ' . implode(' ', $classes);

        $currency_countries_json = json_decode($currency_countries['body'] ?? '', true) ?? [];

        $flag_show       = $style_options['eccw_switcher_flag_show_hide'] ?? 'yes';
        $cur_name_show   = $style_options['eccw_switcher_currency_name_show_hide'] ?? 'no';
        $cur_symbol_show = $style_options['eccw_switcher_currency_symbol_show_hide'] ?? 'yes';
        $cur_code_show   = $style_options['eccw_switcher_currency_code_show_hide'] ?? 'yes';

       
        $default_country = $currency_countries_json[$default_currency]['countries'][0] ?? '';
        $default_symbol  = $currency_countries_json[$default_currency]['symbol'] ?? '';
        $default_name    = $currency_countries_json[$default_currency]['name'] ?? '';
        $default_flag    = ECCW_PL_URL . 'public/assets/images/flags/' . strtolower($default_country) . '.png';


        ob_start();
        ?>
        <div class="easy-currency-switcher <?php echo esc_attr($wrapper_class); ?>">  
            <form method="post" id="easy_currency_switcher_form" class="easy_currency_switcher_form">
                <?php wp_nonce_field('eccw_currency_update_nonce', 'eccw_nonce'); ?>
                <input type="hidden" name="easy_currency">

                <!-- Toggle Button -->
                <button type="button" class="easy-currency-switcher-toggle">
                    <div class="easy-currency-elements">
                        <?php if ($flag_show === 'yes'): ?>
                            <img src="<?php echo esc_url($default_flag); ?>" alt="flag" class="flag">
                        <?php endif; ?>

                        <?php if ($cur_code_show === 'yes'): ?>
                            <span class="easy-country-code"><?php echo esc_html($default_currency); ?></span>
                        <?php endif; ?>

                        <?php if ($cur_name_show === 'yes'): ?>
                            <span class="easy-country-name"><?php echo esc_html($default_name); ?></span>
                        <?php endif; ?>

                        <?php if ($cur_symbol_show === 'yes'): ?>
                            <span class="easy-country-symbol">(<?php echo esc_html($default_symbol); ?>)</span>
                        <?php endif; ?>
                    </div>
                    <span class="dropdown-icon"></span>
                </button>

                <!-- Dropdown List -->
                <ul class="easy-currency-switcher-select list <?php echo $flag_show === 'yes' ? 'has-flag' : ''; ?>">
                    <?php if (!empty($eccw_currency_table) && is_array($eccw_currency_table)): ?>
                        <?php foreach ($eccw_currency_table as $currency): 
                            $code    = $currency['code'];
                            $country = $currency_countries_json[$code]['countries'][0] ?? '';
                            $symbol  = $currency_countries_json[$code]['symbol'] ?? '';
                            $name    = $currency_countries_json[$code]['name'] ?? '';
                            $flag      = ECCW_PL_URL . 'public/assets/images/flags/' . strtolower($country) . '.png';
                            ?>
                            <li data-value="<?php echo esc_attr($code); ?>" 
                                class="option <?php echo $default_currency === $code ? 'selected' : ''; ?>">

                                <?php if ($flag_show === 'yes'): ?>
                                    <img src="<?php echo esc_url($flag); ?>" 
                                        alt="<?php echo esc_attr($code); ?> flag" 
                                        class="flag" data-value="<?php echo esc_attr($code); ?>">
                                <?php endif; ?>

                                <?php if ($cur_code_show === 'yes'): ?>
                                    <span class="eccw-dropdown-country-code"><?php echo esc_html($code); ?></span>
                                <?php endif; ?>

                                <?php if ($cur_name_show === 'yes'): ?>
                                    <span class="eccw-dropdown-country-name"><?php echo esc_html($name); ?></span>
                                <?php endif; ?>

                                <?php if ($cur_symbol_show === 'yes'): ?>
                                    <span class="eccw-dropdown-symbol-code">(<?php echo esc_html($symbol); ?>)</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }


    public function eccw_get_currency_switcher($atts = []) {

        $atts = shortcode_atts([
            'wrapper_class' => 'switcher-list-content',
            'shortcode_id'  => isset($atts['id']) ? sanitize_text_field($atts['id']) : '', 
        ], $atts, 'eccw_currency_switcher');

        $wrapper_class = sanitize_text_field($atts['wrapper_class']);
        $shortcode_id  = $atts['shortcode_id'];

        $switcher_settings = get_option('eccw_switcher_styles', []);

        if (!empty($shortcode_id) && isset($switcher_settings[$shortcode_id]) && is_array($switcher_settings[$shortcode_id])) {
            $get_switcher_settings = $switcher_settings[$shortcode_id];
            $template_style = $get_switcher_settings['switcher_dropdown_option']['template'] ?? 'default_template';

        } else {
            $get_switcher_settings = [];
            $template_style = 'eccw_template_1';
        }

        return $this->eccw_render_currency_switcher([
            'wrapper_class'  => $wrapper_class,
            'shortcode_id'   => $shortcode_id,
            'template_style' => $template_style,
            'style_options'  => $get_switcher_settings, 
        ]);
    }

    public function eccw_single_shortcode_position() {

        if( $this->single_shortcode_onoff == '1' ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo eccw_do_shortcode('easy_currency_switcher', [ 'id' => $this->shortcode_id_proudct_page ]);
        }
    }
}

new ECCW_CURRENCY_VIEW();


