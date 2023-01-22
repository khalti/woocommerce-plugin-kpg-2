## Integrate Khalti in WooCommerce
Contributors: act360
Tags: woocommerce, khalti, payment gateway
Requires at least: 5.0
Tested up to: 6.0.1
Stable tag: trunk
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Adds Khalti payment gateway option in the WooCommerce plugin.

## Description

This plugin adds Khalti payment gateway to WooCommerce.

Please note that [WooCommerce](https://wordpress.org/plugins/woocommerce/) must be installed and active.

== Introduction ==

Add Khalti as a payment method in your WooCommerce store.

[Khalti](https://Khalti.com/) is a Nepali Digital Payment Portal developed by Khalti. This means, if your store doesn't accept payment in NPR, you do not need this plugin.


== Installation ==

= Minimum Requirements =

* WordPress 5.0 or greater.
* WooCommerce 3.6 or greater.

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of Integrate Khalti in WooCommerce, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type "Integrate Khalti in WooCommerce" and click Search Plugins. Once you’ve found our payment gateway plugin you can view details about it such as the the point release, rating and description. Most importantly of course, you can install it by simply clicking “Install Now”.

= Manual installation =

The manual installation method involves downloading our Integrate Khalti in WooCommerce plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Updating =

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.


== Frequently Asked Questions ==

= What is the plugin license? =

* This plugin is released under a GPL license.

= What is needed to use this plugin? =

* PHP 5.6 or later
* WordPress 5.0 or later.
* WooCommerce 3.6 or later.
* Merchant secret from Khalti.

= Which countries does Khalti accepts payment from? =

At the moment the Khalti accepts payments only from Nepal.

Configure the plugin to receive payments only users who select Nepal in payment information during checkout.

= I don't see Khalti payment option during checkout? =

You forgot to select the Nepal during registration at checkout. The Khalti payment option works only with Nepal.

= The request was paid and got the status of "processing" and not as "complete", that is right? =

Yes, this is right and it means that the plugin is working as it should.

Payment gateways in WooCommerce change the order status to "processing" when the payment is confirmed and should never be changed alone to "complete" because the request should go only to the status "completed" after it has been delivered.

For downloadable products, WooCommerce default setting is to allow access only when the request has the status "completed", however in WooCommerce settings tab Products you can enable the option "Grant access to download the product after payment" and thus release download when the order status is as "processing."

= Where can I report bugs or contribute to the project? =

Bugs can be reported either in our support forum or preferably on the [WooCommerce Khalti GitHub repository](https://github.com/act360/Khalti-for-woocommerce/issues). You can directly reach our developers at developers@act360.com.np

= Can I contribute to this plugin? =

Of course! Join in on our [GitHub repository](https://github.com/act360/Khalti-for-woocommerce)


== Credits ==
[ACT360](https://www.act360.com.np/)


== Screenshots ==

1. Settings page.
2. Checkout page.

== Changelog ==

= 1.0.0 - 04-09-2020 =
* First release.
