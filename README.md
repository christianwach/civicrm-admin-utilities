CiviCRM Admin Utilities
=======================

*CiviCRM Admin Utilities* is a WordPress plugin that modifies CiviCRM's behaviour in single site and multisite installs. It does a number of useful things:

* Modifies the styling of the CiviCRM menu to fix a number of issues
* Allows you to choose which Post Types the CiviCRM shortcode button appears on
* In WordPress multisite, allows you to hide CiviCRM on sub-sites

#### Notes ####

This plugin has been developed using a minimum of *WordPress 3.9* and *CiviCRM 4.4.5*. For versions of *CiviCRM* prior to 4.6-alpha1, this plugin requires the corresponding branch of the [CiviCRM WordPress plugin](https://github.com/civicrm/civicrm-wordpress) plus the custom WordPress.php hook file from the [CiviCRM Hook Tester repo on GitHub](https://github.com/christianwach/civicrm-wp-hook-tester) so that it overrides the built-in *CiviCRM* file. Please refer to the each repo for further instructions.

#### Installation ####

There are two ways to install from GitHub:

###### ZIP Download ######

If you have downloaded *CiviCRM Admin Utilities* as a ZIP file from the GitHub repository, do the following to install and activate the plugin and theme:

1. Unzip the .zip file and, if needed, rename the enclosing folder so that the plugin's files are located directly inside `/wp-content/plugins/civicrm-admin-utilities`
2. Activate the plugin (in multisite, network activate)
3. You are done!


###### git clone ######

If you have cloned the code from GitHub, it is assumed that you know what you're doing.
