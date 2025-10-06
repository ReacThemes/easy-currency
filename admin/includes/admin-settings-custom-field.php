<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly.

class ECCW_admin_settings_Customfields
{

    private static $instance = null;
    protected $fixed_price_eachproduct = false;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {

        add_action('woocommerce_admin_field_template_preview', array($this, 'eccw_template_preview_field'));
        add_action('woocommerce_admin_field_switcher', array($this, 'eccw_admin_field_switcher_show_hide'));
        add_action('woocommerce_admin_field_eccw_slider', array($this, 'eccw_admin_field_custom_slider'));
        add_action('woocommerce_admin_field_html', array($this, 'eccw_admin_field_switcher_html_start_end'));
        add_action('woocommerce_admin_field_tabswitch', array($this, 'eccw_admin_field_switcher_tabswitch'));
        add_action('woocommerce_admin_field_eccw_border_control', array($this, "eccw_admin_border_control"));
        add_action('woocommerce_admin_field_select2', array($this, 'eccw_admin_field_eccw_select2'));
        add_action('woocommerce_admin_field_eccw_searchable_select', array($this, 'eccw_searchable_select_field') );
        add_action('woocommerce_admin_field_eccw_searchable_country', array($this, 'eccw_searchable_country_select_field') );

        add_action('woocommerce_admin_field_eccw_currency_on_billing',array( $this, 'eccw_admin_field_eccw_currency_on_billing' ) );

        add_action('woocommerce_product_options_pricing', [$this, 'eccw_eccw_add_fixed_pricing_options']);
        
        add_filter('eccw_pricing_fixed_rules', [$this, 'eccw_add_fixed_pricing'], 10, 3);
        add_action('woocommerce_process_product_meta', [$this, 'eccw_update_fixed_pricing_options']);

        $this->fixed_price_eachproduct =  $this->eccw_fixed_price_rule();

        

    }

    public function eccw_admin_field_eccw_currency_on_billing($field) {
        
        $value = $field['value'] ?? $field['default'] ?? '';
        $option_id = $field['field_name'] ?? $field['id'];
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($option_id); ?>">
                    <?php echo esc_html($field['title'] ?? __('Currency on Billing', 'easy-currency')); ?>
                </label>
            </th>
            <td class="forminp forminp-radio">
                <fieldset>
                    <legend class="screen-reader-text">
                        <span><?php echo esc_html($field['title'] ?? ''); ?></span>
                    </legend>

                    <label>
                        <input type="radio" name="<?php echo esc_attr($option_id); ?>" value="none"
                            <?php checked($value, 'none'); ?> />
                        <?php esc_html_e('None', 'easy-currency'); ?>
                    </label><br/>

                    <label>
                        <input type="radio" name="<?php echo esc_attr($option_id); ?>" value="billing"
                            <?php checked($value, 'billing'); ?> />
                        <?php esc_html_e('Change currency by billing country on checkout', 'easy-currency'); ?>
                    </label><br/>

                    <label>
                        <input type="radio" name="<?php echo esc_attr($option_id); ?>" value="shipping"
                            <?php checked($value, 'shipping'); ?> />
                        <?php esc_html_e('Change currency by shipping country on checkout', 'easy-currency'); ?>
                    </label>

                    <?php if (!empty($field['desc'])): ?>
                        <p class="description"><?php echo wp_kses_post($field['desc']); ?></p>
                    <?php endif; ?>
                </fieldset>
            </td>
        </tr>
        
        <?php
    }

