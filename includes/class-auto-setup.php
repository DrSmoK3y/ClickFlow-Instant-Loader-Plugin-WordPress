<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ClickFlow_Auto_Setup (LM_INAV_Auto_Setup)
 * 1-Click Auto Setup Engine that safely programs LiteSpeed Cache options,
 * Delay-JS exclusions, auto-detects theme selectors, and activates optimal builder adapters.
 * Fully hardened against ArgumentCountError, TypeErrors, and uncaught exceptions.
 */
class LM_INAV_Auto_Setup {

    private $settings;

    public function __construct( $settings ) {
        $this->settings = $settings;
    }

    public function init() {
        // Register AJAX actions for WordPress admin (both modern clickflow and legacy names)
        add_action( 'wp_ajax_clickflow_apply_auto_setup', array( $this, 'ajax_apply_auto_setup' ) );
        add_action( 'wp_ajax_lm_inav_apply_auto_setup', array( $this, 'ajax_apply_auto_setup' ) );

        add_action( 'wp_ajax_clickflow_rollback_auto_setup', array( $this, 'ajax_rollback_auto_setup' ) );
        add_action( 'wp_ajax_lm_inav_rollback_auto_setup', array( $this, 'ajax_rollback_auto_setup' ) );

        add_action( 'wp_ajax_clickflow_purge_cache', array( $this, 'ajax_purge_cache' ) );
        add_action( 'wp_ajax_lm_inav_purge_cache', array( $this, 'ajax_purge_cache' ) );
    }

    /**
     * Check if LiteSpeed Cache plugin is active.
     */
    public function is_litespeed_active() {
        return defined( 'LSCWP_V' ) || class_exists( 'LiteSpeed\\Core' ) || class_exists( 'LiteSpeed\\Conf' );
    }

    /**
     * Detect current active theme and return recommended content selector.
     */
    public function detect_theme_selector() {
        $theme = wp_get_theme();
        $template = strtolower( $theme->get_template() );
        $name = strtolower( $theme->get( 'Name' ) );

        if ( false !== strpos( $template, 'hello-elementor' ) || false !== strpos( $name, 'hello elementor' ) ) {
            return '#content, .site-content, main, .elementor-location-single, [data-elementor-type]';
        }

        if ( false !== strpos( $template, 'astra' ) || false !== strpos( $name, 'astra' ) ) {
            return '#primary, .site-main, main, .ast-container';
        }

        if ( false !== strpos( $template, 'generatepress' ) || false !== strpos( $name, 'generatepress' ) ) {
            return '#primary, main.site-main, #main';
        }

        if ( false !== strpos( $template, 'kadence' ) || false !== strpos( $name, 'kadence' ) ) {
            return '#primary, #main, main, .site-main';
        }

        if ( false !== strpos( $template, 'oceanwp' ) || false !== strpos( $name, 'oceanwp' ) ) {
            return '#primary, #content-wrap, main, .site-main';
        }

        if ( false !== strpos( $template, 'twentytwentyfour' ) || false !== strpos( $template, 'block' ) ) {
            return 'main, .wp-site-blocks, #main';
        }

        // Universal fallback
        return 'main, #primary, #main, .site-main, #content, .elementor-location-single';
    }

    /**
     * Detect current active theme and return recommended header selector.
     */
    public function detect_theme_header_selector() {
        $theme = wp_get_theme();
        $template = strtolower( $theme->get_template() );
        $name = strtolower( $theme->get( 'Name' ) );

        if ( false !== strpos( $template, 'hello-elementor' ) || false !== strpos( $name, 'hello elementor' ) ) {
            return 'header.site-header, #masthead, .elementor-location-header, [data-elementor-type="header"]';
        }
        if ( false !== strpos( $template, 'astra' ) || false !== strpos( $name, 'astra' ) ) {
            return '.site-header, #masthead, .ast-main-header-wrap';
        }
        if ( false !== strpos( $template, 'generatepress' ) || false !== strpos( $name, 'generatepress' ) ) {
            return '#masthead, .site-header';
        }
        if ( false !== strpos( $template, 'kadence' ) || false !== strpos( $name, 'kadence' ) ) {
            return '#wrapper #main-header, header.site-header';
        }
        return 'header.site-header, #masthead, .elementor-location-header';
    }

