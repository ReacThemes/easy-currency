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
    ));

    $normal_pages = array();
    if ( ! empty( $pages ) ) {
        foreach ( $pages as $page ) {
            if ( ! in_array( $page->ID, $special_page_ids, true ) ) {
                $normal_pages[ (string) $page->ID ] = $page->post_title;
            }
        }
    }

    $options = array(
        'Special Pages' => $special_pages,
        'Normal Pages'  => $normal_pages
    );

    return $options;
}


// function eccw_get_currency_common_settings()
// {

//     $ecccw_get_plugin_settings = new ECCW_Plugin_Settings();
//     $plugin_settings = $ecccw_get_plugin_settings->ecccw_get_plugin_settings();

//     $admin_settings = new ECCW_admin_settings();
//     $currency_settings = $plugin_settings;
//     $eccw_currency_table = isset($currency_settings['eccw_currency_table']) ? $currency_settings['eccw_currency_table'] : [];
//     $default_currency = isset($_COOKIE['user_preferred_currency']) && !empty($_COOKIE['user_preferred_currency'])
//         ? sanitize_text_field(wp_unslash($_COOKIE['user_preferred_currency']))
//         : (isset($currency_settings['default_currency']) ? $currency_settings['default_currency'] : 'USD');

//     $options = isset($currency_settings['options']) ? $currency_settings['options'] : [];
//     $flag_visibility = isset($options['flag_visibility']) && !empty($options['flag_visibility']) ? $options['flag_visibility'] : 'no';

//     $currency_countries = wp_remote_get(ECCW_DIR_URL . '/admin/assets/json/currency-countries.json', []);

//     return [
//         'eccw_currency_table' => $eccw_currency_table,
//         'default_currency' => $default_currency,
//         'flag_visibility' => $flag_visibility,
//         'currency_countries' => $currency_countries,
//     ];
// }

function eccw_get_currency_common_settings() {

    $ecccw_get_plugin_settings = new ECCW_Plugin_Settings();
    $plugin_settings = $ecccw_get_plugin_settings->ecccw_get_plugin_settings();

    $admin_settings = new ECCW_admin_settings();
    $currency_settings = $plugin_settings;

    $eccw_currency_table = isset($currency_settings['eccw_currency_table']) ? $currency_settings['eccw_currency_table'] : [];

    $options = isset($currency_settings['options']) ? $currency_settings['options'] : [];
    $flag_visibility = isset($options['flag_visibility']) && !empty($options['flag_visibility']) ? $options['flag_visibility'] : 'no';

    $currency_countries = wp_remote_get(ECCW_DIR_URL . '/admin/assets/json/currency-countries.json', []);

    // -----------------------
    // Step 1: Default currency from cookie or plugin default
    $default_currency = isset($_COOKIE['user_preferred_currency']) && !empty($_COOKIE['user_preferred_currency'])
        ? sanitize_text_field(wp_unslash($_COOKIE['user_preferred_currency']))
        : (isset($currency_settings['default_currency']) ? $currency_settings['default_currency'] : 'USD');

    // -----------------------
    // Step 2: GeoIP detection
    if (class_exists('EASY_GEOIP_Currency_Detection')) {
        $geo_data = EASY_GEOIP_Currency_Detection::get_instance()->eccw_set_currency_by_geoip();
        $detected_currency = EASY_GEOIP_Currency_Detection::get_instance()->eccw_detect_currency_by_country($geo_data);

        if ($detected_currency) {
            $default_currency = $detected_currency;
        }
    }

    // -----------------------
    // Step 3: Return all common settings
    return [
        'eccw_currency_table' => $eccw_currency_table,
        'default_currency' => $default_currency,
        'flag_visibility' => $flag_visibility,
        'currency_countries' => $currency_countries,
    ];
}


 /**
 * Generate custom CSS from styles array
 *
 * @param array $styles Array of CSS rules and values.
 * @return string Generated CSS.
 */
