=== Antigravity Purchase Notifications for WooCommerce ===
Contributors: Antigravity
Tags: woocommerce, purchase notifications, social proof, conversion optimization, sales notifications, live notifications, popup
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Boost your store conversion rates by showing authentic, elegant recent purchase notifications of the product currently being viewed using real-time WooCommerce order data.

== Description ==

Antigravity Purchase Notifications is a premium-quality, lightweight, and fully self-hosted social proof solution that displays authentic recent purchases on your product pages. 

By leveraging native order history, the plugin generates floating purchase notification cards that prove to visitors that your store has active, satisfied buyers. 

=== Key Features ===
* **Fully Self-Hosted & Private**: Runs entirely on your own server. No external APIs, SaaS accounts, subscription fees, or monthly data limits.
* **Current Product Context**: Dynamically filters notifications to display recent purchases of only the product currently being viewed, maintaining 100% authenticity.
* **High Performance**: Asynchronous AJAX loading prevents page caching issues with caching suites (WP Rocket, LiteSpeed Cache, FlyingPress, W3 Total Cache). Server-side transient caching and client-side session storage prevent database overloading.
* **Privacy Focused & GDPR Ready**: All name anonymization and location mapping is executed server-side. No customer-identifying data (PII) is sent to the client browser. Includes toggles for anonymous mode, location hiding, and automatic name obfuscation patterns.
* **Extensive Visual Customization**: Change typography, background colors, accents, border radii, card shadow, spacing, and thumbnail sizes. Includes 8 distinct entry/exit animations (Fade, Slide Up/Down/Left/Right, Scale, Zoom, Bounce).
* **Live Settings Preview**: A persistent, real-time preview dashboard in your WordPress admin panel displays appearance updates instantly as you tweak settings.
* **Accessible (WCAG) & Responsive**: Optimized for keyboard access, screen readers (`aria-live`), and touch screens. Responsive toggles enable or disable notifications on mobile, tablet, or desktop.
* **High-Performance Order Storage (HPOS) Ready**: Fully compatible with WooCommerce HPOS and custom database order tables.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/antigravity-purchase-notifications` directory, or install the plugin directly through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to WooCommerce -> Purchase Notifications to configure options.

== Frequently Asked Questions ==

= Does this plugin work with caching systems? =
Yes! The plugin loads notifications asynchronously via AJAX. This ensures cached HTML pages do not show stale, duplicate, or incorrect notifications.

= Does this plugin comply with GDPR? =
Yes! Under GDPR mode, all anonymization processes are executed on the server before data is sent to the frontend. No customer database identifiers, billing details, or private fields are sent to the user's browser.

= Does it support WooCommerce variations? =
Yes! If a variation is purchased, the plugin detects the parent product ID and displays the parent product details along with its image and the relative purchase time.

== Screenshots ==

1. Settings Panel Dashboard with Live Cards Preview.
2. Visual configuration options including 6 responsive tabs.
3. Real-time floating purchase card popup on WooCommerce single product pages.

== Changelog ==

= 1.0.0 =
* Initial release.
