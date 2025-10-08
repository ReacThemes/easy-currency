<?php
/**
 * Easy Currency Menu Shortcode
 *
 * @package Easy_Currency_Menu_Shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Frontend Methods
 */
trait Easy_Currency_Menu_Shortcode_Frontend {

    public function has_shortcode( $content ): bool {
        if ( false !== strpos( $content, '[' ) ) {
            preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER );
            return ! empty( $matches );
        }
        return false;
    }

    public function start_el( $item_output, $item ) {
        if ( ! is_object( $item ) || ! isset( $item->object ) ) {
            return $item_output;
        }

        if ( 'easy_eccw_menu_type' !== $item->object ) {
            return do_shortcode(
                ( isset( $item->post_title ) && 'FULL HTML OUTPUT' === $item->post_title )
                    ? $item->url
                    : $item_output
            );
        } elseif ( isset( $item->description ) ) {
            return do_shortcode( $item->description );
        }

        return $item_output;
    }

    public function setup_item( $item ) {
        if ( ! is_object( $item ) ) return $item;

        if ( 'easy_eccw_menu_type' === $item->object ) {
            $item->type_label = __( 'Shortcode', 'easy-currency' );
            if ( ! empty( $item->post_content ) ) {
                $item->description = $item->post_content;
            } else {
                $item->description  = get_transient( 'eccw_get_description_transient_' . $item->object_id );
                $item->shortcode_id = get_transient( 'eccw_get_shortcode_id_' . $item->object_id );
                delete_transient( 'eccw_get_description_transient_' . $item->object_id );
                delete_transient( 'eccw_get_shortcode_id_' . $item->object_id );
            }
        }

        return $item;
    }
}

/**
 * Admin Methods
 */
trait Easy_Currency_Menu_Shortcode_Admin {

    public function eccw_meta_box_func() {
        add_meta_box(
            'eccw-currency-menu-shortcode',
            __( 'Easy Currency Shortcode', 'easy-currency' ),
            [ $this, 'eccw_meta_render_func' ],
            'nav-menus',
            'side',
            'default'
        );
    }

