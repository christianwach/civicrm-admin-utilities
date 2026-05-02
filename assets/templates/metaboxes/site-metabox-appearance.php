<?php
/**
 * Site "Appearance" metabox Template.
 *
 * Handles markup for the Site "Appearance" metabox.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.8.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/templates/metaboxes/site-metabox-appearance.php -->
<p><?php esc_html_e( 'These options apply modifications that improve the appearance of CiviCRM Backend screens.', 'civicrm-admin-utilities' ); ?></p>

<table class="form-table">

	<tr>
		<th scope="row"><?php esc_html_e( 'CiviCRM Dashboard Title', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_dashboard_title" id="civicrm_admin_utilities_dashboard_title" value="1"<?php checked( 1, $dashboard_title ); ?> />
			<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_dashboard_title"><?php esc_html_e( 'Make the CiviCRM Dashboard Title more welcoming.', 'civicrm-admin-utilities' ); ?></label>
			<p class="description"><?php esc_html_e( 'Checking this alters "CiviCRM Home" to become "Hi FirstName, welcome to CiviCRM".', 'civicrm-admin-utilities' ); ?><br>
			<?php

			echo sprintf(
				/* translators: %s: The name of the filter wrapped in a <code> tag. */
				esc_html__( 'The %s filter can be used to modify this further if required.', 'civicrm-admin-utilities' ),
				'<code style="font-style: normal">civicrm_admin_utilities_dashboard_title</code>'
			);

			?>
			</p>
		</td>
	</tr>

	<tr>
		<th scope="row"><?php esc_html_e( 'CiviCRM Menu', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_menu" id="civicrm_admin_utilities_menu" value="1"<?php checked( 1, $prettify_menu ); ?> />
			<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_menu"><?php esc_html_e( 'Apply WordPress styles to the CiviCRM Menu.', 'civicrm-admin-utilities' ); ?></label>
			<p class="description"><?php esc_html_e( 'Checking this applies styles from the WordPress "Administration Colour Scheme" to the CiviCRM Menu regardless of which CiviCRM "Backend Theme" is active. Note that this will not apply styles when the "Menubar position" option is set to "Above content area" in CiviCRM.', 'civicrm-admin-utilities' ); ?><br>
		</td>
	</tr>

	<tr>
		<th scope="row"><?php esc_html_e( 'CiviCRM Backend Theme', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_styles_admin" id="civicrm_admin_utilities_styles_admin" value="1"<?php checked( 1, $admin_css ); ?> />
			<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_styles_admin"><?php esc_html_e( 'Enable the CiviCRM Admin Utilities "Radstock" Theme.', 'civicrm-admin-utilities' ); ?></label>
			<p class="description"><?php esc_html_e( 'Note that although the Radstock Theme can be chosen as the "Frontend Theme" in CiviCRM, none of its styles will be loaded on Public Pages.', 'civicrm-admin-utilities' ); ?><br>
			<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
			<div class="theme-compare-wrapper theme-compare-dashboard" style="margin: 1em 0 0.4em 0;<?php echo $theme_preview; ?>">
				<div id="theme-compare-dashboard" class="twentytwenty-container" style="max-width: 720px;">
					<img src="<?php echo esc_url( plugins_url( 'assets/images/civicrm-dashboard.jpg', CIVICRM_ADMIN_UTILITIES_FILE ) ); ?>">
					<img src="<?php echo esc_url( plugins_url( 'assets/images/civicrm-dashboard-cau.jpg', CIVICRM_ADMIN_UTILITIES_FILE ) ); ?>">
				</div>
			</div>
		</td>
	</tr>

</table>
