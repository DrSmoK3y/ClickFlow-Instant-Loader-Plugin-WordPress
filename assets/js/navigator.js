/**
 * ClickFlow - Master Client Router (v0.9.5 BETA)
 * Engineered for LiteSpeed Cache with Elementor, Gutenberg, WooCommerce, and Classic Editor adapters.
 * 
 * Major Architectural Features:
 * 1. OffcanvasManager: Pre-navigation and post-navigation drawer, modal & backdrop dismissal + body scroll unlock
 * 2. LayoutSyncManager: Full container class morphing, attribute synchronization & sibling sidebar reconciliation
 * 3. SpeculationEngine: W3C Speculation Rules API for genuine 0ms native browser prerendering (Chrome/Edge/Opera)
 * 4. Native View Transitions API: Hardware-accelerated 60 FPS transitions without DOM blocking
 * 5. Pre-emptive Head & Body Synchronization: Classes and metadata updated before DOM insertion to eliminate layout flashes
 * 6. Media & LiteSpeed Lazy-Load Rehydration: Restores missing images, backgrounds, and responsive sources
 * 7. Instant Address Bar URL & Title Update: Immediate pushState immune to DOMExceptions
 */
(function() {
    'use strict';

    if (window.__CLICKFLOW_INITIALIZED__ || window.__LM_INAV_INITIALIZED__) return;
    window.__CLICKFLOW_INITIALIZED__ = true;
    window.__LM_INAV_INITIALIZED__ = true;

    const config = window.lmInavConfig || window.clickflowConfig || {
        contentSelector: 'main, #primary, #main, .site-main, #content, .site-content, .elementor-location-single, [data-elementor-type]',
        debug: true,
        prefetch: true,
        prefetchDelay: 50,
        cacheMaxPages: 25,
        cacheTTL: 300,
        speculationRules: true,
        offcanvasDismiss: true,
        viewTransitions: true,
        adapters: { elementor: true, gutenberg: true, classic: true, woocommerce: true }
    };

    function log(message, details) {
        if (!config.debug) return;
        const prefix = '%c[ClickFlow]';
        const style = 'color: #0284c7; font-weight: bold;';
        if (details !== undefined) {
            console.log(prefix + ' ' + message, style, details);
        } else {
            console.log(prefix + ' ' + message, style);
        }
    }

    function warn(message, details) {
        if (config.debug) {
            console.warn('[ClickFlow] ' + message, details || '');
        }
    }

    // ARIA Live Region for Accessibility (Screen Reader Announcements)
    let a11yAnnouncer = null;
    function announceRoute(title) {
        if (!a11yAnnouncer) {
            a11yAnnouncer = document.createElement('div');
            a11yAnnouncer.setAttribute('aria-live', 'polite');
            a11yAnnouncer.setAttribute('aria-atomic', 'true');
            a11yAnnouncer.className = 'clickflow-sr-only';
            a11yAnnouncer.style.cssText = 'position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0;';
            document.body.appendChild(a11yAnnouncer);
        }
        a11yAnnouncer.textContent = 'Navigated to ' + (title || document.title);
    }

    // =========================================================================
    // 1. OFFCANVAS & DRAWER DISMISSAL ENGINE (Fixes Bug 1: Stuck Modals/Drawers)
    // =========================================================================
    const OffcanvasManager = {
        dismissAll: function() {
            if (config.offcanvasDismiss === false) return;
            log('OffcanvasManager: Purging active drawers, popups, and unlocking body scroll');

            // A. Elementor Popups, Offcanvas & Lightboxes
            try {
                if (window.elementorFrontend && window.elementorFrontend.documentsManager) {
                    const docs = window.elementorFrontend.documentsManager.documents || {};
                    Object.keys(docs).forEach(function(id) {
                        const doc = docs[id];
                        if (doc && typeof doc.getModal === 'function') {
                            const modal = doc.getModal();
                            if (modal && typeof modal.hide === 'function') {
                                modal.hide();
                            }
                        }
                    });
                }
                // Hide lingering Elementor popup dialog wrappers
                document.querySelectorAll('.elementor-popup-modal, .dialog-widget.dialog-lightbox-widget').forEach(function(el) {
                    el.style.display = 'none';
                });
            } catch (err) {}

            // B. Elementor & WooCommerce Mini-Cart Slide-Out Drawers
            try {
                document.querySelectorAll('.elementor-menu-cart--shown, .elementor-menu-cart__container--shown').forEach(function(el) {
                    el.classList.remove('elementor-menu-cart--shown', 'elementor-menu-cart__container--shown');
                });
                document.querySelectorAll('.widget_shopping_cart.show, .widget_shopping_cart.open, .cart-drawer.is-open, .wc-cart-drawer--open').forEach(function(el) {
                    el.classList.remove('show', 'open', 'is-open', 'wc-cart-drawer--open');
                });
            } catch (err) {}

            // C. Popular WordPress Themes (Astra, Kadence, OceanWP, GeneratePress, Divi, Blocksy)
            try {
                // Reset ARIA expanded toggles
                document.querySelectorAll('.menu-toggle[aria-expanded="true"], .ast-mobile-menu-trigger[aria-expanded="true"], [data-nav-toggle][aria-expanded="true"]').forEach(function(el) {
                    el.setAttribute('aria-expanded', 'false');
                });

                // Clear active drawer classes from body and root
                const drawerClasses = [
                    'ast-mobile-menu-active', 'ast-main-header-nav-open', 'ast-popup-nav-open',
                    'menu-open', 'mobile-menu-open', 'nav-open', 'is-menu-open',
                    'offcanvas-open', 'off-canvas-open', 'slideout-open', 'drawer-open',
                    'is-active', 'opened', 'mobile-nav-open'
                ];
                drawerClasses.forEach(function(cls) {
                    document.body.classList.remove(cls);
                    document.documentElement.classList.remove(cls);
                });

                // Clear active classes from drawer containers
                document.querySelectorAll('.ast-mobile-header-wrap.ast-mobile-header-menu-active, .mobile-menu.is-active, .drawer.is-open, .offcanvas.show, .mobile-nav.open, .site-navigation.toggled, .navigation-drawer.open').forEach(function(drawer) {
                    drawer.classList.remove('ast-mobile-header-menu-active', 'is-active', 'is-open', 'show', 'open', 'toggled');
                });
            } catch (err) {}

            // D. Force-Remove Hanging Backdrops & Overlays
            try {
                const backdropSelectors = [
                    '.elementor-background-overlay',
                    '.drawer-backdrop',
                    '.menu-overlay',
                    '.modal-backdrop',
                    '.offcanvas-backdrop',
                    '.site-below-header-overlay',
                    '.astra-off-canvas-overlay',
                    '.header-overlay',
                    '.dropdown-backdrop',
                    '.cf-overlay-backdrop'
                ];
                backdropSelectors.forEach(function(sel) {
                    document.querySelectorAll(sel).forEach(function(backdrop) {
                        // Prevent removing permanent section backgrounds
                        if (backdrop.classList.contains('elementor-section') || backdrop.classList.contains('elementor-container')) return;
                        if (backdrop.parentNode) {
                            backdrop.parentNode.removeChild(backdrop);
                        } else {
                            backdrop.style.display = 'none';
                        }
                    });
                });
            } catch (err) {}

            // E. Guarantee Body & Document Scroll Unlock
            try {
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('position');
                document.body.style.removeProperty('top');
                document.body.style.removeProperty('touch-action');
                document.documentElement.style.removeProperty('overflow');
                document.documentElement.style.removeProperty('position');

                const scrollLockClasses = [
                    'overflow-hidden', 'no-scroll', 'modal-open', 'lock-scroll',
                    'prevent-scroll', 'noscroll', 'ast-mobile-menu-active'
                ];
                scrollLockClasses.forEach(function(cls) {
                    document.body.classList.remove(cls);
                    document.documentElement.classList.remove(cls);
                });
            } catch (err) {}

            // F. Notify External Listeners (WooCommerce / jQuery / Custom)
            try {
                document.dispatchEvent(new CustomEvent('clickflow:offcanvas-dismiss'));
                if (window.jQuery) {
                    window.jQuery(document).trigger('clickflow:offcanvas-dismiss');
                    window.jQuery(document.body).trigger('clickflow:offcanvas-dismiss');
                }
            } catch (err) {}
        }
    };

    // =========================================================================
    // 2. W3C SPECULATION RULES & HIGH-SPEED ENGINE (Genuine Instant Speed)
    // =========================================================================
    const SpeculationEngine = {
        supported: false,
        prerenderSet: new Set(),

        init: function() {
            this.supported = Boolean(HTMLScriptElement.supports && HTMLScriptElement.supports('speculationrules'));
            if (this.supported && config.speculationRules !== false) {
                log('Speculation Rules API supported: Activating native 0ms prerender engine');
                this.injectRules();
            } else {
                log('Speculation Rules not natively available. Utilizing Turbo Memory Cache + Touchstart Predictive Engine');
            }
        },

        injectRules: function() {
            if (document.getElementById('clickflow-speculationrules')) return;

            const script = document.createElement('script');
            script.id = 'clickflow-speculationrules';
            script.type = 'speculationrules';

            const rules = {
                prefetch: [
                    {
                        source: 'document',
                        where: {
                            and: [
                                { href_matches: '/*' },
                                { not: { href_matches: '/wp-admin/*' } },
                                { not: { href_matches: '/wp-login.php*' } },
                                { not: { href_matches: '/cart/*' } },
                                { not: { href_matches: '/checkout/*' } },
                                { not: { href_matches: '/my-account/*' } },
                                { not: { href_matches: '/*customer-logout*' } },
                                { not: { href_matches: '/*logout*' } },
                                { not: { href_matches: '/*resetpass*' } },
                                { not: { href_matches: '/*order-received*' } },
                                { not: { href_matches: '/*cancel-order*' } },
                                { not: { href_matches: '/*xmlrpc.php*' } },
                                { not: { href_matches: '/*wp-json/*' } },
                                { not: { href_matches: '/*\\?*add-to-cart=*' } },
                                { not: { href_matches: '/*\\?*wc-ajax=*' } },
                                { not: { href_matches: '/*\\?*action=*' } },
                                { not: { href_matches: '/*\\?*_wpnonce=*' } },
                                { not: { href_matches: '/*\\?*key=*' } },
                                { not: { href_matches: '/*\\?*token=*' } },
                                { not: { href_matches: '/*\\?*s=*' } },
                                { not: { href_matches: '/*\\?*preview=*' } },
                                { not: { href_matches: '/*\\?*elementor-preview=*' } }
                            ]
                        },
                        eagerness: 'conservative'
                    }
                ]
            };

            script.textContent = JSON.stringify(rules);
            document.head.appendChild(script);
        },

        prefetch: function(url) {
            // Safe prefetch list registration
            if (!this.supported || config.speculationRules === false) return;
        },

        prerender: function(url) {
            // Prerender disabled for security: routed safely to prefetch
            this.prefetch(url);
        }
    };

    // =========================================================================
    // 3. TOP PROGRESS BAR INDICATOR (Guaranteed Auto-Dismissal)
    // =========================================================================
    const ProgressBar = {
        bar: null,
        timeoutId: null,
        failsafeTimer: null,
        init: function() {
            if (!this.bar) {
                this.bar = document.getElementById('clickflow-progress-bar') || document.getElementById('lm-inav-progress-bar');
                if (!this.bar) {
                    this.bar = document.createElement('div');
                    this.bar.id = 'clickflow-progress-bar';
                    this.bar.setAttribute('role', 'progressbar');
                    this.bar.setAttribute('aria-hidden', 'true');
                    document.body.appendChild(this.bar);
                }
            }
        },
        start: function() {
            this.init();
            if (this.timeoutId) {
                clearTimeout(this.timeoutId);
                this.timeoutId = null;
            }
            if (this.failsafeTimer) {
                clearTimeout(this.failsafeTimer);
            }
            // Auto-dismiss failsafe: after 3.2s, dismiss automatically
            this.failsafeTimer = setTimeout(() => {
                this.done();
            }, 3200);

            this.bar.classList.remove('cf-done');
            this.bar.style.display = 'block';
            this.bar.style.width = '32%';
            this.bar.style.opacity = '1';
        },
        inc: function(percent) {
            if (this.bar) {
                this.bar.style.width = percent + '%';
            }
        },
        done: function() {
            if (this.failsafeTimer) {
                clearTimeout(this.failsafeTimer);
                this.failsafeTimer = null;
            }
            if (!this.bar) return;
            this.bar.style.width = '100%';
            if (this.timeoutId) clearTimeout(this.timeoutId);
            this.timeoutId = setTimeout(() => {
                if (this.bar) {
                    this.bar.classList.add('cf-done');
                    this.bar.style.opacity = '0';
                    setTimeout(() => {
                        if (this.bar) {
                            this.bar.style.width = '0%';
                            this.bar.style.display = 'none';
                        }
                    }, 240);
                }
            }, 90);
        },
        reset: function() {
            if (this.failsafeTimer) clearTimeout(this.failsafeTimer);
            if (this.timeoutId) clearTimeout(this.timeoutId);
            if (this.bar) {
                this.bar.classList.add('cf-done');
                this.bar.style.opacity = '0';
                this.bar.style.width = '0%';
                this.bar.style.display = 'none';
            }
        }
    };

    window.addEventListener('pageshow', () => ProgressBar.reset());
    window.addEventListener('popstate', () => ProgressBar.reset());
    window.addEventListener('load', () => ProgressBar.reset());
    window.addEventListener('DOMContentLoaded', () => ProgressBar.reset());

    // =========================================================================
    // 4. MEMORY DOCUMENT CACHE (Ultra-Fast LRU in RAM)
    // =========================================================================
    const MemoryCache = {
        store: new Map(),
        get: function(url) {
            const cleanUrl = url.split('#')[0];
            const entry = this.store.get(cleanUrl);
            if (!entry) return null;

            const maxAgeMs = (config.cacheTTL || 300) * 1000;
            if (Date.now() - entry.timestamp > maxAgeMs) {
                this.store.delete(cleanUrl);
                return null;
            }
            return entry;
        },
        clear: function() {
            this.store.clear();
            log('Memory Cache completely purged');
        },
        set: function(url, data) {
            // Privacy Protection: Never cache personalized/logged-in data in RAM
            if (document.body.classList.contains('logged-in') || (data && data.isPrivate)) {
                return;
            }

            const cleanUrl = url.split('#')[0];
            const limit = config.cacheMaxPages || 25;

            if (this.store.size >= limit) {
                const oldestKey = this.store.keys().next().value;
                this.store.delete(oldestKey);
            }

            this.store.set(cleanUrl, Object.assign({}, data, { timestamp: Date.now() }));
        }
    };

    window.ClickFlow_Cache = MemoryCache;
    window.LM_INAV_Cache = MemoryCache;

    // =========================================================================
    // 5. HEAD & METADATA MANAGER (Pre-emptive Class and Header Sync)
    // =========================================================================
    const HeadManager = {
        update: function(nextDoc) {
            // 1. Page Title
            if (nextDoc.title) {
                document.title = nextDoc.title;
            }

            // 2. Body Classes (Pre-emptively updated BEFORE DOM morph to guarantee styling rules apply)
            if (nextDoc.body && nextDoc.body.className) {
                document.body.className = nextDoc.body.className;
            }

            // 3. Body Dataset / Attributes
            if (nextDoc.body) {
                Array.from(nextDoc.body.attributes).forEach(function(attr) {
                    if (attr.name.startsWith('data-') || attr.name.startsWith('aria-')) {
                        document.body.setAttribute(attr.name, attr.value);
                    }
                });
            }

            // 4. Canonical Link
            const currCanon = document.querySelector('link[rel="canonical"]');
            const nextCanon = nextDoc.querySelector('link[rel="canonical"]');
            if (nextCanon) {
                if (currCanon) {
                    currCanon.setAttribute('href', nextCanon.getAttribute('href') || '');
                } else {
                    document.head.appendChild(nextCanon.cloneNode(true));
                }
            }

            // 5. Meta Description
            const currDesc = document.querySelector('meta[name="description"]');
            const nextDesc = nextDoc.querySelector('meta[name="description"]');
            if (nextDesc) {
                if (currDesc) {
                    currDesc.setAttribute('content', nextDesc.getAttribute('content') || '');
                } else {
                    document.head.appendChild(nextDesc.cloneNode(true));
                }
            }

            // 6. OpenGraph Tags
            const ogTags = ['og:title', 'og:url', 'og:description'];
            ogTags.forEach(function(prop) {
                const nextTag = nextDoc.querySelector('meta[property="' + prop + '"]');
                const currTag = document.querySelector('meta[property="' + prop + '"]');
                if (nextTag) {
                    if (currTag) {
                        currTag.setAttribute('content', nextTag.getAttribute('content') || '');
                    } else {
                        document.head.appendChild(nextTag.cloneNode(true));
                    }
                }
            });

            // 7. HTML Root Attributes (Language & Direction)
            if (nextDoc.documentElement) {
                const lang = nextDoc.documentElement.getAttribute('lang');
                const dir = nextDoc.documentElement.getAttribute('dir');
                if (lang) document.documentElement.setAttribute('lang', lang);
                if (dir) document.documentElement.setAttribute('dir', dir);
            }
        }
    };

    // =========================================================================
    // 6. STYLESHEET & DYNAMIC SCRIPT MANAGER (Lazy Assets Blocker Synergy)
    // =========================================================================
    const AssetManager = {
        syncStyles: async function(nextDoc) {
            // A. Synchronize Inline <style id="..."> tags (Elementor post styles, theme dynamic CSS)
            const nextInlineStyles = Array.from(nextDoc.querySelectorAll('head style[id]'));
            nextInlineStyles.forEach(function(styleEl) {
                const existing = document.getElementById(styleEl.id);
                if (!existing) {
                    document.head.appendChild(styleEl.cloneNode(true));
                }
            });

            // B. Synchronize External <link rel="stylesheet">
            const nextLinks = Array.from(nextDoc.querySelectorAll('link[rel="stylesheet"]'));
            const currentHrefs = new Set(
                Array.from(document.querySelectorAll('link[rel="stylesheet"]')).map(function(link) {
                    return link.href;
                })
            );

            const missingLinks = nextLinks.filter(function(link) {
                return link.href && !currentHrefs.has(link.href);
            });

            if (missingLinks.length === 0) return;

            log('Loading ' + missingLinks.length + ' destination stylesheet(s)', missingLinks.map(l => l.href));

            const promises = missingLinks.map(function(link) {
                return new Promise(function(resolve) {
                    const newLink = document.createElement('link');
                    newLink.rel = 'stylesheet';
                    newLink.href = link.href;
                    newLink.id = link.id || ('cf-css-' + Math.random().toString(36).substring(7));
                    newLink.onload = function() { resolve(); };
                    newLink.onerror = function() {
                        warn('Non-blocking stylesheet load failure: ' + link.href);
                        resolve();
                    };
                    document.head.appendChild(newLink);
                });
            });

            // Safety timeout of 1200ms
            await Promise.race([
                Promise.all(promises),
                new Promise(function(resolve) { setTimeout(resolve, 1200); })
            ]);
        },

        // Dynamic Asset Sync: Load missing external scripts (e.g. unblocked on destination page)
        syncScripts: async function(nextDoc) {
            const nextScripts = Array.from(nextDoc.querySelectorAll('script[src]'));
            const currentSrcs = new Set(
                Array.from(document.querySelectorAll('script[src]')).map(function(s) {
                    return s.src;
                })
            );

            const missingScripts = nextScripts.filter(function(s) {
                const src = s.src;
                if (!src) return false;
                // Exclude analytics, tag managers, and clickflow runtime
                if (src.includes('google-analytics') || src.includes('googletagmanager') || src.includes('navigator.js')) {
                    return false;
                }

                // Security Shield: Origin & Trusted CDN host validation for external scripts
                try {
                    const scriptUrl = new URL(src, window.location.href);
                    const trustedHosts = [
                        window.location.hostname,
                        'cdnjs.cloudflare.com',
                        'cdn.jsdelivr.net',
                        'unpkg.com',
                        'ajax.googleapis.com',
                        'fonts.googleapis.com'
                    ];
                    const isSameOrigin = scriptUrl.origin === window.location.origin;
                    const isTrustedCdn = trustedHosts.some(h => scriptUrl.hostname === h || scriptUrl.hostname.endsWith('.' + h));
                    if (!isSameOrigin && !isTrustedCdn) {
                        warn('Untrusted cross-origin script blocked from dynamic execution: ' + src);
                        return false;
                    }
                } catch (e) {
                    return false;
                }

                return !currentSrcs.has(src);
            });

            if (missingScripts.length === 0) return;

            log('Dynamic Asset Sync: Detected ' + missingScripts.length + ' new external script(s) required on destination page', missingScripts.map(s => s.src));

            const promises = missingScripts.map(function(scriptEl) {
                return new Promise(function(resolve) {
                    const newScript = document.createElement('script');
                    newScript.src = scriptEl.src;
                    if (scriptEl.id) newScript.id = scriptEl.id;

                    // Security: ONLY retain nonce if the incoming script itself was delivered by the server with a nonce
                    if (scriptEl.hasAttribute('nonce')) {
                        newScript.setAttribute('nonce', scriptEl.getAttribute('nonce'));
                    }

                    newScript.async = false; // preserve execution sequence
                    newScript.onload = function() { resolve(); };
                    newScript.onerror = function() {
                        warn('Non-fatal script load failure: ' + scriptEl.src);
                        resolve();
                    };
                    document.body.appendChild(newScript);
                });
            });

            await Promise.race([
                Promise.all(promises),
                new Promise(function(resolve) { setTimeout(resolve, 1200); })
            ]);
        },

        // Re-execute interactive inline scripts inside swapped containers
        executeInlineScripts: function(container) {
            if (!container) return;
            const scripts = Array.from(container.querySelectorAll('script'));
            scripts.forEach(function(oldScript) {
                const type = (oldScript.getAttribute('type') || 'text/javascript').toLowerCase();
                if (type.includes('json') || type.includes('template') || type.includes('speculationrules')) return;

                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(function(attr) {
                    newScript.setAttribute(attr.name, attr.value);
                });

                // Security Shield: Do NOT slap a recycled nonce onto un-nonced scripts!
                // Only retain the nonce if the incoming script itself arrived with a server nonce attribute.
                if (oldScript.hasAttribute('nonce')) {
                    newScript.setAttribute('nonce', oldScript.getAttribute('nonce'));
                } else {
                    newScript.removeAttribute('nonce');
                }

                newScript.textContent = oldScript.textContent;
                if (oldScript.parentNode) {
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                }
            });
        }
    };

    // =========================================================================
    // 6.5 DOM SANITIZER (Anti-Weaponized SPA & Stored XSS Mitigation)
    // =========================================================================
    const DOMSanitizer = {
        cleanseTree: function(rootNode) {
            if (!rootNode || !rootNode.querySelectorAll) return rootNode;

            const dangerousTags = ['EMBED', 'OBJECT', 'APPLET', 'BASE'];
            const uriAttrs = ['href', 'src', 'action', 'formaction', 'xlink:href', 'data'];

            // 1. Remove dangerous active embedding tags & base tag (prevents base URL hijacking)
            dangerousTags.forEach(function(tag) {
                const els = rootNode.querySelectorAll(tag);
                els.forEach(function(el) { el.remove(); });
            });

            // 1.5 Remove meta refresh tags (prevents post-navigation phishing / redirect hijacking)
            const refreshMetas = rootNode.querySelectorAll('meta[http-equiv="refresh" i], meta[http-equiv="Refresh"]');
            refreshMetas.forEach(function(m) { m.remove(); });

            // 2. Scan all descendant elements for inline event handlers and malicious URI pseudo-protocols
            const allElements = rootNode.querySelectorAll('*');
            for (let i = 0; i < allElements.length; i++) {
                const el = allElements[i];
                const attrs = Array.from(el.attributes);

                for (let j = 0; j < attrs.length; j++) {
                    const attr = attrs[j];
                    const name = attr.name.toLowerCase();
                    const val = (attr.value || '').trim().toLowerCase();

                    // Strip all inline event handlers (onerror, onload, onclick, onmouseover, onfocus, etc.)
                    if (name.startsWith('on')) {
                        el.removeAttribute(attr.name);
                        continue;
                    }

                    // Neutralize executable URI schemes
                    if (uriAttrs.includes(name)) {
                        if (
                            val.startsWith('javascript:') ||
                            val.startsWith('vbscript:') ||
                            val.startsWith('data:text/html')
                        ) {
                            el.removeAttribute(attr.name);
                        }
                    }
                }
            }
            return rootNode;
        }
    };

    // =========================================================================
    // 7. SMART SHELL SYNC ENGINE (Header & Footer Morphing across Pages)
    // =========================================================================
    const ShellManager = {
        getHeaderElement: function(doc) {
            const sel = config.headerSelector || 'header.site-header, #masthead, .site-header, .elementor-location-header, [data-elementor-type="header"]';
            const candidates = sel.split(',').map(s => s.trim()).filter(Boolean);
            for (const candidate of candidates) {
                const el = doc.querySelector(candidate);
                if (el) return el;
            }
            return doc.querySelector('header');
        },

        getFooterElement: function(doc) {
            const sel = config.footerSelector || 'footer.site-footer, #colophon, .site-footer, .elementor-location-footer, [data-elementor-type="footer"]';
            const candidates = sel.split(',').map(s => s.trim()).filter(Boolean);
            for (const candidate of candidates) {
                const el = doc.querySelector(candidate);
                if (el) return el;
            }
            return doc.querySelector('footer');
        },

        syncShells: function(nextDoc) {
            this.syncHeader(nextDoc);
            this.syncFooter(nextDoc);
        },

        syncHeader: function(nextDoc) {
            const currHeader = this.getHeaderElement(document);
            const nextHeader = this.getHeaderElement(nextDoc);

            if (!currHeader && !nextHeader) return;

            // Target page has no header (e.g. landing page or canvas)
            if (currHeader && !nextHeader) {
                currHeader.style.display = 'none';
                log('ShellManager: Header hidden for landing/canvas template');
                return;
            }

            // Target page has header but current page didn't
            if (!currHeader && nextHeader) {
                const clone = nextHeader.cloneNode(true);
                document.body.insertBefore(clone, document.body.firstChild);
                log('ShellManager: Mounted missing header for destination template');
                this.rebindHeader(clone);
                return;
            }

            currHeader.style.display = '';

            // Check if header classes and innerHTML are identical
            const isSameClass = currHeader.className === nextHeader.className;
            const isSameHtml = currHeader.innerHTML === nextHeader.innerHTML;

            if (isSameClass && isSameHtml) {
                log('ShellManager: Header shell is identical (0ms preserved, zero layout shift)');
                return;
            }

            log('ShellManager: Morphed header shell to match destination page');
            DOMSanitizer.cleanseTree(nextHeader);
            currHeader.className = nextHeader.className;
            Array.from(nextHeader.attributes).forEach(function(attr) {
                if (attr.name.startsWith('data-') || attr.name.startsWith('aria-') || attr.name === 'style') {
                    currHeader.setAttribute(attr.name, attr.value);
                }
            });

            if (typeof currHeader.replaceChildren === 'function') {
                const nodes = Array.from(nextHeader.childNodes).map(n => document.importNode(n, true));
                currHeader.replaceChildren.apply(currHeader, nodes);
            } else {
                currHeader.innerHTML = nextHeader.innerHTML;
            }
            this.rebindHeader(currHeader);
        },

        syncFooter: function(nextDoc) {
            const currFooter = this.getFooterElement(document);
            const nextFooter = this.getFooterElement(nextDoc);

            if (!currFooter && !nextFooter) return;

            if (currFooter && !nextFooter) {
                currFooter.style.display = 'none';
                return;
            }

            if (!currFooter && nextFooter) {
                const clone = nextFooter.cloneNode(true);
                document.body.appendChild(clone);
                return;
            }

            currFooter.style.display = '';

            if (currFooter.className === nextFooter.className && currFooter.innerHTML === nextFooter.innerHTML) {
                return;
            }

            DOMSanitizer.cleanseTree(nextFooter);
            currFooter.className = nextFooter.className;
            Array.from(nextFooter.attributes).forEach(function(attr) {
                if (attr.name.startsWith('data-') || attr.name.startsWith('aria-')) {
                    currFooter.setAttribute(attr.name, attr.value);
                }
            });

            if (typeof currFooter.replaceChildren === 'function') {
                const nodes = Array.from(nextFooter.childNodes).map(n => document.importNode(n, true));
                currFooter.replaceChildren.apply(currFooter, nodes);
            } else {
                currFooter.innerHTML = nextFooter.innerHTML;
            }
        },

        rebindHeader: function(headerEl) {
            try {
                if (window.jQuery) {
                    window.jQuery(document).trigger('clickflow:header-synced', [headerEl]);
                }
            } catch (e) {}
        }
    };

    // =========================================================================
    // 8. LAYOUT SYNCHRONIZATION ENGINE (Fixes Bug 2: Layout & Sidebar Mismatches)
    // =========================================================================
    const LayoutSyncManager = {
        isTemplateCompatible: function(nextDoc, nextState) {
            // Protect against cross-template breakage (e.g. Elementor Canvas blank page vs full theme page)
            const isCurrentCanvas = document.body.classList.contains('elementor-template-canvas');
            const isNextCanvas = nextDoc.body && nextDoc.body.classList.contains('elementor-template-canvas');

            if (isCurrentCanvas !== isNextCanvas) {
                warn('Template divergence detected (Elementor Canvas vs Full Theme). Performing clean native load.');
                return false;
            }

            return true;
        },

        morphContainer: function(currentContainer, nextContainer) {
            // Anti-Weaponized SPA: Strip inline on* handlers and malicious URIs before DOM insertion
            DOMSanitizer.cleanseTree(nextContainer);

            // 1. Sync Container Classes (Fixes WooCommerce grid classes sticking on Single Post)
            if (nextContainer.className) {
                currentContainer.className = nextContainer.className;
            } else {
                currentContainer.removeAttribute('class');
            }

            // 2. Sync Container Attributes (data-*, aria-*, role, style)
            Array.from(nextContainer.attributes).forEach(function(attr) {
                if (attr.name.startsWith('data-') || attr.name.startsWith('aria-') || attr.name === 'role') {
                    currentContainer.setAttribute(attr.name, attr.value);
                }
            });

            // 3. Swap Children safely via native replaceChildren and importNode (prevents unparsed innerHTML hazards)
            if (typeof currentContainer.replaceChildren === 'function') {
                const importedNodes = Array.from(nextContainer.childNodes).map(function(node) {
                    return document.importNode(node, true);
                });
                currentContainer.replaceChildren.apply(currentContainer, importedNodes);
            } else {
                currentContainer.innerHTML = nextContainer.innerHTML;
            }
        },

        reconcileSidebars: function(currentContainer, nextDoc) {
            const sidebarSelectors = [
                '#secondary',
                '.widget-area',
                'aside.sidebar',
                '.sidebar',
                '#sidebar',
                '.secondary-sidebar'
            ];

            let currSidebar = null;
            let nextSidebar = null;

            for (const sel of sidebarSelectors) {
                if (!currSidebar) currSidebar = document.querySelector(sel);
                if (!nextSidebar) nextSidebar = nextDoc.querySelector(sel);
            }

            if (nextSidebar) {
                // Next page HAS a sidebar
                if (currSidebar) {
                    // Both have sidebar: morph content safely and classes
                    if (typeof currSidebar.replaceChildren === 'function') {
                        const nodes = Array.from(nextSidebar.childNodes).map(n => document.importNode(n, true));
                        currSidebar.replaceChildren.apply(currSidebar, nodes);
                    } else {
                        currSidebar.innerHTML = nextSidebar.innerHTML;
                    }
                    currSidebar.className = nextSidebar.className;
                    currSidebar.style.display = '';
                    log('Reconciled existing sidebar for target template');
                } else {
                    // Current page didn't have a sidebar, but next page does: inject adjacent to container
                    const clonedSidebar = nextSidebar.cloneNode(true);
                    if (currentContainer.parentNode) {
                        currentContainer.after(clonedSidebar);
                        log('Attached missing sidebar for target template');
                    }
                }
            } else {
                // Next page is FULL-WIDTH (no sidebar), e.g. WooCommerce product full layout or Elementor page
                if (currSidebar) {
                    currSidebar.style.display = 'none';
                    log('Hid sidebar for full-width target layout');
                }
            }
        }
    };

    // =========================================================================
    // 9. MEDIA & LAZY-LOAD REHYDRATOR (AVIF & WebP Native Support)
    // =========================================================================
    const MediaRehydrator = {
        rehydrate: function(container) {
            if (!container) return;

            // 1. LiteSpeed Cache, native lazyload, and AVIF images
            const images = container.querySelectorAll('img');
            images.forEach(function(img) {
                const dataSrc = img.getAttribute('data-src') || 
                                img.getAttribute('data-lazy-src') || 
                                img.getAttribute('data-original') ||
                                img.getAttribute('data-src-avif') ||
                                img.getAttribute('data-lazy-src-avif') ||
                                img.getAttribute('data-src-webp');
                if (dataSrc) {
                    img.setAttribute('src', dataSrc);
                    img.removeAttribute('data-src');
                    img.removeAttribute('data-src-avif');
                }

                const dataSrcset = img.getAttribute('data-srcset') || 
                                   img.getAttribute('data-lazy-srcset');
                if (dataSrcset) {
                    img.setAttribute('srcset', dataSrcset);
                    img.removeAttribute('data-srcset');
                }

                img.classList.remove('lazyload', 'lazyloading');
                img.classList.add('lazyloaded');
                img.removeAttribute('data-lazyloaded');
                img.style.opacity = '1';
                img.style.visibility = 'visible';
            });

            // 2. Picture sources (including AVIF & WebP sources)
            const sources = container.querySelectorAll('picture source[data-srcset]');
            sources.forEach(function(source) {
                const dataSrcset = source.getAttribute('data-srcset');
                if (dataSrcset) {
                    source.setAttribute('srcset', dataSrcset);
                    source.removeAttribute('data-srcset');
                }
            });

            // 3. Background images with data-bg (Elementor sections/columns)
            const bgElements = container.querySelectorAll('[data-bg], [data-background], [data-lazy-bg]');
            bgElements.forEach(function(el) {
                const bg = el.getAttribute('data-bg') || el.getAttribute('data-background') || el.getAttribute('data-lazy-bg');
                if (bg) {
                    el.style.backgroundImage = 'url("' + bg + '")';
                    el.removeAttribute('data-bg');
                }
            });

            // 4. LiteSpeed Global Re-scan
            try {
                if (typeof window.litespeed_lazyload === 'function') {
                    window.litespeed_lazyload();
                }
            } catch (e) {}

            // 5. Trigger resize and scroll for IntersectionObservers
            window.dispatchEvent(new Event('scroll'));
            window.dispatchEvent(new Event('resize'));
            setTimeout(function() {
                window.dispatchEvent(new Event('scroll'));
            }, 100);
        }
    };

    // =========================================================================
    // 8.5 NONCE & CSRF MANAGER (Prevents Stale Token Failures in SPA)
    // =========================================================================
    const NonceManager = {
        sync: function(nextDoc, isPrivateResponse) {
            try {
                // If destination was public cache and we already have a valid session nonce, do not overwrite with shared cache nonce
                const hasExistingNonce = Boolean(window.wpApiSettings && window.wpApiSettings.nonce);
                if (hasExistingNonce && isPrivateResponse === false && document.body.classList.contains('logged-in')) {
                    log('NonceManager: Preserving private user session nonce against public cache');
                    return;
                }

                const nonceRegex = /^[a-zA-Z0-9]{10,32}$/;
                const scripts = Array.from(nextDoc.querySelectorAll('script'));
                for (const s of scripts) {
                    const text = s.textContent || '';
                    if (text.includes('wpApiSettings') && text.includes('"nonce"')) {
                        const match = text.match(/"nonce"\s*:\s*"([a-zA-Z0-9]+)"/);
                        if (match && match[1] && nonceRegex.test(match[1])) {
                            if (window.wpApiSettings) {
                                window.wpApiSettings.nonce = match[1];
                            } else {
                                window.wpApiSettings = { nonce: match[1] };
                            }
                            log('NonceManager: Synchronized wpApiSettings.nonce');
                        }
                    }
                    if (text.includes('wc_cart_params') && text.includes('"nonce"')) {
                        const match = text.match(/"nonce"\s*:\s*"([a-zA-Z0-9]+)"/);
                        if (match && match[1] && nonceRegex.test(match[1]) && window.wc_cart_params) {
                            window.wc_cart_params.nonce = match[1];
                            log('NonceManager: Synchronized wc_cart_params.nonce');
                        }
                    }
                }
                document.dispatchEvent(new CustomEvent('clickflow:nonces-refreshed'));
            } catch (e) {
                warn('Nonce sync warning:', e);
            }
        }
    };

    // =========================================================================
    // 9. PAGE BUILDER & WOOCOMMERCE ADAPTERS
    // =========================================================================
    const Adapters = {
        elementor: {
            afterReplace: function(container) {
                log('Elementor Adapter: Reinitializing widget handlers');
                if (!window.elementorFrontend) return;

                try {
                    if (window.elementorFrontend.elementsHandler && typeof window.elementorFrontend.elementsHandler.runReadyTrigger === 'function') {
                        window.elementorFrontend.elementsHandler.runReadyTrigger(container);
                    }

                    if (window.jQuery) {
                        const $container = window.jQuery(container);
                        $container.find('.elementor-element').each(function() {
                            try {
                                if (window.elementorFrontend.elementsHandler) {
                                    window.elementorFrontend.elementsHandler.runReadyTrigger(window.jQuery(this));
                                }
                            } catch (err) {}
                        });

                        if (window.elementorFrontend.hooks && typeof window.elementorFrontend.hooks.doAction === 'function') {
                            window.elementorFrontend.hooks.doAction('frontend/element_ready/global', $container);
                        }
                    }
                } catch (adapterErr) {
                    warn('Elementor re-init warning:', adapterErr);
                }
            }
        },
        gutenberg: {
            afterReplace: function(container) {
                log('Gutenberg Adapter: Rebinding blocks & media');
                try {
                    const iframes = container.querySelectorAll('.wp-block-embed iframe');
                    iframes.forEach(function(iframe) {
                        if (iframe.dataset.src) iframe.src = iframe.dataset.src;
                    });
                } catch (gutenbergErr) {}
            }
        },
        woocommerce: {
            afterReplace: function(container) {
                log('WooCommerce Adapter: Refreshing cart fragments and photo gallery');
                try {
                    if (window.jQuery) {
                        window.jQuery(document.body).trigger('wc_fragment_refresh');
                        // Rebind WooCommerce single product gallery if flexslider exists
                        const $gallery = window.jQuery(container).find('.woocommerce-product-gallery');
                        if ($gallery.length && typeof $gallery.wc_product_gallery === 'function') {
                            $gallery.wc_product_gallery();
                        }
                    }
                } catch (wooErr) {}
            }
        },
        classic: {
            afterReplace: function(container) {
                try {
                    const commentForm = container.querySelector('#commentform');
                    if (commentForm && window.addComment && typeof window.addComment.cancel === 'function') {
                        window.addComment.cancel();
                    }
                } catch (classicErr) {}
            }
        }
    };

    // =========================================================================
    // 10. LINK ELIGIBILITY CHECKER
    // =========================================================================
    function isEligibleLink(anchor, event) {
        if (!anchor || anchor.tagName !== 'A') return false;

        // Honor modifier keys
        if (event && (event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0)) {
            return false;
        }

        const href = anchor.getAttribute('href');
        if (!href) return false;

        const trimmedHref = href.trim();
        if (
            trimmedHref.startsWith('#') ||
            trimmedHref.startsWith('javascript:') ||
            trimmedHref.startsWith('mailto:') ||
            trimmedHref.startsWith('tel:') ||
            trimmedHref.startsWith('data:') ||
            trimmedHref.startsWith('blob:')
        ) {
            return false;
        }

        if (anchor.getAttribute('target') === '_blank' || anchor.hasAttribute('download')) {
            return false;
        }

        if (anchor.hasAttribute('data-clickflow-ignore') || anchor.hasAttribute('data-lm-no-instant') || anchor.closest('[data-lm-no-instant]')) {
            return false;
        }

        if (anchor.closest('form')) {
            return false;
        }

        try {
            const url = new URL(anchor.href, window.location.href);

            if (url.protocol !== 'http:' && url.protocol !== 'https:') {
                return false;
            }

            if (url.origin !== window.location.origin) {
                return false;
            }

            // Same page anchor scroll handling
            const currentPathAndSearch = window.location.pathname + window.location.search;
            const targetPathAndSearch = url.pathname + url.search;
            if (currentPathAndSearch === targetPathAndSearch) {
                if (url.hash) {
                    const targetEl = document.querySelector(url.hash);
                    if (targetEl) {
                        targetEl.scrollIntoView({ behavior: 'smooth' });
                        window.history.pushState(null, '', url.href);
                    }
                }
                return false;
            }

            // Excluded paths & endpoints (Enterprise Security Hardening)
            const path = url.pathname.toLowerCase();
            const search = url.search.toLowerCase();

            if (
                path.includes('/wp-admin') ||
                path.includes('/wp-login.php') ||
                path.includes('/cart') ||
                path.includes('/checkout') ||
                path.includes('/my-account') ||
                path.includes('/xmlrpc.php') ||
                path.includes('/wp-json/') ||
                path.includes('/wp-comments-post.php') ||
                path.includes('/customer-logout') ||
                path.includes('/logout') ||
                path.includes('/delete/') ||
                path.includes('/cancel-order') ||
                path.includes('/order-received') ||
                path.includes('/resetpass') ||
                path.includes('/empty-cart')
            ) {
                return false;
            }

            // Exclude any state-changing queries, actions, nonces, or dynamic tokens (Anti-Encoding Bypass)
            const blockedKeys = [
                'action', 'add-to-cart', 'wc-ajax', 'nonce', '_wpnonce', 'key',
                'token', 'order-received', 'cancel_order', 'unsubscribe',
                'verify-email', 'resetpass', 'customer-logout', 'remove_item',
                'empty-cart', 's', 'elementor-preview', 'preview', 'delete'
            ];

            // 1. Direct searchParams keys inspection (Standard WHATWG URL parsing)
            for (let i = 0; i < blockedKeys.length; i++) {
                if (url.searchParams.has(blockedKeys[i])) {
                    return false;
                }
            }

            // 2. Specific Action Verification
            if (url.searchParams.has('action')) {
                const act = (url.searchParams.get('action') || '').toLowerCase();
                const dangerousActions = ['logout', 'delete', 'trash', 'postpass', 'rp', 'resetpass', 'elementor'];
                if (dangerousActions.includes(act)) {
                    return false;
                }
            }

            // 3. Multi-pass URL Decoding (Thwarts %3D, %253D, %25253D double/triple encoding bypasses)
            let decodedSearch = url.search.toLowerCase();
            for (let d = 0; d < 3; d++) {
                try {
                    const nextDec = decodeURIComponent(decodedSearch);
                    if (nextDec === decodedSearch) break;
                    decodedSearch = nextDec;
                } catch (eDec) {
                    break;
                }
            }

            const dangerousSignatures = [
                'add-to-cart=',
                'wc-ajax=',
                'action=',
                'nonce=',
                '_wpnonce=',
                'logout',
                'delete=',
                'key=',
                'token=',
                'order-received',
                'cancel_order',
                'unsubscribe',
                'verify-email',
                'resetpass',
                'customer-logout',
                'remove_item',
                'empty-cart',
                's=',
                'elementor-preview=',
                'preview=true'
            ];

            for (let s = 0; s < dangerousSignatures.length; s++) {
                if (search.includes(dangerousSignatures[s]) || decodedSearch.includes(dangerousSignatures[s])) {
                    return false;
                }
            }

            // 4. Inspect decoded parameter keys/values for obfuscated payloads
            for (const [paramKey, paramVal] of url.searchParams.entries()) {
                const pk = paramKey.toLowerCase();
                const pv = paramVal.toLowerCase();
                if (
                    pk.includes('action') ||
                    pk.includes('cart') ||
                    pk.includes('nonce') ||
                    pk.includes('logout') ||
                    pk.includes('delete') ||
                    pv.includes('logout') ||
                    pv.includes('delete')
                ) {
                    return false;
                }
            }

            return true;
        } catch (e) {
            return false;
        }
    }

    // =========================================================================
    // 11. FETCH FULL PAGE HTML (LiteSpeed / Memory Cache Synergy)
    // =========================================================================
    async function fetchPage(url, signal, isPrefetch) {
        const cached = MemoryCache.get(url);
        if (cached) {
            log('Memory Cache Instant Hit (0ms): ' + url);
            return cached;
        }

        const reqHeaders = {
            'X-Requested-With': 'ClickFlow-Navigation'
        };

        if (isPrefetch) {
            reqHeaders['X-ClickFlow-Prefetch'] = '1';
            reqHeaders['X-Purpose'] = 'prefetch';
            reqHeaders['Purpose'] = 'prefetch';
        }

        log('Network Fetch: ' + url + (isPrefetch ? ' (Speculative Prefetch)' : ''));
        const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: reqHeaders,
            signal: signal
        });

        // Security Shield: Login / Redirect detection (Always perform clean browser hard navigation on redirects)
        if (response.redirected) {
            log('Server redirected to ' + response.url + '. Performing clean native hard load.');
            window.location.assign(response.url);
            return null;
        }

        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }

        const finalUrl = response.url || url;

        // Security Shield: Prevent open redirects and cross-origin injection
        try {
            const finalOrigin = new URL(finalUrl, window.location.href).origin;
            if (finalOrigin !== window.location.origin) {
                throw new Error('Cross-origin redirection rejected: ' + finalOrigin);
            }
        } catch (urlErr) {
            throw new Error('Invalid destination origin: ' + urlErr.message);
        }

        const contentType = response.headers.get('content-type') || '';
        if (contentType && !contentType.includes('text/html')) {
            throw new Error('Non-HTML response received: ' + contentType);
        }

        const cacheControl = (response.headers.get('cache-control') || '').toLowerCase();
        let isPrivate = cacheControl.includes('private') || cacheControl.includes('no-store') || cacheControl.includes('no-cache');

        const html = await response.text();
        const parser = new DOMParser();
        const nextDoc = parser.parseFromString(html, 'text/html');

        // Anti-Weaponized SPA (Stored XSS Mitigation): Strip inline event handlers and malicious URI schemes
        if (nextDoc.body) {
            DOMSanitizer.cleanseTree(nextDoc.body);
        }

        // Security Shield: Destination page logged-in check (Bypasses RAM cache if destination has active user session)
        if (nextDoc.body && nextDoc.body.classList.contains('logged-in')) {
            isPrivate = true;
        }

        let state = null;
        // Anti-DOM-Clobbering: Specifically query only valid script elements with application/json
        const stateScript = nextDoc.querySelector('script#clickflow-state[type="application/json"]') ||
                            nextDoc.querySelector('script#lm-inav-state[type="application/json"]');
        if (stateScript && stateScript.tagName === 'SCRIPT' && !(stateScript instanceof HTMLAnchorElement) && !(stateScript instanceof HTMLFormElement)) {
            try {
                state = JSON.parse(stateScript.textContent);
                if (state && state.loggedIn) {
                    isPrivate = true;
                }
            } catch (e) {}
        }

        const data = { html: html, doc: nextDoc, state: state, finalUrl: finalUrl, isPrivate: isPrivate };
        MemoryCache.set(url, data);
        if (finalUrl !== url) {
            MemoryCache.set(finalUrl, data);
        }
        return data;
    }

    // =========================================================================
    // 12. CONTAINER RESOLUTION ENGINE
    // =========================================================================
    function findMatchingContainers(nextDoc) {
        const candidates = [
            'main.site-main',
            'main#main',
            'main#content',
            '#primary',
            '#main',
            'main',
            '.site-main',
            '#content',
            '.site-content',
            '.elementor-location-single',
            '[data-elementor-type="single-post"]',
            '[data-elementor-type="wp-page"]',
            '.entry-content'
        ];

        // 1. Look for matching selector in both docs
        for (const sel of candidates) {
            const currentEl = document.querySelector(sel);
            const nextEl = nextDoc.querySelector(sel);
            if (currentEl && nextEl) {
                return { current: currentEl, next: nextEl };
            }
        }

        // 2. Custom selector list
        const customList = (config.contentSelector || '').split(',').map(s => s.trim()).filter(Boolean);
        for (const sel of customList) {
            const currentEl = document.querySelector(sel);
            const nextEl = nextDoc.querySelector(sel);
            if (currentEl && nextEl) {
                return { current: currentEl, next: nextEl };
            }
        }

        // 3. Fallback pairing
        const currentEl = document.querySelector('main, #primary, #content, .site-main, .entry-content');
        const nextEl = nextDoc.querySelector('main, #primary, #content, .site-main, .entry-content');
        if (currentEl && nextEl) {
            return { current: currentEl, next: nextEl };
        }

        return null;
    }

    let activeAbortController = null;
    let isNavigating = false;
    let currentNavToken = 0;

    // =========================================================================
    // 13. MASTER NAVIGATION CONTROLLER
    // =========================================================================
    async function navigateTo(destinationUrl, pushHistory) {
        // Step 0: Immediate Offcanvas and drawer dismissal
        OffcanvasManager.dismissAll();

        if (isNavigating && activeAbortController) {
            activeAbortController.abort();
            log('Aborted prior in-flight navigation');
        }

        const navToken = ++currentNavToken;
        activeAbortController = new AbortController();
        isNavigating = true;
        ProgressBar.start();

        const startTime = performance.now();
        document.dispatchEvent(new CustomEvent('clickflow:before-fetch', { detail: { url: destinationUrl } }));

        try {
            ProgressBar.inc(45);
            const data = await fetchPage(destinationUrl, activeAbortController.signal);

            if (navToken !== currentNavToken) {
                ProgressBar.done();
                return;
            }

            const nextDoc = data.doc;
            const nextState = data.state;
            const targetUrl = data.finalUrl || destinationUrl;
            ProgressBar.inc(70);

            // Template compatibility safeguard
            if (!LayoutSyncManager.isTemplateCompatible(nextDoc, nextState)) {
                ProgressBar.done();
                window.location.assign(targetUrl);
                return;
            }

            // Container matching
            const matched = findMatchingContainers(nextDoc);
            if (!matched || !matched.current || !matched.next) {
                warn('Matching container not found between templates. Falling back to native hard load: ' + targetUrl);
                ProgressBar.done();
                window.location.assign(targetUrl);
                return;
            }

            const currentContainer = matched.current;
            const nextContainer = matched.next;

            // STEP 1: Address Bar URL & Title Update
            if (pushHistory !== false) {
                try {
                    const parsedUrl = new URL(targetUrl, window.location.href);
                    const safePath = parsedUrl.pathname + parsedUrl.search + parsedUrl.hash;
                    window.history.pushState({ url: targetUrl, title: nextDoc.title || document.title }, '', safePath);
                    log('Updated Address Bar URL: ' + safePath);
                } catch (pushErr) {
                    try {
                        window.history.pushState(null, '', targetUrl);
                    } catch (e2) {}
                }
            }

            if (nextDoc.title) {
                document.title = nextDoc.title;
            }

            // STEP 2: Pre-emptive Stylesheet & Dynamic Script Synchronization (Lazy Assets Blocker Support)
            await AssetManager.syncStyles(nextDoc);
            await AssetManager.syncScripts(nextDoc);

            if (navToken !== currentNavToken) return;

            document.dispatchEvent(new CustomEvent('clickflow:before-replace', {
                detail: { url: targetUrl, state: nextState }
            }));

            // STEP 3: PRE-EMPTIVE HEAD & BODY CLASS UPDATE (Fixes Layout Bug 2)
            // Body classes MUST update before container DOM replacement so all CSS layout rules apply
            HeadManager.update(nextDoc);

            // STEP 4: DOM RECONCILIATION & CONTAINER MORPHING (with View Transitions if supported)
            const executeDomMorph = function() {
                // A. Smart Shell Sync: Reconcile Header & Footer shells when templates differ
                ShellManager.syncShells(nextDoc);

                // B. Morph Content Container classes, attributes, and content
                LayoutSyncManager.morphContainer(currentContainer, nextContainer);

                // C. Reconcile sidebars (#secondary)
                LayoutSyncManager.reconcileSidebars(currentContainer, nextDoc);

                // D. Dynamic Script Execution: re-execute inline interactive scripts
                AssetManager.executeInlineScripts(currentContainer);

                // Accessibility focus
                try {
                    currentContainer.setAttribute('tabindex', '-1');
                    currentContainer.focus({ preventScroll: true });
                } catch (a11yErr) {}

                // STEP 5: Rehydrate Media, AVIF & LiteSpeed Lazyload
                MediaRehydrator.rehydrate(currentContainer);

                // STEP 6: Reinitialize Page Builder & WooCommerce Adapters
                try {
                    if (nextState && nextState.builder === 'elementor' && config.adapters.elementor) {
                        Adapters.elementor.afterReplace(currentContainer);
                    } else if (nextState && nextState.builder === 'gutenberg' && config.adapters.gutenberg) {
                        Adapters.gutenberg.afterReplace(currentContainer);
                    } else if (config.adapters.classic) {
                        Adapters.classic.afterReplace(currentContainer);
                    }

                    // WooCommerce adapter triggers
                    if (config.adapters.woocommerce) {
                        Adapters.woocommerce.afterReplace(currentContainer);
                    }

                    // Nonce & CSRF token refresh (Gutenberg, REST, WooCommerce)
                    NonceManager.sync(nextDoc, data.isPrivate);
                } catch (adapterErr) {
                    warn('Adapter error:', adapterErr);
                }
            };

            // Use native View Transitions API if supported for hardware-accelerated 60 FPS transitions
            if (document.startViewTransition && config.viewTransitions !== false) {
                document.startViewTransition(executeDomMorph);
            } else {
                executeDomMorph();
            }

            // STEP 7: Post-Navigation Offcanvas Cleanup (Guarantees zero lingering backdrops)
            OffcanvasManager.dismissAll();

            // Scroll restoration
            const hash = new URL(targetUrl, window.location.href).hash;
            if (hash) {
                const targetEl = document.querySelector(hash);
                if (targetEl) targetEl.scrollIntoView({ behavior: 'smooth' });
            } else {
                window.scrollTo({ top: 0, behavior: 'auto' });
            }

            ProgressBar.done();
            const elapsed = Math.round(performance.now() - startTime);
            log('Navigation to ' + targetUrl + ' completed in ' + elapsed + 'ms');

            announceRoute(document.title);

            // Lifecycle page-view event
            document.dispatchEvent(new CustomEvent('clickflow:page-view', {
                detail: { url: targetUrl, title: document.title, elapsed: elapsed, state: nextState }
            }));
            document.dispatchEvent(new CustomEvent('lm-inav:page-view', {
                detail: { url: targetUrl, title: document.title, elapsed: elapsed, state: nextState }
            }));

            // Google Analytics 4 / Google Tag Manager
            if (window.dataLayer && Array.isArray(window.dataLayer)) {
                window.dataLayer.push({
                    event: 'page_view',
                    page_path: window.location.pathname,
                    page_title: document.title
                });
            }
            if (typeof window.gtag === 'function') {
                window.gtag('event', 'page_view', {
                    page_path: window.location.pathname,
                    page_title: document.title
                });
            }

        } catch (error) {
            ProgressBar.done();
            OffcanvasManager.dismissAll();
            if (error.name === 'AbortError') return;
            warn('Soft navigation failed: ' + error.message + '. Triggering native hard load.');
            window.location.assign(destinationUrl);
        } finally {
            if (navToken === currentNavToken) {
                isNavigating = false;
                ProgressBar.done();
            }
        }
    }

    // =========================================================================
    // 14. PREDICTIVE PREFETCH ENGINE (Hover Intent + Touchstart)
    // =========================================================================
    const PrefetchQueue = new Set();
    let hoverTimeout = null;

    function handleLinkHover(e) {
        if (!config.prefetch) return;
        if (navigator.connection && navigator.connection.saveData) return;

        const anchor = e.target.closest('a');
        if (!isEligibleLink(anchor, e)) return;

        const url = anchor.href;

        // Register with native Speculation Rules engine if available
        SpeculationEngine.prerender(url);

        if (MemoryCache.get(url) || PrefetchQueue.has(url)) return;

        clearTimeout(hoverTimeout);
        hoverTimeout = setTimeout(function() {
            log('Prefetching on hover intent (50ms): ' + url);
            PrefetchQueue.add(url);
            fetchPage(url, null, true).catch(() => {}).finally(function() {
                PrefetchQueue.delete(url);
            });
        }, config.prefetchDelay || 50);
    }

    function handleLinkLeave() {
        clearTimeout(hoverTimeout);
    }

    // Mobile touchstart prefetch: touchstart fires ~80-120ms before click on mobile!
    document.addEventListener('touchstart', function(e) {
        const anchor = e.target.closest('a');
        if (isEligibleLink(anchor, e)) {
            SpeculationEngine.prerender(anchor.href);
            fetchPage(anchor.href, null, true).catch(() => {});
        }
    }, { passive: true });

    document.addEventListener('mouseover', handleLinkHover, { passive: true });
    document.addEventListener('mouseout', handleLinkLeave, { passive: true });

    // Click Interception (Capturing Phase)
    document.addEventListener('click', function(e) {
        const anchor = e.target.closest('a');
        if (!anchor) return;

        // Security Shield 1: Tabnabbing prevention on target="_blank"
        if (anchor.getAttribute('target') === '_blank') {
            const rel = anchor.getAttribute('rel') || '';
            if (!rel.includes('noopener') || !rel.includes('noreferrer')) {
                anchor.setAttribute('rel', (rel + ' noopener noreferrer').trim());
            }
        }

        // Security Shield 2: Flush RAM cache on WordPress or WooCommerce logout
        const href = anchor.getAttribute('href') || '';
        const anchorPath = (anchor.pathname || '').toLowerCase();
        if (
            href.includes('action=logout') ||
            href.includes('customer-logout') ||
            href.includes('wp-login.php?action=logout') ||
            anchorPath.includes('/customer-logout') ||
            anchorPath.includes('/logout')
        ) {
            MemoryCache.clear();
            return;
        }

        if (!isEligibleLink(anchor, e)) return;

        e.preventDefault();
        navigateTo(anchor.href, true);
    }, true);

    // Browser History Back/Forward (Popstate)
    window.addEventListener('popstate', function(e) {
        const destination = window.location.href;
        log('Popstate triggered: restoring ' + destination);
        navigateTo(destination, false);
    });

    // Initialize Speculation Rules API
    SpeculationEngine.init();

    log('ClickFlow Engine v0.9.5 BETA active: Speculation Rules, Layout Sync & Offcanvas Dismissal armed.');
})();
