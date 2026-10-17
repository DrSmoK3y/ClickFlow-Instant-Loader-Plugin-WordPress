<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LM_INAV_Assets / ClickFlow_Assets
 * Enqueues frontend scripts, modular adapters, and CSS transitions.
 */
class LM_INAV_Assets {

    private $settings;

    public function __construct( $settings ) {
        $this->settings = $settings;
    }

    public function init() {
        if ( (bool) $this->settings->get( 'speculation_rules', true ) ) {
            add_action( 'wp_head', array( $this, 'render_speculation_rules' ), 5 );
        }
    }

    public function render_speculation_rules() {
        $plugin = LM_INAV_Plugin::get_instance();
        if ( $plugin->get_exclusions()->is_request_excluded() ) {
            return;
        }

        // Native W3C Speculation Rules (Chrome, Edge, Brave, Opera)
        $rules = array(
            'prefetch' => array(
                array(
                    'source'    => 'document',
                    'where'     => array(
                        'and' => array(
                            array( 'href_matches' => '/*' ),
                            array( 'not' => array( 'href_matches' => '/wp-admin/*' ) ),
                            array( 'not' => array( 'href_matches' => '/wp-login.php*' ) ),
                            array( 'not' => array( 'href_matches' => '/cart/*' ) ),
                            array( 'not' => array( 'href_matches' => '/checkout/*' ) ),
                            array( 'not' => array( 'href_matches' => '/my-account/*' ) ),
                            array( 'not' => array( 'href_matches' => '/*customer-logout*' ) ),
                            array( 'not' => array( 'href_matches' => '/*logout*' ) ),
                            array( 'not' => array( 'href_matches' => '/*resetpass*' ) ),
                            array( 'not' => array( 'href_matches' => '/*order-received*' ) ),
                            array( 'not' => array( 'href_matches' => '/*cancel-order*' ) ),
                            array( 'not' => array( 'href_matches' => '/*xmlrpc.php*' ) ),
                            array( 'not' => array( 'href_matches' => '/*wp-json/*' ) ),
                            array( 'not' => array( 'href_matches' => '/*\?*add-to-cart=*' ) ),
                            array( 'not' => array( 'href_matches' => '/*\?*wc-ajax=*' ) ),
                            array( 'not' => array( 'href_matches' => '/*\?*action=*' ) ),
                            array( 'not' => array( 'href_matches' => '/*\?*_wpnonce=*' ) ),
                            array( 'not' => array( 'href_matches' => '/*\?*key=*' ) ),
                            array( 'not' => array( 'href_matches' => '/*\?*token=*' ) ),
                            array( 'not' => array( 'href_matches' => '/*\?*s=*' ) ),
                            array( 'not' => array( 'href_matches' => '/*\?*preview=*' ) ),
                            array( 'not' => array( 'href_matches' => '/*\?*elementor-preview=*' ) ),
                        ),
                    ),
                    'eagerness' => 'conservative',
                ),
            ),
        );

        echo "\n" . '<script type="speculationrules" id="clickflow-speculationrules">' . "\n";
        echo wp_json_encode( $rules, JSON_UNESCAPED_SLASHES );
        echo "\n" . '</script>' . "\n";
    }

    public function enqueue_frontend_scripts() {
        $plugin = LM_INAV_Plugin::get_instance();
        if ( $plugin->get_exclusions()->is_request_excluded() ) {
            return;
        }

        $script_version = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || (bool) $this->settings->get( 'debug_mode', false )
            ? LM_INAV_VERSION . '.' . time()
            : LM_INAV_VERSION;

        // Progress bar and CSS transitions
        wp_enqueue_style(
            'lm-inav-style',
            LM_INAV_URL . 'assets/css/navigation.css',
            array(),
            $script_version
        );

        // Core Navigator Script
        wp_enqueue_script(
            'lm-inav-core',
            LM_INAV_URL . 'assets/js/navigator.js',
            array(),
            $script_version,
            true // Footer
        );

        // Localized Config Object passed into the router
        $config = array(
            'mode'              => $this->settings->get( 'mode', 'smart_builder' ),
            'contentSelector'   => $this->settings->get( 'content_selector', 'main, #primary, #main, .site-main, .elementor-location-single' ),
            'headerSelector'    => $this->settings->get( 'header_selector', 'header.site-header' ),
            'footerSelector'    => $this->settings->get( 'footer_selector', 'footer.site-footer' ),
            'prefetch'          => (bool) $this->settings->get( 'enable_prefetch', true ),
            'prefetchTrigger'   => $this->settings->get( 'prefetch_trigger', 'all' ),
            'prefetchDelay'     => (int) $this->settings->get( 'prefetch_delay', 50 ),
            'cacheMaxPages'     => (int) $this->settings->get( 'cache_max_pages', 25 ),
            'cacheTTL'          => (int) $this->settings->get( 'cache_ttl', 300 ),
            'speculationRules'  => (bool) $this->settings->get( 'speculation_rules', true ),
            'offcanvasDismiss'  => (bool) $this->settings->get( 'offcanvas_dismiss', true ),
            'viewTransitions'   => (bool) $this->settings->get( 'view_transitions', true ),
            'adapters'          => array(
                'elementor'   => (bool) $this->settings->get( 'enable_elementor', true ),
                'gutenberg'   => (bool) $this->settings->get( 'enable_gutenberg', true ),
                'classic'     => (bool) $this->settings->get( 'enable_classic', true ),
                'woocommerce' => true,
            ),
            'debug'             => (bool) $this->settings->get( 'debug_mode', false ),
            'homeUrl'           => trailingslashit( home_url() ),
        );

        wp_localize_script( 'lm-inav-core', 'clickflowConfig', $config );
        wp_localize_script( 'lm-inav-core', 'lmInavConfig', $config );
    }
}
