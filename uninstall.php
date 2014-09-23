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



// kick out if uninstall not called from WordPress
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) exit();



// delete installed flag
delete_site_option( 'civicrm_modifier_installed' );

// delete settings
delete_site_option( 'civicrm_modifier_settings' );



