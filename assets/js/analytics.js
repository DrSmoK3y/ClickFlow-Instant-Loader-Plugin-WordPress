/**
 * LM Instant Navigation - Analytics & Lifecycle Dispatcher
 * Dispatches canonical lm-inav:page-view events and integrates with GA4 / GTM.
 */
window.LM_INAV_Analytics = {
    dispatchPageView: function(url, title, elapsed, state) {
        // Dispatch unified custom event
        document.dispatchEvent(new CustomEvent('lm-inav:page-view', {
            detail: {
                url: url,
                title: title,
                elapsed: elapsed,
                state: state
            }
        }));

        // Google Tag Manager
        if (window.dataLayer && Array.isArray(window.dataLayer)) {
            window.dataLayer.push({
                event: 'page_view',
                page_path: window.location.pathname,
                page_title: title
            });
        }

        // Google Analytics 4 gtag
        if (typeof window.gtag === 'function') {
            window.gtag('event', 'page_view', {
                page_path: window.location.pathname,
                page_title: title
            });
        }
    }
};