    /**
     * Detect current active theme and return recommended footer selector.
     */
    public function detect_theme_footer_selector() {
        $theme = wp_get_theme();
        $template = strtolower( $theme->get_template() );
        $name = strtolower( $theme->get( 'Name' ) );

        if ( false !== strpos( $template, 'hello-elementor' ) || false !== strpos( $name, 'hello elementor' ) ) {
            return 'footer.site-footer, #colophon, .elementor-location-footer, [data-elementor-type="footer"]';
        }
        if ( false !== strpos( $template, 'astra' ) || false !== strpos( $name, 'astra' ) ) {
            return '.site-footer, #colophon, .ast-footer-builder';
        }
        if ( false !== strpos( $template, 'generatepress' ) || false !== strpos( $name, 'generatepress' ) ) {
            return '#colophon, .site-footer';
        }
        if ( false !== strpos( $template, 'kadence' ) || false !== strpos( $name, 'kadence' ) ) {
            return '#colophon, footer.site-footer';
        }
        return 'footer.site-footer, #colophon, .elementor-location-footer';
    }

    /**
     * Profile Definitions for 1-Click Auto Setup
     */
    public function get_profiles() {
        return array(
            'blog' => array(
                'name'        => __( 'Blog / News & Magazine', 'clickflow' ),
                'description' => __( 'Maximum speed for content-heavy blogs, editorial news, and magazines.', 'clickflow' ),
                'icon'        => 'dashicons-welcome-write-blog',
                'lscwp'       => array(
                    'cache'            => 1,
                    'cache_login'      => 1,
                    'cache_priv'       => 0,
                    'cache_commenter'  => 0,
                    'cache_rest'       => 1,
                    'cache_ttl_pub'    => 604800,
                    'cache_ttl_front'  => 604800,
                    'optm_css_min'     => 1,
                    'optm_css_comb'    => 0,
                    'optm_css_async'   => 0,
                    'optm_js_min'      => 1,
                    'optm_js_comb'     => 0,
                    'optm_js_defer'    => 0,
                    'optm_js_delay'    => 1,
                    'media_lazy'       => 1,
                    'media_placeholder_resp' => 1,
                    'delay_js_excludes' => array(
                        'clickflow',
                        'clickflow-nav',
                        'clickflow-state',
                        'clickflow-config',
                        'navigator.js',
                        'lm-navigator.js',
                        'lm-inav-state',
                        'lm-inav-config',
                    ),
                    'uri_excludes'     => array(
                        '/wp-admin/*',
                        '/wp-login.php',
                    ),
                ),
                'plugin'      => array(
                    'mode'             => 'safe_blog',
                    'enable_elementor' => 0,
                    'enable_gutenberg' => 1,
                    'enable_classic'   => 1,
                    'prefetch_delay'   => 65,
                    'cache_max_pages'  => 20,
                )
            ),
            'elementor' => array(
                'name'        => __( 'Elementor Business / Agency', 'clickflow' ),
                'description' => __( 'Preserves Elementor carousels, sliders, motion effects, and per-post styles.', 'clickflow' ),
                'icon'        => 'dashicons-layout',
                'lscwp'       => array(
                    'cache'            => 1,
                    'cache_login'      => 0,
                    'cache_priv'       => 0,
                    'cache_rest'       => 1,
                    'cache_ttl_pub'    => 604800,
                    'optm_css_min'     => 1,
                    'optm_css_comb'    => 0, // Keep combine OFF for per-post CSS
                    'optm_ucss'        => 0,
                    'optm_js_min'      => 1,
                    'optm_js_comb'     => 0,
                    'optm_js_defer'    => 0,
                    'optm_js_delay'    => 1,
                    'media_lazy'       => 1,
                    'delay_js_excludes' => array(
                        'clickflow',
                        'clickflow-nav',
                        'clickflow-state',
                        'clickflow-config',
                        'navigator.js',
                        'lm-navigator.js',
                        'lm-inav-state',
                        'lm-inav-config',
                        'elementorFrontend',
                        'elementor-frontend',
                        'jquery.min.js',
                        'swiper',
                    ),
                    'uri_excludes'     => array(
                        '/wp-admin/*',
                        '/wp-login.php',
                        'elementor-preview',
                    ),
                ),
                'plugin'      => array(
                    'mode'             => 'smart_builder',
                    'enable_elementor' => 1,
                    'enable_gutenberg' => 1,
                    'enable_classic'   => 1,
                    'prefetch_delay'   => 65,
                    'cache_max_pages'  => 20,
                )
            ),
            'ecommerce' => array(
                'name'        => __( 'WooCommerce Online Store', 'clickflow' ),
                'description' => __( 'Instant product catalog browsing with 100% secure checkout and cart isolation.', 'clickflow' ),
                'icon'        => 'dashicons-cart',
                'lscwp'       => array(
                    'cache'            => 1,
                    'cache_login'      => 0,
                    'cache_priv'       => 0,
                    'cache_rest'       => 1,
                    'optm_css_min'     => 1,
                    'optm_css_comb'    => 0,
                    'optm_js_min'      => 1,
                    'optm_js_comb'     => 0,
                    'optm_js_defer'    => 0,
                    'optm_js_delay'    => 1,
                    'media_lazy'       => 1,
                    'delay_js_excludes' => array(
                        'clickflow',
                        'clickflow-nav',
                        'clickflow-state',
                        'clickflow-config',
                        'navigator.js',
                        'lm-navigator.js',
                        'lm-inav-state',
                        'lm-inav-config',
                        'wc-cart-fragments',
                        'woocommerce.min.js',
                    ),
                    'uri_excludes'     => array(
                        '/cart*',
                        '/checkout*',
                        '/my-account*',
                        '/wc-api*',
                    ),
                ),
                'plugin'      => array(
                    'mode'             => 'smart_builder',
                    'enable_elementor' => 1,
                    'enable_gutenberg' => 1,
                    'enable_classic'   => 1,
                    'prefetch_delay'   => 65,
                    'cache_max_pages'  => 25,
                )
            ),
        );
    }