    public function enqueue( $hook ) {
        if ( 'nav-menus.php' !== $hook ) return;

        wp_enqueue_script(
            'eccw-shortcode-menu-script',
            ECCW_PL_URL . 'admin/assets/js/nav-menu.js',
            [ 'nav-menu' ],
            time(),
            true
        );

        wp_localize_script( 'eccw-shortcode-menu-script', 'eccw_nav_menu_ajax', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'eccw_add_menu_item' ),
        ] );
    }

    public function description_hack() {

		$nonce = isset($_POST['description-nonce']) ? sanitize_text_field( wp_unslash($_POST['description-nonce']) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'eccw-menu-item-nonce' ) ) {
			wp_die();
		}

        $items = isset($_POST['menu-item']) && is_array($_POST['menu-item'])
        ? map_deep(wp_unslash($_POST['menu-item']), 'sanitize_text_field')
        : [];

        $object_id = isset($item['menu-item-object-id']) ? intval($item['menu-item-object-id']) : 0;
        $description = isset($item['menu-item-description']) ? sanitize_text_field($item['menu-item-description']) : '';

        if ($object_id && $description) {
            set_transient('eccw_get_description_transient_' . $object_id, $description);
        }

        $object_id = $this->eccw_menu_object_id( $object_id );
        echo esc_js( $object_id );
        wp_die();
    }

    public function eccw_ajax_menu_add_item() {
        check_ajax_referer( 'add-menu_item', 'menu-settings-column-nonce' );

        if ( ! current_user_can( 'edit_theme_options' ) ) {
            wp_die( -1 );
        }

        require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

        $menu_items_data = [];
        $menu_item = isset($_POST['menu-item']) && is_array($_POST['menu-item'])
        ? map_deep(wp_unslash($_POST['menu-item']), 'sanitize_text_field')
        : [];


        foreach ( $menu_item as $menu_item_data ) {
            if (
                ! empty( $menu_item_data['menu-item-type'] ) &&
                'custom' !== $menu_item_data['menu-item-type'] &&
                'easy_eccw_menu_type' !== $menu_item_data['menu-item-type'] &&
                ! empty( $menu_item_data['menu-item-object-id'] )
            ) {

                $object_id = isset($menu_item_data['menu-item-object-id']) ? intval($menu_item_data['menu-item-object-id']) : 0;
                $menu_item_object = isset($menu_item_data['menu-item-object']) ? sanitize_key($menu_item_data['menu-item-object']) : '';

                switch ( $menu_item_data['menu-item-type'] ) {
                    case 'post_type':
                        if (!post_type_exists($menu_item_object)) {
                            $menu_item_object = '';
                        }
                        $_object = get_post( $object_id );
                        break;
                    case 'taxonomy':
                        if (!taxonomy_exists($menu_item_object)) {
                            $menu_item_object = '';
                        }
                        $_object = get_term( $object_id, $menu_item_object );
                        break;
                    default:
                        $_object = null;
                        break;
                }

                if ( $_object ) {
                    $_menu_items = array_map( 'wp_setup_nav_menu_item', [ $_object ] );
                    $_menu_item  = reset( $_menu_items );

                    $menu_item_data['menu-item-description'] = isset($_menu_item->description)
                        ? sanitize_text_field($_menu_item->description)
                        : '';
                }
            }

            $menu_items_data[] = $menu_item_data;
        }

        // Save all sanitized menu items
        $item_ids = wp_save_nav_menu_items( 0, $menu_items_data );
        if ( is_wp_error( $item_ids ) ) {
            wp_die( 0 );
        }

        $menu_items = [];
        foreach ( (array) $item_ids as $menu_item_id ) {
            $menu_obj = get_post( $menu_item_id );
            if ( ! empty( $menu_obj->ID ) ) {
                $menu_obj        = wp_setup_nav_menu_item( $menu_obj );
                $menu_obj->label = $menu_obj->title;
                $menu_items[]    = $menu_obj;
            }
        }

        $menu = filter_input( INPUT_POST, 'menu', FILTER_SANITIZE_NUMBER_INT );
        $walker_class_name = apply_filters( 'wp_edit_nav_menu_walker', 'Walker_Nav_Menu_Edit', $menu );

        if ( ! class_exists( $walker_class_name ) ) {
            wp_die( 0 );
        }

        if ( ! empty( $menu_items ) ) {
            $args = [
                'after'       => '',
                'before'      => '',
                'link_after'  => '',
                'link_before' => '',
                'walker'      => new $walker_class_name(),
            ];
            echo walk_nav_menu_tree( $menu_items, 0, (object) $args );
        }
        wp_die();
    }

    public function eccw_menu_object_id( $last_object_id ) {
        $object_id = (int) $last_object_id;
        $object_id ++;
        $object_id = ( $object_id < 1 ) ? 1 : $object_id;

        update_option( 'eccw_menus_last_object_id', $object_id );
        return $object_id;
    }

    public function eccw_meta_render_func() {
        global $_nav_menu_placeholder, $nav_menu_selected_id;

        $nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;
        $last_object_id = get_option( 'eccw_menus_last_object_id', 0 );
        $object_id      = $this->eccw_menu_object_id( $last_object_id );
        ?>
        <div class="eccw-menu-shortcode-wrapper" id="eccw-menu-shortcode-wrapper">
            <input type="hidden" class="menu-item-db-id" name="menu-item[<?php echo esc_attr( $nav_menu_placeholder ); ?>][menu-item-db-id]" value="0" />
            <input type="hidden" class="menu-item-object-id" name="menu-item[<?php echo esc_attr( $nav_menu_placeholder ); ?>][menu-item-object-id]" value="<?php echo esc_attr( $object_id ); ?>" />
            <input type="hidden" class="menu-item-object" name="menu-item[<?php echo esc_attr( $nav_menu_placeholder ); ?>][menu-item-object]" value="easy_eccw_menu_type" />
            <input type="hidden" class="menu-item-type" name="menu-item[<?php echo esc_attr( $nav_menu_placeholder ); ?>][menu-item-type]" value="easy_eccw_menu_type" />
            <input type="hidden" id="eccw-menu-item-nonce" value="<?php echo esc_attr( wp_create_nonce( 'eccw-menu-item-nonce' ) ); ?>" />
            <p id="menu-item-title-wrap">
                <label for="eccw-menu-shortcode-title"><?php esc_html_e( 'Title', 'easy-currency' ); ?></label>
                <input id="eccw-menu-shortcode-title" name="menu-item[<?php echo esc_attr( $nav_menu_placeholder ); ?>][menu-item-title]" type="text" class="regular-text menu-item-textbox" style="width:100%" />
            </p>
            <p id="menu-item-html-wrap">
                <textarea style="width:100%;" rows="9" id="eccw-menu-shortcode-html-field" name="menu-item[<?php echo esc_attr( $nav_menu_placeholder ); ?>][menu-item-description]" class="code menu-item-textbox"></textarea>
            </p>
            <p class="button-controls">
                <span class="add-to-menu">
                    <input type="submit" <?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?> class="button-secondary eccw-submit-menu-add-button right" value="<?php esc_attr_e( 'Add to Menu', 'easy-currency' ); ?>" name="eccw-add-shortcode-menu-item" id="eccw-add-shortcode-menu-button" />
                    <span class="spinner"></span>
                </span>
            </p>
        </div>
        <?php
    }
}

/**
 * Main Singleton Class
 */
if ( ! class_exists( 'Easy_Currency_Menu_Shortcode' ) ) {

    final class Easy_Currency_Menu_Shortcode {

        use Easy_Currency_Menu_Shortcode_Frontend;
        use Easy_Currency_Menu_Shortcode_Admin;

        private static $instance = null;

        public static function get_instance(): self {
            if ( null === static::$instance ) {
                static::$instance = new static();
            }
            return static::$instance;
        }

        private function __construct() {
            $this->init_hooks();
        }

        private function __clone() {}
        public function __wakeup() {
            throw new \Exception("Cannot unserialize singleton");
        }

        private function init_hooks() {
            // frontend
            add_filter( 'walker_nav_menu_start_el', [ $this, 'start_el' ], 20, 2 );
            add_filter( 'megamenu_walker_nav_menu_start_el', [ $this, 'start_el' ], 20, 2 );

            add_filter( 'wp_setup_nav_menu_item', [ $this, 'setup_item' ], 10, 1 );

            // admin
            if ( is_admin() ) {
                add_action( 'admin_init', [ $this, 'eccw_meta_box_func' ] );
                add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
                add_action( 'wp_ajax_eccw_get_description_transient', [ $this, 'description_hack' ] );
                add_action( 'wp_ajax_add-menu-item', [ $this, 'eccw_ajax_menu_add_item' ], 0 );
            }
        }
    }
}

Easy_Currency_Menu_Shortcode::get_instance();
