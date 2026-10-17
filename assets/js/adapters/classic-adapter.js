/**
 * LM Instant Navigation - Classic WordPress Adapter
 * Handles traditional theme templates, loop rendering, and comment reply form reset.
 */
window.LM_INAV_ClassicAdapter = {
    name: 'classic',

    isMatch: function(state) {
        return true; // Default fallback
    },

    afterReplace: function(container, state) {
        // Reset comment reply form state
        const commentForm = container.querySelector('#commentform');
        if (commentForm && window.addComment && typeof window.addComment.cancel === 'function') {
            try {
                window.addComment.cancel();
            } catch (e) {}
        }
    }
};
