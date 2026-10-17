/**
 * LM Instant Navigation - Gutenberg Client Adapter
 * Handles core block image lightboxes, embeds, and query loop pagination.
 */
window.LM_INAV_GutenbergAdapter = {
    name: 'gutenberg',

    isMatch: function(state) {
        return state && state.builder === 'gutenberg';
    },

    afterReplace: function(container, state) {
        // Core Block Lightbox re-binding
        const lightboxes = container.querySelectorAll('.wp-lightbox-overlay');
        if (lightboxes.length > 0 && window.wp && window.wp.interactivity) {
            // WordPress Interactivity API auto-hydration
        }

        // Core Embeds (YouTube / Vimeo resize check)
        const iframes = container.querySelectorAll('.wp-block-embed iframe');
        iframes.forEach(function(iframe) {
            if (iframe.dataset.src) {
                iframe.src = iframe.dataset.src;
            }
        });
    }
};
