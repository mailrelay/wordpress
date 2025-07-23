=== Mailrelay ===
Contributors: mailrelay
Donate link:
Tags: mailrelay,newsletter,email marketing
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily sync your wordpress users with Mailrelay.com.

== Description ==

This plugin allows you to sync all Wordpress users with one or more groups in Mailrelay allowing your website users to receive newsletters, notifications etc.

== Installation ==

Before starting, make sure you have an active Mailrelay.com account and your account has a valid Api Key.

1. Upload mailrelay folder to the `/wp-content/plugins/` directory or download it from the Wordpress plugin repository
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings -> Mailrelay and configure your account details

== Configuration ==

Once the extension is installed you will have a new settings option:  Mailrelay -> Authentication

At this page, you will need to fill in the following data:

- Account
- API Key

The Account information can be found in your Mailrelay's welcome email.
And your API Key can be created at your Mailrelay account under settings -> API Keys

Once this data is successfully saved you can run the user sync.

== Changelog ==

= 3.0 =
*Release Date - 23 Jun 2025*

- Added WooCommerce integration with product and cart synchronization
- Changed plugin to sync subscribers using background tasks avoiding errors if API isn't available

= 2.1.3 =
*Release Date - 24 Oct 2023*

- Fixed a deprecation warning in newer PHP versions

= 2.1.2 =
*Release Date - 24 Oct 2023*

- Added nonce validation in admin pages

= 2.1.1 =
*Release Date - 15 Sep 2022*

- Disable clicks in Mailrelay form iframe when displaying it in Gutenberg editor
- Fix form widget not working on newer Gutenberg editor versions
- Fix issue with only part of groups being displayed

= 2.1.0 =
*Release Date - 07 Jul 2022*

- Added Gutenberg block to display Mailrelay signup forms
- Added WPForms Mailrelay integration
- Fix issue with subscriber name not being synced correctly.

= 2.0.2 =
*Release Date - 08 Feb 2022*

- Fix issue with empty subscriber name being synced in certain cases

= 2.0.1 =
*Release Date - 07 Feb 2022*

* Fix issue with connection settings not being saved

= 2.0 =
*Release Date - 04 Feb 2022*

* Add option to sync WooCommerce customers
* Plugin is compatible with Wordpress 5.9.
* Removed support for legacy API

= 1.8.1 =
*Release Date - 29 Oct 2016*

* Fix a warning depending on error_reporting set in PHP

= 1.8.0 =
*Release Date - 17 May 2016*

* Add option to automatically sync new users

= 1.7.4 =
*Release Date - 18 Nov 2015*

* Add German translation
