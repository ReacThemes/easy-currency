<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

if(! is_admin())  add_action('wp_enqueue_scripts', 'eccw_wp_enqueue_scripts');

function eccw_wp_enqueue_scripts (){

    $currency_server = new ECCW_CURRENCY_SERVER();
    $preferred_currency_data = $currency_server->eccw_get_user_preferred_currency_data();

    // Enqueue Dashicons
    wp_register_style( 'eccw-style', ECCW_PL_URL . 'public/assets/css/public.css', array(), ECCW_VERSION );
    wp_register_script( 'eccw-public', ECCW_PL_URL . 'public/assets/js/public-script.js', array('jquery', 'wp-hooks'), ECCW_VERSION, true );

    wp_enqueue_style( 'eccw-style' );
    wp_enqueue_script( 'eccw-public'  );

    wp_localize_script(
        'eccw-public', 
        'eccw_vars',
            [
                'ajaxurl'          => admin_url( 'admin-ajax.php' ),
                'adminURL'         => admin_url(),
                'nonce'            => wp_create_nonce('eccw_nonce'),
                'version'          => ECCW_VERSION,
                'pluginURL'        => ECCW_DIR_URL,
            ]
    );
}
