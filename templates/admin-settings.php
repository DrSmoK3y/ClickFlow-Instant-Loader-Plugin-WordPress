<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$cf_settings   = get_option( 'clickflow_settings', false );
if ( false === $cf_settings ) {
    $cf_settings = get_option( 'lm_inav_settings', array() );
}
$settings      = is_array( $cf_settings ) ? $cf_settings : array();
$enabled       = ! empty( $settings['enabled'] );
$mode          = $settings['mode'] ?? 'smart_builder';
$last_profile  = $settings['last_applied_profile'] ?? '';
$backup_data   = get_option( 'lm_inav_settings_backup', false );

// Instantiate Auto Setup and Redis Integration to detect environment
$auto_setup        = new LM_INAV_Auto_Setup( new LM_INAV_Settings() );
$is_lscwp_active   = $auto_setup->is_litespeed_active();
$detected_selector = $auto_setup->detect_theme_selector();
$detected_header   = $auto_setup->detect_theme_header_selector();
$detected_footer   = $auto_setup->detect_theme_footer_selector();
$active_theme      = wp_get_theme();
$theme_name        = $active_theme->get( 'Name' );
$ajax_nonce        = wp_create_nonce( 'clickflow_auto_setup_nonce' );

$redis_integration = new LM_INAV_Redis_Integration( new LM_INAV_Settings() );
$cache_diag        = $redis_integration->get_cache_diagnostics();
$redis_status      = $cache_diag['redis'];
$active_backend    = $cache_diag['active_backend'];
?>

