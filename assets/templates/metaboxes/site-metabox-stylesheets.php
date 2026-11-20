<?php
/**
 * Site "Stylesheets" metabox Template.
 *
 * Handles markup for the Site "Stylesheets" metabox.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.8.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/templates/metaboxes/site-metabox-stylesheets.php -->
<p><?php esc_html_e( 'This section allows you to configure how various CiviCRM stylesheets are loaded on your website. This is useful if you have created custom styles for CiviCRM in your theme, for example. By default, this plugin prevents the CiviCRM menu stylesheet from loading on the front-end, since the CiviCRM menu itself is only ever present in WordPress admin.', 'civicrm-admin-utilities' ); ?></p>

<table class="form-table">

	<tr>
		<th scope="row"><?php esc_html_e( 'Default CiviCRM stylesheet', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_styles_default" id="civicrm_admin_utilities_styles_default" value="1"<?php checked( 1, $default_css ); ?> />
			<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_styles_default"><?php esc_html_e( 'Prevent the default CiviCRM stylesheet (civicrm.css) from loading on Public Pages.', 'civicrm-admin-utilities' ); ?></label>
		</td>
	</tr>

	<tr>
		<th scope="row"><?php esc_html_e( 'CiviCRM Menu stylesheet', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_styles_nav" id="civicrm_admin_utilities_styles_nav" value="1"<?php checked( 1, $navigation_css ); ?> />
			<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_styles_nav"><?php esc_html_e( 'Prevent the CiviCRM menu stylesheet (civicrmNavigation.css) from loading on Public Pages.', 'civicrm-admin-utilities' ); ?></label>
		</td>
	</tr>

	<?php if ( false === $shoreditch ) : ?>

		<tr>
			<th scope="row"><?php esc_html_e( 'Custom Stylesheet on Public Pages', 'civicrm-admin-utilities' ); ?></th>
			<td>
				<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_styles_custom" id="civicrm_admin_utilities_styles_custom" value="1"<?php checked( 1, $custom_css ); ?> />
				<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_styles_custom"><?php esc_html_e( 'Prevent the user-defined CiviCRM custom stylesheet from loading on Public Pages.', 'civicrm-admin-utilities' ); ?></label>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Custom Stylesheet in CiviCRM Admin', 'civicrm-admin-utilities' ); ?></th>
			<td>
				<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_styles_custom_public" id="civicrm_admin_utilities_styles_custom_public" value="1"<?php checked( 1, $custom_public_css ); ?> />
				<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_styles_custom_public"><?php esc_html_e( 'Prevent the user-defined CiviCRM custom stylesheet from loading in CiviCRM Admin.', 'civicrm-admin-utilities' ); ?></label>
			</td>
		</tr>

	<?php else : ?>

		<tr>
			<th scope="row"><?php esc_html_e( 'Shoreditch stylesheet', 'civicrm-admin-utilities' ); ?></th>
			<td>
				<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_styles_shoreditch" id="civicrm_admin_utilities_styles_shoreditch" value="1"<?php checked( 1, $shoreditch_css ); ?> />
				<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_styles_shoreditch"><?php esc_html_e( 'Prevent the Shoreditch extension stylesheet (civicrm-custom.css) from loading on Public Pages.', 'civicrm-admin-utilities' ); ?></label>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Shoreditch Bootstrap stylesheet', 'civicrm-admin-utilities' ); ?></th>
			<td>
				<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_styles_bootstrap" id="civicrm_admin_utilities_styles_bootstrap" value="1"<?php checked( 1, $bootstrap_css ); ?> />
				<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_styles_bootstrap"><?php esc_html_e( 'Prevent the Shoreditch extension Bootstrap stylesheet (bootstrap.css) from loading on Public Pages.', 'civicrm-admin-utilities' ); ?></label>
			</td>
		</tr>

	<?php endif; ?>

</table>

