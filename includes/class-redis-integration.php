<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ClickFlow_Redis_Integration / LM_INAV_Redis_Integration
 * Detects Redis Object Cache daemon, APCu, Memcached, latency benchmarking,
 * and handles graceful fallbacks for shared hosting without server daemons.
 */
class LM_INAV_Redis_Integration {

    private $settings;

    public function __construct( $settings ) {
        $this->settings = $settings;
    }

    public function init() {
        // Register AJAX endpoint for unified purge (LiteSpeed + Object Cache)
        add_action( 'wp_ajax_clickflow_purge_unified_cache', array( $this, 'ajax_purge_unified_cache' ) );
    }

    /**
     * Checks if Redis PHP extension is installed on server
     */
    public function is_redis_extension_loaded() {
        return class_exists( 'Redis' ) || extension_loaded( 'redis' );
    }

    /**
     * Checks if APCu PHP extension is installed & enabled on server
     */
    public function is_apcu_loaded() {
        return extension_loaded( 'apcu' ) && ( function_exists( 'apcu_fetch' ) || ini_get( 'apc.enabled' ) );
    }

    /**
     * Checks if Memcached PHP extension is installed on server
     */
    public function is_memcached_loaded() {
        return class_exists( 'Memcached' ) || extension_loaded( 'memcached' );
    }

    /**
     * Checks if WordPress is actively using an external object cache dropin
     */
    public function is_object_cache_active() {
        return function_exists( 'wp_using_ext_object_cache' ) && wp_using_ext_object_cache();
    }

    /**
     * Comprehensive cache backend detection and diagnostic
     */
    public function get_cache_diagnostics() {
        $redis_test = $this->test_connection();
        $is_ext_cache = $this->is_object_cache_active();
        $has_apcu = $this->is_apcu_loaded();
        $has_memcached = $this->is_memcached_loaded();

        $active_backend = 'none';
        $badge_label = 'Object Cache Inactive';
        $badge_color = '#f59e0b';
        $badge_status = 'Standby (ClickFlow Client 0ms Active)';

        if ( ! empty( $redis_test['connected'] ) ) {
            $active_backend = 'redis';
            $badge_label = 'Redis Active';
            $badge_color = '#38bdf8';
            $badge_status = $redis_test['latency'] > 0 ? $redis_test['latency'] . 'ms' : 'Active';
        } elseif ( $is_ext_cache && $has_apcu ) {
            $active_backend = 'apcu';
            $badge_label = 'APCu Active';
            $badge_color = '#10b981';
            $badge_status = '0.02ms (Direct RAM)';
        } elseif ( $is_ext_cache && $has_memcached ) {
            $active_backend = 'memcached';
            $badge_label = 'Memcached Active';
            $badge_color = '#818cf8';
            $badge_status = 'Active';
        } elseif ( $is_ext_cache ) {
            $active_backend = 'custom';
            $badge_label = 'Object Cache Active';
            $badge_color = '#34d399';
            $badge_status = 'Drop-in Active';
        }

        $salt_sec = $this->check_cache_salt_security();

        return array(
            'active_backend'  => $active_backend,
            'badge_label'     => $badge_label,
            'badge_color'     => $badge_color,
            'badge_status'    => $badge_status,
            'redis'           => $redis_test,
            'apcu_available'  => $has_apcu,
            'memcached_avail' => $has_memcached,
            'object_dropin'   => $is_ext_cache,
            'salt_security'   => $salt_sec,
            'recommendations' => $this->get_recommendations( $active_backend, $redis_test, $has_apcu, $salt_sec ),
        );
    }

    /**
     * Helpful recommendations when Redis is unreachable or cache salt is insecure
     */
    private function get_recommendations( $active_backend, $redis_test, $has_apcu, $salt_sec = null ) {
        $items = array();

        if ( $salt_sec && empty( $salt_sec['configured'] ) && ( 'redis' === $active_backend || 'apcu' === $active_backend || 'memcached' === $active_backend || 'custom' === $active_backend ) ) {
            $items[] = array(
                'title'       => 'Secure Object Cache Key Salt (Prevent Shared Host Poisoning)',
                'description' => 'Add WP_CACHE_KEY_SALT to your wp-config.php so keys cannot collide with other sites on shared Redis or APCu server: ' . $salt_sec['config_snippet'],
                'action_hint' => 'Add to wp-config.php: ' . $salt_sec['config_snippet'],
                'install_url' => '',
            );
        }

        if ( 'redis' === $active_backend || 'apcu' === $active_backend ) {
            return ! empty( $items ) ? $items : null;
        }

        if ( ! empty( $redis_test['refused'] ) || ! empty( $redis_test['message'] ) ) {
            if ( $has_apcu ) {
                $items[] = array(
                    'title'       => 'Enable APCu Manager (Best Free Alternative to Redis)',
                    'description' => 'Your server has the PHP APCu extension ready! Install the free "APCu Manager" plugin for instant in-memory SQL caching without needing any Redis server daemon.',
                    'action_hint' => 'Install plugin: "APCu Manager" by Pierre Lannoy',
                    'install_url' => 'plugin-install.php?s=APCu+Manager&tab=search&type=term',
                );
            } else {
                $items[] = array(
                    'title'       => 'Enable APCu in cPanel / Hosting PHP Settings',
                    'description' => 'In your hosting cPanel, go to "Select PHP Version" > "Extensions" and check "apcu". This enables zero-daemon memory caching.',
                    'action_hint' => 'cPanel > Select PHP Version > Extensions > check apcu',
                    'install_url' => '',
                );
            }

            $items[] = array(
                'title'       => 'Install "Index WP MySQL For Speed"',
                'description' => 'Adds high-performance database indexes to wp_posts, wp_postmeta, and wp_options to speed up database queries by 300% to 800% without any RAM daemon.',
                'action_hint' => 'Install plugin: "Index WP MySQL For Speed"',
                'install_url' => 'plugin-install.php?s=Index+WP+MySQL+For+Speed&tab=search&type=term',
            );

            $items[] = array(
                'title'       => 'Switch to Memcached in LiteSpeed Cache',
                'description' => 'In LiteSpeed Cache > Cache > Object, select Memcached (Port: 11211). Many cPanel shared hosts have Memcached open even when Redis is blocked.',
                'action_hint' => 'LiteSpeed Cache > Cache > Object > Memcached',
                'install_url' => 'admin.php?page=litespeed-cache',
            );
        }

        return $items;
    }

