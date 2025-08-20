<?php
/**
 * Main class of the plugin interacting with WordPress.
 *
 * @package Shortcode_In_Menus
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Shortcode_In_Menus' ) ) {

	/**
	 * Handles Shortcode in Menus plugin interactions with WordPress.
	 *
	 * @since 3.2
	 */
	class Shortcode_In_Menus {

		/**
		 * Current instance of the class object.
		 *
		 * @since 3.2
		 * @access protected
		 * @static
		 *
		 * @var Shortcode_In_Menus
		 */
		protected static $instance = null;

		/**
		 * Returns the current instance of the class Shortcode_In_Menus.
		 *
		 * @since 3.2
		 * @access public
		 * @static
		 *
		 * @return Shortcode_In_Menus Returns the current instance of the class object.
		 */
		public static function get_instance() {

			// If the single instance hasn't been set, set it now.
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Hooks, filters and registers everything appropriately.
		 *
		 * @since 3.2
		 * @access public
		 */
		public function __construct() {

			// register a test shortcode for testing.
			add_shortcode( 'gs_test_shortcode', array( $this, 'shortcode' ) );

			// filter the menu item output on frontend.
			add_filter( 'walker_nav_menu_start_el', array( $this, 'start_el' ), 20, 2 );

			// Making it work with Max Mega Menu Plugin.
			add_filter( 'megamenu_walker_nav_menu_start_el', array( $this, 'start_el' ), 20, 2 );

			// filter the output when shortcode is saved using custom links, for legacy support.
			add_filter( 'clean_url', array( $this, 'display_shortcode' ), 1, 3 );

			// filter the menu item before display in admin and in frontend.
			add_filter( 'wp_setup_nav_menu_item', array( $this, 'setup_item' ), 10, 1 );

		}

		/**
		 * Test shortcode. Output's WordPress.org URL.
		 *
		 * @since 1.2
		 * @access public
		 *
		 * @return string Returns WordPress.org URL.
		 */
		public function shortcode() {
			return __( 'https://wordpress.org', 'easy-currency-menu-shortcode' );
		}

		/**
		 * Check if the passed content has any shortcode. Inspired from the
		 * core's has_shortcode.
		 *
		 * @since 2.0
		 * @access public
		 *
		 * @param string $content The content to check for shortcode.
		 *
		 * @return boolean Returns true if the $content has shortcode, false otherwise.
		 */
		public function has_shortcode( $content ) {

			if ( false !== strpos( $content, '[' ) ) {

				preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER );

				if ( ! empty( $matches ) ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Modifies the menu item display on frontend.
		 *
		 * @since 2.0
		 *
		 * @param string $item_output The original html.
		 * @param object $item  The menu item being displayed.
		 *
		 * @return string Modified menu item to display.
		 */
		public function start_el( $item_output, $item ) {
			// Rare case when $item is not an object, usually with custom themes.
			if ( ! is_object( $item ) || ! isset( $item->object ) ) {
				return $item_output;
			}

			// if it isn't our custom object.
			if ( 'easy_eccw_menu_type' !== $item->object ) {

				// check the legacy hack.
				if ( isset( $item->post_title ) && 'FULL HTML OUTPUT' === $item->post_title ) {

					// then just process as we used to.
					$item_output = do_shortcode( $item->url );
				} else {
					$item_output = do_shortcode( $item_output );
				}

				// if it is our object.
			} elseif ( isset( $item->description ) ) {
				// just process it.
				$item_output = do_shortcode( $item->description );
			}

			return $item_output;
		}

		/**
		 * Allows shortcode to be processed and displayed.
		 *
		 * @since 1.0
		 *
		 * @param string $url       The processed URL for displaying/saving.
		 * @param string $orig_url  The URL that was submitted, retrieved.
		 * @param string $context   Whether saving or displaying.
		 *
		 * @return string Output string after shortcode has been executed.
		 */
		public function display_shortcode( $url, $orig_url, $context ) {
			if ( 'display' === $context && $this->has_shortcode( $orig_url ) ) {
				return do_shortcode( $orig_url );
			}
			return $url;
		}

		/**
		 * Modify the menu item before display on Menu editor and in frontend.
		 *
		 * @since 2.0
		 * @access public
		 *
		 * @param object $item The menu item.
		 *
		 * @return object Modified menu item object.
		 */
		public function setup_item($item) {
            if (!is_object($item)) return $item;

            if ('easy_eccw_menu_type' === $item->object) {
                $item->type_label = __('Shortcode', 'easy-currency-menu-shortcode');

                if (!empty($item->post_content)) {
                    $item->description = $item->post_content;
                } else {
                    $item->description = get_transient('gs_sim_description_hack_' . $item->object_id);
                    $item->shortcode_id = get_transient('gs_sim_shortcode_id_' . $item->object_id);

                    delete_transient('gs_sim_description_hack_' . $item->object_id);
                    delete_transient('gs_sim_shortcode_id_' . $item->object_id);
                }
            }
            return $item;
        }


	}

}

if ( ! class_exists( 'Shortcode_In_Menus_Admin' ) && class_exists( 'Shortcode_In_Menus' ) ) {

	/**
	 * Handles admin side interactions of Shortcode in Menus plugin with WordPress.
	 *
	 * @since 3.3
	 */
	class Shortcode_In_Menus_Admin extends Shortcode_In_Menus {

		/**
		 * Current instance of the class object.
		 *
		 * @since 3.3
		 * @access protected
		 * @static
		 *
		 * @var Shortcode_In_Menus_Admin
		 */
		protected static $instance = null;

		/**
		 * Admin side hooks, filters and registers everything appropriately.
		 *
		 * @since 3.3
		 * @access public
		 */
		public function __construct() {

			// Calling parent class' constructor.
			parent::__construct();

			// Setup the meta box.
			add_action( 'admin_init', array( $this, 'setup_meta_box' ) );

			// Enqueue custom JS.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );

			// Add an ajax hack to save the html content.
			add_action( 'wp_ajax_gs_sim_description_hack', array( $this, 'description_hack' ) );

			// Hook to allow saving of shortcode in custom link metabox for legacy support.
			add_action( 'wp_loaded', array( $this, 'security_check' ) );

			// Hijack the ajax_add_menu_item function in order to save Shortcode menu item properly.
			add_action( 'wp_ajax_add-menu-item', array( $this, 'ajax_add_menu_item' ), 0 );
		}

		/**
		 * Returns the current instance of the class Shortcode_In_Menus_Admin.
		 *
		 * @since 3.3
		 * @access public
		 * @static
		 *
		 * @return Shortcode_In_Menus_Admin Returns the current instance of the
		 *                                  class object.
		 */
		public static function get_instance() {

			// If the single instance hasn't been set, set it now.
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Register our custom meta box.
		 *
		 * @since 2.0
		 * @access public
		 *
		 * @return void
		 */
		public function setup_meta_box() {
			add_meta_box( 'eccw-currency-menu-shortcode', __( 'Easy Currency Shortcode', 'easy-currency-menu-shortcode' ), array( $this, 'meta_box' ), 'nav-menus', 'side', 'default' );
		}

		
		public function enqueue( $hook ) {

			if ( 'nav-menus.php' !== $hook ) {
				return;
			}

			wp_enqueue_script( 'eccw-sim-admin', ECCW_PL_URL . 'admin/assets/js/nav-menu.js', array( 'nav-menu' ), time(), true );

            wp_localize_script('eccw-sim-admin', 'eccw_nav_menu_ajax', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('eccw_add_menu_item'),
            ]);

            
		}

		/**
		 * An AJAX based workaround to save descriptions without using the
		 * custom object type.
		 *
		 * @since 2.0
		 * @access public
		 *
		 * @return void
		 */
		public function description_hack() {
            $nonce = filter_input(INPUT_POST, 'description-nonce', FILTER_SANITIZE_STRING);
            if (!wp_verify_nonce($nonce, 'eccw-menu-item-nonce')) {
                wp_die();
            }

            $item = filter_input(INPUT_POST, 'menu-item', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);

            // Save description
            set_transient('gs_sim_description_hack_' . $item['menu-item-object-id'], $item['menu-item-description']);

            // Save selected shortcode ID too
            set_transient('gs_sim_shortcode_id_' . $item['menu-item-object-id'], $item['menu-item-select-shortcode']);

            $object_id = $this->new_object_id($item['menu-item-object-id']);
            echo esc_js($object_id);
            wp_die();
        }


		/**
		 * Allows shortcodes into the custom link URL field.
		 *
		 * @since 1.0
		 *
		 * @return void
		 */
		public function security_check() {
			if ( current_user_can( 'activate_plugins' ) ) {
				// Conditionally adding the function for database context for.
				add_filter( 'clean_url', array( $this, 'save_shortcode' ), 99, 3 );
			}
		}

		/**
		 * Ajax handler for add menu item request.
		 *
		 * This method is hijacked from WordPress default ajax_add_menu_item
		 * so need to be updated accordingly.
		 *
		 * @since 2.0
		 *
		 * @return void
		 */
		public function ajax_add_menu_item() {

			check_ajax_referer( 'add-menu_item', 'menu-settings-column-nonce' );

			if ( ! current_user_can( 'edit_theme_options' ) ) {
				wp_die( -1 );
			}

			require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

			// For performance reasons, we omit some object properties from the checklist.
			// The following is a hacky way to restore them when adding non-custom items.
			$menu_items_data = array();
			// Get the menu item. We need this unfiltered, so using FILTER_UNSAFE_RAW.
			// phpcs:ignore WordPressVIPMinimum.Security.PHPFilterFunctions.RestrictedFilter
			$menu_item = filter_input( INPUT_POST, 'menu-item', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY );
			foreach ( $menu_item as $menu_item_data ) {
				if (
				! empty( $menu_item_data['menu-item-type'] ) &&
				'custom' !== $menu_item_data['menu-item-type'] &&
				'easy_eccw_menu_type' !== $menu_item_data['menu-item-type'] &&
				! empty( $menu_item_data['menu-item-object-id'] )
				) {
					switch ( $menu_item_data['menu-item-type'] ) {
						case 'post_type':
							$_object = get_post( $menu_item_data['menu-item-object-id'] );
							break;

						case 'taxonomy':
							$_object = get_term( $menu_item_data['menu-item-object-id'], $menu_item_data['menu-item-object'] );
							break;
					}

					$_menu_items = array_map( 'wp_setup_nav_menu_item', array( $_object ) );
					$_menu_item  = reset( $_menu_items );

					// Restore the missing menu item properties.
					$menu_item_data['menu-item-description'] = $_menu_item->description;
				}

				$menu_items_data[] = $menu_item_data;
			}

			$item_ids = wp_save_nav_menu_items( 0, $menu_items_data );
			if ( is_wp_error( $item_ids ) ) {
				wp_die( 0 );
			}

			$menu_items = array();

			foreach ( (array) $item_ids as $menu_item_id ) {
				$menu_obj = get_post( $menu_item_id );
				if ( ! empty( $menu_obj->ID ) ) {
					$menu_obj        = wp_setup_nav_menu_item( $menu_obj );
					$menu_obj->label = $menu_obj->title; // don't show "(pending)" in ajax-added items.
					$menu_items[]    = $menu_obj;
				}
			}

			$menu = filter_input( INPUT_POST, 'menu', FILTER_SANITIZE_NUMBER_INT );
			/** This filter is documented in wp-admin/includes/nav-menu.php */
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$walker_class_name = apply_filters( 'wp_edit_nav_menu_walker', 'Walker_Nav_Menu_Edit', $menu );

			if ( ! class_exists( $walker_class_name ) ) {
				wp_die( 0 );
			}

			if ( ! empty( $menu_items ) ) {
				$args = array(
					'after'       => '',
					'before'      => '',
					'link_after'  => '',
					'link_before' => '',
					'walker'      => new $walker_class_name(),
				);
				echo walk_nav_menu_tree( $menu_items, 0, (object) $args );
			}
			wp_die();
		}

		/**
		 * Method to allow saving of shortcodes in custom_link URL.
		 *
		 * @since 1.0
		 *
		 * @param string $url The processed URL for displaying/saving.
		 * @param string $orig_url The URL that was submitted, retreived.
		 * @param string $context Whether saving or displaying.
		 *
		 * @return string String containing the shortcode.
		 */
		public function save_shortcode( $url, $orig_url, $context ) {

			if ( 'db' === $context && $this->has_shortcode( $orig_url ) ) {
				return $orig_url;
			}
			return $url;
		}

		/**
		 * Gets a new object ID, given the current one
		 *
		 * @since 2.0
		 * @access public
		 *
		 * @param int $last_object_id The current/last object id.
		 *
		 * @return int Returns new object ID.
		 */
		public function new_object_id( $last_object_id ) {

			// make sure it's an integer.
			$object_id = (int) $last_object_id;

			// increment it.
			$object_id ++;

			// if object_id was 0 to start off with, make it 1.
			$object_id = ( $object_id < 1 ) ? 1 : $object_id;

			// save into the options table.
			update_option( 'eccw_menus_last_object_id', $object_id );

			return $object_id;
		}

		/**
		 * Display our custom meta box.
		 *
		 * @since 2.0
		 * @access public
		 *
		 * @global int $_nav_menu_placeholder        A placeholder index for the menu item.
		 * @global int|string $nav_menu_selected_id  (id, name or slug) of the currently-selected menu.
		 *
		 * @return void
		 */
		public function meta_box() {
			global $_nav_menu_placeholder, $nav_menu_selected_id;

			$nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;

			$last_object_id = get_option( 'eccw_menus_last_object_id', 0 );
			$object_id      = $this->new_object_id( $last_object_id );

            global $ECCW_Admin_Ajax;
            $shortcodes = $ECCW_Admin_Ajax->eccw_get_all_shortcodes_cached();

            foreach ( $shortcodes as $shortcode ) {
                error_log(print_r( $shortcode, true ));
            }


			?>
			<div class="eccw-menu-shortcode-wrapper" id="eccw-menu-shortcode-wrapper">
				<input type="hidden" class="menu-item-db-id" name="menu-item[<?php echo esc_attr( $nav_menu_placeholder ); ?>][menu-item-db-id]" value="0" />
				<input type="hidden" class="menu-item-object-id" name="menu-item[<?php echo esc_attr( $nav_menu_placeholder ); ?>][menu-item-object-id]" value="<?php echo esc_attr( $object_id ); ?>" />
				<input type="hidden" class="menu-item-object" name="menu-item[<?php echo esc_attr( $nav_menu_placeholder ); ?>][menu-item-object]" value="easy_eccw_menu_type" />
				<input type="hidden" class="menu-item-type" name="menu-item[<?php echo esc_attr( $nav_menu_placeholder ); ?>][menu-item-type]" value="easy_eccw_menu_type" />
				<input type="hidden" id="eccw-menu-item-nonce" value="<?php echo esc_attr( wp_create_nonce( 'eccw-menu-item-nonce' ) ); ?>" />
				<p id="menu-item-title-wrap">
					<label for="eccw-menu-shortcode-title"><?php esc_html_e( 'Title', 'easy-currency-menu-shortcode' ); ?></label>
					<input id="eccw-menu-shortcode-title" name="menu-item[<?php echo esc_attr( $nav_menu_placeholder ); ?>][menu-item-title]" type="text" class="regular-text menu-item-textbox" title="<?php esc_attr_e( 'Title', 'easy-currency-menu-shortcode' ); ?>" style="width:100%" />
				</p>

				<p id="menu-item-html-wrap">
					<textarea style="width:100%;" rows="9" id="gs-sim-html" name="menu-item[<?php echo esc_attr( $nav_menu_placeholder ); ?>][menu-item-description]" class="code menu-item-textbox" title="<?php esc_attr_e( 'Text/HTML/shortcode here!', 'easy-currency-menu-shortcode' ); ?>"></textarea>
				</p>
                <p>
                <?php if ( ! empty( $shortcodes ) ) : ?>
                    <select id="eccw-menu-shortcode-id"
                            name="menu-item[<?php echo esc_attr( $nav_menu_placeholder ); ?>][menu-item-select-shortcode]"
                            class="eccw-searchable-select widefat"
                            data-placeholder="<?php esc_attr_e( 'Search for a shortcode...', 'easy-currency' ); ?>">

                        <?php foreach ( $shortcodes as $shortcode ) { 
                            ?>
                            <option value="<?php echo esc_attr($shortcode['id']); ?>">
                                <?php echo esc_attr($shortcode['switcher_name']); ?>
                            </option>
                        <?php } ?>

                    </select>
                </p>

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


				<p class="button-controls">
					<span class="add-to-menu">
						<input type="submit" <?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu', 'easy-currency-menu-shortcode' ); ?>" name="eccw-add-shortcode-menu-item" id="eccw-add-shortcode-menu-button" />
						<span class="spinner"></span>
					</span>
				</p>

			</div>
			<?php
		}

	}

}