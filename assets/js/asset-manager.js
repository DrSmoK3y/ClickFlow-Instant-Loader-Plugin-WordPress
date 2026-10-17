/**
 * LM Instant Navigation - Asset Manager
 * Diffs stylesheets and loads required CSS before revealing content (preventing FOUC).
 */
window.LM_INAV_AssetManager = {
    syncStylesheets: async function(nextDoc) {
        const nextLinks = Array.from(nextDoc.querySelectorAll('link[rel="stylesheet"]'));
        const currentHrefs = new Set(
            Array.from(document.querySelectorAll('link[rel="stylesheet"]')).map(function(link) {
                return link.href;
            })
        );

        const missingLinks = nextLinks.filter(function(link) {
            return link.href && !currentHrefs.has(link.href);
        });

        if (missingLinks.length === 0) {
            return;
        }

        const promises = missingLinks.map(function(link) {
            return new Promise(function(resolve) {
                const newLink = document.createElement('link');
                newLink.rel = 'stylesheet';
                newLink.href = link.href;
                newLink.id = link.id || ('lm-inav-css-' + Math.random().toString(36).substring(7));
                newLink.onload = function() { resolve(); };
                newLink.onerror = function() {
                    console.warn('[LM INAV] Non-blocking CSS load failure:', link.href);
                    resolve(); // Do not block navigation on CSS error
                };
                document.head.appendChild(newLink);
            });
        });

        // Race against a safety timeout (2500ms max)
        await Promise.race([
            Promise.all(promises),
            new Promise(function(resolve) { setTimeout(resolve, 2500); })
        ]);
    }
};
