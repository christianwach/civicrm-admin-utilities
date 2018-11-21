<?php /*
================================================================================
CiviCRM Admin Utilities Uninstaller
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====


--------------------------------------------------------------------------------
*/



// Kick out if uninstall not called from WordPress
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit();



// Delete installed flag
delete_site_option( 'civicrm_admin_utilities_installed' );

// Delete settings
delete_site_option( 'civicrm_admin_utilities_settings' );



