<?php
/**
 * Site "Access" metabox Template.
 *
 * Handles markup for the Site "Access" metabox.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.8.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/templates/metaboxes/site-metabox-access.php -->
<table class="form-table">

	<tr>
		<th scope="row"><?php esc_html_e( 'Hide CiviCRM', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_hide_civicrm" id="civicrm_admin_utilities_hide_civicrm" value="1"<?php checked( 1, $hide_civicrm ); ?> /><label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_hide_civicrm"><?php esc_html_e( 'Hide CiviCRM on this site.', 'civicrm-admin-utilities' ); ?></label>
			<p class="description"><?php esc_html_e( 'In Multisite, you may not want users of this site to be able to access CiviCRM easily. If that is the case, check this and CiviCRM will be hidden from view.', 'civicrm-admin-utilities' ); ?></p>

		</td>
	</tr>

</table>
