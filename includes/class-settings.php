<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ClickFlow_Settings
 * Admin management, screen isolation, options sanitization, and clean single-page dashboard.
 */
class LM_INAV_Settings {

    private $options;

    public function __construct() {
        $cf_settings = get_option( 'clickflow_settings', false );
        if ( false === $cf_settings ) {
            $cf_settings = get_option( 'lm_inav_settings', array() );
        }
        $this->options = is_array( $cf_settings ) ? $cf_settings : array();
    }

    public function init() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        // Suppress 3rd party admin notices on ClickFlow settings screen for clean UI
        add_action( 'in_admin_header', array( $this, 'suppress_admin_notices' ), 999 );
        add_action( 'admin_head', array( $this, 'suppress_admin_notices' ), 999 );
        add_action( 'admin_head', array( $this, 'inject_admin_isolation_css' ), 1000 );

        // Recursion-safe synchronization between clickflow_settings and legacy lm_inav_settings
        add_action( 'update_option_clickflow_settings', array( $this, 'sync_to_legacy_settings' ), 10, 2 );
        add_action( 'update_option_lm_inav_settings', array( $this, 'sync_to_clickflow_settings' ), 10, 2 );
    }

    /**
     * Register clean Top-Level ClickFlow Admin Menu (Single Main Page)
     * Zero font-icon dependency: uses pure inline SVG data URI.
     */
    public function add_admin_menu() {
        // Crisp lightning-bolt flow icon in clean SVG (No Dashicons!)
        $svg_icon = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>'
        );

        // 1. Primary Top-Level Menu
        add_menu_page(
            'ClickFlow (0.9 BETA)',
            'ClickFlow',
            'manage_options',
            'clickflow',
            array( $this, 'render_settings_page' ),
            $svg_icon,
            64
        );

        // Submenu: Single Main Setup Page
        add_submenu_page(
            'clickflow',
            'ClickFlow Dashboard',
            'Dashboard & Setup',
            'manage_options',
            'clickflow',
            array( $this, 'render_settings_page' )
        );

        // Backward compatibility alias for legacy bookmark /lm-instant-navigation
        add_submenu_page(
            null,
            'ClickFlow',
            'ClickFlow',
            'manage_options',
            'lm-instant-navigation',
            array( $this, 'render_settings_page' )
        );

        // Under Settings menu (options-general.php)
        add_options_page(
            'ClickFlow',
            'ClickFlow',
            'manage_options',
            'clickflow',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Suppress annoying 3rd party plugin notices (Elementor, Mailchimp, WooCommerce, themes)
     * strictly on the ClickFlow screen so the dashboard remains pristine and clutter-free.
     */
    public function suppress_admin_notices() {
        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        if ( false !== strpos( $screen->id, 'clickflow' ) || false !== strpos( $screen->id, 'lm-instant-navigation' ) ) {
            remove_all_actions( 'admin_notices' );
            remove_all_actions( 'all_admin_notices' );
            remove_all_actions( 'user_admin_notices' );
            remove_all_actions( 'network_admin_notices' );
        }
    }

    /**
     * Inject scoped CSS on ClickFlow screen to hide any late-injected DOM notices from the head.
     */
    public function inject_admin_isolation_css() {
        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        if ( false !== strpos( $screen->id, 'clickflow' ) || false !== strpos( $screen->id, 'lm-instant-navigation' ) ) {
            ?>
            <style id="clickflow-admin-isolation">
                /* Isolate ClickFlow admin page from external notices */
                .toplevel_page_clickflow .notice:not(.clickflow-allowed-notice),
                .toplevel_page_clickflow div.error,
                .toplevel_page_clickflow div.updated:not(.clickflow-allowed-notice),
                .toplevel_page_clickflow .update-nag,
                .toplevel_page_clickflow .elementor-message,
                .toplevel_page_clickflow #wpbody-content > .notice {
                    display: none !important;
                }
                /* Clean container padding */
                .toplevel_page_clickflow #wpbody-content {
                    padding-bottom: 60px;
                }
            </style>
            <?php
        }
    }

    public function register_settings() {
        // Register ClickFlow settings group
        register_setting( 'clickflow_settings_group', 'clickflow_settings', array(
            'sanitize_callback' => array( $this, 'sanitize_settings' )
        ) );

        // Maintain legacy group so existing scripts and options.php continue to sync
        register_setting( 'lm_inav_settings_group', 'lm_inav_settings', array(
            'sanitize_callback' => array( $this, 'sanitize_settings' )
        ) );
    }

    public function sanitize_settings( $input ) {
        $sanitized = array();
        $sanitized['enabled']                 = ! empty( $input['enabled'] ) ? 1 : 0;
        $sanitized['mode']                    = in_array( $input['mode'] ?? '', array( 'safe_blog', 'smart_builder', 'full_site' ), true ) ? sanitize_text_field( $input['mode'] ) : 'smart_builder';
        $sanitized['disable_for_logged_in']   = ! empty( $input['disable_for_logged_in'] ) ? 1 : 0;
        $sanitized['content_selector']        = sanitize_text_field( $input['content_selector'] ?? 'main, #primary, #main, .site-main, .elementor-location-single' );
        $sanitized['header_selector']         = sanitize_text_field( $input['header_selector'] ?? 'header.site-header' );
        $sanitized['footer_selector']         = sanitize_text_field( $input['footer_selector'] ?? 'footer.site-footer' );
        $sanitized['enable_prefetch']         = ! empty( $input['enable_prefetch'] ) ? 1 : 0;
        $sanitized['prefetch_trigger']        = in_array( $input['prefetch_trigger'] ?? '', array( 'all', 'hover', 'touch' ), true ) ? sanitize_key( $input['prefetch_trigger'] ) : 'all';
        $sanitized['prefetch_delay']          = min( 2000, max( 20, absint( $input['prefetch_delay'] ?? 65 ) ) );
        $sanitized['cache_max_pages']         = min( 50, max( 5, absint( $input['cache_max_pages'] ?? 15 ) ) );
        $sanitized['cache_ttl']               = min( 86400, max( 30, absint( $input['cache_ttl'] ?? 300 ) ) );
        $sanitized['enable_elementor']        = ! empty( $input['enable_elementor'] ) ? 1 : 0;
        $sanitized['enable_gutenberg']        = ! empty( $input['enable_gutenberg'] ) ? 1 : 0;
        $sanitized['enable_classic']          = ! empty( $input['enable_classic'] ) ? 1 : 0;
        $sanitized['litespeed_exclude_delay'] = ! empty( $input['litespeed_exclude_delay'] ) ? 1 : 0;
        $sanitized['debug_mode']                 = ! empty( $input['debug_mode'] ) ? 1 : 0;
        $sanitized['enable_early_hints']         = isset( $input['enable_early_hints'] ) ? ( ! empty( $input['enable_early_hints'] ) ? 1 : 0 ) : 1;
        $sanitized['enable_http3_altsvc']        = isset( $input['enable_http3_altsvc'] ) ? ( ! empty( $input['enable_http3_altsvc'] ) ? 1 : 0 ) : 1;
        $sanitized['enable_security_hardening']  = isset( $input['enable_security_hardening'] ) ? ( ! empty( $input['enable_security_hardening'] ) ? 1 : 0 ) : 1;
        $sanitized['excluded_urls']              = sanitize_textarea_field( $input['excluded_urls'] ?? '' );
        $sanitized['speculation_rules']          = isset( $input['speculation_rules'] ) ? ( ! empty( $input['speculation_rules'] ) ? 1 : 0 ) : 1;
        $sanitized['offcanvas_dismiss']          = isset( $input['offcanvas_dismiss'] ) ? ( ! empty( $input['offcanvas_dismiss'] ) ? 1 : 0 ) : 1;
        $sanitized['view_transitions']           = isset( $input['view_transitions'] ) ? ( ! empty( $input['view_transitions'] ) ? 1 : 0 ) : 1;
        $sanitized['last_applied_profile']       = sanitize_text_field( $input['last_applied_profile'] ?? ( $this->options['last_applied_profile'] ?? '' ) );

        return $sanitized;
    }

    /**
     * Recursion-safe sync: when clickflow_settings updates, sync to legacy lm_inav_settings.
     */
    public function sync_to_legacy_settings( $old_value, $new_value ) {
        static $syncing = false;
        if ( $syncing || ! is_array( $new_value ) ) {
            return;
        }
        $syncing = true;
        update_option( 'lm_inav_settings', $new_value );
        $syncing = false;
    }

    /**
     * Recursion-safe sync: when legacy lm_inav_settings updates, sync to clickflow_settings.
     */
    public function sync_to_clickflow_settings( $old_value, $new_value ) {
        static $syncing = false;
        if ( $syncing || ! is_array( $new_value ) ) {
            return;
        }
        $syncing = true;
        update_option( 'clickflow_settings', $new_value );
        $syncing = false;
    }

    public function render_settings_page() {
        require_once LM_INAV_PATH . 'templates/admin-settings.php';
    }

    public function get( $key, $default = null ) {
        return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
    }

    public function is_enabled() {
        return (bool) $this->get( 'enabled', true );
    }
}
