=== ham3da integration for OxaPay ===
Contributors: ham3da
Tags: crypto payment, bitcoin, usdt, bnb, payment gateway, farsi
Requires at least: 5.0
Tested up to: 6.7.1
Stable tag: 1.1.0
Requires PHP: 7.2
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Accept cryptocurrency payments on your WooCommerce store.

== Description ==

ham3da integration for OxaPay is a WooCommerce plugin that allows your customers to pay for their orders using cryptocurrency. 
It provides a seamless and secure checkout experience, making it easy for your customers to shop with their preferred digital assets.


= Key features =

* Accept cryptocurrency payments from your customers, such as Bitcoin, Tether(USDT), Tron, Ethereum, Dogecoin, Monero, Solana, Toncoin, etc.
* Ability to set the conversion rate(Convert your currency to USD)
* Update the conversion rate via currencyapi API
* Ability to set transaction fee payment by the user
* Save transaction details in the order as a note
* Simple and easy setup
* Debug Log 
* No KYC required
* Low fees
* View all incoming payments via your [OxaPay dashboard](https://oxapay.com)
* Clean Code

== Installation ==

1. Upload ham3da integration for OxaPay to your wordpress and activate it.
2. Go to the Admin panel -> Woocommerece -> Settings -> Payments -> OxaPay.
3. Enable it and complete the settings.
4. You can now make a test purchase with this payment gateway.

== Screenshots ==

1. ham3da integration for OxaPay plugin settings (1)
2. ham3da integration for OxaPay plugin settings (2)
3. Checkout and OxaPay payment method
4. OxaPay hosted invoice(1) - Select currency and network to pay. Displayed to the user after he clicked the "Pay with OxaPay" button. 
5. OxaPay hosted invoice(2) - Send currency to the displayed address. Displayed to the user after he clicked the "Proceed to payment" button.
6. Steps to create an Merchant API Key in OxaPay dashboard.

== Frequently Asked Questions ==

= How do I pay a OxaPay invoice? =
You must send the invoice amount to the displayed wallet address for the invoice to change to paid status.

= Does OxaPay have a test environment? =
Yes, You can use "sandbox" as an Merchant API key for testing.

= I need support from OxaPay =
* If you encounter a problem with the plugin, submit your problem via reviews. 
* If you have any problems with gateway on oxapay.com, you can chat with their online support through the [OxaPay website](https://oxapay.com).


== Installation ==

= Requirements =

* This plugin requires [WooCommerce](https://wordpress.org/plugins/woocommerce/).
* A OxaPay merchant api key ([oxapay](https://oxapay.com))


= Plugin installation =

1. Get started by signing up for a [OxaPay Merchant API](https://oxapay.com/?ref=30943315)
2. Look for the OxaPay plugin via the [WordPress Plugin Manager](https://codex.wordpress.org/Plugins_Add_New_Screen). From your WordPress admin panel, go to Plugins > Add New > Search plugins and type **OxaPay**
3. Select **ham3da integration for OxaPay** and click on **Install Now** and then on **Activate Plugin**

After the plugin is activated, OxaPay will appear in the WooCommerce > Settings > Payments section.


== Changelog ==

= 1.0.0 =
* First release

= 1.1.0 =
* Minor changes