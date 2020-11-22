<!-- assets/templates/templates/metaboxes/site-metabox-admin-bar.php -->
<table class="form-table">

	<tr>
		<th scope="row"><?php _e( 'Shortcuts Menu', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_admin_bar" id="civicrm_admin_utilities_admin_bar" value="1"<?php echo $admin_bar; ?> />
			<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_admin_bar"><?php _e( 'Add a CiviCRM Shortcuts Menu to the WordPress admin bar.', 'civicrm-admin-utilities' ); ?></label>
			<p class="description"><?php _e( 'Some people find it helpful to have links directly to CiviCRM components available from the WordPress admin bar.', 'civicrm-admin-utilities' ); ?></p>
		</td>
	</tr>

	<tr>
		<th scope="row"><?php _e( 'Hide "Manage Groups"', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_admin_bar_groups" id="civicrm_admin_utilities_admin_bar_groups" value="1"<?php echo $admin_bar_groups; ?> />
			<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_admin_bar_groups"><?php _e( 'Hide the "Manage Groups" menu item from the CiviCRM Shortcuts Menu.', 'civicrm-admin-utilities' ); ?></label>
			<p class="description"><?php _e( 'There is no permission or capability that can be checked to find out if a user has access to the "Manage Groups" screen. Check this to hide the menu item. More granular permissions can be applied via the <code style="font-style: normal">civicrm_admin_utilities_manage_groups_menu_item</code> filter if they are required, for example, on a per-user basis.', 'civicrm-admin-utilities' ); ?></p>
		</td>
	</tr>

</table>
