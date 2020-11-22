<!-- assets/templates/templates/metaboxes/site-metabox-appearance.php -->
<p><?php _e( 'Checking these options applies styles that make CiviCRM Admin pages look better. If you only want to fix the appearance of the CiviCRM Menu and keep the default CiviCRM Admin theme, then check the box for "CiviCRM Menu" and leave "CiviCRM Admin Theme" unchecked.', 'civicrm-admin-utilities' ); ?></p>

<table class="form-table">

	<tr>
		<th scope="row"><?php _e( 'CiviCRM Dashboard Title', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_dashboard_title" id="civicrm_admin_utilities_dashboard_title" value="1"<?php echo $dashboard_title; ?> />
			<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_dashboard_title"><?php _e( 'Make the CiviCRM Dashboard Title more welcoming.', 'civicrm-admin-utilities' ); ?></label>
			<p class="description"><?php _e( 'Checking this alters "CiviCRM Home" to become "Hi FirstName, welcome to CiviCRM".', 'civicrm-admin-utilities' ); ?><br>
			<?php _e( 'The "civicrm_admin_utilities_dashboard_title" filter can be used to modify this further if required.', 'civicrm-admin-utilities' ); ?></p>
		</td>
	</tr>

	<tr>
		<th scope="row"><?php _e( 'CiviCRM Menu', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_menu" id="civicrm_admin_utilities_menu" value="1"<?php echo $prettify_menu; ?> />
			<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_menu"><?php _e( 'Apply style fixes to the CiviCRM menu.', 'civicrm-admin-utilities' ); ?></label>
		</td>
	</tr>

	<tr>
		<th scope="row"><?php _e( 'CiviCRM Admin Theme', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_styles_admin" id="civicrm_admin_utilities_styles_admin" value="1"<?php echo $admin_css; ?> />
			<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_styles_admin"><?php _e( 'Enable the CiviCRM Admin Utilities theme.', 'civicrm-admin-utilities' ); ?></label>
			<div class="theme-compare-wrapper theme-compare-dashboard" style="margin: 1em 0 0.4em 0;<?php echo $theme_preview; ?>">
				<div id="theme-compare-dashboard" class="twentytwenty-container" style="max-width: 720px;">
					<img src="<?php echo plugins_url( 'assets/images/civicrm-dashboard.jpg', CIVICRM_ADMIN_UTILITIES_FILE ); ?>">
					<img src="<?php echo plugins_url( 'assets/images/civicrm-dashboard-cau.jpg', CIVICRM_ADMIN_UTILITIES_FILE ); ?>">
				</div>
			</div>
		</td>
	</tr>

</table>
