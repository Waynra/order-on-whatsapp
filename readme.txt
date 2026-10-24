=== Order on WhatsApp for WooCommerce ===
Contributors: wahyunur
Tags: whatsapp, woocommerce, order, chat, click to chat
Requires at least: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let shoppers send an order for a product or their whole cart to your WhatsApp number, with the message already written for them.

== Description ==

Many small shops still close sales over WhatsApp. This plugin adds a WhatsApp button next to "Add to cart" that opens a chat with a pre-filled order message: product name, chosen options, quantity, price and link.

Features:

* Button under "Add to cart" on product pages. The message follows the quantity field and the selected variation.
* Optional button on shop and category pages for simple products.
* Cart button that sends every item in the cart with the total. Works on the classic cart and the block-based Cart page.
* Floating chat button on every page, bottom left or bottom right.
* `[order_on_whatsapp]` shortcode for any page or post.
* Editable message templates with placeholders.
* Prices use the store's own currency format.
* Compatible with HPOS and the Cart & Checkout blocks.

== Installation ==

1. Upload the `order-on-whatsapp` folder to `/wp-content/plugins/`, or install the zip from Plugins > Add New > Upload Plugin.
2. Activate the plugin.
3. Go to Settings > Order on WhatsApp and enter your number with the country code, without "+" or a leading 0 (for example 6281234567890).

== Frequently Asked Questions ==

= Which placeholders can I use? =

Product message: `{product}`, `{qty}`, `{price}`, `{subtotal}`, `{sku}`, `{url}`.
Cart message: `{items}`, `{count}`, `{total}`.

= How do I use the shortcode? =

`[order_on_whatsapp]` on a product page sends that product. Elsewhere it sends the floating-button message. Options: `text="Ask us"`, `message="Custom message"`, `product_id="123"`.

= Can I change the button color? =

Yes. Set the CSS variables `--oow-bg`, `--oow-bg-hover` and `--oow-fg` in your theme or in Appearance > Customize > Additional CSS.

= The floating button covers something at the bottom of my theme =

Raise it with the `--oow-floating-bottom` CSS variable, e.g. `body { --oow-floating-bottom: 90px; }`. Storefront's mobile toolbar is handled automatically.

== Changelog ==

= 1.0.0 =
* First release.
