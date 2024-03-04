<?php
/**
 * Site "Contacts" metabox Template.
 *
 * Handles markup for the Site "Contacts" metabox.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.8.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/templates/metaboxes/site-metabox-contacts.php -->
<table class="form-table">

	<tr>
		<th scope="row"><?php esc_html_e( 'WordPress User notification', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_email_suppress" id="civicrm_admin_utilities_email_suppress" value="1"<?php checked( 1, $email_suppress ); ?> />
			<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_email_suppress"><?php esc_html_e( 'Suppress the email to the WordPress user.', 'civicrm-admin-utilities' ); ?></label>
			<p class="description"><?php esc_html_e( 'When the primary email for a CiviCRM Contact is changed, CiviCRM updates the email address of the corresponding  WordPress User. This triggers an email to be sent to the User.', 'civicrm-admin-utilities' ); ?></p>
		</td>
	</tr>

	<tr>
		<th scope="row"><?php esc_html_e( 'CiviCRM Contact "Soft Delete"', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_fix_soft_delete" id="civicrm_admin_utilities_fix_soft_delete" value="1"<?php checked( 1, $fix_soft_delete ); ?> />
			<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_fix_soft_delete"><?php esc_html_e( 'Fix the Contact "soft delete" process.', 'civicrm-admin-utilities' ); ?></label>
			<p class="description"><?php esc_html_e( 'When a CiviCRM Contact is initially deleted, CiviCRM does not actually delete the Contact record, but instead flags it as deleted so that it can be "undeleted" if necessary. However, this "soft delete" process deletes the data linking the Contact to the WordPress User (the UFMatch record) and this is lost when the Contact is "Restored from Trash". Checking this option retains this data until the Contact is "hard deleted". Leave unchecked to retain existing behaviour.', 'civicrm-admin-utilities' ); ?></p>
		</td>
	</tr>

</table>
