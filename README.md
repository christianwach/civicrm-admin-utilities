CiviCRM Admin Utilities
=======================

Please note: this is the development repository for *CiviCRM Admin Utilities*. It can be found in [the WordPress Plugin Directory](https://wordpress.org/plugins/civicrm-admin-utilities/), which is the best place to get it from if you're not a developer.

*CiviCRM Admin Utilities* is a WordPress plugin that modifies CiviCRM's behaviour in single site and multisite installs. It does a number of useful things:

* Modifies the styling of the CiviCRM menu to fix a number of issues
* Fixes the appearance of the Shoreditch extension in WordPress admin
* Fixes the appearance of the WordPress Access Control form where necessary
* Offers options to prevent various CiviCRM Stylesheets from loading on the front-end
* Adds a handy CiviCRM Shortcuts menu to the WordPress Admin Bar
* Allows you to choose which Post Types the CiviCRM shortcode button appears on
* In WordPress multisite, allows you to hide CiviCRM on sub-sites

#### Notes ####

This plugin requires a minimum of *WordPress 4.4* and *CiviCRM 4.6*. For versions of CiviCRM prior to 4.6, this plugin requires the corresponding branch of the [CiviCRM WordPress plugin](https://github.com/civicrm/civicrm-wordpress) plus the custom WordPress.php hook file from the [CiviCRM Hook Tester repo on GitHub](https://github.com/christianwach/civicrm-wp-hook-tester) so that it overrides the built-in CiviCRM file. Please refer to the each repo for further instructions.

If you have installed the Shoreditch extension for CiviCRM, then this plugin does its best to make it compatible with WordPress. Unfortunately, Shoreditch version 0.1-alpha25 now makes it very difficult to override the margin applied to the body tag on the front end of your site, so you will have to do this yourself. See [this PR for details](https://github.com/civicrm/org.civicrm.shoreditch/pull/291).

#### Installation ####

There are two ways to install from GitHub:

###### ZIP Download ######

If you have downloaded *CiviCRM Admin Utilities* as a ZIP file from the GitHub repository, do the following to install and activate the plugin and theme:

1. Unzip the .zip file and, if needed, rename the enclosing folder so that the plugin's files are located directly inside `/wp-content/plugins/civicrm-admin-utilities`
2. Activate the plugin (in multisite, network activate)
3. You are done!

###### git clone ######

If you have cloned the code from GitHub, it is assumed that you know what you're doing.

#### Setup ####

In single-site installs, you can adjust this plugin's settings by visiting "Settings" --> "CiviCRM Admin Utilities". If you install this plugin in multisite and have CiviCRM network-enabled, then you should also network-enable this plugin. You'll then find its settings page at "Network Admin" --> "Settings" --> "CiviCRM Admin Utilities".