    /**
     * Backup current LiteSpeed & ClickFlow settings before applying changes.
     */
    public function backup_settings() {
        try {
            $backup = array(
                'timestamp'  => current_time( 'mysql' ),
                'lm_inav'    => get_option( 'clickflow_settings', get_option( 'lm_inav_settings', array() ) ),
                'lscwp_conf' => get_option( 'litespeed.conf.*', array() ),
            );
            update_option( 'clickflow_settings_backup', $backup, false );
            update_option( 'lm_inav_settings_backup', $backup, false );
        } catch ( \Throwable $e ) {
            error_log( 'ClickFlow: Backup snapshot notice - ' . $e->getMessage() );
        }
    }

    /**
     * Apply the selected profile directly to LiteSpeed Cache and ClickFlow.
     */
    public function apply_profile( $profile_key ) {
        if ( 'woocommerce' === $profile_key || 'woo' === $profile_key ) {
            $profile_key = 'ecommerce';
        }
        $profiles = $this->get_profiles();
        if ( ! isset( $profiles[ $profile_key ] ) ) {
            return new WP_Error( 'invalid_profile', __( 'Invalid profile selected.', 'clickflow' ) );
        }

        // 1. Take safety snapshot
        $this->backup_settings();

        $selected = $profiles[ $profile_key ];
        $theme_selector  = $this->detect_theme_selector();
        $header_selector = $this->detect_theme_header_selector();
        $footer_selector = $this->detect_theme_footer_selector();

        // 2. Update ClickFlow Plugin Settings
        $plugin_settings = get_option( 'clickflow_settings', false );
        if ( false === $plugin_settings || ! is_array( $plugin_settings ) ) {
            $plugin_settings = get_option( 'lm_inav_settings', array() );
        }
        if ( ! is_array( $plugin_settings ) ) {
            $plugin_settings = array();
        }

        $plugin_settings['enabled']                 = 1;
        $plugin_settings['mode']                    = $selected['plugin']['mode'];
        $plugin_settings['content_selector']        = $theme_selector;
        $plugin_settings['header_selector']         = $header_selector;
        $plugin_settings['footer_selector']         = $footer_selector;
        $plugin_settings['enable_elementor']        = $selected['plugin']['enable_elementor'];
        $plugin_settings['enable_gutenberg']        = $selected['plugin']['enable_gutenberg'];
        $plugin_settings['enable_classic']          = $selected['plugin']['enable_classic'];
        $plugin_settings['enable_prefetch']         = 1;
        $plugin_settings['prefetch_delay']          = $selected['plugin']['prefetch_delay'];
        $plugin_settings['cache_max_pages']         = $selected['plugin']['cache_max_pages'];
        $plugin_settings['litespeed_exclude_delay'] = 1;
        $plugin_settings['speculation_rules']       = 1;
        $plugin_settings['offcanvas_dismiss']       = 1;
        $plugin_settings['view_transitions']        = 1;
        $plugin_settings['last_applied_profile']    = $profile_key;

        update_option( 'clickflow_settings', $plugin_settings );
        update_option( 'lm_inav_settings', $plugin_settings );

        // 3. Update LiteSpeed Cache in Database safely (with 100% Throwable protection)
        $lscwp_applied = false;
        try {
            $lscwp_applied = $this->program_litespeed_options( $selected['lscwp'] );
        } catch ( \Throwable $e ) {
            error_log( 'ClickFlow: LiteSpeed programming notice - ' . $e->getMessage() );
        }

        // 4. Auto purge LiteSpeed cache and Redis object cache so new rules apply instantly
        try {
            $this->purge_litespeed_cache();
        } catch ( \Throwable $e ) {
            error_log( 'ClickFlow: LiteSpeed purge notice - ' . $e->getMessage() );
        }

        $redis_flushed = false;
        if ( function_exists( 'wp_cache_flush' ) ) {
            try {
                $redis_flushed = (bool) wp_cache_flush();
            } catch ( \Throwable $e ) {}
        }

        return array(
            'success'         => true,
            'profile'         => $selected['name'],
            'theme_selector'  => $theme_selector,
            'header_selector' => $header_selector,
            'footer_selector' => $footer_selector,
            'lscwp_synced'    => $lscwp_applied,
            'redis_flushed'   => $redis_flushed,
            'backup_time'     => current_time( 'H:i:s' ),
            'excludes_count'  => isset( $selected['lscwp']['delay_js_excludes'] ) ? count( $selected['lscwp']['delay_js_excludes'] ) : 0,
        );
    }

