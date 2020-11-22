<!-- assets/templates/templates/metaboxes/site-metabox-access.php -->
<table class="form-table">

	<tr>
		<th scope="row"><?php _e( 'Hide CiviCRM', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_hide_civicrm" id="civicrm_admin_utilities_hide_civicrm" value="1"<?php echo $hide_civicrm; ?> /><label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_hide_civicrm"><?php _e( 'Hide CiviCRM on this site.', 'civicrm-admin-utilities' ); ?></label>
			<p class="description"><?php _e( 'In multisite, you may not want users of this site to be able to access CiviCRM easily. If that is the case, check this and CiviCRM will be hidden from view.', 'civicrm-admin-utilities' ); ?></p>

		</td>
	</tr>

</table>
