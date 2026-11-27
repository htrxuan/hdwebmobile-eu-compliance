=== HDWebmobile EU Compliance ===
Contributors: htrxuan
Donate link: https://paypal.me/htrxuan/20
Tags: omnibus, gpsr, price history, product safety, eu
Requires at least: 6.9
Tested up to: 7.2
Requires PHP: 7.4
Stable tag: 1.0.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show the lowest 30-day price on discounted products, product safety (GPSR) details and a legal guarantee notice for EU shoppers.

== Description ==

Stores that sell to customers in the European Union need to show some extra information on product pages. HDWebmobile EU Compliance helps your WooCommerce store display three of the most common items:

**1. Lowest prior price (Omnibus Directive).** When a product is on sale, shoppers must be able to see the lowest price it had in the 30 days before the discount. The plugin records every price change automatically and shows a line such as "Lowest price in the 30 days before this discount: 99.00" under the price.

**2. Product safety details (GPSR).** Show the manufacturer, the responsible person in the EU and safety information in a "Product safety" tab. Set store-wide defaults once and override them on any product.

**3. Legal guarantee notice.** Show your legal guarantee text under the add-to-cart button and in the safety tab, with an option to hide it on individual products.

This plugin displays the information you enter. It is not legal advice: check which rules apply to your store and country.

= Features =
* Automatic price history for simple products and every variation
* Records manual edits, bulk edits and WooCommerce's scheduled sales
* Lowest-prior-price line on product pages, optionally in shop and category lists too
* Adjustable period (30 days by default) and wording
* Variation-aware: the notice updates when a shopper picks a variation
* "Product safety" tab with manufacturer, EU responsible person and safety information
* Store-wide defaults with per-product overrides in the product data panel
* Legal guarantee notice with a per-product "hide" option
* Works with block themes and classic themes
* Old price history is cleaned up automatically

= Security by design =
* Only users who can manage WooCommerce can change settings, protected by a nonce; product fields are saved inside WooCommerce's own nonce-checked product save.
* All texts are stored as plain text and escaped when shown, so nothing entered can add scripts or markup to your pages.
* The price history table is only written by the plugin itself, with prepared database queries.

= Limitations (please read before installing) =
* Price history starts when the plugin is activated. Until a product has older history, its regular price is shown as the earlier price.
* Prices changed directly in the database (bypassing WooCommerce) are not recorded.
* Variable products show the notice for the selected variation, not for the price range.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/hdwebmobile-eu-compliance` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress. WooCommerce must already be installed and active.
3. Go to **WooCommerce > HDWebmobile > EU Compliance** to review the settings.

== How to Use ==

= 1. Lowest prior price =
It works straight away. Put a product on sale and the notice appears under its price. Adjust the period and the wording on the settings screen.

= 2. Product safety =
Enter your default manufacturer, EU responsible person and safety information on the settings screen. To use different details for one product, fill in the "EU compliance" fields in that product's General tab.

= 3. Legal guarantee =
Tick "Show notice" under Legal guarantee and adjust the text.

== Frequently Asked Questions ==

= Which price is shown as the lowest earlier price? =
The lowest price the product had during the chosen period (30 days by default) before its current sale price started. If the sale has a start date, that date is used.

= Does it work with variable products? =
Yes. Every variation has its own history, and the notice appears when the shopper selects a variation.

= What happens to the data if I delete the plugin? =
Deleting the plugin removes its settings, the price history table and the per-product EU fields.

== Screenshots ==

1. The lowest prior price under a discounted product's price.
2. The Product safety tab.
3. The settings screen.

== Changelog ==

= 1.0.0 =
* Initial release: lowest prior price notice with automatic price history, product safety (GPSR) tab and legal guarantee notice.
