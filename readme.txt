=== CiviCRM Admin Utilities ===
Contributors: needle, cuny-academic-commons
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PZSKM8T5ZP3SC
Tags: civicrm, admin, utility, styling, menu
Requires at least: 3.9
Tested up to: 4.1
Stable tag: 0.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

CiviCRM Admin Utilities modifies CiviCRM's behaviour and appearance in single site and multisite installs.



== Description ==

CiviCRM Admin Utilities modifies CiviCRM's behaviour in single site and multisite installs. It does a number of useful things:

* Modifies the styling of the CiviCRM menu to fix a number of issues
* Allows you to choose which Post Types the CiviCRM shortcode button appears on
* In WordPress multisite, allows you to hide CiviCRM on sub-sites

### Requirements

This plugin requires a minimum of *WordPress 3.9* and *CiviCRM 4.6-alpha1*. Please refer to the installation page for configuration instructions as well as for how to use this plugin with versions of CiviCRM prior to 4.6-alpha1.

### Plugin Development

This plugin is in active development. For feature requests and bug reports (or if you're a plugin author and want to contribute) please visit the plugin's [GitHub repository](https://github.com/christianwach/civicrm-admin-utilities).



== Installation ==

1. Extract the plugin archive
1. Upload plugin files to your `/wp-content/plugins/` directory
1. Make sure CiviCRM is activated and properly configured
1. Activate the plugin through the 'Plugins' menu in WordPress

In single-site installs, you can ajust this plugin's settings by visiting "Settings" --> "CiviCRM Admin Utilities". If you install this plugin in multisite and have CiviCRM network-enabled, then you should also network-enable this plugin. You'll then find its settings page at "Network Admin" --> "Settings" --> "CiviCRM Admin Utilities".

This plugin requires a minimum of *WordPress 3.9* and *CiviCRM 4.6-alpha1*. For versions of CiviCRM prior to 4.6-alpha1, this plugin requires the corresponding branch of the [CiviCRM WordPress plugin](https://github.com/civicrm/civicrm-wordpress) plus the custom WordPress.php hook file from the [CiviCRM Hook Tester repo on GitHub](https://github.com/christianwach/civicrm-wp-hook-tester) so that it overrides the built-in CiviCRM file. Please refer to the each repo for further instructions.



== Changelog ==

= 0.2.1 =

Fixes removal of Civi admin menu in multisite

= 0.2 =

First public release

= 0.1 =

Initial release
