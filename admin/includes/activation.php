<?php
function eccw_save_plugin_default_settings() {

    $option_name = 'eccw_currency_settings';
    
    $default_settings = array(
        'design' => array(
            'switcher_button' => array(


                'width' => 52
            ),
            'switcher_dropdown' => array(
                'width' => 180
            )
        )
    );

    // Retrieve current option value
    $saved_options = get_option($option_name);

    // If the option does not exist, save the default settings
    if (!$saved_options) {
        update_option($option_name, $default_settings);
    }

    include_once ECCW_PL_PATH . '/admin/includes/eccw-create-table.php';
}