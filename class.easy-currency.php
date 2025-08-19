<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

define( 'ECCW_ID', 5671 );

class ECCW_CURRENCY_SWITCHER {
	
    private static $_instance = null;

	/**
	 * Instance
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @static
	 *
	 * @return Elementor_Test_Extension An instance of the class.
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;

	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'init' ] );
	}

	/**
	 * Initialize the plugin
	 *
	 * Load the plugin only after Elementor (and other plugins) are loaded.
	 * Checks for basic plugin requirements, if one check fail don't continue,
	 * if all check have passed load the files required to run the plugin.
	 *
	 * Fired by `plugins_loaded` action hook.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function init() {


		// Add Plugin actions
		add_filter( 'plugin_action_links_' . ECCW_PLUGIN_BASE, [ $this, 'eccw_plugin_action_links' ], 10, 4 );
		add_action( 'elementor/widgets/register', [ $this, 'init_widgets' ] );
		add_action( 'elementor/elements/categories_registered', [ $this, 'add_category' ] );

	}

	function eccw_plugin_action_links( $plugin_actions, $plugin_file, $plugin_data, $context ) {

		$new_actions = array();
		$new_actions['eccw_plugin_actions_setting'] = '<a href="'.admin_url( 'admin.php?page=wc-settings&tab=eccw_settings_tab' ).'">Settings</a>';
		return array_merge( $new_actions, $plugin_actions );

	}

	public function add_category( $elements_manager ) {
        $elements_manager->add_category(
            'easy_currency_category',
            [
                'title' => esc_html__('Easy Currecny', 'easy-currency' ),
                'icon' => 'fa fa-smile-o',
            ]
        );
    }

    public function init_widgets() {
		
		//Switcher Widget		
		require_once(__DIR__ . '/public/includes/widget.php');
		\Elementor\Plugin::instance()->widgets_manager->register(new \Easy_Currency_Switcher_Widget());
    }
}