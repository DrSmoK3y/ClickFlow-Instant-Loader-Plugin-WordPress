/**
 * LM Instant Navigation - Prefetch Engine
 * Observes hover intent, touchstart, and keyboard focus with connection-awareness.
 */
window.LM_INAV_Prefetch = (function() {
    'use strict';

    const queue = new Set();
    let hoverTimer = null;

    function isSaveDataActive() {
        return Boolean(navigator.connection && navigator.connection.saveData);
    }

    return {
        init: function(config, fetchCallback, eligibilityCallback) {
            if (!config.prefetch || isSaveDataActive()) return;

            const delay = config.prefetchDelay || 65;
            const MAX_CONCURRENT = 3;
            let inFlight = 0;

            // Hover Intent
            document.addEventListener('mouseover', function(e) {
                const anchor = e.target.closest('a');
                if (!anchor || !eligibilityCallback(anchor, e)) return;

                const url = anchor.href;
                if (queue.has(url) || inFlight >= MAX_CONCURRENT) return;

                clearTimeout(hoverTimer);
                hoverTimer = setTimeout(function() {
                    if (inFlight >= MAX_CONCURRENT) return;
                    queue.add(url);
                    inFlight++;
                    fetchCallback(url).finally(function() {
                        queue.delete(url);
                        inFlight = Math.max(0, inFlight - 1);
                    });
                }, delay);
            }, { passive: true });

            document.addEventListener('mouseout', function() {
                clearTimeout(hoverTimer);
            }, { passive: true });

            // Touch Intent for mobile devices
            document.addEventListener('touchstart', function(e) {
                const anchor = e.target.closest('a');
                if (!anchor || !eligibilityCallback(anchor, e)) return;

                const url = anchor.href;
                if (!queue.has(url) && inFlight < MAX_CONCURRENT) {
                    queue.add(url);
                    inFlight++;
                    fetchCallback(url).finally(function() {
                        queue.delete(url);
                        inFlight = Math.max(0, inFlight - 1);
                    });
                }
            }, { passive: true });
        }
    };
})();
