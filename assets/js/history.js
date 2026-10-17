/**
 * LM Instant Navigation - History Manager
 * Manages pushState, popstate, and scroll restoration.
 */
window.LM_INAV_History = {
    init: function(onPopstate) {
        window.addEventListener('popstate', function(e) {
            onPopstate(window.location.href);
        });
    },

    push: function(url) {
        window.history.pushState({ url: url }, '', url);
    },

    replace: function(url) {
        window.history.replaceState({ url: url }, '', url);
    },

    restoreScroll: function(url) {
        const hash = new URL(url, window.location.href).hash;
        if (hash) {
            const target = document.querySelector(hash);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
                return;
            }
        }
        window.scrollTo({ top: 0, behavior: 'auto' });
    }
};
