=== Mailrelay ===
Contributors: Mailrelay.com
Donate link: 
Tags: mailrelay,newsletter,email marketing
Requires at least: 3.3
Tested up to: 4.6
Stable tag: 1.8.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily sync your wordpress users with Mailrelay.com.

== Description ==

This plugin allows you to sync all Wordpress users with one or more groups in Mailrelay allowing your website users to receive newsletters, notifications etc.

== Installation ==

Before starting, make sure you have an active Mailrelay.com account and your account have a valid Api Key. 

1. Upload mailrelay folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings -> Mailrelay and configure your account details

== Configuration ==

Once the extension is installed you will have a new settings option: Settings > Mailrelay

At this page, you will need to fill in the following data:

- Username
- Password
- Host

This information can be found in your Mailrelay's welcome email. Please note that the host must be entered without initial http://.

Once this data is successfully saved you can run the user sync.

== Changelog ==

= 1.8.1 =
*Release Date - 29 Oct 2016*

* Fix a warning depending on error_reporting set in PHP

= 1.8.0 =
*Release Date - 17 May 2016*

* Add option to automatically sync new users

= 1.7.4 =
*Release Date - 18 Nov 2015*

* Add German translation
