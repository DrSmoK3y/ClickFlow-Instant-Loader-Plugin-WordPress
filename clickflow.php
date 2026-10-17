<?php
/**
 * Plugin Name:       ClickFlow (BETA)
 * Plugin URI:        https://github.com/lmwebmaker/clickflow
 * Description:       ClickFlow: Instant Page Navigation & LiteSpeed Cache Optimization Engine with 1-Click Category Setup, Elementor & WooCommerce integration.
 * Version:           0.9-beta
 * Author:            ClickFlow Team
 * Author URI:        https://github.com/lmwebmaker
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       clickflow
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'CLICKFLOW_VERSION', '0.9-beta' );
define( 'CLICKFLOW_IS_BETA', true );
define( 'LM_INAV_VERSION', '0.9-beta' );
define( 'LM_INAV_FILE', __FILE__ );
define( 'LM_INAV_PATH', plugin_dir_path( __FILE__ ) );
define( 'LM_INAV_URL', plugin_dir_url( __FILE__ ) );
define( 'LM_INAV_BASENAME', plugin_basename( __FILE__ ) );

// Autoload Core Classes
require_once LM_INAV_PATH . 'includes/class-settings.php';
require_once LM_INAV_PATH . 'includes/class-route-state.php';
require_once LM_INAV_PATH . 'includes/class-exclusions.php';
require_once LM_INAV_PATH . 'includes/class-litespeed-integration.php';
require_once LM_INAV_PATH . 'includes/class-assets.php';
require_once LM_INAV_PATH . 'includes/class-compatibility.php';
require_once LM_INAV_PATH . 'includes/class-auto-setup.php';
require_once LM_INAV_PATH . 'includes/class-redis-integration.php';
require_once LM_INAV_PATH . 'includes/class-early-hints-security.php';

// Autoload Adapters (Focused on Elementor, Gutenberg, and Classic Editor)
require_once LM_INAV_PATH . 'includes/adapters/interface-adapter.php';
require_once LM_INAV_PATH . 'includes/adapters/class-wordpress-adapter.php';
require_once LM_INAV_PATH . 'includes/adapters/class-gutenberg-adapter.php';
require_once LM_INAV_PATH . 'includes/adapters/class-elementor-adapter.php';

// Plugin Lifecycle Controller
require_once LM_INAV_PATH . 'includes/class-plugin.php';

/**
 * Bootstrap LM Instant Navigation
 */
function lm_inav_init() {
    $plugin = LM_INAV_Plugin::get_instance();
    $plugin->init();
}
add_action( 'plugins_loaded', 'lm_inav_init' );

/**
 * Plugin activation hook: seed default configuration.
 */
register_activation_hook( __FILE__, function() {
    $defaults = array(
        'enabled'                    => 1,
        'mode'                       => 'smart_builder', // safe_blog | smart_builder | full_site
        'disable_for_logged_in'      => 1,
        'content_selector'           => 'main, #primary, #main, .site-main, .elementor-location-single',
        'header_selector'            => 'header.site-header, #masthead',
        'footer_selector'            => 'footer.site-footer, #colophon',
        'enable_prefetch'            => 1,
        'prefetch_trigger'           => 'all', // hover, touch, focus, all
        'prefetch_delay'             => 65,    // hover intent delay in ms
        'cache_max_pages'            => 15,    // volatile browser memory cache size
        'cache_ttl'                  => 300,   // 5 minutes TTL
        'enable_elementor'           => 1,
        'enable_gutenberg'           => 1,
        'enable_classic'             => 1,
        'litespeed_exclude_delay'    => 1,
        'debug_mode'                 => 1,
        'enable_early_hints'         => 1,
        'enable_http3_altsvc'        => 1,
        'enable_security_hardening'  => 1,
        'excluded_urls'              => "/wp-admin/*\n/wp-login.php\n/cart/*\n/checkout/*\n/my-account/*\n/?preview=true",
    );

    if ( ! get_option( 'lm_inav_settings' ) ) {
        add_option( 'lm_inav_settings', $defaults );
    }
} );