    public function eccw_searchable_select_field( $value ) {

        global $ECCW_Admin_Ajax;
        $shortcodes = $ECCW_Admin_Ajax->eccw_get_all_shortcodes_cached();

        $current_value = isset( $value['default'] ) ? $value['default'] : '';
        $class         = isset( $value['class'] ) ? $value['class'] : '';
        $current_text  = '';

        foreach ( $shortcodes as $shortcode ) {
            if ( $shortcode['id'] == $current_value ) {
                $current_text = $shortcode['switcher_name'];
                break;
            }
        }

        ?>

        <tr valign="top" class="<?php echo esc_attr( $class ); ?>">
            <th scope="row" class="titledesc"><?php echo esc_html( $value['name'] ); ?></th>
            <td class="forminp forminp-select">

                <?php if ( ! empty( $shortcodes ) ) : ?>
                    <select id="<?php echo esc_attr( $value['id'] ); ?>"
                            name="<?php echo esc_attr( $value['id'] ); ?>"
                            class="eccw-searchable-select"
                            data-placeholder="<?php esc_attr_e( 'Search for a shortcode...', 'easy-currency' ); ?>">

                        <?php if ( ! empty( $current_value ) && ! empty( $current_text ) ) : ?>
                            <option value="<?php echo esc_attr( $current_value ); ?>" selected>
                                <?php echo esc_html( $current_text ); ?>
                            </option>
                        <?php endif; ?>

                    </select>

                <?php else : ?>
                    <p>
                        <?php 
                        /* translators: %s: link to the Shortcodes tab in the plugin settings */
                        printf(
                            /* translators: %s is replaced with a link to the Shortcodes tab */
                            esc_html__( 'No shortcodes found. If you create a shortcode in the %s tab, it will appear here.', 'easy-currency' ),  esc_html__( 'Shortcodes', 'easy-currency' ) . '</a>'
                        ); 
                        ?>
                    </p>


                <?php endif; ?>

                <?php if ( ! empty( $value['desc'] ) ) : ?>
                    <p class="description"><?php echo esc_html( $value['desc'] ); ?></p>
                <?php endif; ?>

            </td>
        </tr>

        <?php
    }

    public function eccw_searchable_country_select_field( $field ) {

        $current_value = isset( $field['default'] ) ? $field['default'] : [];
        $class         = isset( $field['class'] ) ? $field['class'] : '';
        $desc_tip      = ! empty( $field['desc_tip'] ) ? true : false;
        $description   = ! empty( $field['description'] ) ? $field['description'] : '';
        $eccw_pro      = ! empty($field['eccw_pro']) ? $field['eccw_pro'] : false;
        $pro_active    = $eccw_pro && ! class_exists('ECCW_CURRENCY_SWITCHER_PRO');

        if ( ! is_array( $current_value ) ) {
            $current_value = ! empty( $current_value ) ? [$current_value] : [];
        }

        $countries = WC()->countries->get_countries();
        ?>
        <tr valign="top" class="<?php echo esc_attr( $class ); ?>">
            <th scope="row" class="titledesc">
                <?php echo esc_html( $field['name'] ); ?>
                <?php if ( $pro_active ) : ?>
                    (<span class="eccw-pro-lock">PRO</span>)
                <?php endif; ?>
                <?php if ( $desc_tip && $description ) : ?>
                    <span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $description ); ?>"></span>
                <?php endif; ?>
            </th>
            <td class="forminp forminp-select">
                <label <?php echo $pro_active ? 'class="eccw-pro-lock-tooltip" data-tooltip="' . esc_attr__('This feature is available in Pro version', 'easy-currency') . '"' : ''; ?>>
                    <select id="<?php echo esc_attr( $field['id'] ); ?>"
                            name="<?php echo esc_attr( $field['id'] ); ?>[]"
                            class="eccw-searchable-country-select"
                            multiple="multiple"
                            style="width:100%;"
                            <?php echo $pro_active ? 'disabled' : ''; ?>
                            <?php if ( ! $pro_active ) : ?>
                            data-placeholder="<?php esc_attr_e( 'Search for a country...', 'easy-currency' ); ?>"
                            <?php endif; ?>
                    >
                        <?php foreach ( $current_value as $val ) : 
                            if ( isset( $countries[ $val ] ) ) : ?>
                                <option value="<?php echo esc_attr( $val ); ?>" selected>
                                    <?php echo esc_html( $countries[ $val ] ); ?>
                                </option>
                            <?php endif;
                        endforeach; ?>
                    </select>
                </label>

                <?php if ( ! $desc_tip && $description ) : ?>
                    <p class="description"><?php echo esc_html( $description ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }

