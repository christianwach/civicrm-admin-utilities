<?php
/**
 * Site Settings Domain tab "Edit Domain" metabox Template.
 *
 * Handles markup for the Site Settings Domain tab "Edit Domain" metabox.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.8.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $civicrm_setting, $civicrm_paths;

?>
<!-- assets/templates/metaboxes/site-metabox-domain-edit.php -->
<div class="notice notice-warning inline">
	<p><?php esc_html_e( 'Edit this Domain with caution &mdash; it could cause problems if you make edits to a Domain which is not completely new and unused.', 'civicrm-admin-utilities' ); ?><br>
	<strong><?php esc_html_e( 'You have been warned.', 'civicrm-admin-utilities' ); ?></strong></p>
</div>

<div class="cau-domain-edit">

	<table class="form-table">

		<tr>
			<th scope="row">
				<label class="civicrm_admin_utilities_settings_label" for="cau_domain_mapped">
					<?php esc_html_e( 'WordPress Site', 'civicrm-admin-utilities' ); ?>
				</label>
			</th>
			<td>
				<input type="checkbox" id="cau_domain_mapped" name="cau_domain_mapped" value="1"<?php checked( 1, $metabox['args']['domain_mapped'] ); ?>> <label class="civicrm_admin_utilities_settings_label" for="cau_domain_mapped"><?php esc_html_e( 'Assign this Domain to the current WordPress Site', 'civicrm-admin-utilities' ); ?></label>
				<p class="description"><?php esc_html_e( 'You can bulk apply these mappings on the "Domains" tab of the CiviCRM Admin Utilities Network Settings screen.', 'civicrm-admin-utilities' ); ?><br>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label class="civicrm_admin_utilities_settings_label" for="cau_domain_group_select">
					<?php esc_html_e( 'Domain Group', 'civicrm-admin-utilities' ); ?>
				</label>
			</th>
			<td>
				<select id="cau_domain_group_select" name="cau_domain_group_select" data-security="<?php echo esc_attr( wp_create_nonce( 'cau_domain_group' ) ); ?>">
					<option value=""><?php esc_html_e( 'Select existing Group', 'civicrm-admin-utilities' ); ?></option>
					<?php if ( 0 !== $metabox['args']['domain_group']['id'] ) : ?>
						<option value="<?php echo esc_attr( $metabox['args']['domain_group']['id'] ); ?>" selected="selected"><?php echo esc_html( $metabox['args']['domain_group']['name'] ); ?></option>
					<?php endif; ?>
				</select>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label class="civicrm_admin_utilities_settings_label" for="cau_domain_org_select">
					<?php esc_html_e( 'Domain Organisation', 'civicrm-admin-utilities' ); ?>
				</label>
			</th>
			<td>
				<select id="cau_domain_org_select" name="cau_domain_org_select" data-security="<?php echo esc_attr( wp_create_nonce( 'cau_domain_org' ) ); ?>">
					<option value=""><?php esc_html_e( 'Select existing Organisation', 'civicrm-admin-utilities' ); ?></option>
					<?php if ( 0 !== $metabox['args']['domain_org']['id'] ) : ?>
						<option value="<?php echo esc_attr( $metabox['args']['domain_org']['id'] ); ?>" selected="selected"><?php echo esc_html( $metabox['args']['domain_org']['name'] ); ?></option>
					<?php endif; ?>
				</select>
			</td>
		</tr>

		<tr class="cau_domain_name_selector" style="display: none;">
			<th scope="row">
				<label class="civicrm_admin_utilities_settings_label" for="cau_domain_name_select">
					<?php esc_html_e( 'Domain', 'civicrm-admin-utilities' ); ?>
				</label>
			</th>
			<td>
				<select id="cau_domain_name_select" name="cau_domain_name_select">
					<option value="keep" selected="selected"><?php esc_html_e( 'Keep current name', 'civicrm-admin-utilities' ); ?></option>
					<option value="overwrite"><?php esc_html_e( 'Use name of new Domain Organisation', 'civicrm-admin-utilities' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Choose whether to keep the current name of the Domain or overwrite with the name of the new Domain Organisation.', 'civicrm-admin-utilities' ); ?></p>
			</td>
		</tr>

	</table>

</div>
