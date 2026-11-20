<?php
/**
 * Site Users Table Help Template.
 *
 * Handles markup for the Site Users Table Help.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.9
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/site-users-table-help.php -->
<p><?php esc_html_e( 'This screen helps you to see the relationships between WordPress Users and CiviCRM Contacts. If there are too many columns for your liking, you can manage which ones you see in the "Screen Options" tab.', 'civicrm-admin-utilities' ); ?></p>

<p><?php esc_html_e( 'If you are using BuddyPress and allowing Users to register, then you may see a mismatch between the count for "All Users" and the count for "In CiviCRM" if there are Users who are currently being held in the BuddyPress signup queue.', 'civicrm-admin-utilities' ); ?></p>
