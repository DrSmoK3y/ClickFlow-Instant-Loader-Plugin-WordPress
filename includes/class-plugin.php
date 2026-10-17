<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LM_INAV_Plugin
 * Central orchestrator for the LM Instant Navigation plugin.
 */
class LM_INAV_Plugin {

    private static $instance = null;
    private $settings;
    private $exclusions;
    private $litespeed;
    private $assets;
    private $compatibility;
    private $route_state;
    private $auto_setup;
    private $redis;
    private $early_hints;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->settings      = new LM_INAV_Settings();
        $this->exclusions    = new LM_INAV_Exclusions( $this->settings );
        $this->litespeed     = new LM_INAV_LiteSpeed_Integration( $this->settings );
        $this->assets        = new LM_INAV_Assets( $this->settings );
        $this->compatibility = new LM_INAV_Compatibility( $this->settings );
        $this->route_state   = new LM_INAV_Route_State( $this->settings, $this->compatibility );
        $this->auto_setup    = new LM_INAV_Auto_Setup( $this->settings );
        $this->redis         = new LM_INAV_Redis_Integration( $this->settings );
        $this->early_hints   = new LM_INAV_Early_Hints_Security( $this->settings );
    }

    public function init() {
        $this->settings->init();
        $this->litespeed->init();
        $this->auto_setup->init();
        $this->redis->init();
        $this->early_hints->init();

        if ( ! $this->settings->is_enabled() ) {
            return;
        }

        $this->assets->init();

        // Register frontend scripts and assets
        add_action( 'wp_enqueue_scripts', array( $this->assets, 'enqueue_frontend_scripts' ) );

        // Render machine-readable page state in cached HTML
        add_action( 'wp_footer', array( $this->route_state, 'render_page_state_tag' ), 99 );

        // Add settings action link on plugins page
        add_filter( 'plugin_action_links_' . LM_INAV_BASENAME, array( $this, 'add_settings_link' ) );
    }

    public function add_settings_link( $links ) {
        $setup_url   = admin_url( 'admin.php?page=clickflow' );
        $setup_link  = '<a href="' . esc_url( $setup_url ) . '" style="color: #2563eb; font-weight: 700;">' . __( '1-Click Setup & Settings', 'clickflow' ) . '</a>';
        array_unshift( $links, $setup_link );
        return $links;
    }

    public function get_settings() {
        return $this->settings;
    }

    public function get_exclusions() {
        return $this->exclusions;
    }
}
