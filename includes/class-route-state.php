<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LM_INAV_Route_State / ClickFlow_Route_State
 * Injects machine-readable JSON metadata into the cached HTML output with strict JSON encoding flags.
 */
class LM_INAV_Route_State {

    private $settings;
    private $compatibility;

    public function __construct( $settings, $compatibility ) {
        $this->settings      = $settings;
        $this->compatibility = $compatibility;
    }

    public function generate_state() {
        global $post;

        $post_id  = $post ? $post->ID : 0;
        $builder  = $this->compatibility->detect_builder( $post_id );
        $is_safe  = $this->compatibility->is_builder_safe( $builder );

        // Determine post type and layout taxonomy
        $post_type = is_singular() ? get_post_type( $post_id ) : ( is_archive() ? 'archive' : ( is_search() ? 'search' : 'home' ) );
        $template  = is_singular() ? 'singular' : ( is_front_page() ? 'front_page' : 'archive' );

        // WooCommerce context detection
        $is_woo = false;
        if ( function_exists( 'is_woocommerce' ) ) {
            $is_woo = is_woocommerce() || is_cart() || is_checkout() || is_account_page();
        }

        // Elementor Canvas detection
        $is_canvas = false;
        if ( $post_id ) {
            $wp_template = get_post_meta( $post_id, '_wp_page_template', true );
            $is_canvas   = ( 'elementor_canvas' === $wp_template );
        }

        // Sidebar active detection
        $has_sidebar = is_active_sidebar( 'sidebar-1' ) || is_active_sidebar( 'primary-sidebar' ) || is_dynamic_sidebar();

        $state = array(
            'postId'            => $post_id,
            'type'              => $post_type,
            'template'          => $template,
            'builder'           => $builder,
            'isWooCommerce'     => (bool) $is_woo,
            'isElementorCanvas' => (bool) $is_canvas,
            'hasSidebar'        => (bool) $has_sidebar,
            'loggedIn'          => is_user_logged_in(),
            'modified'          => $post ? get_post_modified_time( 'c', true, $post ) : gmdate( 'c' ),
            'safeMode'          => $is_safe,
            'requiredAssets'    => $this->compatibility->get_required_assets( $post_id, $builder ),
        );

        return apply_filters( 'lm_inav_page_state', $state, $post_id );
    }

    public function render_page_state_tag() {
        $state = $this->generate_state();
        // Prevent script breakout injection using HEX encoding flags
        $json_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES;
        echo "\n" . '<script type="application/json" id="clickflow-state">' . "\n";
        echo wp_json_encode( $state, $json_flags );
        echo "\n" . '</script>' . "\n";
        // Legacy alias for existing integrations
        echo '<script type="application/json" id="lm-inav-state">' . "\n";
        echo wp_json_encode( $state, $json_flags );
        echo "\n" . '</script>' . "\n";
    }
}
