<?php
/**
 * Uninstaller.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.1
 */

// Kick out if uninstall not called from WordPress.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Delete legacy installed flag.
delete_site_option( 'civicrm_admin_utilities_installed' );

// Delete version.
delete_site_option( 'civicrm_admin_utilities_version' );

// Delete settings.
delete_site_option( 'civicrm_admin_utilities_settings' );

// TODO: Maybe delete settings for each site.
