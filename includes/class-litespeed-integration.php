<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LM_INAV_LiteSpeed_Integration
 * Harmonizes LM Instant Navigation with LiteSpeed Cache for WordPress (LSCWP).
 */
class LM_INAV_LiteSpeed_Integration {

    private $settings;

    public function __construct( $settings ) {
        $this->settings = $settings;
    }

    public function init() {
        if ( ! $this->is_litespeed_active() ) {
            return;
        }

        // Exclude navigation bundle from LiteSpeed Delay JS to avoid first-click race condition
        if ( $this->settings->get( 'litespeed_exclude_delay', true ) ) {
            add_filter( 'litespeed_optm_delay_js_exc', array( $this, 'exclude_navigator_from_delay_js' ) );
            add_filter( 'litespeed_optm_js_defer_exc', array( $this, 'exclude_navigator_from_defer' ) );
        }

        // Helper hook to purge specific URL from LiteSpeed cache
        add_action( 'lm_inav_purge_route', array( $this, 'purge_url_cache' ), 10, 1 );
    }

    public function is_litespeed_active() {
        return defined( 'LSCWP_V' ) || class_exists( 'LiteSpeed\\Core' );
    }

    public function exclude_navigator_from_delay_js( $excludes ) {
        if ( ! is_array( $excludes ) ) {
            $excludes = array();
        }
        $excludes[] = 'lm-navigator.js';
        $excludes[] = 'navigator.js';
        $excludes[] = 'lm-inav-state';
        $excludes[] = 'lm-inav-config';
        return array_unique( $excludes );
    }

    public function exclude_navigator_from_defer( $excludes ) {
        if ( ! is_array( $excludes ) ) {
            $excludes = array();
        }
        $excludes[] = 'lm-navigator.js';
        $excludes[] = 'navigator.js';
        return array_unique( $excludes );
    }

    public function purge_url_cache( $url ) {
        if ( function_exists( 'litespeed_purge_url' ) ) {
            litespeed_purge_url( $url );
        }
    }
}
