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

?>
<!-- assets/templates/metaboxes/site-metabox-domain-edit.php -->
<div class="notice notice-warning inline">
	<p>
		<?php

		printf(
			/* translators: 1: The opening <code> tag, 2: The closing </code> tag. */
			esc_html__( 'In an ordinary install of CiviCRM, these paths and URLs are defined in your %1$scivicrm.setting.php%2$s file. With multiple CiviCRM Domains, these need to be loaded dynamically for each Domain. Make sure you set these paths and URLs appropriately for this Domain to work correctly.', 'civicrm-admin-utilities' ),
			'<code>',
			'</code>'
		);

		?>
	</p>
</div>

<div class="cau-domain-edit">

	<table class="form-table">
		<tr>
			<th scope="row">
				<label class="civicrm_admin_utilities_settings_label" for="cau_civicrm_core_url">
					<?php esc_html_e( 'CiviCRM Core Directory URL', 'civicrm-admin-utilities' ); ?>
				</label>
			</th>
			<td>
				<input type="text" id="cau_civicrm_core_url" name="cau_civicrm_core_url" class="cau_text_input widefat" value="<?php echo esc_attr( $metabox['args']['paths']['core_url'] ); ?>" />
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label class="civicrm_admin_utilities_settings_label" for="cau_civicrm_extensions_dir">
					<?php esc_html_e( 'Extensions Directory Path', 'civicrm-admin-utilities' ); ?>
				</label>
			</th>
			<td>
				<input type="text" id="cau_civicrm_extensions_dir" name="cau_civicrm_extensions_dir" class="cau_text_input widefat" value="<?php echo esc_attr( $metabox['args']['paths']['extensions_path'] ); ?>" />
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label class="civicrm_admin_utilities_settings_label" for="cau_civicrm_extensions_url">
					<?php esc_html_e( 'Extensions Directory URL', 'civicrm-admin-utilities' ); ?>
				</label>
			</th>
			<td>
				<input type="text" id="cau_civicrm_extensions_url" name="cau_civicrm_extensions_url" class="cau_text_input widefat" value="<?php echo esc_attr( $metabox['args']['paths']['extensions_url'] ); ?>" />
			</td>
		</tr>

	</table>

</div>
