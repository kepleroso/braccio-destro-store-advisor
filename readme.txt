=== Braccio Destro Store Advisor ===
Contributors: ceotrust
Tags: woocommerce, analytics, cash flow, inventory, ai
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connects your WooCommerce store to Braccio Destro with a read-only key, so AI experts can advise you every morning on sales, stock and cash.

== Description ==

Braccio Destro Store Advisor connects your store to the service at https://www.ceotrust.it, a team of AI experts for small and medium-sized businesses.
This plugin connects your WooCommerce store to your Braccio Destro account with a one-time code, without copying API keys by hand.

* It creates a WooCommerce REST API key with **read-only** permission: Braccio Destro cannot change anything in your store.
* Braccio Destro reads orders, products and stock levels and refreshes them every night.
* Customer names and email addresses are not stored: returning customers are counted with an anonymous code.
* Disconnect whenever you want: the key is deleted and Braccio Destro deletes the data it read. The same happens when you deactivate or uninstall the plugin.

A Braccio Destro account is required (free trial available at https://www.ceotrust.it). The plugin is ready for translation through translate.wordpress.org.

== External services ==

This plugin connects to the Braccio Destro service, operated by Andrea Motta (https://www.ceotrust.it), to link your store to your Braccio Destro account.

Endpoint: https://xmbgjjucxlmcgkxcjxwi.supabase.co/functions/v1/shop

Data is sent only in two situations:

* When you click "Connect": the connection code you pasted, your site address, the WooCommerce version and the read-only API key created by the plugin (consumer key and consumer secret).
* When you click "Disconnect", deactivate or uninstall the plugin: your site address and the consumer key, so that Braccio Destro deletes the connection and the data it read.

Afterwards, Braccio Destro uses the read-only key to read orders, products and stock from your store's WooCommerce REST API.

Terms of service: https://www.ceotrust.it/termini.html
Privacy policy: https://www.ceotrust.it/privacy.html

== Installation ==

1. Install and activate the plugin.
2. Go to WooCommerce → Braccio Destro and click "Open Braccio Destro and copy the code" (or, on www.ceotrust.it, open "Collega il negozio online" and choose WooCommerce).
3. Paste the code in WooCommerce → Braccio Destro and click "Connect".

Your store must be reachable over https and permalinks must not be set to "Plain" (Settings → Permalinks).

== Frequently Asked Questions ==

= Can Braccio Destro change my store? =

No. The key created by the plugin only has read permission: WooCommerce rejects any change.

= What data does Braccio Destro read? =

Orders from the last 18 months, products and stock levels. For customers, Braccio Destro only keeps an anonymous code to count returning buyers.

= How do I disconnect my store? =

Go to WooCommerce → Braccio Destro and click "Disconnect from Braccio Destro", or deactivate the plugin. The key is deleted and Braccio Destro deletes the data it read.

== Changelog ==

= 1.0.0 =
* First release.
