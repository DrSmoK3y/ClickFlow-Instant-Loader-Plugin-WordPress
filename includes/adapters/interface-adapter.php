<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LM_INAV_Adapter_Interface
 * Uniform interface for page builders / editor adapters.
 */
interface LM_INAV_Adapter_Interface {

    /**
     * Determine if current post uses this builder/editor.
     *
     * @param int $post_id WordPress post ID.
     * @return bool
     */
    public function detect( $post_id ): bool;

    /**
     * Determine if soft navigation is safe for this page.
     *
     * @return bool
     */
    public function is_safe(): bool;

    /**
     * Return list of required stylesheets/scripts unique to this post.
     *
     * @param int $post_id WordPress post ID.
     * @return array
     */
    public function get_required_assets( $post_id ): array;

    /**
     * Return client-side JavaScript adapter name.
     *
     * @return string
     */
    public function get_client_adapter_name(): string;
}