/**
 * Generate custom CSS from styles array
 *
 * @param array $styles Array of CSS rules and values.
 * @return string Generated CSS.
 */
function eccw_generate_css(array $styles)
{
    $css = '';

    foreach ($styles as $selector => $properties) {

        if (empty(array_filter($properties))) {
            continue;
        }

        $css .= $selector . " {\n";
        foreach ($properties as $property => $value) {
            if ($value !== '') {
                $css .= "    {$property}: " . wp_strip_all_tags($value) . ";\n";
            }
        }
        $css .= "}\n";
    }

    return $css;
}

/**
 * eccw_do_shortcode function
 *
 * @param [type] $shortcode
 * @param array $atts
 * @return void
 */
function eccw_do_shortcode($shortcode, $atts = []) {
    $atts_string = '';
    foreach ($atts as $key => $value) {
        $atts_string .= $key . '="' . esc_attr($value) . '" ';
    }
    $atts_string = trim($atts_string);

    return do_shortcode("[$shortcode $atts_string]");
}


if (!function_exists('eccw_is_checkout_ajax_request')) {
    function eccw_is_checkout_ajax_request() {
        if (defined('DOING_AJAX')) {
            $ajax_request = $_REQUEST['wc-ajax'] ?? null;
            return in_array($ajax_request, array('update_order_review', 'checkout', 'get_refreshed_fragments'));
        }
        return false;
    }
}


/**
 * Forces the currency for REST API calls.
 * 
 * @param bool $is_request_to_rest_api
 * @return bool
 */
add_filter('woocommerce_rest_is_request_to_rest_api', function($is_request_to_rest_api) {
  if($is_request_to_rest_api) {
    // If the call is to the REST API, force the currency to USD
    add_filter('wc_aelia_cs_selected_currency', function($currency) {
      return 'USD';
    }, 10);
  }

  return $is_request_to_rest_api;
}, 999);



