<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LM_INAV_Elementor_Adapter
 * Handles Elementor post detection, per-post CSS path extraction, and editor safety checks.
 */
class LM_INAV_Elementor_Adapter implements LM_INAV_Adapter_Interface {

    public function detect( $post_id ): bool {
        if ( ! $post_id || ! class_exists( '\\Elementor\\Plugin' ) ) {
            return false;
        }
        return (bool) get_post_meta( $post_id, '_elementor_edit_mode', true );
    }

    public function is_safe(): bool {
        // Exclude live Elementor editor and preview iframe
        if ( class_exists( '\\Elementor\\Plugin' ) ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
                return false;
            }
        }
        return true;
    }

    public function get_required_assets( $post_id ): array {
        $assets = array();
        if ( ! $post_id ) {
            return $assets;
        }

        // Elementor generated post-specific CSS file: post-{id}.css
        $upload_dir   = wp_upload_dir();
        $post_css_url = $upload_dir['baseurl'] . '/elementor/css/post-' . $post_id . '.css';
        $assets[]     = array(
            'handle' => 'elementor-post-' . $post_id,
            'src'    => $post_css_url,
            'type'   => 'style'
        );

        return $assets;
    }

    public function get_client_adapter_name(): string {
        return 'ElementorAdapter';
    }
}