    public function eccw_admin_field_eccw_select2($field)
    {

        $option_value = $field['value'] ?? $field['default'] ?? '';
        $name = $field['field_name'] ?? $field['id'];

        $is_multiple = isset($field['custom_attributes']['multiple']);

        echo '<tr valign="top" class="eccw-switcher-ui-control">';
        echo '<th scope="row" class="titledesc"><label>' . esc_html($field['name']) . '</label></th>';
        echo '<td class="forminp">';

        printf(
            '<select name="%s" id="%s" class="eccw-sticky-select2" %s>',
            esc_attr( $name ),
            esc_attr( $field['id'] ),
            $is_multiple ? ' multiple="multiple"' : ''
        );

        if (!empty($field['options']) && is_array($field['options'])) {
            foreach ( $field['options'] as $group_label => $group_options ) {

                if (is_array($group_options)) {
                    echo '<optgroup label="' . esc_attr($group_label) . '">';
                    foreach ($group_options as $key => $label) {
                        $is_selected = false;

                        if ($is_multiple && is_array($option_value)) {
                            $is_selected = in_array((string)$key, array_map('strval', $option_value), true);
                        } else {
                            $is_selected = ((string)$option_value === (string)$key);
                        }

                        echo '<option value="' . esc_attr($key) . '" ' . selected($is_selected, true, false) . '>' . esc_html($label) . '</option>';
                    }
                    echo '</optgroup>';
                } else {

                    $is_selected = false;

                    if ($is_multiple && is_array($option_value)) {
                        $is_selected = in_array((string)$group_label, array_map('strval', $option_value), true);
                    } else {
                        $is_selected = ((string)$option_value === (string)$group_label);
                    }

                    echo '<option value="' . esc_attr($group_label) . '" ' . selected($is_selected, true, false) . '>' . esc_html($group_options) . '</option>';
                }
            }
        }

        echo '</select>';

        if (!empty($field['desc'])) {
            echo '<p class="description">' . esc_html($field['desc']) . '</p>';
        }

        echo '</td></tr>';
    }

    public function eccw_admin_border_control($value)
    {

        $option_value = isset($value['value']) && is_array($value['value']) ? $value['value'] : $value['default'];

        $top    = esc_attr($option_value['top'] ?? '');
        $right  = esc_attr($option_value['right'] ?? '');
        $bottom = esc_attr($option_value['bottom'] ?? '');
        $left   = esc_attr($option_value['left'] ?? '');
        $style  = esc_attr($option_value['style'] ?? '');
        $color  = esc_attr($option_value['color'] ?? '');

?>
       <tr valign="top" class="<?php echo esc_attr($value['class']); ?>" class="easy-currency-admin-border-control">
            <th scope="row" class="titledesc">
                <?php echo esc_html($value['name']); ?>
            </th>
            <td class="forminp forminp-text">
                <div class="eccw-border-control">
                    <div  class="eccw-border-tab-control">
                        <label>Top</label>
                        <input
                            type="text"
                            name="<?php echo esc_attr($value['id']); ?>[top]"
                            value="<?php echo esc_attr( $top ); ?>"
                            placeholder="2px"
                            style="width:60px;">
                    </div>
                    <div  class="eccw-border-tab-control">
                        <label>Right</label>
                        <input
                            type="text"
                            name="<?php echo esc_attr($value['id']); ?>[right]"
                            value="<?php echo esc_attr( $right ); ?>"
                            placeholder="2px"
                            style="width:60px;">
                    </div>
                    <div  class="eccw-border-tab-control">
                        <label>Bottom</label>
                        <input
                            type="text"
                            name="<?php echo esc_attr($value['id']); ?>[bottom]"
                            value="<?php echo esc_attr( $bottom ); ?>"
                            placeholder="2px"
                            style="width:60px;">
                    </div>
                    <div  class="eccw-border-tab-control">
                        <label>Left</label>
                        <input
                            type="text"
                            name="<?php echo esc_attr($value['id']); ?>[left]"
                            value="<?php echo esc_attr( $left ); ?>"
                            placeholder="2px"
                            style="width:60px;">
                    </div>

                    <select name="<?php echo esc_attr($value['id']); ?>[style]"  class="easy-currency-border-style-control">
                        <option value="default" <?php selected($style, 'default'); ?>>Default</option>
                        <option value="none" <?php selected($style, 'none'); ?>>None</option>
                        <option value="solid" <?php selected($style, 'solid'); ?>>Solid</option>
                        <option value="dashed" <?php selected($style, 'dashed'); ?>>Dashed</option>
                        <option value="dotted" <?php selected($style, 'dotted'); ?>>Dotted</option>
                        <option value="double" <?php selected($style, 'double'); ?>>Double</option>
                    </select>

                    <input
                        type="text"
                        class="eccw-border-picker"
                        name="<?php echo esc_attr($value['id']); ?>[color]"
                        value="<?php echo esc_attr( $color ); ?>"
                        placeholder="#000000">
                </div>
                <?php if (!empty($value['desc'])) : ?>
                    <p class="description"><?php echo esc_html($value['desc']); ?></p>
                <?php endif; ?>
            </td>
        </tr>
    <?php
    }