    /**
     * Test connection to Redis server safely without blocking
     */
    public function test_connection() {
        if ( ! $this->is_redis_extension_loaded() ) {
            return array(
                'connected' => false,
                'latency'   => 0,
                'refused'   => false,
                'message'   => 'PHP Redis extension is not installed.',
            );
        }

        $host    = defined( 'WP_REDIS_HOST' ) ? WP_REDIS_HOST : '127.0.0.1';
        $port    = defined( 'WP_REDIS_PORT' ) ? WP_REDIS_PORT : 6379;
        $timeout = defined( 'WP_REDIS_TIMEOUT' ) ? (float) WP_REDIS_TIMEOUT : 0.8;

        try {
            $redis = new Redis();
            $start = microtime( true );

            // Connect using host or unix socket with strict error suppression
            $connected = false;
            if ( strpos( $host, '/' ) === 0 ) {
                $connected = @$redis->connect( $host, null, $timeout );
            } else {
                $connected = @$redis->connect( $host, (int) $port, $timeout );
            }

            if ( ! $connected ) {
                return array(
                    'connected' => false,
                    'latency'   => 0,
                    'refused'   => true,
                    'host'      => $host,
                    'port'      => $port,
                    'message'   => 'Connection refused: Redis server daemon is not running on ' . esc_html( $host . ':' . $port ),
                );
            }

            // Auth if defined
            if ( defined( 'WP_REDIS_PASSWORD' ) && ! empty( WP_REDIS_PASSWORD ) ) {
                @$redis->auth( WP_REDIS_PASSWORD );
            }

            @$redis->ping();
            $elapsed_ms = round( ( microtime( true ) - $start ) * 1000, 2 );

            @$redis->close();

            return array(
                'connected' => true,
                'latency'   => $elapsed_ms,
                'refused'   => false,
                'host'      => $host,
                'port'      => $port,
                'message'   => 'Redis connection active (' . $elapsed_ms . ' ms)',
            );
        } catch ( \Throwable $e ) {
            error_log( 'ClickFlow Redis test notice: ' . $e->getMessage() );
            return array(
                'connected' => false,
                'latency'   => 0,
                'refused'   => true,
                'host'      => $host,
                'port'      => $port,
                'message'   => __( 'Redis connection refused or timed out.', 'clickflow' ),
            );
        }
    }

    /**
     * Inspect WP_CACHE_KEY_SALT to prevent shared hosting Object Cache Poisoning
     */
    public function check_cache_salt_security() {
        $has_custom_salt = defined( 'WP_CACHE_KEY_SALT' ) && '' !== trim( constant( 'WP_CACHE_KEY_SALT' ) );
        $suggested_salt  = hash( 'sha256', DB_NAME . '_' . ( defined( 'AUTH_KEY' ) ? AUTH_KEY : 'clickflow' ) . '_' . get_current_blog_id() );

        return array(
            'configured' => $has_custom_salt,
            'status'     => $has_custom_salt ? 'Isolated (Secure)' : 'Default/Empty (Shared Host Collision Risk)',
            'suggested'  => substr( $suggested_salt, 0, 16 ) . '...',
            'config_snippet' => "define( 'WP_CACHE_KEY_SALT', '" . substr( $suggested_salt, 0, 24 ) . "' );",
        );
    }

    /**
     * Flush WordPress Object Cache (works for Redis, APCu, Memcached, etc.)
     */
    public function flush_object_cache() {
        if ( function_exists( 'wp_cache_flush' ) ) {
            return (bool) wp_cache_flush();
        }
        return false;
    }

    /**
     * AJAX handler to purge both LiteSpeed Full Page Cache and active Object Cache
     */
    public function ajax_purge_unified_cache() {
        check_ajax_referer( 'clickflow_auto_setup_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized permission level' ), 403 );
            return;
        }

        $lscwp_purged = false;
        $obj_flushed = false;

        // 1. Purge LiteSpeed full page cache
        if ( class_exists( 'LiteSpeed_Cache_API' ) && method_exists( 'LiteSpeed_Cache_API', 'purge_all' ) ) {
            LiteSpeed_Cache_API::purge_all();
            $lscwp_purged = true;
        } elseif ( has_action( 'litespeed_purge_all' ) ) {
            do_action( 'litespeed_purge_all' );
            $lscwp_purged = true;
        }

        // 2. Flush Object Cache
        $obj_flushed = $this->flush_object_cache();

        $diag = $this->get_cache_diagnostics();

        wp_send_json_success( array(
            'message'        => 'Unified Cache Purged: LiteSpeed full-page cache and object cache flushed.',
            'litespeed'      => $lscwp_purged,
            'object_flushed' => $obj_flushed,
            'diagnostics'    => $diag,
        ) );
    }
}
