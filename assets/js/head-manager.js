/**
 * LM Instant Navigation - Head Manager
 * Updates document title, canonical link, meta tags, and body classes.
 */
window.LM_INAV_HeadManager = {
    update: function(nextDoc) {
        // Document Title
        if (nextDoc.title) {
            document.title = nextDoc.title;
        }

        // Meta Description
        const currDesc = document.querySelector('meta[name="description"]');
        const nextDesc = nextDoc.querySelector('meta[name="description"]');
        if (nextDesc) {
            if (currDesc) currDesc.setAttribute('content', nextDesc.getAttribute('content'));
            else document.head.appendChild(nextDesc.cloneNode(true));
        }

        // Canonical URL
        const currCanon = document.querySelector('link[rel="canonical"]');
        const nextCanon = nextDoc.querySelector('link[rel="canonical"]');
        if (nextCanon) {
            if (currCanon) currCanon.setAttribute('href', nextCanon.getAttribute('href'));
            else document.head.appendChild(nextCanon.cloneNode(true));
        }

        // OpenGraph Title & URL
        const nextOgTitle = nextDoc.querySelector('meta[property="og:title"]');
        if (nextOgTitle) {
            const currOg = document.querySelector('meta[property="og:title"]');
            if (currOg) currOg.setAttribute('content', nextOgTitle.getAttribute('content'));
        }

        // Synchronize <body> classes to maintain page/theme layout rules
        if (nextDoc.body && nextDoc.body.className) {
            document.body.className = nextDoc.body.className;
        }
    }
};
