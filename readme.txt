=== CiviCRM Admin Utilities ===
Contributors: needle, cuny-academic-commons
Donate link: https://www.paypal.me/interactivist
Tags: civicrm, admin, utility, styling, menu
Requires at least: 4.4
Tested up to: 5.1
Stable tag: 0.6.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

CiviCRM Admin Utilities modifies CiviCRM's behaviour and appearance in single site and multisite installs.



== Description ==

CiviCRM Admin Utilities modifies CiviCRM's behaviour in single site and multisite installs. It does a number of useful things:

* Supplies an alternative theme for CiviCRM admin screens (see below)
* Modifies the styling of the CiviCRM menu to fix a number of issues
* Fixes the appearance of the Shoreditch extension in WordPress admin
* Fixes the appearance of the WordPress Access Control form where necessary
* Offers options to prevent various CiviCRM Stylesheets from loading on the front-end
* Adds a handy CiviCRM Shortcuts menu to the WordPress Admin Bar
* Allows you to choose which Post Types the CiviCRM shortcode button appears on
* In WordPress multisite, allows you to hide CiviCRM on sub-sites
* Allows you to remove "administer CiviCRM" capabilities from sub-site administrators
* Allows suppression of the "change of email" notification when a CiviCRM Contact's primary email is changed

### CiviCRM Admin Theme

Version 0.5 introduces a new theme for CiviCRM admin screens that is more in keeping with WordPress. It can be enabled on the CiviCRM Admin Utilities settings page. Feedback is welcome - please open an issue on the plugin's [GitHub repository](https://github.com/christianwach/civicrm-admin-utilities) if you find any bugs or have suggestions for improvements.

### Requirements

This plugin requires a minimum of *WordPress 4.4* and *CiviCRM 4.7*.

### Notes

If you have installed the Shoreditch extension for CiviCRM, then this plugin does its best to make it compatible with WordPress. Unfortunately, Shoreditch version 0.1-alpha25 now makes it very difficult to override the margin applied to the body tag on the front end of your site, so you will have to do this yourself.

### Plugin Development

This plugin is in active development. For feature requests and bug reports (or if you're a plugin author and want to contribute) please visit the plugin's [GitHub repository](https://github.com/christianwach/civicrm-admin-utilities).



== Installation ==

1. Extract the plugin archive
1. Upload plugin files to your `/wp-content/plugins/` directory
1. Make sure CiviCRM is activated and properly configured
1. Activate the plugin through the 'Plugins' menu in WordPress

In single-site installs, you can adjust this plugin's settings by visiting "Settings" --> "CiviCRM Admin Utilities". In WordPress Multisite, you will find the network settings page at "Network Admin" --> "Settings" --> "CiviCRM Admin Utilities". If you install this plugin in WordPress Multisite and have CiviCRM network-activated, then you should also network-activate this plugin.

<h4>Upgrading from 0.3.3 or earlier</h4>

If you have this plugin installed on WordPress Multisite and this plugin is *not* network activated, then read on.

Prior to version 0.3.4, this plugin stored its settings in the *network options* rather than in the *site's options*. This meant that separate sites *shared* their settings rather than being individually configurable. Version 0.3.4 changed the location where the plugin's settings are stored to be appropriate to the install location and, as a result, each site can be configured differently.

If you are upgrading from 0.3.3, therefore, you may need to review the settings for each site where CiviCRM Admin Utilities is activated.

Version 0.6 introduces further changes to configuration in WordPress Multisite which you should be aware of. When the plugin is activated on any site on the network, Network Administrators will then have access to a Settings Page in WordPress Network Admin at "Network Admin" --> "Settings" --> "CiviCRM Admin Utilities". This means that there are settings at both the site level and the network level.

Network Administrators can now set site defaults for any further activations of this plugin across the network. There are also network-specific settings that can be set. Of particular note are new permissions settings which allow you to remove "administer CiviCRM" capabilities from individual site administrators if that's how you want to configure your network.



== Changelog ==

= 0.6.5 =

* Enable suppression of email notification when a CiviCRM Contact's primary email is changed
* Introduce setting to hide "Manage Groups" menu item
* Compatibility with new menu in CiviCRM 5.12.x

= 0.6.4 =

* Respect CiviCRM permissions when showing link to CiviCRM Contact

= 0.6.3 =

* Adds link to CiviCRM Contact on WordPress user edit screen
* Fixes tab alignment when tabs wrap on to multiple rows
* Allows "Manage Groups" menu item to be restricted or hidden

= 0.6.2 =

* Better management of CiviCRM Domains

= 0.6.1 =

* Fixes various issues with CiviCRM admin theme
* Adds link to CiviCRM Admin Utilities settings page to Shortcuts menu

= 0.6 =

* Public release of changes made in 0.5.4

= 0.5.4 =

* Plugin refactor to separate network functionality and for greater extensibility
* Added option to restrict access to site settings pages

= 0.5.3 =

* Better detection of KAM extension

= 0.5.2 =

* Prevent fatal error when KAM extension not present

= 0.5.1 =

* Maintenance release

= 0.5 =

* Introduce CiviCRM theme for WordPress

= 0.4.2 =

* Allows user-defined custom CiviCRM stylesheets to be disabled on front-end

= 0.4.1 =

* Allows CiviCRM and Shoreditch stylesheets to be disabled on front-end

= 0.4 =

* Fixes location of settings on WordPress Multisite when plugin is not network activated
* Prevent plugin init unless CiviCRM is fully installed
* Fixes detection and styles for the Shoreditch theme

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
