<?php
/**
 * Multidomain Edit Domain metabox Template.
 *
 * Handles markup for the Multidomain Edit Domain metabox.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.8.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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

	</table>

</div>
