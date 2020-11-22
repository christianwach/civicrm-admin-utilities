<!-- assets/templates/metaboxes/site-metabox-domain-edit.php -->
<div class="notice notice-warning inline" style="background-color: #f7f7f7;">
	<p><?php _e( 'Edit this Domain with caution &mdash; it could cause problems if you make edits to a Domain which is not completely new and unused.', 'civicrm-admin-utilities' ); ?><br>
	<strong><?php _e( 'You have been warned.', 'civicrm-admin-utilities' ); ?></strong></p>
</div>

<div class="cau-domain-edit">

	<table class="form-table">

		<tr>
			<th scope="row">
				<label class="civicrm_admin_utilities_settings_label" for="cau_domain_group_select">
					<?php _e( 'Domain Group', 'civicrm-admin-utilities' ); ?>
				</label>
			</th>

			<td>
				<select id="cau_domain_group_select" name="cau_domain_group_select">
					<option value=""><?php _e( 'Select existing Group', 'civicrm-admin-utilities' ); ?></option>
					<?php if ( $metabox['args']['domain_group']['id'] !== 0 ) : ?>
						<option value="<?php echo $metabox['args']['domain_group']['id']; ?>" selected="selected"><?php echo $metabox['args']['domain_group']['name']; ?></option>
					<?php endif; ?>
				</select>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label class="civicrm_admin_utilities_settings_label" for="cau_domain_org_select">
					<?php _e( 'Domain Organisation', 'civicrm-admin-utilities' ); ?>
				</label>
			</th>

			<td>
				<select id="cau_domain_org_select" name="cau_domain_org_select">
					<option value=""><?php _e( 'Select existing Organisation', 'civicrm-admin-utilities' ); ?></option>
					<?php if ( $metabox['args']['domain_org']['id'] !== 0 ) : ?>
						<option value="<?php echo $metabox['args']['domain_org']['id']; ?>" selected="selected"><?php echo $metabox['args']['domain_org']['name']; ?></option>
					<?php endif; ?>
				</select>
			</td>
		</tr>

	</table>

</div>

