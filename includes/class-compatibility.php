<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LM_INAV_Compatibility
 * Detects whether current post uses Elementor, Gutenberg, or Classic Editor.
 */
class LM_INAV_Compatibility {

    private $settings;
    private $adapters = array();

    public function __construct( $settings ) {
        $this->settings = $settings;
        $this->register_adapters();
    }

    private function register_adapters() {
        if ( $this->settings->get( 'enable_elementor', true ) ) {
            $this->adapters['elementor'] = new LM_INAV_Elementor_Adapter();
        }
        if ( $this->settings->get( 'enable_gutenberg', true ) ) {
            $this->adapters['gutenberg'] = new LM_INAV_Gutenberg_Adapter();
        }
        if ( $this->settings->get( 'enable_classic', true ) ) {
            $this->adapters['classic'] = new LM_INAV_WordPress_Adapter();
        }
    }

    public function detect_builder( $post_id ) {
        // Priority 1: Check Elementor
        if ( isset( $this->adapters['elementor'] ) && $this->adapters['elementor']->detect( $post_id ) ) {
            return 'elementor';
        }

        // Priority 2: Check Gutenberg
        if ( isset( $this->adapters['gutenberg'] ) && $this->adapters['gutenberg']->detect( $post_id ) ) {
            return 'gutenberg';
        }

        // Fallback: Classic WordPress theme / editor
        return 'classic';
    }

    public function is_builder_safe( $builder ) {
        if ( isset( $this->adapters[ $builder ] ) ) {
            return $this->adapters[ $builder ]->is_safe();
        }
        return true;
    }

    public function get_required_assets( $post_id, $builder ) {
        if ( isset( $this->adapters[ $builder ] ) ) {
            return $this->adapters[ $builder ]->get_required_assets( $post_id );
        }
        return array();
    }
}
