<?php
/**
 * Site "Fixes" metabox Template.
 *
 * Handles markup for the Site "Fixes" metabox.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 1.0.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/templates/metaboxes/site-metabox-fixes.php -->
<table class="form-table">

	<tr>
		<th scope="row"><?php esc_html_e( 'API timezone', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_fix_api_timezone" id="civicrm_admin_utilities_fix_api_timezone" value="1"<?php checked( 1, $fix_api_timezone ); ?> /><label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_fix_api_timezone"><?php esc_html_e( 'Fix the CiviCRM API timezone.', 'civicrm-admin-utilities' ); ?></label>
			<p class="description"><?php esc_html_e( 'When calling the CiviCRM API, the PHP timezone is normally expected be the same timezone as defined in WordPress. Checking this ensures that default datetimes written to the CiviCRM database are in the correct timezone for the site. In the unlikely event that your workflow conflicts with the fix, uncheck this to disable it.', 'civicrm-admin-utilities' ); ?></p>
		</td>
	</tr>

</table>
