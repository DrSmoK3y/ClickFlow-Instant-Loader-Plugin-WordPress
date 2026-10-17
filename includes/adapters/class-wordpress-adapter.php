<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LM_INAV_WordPress_Adapter
 * Default adapter for Classic Editor posts and standard PHP theme templates.
 */
class LM_INAV_WordPress_Adapter implements LM_INAV_Adapter_Interface {

    public function detect( $post_id ): bool {
        // Universal baseline adapter
        return true;
    }

    public function is_safe(): bool {
        return true;
    }

    public function get_required_assets( $post_id ): array {
        return array();
    }

    public function get_client_adapter_name(): string {
        return 'ClassicAdapter';
    }
}
