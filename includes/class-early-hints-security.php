<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LM_INAV_Early_Hints_Security
 * 
 * Provides enterprise-grade HTTP/3 (QUIC) Alt-Svc negotiation, 
 * RFC 8297 Early Hints (HTTP 103) preload link emission for LiteSpeed & Cloudflare,
 * and comprehensive security headers (nosniff, sameorigin, referrer-policy, permissions-policy).
 */
class LM_INAV_Early_Hints_Security {

    private $settings;
    private $preload_resources = array();

    public function __construct( $settings ) {
        $this->settings = $settings;
    }

    public function init() {
        // 1. Send enterprise security headers & HTTP/3 Alt-Svc
        add_filter( 'wp_headers', array( $this, 'filter_wp_headers' ), 99 );
        add_action( 'send_headers', array( $this, 'send_early_security_headers' ), 1 );

        // 2. Server-side Prefetch Security Shield (Prevents state mutation & CSRF via prefetch)
        add_action( 'init', array( $this, 'intercept_sensitive_prefetch' ), 1 );

        // 3. Early Hints (HTTP 103) collector & emitter
        if ( $this->is_early_hints_enabled() ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'collect_critical_assets' ), 999 );
            add_action( 'template_redirect', array( $this, 'emit_early_hints_links' ), 2 );
            // LiteSpeed Cache Early Hints hook integration
            add_filter( 'litespeed_optm_early_hints', array( $this, 'filter_litespeed_early_hints' ) );
        }
    }

    public function is_early_hints_enabled() {
        return (bool) $this->settings->get( 'enable_early_hints', true );
    }

    public function is_http3_enabled() {
        return (bool) $this->settings->get( 'enable_http3_altsvc', true );
    }

    public function is_security_hardening_enabled() {
        return (bool) $this->settings->get( 'enable_security_hardening', true );
    }

    /**
     * Filter standard WordPress response headers to inject HTTP/3 and Security policies
     */
    public function filter_wp_headers( $headers ) {
        if ( is_admin() ) {
            return $headers;
        }

        // 1. HTTP/3 (QUIC) Alt-Svc advertising (Standard RFC 9114 h3)
        if ( $this->is_http3_enabled() && ! isset( $headers['Alt-Svc'] ) ) {
            $headers['Alt-Svc'] = 'h3=":443"; ma=86400';
        }

        // 2. Enterprise Security Hardening
        if ( $this->is_security_hardening_enabled() ) {
            if ( ! isset( $headers['X-Content-Type-Options'] ) ) {
                $headers['X-Content-Type-Options'] = 'nosniff';
            }
            if ( ! isset( $headers['X-Frame-Options'] ) ) {
                $headers['X-Frame-Options'] = 'SAMEORIGIN';
            }
            if ( ! isset( $headers['Referrer-Policy'] ) ) {
                $headers['Referrer-Policy'] = 'strict-origin-when-cross-origin';
            }
            if ( ! isset( $headers['Permissions-Policy'] ) ) {
                $headers['Permissions-Policy'] = 'camera=(), microphone=(), geolocation=()';
            }
            // Strict Transport Security (HSTS) if running on HTTPS (subdomains isolated by default)
            if ( is_ssl() && ! isset( $headers['Strict-Transport-Security'] ) ) {
                $headers['Strict-Transport-Security'] = 'max-age=31536000';
            }
        }

        return $headers;
    }

    /**
     * Direct early header emission for environments where wp_headers runs late
     */
    public function send_early_security_headers() {
        if ( is_admin() || headers_sent() ) {
            return;
        }

        if ( $this->is_http3_enabled() ) {
            header( 'Alt-Svc: h3=":443"; ma=86400', false );
        }

        if ( $this->is_security_hardening_enabled() ) {
            header( 'X-Content-Type-Options: nosniff', false );
            header( 'X-Frame-Options: SAMEORIGIN', false );
            header( 'Referrer-Policy: strict-origin-when-cross-origin', false );
            if ( is_ssl() ) {
                header( 'Strict-Transport-Security: max-age=31536000', false );
            }
        }
    }

    /**
     * Collect critical theme CSS and Navigator script to build 103 Early Hints
     */
    public function collect_critical_assets() {
        if ( is_admin() ) {
            return;
        }

        global $wp_styles, $wp_scripts;

        // A. Primary Theme Stylesheet
        $theme_style_uri = get_stylesheet_uri();
        if ( ! empty( $theme_style_uri ) ) {
            $this->add_preload_resource( $theme_style_uri, 'style' );
        }

        // B. ClickFlow Navigator Core
        $navigator_uri = LM_INAV_URL . 'assets/js/navigator.js';
        $this->add_preload_resource( $navigator_uri, 'script' );

        // C. Core Block Library or Elementor Main CSS if enqueued
        if ( ! empty( $wp_styles->registered['wp-block-library']->src ) ) {
            $this->add_preload_resource( $wp_styles->registered['wp-block-library']->src, 'style' );
        }
        if ( ! empty( $wp_styles->registered['elementor-frontend']->src ) ) {
            $this->add_preload_resource( $wp_styles->registered['elementor-frontend']->src, 'style' );
        }
    }

    private function add_preload_resource( $uri, $type ) {
        if ( empty( $uri ) || ! is_string( $uri ) ) {
            return;
        }

        // Convert to absolute or root-relative path
        $clean_uri = esc_url_raw( $uri );
        if ( in_array( $clean_uri, array_column( $this->preload_resources, 'uri' ), true ) ) {
            return;
        }

        // Limit to max 6 critical resources to avoid early hint buffer bloat
        if ( count( $this->preload_resources ) < 6 ) {
            $this->preload_resources[] = array(
                'uri'  => $clean_uri,
                'type' => $type,
            );
        }
    }

    /**
     * Emit RFC 8297 Link preload headers compatible with Early Hints
     */
    public function emit_early_hints_links() {
        if ( is_admin() || headers_sent() || empty( $this->preload_resources ) ) {
            return;
        }

        foreach ( $this->preload_resources as $item ) {
            $header_value = sprintf( '<%s>; rel=preload; as=%s', $item['uri'], $item['type'] );
            if ( 'style' === $item['type'] ) {
                $header_value .= '; crossorigin';
            }
            header( 'Link: ' . $header_value, false );
        }
    }

    /**
     * Feed into LiteSpeed Cache's built-in Early Hints filter
     */
    public function filter_litespeed_early_hints( $hints ) {
        if ( ! is_array( $hints ) ) {
            $hints = array();
        }

        foreach ( $this->preload_resources as $item ) {
            $hints[] = array(
                'url' => $item['uri'],
                'as'  => $item['type'],
            );
        }

        return $hints;
    }

    /**
     * Intercept speculative prefetch requests targeting state-changing or sensitive actions.
     * Prevents accidental cart additions, logouts, token exhaustion, or CSRF via prefetch.
     */
    public function intercept_sensitive_prefetch() {
        $is_prefetch = false;

        // 1. Inspect Native Browser Headers (Sec-Purpose, Purpose) with HPP / prerender resilience
        $raw_sec_purpose = ! empty( $_SERVER['HTTP_SEC_PURPOSE'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_SEC_PURPOSE'] ) ) ) : '';
        $raw_purpose     = ! empty( $_SERVER['HTTP_PURPOSE'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_PURPOSE'] ) ) ) : '';

        if ( false !== strpos( $raw_sec_purpose, 'prefetch' ) || false !== strpos( $raw_sec_purpose, 'prerender' ) ) {
            $is_prefetch = true;
        } elseif ( false !== strpos( $raw_purpose, 'prefetch' ) || false !== strpos( $raw_purpose, 'prerender' ) ) {
            $is_prefetch = true;
        } elseif ( ! empty( $_SERVER['HTTP_X_CLICKFLOW_PREFETCH'] ) || ! empty( $_SERVER['HTTP_X_PURPOSE'] ) ) {
            // ClickFlow client-side JavaScript prefetch headers (Allowed in standard fetch)
            $is_prefetch = true;
        }

        if ( ! $is_prefetch ) {
            return;
        }

        // 1. Direct GET Parameter Keys Inspection (immune to standard URL-encoding)
        $sensitive_get_keys = array(
            'add-to-cart',
            'wc-ajax',
            'remove_item',
            'empty-cart',
            'cancel_order',
            'order-received',
            '_wpnonce',
            'key',
            'token',
            'preview',
            'customize_changeset_uuid',
            'elementor-preview',
            'customer-logout',
            's'
        );

        foreach ( $sensitive_get_keys as $key ) {
            if ( isset( $_GET[ $key ] ) ) {
                status_header( 400 );
                header( 'Cache-Control: no-store, no-cache, must-revalidate, private' );
                wp_die(
                    'ClickFlow Security Shield: Speculative prefetch of state-changing endpoint blocked.',
                    'Prefetch Prohibited',
                    array( 'response' => 400 )
                );
            }
        }

        // 2. Action query specific matching (avoids false positives like ?hotkey=logout)
        if ( isset( $_GET['action'] ) ) {
            $action = strtolower( sanitize_key( $_GET['action'] ) );
            $dangerous_actions = array( 'logout', 'delete', 'trash', 'postpass', 'rp', 'resetpass', 'elementor' );
            if ( in_array( $action, $dangerous_actions, true ) ) {
                status_header( 400 );
                header( 'Cache-Control: no-store, no-cache, must-revalidate, private' );
                wp_die(
                    'ClickFlow Security Shield: Speculative prefetch of action blocked.',
                    'Prefetch Prohibited',
                    array( 'response' => 400 )
                );
            }
        }

        // 3. Recursive URL-Decoding & Query String Parsing (Thwarts %3D, %253D double encoding evasion)
        $raw_uri   = isset( $_SERVER['REQUEST_URI'] ) ? strtolower( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        $raw_query = isset( $_SERVER['QUERY_STRING'] ) ? strtolower( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '';

        // Multi-pass urldecode (up to 3 levels to neutralize double & triple encoded payloads)
        $decoded_uri   = $raw_uri;
        $decoded_query = $raw_query;
        for ( $i = 0; $i < 3; $i++ ) {
            $next_u = rawurldecode( $decoded_uri );
            $next_q = rawurldecode( $decoded_query );
            if ( $next_u === $decoded_uri && $next_q === $decoded_query ) {
                break;
            }
            $decoded_uri   = $next_u;
            $decoded_query = $next_q;
        }

        // Parse extracted decoded query parameters to inspect keys parsed after %3D normalization
        $parsed_decoded = array();
        if ( ! empty( $decoded_query ) ) {
            parse_str( $decoded_query, $parsed_decoded );
        }

        foreach ( $sensitive_get_keys as $key ) {
            if ( isset( $parsed_decoded[ $key ] ) ) {
                status_header( 400 );
                header( 'Cache-Control: no-store, no-cache, must-revalidate, private' );
                wp_die(
                    'ClickFlow Security Shield: Speculative prefetch with encoded parameters blocked.',
                    'Prefetch Prohibited',
                    array( 'response' => 400 )
                );
            }
        }

        if ( isset( $parsed_decoded['action'] ) ) {
            $parsed_action = strtolower( sanitize_key( $parsed_decoded['action'] ) );
            $dangerous_actions = array( 'logout', 'delete', 'trash', 'postpass', 'rp', 'resetpass', 'elementor' );
            if ( in_array( $parsed_action, $dangerous_actions, true ) ) {
                status_header( 400 );
                header( 'Cache-Control: no-store, no-cache, must-revalidate, private' );
                wp_die(
                    'ClickFlow Security Shield: Speculative prefetch of action blocked.',
                    'Prefetch Prohibited',
                    array( 'response' => 400 )
                );
            }
        }

        // 4. Substring signature matching on raw AND multi-pass decoded URI/query
        $uri_signatures = array(
            'add-to-cart=',
            'empty-cart',
            'remove_item=',
            'action=logout',
            'action=delete',
            'action=trash',
            'customer-logout',
            '_wpnonce=',
            'order-received'
        );

        foreach ( $uri_signatures as $sig ) {
            if (
                false !== strpos( $raw_uri, $sig ) ||
                false !== strpos( $decoded_uri, $sig ) ||
                false !== strpos( $raw_query, $sig ) ||
                false !== strpos( $decoded_query, $sig )
            ) {
                status_header( 400 );
                header( 'Cache-Control: no-store, no-cache, must-revalidate, private' );
                wp_die(
                    'ClickFlow Security Shield: Speculative prefetch of state-changing signature blocked.',
                    'Prefetch Prohibited',
                    array( 'response' => 400 )
                );
            }
        }

        // 5. Path-Based State Changes Inspection (Qwen Vector 4: RESTful and path actions)
        $parsed_path = wp_parse_url( $decoded_uri, PHP_URL_PATH );
        if ( ! empty( $parsed_path ) ) {
            $clean_path = '/' . trim( strtolower( $parsed_path ), '/' ) . '/';
            $blocked_paths = array(
                '/logout/',
                '/customer-logout/',
                '/wp-login.php/',
                '/cart/',
                '/checkout/',
                '/my-account/',
                '/delete/',
                '/cancel-order/',
                '/order-received/',
                '/resetpass/',
                '/empty-cart/'
            );
            foreach ( $blocked_paths as $bpath ) {
                if ( false !== strpos( $clean_path, $bpath ) ) {
                    status_header( 400 );
                    header( 'Cache-Control: no-store, no-cache, must-revalidate, private' );
                    wp_die(
                        'ClickFlow Security Shield: Speculative prefetch of path-based endpoint blocked.',
                        'Prefetch Prohibited',
                        array( 'response' => 400 )
                    );
                }
            }
        }
    }
}
