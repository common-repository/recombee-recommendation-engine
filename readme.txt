=== Recombee Recommendation Engine ===
Contributors: recombee, 4341922
Tags: recommendations, personalization, related products, ai, e-commerce
Requires at least: 4.9
Tested up to: 5.3
Stable tag: 2.8.1
Requires PHP: 5.5
WC requires at least: 3.3
WC tested up to: 3.9
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Increase your customer satisfaction and spending with Amazon-like AI powered recommendations on your home page, product detail or emailing campaigns.


== Description ==

Recombee plugin for AI Powered Recommendation Engine on your WooCommerce website lets you increase your customer satisfaction and spending with Amazon-like product recommendations applicable to home page, product detail, in-category prioritization, shopping cart, emailing campaigns, and much more. Scenario control and machine learning included. 


Our cloud-based AI solution processes information about your items, users and their interactions to construct real-time recommendations. This approach allows you to make a list of recommended items as personalized as possible, which greatly increases the likelihood of their purchase and, consequently, your profit. 

The Recombee plugin requires account at [recombee.com](https://www.recombee.com/) for it work so you have to register at [Recombee](https://www.recombee.com/).

Please note that the plugin is an addition to the popular WooCommerce plugin. Therefore, for the plugin to work you need to first, install and activate the plugin [Woocommerce](https://wordpress.org/plugins/woocommerce/). 


== Frequently Asked Questions ==

= Where can I get support =

For help you can e-mail [our support](mailto:support@recombee.com) for questions related to this plugin, as well as our core solution.

== Installation ==

= Minimum Requirements =

* Registered account at [Recombee.com](https://www.recombee.com/)
* WooCommerce 3.3 installed and activated
* PHP version 5.5.0 or greater (PHP 5.6 or greater is recommended)
* MySQL version 5.0 or greater (MySQL 5.6 or greater is recommended)

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of this plugin, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New. In the search field type Recombee and click Search Plugins. Once you’ve found our plugin you can install it by simply clicking “Install Now”.

= Manual installation =

The manual installation method involves downloading our plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= After installation =

After you register an instant account at [Recombee](https://www.recombee.com/), go back into your WordPress dashboard, click the menu item Recombee, specify the name of your database at Recombee and the secret token. With these information provided, you can connect to the Recombee service and configure the recommendations widget according to your needs and preferences. For more information, see the website Help on the plugin menu page and the widgets page.

== Screenshots ==

1. Setting credentials
2. Setting DataBase data
3. Plugin settings

== Changelog ==
= 2.8.1 - 31-01-2020 =
* Fix - Set Permalinks for RRE ajax virtual page to avoid visiting Admin permalinks pge manualy 

= 2.8.0 - 31-01-2020 =
* Fix - WC 3.9 compatibility

= 2.7.0 - 22-11-2019 =
* Dev - WP 5.3 compatibility

= 2.6.10 - 01-11-2019 =
* Fix - User synchronization occurred when the connection to Recombee database was disabled

= 2.6.9 - 29-10-2019 =
* Test - WP 5.2
* Test - WC 3.7

= 2.6.8 - 06-06-2019 =
* Test - WP 5.2
* Test - WC 3.6

= 2.6.7 - 25-05-2019 =
* Fix - PHPWee Class declaration conflict
* Fix - CSS rules errors

= 2.6.6 - 09-04-2019 =
* Fix - Internal Error of getting slug of un-existing taxonomy

= 2.6.5 - 20-02-2019 =
* Fix - Error of initial synchronization of customers, in the case when the customer was registered in the store, but later deleted as a user of the WordPress
* Tweak - Set "define('WP_DEBUG', true)" inside wp-config.php - and all Server-side PHP critical errors will be transmitted into browser console during execution of AJAX requests

= 2.6.4 - 10-02-2019 =
* Fix - Error in default value of shortcode for setting page fixed

= 2.6.3 - 26-11-2018 =
* Fix - Fixed a bug related to incorrect definition of URLs of scripts on sites where the installation directory of a WordPress is different from the URL of a site

= 2.6.2 - 09-11-2018 =
* Fix - Minor bug fixed

= 2.6.1 - 03-11-2018 =
* Fix - Minor bug fixed

= 2.6.0 - 03-11-2018 =
* Fix - Unstable number of recommended items via widget. Products and product properties should be resynchronized.

= 2.5.1 - 01-11-2018 =
* Dev - Ajax Dispatcher now also controls requests in admin area

= 2.5.0 - 30-10-2018 =
* Dev - The plugin adds an additional module as a "Must-Use" separate plugin, that controls Ajax requests and increases general perfomance

= 2.4.0 - 25-10-2018 =
* Dev - Plugin start using it's own custom AJAX gateway to increase perfomance

= 2.3.1 - 21-10-2018 =
* Dev - For the widget and shortcode, new options have been added that allows to suppress receiving and displaying recommendations according to different rules. See the embedded help for details. Be sure to re-save all the widgets that are in-use.

= 2.3.0 - 21-10-2018 =
* Fix - CSS styles

= 2.2.5 - 12-10-2018 =
* Dev - Internal modifications

= 2.2.4 - 06-10-2018 =
* Dev - New option WC Related Tags. Allows to specify nook names via WooCommerce related products section generates and replace it with Recombee content

= 2.2.3 - 04-10-2018 =
* Fix - Shortcode & Widget parameter "columns" not respects with followThemeCss = on

= 2.2.2 - 21-09-2018 =
* Fix - Unstable number of recommended items via widget. Products and product properties should be resynchronized.

= 2.2.1 - 19-09-2018 =
* Dev - The request to add a detailed view (visit the product page) is now performed asynchronously
* Tweak - All JS and CSS assets is now loads minified by default. To use developer version add GET variable into URL jscss=dev. Ex: domain.com?jscss=dev or domain.com?something=all&jscss=dev

= 2.2.0 - 14-08-2018 =
* Fix - error generating temporary visitor ID

= 2.1.9 - 14-08-2018 =
* Tweak - A long-time request to MergeUsers to Recombee on user login now goes asynchronously to not slow down login process

= 2.1.8 - 28-06-2018 =
* Fix - On hitting back and forward inside the browser no recommendations were shown, but endless "spinning wheel"
* Dev - Added parameter to the widget and shorcode - "followThemeCss" (on/off) to make products inside the recommendations box looks and styles like active theme do

= 2.1.7 - 28-06-2018 =
* Fix - Slow down WooCommerce "Ajax Add-to-Cart" with plugin activated
* Tweak - Double click on filter and booster fields inside widget will suggest to unlock them

= 2.1.6 - 27-06-2018 =
* Fix - Fixed error in ReQL filter and booster string generation
* Dev - Default widget filter field value and woocommerce related products substitution shortcode changed to filter products, that are in stock now

= 2.1.5 - 26-06-2018 =
* Fix - Unnecessary requests to the server removed

= 2.1.4 - 21-06-2018 =
* Dev - Added attribute async="async" to widget script tag

= 2.1.3 - 19-06-2018 =
* Dev - Widget loading optimized at frontend

= 2.1.2 - 16-06-2018 =
* Fix - PHP error type "E_WARNING: in_array() expects parameter 2 to be array, string given | at class-RecombeeReBlogSettingsDb.php (1097)" Fixed

= 2.1.1 - 14-06-2018 =
* Dev - Multiple booster conditions allowed

= 2.1.0 - 06-06-2018 =
* Dev - Implemented Query Builder for arrangement one-level booster expression within widget. 

= 2.0.0 - 26-05-2018 =
* Tweak - "Log Requests Error" mechanism track and write into "requests-errors.log" also error trigger function name and item ID for which error occurs if available.
* Tweak - Multi-select boxes "Category" and "Tag" removed from widget form to be replaced with handy query builder in next release.
* Fix - All customers on initial synchronization will be detected and transmitted to Recombee, even if WooCommerce "Enable guest checkout" was On. Such customers will be marked as "init_sync_guest" in "wpStatus" at Recombee.
* Dev - Implemented handy Query Builder for arrangement filter expression within widget. 
* Dev - Added widget option "Parent products only".
* Dev - Shortcode [RecombeeRecommends] removed. Use [RecombeeRecommendations] instead.
* Dev - Added ability to setup Recombee scenario with widget and shortcode.
* Dev - Added widget and shortcode parameter "ajaxMode". With this parameter turned on all the instances of widgets and shortcodes within the page will be merged into one request and resulting recommendations will be rendered on the page asynchronously.
* Dev - Added settings option "Distinct recommendations". With this parameter and "ajaxMode" turned on, Recombee will return unique recommendations, so no recommended item will repeat on the same page.
* Dev - Added settings option "Synchronziation Chunk Size", which controls size of the data chunks that are being transferred to Recombee. Adjust it to meet your server’s performance and Apache restrictions.
* Dev - Added support for WooCommerce native attributes taxonomy and the attributes values.
* Dev - Added dynamic detection of any custom taxonomies registered for WooCommerce post type. Those taxonomies may also be used within synchronization to Recombee.
* Dev - Added customizable product & customer properties to use across the initial and regular synchronization to Recombee.

= 1.0.1 - 25-02-2018  =
* Dev - Shortcode [RecombeeRecommends] added

= 1.0.0 - 02-02-2018 =
* Initial release

== Upgrade Notice ==
= 2.6.0 =
<strong>Heads up!</strong> New product property added - Product is Visible. Be sure to re-sync properties set and all products.

= 2.4.0 =
<strong>Heads up!</strong> The plugin needs to change the structure of permalinks after the update to this new version. To do this automatically, just go to any page of the administrative part of the site at the end of the update.

= 2.3.1 =
<strong>Heads up!</strong> This plugin version contains changes to widget. To make it work correct - just re-save all Recombee widgets instance.

= 2.3.0 =
<strong>Heads up!</strong> This plugin version contains changes to widget. To make it work correct - just re-save all Recombee widgets instance.

= 2.1.8 =
<strong>Heads up!</strong> New parameter "followThemeCss" (on/off) added to the widget and shortcode. Read help section on the admin widgets page and plugin settings page. Re-save active widgets to ensure their operability. 

= 2.1.0 =
<strong>Heads up!</strong> This plugin version contains changes to widget. To make it work correct - just reinstall all Recombee widgets instance.