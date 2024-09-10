=== CiviCRM Admin Utilities ===
Contributors: needle, cuny-academic-commons
Donate link: https://www.paypal.me/interactivist
Tags: civicrm, admin, utility, styling, menu
Requires at least: 4.9
Tested up to: 6.6
Stable tag: 1.0.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Utilities for customising CiviCRM's behaviour and appearance in single site and multisite installs.



== Description ==

CiviCRM Admin Utilities modifies CiviCRM's behaviour in single site and multisite installs. It does a number of useful things:

* Supplies a theme for CiviCRM admin screens that is more in keeping with WordPress (see below)
* Modifies the styling of the CiviCRM menu to fix a number of issues
* Fixes the appearance of the Shoreditch extension in WordPress admin
* Fixes the appearance of the WordPress Access Control form where necessary
* Offers options to prevent various CiviCRM Stylesheets from loading on the front-end
* Adds a handy CiviCRM Shortcuts menu to the WordPress Admin Bar
* Allows you to choose which Post Types the CiviCRM shortcode button appears on
* In WordPress multisite, allows you to hide CiviCRM on sub-sites
* Allows you to remove "administer CiviCRM" capabilities from sub-site administrators
* Allows suppression of the "change of email" notification when a CiviCRM Contact's primary email is changed
* Gives an overview of the relationships between Users and Contacts via the "Manage Users" screen

### CiviCRM Admin Theme

Version 0.5 introduces a new theme for CiviCRM admin screens that is more in keeping with WordPress. It can be enabled on the CiviCRM Admin Utilities settings page. Feedback is welcome - please open an issue on the plugin's [GitHub repository](https://github.com/christianwach/civicrm-admin-utilities) if you find any bugs or have suggestions for improvements.

### Requirements

This plugin requires a minimum of *WordPress 4.9* and *CiviCRM 5.39* but recommends you keep up to date with both.

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

= 1.0.7 =

* Adds a setting to enable auto-loading of Afforms outside content

= 1.0.6 =

* Updates accordion styling in Contact Layout Editor
* Reinstates missing "All Contacts" menu item
* Fixes resetting CiviCRM theme to default when saving settings

= 1.0.5 =

* Updates accordion styling in CiviCRM 5.72+
* Better PHP 8.2+ compatibility

= 1.0.4 =

* Updates support for WordPress admin colour schemes
* Updates accordion styling in CiviCRM 5.69+

= 1.0.3 =

* Fixes appearance of various screens in CiviCRM 5.69+
* Prevents fatal error on versions of CiviCRM prior to 5.39

= 1.0.2 =

* Fixes SearchKit summary tabs appearance
* Prevents fatal error on deactivation when CiviCRM not installed

= 1.0.1 =

* Introduces fix for API timezone mismatch
* Uses API4 for retrieving Dedupe Rule Groups if available

= 1.0.0 =

* Theme improvements

