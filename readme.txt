=== ClickFlow (BETA) ===
Contributors: clickflow
Tags: litespeed, instant navigation, clickflow, spa, elementor, woocommerce, speed
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.9-beta
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

ClickFlow: Instant Page Navigation & LiteSpeed Cache Optimization Engine (v0.9 BETA).

== Description ==

LM Instant Navigation brings seamless, app-like continuity to your WordPress site without rewriting your frontend in React or maintaining custom REST endpoints.

= Features =
* 1-Click Auto Setup Wizard: Automatically configures LiteSpeed Cache, Delay-JS exclusions, and theme selectors based on site profile (Blog, Elementor, WooCommerce).
* LiteSpeed HTML Cache reuse: Fetches standard canonical WordPress URLs directly from LSCWP cache.
* Elementor Adapter: Reinitializes Elementor frontend widgets (`runReadyTrigger`) and syncs per-post CSS (`elementor-post-{id}.css`).
* Gutenberg Adapter: Supports block lightboxes, core embed iframes, and Query Loop blocks.
* Classic Editor Adapter: Supports standard WordPress themes, archive loops, and comment reply forms.
* Hover Intent & Touch Prefetching: Zero perceived navigation delay on desktop and mobile.
* Head & Asset Manager: Diffs required stylesheets and updates `<title>`, canonical, and meta description before reveal.
* Safe Hard-Navigation Fallback: Guarantees zero broken pages by falling back to native browser navigation if any check fails.

== Installation ==

1. Upload `lm-instant-navigation.zip` via WordPress Admin -> Plugins -> Add New -> Upload Plugin.
2. Activate the plugin.
3. Open Settings -> Instant Navigation and click "Apply 1-Click Auto Setup" for your site category (Blog, Elementor Business, or WooCommerce).
4. Enjoy instant page transitions and maximum LiteSpeed Cache compatibility!

== Changelog ==

= 1.1.0 =
* NEW: 1-Click Auto Setup Wizard inside WordPress Admin.
* NEW: Auto-detection of active theme selectors (Hello Elementor, Astra, GeneratePress, Kadence, OceanWP, Block themes).
* NEW: Automatic database programming of LiteSpeed Cache options and Delay-JS exclusions.
* NEW: Safety backup snapshot and instant 1-click rollback functionality.
* NEW: WooCommerce Store compatibility profile with cart/checkout boundary preservation.

= 1.0.0 =
* Initial release with Elementor, Gutenberg, and Classic Editor adapters.
* LiteSpeed Cache Delay JS exclusion integration.
* Zero click-delay hover intent prefetch and volatile LRU in-memory cache.
