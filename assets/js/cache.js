/**
 * LM Instant Navigation - Memory Cache Engine
 * In-memory LRU document storage with configurable max size and TTL.
 */
window.LM_INAV_Cache = (function() {
    'use strict';

    const store = new Map();

    return {
        get: function(url, ttlSeconds) {
            const cleanUrl = url.split('#')[0];
            const entry = store.get(cleanUrl);
            if (!entry) return null;

            const maxAgeMs = (ttlSeconds || 300) * 1000;
            if (Date.now() - entry.timestamp > maxAgeMs) {
                store.delete(cleanUrl);
                return null;
            }
            return entry;
        },

        set: function(url, data, maxPages) {
            const cleanUrl = url.split('#')[0];
            const limit = maxPages || 15;

            // LRU Eviction: delete oldest entry if limit reached
            if (store.size >= limit) {
                const oldestKey = store.keys().next().value;
                store.delete(oldestKey);
            }

            store.set(cleanUrl, Object.assign({}, data, { timestamp: Date.now() }));
        },

        has: function(url) {
            const cleanUrl = url.split('#')[0];
            return store.has(cleanUrl);
        },

        clear: function() {
            store.clear();
        },

        size: function() {
            return store.size;
        }
    };
})();