    public function eccw_admin_field_switcher_tabswitch($field)
    {
        $value = get_option($field['id'], $field['default'] ?? '');

        $options = !empty($field['options']) && is_array($field['options'])
            ? $field['options']
            : array(
                'left'   => 'Left',
                'right'  => 'Right'
            );

        echo '<tr valign="top" class="' . esc_attr($field['class']) . '">
            <th scope="row" class="titledesc">
                <label>' . esc_html($field['name']) . '</label>
            </th>
            <td class="forminp forminp-tabswitch">
                <div class="eccw-tab-toggle" data-input="' . esc_attr($field['id']) . '">';

        foreach ($options as $opt_value => $opt_label) {
            $active = ($value === $opt_value) ? 'active' : '';
            echo '<div class="eccw-tab-option ' . esc_attr( $active ) . '" data-value="' . esc_attr($opt_value) . '">'
                . esc_html($opt_label) . '</div>';
        }

        echo '  </div>
                <input type="hidden" name="' . esc_attr($field['id']) . '" value="' . esc_attr($value) . '" />
            </td>
        </tr>';
    }


    public function eccw_admin_field_switcher_html_start_end($value)
    {
        if (!empty($value['html'])) {
            echo wp_kses_post( $value['html'] );
        }
    }

    public function eccw_admin_field_custom_slider($field)
    {
        $id     = esc_attr($field['id']);
        $name   = esc_attr($field['id']);
        $min    = isset($field['min']) ? intval($field['min']) : 0;
        $max    = isset($field['max']) ? intval($field['max']) : 100;
        $step   = isset($field['step']) ? intval($field['step']) : 1;

        $default = isset($field['default']) ? $field['default'] : '';

        $value = get_option($field['id']);

        if ($value === false) {
            $value = $default;
        }

        echo '<tr valign="top" class="' . esc_attr($field['class']) . '">';
        echo '<th scope="row" class="titledesc">' . esc_html($field['name']) . '</th>';

        echo '<td class="forminp">';
        echo '<input class="eccw-slider-range" type="range" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" min="' . esc_attr( $min ). '" max="' . esc_attr( $max ). '" step="' . esc_attr( $step ) . '" value="' . esc_attr($value) . '">';
        echo '<input class="eccw-slider-range-value" type="number" id="' . esc_attr( $id ) . '_value" value="' . esc_attr($value) . '">';
        echo '</td>';
        echo '</tr>';
    }


    public function eccw_template_preview_field($field)
    {
        $value      = isset( $field['value'] ) ? sanitize_text_field( $field['value'] ) : '';
        $name       = isset( $field['id'] ) ? $field['id'] : '';
        $class      = isset( $field['class'] ) ? $field['class'] : '';
        $field_name = isset( $field['name'] ) ? $field['name'] : '';
        $desc       = isset( $field['desc'] ) ? $field['desc'] : '';
        $templates  = isset( $field['templates'] ) && is_array( $field['templates'] ) ? $field['templates'] : array();
        ?>
        <tr valign="top" class="<?php echo esc_attr( $class); ?>">
            <th scope="row" class="titledesc">
                <label><?php echo esc_html( $field_name ); ?></label>
            </th>
            <td class="forminp">
                <div class="eccw-template-preview-wrapper">
                    <?php 
                    $count = 1;
                    foreach ($templates as $key => $img_url): 
                        $checked = $value === $key ? 'checked' : '';
                    ?>
                        <label class="eccw-template">
                            <input type="radio" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr($key); ?>" <?php echo esc_attr( $checked ); ?> />
                            <div class="eccw-template-preview-image">
                                <img src="<?php echo esc_url( $img_url); ?>" alt="layout image"/>
                            </div>
                            <p class="eccw-template-count"><?php echo esc_html__("Template", "easy-currency"); ?> -  <?php echo esc_html( $count); ?></p>
                        </label>
                    <?php 
                        $count++;
                    endforeach; 
                    ?>
                </div>

                <?php if (!empty($desc)): ?>
                    <p class="description"><?php echo wp_kses_post( $desc); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }

