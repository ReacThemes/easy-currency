<?php 
/**
 * Easy Currency Menu Shortcode
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Easy_Currency_Admin_Menu {

    private static $instance = null;

    private function __construct() {
        add_action( 'admin_head-nav-menus.php', [ $this, 'eccw_register_metabox' ] );
        add_action( 'wp_update_nav_menu_item', [ $this, 'eccw_save_menu_item' ], 10, 3 );
        add_filter( 'walker_nav_menu_start_el', [ $this, 'start_el' ], 20, 2 );
        add_filter( 'wp_setup_nav_menu_item', array( $this, 'setup_item' ), 10, 1 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
        add_action( 'wp_ajax_eccw_add_menu_item', [ $this, 'ajax_add_menu_item' ] );
        
    }

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Add Metabox
    public function eccw_register_metabox() {
        add_meta_box(
            'eccw_custom_shortcode_menu',
            __( 'Currency Shortcode', 'easy-currency' ),
            [ $this, 'eccw_render_metabox' ],
            'nav-menus',
            'side',
            'default'
        );
    }

    public function eccw_render_metabox() {
        ?>
        <div class="customlinkdiv">
            <p>
                <label><?php _e( 'Title', 'easy-currency' ); ?></label><br>
                <input type="text" id="eccw_menu_title" class="widefat" />
            </p>

            <p>
                <label><?php _e( 'Shortcode', 'easy-currency' ); ?></label><br>
                <textarea id="eccw_menu_shortcode" rows="4" class="widefat" placeholder="[eccw_currency_switcher id=1]"></textarea>
                <small><?php _e('Any valid shortcode.', 'easy-currency'); ?></small>
            </p>

            <p class="button-controls">
                <button type="button" id="eccw_add_menu_item" class="button button-secondary right"><?php esc_html_e('Add to Menu'); ?></button>
                <span class="spinner"></span>
            </p>
        </div>
        <?php
    }

    // Enqueue JS
    public function enqueue_admin_scripts( $hook ) {

        if ( $hook !== 'nav-menus.php' ) return;
        wp_enqueue_script( 'eccw-nav-menu', ECCW_PL_URL . 'admin/assets/js/nav-menu.js', [ 'jquery' ], ECCW_VERSION, true );
        wp_localize_script( 'eccw-nav-menu', 'eccw_nav_menu_ajax', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'eccw_add_menu_item' ),
        ]);

    }

    // Save menu item shortcode
    public function eccw_save_menu_item( $menu_id, $menu_item_db_id, $args ) {
        if ( isset( $_POST['menu-item-type'][$menu_item_db_id] ) && $_POST['menu-item-type'][$menu_item_db_id] === 'eccw_shortcode' ) {
            $shortcode = $_POST['menu-item-description'][$menu_item_db_id] ?? '';
            update_post_meta( $menu_item_db_id, '_menu_item_shortcode', wp_kses_post( $shortcode ) );
        }
    }

    public function ajax_add_menu_item() {
    // Use the same nonce WordPress uses for menus
   // check_ajax_referer( 'add-menu_item', 'menu-settings-column-nonce' );

    if ( ! current_user_can( 'edit_theme_options' ) ) {
        wp_die( -1 );
    }

    require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

    // Grab posted menu item data
    $menu_items_data = filter_input( INPUT_POST, 'menu-item', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY );

    // Our custom shortcode item
    foreach ( $menu_items_data as &$menu_item_data ) {
        if ( isset( $menu_item_data['menu-item-type'] ) && $menu_item_data['menu-item-type'] === 'eccw_shortcode' ) {
            // Save the shortcode in meta
            update_post_meta( 0, '_menu_item_shortcode', $menu_item_data['menu-item-description'] );
        }
    }

    // Use WordPress to save menu items (menu ID is sent in POST)
    $menu_id = filter_input( INPUT_POST, 'menu', FILTER_SANITIZE_NUMBER_INT );
    $item_ids = wp_save_nav_menu_items( $menu_id, $menu_items_data );

    if ( is_wp_error( $item_ids ) ) {
        wp_die( 0 );
    }

    $menu_items = [];
    foreach ( (array) $item_ids as $menu_item_id ) {
        $menu_obj = wp_setup_nav_menu_item( get_post( $menu_item_id ) );
        $menu_obj->label = $menu_obj->title; // remove "(pending)"
        $menu_items[] = $menu_obj;
    }

    // Build output like WP
    $walker_class_name = apply_filters( 'wp_edit_nav_menu_walker', 'Walker_Nav_Menu_Edit', $menu_id );
    if ( ! class_exists( $walker_class_name ) ) {
        wp_die( 0 );
    }

    if ( ! empty( $menu_items ) ) {
        $args = (object) [
            'after'       => '',
            'before'      => '',
            'link_after'  => '',
            'link_before' => '',
            'walker'      => new $walker_class_name(),
        ];
        echo walk_nav_menu_tree( $menu_items, 0, $args );
    }

    wp_die();
}



    // Frontend render
    public function start_el( $item_output, $item ) {

        if ( ! is_object( $item ) || ! isset( $item->object ) ) {
				return $item_output;
			}


        // if it isn't our custom object.
			if ( 'gs_sim' !== $item->object ) {

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

   
    public function setup_item( $item ) {
    if ( ! is_object( $item ) ) {
        return $item;
    }

    // only if it is our object.
    if ( 'gs_sim' === $item->object ) {

        // setup our label.
        $item->type_label = __( 'Shortcode', 'shortcode-in-menus' );

        if ( ! empty( $item->post_content ) ) {
            $item->description = $item->post_content;
        } else {

            // set up the description from the transient.
            $item->description = get_transient( 'gs_sim_description_hack_' . $item->object_id );

            // discard the transient.
            delete_transient( 'gs_sim_description_hack_' . $item->object_id );
        }
    }
    return $item;
}


}

Easy_Currency_Admin_Menu::get_instance();