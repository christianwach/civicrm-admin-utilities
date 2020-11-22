<!-- assets/templates/metaboxes/network-metabox-domain-create.php -->
<div class="notice notice-warning inline" style="background-color: #f7f7f7;">
	<p><?php _e( 'If you want a WordPress sub-site to have a separate CiviCRM Domain, create the Domain here before you activate the CiviCRM plugin on that WordPress sub-site.', 'civicrm-admin-utilities' ); ?></p>
</div>

<table class="form-table">
	<tr>
		<th scope="row">
			<label class="civicrm_admin_utilities_settings_label" for="cau_domain_name">
				<?php _e( 'Domain Name', 'civicrm-admin-utilities' ); ?>
			</label>
		</th>
		<td>
			<input type="text" id="cau_domain_name" name="cau_domain_name" class="cau_text_input" value="" />
		</td>
	</tr>
</table>
