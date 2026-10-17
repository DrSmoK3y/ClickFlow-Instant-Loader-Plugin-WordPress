<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LM_INAV_Gutenberg_Adapter
 * Handles Gutenberg (Block Editor) detection and core block asset handling.
 */
class LM_INAV_Gutenberg_Adapter implements LM_INAV_Adapter_Interface {

    public function detect( $post_id ): bool {
        if ( ! $post_id ) {
            return false;
        }
        $post = get_post( $post_id );
        if ( ! $post ) {
            return false;
        }
        return function_exists( 'has_blocks' ) && has_blocks( $post->post_content );
    }

    public function is_safe(): bool {
        // Native Gutenberg blocks produce clean semantic HTML/CSS
        return true;
    }

    public function get_required_assets( $post_id ): array {
        $assets = array();
        if ( wp_style_is( 'wp-block-library', 'enqueued' ) ) {
            $assets[] = 'wp-block-library';
        }
        return $assets;
    }

    public function get_client_adapter_name(): string {
        return 'GutenbergAdapter';
    }
}
