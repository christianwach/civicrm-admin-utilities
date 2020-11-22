<!-- assets/templates/templates/metaboxes/site-metabox-access-form.php -->
<p><?php _e( 'Checking this option fixes the appearance of the WordPress Access Control form.', 'civicrm-admin-utilities' ); ?></p>

<table class="form-table">

	<tr>
		<th scope="row"><?php _e( 'Fix WordPress Access Control form', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_access" id="civicrm_admin_utilities_access" value="1"<?php echo $prettify_access; ?> />
			<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_access"><?php _e( 'Fix the appearance of the WordPress Access Control form.', 'civicrm-admin-utilities' ); ?></label>
		</td>
	</tr>

</table>