    public function eccw_admin_field_switcher_show_hide($field)
    {
        
        $value     = get_option($field['id'], $field['default'] ?? '');
        $desc      = ! empty($field['desc']) ? $field['desc'] : '';
        $desc_tip  = ! empty($field['desc_tip']) ? $field['desc_tip'] : false;
        $eccw_pro  = ! empty($field['eccw_pro']) ? $field['eccw_pro'] : false;

        $pro_enable =  $eccw_pro && !class_exists('ECCW_CURRENCY_SWITCHER_PRO');

        ?>
        <tr valign="top" class="<?php echo esc_attr($field['class']); ?>">
            <th scope="row" class="titledesc">
                <?php echo esc_html($field['title']); ?>
                <?php if ( $pro_enable ) : ?>
                (<span class="eccw-pro-lock">PRO</span>)
                <?php endif; ?>
                <?php if ( $desc_tip ) : ?>
                    <span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $field['description'] ?? '' ); ?>"></span>
                <?php endif; ?>
            </th>
            <td class="forminp forminp-checkbox">

                <label class="eccw-switch<?php echo $pro_enable ? ' eccw-pro-lock-tooltip' : ''; ?> "
                    <?php if ( $pro_enable ) : ?>
                        data-tooltip="<?php echo esc_attr__('This feature is available in Pro version', 'easy-currency'); ?>"
                    <?php endif; ?>>
                    <input type="hidden" name="<?php echo esc_attr($field['id']); ?>" value="no" />
                   <input type="checkbox" 
                    name="<?php echo esc_attr($field['id']); ?>" 
                    value="yes" 
                    <?php echo ($value === 'yes' || $value == 1 ) ? 'checked="checked"' : ''; ?>
                    <?php echo $pro_enable ? 'disabled="disabled"' : ''; ?> />
                    <span class="eccw-slider"></span>
                </label>
                <?php if (!empty($field['desc'])) : ?>
                        <p class="description"><?php echo esc_html($field['desc']); ?></p>
                    <?php endif; ?>
                
               
            </td>
        </tr>
        <?php
    }


    public function eccw_eccw_add_fixed_pricing_options() {

        global $post;
    
        if (!function_exists('WC')) {
            return;
        }

        if ( $this->fixed_price_eachproduct != 'yes' && $this->fixed_price_eachproduct != 1 ) {
            return; 
        }

        $countries = eccw_get_available_countries(); 
        $eccw_pricing_fixed_rules = get_post_meta($post->ID, '_eccw_pricing_fixed_rules', true);
        $eccw_pricing_fixed_rules = is_array($eccw_pricing_fixed_rules) ? $eccw_pricing_fixed_rules : [];
    
        echo '<div class="options_group eccw_fixed_price_options_group">';
        echo '<h4 style="margin-top: 20px;">Easy fixed Price rule</h4>';
        
        echo '<div id="eccw_pricing_fixed_rules_container">';
        
        foreach ($eccw_pricing_fixed_rules as $index => $rule) {
            $regular_price = isset($rule['regular_price']) ? esc_attr($rule['regular_price']) : '';
            $sale_price = isset($rule['sale_price']) ? esc_attr($rule['sale_price']) : '';
            $selected_country = isset($rule['country']) ? esc_attr($rule['country']) : '';
    
            echo '<div class="fixed_price_rule_item">';
            echo '<input type="text" name="eccw_pricing_fixed_rules[' . esc_attr( $index ) . '][regular_price]" value="' . esc_attr( $regular_price ) . '" placeholder="Regular Price" class="eccw_fixed_regular_price_input">';
            echo '<input type="text" name="eccw_pricing_fixed_rules[' . esc_attr( $index ). '][sale_price]" value="' . esc_attr( $sale_price ) . '" placeholder="Sale Price" class="eccw_fixed_sale_price_input">';
            
            echo '<select name="eccw_pricing_fixed_rules[' . esc_attr( $index ) . '][country]" class="eccw_fixed_price_country_select">';
            echo '<option value="">Select Country</option>';
            foreach ($countries as $code => $name) {
            $selected = ($code === $selected_country) ? 'selected' : '';
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($code),
                esc_attr($selected),
                esc_html($name)
            );
        }
            echo '</select>';
    
            echo '<button type="button" class="remove_fixed_price_rule button">Remove</button>';
            echo '</div>';
        }
    
        echo '</div>';
        
        echo '<button type="button" class="button add_fixed_price_rule">Add Rule</button>';
        echo '</div>';

    }

    public function eccw_update_fixed_pricing_options($post_id){

        if ( $this->fixed_price_eachproduct != 'yes' && $this->fixed_price_eachproduct != 1 ) {
            return; 
        }
        
         // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( isset( $_POST['eccw_pricing_fixed_rules'] ) && is_array( $_POST['eccw_pricing_fixed_rules'] ) ) {

            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing
            $rules = eccw_recursive_sanitize( wp_unslash( $_POST['eccw_pricing_fixed_rules'] ) );

            update_post_meta( $post_id, '_eccw_pricing_fixed_rules', array_values( $rules ) );

        } else {
            delete_post_meta( $post_id, '_eccw_pricing_fixed_rules' );
        }
    }



    public function eccw_add_fixed_pricing($price, $product) {

        if ( $this->fixed_price_eachproduct != 'yes' && $this->fixed_price_eachproduct != 1 ) {
            return; 
        }

        $eccw_pricing_fixed_rules = get_post_meta($product->get_id(), '_eccw_pricing_fixed_rules', true);
       
        $default_currency = isset($_COOKIE['user_preferred_currency']) && !empty($_COOKIE['user_preferred_currency'])
            ? sanitize_text_field(wp_unslash($_COOKIE['user_preferred_currency']))
            :  '';
        
        $welcome_currency = eccw_get_first_visit_currency();

        if ( !empty($welcome_currency) && !isset($_COOKIE['user_preferred_currency']) && empty($_COOKIE['user_preferred_currency']) ) {
            $default_currency = $welcome_currency;
        }

        $easy_indivisual_fixed_price = [];

        if ( is_array($eccw_pricing_fixed_rules) && !empty($eccw_pricing_fixed_rules) ) {

            foreach ( $eccw_pricing_fixed_rules as $index => $rule ) {

                $ccode     = isset( $rule['country'] ) ? $rule['country'] : '';

                if ( $ccode == $default_currency ) {

                    $regular_price = !empty($rule['regular_price']) ? (float) $rule['regular_price'] : '';
                    $sale_price    = !empty($rule['sale_price']) ? (float) $rule['sale_price'] : '';

                    if ( $regular_price === '' ) {
                        $sale_price = '';
                    }

                    if ( $sale_price !== '' && $regular_price !== '' && $sale_price >= $regular_price ) {
                        $sale_price = '';
                    }

                    $easy_indivisual_fixed_price = [
                        'easy_fixed_rule_regular_price' => $regular_price,
                        'easy_fixed_rule_sale_price'    => $sale_price,
                    ];
                }
            }
        }

        return $easy_indivisual_fixed_price;
    }

    public function eccw_fixed_price_rule() {
        
        $currency_settings = get_option('eccw_currency_settings', []);
        
        $advanced_settings = isset($currency_settings['advanced_settings']) ? $currency_settings['advanced_settings'] : [];

        if (
            !isset($advanced_settings['eccw_enable_fixed_price_rule']) ||
            !in_array($advanced_settings['eccw_enable_fixed_price_rule'], ['yes', '1'], true)
        ) {
            return false;
        }

        return true;
    }

}

ECCW_admin_settings_Customfields::get_instance();