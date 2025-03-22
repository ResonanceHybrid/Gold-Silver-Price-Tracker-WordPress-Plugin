=== Gold & Silver Price Tracker ===
Contributors: Sohan Mehta
Tags: gold, silver, metal prices, price tracker, commodities
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.0.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display real-time gold and silver prices in any currency with a customizable shortcode.

== Description ==

Gold & Silver Price Tracker is a flexible WordPress plugin that allows you to display current precious metal prices anywhere on your website using a simple shortcode.

= Features =
* Real-time gold and silver price tracking
* Support for multiple currencies (USD, EUR, GBP, JPY, etc.)
* Multiple weight units (oz, g, kg, tola)
* Customizable display layouts
* Responsive design
* Cache control to reduce API usage
* Shortcode support with customization options

= Requirements =
* WordPress 5.0 or higher
* PHP 7.0 or higher
* A free or paid API key from GoldAPI.io

== Installation ==

1. Upload the `gold-silver-price-tracker` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Metal Price Tracker to configure the plugin
4. Add your GoldAPI.io API key in the settings page
5. Use the shortcode `[metal_prices]` in any post or page to display the prices

== Frequently Asked Questions ==

= Where do I get an API key? =

You need to register for an API key at [GoldAPI.io](https://www.goldapi.io). They offer free and paid plans depending on your usage requirements.

= How often are the prices updated? =

By default, prices are cached for 60 minutes to reduce API calls. You can adjust this in the plugin settings, or your visitors can use the "Refresh Prices" button to get the latest data.

= Can I customize the display? =

Yes! You can choose between different layouts in the settings, and also add custom CSS. Additionally, you can customize individual instances with shortcode attributes.

== Screenshots ==

1. Metal prices display on the frontend
2. Plugin settings page
3. Different layout options

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release