$global_countries = [
    'AF' => 'Afghanistan',
    'AX' => 'Åland Islands',
    'AL' => 'Albania',
    'DZ' => 'Algeria',
    'AS' => 'American Samoa',
    'AD' => 'Andorra',
    'AO' => 'Angola',
    'AI' => 'Anguilla',
    'AQ' => 'Antarctica',
    'AG' => 'Antigua and Barbuda',
    'AR' => 'Argentina',
    'AM' => 'Armenia',
    'AW' => 'Aruba',
    'AU' => 'Australia',
    'AT' => 'Austria',
    'AZ' => 'Azerbaijan',
    'BS' => 'Bahamas',
    'BH' => 'Bahrain',
    'BD' => 'Bangladesh',
    'BB' => 'Barbados',
    'BY' => 'Belarus',
    'PW' => 'Belau',
    'BE' => 'Belgium',
    'BZ' => 'Belize',
    'BJ' => 'Benin',
    'BM' => 'Bermuda',
    'BT' => 'Bhutan',
    'BO' => 'Bolivia',
    'BQ' => 'Bonaire, Saint Eustatius and Saba',
    'BA' => 'Bosnia and Herzegovina',
    'BW' => 'Botswana',
    'BV' => 'Bouvet Island',
    'BR' => 'Brazil',
    'IO' => 'British Indian Ocean Territory',
    'BN' => 'Brunei',
    'BG' => 'Bulgaria',
    'BF' => 'Burkina Faso',
    'BI' => 'Burundi',
    'KH' => 'Cambodia',
    'CM' => 'Cameroon',
    'CA' => 'Canada',
    'CV' => 'Cape Verde',
    'KY' => 'Cayman Islands',
    'CF' => 'Central African Republic',
    'TD' => 'Chad',
    'CL' => 'Chile',
    'CN' => 'China',
    'CX' => 'Christmas Island',
    'CC' => 'Cocos (Keeling) Islands',
    'CO' => 'Colombia',
    'KM' => 'Comoros',
    'CG' => 'Congo (Brazzaville)',
    'CD' => 'Congo (Kinshasa)',
    'CK' => 'Cook Islands',
    'CR' => 'Costa Rica',
    'HR' => 'Croatia',
    'CU' => 'Cuba',
    'CW' => 'Curaçao',
    'CY' => 'Cyprus',
    'CZ' => 'Czech Republic',
    'DK' => 'Denmark',
    'DJ' => 'Djibouti',
    'DM' => 'Dominica',
    'DO' => 'Dominican Republic',
    'EC' => 'Ecuador',
    'EG' => 'Egypt',
    'SV' => 'El Salvador',
    'GQ' => 'Equatorial Guinea',
    'ER' => 'Eritrea',
    'EE' => 'Estonia',
    'SZ' => 'Eswatini',
    'ET' => 'Ethiopia',
    'FK' => 'Falkland Islands',
    'FO' => 'Faroe Islands',
    'FJ' => 'Fiji',
    'FI' => 'Finland',
    'FR' => 'France',
    'GF' => 'French Guiana',
    'PF' => 'French Polynesia',
    'TF' => 'French Southern Territories',
    'GA' => 'Gabon',
    'GM' => 'Gambia',
    'GE' => 'Georgia',
    'DE' => 'Germany',
    'GH' => 'Ghana',
    'GI' => 'Gibraltar',
    'GR' => 'Greece',
    'GL' => 'Greenland',
    'GD' => 'Grenada',
    'GP' => 'Guadeloupe',
    'GU' => 'Guam',
    'GT' => 'Guatemala',
    'GG' => 'Guernsey',
    'GN' => 'Guinea',
    'GW' => 'Guinea-Bissau',
    'GY' => 'Guyana',
    'HT' => 'Haiti',
    'HM' => 'Heard Island and McDonald Islands',
    'HN' => 'Honduras',
    'HK' => 'Hong Kong',
    'HU' => 'Hungary',
    'IS' => 'Iceland',
    'IN' => 'India',
    'ID' => 'Indonesia',
    'IR' => 'Iran',
    'IQ' => 'Iraq',
    'IE' => 'Ireland',
    'IM' => 'Isle of Man',
    'IL' => 'Israel',
    'IT' => 'Italy',
    'CI' => 'Ivory Coast',
    'JM' => 'Jamaica',
    'JP' => 'Japan',
    'JE' => 'Jersey',
    'JO' => 'Jordan',
    'KZ' => 'Kazakhstan',
    'KE' => 'Kenya',
    'KI' => 'Kiribati',
    'KW' => 'Kuwait',
    'KG' => 'Kyrgyzstan',
    'LA' => 'Laos',
    'LV' => 'Latvia',
    'LB' => 'Lebanon',
    'LS' => 'Lesotho',
    'LR' => 'Liberia',
    'LY' => 'Libya',
    'LI' => 'Liechtenstein',
    'LT' => 'Lithuania',
    'LU' => 'Luxembourg',
    'MO' => 'Macao',
    'MG' => 'Madagascar',
    'MW' => 'Malawi',
    'MY' => 'Malaysia',
    'MV' => 'Maldives',
    'ML' => 'Mali',
    'MT' => 'Malta',
    'MH' => 'Marshall Islands',
    'MQ' => 'Martinique',
    'MR' => 'Mauritania',
    'MU' => 'Mauritius',
    'YT' => 'Mayotte',
    'MX' => 'Mexico',
    'FM' => 'Micronesia',
    'MD' => 'Moldova',
    'MC' => 'Monaco',
    'MN' => 'Mongolia',
    'ME' => 'Montenegro',
    'MS' => 'Montserrat',
    'MA' => 'Morocco',
    'MZ' => 'Mozambique',
    'MM' => 'Myanmar',
    'NA' => 'Namibia',
    'NR' => 'Nauru',
    'NP' => 'Nepal',
    'NL' => 'Netherlands',
    'NC' => 'New Caledonia',
    'NZ' => 'New Zealand',
    'NI' => 'Nicaragua',
    'NE' => 'Niger',
    'NG' => 'Nigeria',
    'NU' => 'Niue',
    'NF' => 'Norfolk Island',
    'KP' => 'North Korea',
    'MK' => 'North Macedonia',
    'MP' => 'Northern Mariana Islands',
    'NO' => 'Norway',
    'OM' => 'Oman',
    'PK' => 'Pakistan',
    'PS' => 'Palestinian Territory',
    'PA' => 'Panama',
    'PG' => 'Papua New Guinea',
    'PY' => 'Paraguay',
    'PE' => 'Peru',
    'PH' => 'Philippines',
    'PN' => 'Pitcairn',
    'PL' => 'Poland',
    'PT' => 'Portugal',
    'PR' => 'Puerto Rico',
    'QA' => 'Qatar',
    'RE' => 'Reunion',
    'RO' => 'Romania',
    'RU' => 'Russia',
    'RW' => 'Rwanda',
    'BL' => 'Saint Barthélemy',
    'SH' => 'Saint Helena',
    'KN' => 'Saint Kitts and Nevis',
    'LC' => 'Saint Lucia',
    'SX' => 'Saint Martin (Dutch part)',
    'MF' => 'Saint Martin (French part)',
    'PM' => 'Saint Pierre and Miquelon',
    'VC' => 'Saint Vincent and the Grenadines',
    'WS' => 'Samoa',
    'SM' => 'San Marino',
    'ST' => 'São Tomé and Príncipe',
    'SA' => 'Saudi Arabia',
    'SN' => 'Senegal',
    'RS' => 'Serbia',
    'SC' => 'Seychelles',
    'SL' => 'Sierra Leone',
    'SG' => 'Singapore',
    'SK' => 'Slovakia',
    'SI' => 'Slovenia',
    'SB' => 'Solomon Islands',
    'SO' => 'Somalia',
    'ZA' => 'South Africa',
    'GS' => 'South Georgia/Sandwich Islands',
    'KR' => 'South Korea',
    'SS' => 'South Sudan',
    'ES' => 'Spain',
    'LK' => 'Sri Lanka',
    'SD' => 'Sudan',
    'SR' => 'Suriname',
    'SJ' => 'Svalbard and Jan Mayen',
    'SE' => 'Sweden',
    'CH' => 'Switzerland',
    'SY' => 'Syria',
    'TW' => 'Taiwan',
    'TJ' => 'Tajikistan',
    'TZ' => 'Tanzania',
    'TH' => 'Thailand',
    'TL' => 'Timor-Leste',
    'TG' => 'Togo',
    'TK' => 'Tokelau',
    'TO' => 'Tonga',
    'TT' => 'Trinidad and Tobago',
    'TN' => 'Tunisia',
    'TR' => 'Türkiye',
    'TM' => 'Turkmenistan',
    'TC' => 'Turks and Caicos Islands',
    'TV' => 'Tuvalu',
    'UG' => 'Uganda',
    'UA' => 'Ukraine',
    'AE' => 'United Arab Emirates',
    'GB' => 'United Kingdom (UK)',
    'US' => 'United States (US)',
    'UM' => 'United States (US) Minor Outlying Islands',
    'UY' => 'Uruguay',
    'UZ' => 'Uzbekistan',
    'VU' => 'Vanuatu',
    'VA' => 'Vatican',
    'VE' => 'Venezuela',
    'VN' => 'Vietnam',
    'VG' => 'Virgin Islands (British)',
    'VI' => 'Virgin Islands (US)',
    'WF' => 'Wallis and Futuna',
    'EH' => 'Western Sahara',
    'YE' => 'Yemen',
    'ZM' => 'Zambia',
    'ZW' => 'Zimbabwe',
];