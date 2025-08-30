=== Disable XML-RPC-API ===

Plugin Name: Disable XML-RPC-API
Plugin URI: https://neatma.com
Contributors: aminnz,neatmarketing
Description: Simple plugin to disable XML-RPC API and X-Pingback for faster and more secure website.
Tags: disable xml-rpc, disable xmlrpc,remove xmlrpc, XML-RPC, pingback, stop brute force attacks
Version: 2.0.0
Tested up to: 5.7
Requires at least: 3.5
Author: Neatmarketing
Author URI: https://neatma.com
License: GPLv2

A simple and lightweight plugin to disable XML-RPC API, X-Pingback and pingback-ping in WordPress 3.5+ for a faster and more secure website

== Description ==

Protect your website from xmlrpc brute-force attacks,DOS and DDOS attacks, this plugin disables the XML-RPC and trackbacks-pingbacks on your WordPress website.

**PLUGIN FEATURES**

* Disable access to xmlrpc.php file using .httacess file 
* Disable X-pingback to minimize CPU usage 
* Remove pingback-ping link from header
* Disable trackbacks and pingbacks to avoid spammers and hackers
* Disable xmlrpc API entirely 

**What is XMLRPC**

XML-RPC, or XML Remote Procedure Call is a protocol which uses XML to encode its calls and HTTP as a transport mechanism.
Beginning in WordPress 3.5, XML-RPC is enabled by default. Additionally, the option to disable/enable XML-RPC was removed. For various reasons, site owners may wish to disable this functionality. This plugin provides an easy way to do so.

**Why you should disable XML-RPC**
*Xmlrpc has two main weaknesses*

* Brute force attacks:
 Attackers try to login to WordPress using xmlrpc.php with as many username/password combinations as they can enter. A method within xmlrpc.php allows the attacker to use a single command (system.multicall) to guess hundreds of passwords. Daniel Cid at Sucuri described it well in October 2015: “With only 3 or 4 HTTP requests, the attackers could try thousands of passwords, bypassing security tools that are designed to look and block brute force attempts.”
* Denial of Service Attacks via Pingback:
 Back in 2013, attackers sent Pingback requests through xmlrpc.php of approximately 2500 WordPress sites to “herd (these sites) into a voluntary botnet,” according to Gur Schatz at Incapsula. “This gives any attacker a virtually limitless set of IP addresses to Distribute a Denial of Service attack across a network of over 100 million WordPress sites, without having to compromise them.”



== Installation ==

1. Upload the disable-xml-rpc directory to the `/wp-content/plugins/` directory in your WordPress installation
2. Activate the plugin through the 'Plugins' menu in WordPress
3. XML-RPC-API is now disabled!

To re-enable XML-RPC, just deactivate the plugin through the 'Plugins' menu.

== Frequently Asked Questions ==

= Is there an admin interface for this plugin? =

No. This plugin is as simple as XML-RPC is off (plugin activated) or XML-RPC is on (plugin is deactivated).

= How do I know if the plugin is working? =

There are three easy methods for checking if XML-RPC is off:
1. Easiest way is going to this url: http://yourdomain/xmlrpc.php enter your domain name instead of 'yourdomain' if you see "Access forbidden!" or "403 error" it's working.
2. First, try using an XML-RPC client, like the official WordPress mobile apps. The WordPress mobile app should tell you that "XML-RPC services are disabled on this site" if the plugin is activated.
3. Or you can try the XML-RPC Validator, written by Danilo Ercoli of the Automattic Mobile Team - the tool is available at [http://xmlrpc.eritreo.it/](http://xmlrpc.eritreo.it/) with a blog post about it at [http://daniloercoli.com/2012/05/15/wordpress-xml-rpc-endpoint-validator/](http://daniloercoli.com/2012/05/15/wordpress-xml-rpc-endpoint-validator/). Keep in mind that you want the validator to fail and tell you that XML-RPC services are disabled.

= Something doesn't seem to be working correctly =

If the plugin is activated, but XML-RPC appears to still be working ... OR ... the plugin is deactivated, but XML-RPC is not working, then it's possible that another plugin or theme function is affecting the plugin functions.

== Screenshots ==
screenshot-1.png
== Changelog ==

= 1.0.0 =
* Initial release

= 1.0.1 =
* Fix bugs

= 1.0.5 =
* Remove pingback link tag in header
* Add ability to fix htaccess file permission

= 1.0.6 =
* Fix warnings for htaccess permission

= 1.0.7 =
* Fix blank page when using W3 Total Cache and some other cache plugins

= 1.0.8 =
* Fix code conflict with Autoptimize plugin

= 1.0.9 =
* Wordpress 5.7 compatible
* Fix some issues

= 2.0.0 =
* Fix code conflict with some other plugin
* Fix hiding data in WooCommerce Product Tabs