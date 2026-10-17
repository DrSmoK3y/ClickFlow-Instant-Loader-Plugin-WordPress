# ClickFlow Instant Loader ⚡
### Enterprise 0ms Instant Single Page Application (SPA) Engine for WordPress
[![WordPress](https://img.shields.io/badge/WordPress-6.0+-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPLv2%20or%20later-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Speed](https://img.shields.io/badge/Perceived%20Transition-0ms-brightgreen.svg)]()
[![HTTP/3](https://img.shields.io/badge/Protocol-HTTP%2F3%20QUIC-orange.svg)]()
[![Status](https://img.shields.io/badge/Release-v0.9.5%20BETA-blue)]()

> **ClickFlow Instant Loader** turns any WordPress website or WooCommerce store into a blazing-fast, 0ms Single Page Application (SPA). By combining speculative 65ms hover-intent prefetching, native in-memory DOM morphing, LiteSpeed server-cache integration, and strict enterprise security shields, ClickFlow delivers native app-like transitions with **zero white-screen flickering**.

---

## 📑 Table of Contents
1. [The Problem ClickFlow Solves](#the-problem-clickflow-solves)
2. [The 4-Pillar Speed Ecosystem](#the-4-pillar-speed-ecosystem)
3. [How ClickFlow Works (6-Step Lifecycle)](#how-clickflow-works-6-step-lifecycle)
4. [Enterprise Security & Audit Hardening](#enterprise-security--audit-hardening)
5. [Page Builder & Theme Compatibility](#page-builder--theme-compatibility)
6. [Required & Recommended Plugins](#required--recommended-plugins)
7. [Installation & Setup](#installation--setup)
8. [Configuration & 1-Click Presets](#configuration--1-click-presets)
9. [Verification & Benchmarks](#verification--benchmarks)
10. [Repository Structure](#repository-structure)
11. [License & Credits](#license--credits)

---

## The Problem ClickFlow Solves

Traditional WordPress websites suffer from severe page transition latency on every user click:
1. **White Screen Flicker:** The browser unloads the current document, clearing the viewport and flashing blank white for 800ms–2,500ms.
2. **CPU & Database Waste:** The server boots the full PHP core, executes 50+ MySQL queries, and compiles the template from scratch.
3. **Redundant Asset Downloads:** Megabytes of CSS stylesheets, web fonts, and JavaScript bundles (jQuery, React, analytics) are repeatedly re-downloaded, parsed, and executed.
4. **Mobile TCP Latency:** Packet drops on cellular 4G/5G connections stall traditional HTTP handshakes.

### The ClickFlow Solution
ClickFlow keeps the browser window alive. It intercepts internal link clicks, fetches destination HTML speculatively into browser RAM during cursor hover (~65ms), and swaps only the main content container via native `replaceChildren()` and `document.importNode()`. 

The result: **0ms perceived navigation**, preserved browser state, and 100/100 Core Web Vitals.

---

## The 4-Pillar Speed Ecosystem

ClickFlow is designed as part of a synergistic four-pillar speed architecture:

```
┌────────────────────────────────────────────────────────────────────────┐
│                   PILLAR 1: CLICKFLOW INSTANT LOADER                   │
│                      (Browser RAM & Router Engine)                     │
│  - 65ms Speculative Hover-Intent Prefetching in RAM                    │
│  - 0ms Native DOM Morphing with replaceChildren()                      │
│  - Smart Shell Synchronization (Header/Footer morphing)                │
│  - Dynamic Nonce & CSRF Synchronization                                │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ (HTTP/3 0-RTT UDP + Early Hints 103)
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                      PILLAR 2: LITESPEED CACHE                         │
│                    (Server RAM Static HTML Cache)                      │
│  - Static pre-rendered HTML served in < 20ms TTFB                      │
│  - Zero PHP/MySQL execution on cached prefetch hits                    │
│  - HTTP/3 Alt-Svc & RFC 8297 Early Hints headers                       │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ (Cold Cache / Dynamic Hits)
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│               PILLAR 3: INDEX WP MYSQL FOR SPEED                       │
│                   (Zero-Daemon Database Engine)                        │
│  - High-performance B-Tree composite database indexes                  │
│  - Accelerates wp_posts, wp_postmeta, and wp_options by 300% to 800%   │
│  - Solves shared hosting Redis connection refused issues               │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ (Payload Optimization)
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                 PILLAR 4: LM PAGE ASSETS CONTROL                       │
│                     (Dynamic Payload Blocker)                          │
│  - Strips unused CSS/JS from irrelevant URLs (e.g., CF7 on blog posts) │
│  - Reduces initial page weight from 3.5MB to < 350KB                   │
│  - ClickFlow AssetManager re-injects scripts on-demand                 │
└────────────────────────────────────────────────────────────────────────┘
```

| Layer | Plugin | Primary Role | Without This Plugin |
|---|---|---|---|
| **1. Client Router** | **ClickFlow Instant Loader** | 65ms hover prefetch, 0ms DOM morphing | Full page reload on every click, blank screen flash |
| **2. Server Cache** | **LiteSpeed Cache** | Serves static HTML from RAM in &lt;20ms TTFB | Server PHP boots up on every prefetch, causing 503 CPU throttling |
| **3. Database** | **Index WP MySQL For Speed** | Composite B-Tree indexes on core WP tables | Slow unindexed database queries lock MySQL during uncached requests |
| **4. Asset Control** | **LM Page Assets Control** | Unloads unused CSS/JS on non-relevant pages | Heavy 3MB+ initial payload destroys Mobile PageSpeed scores |

---

## How ClickFlow Works (6-Step Lifecycle)

### Step 1: 65ms Hover & Touch Intent Detection
Human eye-hand reaction time between cursor hover and mouse click is 200ms–400ms. ClickFlow registers passive `mouseover` and `touchstart` listeners. If the user hovers over an eligible internal link for **65ms**, a speculative background prefetch is initiated into browser RAM.

### Step 2: Multi-Layer Security Shield
Before prefetching, the URL is scrutinized:
- Client-side checks reject state-changing parameters (`?add-to-cart=`, `logout`, `delete`, `_wpnonce`, `key=`, `token=`).
- Server-side interceptor (`intercept_sensitive_prefetch()`) decodes query strings (handling multi-pass URL encoding like `%3D`) and responds with **HTTP 400 Bad Request** to prevent token burning or session tampering.

### Step 3: Sub-20ms HTML Delivery from LiteSpeed Cache
The prefetch request hits the web server. LiteSpeed serves the static pre-compiled HTML snapshot directly from server RAM in **&lt;20ms TTFB** without invoking the PHP interpreter or database.

### Step 4: DOMParser & DOMSanitizer In-Memory Cleansing
The response HTML is parsed safely in memory via `DOMParser().parseFromString()`. ClickFlow's `DOMSanitizer`:
- Strips all inline `on*` event handlers (e.g., `onload`, `onerror`).
- Neutralizes `<base>` tags to eliminate relative URL hijacking.
- Removes `<meta http-equiv="refresh">` and active `<embed>` / `<object>` elements to defend against Mutation XSS (mXSS).

### Step 5: Zero-Flicker Native DOM Swapping (0ms)
When the user clicks, ClickFlow replaces the container using native:
```javascript
const importedNodes = Array.from(nextContainer.childNodes)
    .map(node => document.importNode(node, true));
currentContainer.replaceChildren.apply(currentContainer, importedNodes);
window.history.pushState(null, '', targetUrl);
```
No window reload occurs. Web fonts, stylesheets, and global JavaScript libraries remain mounted in memory.

### Step 6: AssetManager & Nonce Rehydration
- **Style & Script Sync:** Any newly required CSS or JS handles (unblocked by LM Page Assets Control) are dynamically injected.
- **CSRF Nonce Sync:** `window.wpApiSettings.nonce` and WooCommerce cart tokens are updated to ensure subsequent AJAX requests never fail with 403 Forbidden.
- **Scroll & Analytics:** Scroll position is restored or reset to top; Google Analytics / Tag Manager pageview events are automatically dispatched.

---

## Enterprise Security & Audit Hardening

ClickFlow has undergone rigorous security audits against real-world penetration attack vectors:

1. **Zero Prefetch State Mutation:**
   Both client and server strictly forbid prefetching URLs containing sensitive action parameters. URLs with encoded delimiters (`/?action%3Dlogout`) are recursively decoded (up to 3 passes) and parsed using PHP `parse_str()` and JS `URL.searchParams`.
2. **Anti-DOM-Clobbering Protection:**
   Route state elements are accessed strictly via:
   ```javascript
   document.querySelector('script#clickflow-state[type="application/json"]')
   ```
   Preventing attackers from clobbering `document.getElementById` with injected `<a id="clickflow-state">` or `<form>` tags.
3. **RAM Cache Privacy Isolation:**
   Logged-in user pages (`document.body.classList.contains('logged-in')`) or responses with `Cache-Control: private, no-store` are never stored in browser memory cache. Logging out (`/customer-logout/` or `action=logout`) completely flushes client RAM cache.
4. **Exception Masking & Info Disclosure Defense:**
   All internal exceptions and database errors in auto-setup and Redis routines are logged to server `error_log()` with sanitized, generic messages sent to the client.

---

## Page Builder & Theme Compatibility

ClickFlow includes built-in adapters for all standard WordPress architectures:

- **Elementor:** Dynamically preserves post-specific stylesheets (`elementor-post-{id}.css`) and automatically invokes `elementorFrontend.elementsHandler.runReadyTrigger()` so carousels, accordions, and animations initialize seamlessly.
- **Gutenberg (Block Editor):** Re-runs block scripts and preserves layout alignment wrappers (`wp-block-*`, `alignfull`, `alignwide`).
- **Classic Editor & Custom Themes:** Universal CSS selector auto-detection supports Astra, Hello Elementor, GeneratePress, Kadence, Storefront, and custom roots (`#primary`, `#content`, `main`, `.site-main`).
- **WooCommerce:** Preserves cart fragments, triggers `wc_fragment_refresh`, isolates checkout/cart routes from prefetch mutations, and updates mini-cart badges dynamically.

---

## Required & Recommended Plugins

To achieve the full 0ms performance stack, install the companion plugins:

1. **ClickFlow Instant Loader (This Plugin):**
   - Free GitHub Repository: [DrSmoK3y/ClickFlow-Instant-Loader-Plugin-WordPress](https://github.com/DrSmoK3y/ClickFlow-Instant-Loader-Plugin-WordPress)
2. **LiteSpeed Cache:**
   - Official WordPress Plugin: [wordpress.org/plugins/litespeed-cache/](https://wordpress.org/plugins/litespeed-cache/)
3. **Index WP MySQL For Speed:**
   - Official WordPress Plugin: [wordpress.org/plugins/index-wp-mysql-for-speed/](https://wordpress.org/plugins/index-wp-mysql-for-speed/)
4. **LM Page Assets Control:**
   - Official WordPress Plugin: [wordpress.org/plugins/lm-page-assets-control/](https://wordpress.org/plugins/lm-page-assets-control/)

---

## Installation & Setup

### Method A: WordPress Admin (Recommended)
1. Download the latest `clickflow.zip` from the [Releases](https://github.com/DrSmoK3y/ClickFlow-Instant-Loader-Plugin-WordPress/releases) section or from the web applet.
2. In your WordPress Admin, navigate to **Plugins > Add New > Upload Plugin**.
3. Select `clickflow.zip` and click **Install Now**.
4. Click **Activate Plugin**.

### Method B: Git Clone / Manual FTP
1. Clone this repository directly into your WordPress plugins directory:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/DrSmoK3y/ClickFlow-Instant-Loader-Plugin-WordPress.git clickflow
   ```
2. Activate the plugin in **WordPress Admin > Plugins**.

---

## Configuration & 1-Click Presets

ClickFlow features an automatic 1-click configuration engine in **Settings > ClickFlow**:

1. **Blog / Editorial Profile:**
   - Enables aggressive HTML prefetching.
   - Preserves Gutenberg block scripts and comments.
   - Activates LiteSpeed Delay-JS with automatic ClickFlow router exclusions.
2. **Elementor Business Profile:**
   - Preserves per-post stylesheets (CSS Combine OFF).
   - Re-binds Swiper and widget ready handlers.
   - Excludes Elementor admin editor routes.
3. **WooCommerce Store Profile:**
   - Isolates `/cart/`, `/checkout/`, and `/my-account/` from SPA prefetching.
   - Automatically synchronizes cart fragments and CSRF tokens.

---

## Verification & Benchmarks

### Verifying LiteSpeed Cache HIT
Run the following cURL command twice in your terminal:
```bash
curl -I https://yourdomain.com/sample-post/
```
Expected response headers:
```http
HTTP/2 200 OK
x-litespeed-cache: hit
cache-control: public, max-age=604800
alt-svc: h3=":443"; ma=86400
```

### Verifying ClickFlow 0ms Transitions
1. Open your site in Chrome / Firefox and open **DevTools > Network**.
2. Hover over any internal navigation link for > 65ms: observe a background speculative `fetch` request with `Sec-Purpose: prefetch`.
3. Click the link: the content updates instantly with **0ms latency** and no white screen flash.

---

## Repository Structure

```
├── clickflow/                      # Production WordPress Plugin Directory
│   ├── assets/
│   │   ├── css/
│   │   │   └── admin.css           # Admin Settings UI styling
│   │   └── js/
│   │       ├── admin.js            # Auto-setup wizard & AJAX handler
│   │       └── navigator.js        # Core SPA Router, DOMSanitizer, ShellSync
│   ├── includes/
│   │   ├── adapters/
│   │   │   ├── class-classic-editor-adapter.php
│   │   │   ├── class-elementor-adapter.php
│   │   │   └── class-gutenberg-adapter.php
│   │   ├── class-assets.php
│   │   ├── class-auto-setup.php
│   │   ├── class-early-hints-security.php
│   │   ├── class-exclusions.php
│   │   ├── class-litespeed-integration.php
│   │   ├── class-plugin.php
│   │   ├── class-redis-integration.php
│   │   ├── class-route-state.php
│   │   └── class-settings.php
│   ├── templates/
│   │   └── settings-page.php
│   ├── clickflow.php               # Plugin bootstrap file
│   ├── readme.txt                  # WordPress.org standard metadata
│   └── uninstall.php               # Clean removal hook
│
├── README-ROMAN-URDU.md            # Complete Master Guide in Roman Urdu
└── README.md                       # Main English GitHub Documentation
```

---

## License & Credits

ClickFlow Instant Loader is open-source software licensed under the **GNU General Public License v2.0 or later** (GPLv2+).

Created & maintained by **DrSmoK3y** **Creativators**  .  
Contributions, issues, and pull requests are warmly welcomed on [GitHub](https://github.com/DrSmoK3y/ClickFlow-Instant-Loader-Plugin-WordPress).
