<?php
/**
 * Network Settings "Network Settings" metabox Template.
 *
 * Handles markup for the Network Settings "Network Settings" metabox.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.8.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/templates/metaboxes/network-metabox-network-settings.php -->
<?php if ( $this->plugin->is_civicrm_network_activated() ) : ?>

	<table class="form-table">

		<tr>
			<th scope="row"><?php esc_html_e( 'Restrict CiviCRM', 'civicrm-admin-utilities' ); ?></th>
			<td>
				<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_main_site" id="civicrm_admin_utilities_main_site" value="1"<?php checked( 1, $main_site_only ); ?> />
				<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_main_site"><?php esc_html_e( 'Restrict CiviCRM UI elements to main site only.', 'civicrm-admin-utilities' ); ?></label>
				<p class="description"><?php esc_html_e( 'When CiviCRM is network-activated in WordPress Multisite, it will load on every sub-site. This may not be what you want - especially when Multisite uses subdirectories - because CiviCRM makes assumptions about the path to WordPress admin and as a result there are a number of problems with CiviCRM. So check this option to restrict the appearance of the CiviCRM UI elements (as well as access to the CiviCRM Admin Utilities settings page) to the main site only.', 'civicrm-admin-utilities' ); ?></p>
			</td>
		</tr>

	</table>

<?php endif; ?>

<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
<div class="civicrm-restricted"<?php echo $civicrm_restricted; ?>>

	<table class="form-table">

		<tr>
			<th scope="row"><?php esc_html_e( 'Settings Page access', 'civicrm-admin-utilities' ); ?></th>
			<td>
				<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_restrict_settings_access" id="civicrm_admin_utilities_restrict_settings_access" value="1"<?php checked( 1, $restrict_settings_access ); ?> />
				<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_restrict_settings_access"><?php esc_html_e( 'Restrict access to CiviCRM Admin Utilities Settings Pages to Network Admins only.', 'civicrm-admin-utilities' ); ?></label>
				<p class="description"><?php esc_html_e( 'When CiviCRM is activated on one or more individual sites in WordPress Multisite, you may want to restrict who has access to the CiviCRM Admin Utilities Settings Page on each site. This is useful if the individual site administrators on your network should be prevented from changing the settings that Network Admins define for the sites where CiviCRM is activated.', 'civicrm-admin-utilities' ); ?></p>
			</td>
		</tr>

	</table>

	<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
	<div class="settings-restricted"<?php echo $settings_restricted; ?>>

		<table class="form-table">

			<tr>
				<th scope="row"><?php esc_html_e( 'Domain Page access', 'civicrm-admin-utilities' ); ?></th>
				<td>
					<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_restrict_domain_access" id="civicrm_admin_utilities_restrict_domain_access" value="1"<?php checked( 1, $restrict_domain_access ); ?> />
					<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_restrict_domain_access"><?php esc_html_e( 'Restrict access to CiviCRM Admin Utilities Domain Pages to Network Admins only.', 'civicrm-admin-utilities' ); ?></label>
					<p class="description"><?php esc_html_e( 'When CiviCRM is activated on one or more individual sites in WordPress Multisite and you are allowing access to Settings Pages, it is likely that you want to restrict access to the CiviCRM Admin Utilities Domain Page on each site to Network Admins only. If, for some reason, you want to allow the individual site administrators on your network to access the CiviCRM domain settings, uncheck this setting.', 'civicrm-admin-utilities' ); ?></p>
				</td>
			</tr>

		</table>

	</div>

</div>

<table class="form-table">

	<tr>
		<th scope="row"><?php esc_html_e( 'Administer CiviCRM', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_restrict_administer" id="civicrm_admin_utilities_restrict_administer" value="1"<?php checked( 1, $restrict_administer ); ?> />
			<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_restrict_administer"><?php esc_html_e( 'Restrict "Administer CiviCRM" capability to Network Admins only.', 'civicrm-admin-utilities' ); ?></label>
			<p class="description"><?php esc_html_e( 'When CiviCRM is activated on on one or more individual sites in WordPress Multisite, you may want to restrict who has the "Administer CiviCRM" capability to Network Admins only. This is useful if the individual site administrators on your network should be prevented from having "root access" to CiviCRM. You will need CiviCRM 4.7.19+ for this to have an effect.', 'civicrm-admin-utilities' ); ?></p>
		</td>
	</tr>

</table>
