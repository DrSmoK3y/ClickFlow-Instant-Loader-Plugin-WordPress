/**
 * LM Instant Navigation - Elementor Client Adapter
 * Reinitializes Elementor frontend widgets and motion effects upon container swap.
 */
window.LM_INAV_ElementorAdapter = {
    name: 'elementor',

    isMatch: function(state) {
        return state && state.builder === 'elementor';
    },

    beforeReplace: function(context) {
        // Elementor pre-swap teardown if required
    },

    afterReplace: function(container, state) {
        if (!window.elementorFrontend) {
            return;
        }

        // Elementor Elements Handler runReadyTrigger
        if (window.elementorFrontend.elementsHandler && typeof window.elementorFrontend.elementsHandler.runReadyTrigger === 'function') {
            window.elementorFrontend.elementsHandler.runReadyTrigger(container);
        }

        // Global Element Ready Hook
        if (window.elementorFrontend.hooks && typeof window.elementorFrontend.hooks.doAction === 'function') {
            const $container = window.jQuery ? window.jQuery(container) : container;
            window.elementorFrontend.hooks.doAction('frontend/element_ready/global', $container);
        }
    }
};
