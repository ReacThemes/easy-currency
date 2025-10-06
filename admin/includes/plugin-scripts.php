<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.
add_action('admin_enqueue_scripts', 'eccw_admin_enqueue_scripts');
function eccw_admin_enqueue_scripts (){

    wp_register_style( 'eccw-rangeslider', ECCW_PL_URL . 'admin/assets/lib/rangeslider/css/ion.rangeSlider.min.css', array(), ECCW_VERSION, false);
    wp_register_style( 'eccw-select2', ECCW_PL_URL . 'admin/assets/css/select2.min.css', array(), ECCW_VERSION );
    wp_register_style( 'eccw-admin', ECCW_PL_URL . 'admin/assets/css/admin.css', array(), ECCW_VERSION );
    wp_register_script( 'eccw-rangeslider', ECCW_PL_URL . 'admin/assets/lib/rangeslider/js/ion.rangeSlider.min.js', array('jquery'), ECCW_VERSION, false );
    wp_register_script( 'eccw-select2-min', ECCW_PL_URL . 'admin/assets/js/select2.min.js', array('jquery'), ECCW_VERSION, TRUE );
    wp_register_script( 'eccw-admin', ECCW_PL_URL . 'admin/assets/js/admin.js', array('jquery'), ECCW_VERSION, TRUE );
    
    wp_enqueue_style( 'eccw-rangeslider' );
    wp_enqueue_style( 'eccw-select2' );
    wp_enqueue_style( 'eccw-admin' );

    
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');

    wp_enqueue_script( 'eccw-rangeslider' );
    wp_enqueue_script( 'eccw-select2-min' );
    wp_enqueue_script( 'eccw-admin' );

    $countries = eccw_get_available_countries(); 
    
    wp_localize_script(
        'eccw-admin', 
        'eccw_vars',
        [
            'ajaxurl'          => admin_url( 'admin-ajax.php' ),
            'adminURL'         => admin_url(),
            'nonce'            => wp_create_nonce('eccw_nonce'),
            'version'          => ECCW_VERSION,
            'pluginURL'        => ECCW_DIR_URL,
            'countries'        => $countries,
        ]
    );


    wp_enqueue_style( 'woocommerce_admin_styles' );
    wp_enqueue_script( 'wc-admin' );

}