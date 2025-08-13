<?php
    $common_settings = eccw_get_currency_common_settings();
    extract($common_settings);
    extract($this->settings);

    $sticky_position = "eccw-position-$sticky_position";

    $classes = [
        $eccw_template,
        $sticky_position,
    ];

    $classes = array_map(function($class) {
        if (!empty($class)) {
            return esc_attr(str_replace('_', '-', $class));
        }
        return '';
    }, $classes);

    $classes = array_filter($classes);

    $wrapper_class = ' ' . implode(' ', $classes);


ob_start();
?>

<div class="easy-currency-switcher-auto-select <?php echo esc_attr($wrapper_class); ?>">  
    <form method="post" action="#" id="easy_currency_switcher_form" class="easy_currency_switcher_form">
        <?php wp_nonce_field( 'eccw_currency_update_nonce', 'eccw_nonce'); ?>
        <input type="hidden" name="easy_currency">
        <ul class="easy-currency-switcher-select list">
            <?php 
            try {
                $currency_countries_json = json_decode($currency_countries['body'], true);
            } catch (Exception $ex) {
                $currency_countries_json = null;
            }

            if (is_array($eccw_currency_table) && count($eccw_currency_table) > 0) {
                foreach ($eccw_currency_table as $currency) {
                    $currency_code = $currency['code'];
                    $country = $currency_countries_json[$currency_code]['countries'][0];
                    $symbol = $currency_countries_json[$currency_code]['symbol'];
                    $name = $currency_countries_json[$currency_code]['name'];
                    $flag_url = 'https://flagcdn.com/24x18/' . strtolower($country) . '.png';
                    ?>
                    <li data-value="<?php echo esc_attr($currency_code); ?>" class="option <?php echo $default_currency == $currency_code ? 'selected' : ''; ?>">
                        <?php 
                            if (in_array( $eccw_template, ['eccw_sticky_template_1', 'eccw_sticky_template_3'])) {
                        ?>
                        <img src="<?php echo esc_url($flag_url); ?>" alt="<?php echo esc_attr($currency_code); ?> flag" class="flag" data-value="<?php echo esc_attr($currency_code); ?>">
                        <span class="eccw-side-country-code"><?php echo esc_html($currency_code); ?></span>
                        <?php } else { ?>
                            <span class="eccw-side-country-code"><?php echo esc_html($currency_code); ?></span>
                            <img src="<?php echo esc_url($flag_url); ?>" alt="<?php echo esc_attr($currency_code); ?> flag" class="flag" data-value="<?php echo esc_attr($currency_code); ?>">
                        <?php } ?>
                        <span class="eccw-side-country-name"><?php echo esc_html($name); ?></span>
                        <span class="eccw-side-symbol-code">(<?php echo esc_html($symbol); ?>)</span> 
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
