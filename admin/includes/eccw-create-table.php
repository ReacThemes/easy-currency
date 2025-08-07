<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.
    global $wpdb;
    $table_name = $wpdb->prefix . 'eccw_shortcodes';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        shortcode text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);