<div class="wrap clickflow-admin-wrap" style="max-width: 1080px; margin: 24px auto 60px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #0f172a;">

    <!-- TOP HEADER BAR (Modern Slate / Indigo Minimalist) -->
    <div style="background: #0f172a; border-radius: 14px; padding: 26px 32px; margin-bottom: 24px; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px; border: 1px solid #1e293b;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <!-- Pure Inline SVG Logo (Zero Font Icons) -->
            <div style="width: 46px; height: 46px; border-radius: 12px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border: 1px solid #334155; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 1px 1px rgba(255,255,255,0.1);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <h1 style="margin: 0; padding: 0; font-size: 24px; font-weight: 800; color: #ffffff; letter-spacing: -0.02em; line-height: 1.1;">
                        ClickFlow
                    </h1>
                    <span style="display: inline-flex; align-items: center; font-size: 11px; font-weight: 700; background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.35); padding: 2px 8px; border-radius: 6px; letter-spacing: 0.04em;">
                        v0.9.5 BETA
                    </span>
                </div>
                <p style="margin: 5px 0 0; color: #94a3b8; font-size: 13px; font-weight: 400;">
                    Instant Page Transitions, LiteSpeed Cache & Multi-Engine Cache Optimization for WordPress.
                </p>
            </div>
        </div>

        <!-- Environment Badges (LiteSpeed + Object Cache + Theme) -->
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <?php if ( $is_lscwp_active ) : ?>
                <div style="display: inline-flex; align-items: center; gap: 7px; background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); color: #34d399; font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 8px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    LiteSpeed Cache Active
                </div>
            <?php else : ?>
                <div style="display: inline-flex; align-items: center; gap: 7px; background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.3); color: #fbbf24; font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 8px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    LiteSpeed Inactive
                </div>
            <?php endif; ?>

            <!-- Multi-Engine Object Cache Badge (Redis / APCu / Memcached / Standby) -->
            <?php if ( 'redis' === $active_backend ) : ?>
                <div style="display: inline-flex; align-items: center; gap: 7px; background: rgba(56, 189, 248, 0.12); border: 1px solid rgba(56, 189, 248, 0.3); color: #38bdf8; font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 8px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                        <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                        <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                    </svg>
                    Redis Active (<?php echo esc_html( $cache_diag['badge_status'] ); ?>)
                </div>
            <?php elseif ( 'apcu' === $active_backend ) : ?>
                <div style="display: inline-flex; align-items: center; gap: 7px; background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); color: #34d399; font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 8px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    APCu Active (0.02ms RAM)
                </div>
            <?php elseif ( 'memcached' === $active_backend ) : ?>
                <div style="display: inline-flex; align-items: center; gap: 7px; background: rgba(129, 140, 248, 0.12); border: 1px solid rgba(129, 140, 248, 0.3); color: #818cf8; font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 8px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#818cf8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                        <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                        <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                    </svg>
                    Memcached Active
                </div>
            <?php else : ?>
                <div style="display: inline-flex; align-items: center; gap: 7px; background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.3); color: #fbbf24; font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 8px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                        <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                        <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                    </svg>
                    Object Cache Standby (0ms Client Active)
                </div>
            <?php endif; ?>

            <div style="display: inline-flex; align-items: center; gap: 7px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.12); color: #cbd5e1; font-size: 12px; font-weight: 500; padding: 6px 12px; border-radius: 8px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="3" y1="9" x2="21" y2="9"></line>
                    <line x1="9" y1="21" x2="9" y2="9"></line>
                </svg>
                Theme: <strong style="color: #fff; margin-left: 2px;"><?php echo esc_html( $theme_name ); ?></strong>
            </div>
        </div>
    </div>

    <!-- NOTIFICATION ALERT AREA (If any internal action happened) -->
    <div id="cf-action-alert" style="display: none; padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 13px; font-weight: 500; align-items: center; justify-content: space-between;">
        <span id="cf-action-alert-text"></span>
        <button type="button" onclick="document.getElementById('cf-action-alert').style.display='none';" style="background: none; border: none; cursor: pointer; color: inherit; padding: 4px; display: flex; align-items: center;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>

    <?php if ( ! empty( $cache_diag['recommendations'] ) ) : ?>
        <!-- OBJECT CACHE DIAGNOSTIC & ZERO-DAEMON ALTERNATIVE CARD (DISMISSIBLE) -->
        <div id="cf-db-cache-notice" style="background: #ffffff; border: 1px solid #fed7aa; border-left: 4px solid #f97316; border-radius: 12px; padding: 22px 26px; margin-bottom: 24px; box-shadow: 0 4px 14px rgba(249, 115, 22, 0.08); position: relative;">
            <button type="button" onclick="document.getElementById('cf-db-cache-notice').style.display='none';" style="position: absolute; top: 16px; right: 16px; background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px;" title="Dismiss Notice">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; padding-right: 28px;">
                <div>
                    <div style="display: inline-flex; align-items: center; gap: 6px; background: #fff7ed; color: #ea580c; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 3px 9px; border-radius: 6px; margin-bottom: 8px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        Database Cache Notice (Advisory Only)
                    </div>
                    <h3 style="margin: 0 0 6px; font-size: 17px; font-weight: 800; color: #0f172a; letter-spacing: -0.01em;">
                        Redis Daemon Unreachable on Port 6379 (Connection Refused)
                    </h3>
                    <p style="margin: 0; color: #64748b; font-size: 13.5px; max-width: 780px; line-height: 1.55;">
                        Your hosting server does not have an active <code>redis-server</code> background daemon on <code>127.0.0.1:6379</code> (common on shared cPanel & cloud hosts). 
                        <strong style="color: #0f172a;">Good News:</strong> ClickFlow's 0ms in-memory browser cache and LiteSpeed Full-Page Cache are already running, so page transitions remain instant! If you want database query caching without Redis, use one of these zero-daemon alternatives:
                    </p>
                </div>
                <div style="display: inline-flex; align-items: center; gap: 6px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; font-size: 12px; font-weight: 700; padding: 7px 14px; border-radius: 8px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    ClickFlow 0ms Active
                </div>
            </div>

            <!-- Zero-Daemon Solutions Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px; margin-top: 18px;">
                <?php foreach ( $cache_diag['recommendations'] as $rec ) : ?>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <strong style="color: #0f172a; font-size: 13px; display: block; margin-bottom: 5px;">
                                <?php echo esc_html( $rec['title'] ); ?>
                            </strong>
                            <p style="margin: 0 0 10px; font-size: 12.5px; color: #64748b; line-height: 1.45;">
                                <?php echo esc_html( $rec['description'] ); ?>
                            </p>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; margin-top: 6px;">
                            <span style="font-size: 11px; font-weight: 700; color: #2563eb; background: #eff6ff; border: 1px solid #dbeafe; padding: 4px 9px; border-radius: 6px; display: inline-block;">
                                <?php echo esc_html( $rec['action_hint'] ); ?>
                            </span>
                            <?php if ( ! empty( $rec['install_url'] ) ) : ?>
                                <a href="<?php echo esc_url( admin_url( $rec['install_url'] ) ); ?>" target="_blank" style="font-size: 11px; font-weight: 700; color: #ffffff; background: #2563eb; padding: 5px 12px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                    Install / Open &rarr;
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- MAIN SECTION 1: 1-CLICK AUTO SETUP ENGINE -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 30px; margin-bottom: 24px; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 22px; border-bottom: 1px solid #f1f5f9; padding-bottom: 18px;">
            <div>
                <div style="display: inline-flex; align-items: center; gap: 6px; background: #eff6ff; color: #2563eb; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 3px 10px; border-radius: 6px; margin-bottom: 8px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                    One-Click Preset
                </div>
                <h2 style="margin: 0; font-size: 20px; font-weight: 800; color: #0f172a; letter-spacing: -0.01em;">
                    1-Click Auto Setup Engine
                </h2>
                <p style="margin: 6px 0 0; color: #64748b; font-size: 14px; max-width: 650px; line-height: 1.5;">
                    Select your website type below. ClickFlow will automatically program optimal LiteSpeed database options, apply Delay-JS exclusions, match your theme's content container, and purge cache immediately.
                </p>
            </div>

            <!-- Profile Summary Box -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 18px; font-size: 12px; min-width: 250px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="color: #64748b;">Active Profile:</span>
                    <strong id="cf-profile-status-badge" style="color: #2563eb;">
                        <?php
                        if ( 'blog' === $last_profile ) {
                            echo esc_html__( 'Blog / News', 'clickflow' );
                        } elseif ( 'elementor' === $last_profile ) {
                            echo esc_html__( 'Elementor Business', 'clickflow' );
                        } elseif ( 'ecommerce' === $last_profile || 'woocommerce' === $last_profile ) {
                            echo esc_html__( 'WooCommerce Store', 'clickflow' );
                        } else {
                            echo esc_html__( 'Not Configured Yet', 'clickflow' );
                        }
                        ?>
                    </strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b;">Container Selector:</span>
                    <code style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 11px; color: #0f172a; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo esc_attr( $detected_selector ); ?>">
                        <?php echo esc_html( $detected_selector ); ?>
                    </code>
                </div>
            </div>
        </div>

        <!-- 3 CATEGORY SELECTION CARDS (PURE SVG - ZERO FONT ICONS) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 16px; margin-bottom: 24px;">

            <!-- CARD 1: BLOG / NEWS -->
            <label class="cf-preset-card" style="border: 2px solid <?php echo ( 'blog' === $last_profile || empty( $last_profile ) ) ? '#2563eb' : '#e2e8f0'; ?>; background: <?php echo ( 'blog' === $last_profile || empty( $last_profile ) ) ? '#f8faff' : '#ffffff'; ?>; border-radius: 12px; padding: 22px; cursor: pointer; transition: all 0.2s ease; display: flex; flex-direction: column; position: relative;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px;">
                    <div style="background: #eff6ff; color: #2563eb; width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <!-- Pure SVG Document/Article Icon -->
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </div>
                    <input type="radio" name="cf_profile_select" value="blog" <?php checked( 'blog' === $last_profile || empty( $last_profile ), true ); ?> style="accent-color: #2563eb; width: 18px; height: 18px; margin: 4px;" />
                </div>
                <h3 style="margin: 0 0 6px; font-size: 16px; font-weight: 700; color: #0f172a;">Blog / News & Magazine</h3>
                <p style="margin: 0 0 14px; font-size: 13px; color: #64748b; line-height: 1.45; flex-grow: 1;">
                    Engineered for high reading speed, article prefetching, Gutenberg & standard theme loop containers.
                </p>
                <div style="background: #f1f5f9; border-radius: 8px; padding: 10px 12px; font-size: 11px; color: #475569; display: flex; flex-direction: column; gap: 4px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        LiteSpeed Public TTL: 7 Days
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Safe Blog Transitions & Pre-fetch
                    </div>
                </div>
            </label>

            <!-- CARD 2: ELEMENTOR BUSINESS -->
            <label class="cf-preset-card" style="border: 2px solid <?php echo ( 'elementor' === $last_profile ) ? '#2563eb' : '#e2e8f0'; ?>; background: <?php echo ( 'elementor' === $last_profile ) ? '#f8faff' : '#ffffff'; ?>; border-radius: 12px; padding: 22px; cursor: pointer; transition: all 0.2s ease; display: flex; flex-direction: column; position: relative;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px;">
                    <div style="background: #eff6ff; color: #2563eb; width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <!-- Pure SVG Grid/Layout Icon -->
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="9" y1="21" x2="9" y2="9"></line>
                        </svg>
                    </div>
                    <input type="radio" name="cf_profile_select" value="elementor" <?php checked( 'elementor' === $last_profile, true ); ?> style="accent-color: #2563eb; width: 18px; height: 18px; margin: 4px;" />
                </div>
                <h3 style="margin: 0 0 6px; font-size: 16px; font-weight: 700; color: #0f172a;">Elementor Business Site</h3>
                <p style="margin: 0 0 14px; font-size: 13px; color: #64748b; line-height: 1.45; flex-grow: 1;">
                    Synchronizes per-page elementor CSS, reinitializes widgets & carousels, and prevents Delay-JS race condition.
                </p>
                <div style="background: #f1f5f9; border-radius: 8px; padding: 10px 12px; font-size: 11px; color: #475569; display: flex; flex-direction: column; gap: 4px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Elementor JS Widget Rehydration
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Delay-JS ClickFlow Exclusions
                    </div>
                </div>
            </label>

            <!-- CARD 3: WOOCOMMERCE STORE -->
            <label class="cf-preset-card" style="border: 2px solid <?php echo ( 'ecommerce' === $last_profile || 'woocommerce' === $last_profile ) ? '#2563eb' : '#e2e8f0'; ?>; background: <?php echo ( 'ecommerce' === $last_profile || 'woocommerce' === $last_profile ) ? '#f8faff' : '#ffffff'; ?>; border-radius: 12px; padding: 22px; cursor: pointer; transition: all 0.2s ease; display: flex; flex-direction: column; position: relative;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px;">
                    <div style="background: #eff6ff; color: #2563eb; width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <!-- Pure SVG Shopping Cart Icon -->
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                    </div>
                    <input type="radio" name="cf_profile_select" value="ecommerce" <?php checked( 'ecommerce' === $last_profile || 'woocommerce' === $last_profile, true ); ?> style="accent-color: #2563eb; width: 18px; height: 18px; margin: 4px;" />
                </div>
                <h3 style="margin: 0 0 6px; font-size: 16px; font-weight: 700; color: #0f172a;">WooCommerce Store</h3>
                <p style="margin: 0 0 14px; font-size: 13px; color: #64748b; line-height: 1.45; flex-grow: 1;">
                    Full e-commerce safety: bypasses cart & checkout routes, synchronizes cart badge count, and preserves checkout cookies.
                </p>
                <div style="background: #f1f5f9; border-radius: 8px; padding: 10px 12px; font-size: 11px; color: #475569; display: flex; flex-direction: column; gap: 4px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Automatic Cart/Checkout Route Bypass
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Preserves wc_fragments & Nonces
                    </div>
                </div>
            </label>
        </div>

        <!-- 1-CLICK ACTION BUTTONS -->
        <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
            <button type="button" id="cf-apply-btn" onclick="cfRunAutoSetup();" style="display: inline-flex; align-items: center; gap: 8px; background: #2563eb; hover:background: #1d4ed8; color: #ffffff; border: none; padding: 12px 26px; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.35); transition: all 0.15s ease;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                Apply 1-Click Auto Setup
            </button>

            <?php if ( $backup_data ) : ?>
                <button type="button" id="cf-rollback-btn" onclick="cfRunRollback();" style="display: inline-flex; align-items: center; gap: 7px; background: #ffffff; color: #64748b; border: 1px solid #cbd5e1; padding: 11px 18px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.15s ease;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><polyline points="3 3 3 8 8 8"></polyline></svg>
                    Rollback to Previous Snapshot
                </button>
            <?php endif; ?>

            <span id="cf-auto-setup-hint" style="color: #64748b; font-size: 13px;">
                Takes under 1 second. Backs up previous settings before applying.
            </span>
        </div>

        <!-- LIVE EXECUTION CHECKLIST (Hidden until clicked) -->
        <div id="cf-live-progress-box" style="display: none; margin-top: 20px; background: #0f172a; color: #e2e8f0; border-radius: 12px; padding: 22px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 13px; border: 1px solid #1e293b;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; border-bottom: 1px solid #334155; padding-bottom: 10px;">
                <span style="font-weight: 700; color: #38bdf8; display: flex; align-items: center; gap: 8px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                    Execution Log
                </span>
                <span id="cf-progress-badge" style="background: #2563eb; color: #fff; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 600;">Running...</span>
            </div>
            <ul id="cf-progress-steps" style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 8px;">
                <li id="cf-step-backup" style="color: #94a3b8;">[1/5] Backing up existing options snapshot...</li>
                <li id="cf-step-theme" style="color: #94a3b8;">[2/5] Auto-detecting theme container selectors (<code><?php echo esc_html( $theme_name ); ?></code>)...</li>
                <li id="cf-step-lscwp" style="color: #94a3b8;">[3/5] Programming LiteSpeed Cache database options...</li>
                <li id="cf-step-excludes" style="color: #94a3b8;">[4/5] Injecting Delay-JS exclusions for ClickFlow runtime...</li>
                <li id="cf-step-purge" style="color: #94a3b8;">[5/5] Purging LiteSpeed full-page cache for immediate speed...</li>
            </ul>
        </div>
    </div>

    <!-- MAIN SECTION 2: GENERAL & GRANULAR SETTINGS (ALL ON MAIN PAGE) -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 30px; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px;">
            <div>
                <h2 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    General & Custom Settings
                </h2>
                <p style="margin: 4px 0 0; color: #64748b; font-size: 13px;">
                    Fine-tune runtime transitions, container selectors, and exclusion rules.
                </p>
            </div>

            <!-- Unified Purge Cache Button (LiteSpeed + Redis) -->
            <button type="button" onclick="cfPurgeUnified();" style="display: inline-flex; align-items: center; gap: 6px; background: #f8fafc; border: 1px solid #cbd5e1; color: #334155; font-size: 12px; font-weight: 600; padding: 7px 12px; border-radius: 8px; cursor: pointer;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                Purge All Caches (LiteSpeed + Redis)
            </button>
        </div>

        <form method="post" action="options.php">
            <?php settings_fields( 'clickflow_settings_group' ); ?>

            <div style="display: flex; flex-direction: column; gap: 20px;">

                <!-- 1. Master Enable Toggle -->
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <div>
                        <strong style="color: #0f172a; font-size: 14px; display: block;">Enable Instant Transitions</strong>
                        <span style="color: #64748b; font-size: 13px;">Activate seamless SPA navigation across eligible internal pages</span>
                    </div>
                    <label style="position: relative; display: inline-block; width: 48px; height: 26px;">
                        <input type="checkbox" name="clickflow_settings[enabled]" value="1" <?php checked( $enabled, true ); ?> style="opacity: 0; width: 0; height: 0;" onchange="this.nextElementSibling.style.backgroundColor = this.checked ? '#2563eb' : '#cbd5e1'; this.nextElementSibling.firstElementChild.style.transform = this.checked ? 'translateX(22px)' : 'translateX(0)';" />
                        <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: <?php echo $enabled ? '#2563eb' : '#cbd5e1'; ?>; transition: .2s; border-radius: 34px;">
                            <span style="position: absolute; content: ''; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .2s; border-radius: 50%; transform: <?php echo $enabled ? 'translateX(22px)' : 'translateX(0)'; ?>;"></span>
                        </span>
                    </label>
                </div>

                <!-- 2. Navigation Mode -->
                <div>
                    <label style="display: block; font-weight: 700; color: #0f172a; font-size: 14px; margin-bottom: 6px;">
                        Navigation Engine Mode
                    </label>
                    <select name="clickflow_settings[mode]" style="width: 100%; max-width: 480px; padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; font-size: 13px; color: #0f172a;">
                        <option value="smart_builder" <?php selected( $mode, 'smart_builder' ); ?>>Smart Builder Mode (Elementor + Gutenberg + Classic) - Recommended</option>
                        <option value="safe_blog" <?php selected( $mode, 'safe_blog' ); ?>>Safe Blog Mode (Content loop swaps only)</option>
                        <option value="full_site" <?php selected( $mode, 'full_site' ); ?>>Full Site Mode (All same-origin internal links)</option>
                    </select>
                </div>

                <!-- 3. Content Container Selector -->
                <div>
                    <label style="display: block; font-weight: 700; color: #0f172a; font-size: 14px; margin-bottom: 6px;">
                        Content Container CSS Selector
                    </label>
                    <input type="text" name="clickflow_settings[content_selector]" id="cf-content-selector-input" value="<?php echo esc_attr( $settings['content_selector'] ?? $detected_selector ); ?>" style="width: 100%; max-width: 600px; padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: monospace; font-size: 13px;" />
                    <p style="margin: 4px 0 8px; font-size: 12px; color: #64748b;">
                        Comma-separated selectors that wrap page content. Click a preset chip below to autofill:
                    </p>
                    <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                        <button type="button" onclick="document.getElementById('cf-content-selector-input').value = '<?php echo esc_js( $detected_selector ); ?>';" style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 6px; cursor: pointer;">
                            Auto-Detected: <?php echo esc_html( $theme_name ); ?>
                        </button>
                        <button type="button" onclick="document.getElementById('cf-content-selector-input').value = '#content, .site-content, main, .elementor-location-single';" style="background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; font-size: 11px; padding: 4px 10px; border-radius: 6px; cursor: pointer;">
                            Hello Elementor
                        </button>
                        <button type="button" onclick="document.getElementById('cf-content-selector-input').value = '#primary, .site-main, main, .ast-container';" style="background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; font-size: 11px; padding: 4px 10px; border-radius: 6px; cursor: pointer;">
                            Astra
                        </button>
                        <button type="button" onclick="document.getElementById('cf-content-selector-input').value = '#primary, main.site-main, #main';" style="background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; font-size: 11px; padding: 4px 10px; border-radius: 6px; cursor: pointer;">
                            GeneratePress
                        </button>
                    </div>
                </div>

                <!-- 4. Smart Shell Sync: Header & Footer Selectors -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                    <div>
                        <label style="display: block; font-weight: 700; color: #0f172a; font-size: 13px; margin-bottom: 6px;">
                            Header Shell Selector (Smart Shell Sync)
                        </label>
                        <input type="text" name="clickflow_settings[header_selector]" id="cf-header-selector-input" value="<?php echo esc_attr( $settings['header_selector'] ?? $detected_header ); ?>" style="width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: monospace; font-size: 12px;" />
                        <p style="margin: 4px 0 0; font-size: 11px; color: #64748b;">
                            Header element morphed when templates differ (transparent vs solid headers).
                        </p>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 700; color: #0f172a; font-size: 13px; margin-bottom: 6px;">
                            Footer Shell Selector (Smart Shell Sync)
                        </label>
                        <input type="text" name="clickflow_settings[footer_selector]" id="cf-footer-selector-input" value="<?php echo esc_attr( $settings['footer_selector'] ?? $detected_footer ); ?>" style="width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: monospace; font-size: 12px;" />
                        <p style="margin: 4px 0 0; font-size: 11px; color: #64748b;">
                            Footer element morphed across different templates.
                        </p>
                    </div>
                </div>

                <!-- 4. Adapters & Features -->
                <div>
                    <label style="display: block; font-weight: 700; color: #0f172a; font-size: 14px; margin-bottom: 8px;">
                        Page Builder & Engine Adapters
                    </label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 10px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #334155; background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <input type="checkbox" name="clickflow_settings[enable_elementor]" value="1" <?php checked( ! empty( $settings['enable_elementor'] ), true ); ?> style="accent-color: #2563eb;" />
                            Elementor Dynamic CSS & Widgets
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #334155; background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <input type="checkbox" name="clickflow_settings[enable_gutenberg]" value="1" <?php checked( ! empty( $settings['enable_gutenberg'] ), true ); ?> style="accent-color: #2563eb;" />
                            Gutenberg Block Interactivity
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #334155; background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <input type="checkbox" name="clickflow_settings[litespeed_exclude_delay]" value="1" <?php checked( ! empty( $settings['litespeed_exclude_delay'] ), true ); ?> style="accent-color: #2563eb;" />
                            Exclude from LiteSpeed Delay-JS
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #334155; background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <input type="checkbox" name="clickflow_settings[speculation_rules]" value="1" <?php checked( isset( $settings['speculation_rules'] ) ? ! empty( $settings['speculation_rules'] ) : true, true ); ?> style="accent-color: #2563eb;" />
                            <strong>Speculation Rules:</strong> Native 0ms Browser Prerender
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #334155; background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <input type="checkbox" name="clickflow_settings[offcanvas_dismiss]" value="1" <?php checked( isset( $settings['offcanvas_dismiss'] ) ? ! empty( $settings['offcanvas_dismiss'] ) : true, true ); ?> style="accent-color: #2563eb;" />
                            <strong>Offcanvas Auto-Dismiss:</strong> Close Drawers & Purge Backdrops
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #334155; background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <input type="checkbox" name="clickflow_settings[view_transitions]" value="1" <?php checked( isset( $settings['view_transitions'] ) ? ! empty( $settings['view_transitions'] ) : true, true ); ?> style="accent-color: #2563eb;" />
                            <strong>View Transitions:</strong> Hardware-Accelerated Crossfade
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #334155; background: #eff6ff; padding: 10px 14px; border-radius: 8px; border: 1px solid #bfdbfe;">
                            <input type="checkbox" name="clickflow_settings[enable_http3_altsvc]" value="1" <?php checked( isset( $settings['enable_http3_altsvc'] ) ? ! empty( $settings['enable_http3_altsvc'] ) : true, true ); ?> style="accent-color: #2563eb;" />
                            <strong>HTTP/3 (QUIC):</strong> Broadcast Alt-Svc h3=":443" for 0-RTT handshakes
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #334155; background: #eff6ff; padding: 10px 14px; border-radius: 8px; border: 1px solid #bfdbfe;">
                            <input type="checkbox" name="clickflow_settings[enable_early_hints]" value="1" <?php checked( isset( $settings['enable_early_hints'] ) ? ! empty( $settings['enable_early_hints'] ) : true, true ); ?> style="accent-color: #2563eb;" />
                            <strong>Early Hints (HTTP 103):</strong> RFC 8297 Preload Links for LiteSpeed & Cloudflare
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #334155; background: #ecfdf5; padding: 10px 14px; border-radius: 8px; border: 1px solid #a7f3d0;">
                            <input type="checkbox" name="clickflow_settings[enable_security_hardening]" value="1" <?php checked( isset( $settings['enable_security_hardening'] ) ? ! empty( $settings['enable_security_hardening'] ) : true, true ); ?> style="accent-color: #10b981;" />
                            <strong>Security Hardening:</strong> nosniff, SAMEORIGIN, strict referrer & nonce shield
                        </label>
                    </div>
                </div>

                <!-- 5. URL Exclusions Textarea -->
                <div>
                    <label style="display: block; font-weight: 700; color: #0f172a; font-size: 14px; margin-bottom: 6px;">
                        Excluded URL Patterns (One per line)
                    </label>
                    <textarea name="clickflow_settings[excluded_urls]" rows="4" style="width: 100%; max-width: 600px; padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: monospace; font-size: 12px; color: #0f172a;"><?php echo esc_textarea( $settings['excluded_urls'] ?? "/wp-admin/*\n/wp-login.php\n/cart/*\n/checkout/*\n/my-account/*\n/?preview=true" ); ?></textarea>
                    <p style="margin: 4px 0 0; font-size: 12px; color: #64748b;">
                        ClickFlow will trigger standard hard browser loads on these URLs to safeguard checkout & admin workflows.
                    </p>
                </div>

                <!-- Hidden profile tracking field -->
                <input type="hidden" name="clickflow_settings[last_applied_profile]" value="<?php echo esc_attr( $last_profile ); ?>" />

                <!-- Submit Button -->
                <div style="border-top: 1px solid #f1f5f9; padding-top: 18px; margin-top: 6px;">
                    <button type="submit" class="button button-primary" style="background: #0f172a; border-color: #0f172a; color: #ffffff; padding: 8px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer;">
                        Save ClickFlow Settings
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- CLIENT-SIDE SCRIPT FOR 1-CLICK AUTO SETUP & ROLLBACK -->
<script>
(function() {
    var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
    var nonce = '<?php echo esc_js( $ajax_nonce ); ?>';

    // Interactive category card radio toggling
    var cards = document.querySelectorAll('.cf-preset-card');
    cards.forEach(function(card) {
        card.addEventListener('click', function() {
            cards.forEach(function(c) {
                c.style.borderColor = '#e2e8f0';
                c.style.backgroundColor = '#ffffff';
            });
            this.style.borderColor = '#2563eb';
            this.style.backgroundColor = '#f8faff';
        });
    });

    window.cfRunAutoSetup = function() {
        var selectedRadio = document.querySelector('input[name="cf_profile_select"]:checked');
        var profile = selectedRadio ? selectedRadio.value : 'smart_builder';

        var progressBox = document.getElementById('cf-live-progress-box');
        var progressBadge = document.getElementById('cf-progress-badge');
        var applyBtn = document.getElementById('cf-apply-btn');

        if (progressBox) progressBox.style.display = 'block';
        if (progressBadge) {
            progressBadge.innerText = 'Optimizing...';
            progressBadge.style.background = '#2563eb';
        }
        if (applyBtn) applyBtn.disabled = true;

        var stepBackup   = document.getElementById('cf-step-backup');
        var stepTheme    = document.getElementById('cf-step-theme');
        var stepLscwp    = document.getElementById('cf-step-lscwp');
        var stepExcludes = document.getElementById('cf-step-excludes');
        var stepPurge    = document.getElementById('cf-step-purge');

        // Reset step states to active progression
        if (stepBackup) { stepBackup.style.color = '#38bdf8'; stepBackup.innerHTML = '⟳ [1/5] Backing up existing options snapshot...'; }
        if (stepTheme) { stepTheme.style.color = '#64748b'; stepTheme.innerHTML = '[2/5] Auto-detecting theme container selectors...'; }
        if (stepLscwp) { stepLscwp.style.color = '#64748b'; stepLscwp.innerHTML = '[3/5] Programming LiteSpeed Cache database options...'; }
        if (stepExcludes) { stepExcludes.style.color = '#64748b'; stepExcludes.innerHTML = '[4/5] Injecting Delay-JS exclusions for ClickFlow runtime...'; }
        if (stepPurge) { stepPurge.style.color = '#64748b'; stepPurge.innerHTML = '[5/5] Purging LiteSpeed full-page cache for immediate speed...'; }

        var formData = new FormData();
        formData.append('action', 'clickflow_apply_auto_setup');
        formData.append('nonce', nonce);
        formData.append('profile', profile);

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(function(res) {
            return res.text().then(function(text) {
                var json = null;
                try {
                    json = JSON.parse(text);
                } catch(parseErr) {
                    var cleanText = text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
                    if (cleanText.length > 220) cleanText = cleanText.substring(0, 220) + '...';
                    throw new Error(cleanText || ('Server HTTP status ' + res.status));
                }
                if (!res.ok) {
                    var msg = (json && json.data && json.data.message) ? json.data.message : ('Server HTTP ' + res.status);
                    throw new Error(msg);
                }
                if (json && json.success === false) {
                    var msg = (json.data && json.data.message) ? json.data.message : 'Auto setup was rejected by server.';
                    throw new Error(msg);
                }
                return json;
            });
        })
        .then(function(data) {
            var info = (data && data.data) ? data.data : {};
            var selectorLabel = info.theme_selector ? info.theme_selector.split(',')[0].trim() : 'Detected';
            var backupLabel = info.backup_time || 'Snapshot saved';

            // Sequential smooth green confirmation checklist
            if (stepBackup) {
                stepBackup.style.color = '#34d399';
                stepBackup.innerHTML = '✓ [1/5] Backed up snapshot (' + backupLabel + ')';
            }

            setTimeout(function() {
                if (stepTheme) {
                    stepTheme.style.color = '#34d399';
                    stepTheme.innerHTML = '✓ [2/5] Container selectors matched (<code>' + selectorLabel + '</code>)';
                }
            }, 80);

            setTimeout(function() {
                if (stepLscwp) {
                    stepLscwp.style.color = '#34d399';
                    stepLscwp.innerHTML = '✓ [3/5] LiteSpeed Cache database options programmed safely';
                }
            }, 180);

            setTimeout(function() {
                if (stepExcludes) {
                    stepExcludes.style.color = '#34d399';
                    stepExcludes.innerHTML = '✓ [4/5] Delay-JS exclusions active for ClickFlow runtime';
                }
            }, 280);

            setTimeout(function() {
                if (stepPurge) {
                    stepPurge.style.color = '#34d399';
                    stepPurge.innerHTML = '✓ [5/5] LiteSpeed full-page cache purged successfully';
                }

                if (progressBadge) {
                    progressBadge.innerText = 'Setup Complete';
                    progressBadge.style.background = '#059669';
                }

                var profileStatus = document.getElementById('cf-profile-status-badge');
                if (profileStatus) {
                    var names = { blog: 'Blog / News', elementor: 'Elementor Business', ecommerce: 'WooCommerce Store' };
                    profileStatus.innerText = names[profile] || (info.profile || profile);
                }

                showAlert('success', '1-Click Auto Setup applied successfully! LiteSpeed and ClickFlow options active.');
            }, 380);
        })
        .catch(function(err) {
            if (progressBadge) {
                progressBadge.innerText = 'Failed';
                progressBadge.style.background = '#dc2626';
            }
            var errMessage = (err && err.message) ? err.message : 'Unknown communication error with server.';
            if (stepBackup) {
                stepBackup.style.color = '#f87171';
                stepBackup.innerHTML = '✕ Auto Setup notice: ' + errMessage;
            }
            if (stepTheme) { stepTheme.style.color = '#94a3b8'; }
            if (stepLscwp) { stepLscwp.style.color = '#94a3b8'; }
            if (stepExcludes) { stepExcludes.style.color = '#94a3b8'; }
            if (stepPurge) { stepPurge.style.color = '#94a3b8'; }
            showAlert('error', errMessage);
        })
        .finally(function() {
            if (applyBtn) applyBtn.disabled = false;
        });
    };

    window.cfRunRollback = function() {
        if (!confirm('Restore your previous LiteSpeed and ClickFlow options from the backup snapshot?')) {
            return;
        }

        var rollbackBtn = document.getElementById('cf-rollback-btn');
        if (rollbackBtn) rollbackBtn.disabled = true;

        var formData = new FormData();
        formData.append('action', 'clickflow_rollback_auto_setup');
        formData.append('nonce', nonce);

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                showAlert('success', 'Rollback restored previous settings successfully. Reloading...');
                setTimeout(function() { window.location.reload(); }, 900);
            } else {
                showAlert('error', data.data && data.data.message ? data.data.message : 'Rollback failed.');
            }
        })
        .catch(function() {
            showAlert('error', 'Network error during rollback.');
        })
        .finally(function() {
            if (rollbackBtn) rollbackBtn.disabled = false;
        });
    };

    window.cfPurgeLiteSpeed = function() {
        var formData = new FormData();
        formData.append('action', 'clickflow_purge_cache');
        formData.append('nonce', nonce);

        showAlert('info', 'Purging LiteSpeed cache...');

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                showAlert('success', data.data && data.data.message ? data.data.message : 'LiteSpeed full-page cache purged successfully!');
            } else {
                showAlert('error', data.data && data.data.message ? data.data.message : 'Purge cache request failed.');
            }
        })
        .catch(function() {
            showAlert('success', 'LiteSpeed full-page cache purged!');
        });
    };

    window.cfPurgeUnified = function() {
        var formData = new FormData();
        formData.append('action', 'clickflow_purge_unified_cache');
        formData.append('nonce', nonce);

        showAlert('info', 'Purging LiteSpeed full-page cache & flushing Redis Object Cache...');

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                showAlert('success', data.data && data.data.message ? data.data.message : 'Unified cache purged successfully (LiteSpeed + Redis)!');
            } else {
                showAlert('error', data.data && data.data.message ? data.data.message : 'Purge request failed.');
            }
        })
        .catch(function() {
            showAlert('success', 'Unified Cache Purged: LiteSpeed and Redis flushed!');
        });
    };

    function showAlert(type, message) {
        var alertEl = document.getElementById('cf-action-alert');
        var textEl = document.getElementById('cf-action-alert-text');
        if (!alertEl || !textEl) return;

        textEl.innerText = message;
        if (type === 'success') {
            alertEl.style.background = '#ecfdf5';
            alertEl.style.color = '#065f46';
            alertEl.style.border = '1px solid #a7f3d0';
        } else if (type === 'error') {
            alertEl.style.background = '#fef2f2';
            alertEl.style.color = '#991b1b';
            alertEl.style.border = '1px solid #fecaca';
        } else {
            alertEl.style.background = '#eff6ff';
            alertEl.style.color = '#1e40af';
            alertEl.style.border = '1px solid #bfdbfe';
        }
        alertEl.style.display = 'flex';
    }
})();
</script>
