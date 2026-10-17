<?php
/**
 * LM Instant Navigation Uninstall Handler.
 * Deletes plugin settings from WordPress options table upon uninstallation.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'lm_inav_settings' );
