# HDWebmobile EU Compliance

Show the lowest 30-day price on discounted products, product safety (GPSR) details and a legal guarantee notice for EU shoppers.

- **WordPress.org:** https://wordpress.org/plugins/hdwebmobile-eu-compliance/
- **Requires:** WordPress 6.9+, WooCommerce, PHP 7.4+
- **License:** GPLv2 or later

## Description

Stores that sell to customers in the European Union need to show some extra information on product pages. HDWebmobile EU Compliance helps your WooCommerce store display three of the most common items:

**1. Lowest prior price (Omnibus Directive).** When a product is on sale, shoppers must be able to see the lowest price it had in the 30 days before the discount. The plugin records every price change automatically and shows a line such as "Lowest price in the 30 days before this discount: 99.00" under the price.

**2. Product safety details (GPSR).** Show the manufacturer, the responsible person in the EU and safety information in a "Product safety" tab. Set store-wide defaults once and override them on any product.

**3. Legal guarantee notice.** Show your legal guarantee text under the add-to-cart button and in the safety tab, with an option to hide it on individual products.

This plugin displays the information you enter. It is not legal advice: check which rules apply to your store and country.

### Features
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

### Security by design
* Only users who can manage WooCommerce can change settings, protected by a nonce; product fields are saved inside WooCommerce's own nonce-checked product save.
* All texts are stored as plain text and escaped when shown, so nothing entered can add scripts or markup to your pages.
* The price history table is only written by the plugin itself, with prepared database queries.

## Limitations

* Price history starts when the plugin is activated. Until a product has older history, its regular price is shown as the earlier price.
* Prices changed directly in the database (bypassing WooCommerce) are not recorded.
* Variable products show the notice for the selected variation, not for the price range.

## License

GPLv2 or later.
