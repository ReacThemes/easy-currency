<?php
/**
 * Plugin Name: Easy Currency
 * Description: Develop your site with multiple currency and make more user friendly.
 * Plugin URI:  https://easy-currency.themewant.com/
 * Author:      Themewant
 * Author URI:  http://themewant.com/
 * Requires at least: 6.0
 * Requires PHP:7.4
 * Version:     1.0.1
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: easy-currency
 * Requires Plugins:  woocommerce
*/
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

    
    define( 'ECCW_VERSION', '1.0.1' );
    define( 'ECCW_PL_ROOT', __FILE__ );
    define( 'ECCW_PL_URL', plugins_url( '/', ECCW_PL_ROOT ) );
    define( 'ECCW_PL_PATH', plugin_dir_path( ECCW_PL_ROOT ) );
    define( 'ECCW_DIR_URL', plugin_dir_url( ECCW_PL_ROOT ) );
    define( 'ECCW_PLUGIN_BASE', plugin_basename( ECCW_PL_ROOT ) );
    define( 'ECCW_NAME', 'Easy Currency' );

    include 'global-functions.php';
    include 'admin/includes/admin-settings-custom-field.php';
    include 'admin/includes/admin-settings.php';
    include 'admin/includes/admin-menu-shortcode.php';
    include 'admin/includes/activation.php';
    include 'admin/includes/plugin-scripts.php';
   

    include 'public/includes/plugin-scripts.php';
    include 'public/includes/dynamic-css.php';
    include 'public/views/eccw-auto-switcher/eccw-auto-switcher.php';
    

    include 'class.easy-currency.php';
    include 'class.plugin-settings.php';
    
    include 'admin/includes/currency-server.php';
    include 'admin/includes/woo-functions.php';
    include 'admin/includes/admin-ajax-request.php';
    
    include 'class.easy-currency-view.php';
    
    register_activation_hook(__FILE__, 'eccw_save_plugin_default_settings');

    ECCW_CURRENCY_SWITCHER::instance();