<?php 

    $eccw_options = get_option('eccw_currency_settings');
    $design = isset($eccw_options['design']) && !empty($eccw_options['design']) ? $eccw_options['design'] : '';
    $side_position = isset( $design['switcher_side_dropdown_option'] ) ?  $design['switcher_side_dropdown_option'] : 'eccw-left-position';

    ob_start();
    ?>
    <div class="easy-currency-side-switcher <?php echo esc_attr( $side_position ); ?>">  
        <form  method="post" action="#" id="easy_currency_switcher_form" class="easy_currency_switcher_form">
            <?php wp_nonce_field( 'eccw_currency_update_nonce', 'eccw_nonce'); ?>
            <input type="hidden" name="easy_currency">
            <ul class="easy-currency-switcher-select list <?php echo $flag_visibility == 'yes' ? 'has-flag' : '' ?>">
                <?php 

                    try {
                        $currency_countries_json = json_decode( $currency_countries['body'], true );
                    } catch ( Exception $ex ) {
                        $currency_countries_json = null; 
                    }


                if(is_array($eccw_currency_table) && count($eccw_currency_table) > 0){
                    foreach ($eccw_currency_table as $key => $currency) {

                        $currency_code = $currency['code'];
                        $country = $currency_countries_json[$currency_code][0];
                        $flag_url = 'https://flagcdn.com/24x18/' . strtolower($country).'.png';

                        ?>
                            <li data-value="<?php echo esc_attr($currency_code) ?>" class="option <?php echo $default_currency == $currency_code ? 'selected' : ''; ?>">
                                <img src="<?php echo esc_url( $flag_url )?>" alt="<?php echo esc_attr($currency_code)?> flag" class="flag" data-value="<?php echo esc_attr($currency_code) ?>"><?php echo esc_html($currency_code); ?>
                            </li>
                        <?php
                    } 
                }
                ?>  
            </ul>
        </form>
    </div>
<?php
echo ob_get_clean();