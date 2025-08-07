<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class ECCW_Plugin_Settings {


    public function ecccw_get_plugin_settings($settings_tab_key = NULL){
        $currency_settings = get_option('eccw_currency_settings');
        if($settings_tab_key != NULL&& !empty($settings_tab_key) && isset($currency_settings[$settings_tab_key])){
            $settings = $currency_settings[$settings_tab_key];
            return $settings; 
        }else{
            return $currency_settings;
        }
    }

}

new ECCW_Plugin_Settings();