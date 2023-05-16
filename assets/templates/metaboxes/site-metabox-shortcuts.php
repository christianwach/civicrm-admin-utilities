<?php
/**
 * Site "Shortcuts" metabox Template.
 *
 * Handles markup for the Site "Shortcuts" metabox.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.8.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/templates/metaboxes/site-metabox-misc.php -->
<p><?php esc_html_e( 'Shortcuts to some CiviCRM admin pages that have no menu items.', 'civicrm-admin-utilities' ); ?></p>

<ul>
	<li>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=CiviCRM&q=' . rawurlencode( 'civicrm/menu/rebuild' ) . '?reset=1' ) ); ?>"><?php esc_html_e( 'Rebuild the CiviCRM menu', 'civicrm-admin-utilities' ); ?></a>
	</li>
	<li>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=CiviCRM&q=' . rawurlencode( 'civicrm/menu/rebuild' ) . '?reset=1&triggerRebuild=1' ) ); ?>"><?php esc_html_e( 'Rebuild the CiviCRM database triggers', 'civicrm-admin-utilities' ); ?></a>
	</li>
	<li>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=CiviCRM&q=' . rawurlencode( 'civicrm/upgrade' ) . '&reset=1' ) ); ?>"><?php esc_html_e( 'Upgrade CiviCRM', 'civicrm-admin-utilities' ); ?></a><br>
		<span class="description"><?php esc_html_e( 'Please note: you need to update the CiviCRM plugin folder first.', 'civicrm-admin-utilities' ); ?></span>
	</li>
	<li>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=CiviCRM&q=' . rawurlencode( 'civicrm/admin/extensions/upgrade' ) . '&reset=1' ) ); ?>"><?php esc_html_e( 'Execute CiviCRM Extension updates', 'civicrm-admin-utilities' ); ?></a><br>
		<span class="description"><?php esc_html_e( 'Please note: you need to update each of the CiviCRM extensions first.', 'civicrm-admin-utilities' ); ?></span>
	</li>
</ul>
