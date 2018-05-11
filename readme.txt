=== CiviCRM Admin Utilities ===
Contributors: needle, cuny-academic-commons
Donate link: https://www.paypal.me/interactivist
Tags: civicrm, admin, utility, styling, menu
Requires at least: 4.4
Tested up to: 4.9
Stable tag: 0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

CiviCRM Admin Utilities modifies CiviCRM's behaviour and appearance in single site and multisite installs.



== Description ==

CiviCRM Admin Utilities modifies CiviCRM's behaviour in single site and multisite installs. It does a number of useful things:

* Modifies the styling of the CiviCRM menu to fix a number of issues
* Fixes the appearance of the WordPress Access Control form
* Adds a handy CiviCRM Shortcuts menu to the WordPress Admin Bar
* Allows you to choose which Post Types the CiviCRM shortcode button appears on
* In WordPress multisite, allows you to hide CiviCRM on sub-sites

### Requirements

This plugin requires a minimum of *WordPress 3.9* and *CiviCRM 4.6*. Please refer to the installation page for configuration instructions as well as for how to use this plugin with versions of CiviCRM prior to 4.6.

### Plugin Development

This plugin is in active development. For feature requests and bug reports (or if you're a plugin author and want to contribute) please visit the plugin's [GitHub repository](https://github.com/christianwach/civicrm-admin-utilities).



== Installation ==

1. Extract the plugin archive
1. Upload plugin files to your `/wp-content/plugins/` directory
1. Make sure CiviCRM is activated and properly configured
1. Activate the plugin through the 'Plugins' menu in WordPress

In single-site installs, you can adjust this plugin's settings by visiting "Settings" --> "CiviCRM Admin Utilities". If you install this plugin in multisite and have CiviCRM network-enabled, then you should also network-enable this plugin. You'll then find its settings page at "Network Admin" --> "Settings" --> "CiviCRM Admin Utilities".

This plugin requires a minimum of *WordPress 3.9* and *CiviCRM 4.6*. For versions of CiviCRM prior to 4.6, this plugin requires the corresponding branch of the [CiviCRM WordPress plugin](https://github.com/civicrm/civicrm-wordpress) plus the custom WordPress.php hook file from the [CiviCRM Hook Tester repo on GitHub](https://github.com/christianwach/civicrm-wp-hook-tester) so that it overrides the built-in CiviCRM file. Please refer to the each repo for further instructions.

<h4>Upgrading from 0.3.3 or earlier</h4>

If you have this plugin installed on WordPress Multisite and this plugin is *not* network activated, then read on.

Prior to version 0.3.4, this plugin stored its settings in the *network options* rather than in the *site's options*. This meant that separate sites *shared* their settings rather than being individually configurable. Version 0.3.4 changed the location where the plugin's settings are stored to be appropriate to the install location and, as a result, each site can be configured differently.

If you are upgrading from 0.3.3, therefore, you may need to review the settings for each site where CiviCRM Admin Utilities is activated.



== Changelog ==

= 0.4 =

* Fixes location of settings on WordPress Multisite when plugin is not network activated

= 0.3.4 =

* Fixes appearance of Shortcuts menu on main site when CiviCRM is disabled on subsites
* Further fixes for the Shoreditch theme for CiviCRM

= 0.3.3 =

* Fix styles for the Shoreditch theme for CiviCRM

= 0.3.2 =

* Reinstates missing permissions rows

= 0.3.1 =

* Add link to Reports Listing to CiviCRM shortcuts menu
* Add link to Manage Groups to CiviCRM shortcuts menu
* Set sensible plugin defaults

= 0.3 =

* Add a CiviCRM shortcuts menu to WordPress admin bar
* Fix the appearance of the WordPress Access Control form

= 0.2.9 =

Add basic support for the Shoreditch theme for CiviCRM

= 0.2.8 =

Remove install notice from subsites when restricting CiviCRM to main site only

= 0.2.7 =

Fix unloading of CiviCRM assets when there is no post type defined in admin

= 0.2.6 =

Fix uninstall procedure

= 0.2.5 =

Remove CiviCRM CSS and Javascript when shortcode button is disabled on a post type

= 0.2.4 =

Prevent PHP notice during upgrades

= 0.2.3 =

Add link to rebuild database triggers and functions

= 0.2.2 =

Remove shortcode button when restricting Civi to main site in multisite

= 0.2.1 =

Fixes removal of Civi admin menu in multisite

= 0.2 =

First public release

= 0.1 =

Initial release
