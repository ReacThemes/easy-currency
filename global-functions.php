<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly

function eccw_get_pages_list_for_select()
{

    $special_pages = array(
        'home'              => 'Home Page',
        'single'            => 'Single Post/Page',
        'shop'              => 'Shop Page',
        'category'          => 'Category Page',
        'front_page'        => 'Front Page',
        'woocommerce'       => 'WooCommerce',
        'product_category'  => 'Product Category Page',
        'cart'              => 'Cart Page',
        'product'           => 'Single Product Page',
        'checkout'          => 'Checkout Page',
        'product_tag'       => 'Product Tag Page',
        'blog'              => 'Blog Page'
    );

    $special_page_ids = array();
    if (function_exists('wc_get_page_id')) {
        $special_page_ids[] = wc_get_page_id('shop');
        $special_page_ids[] = wc_get_page_id('cart');
        $special_page_ids[] = wc_get_page_id('checkout');
        $special_page_ids[] = wc_get_page_id('myaccount');
    }

    $special_page_ids = array_filter($special_page_ids, function ($id) {
        return $id > 0;
    });

    $pages = get_posts(array(
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
        'posts_per_page' => -1,
        'post__not_in'   => $special_page_ids
    ));

    $normal_pages = array();
    if (!empty($pages)) {
        foreach ($pages as $page) {
            $normal_pages[(string)$page->ID] = $page->post_title;
        }
    }

    $options = array(
        'Special Pages' => $special_pages,
        'Normal Pages'  => $normal_pages
    );

    return $options;
}


function eccw_get_currency_common_settings()
{

    $ecccw_get_plugin_settings = new ECCW_Plugin_Settings();
    $plugin_settings = $ecccw_get_plugin_settings->ecccw_get_plugin_settings();

    $admin_settings = new ECCW_admin_settings();
    $currency_settings = $plugin_settings;
    $eccw_currency_table = isset($currency_settings['eccw_currency_table']) ? $currency_settings['eccw_currency_table'] : [];
    $default_currency = isset($_COOKIE['user_preferred_currency']) && !empty($_COOKIE['user_preferred_currency'])
        ? sanitize_text_field(wp_unslash($_COOKIE['user_preferred_currency']))
        : (isset($currency_settings['default_currency']) ? $currency_settings['default_currency'] : 'USD');

    $options = isset($currency_settings['options']) ? $currency_settings['options'] : [];
    $flag_visibility = isset($options['flag_visibility']) && !empty($options['flag_visibility']) ? $options['flag_visibility'] : 'no';

    $currency_countries = wp_remote_get(ECCW_DIR_URL . '/admin/assets/json/currency-countries.json', []);

    return [
        'eccw_currency_table' => $eccw_currency_table,
        'default_currency' => $default_currency,
        'flag_visibility' => $flag_visibility,
        'currency_countries' => $currency_countries,
    ];
}