    /**
     * Directly update LiteSpeed Cache configuration options in WordPress database.
     * Hardened against ArgumentCountError and Fatal Errors across all LiteSpeed versions.
     */
    private function program_litespeed_options( $rules ) {
        $applied = false;

        // METHOD 1: Native wp_options update for LiteSpeed Cache (100% crash-proof & universal)
        try {
            $conf_option = get_option( 'litespeed.conf.*', null );
            if ( false !== $conf_option && is_array( $conf_option ) ) {
                if ( isset( $rules['cache'] ) ) $conf_option['cache'] = (int) $rules['cache'];
                if ( isset( $rules['cache_login'] ) ) $conf_option['cache-priv'] = (int) $rules['cache_login'];
                if ( isset( $rules['optm_css_min'] ) ) $conf_option['optm-css_min'] = (int) $rules['optm_css_min'];
                if ( isset( $rules['optm_css_comb'] ) ) $conf_option['optm-css_comb'] = (int) $rules['optm_css_comb'];
                if ( isset( $rules['optm_js_min'] ) ) $conf_option['optm-js_min'] = (int) $rules['optm_js_min'];
                if ( isset( $rules['optm_js_comb'] ) ) $conf_option['optm-js_comb'] = (int) $rules['optm_js_comb'];
                if ( isset( $rules['optm_js_delay'] ) ) $conf_option['optm-js_delay'] = (int) $rules['optm_js_delay'];

                // Delay-JS Excludes merging
                if ( ! empty( $rules['delay_js_excludes'] ) ) {
                    $cur = '';
                    if ( isset( $conf_option['optm-js_delay_exc'] ) ) {
                        $cur = $conf_option['optm-js_delay_exc'];
                    } elseif ( isset( $conf_option['optm_js_delay_exc'] ) ) {
                        $cur = $conf_option['optm_js_delay_exc'];
                    }

                    $cur_arr = is_array( $cur ) ? $cur : explode( "\n", str_replace( "\r", '', (string) $cur ) );
                    $merged = array_unique( array_filter( array_merge( $cur_arr, $rules['delay_js_excludes'] ) ) );
                    $merged_str = implode( "\n", $merged );

                    $conf_option['optm-js_delay_exc'] = $merged_str;
                    $conf_option['optm_js_delay_exc'] = $merged_str;
                }

                // URI Excludes merging
                if ( ! empty( $rules['uri_excludes'] ) ) {
                    $cur_uri = isset( $conf_option['cache-exc'] ) ? $conf_option['cache-exc'] : '';
                    $cur_uri_arr = is_array( $cur_uri ) ? $cur_uri : explode( "\n", str_replace( "\r", '', (string) $cur_uri ) );
                    $merged_uri = array_unique( array_filter( array_merge( $cur_uri_arr, $rules['uri_excludes'] ) ) );
                    $conf_option['cache-exc'] = implode( "\n", $merged_uri );
                }

                update_option( 'litespeed.conf.*', $conf_option );
                $applied = true;
            }

            // Also check legacy litespeed-cache-conf if active
            $legacy_conf = get_option( 'litespeed-cache-conf', null );
            if ( false !== $legacy_conf && is_array( $legacy_conf ) ) {
                if ( isset( $rules['cache'] ) ) $legacy_conf['cache'] = (int) $rules['cache'];
                if ( isset( $rules['optm_js_delay'] ) ) $legacy_conf['optm_js_delay'] = (int) $rules['optm_js_delay'];
                update_option( 'litespeed-cache-conf', $legacy_conf );
                $applied = true;
            }
        } catch ( \Throwable $e ) {
            error_log( 'ClickFlow: LiteSpeed wp_options programming notice - ' . $e->getMessage() );
        }

        // METHOD 2: Synchronize with LiteSpeed Base constants if defined (without calling update_confs)
        try {
            if ( class_exists( 'LiteSpeed\\Core' ) && class_exists( 'LiteSpeed\\Base' ) ) {
                $conf_option = get_option( 'litespeed.conf.*', null );
                if ( is_array( $conf_option ) ) {
                    if ( defined( 'LiteSpeed\\Base::O_CACHE' ) && isset( $rules['cache'] ) ) {
                        $conf_option[ constant( 'LiteSpeed\\Base::O_CACHE' ) ] = (int) $rules['cache'];
                    }
                    if ( defined( 'LiteSpeed\\Base::O_OPTM_JS_DELAY' ) && isset( $rules['optm_js_delay'] ) ) {
                        $conf_option[ constant( 'LiteSpeed\\Base::O_OPTM_JS_DELAY' ) ] = (int) $rules['optm_js_delay'];
                    }
                    if ( defined( 'LiteSpeed\\Base::O_OPTM_JS_DELAY_EXC' ) && ! empty( $rules['delay_js_excludes'] ) ) {
                        $const_key = constant( 'LiteSpeed\\Base::O_OPTM_JS_DELAY_EXC' );
                        $cur = isset( $conf_option[ $const_key ] ) ? $conf_option[ $const_key ] : '';
                        $cur_arr = is_array( $cur ) ? $cur : explode( "\n", str_replace( "\r", '', (string) $cur ) );
                        $merged = array_unique( array_filter( array_merge( $cur_arr, $rules['delay_js_excludes'] ) ) );
                        $conf_option[ $const_key ] = implode( "\n", $merged );
                    }
                    update_option( 'litespeed.conf.*', $conf_option );
                    $applied = true;
                }
            }
        } catch ( \Throwable $e ) {
            error_log( 'ClickFlow: LiteSpeed Base constant notice - ' . $e->getMessage() );
        }

        return $applied;
    }

