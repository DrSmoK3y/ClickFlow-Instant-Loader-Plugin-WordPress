<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LM_INAV_Exclusions
 * Evaluates route eligibility to prevent breaking admin, previews, or sensitive transactions.
 */
class LM_INAV_Exclusions {

    private $settings;

    public function __construct( $settings ) {
        $this->settings = $settings;
    }

    public function is_request_excluded() {
        // Exclude logged in administrators / editors if configured (recommended default)
        if ( $this->settings->get( 'disable_for_logged_in', true ) && is_user_logged_in() ) {
            return true;
        }

        // Exclude WordPress admin panel, theme customizer, post previews, and REST requests
        if ( is_admin() || is_customize_preview() || is_preview() || wp_is_json_request() ) {
            return true;
        }

        // Exclude password-protected content
        if ( post_password_required() ) {
            return true;
        }

        // Exclude Elementor Live Editor and Preview iframe
        if ( isset( $_GET['elementor-preview'] ) || ( isset( $_GET['action'] ) && 'elementor' === $_GET['action'] ) ) {
            return true;
        }

        // Exclude WooCommerce dynamic endpoints & cart actions
        if ( isset( $_GET['add-to-cart'] ) || isset( $_GET['wc-ajax'] ) ) {
            return true;
        }
        if ( function_exists( 'is_cart' ) && is_cart() ) return true;
        if ( function_exists( 'is_checkout' ) && is_checkout() ) return true;
        if ( function_exists( 'is_account_page' ) && is_account_page() ) return true;

        // Exclude search queries from SPA prefetch to prevent database spike
        if ( is_search() || isset( $_GET['s'] ) ) {
            return true;
        }

        // Exclude sensitive WordPress actions, tokens, and nonces
        if ( isset( $_GET['_wpnonce'] ) || isset( $_GET['key'] ) || isset( $_GET['token'] ) ) {
            return true;
        }

        if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'logout', 'postpass', 'rp', 'resetpass', 'delete', 'trash' ), true ) ) {
            return true;
        }

        // Exclude order and cart modifying endpoints
        if ( isset( $_GET['order-received'] ) || isset( $_GET['cancel_order'] ) || isset( $_GET['customer-logout'] ) || isset( $_GET['remove_item'] ) || isset( $_GET['empty-cart'] ) ) {
            return true;
        }

        $uri      = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        $patterns = $this->get_excluded_patterns();

        foreach ( $patterns as $pattern ) {
            $pattern = trim( $pattern );
            if ( empty( $pattern ) ) {
                continue;
            }
            if ( fnmatch( $pattern, $uri ) ) {
                return true;
            }
        }

        return false;
    }

    public function get_excluded_patterns() {
        $raw = $this->settings->get( 'excluded_urls', '' );
        if ( empty( $raw ) ) {
            return array( '/wp-admin/*', '/wp-login.php*', '/cart/*', '/checkout/*', '/my-account/*', '*customer-logout*', '*logout*', '*cancel-order*', '*order-received*' );
        }
        return explode( "\n", str_replace( "\r", '', $raw ) );
    }
}