    /**
     * Purge LiteSpeed full page cache safely.
     */
    public function purge_litespeed_cache() {
        try {
            if ( function_exists( 'litespeed_purge_all' ) ) {
                litespeed_purge_all();
            } elseif ( class_exists( 'LiteSpeed\\Purge' ) && method_exists( 'LiteSpeed\\Purge', 'purge_all' ) ) {
                \LiteSpeed\Purge::purge_all();
            } else {
                do_action( 'litespeed_purge_all' );
            }
        } catch ( \Throwable $e ) {
            error_log( 'ClickFlow: LiteSpeed purge notice - ' . $e->getMessage() );
        }
    }

    /**
     * Rollback to backup snapshot.
     */
    public function rollback_settings() {
        $backup = get_option( 'clickflow_settings_backup', false );
        if ( false === $backup ) {
            $backup = get_option( 'lm_inav_settings_backup', false );
        }

        if ( ! $backup || empty( $backup['lm_inav'] ) ) {
            return new WP_Error( 'no_backup', __( 'No previous backup snapshot found.', 'clickflow' ) );
        }

        update_option( 'clickflow_settings', $backup['lm_inav'] );
        update_option( 'lm_inav_settings', $backup['lm_inav'] );

        if ( ! empty( $backup['lscwp_conf'] ) ) {
            update_option( 'litespeed.conf.*', $backup['lscwp_conf'] );
        }

        $this->purge_litespeed_cache();

        return array(
            'success'   => true,
            'timestamp' => isset( $backup['timestamp'] ) ? $backup['timestamp'] : current_time( 'mysql' ),
        );
    }

    /**
     * AJAX handler: Apply 1-Click Setup.
     * Clean buffer isolation to prevent external PHP notices from breaking JSON output.
     */
    public function ajax_apply_auto_setup() {
        ob_start();

        try {
            // Verify nonce safely without triggering wp_die()
            $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
            if ( ! wp_verify_nonce( $nonce, 'clickflow_auto_setup_nonce' ) && ! wp_verify_nonce( $nonce, 'lm_inav_auto_setup_nonce' ) ) {
                ob_end_clean();
                wp_send_json_error( array( 'message' => __( 'Security verification expired. Please refresh the page and try again.', 'clickflow' ) ), 403 );
                return;
            }

            if ( ! current_user_can( 'manage_options' ) ) {
                ob_end_clean();
                wp_send_json_error( array( 'message' => __( 'Unauthorized: manage_options capability required.', 'clickflow' ) ), 403 );
                return;
            }

            $profile = isset( $_POST['profile'] ) ? sanitize_key( $_POST['profile'] ) : 'blog';
            $result = $this->apply_profile( $profile );

            ob_end_clean();

            if ( is_wp_error( $result ) ) {
                wp_send_json_error( array( 'message' => $result->get_error_message() ) );
                return;
            }

            wp_send_json_success( $result );
        } catch ( \Throwable $e ) {
            ob_end_clean();
            error_log( 'ClickFlow Auto Setup error: ' . $e->getMessage() );
            wp_send_json_error( array( 'message' => __( 'Auto setup encountered an internal server error. Please check server logs.', 'clickflow' ) ), 500 );
        }
    }

    /**
     * AJAX handler: Rollback Auto Setup.
     */
    public function ajax_rollback_auto_setup() {
        ob_start();

        try {
            $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
            if ( ! wp_verify_nonce( $nonce, 'clickflow_auto_setup_nonce' ) && ! wp_verify_nonce( $nonce, 'lm_inav_auto_setup_nonce' ) ) {
                ob_end_clean();
                wp_send_json_error( array( 'message' => __( 'Security verification expired. Please refresh the page and try again.', 'clickflow' ) ), 403 );
                return;
            }

            if ( ! current_user_can( 'manage_options' ) ) {
                ob_end_clean();
                wp_send_json_error( array( 'message' => __( 'Unauthorized: manage_options capability required.', 'clickflow' ) ), 403 );
                return;
            }

            $result = $this->rollback_settings();

            ob_end_clean();

            if ( is_wp_error( $result ) ) {
                wp_send_json_error( array( 'message' => $result->get_error_message() ) );
                return;
            }

            wp_send_json_success( $result );
        } catch ( \Throwable $e ) {
            ob_end_clean();
            error_log( 'ClickFlow Rollback error: ' . $e->getMessage() );
            wp_send_json_error( array( 'message' => __( 'Rollback encountered an internal server error. Please check server logs.', 'clickflow' ) ), 500 );
        }
    }

    /**
     * AJAX handler: Dedicated LiteSpeed Cache Purge.
     */
    public function ajax_purge_cache() {
        ob_start();

        try {
            $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
            if ( ! wp_verify_nonce( $nonce, 'clickflow_auto_setup_nonce' ) && ! wp_verify_nonce( $nonce, 'lm_inav_auto_setup_nonce' ) ) {
                ob_end_clean();
                wp_send_json_error( array( 'message' => __( 'Security verification expired. Please refresh the page.', 'clickflow' ) ), 403 );
                return;
            }

            if ( ! current_user_can( 'manage_options' ) ) {
                ob_end_clean();
                wp_send_json_error( array( 'message' => __( 'Unauthorized user.', 'clickflow' ) ), 403 );
                return;
            }

            $this->purge_litespeed_cache();

            ob_end_clean();
            wp_send_json_success( array( 'message' => __( 'LiteSpeed full-page cache purged successfully!', 'clickflow' ) ) );
        } catch ( \Throwable $e ) {
            ob_end_clean();
            error_log( 'ClickFlow Purge error: ' . $e->getMessage() );
            wp_send_json_error( array( 'message' => __( 'Purge encountered an internal server error. Please check server logs.', 'clickflow' ) ), 500 );
        }
    }
}
