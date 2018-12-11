<!-- assets/templates/site-multidomain.php -->
<div class="wrap">

	<h1><?php _e( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ); ?></h1>

	<h2 class="nav-tab-wrapper">
		<a href="<?php echo $urls['settings']; ?>" class="nav-tab"><?php _e( 'Settings', 'civicrm-admin-utilities' ); ?></a>
		<?php

		/**
		 * Allow others to add tabs.
		 *
		 * @since 0.5.4
		 *
		 * @param array $urls The array of subpage URLs.
		 * @param str The key of the active tab in the subpage URLs array.
		 */
		do_action( 'civicrm_admin_utilities_settings_nav_tabs', $urls, 'multidomain' );

		?>
	</h2>

	<h3><?php _e( 'CiviCRM Domain Information', 'civicrm-admin-utilities' ); ?></h3>

	<?php if ( ! $multisite ) : ?>
		<div class="updated error">
			<p><?php _e( 'It is recommended that you install and activate the <a href="https://civicrm.org/extensions/multisite-permissioning" target="_blank">CiviCRM Multisite</a> extension to work with multiple Domains in CiviCRM.', 'civicrm-admin-utilities' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! $enabled ) : ?>
		<div class="notice notice-warning">
			<p><?php echo sprintf( __( 'Multisite is not enabled on this CiviCRM Domain. Change <a href="%s">the setting in CiviCRM</a> to enable it.', 'civicrm-admin-utilities' ), $multisite_url ); ?></p>
		</div>
	<?php endif; ?>

	<ul>

		<li><?php echo sprintf(
			__( 'The current domain for this site is: "%1$s" (ID: %2$s)', 'civicrm-admin-utilities' ),
			'<span class="cau_domain_name">' . $domain['name'] . '</span>',
			'<span class="cau_domain_id">' . $domain['id'] . '</span>'
		); ?></li>

		<li><?php echo sprintf(
			__( 'The current domain group for this site is: "%1$s" (ID: %2$s)', 'civicrm-admin-utilities' ),
			'<span class="cau_domain_group_name">' . $domain_group['name'] . '</span>',
			'<span class="cau_domain_group_id">' . $domain_group['id'] . '</span>'
		); ?></li>

		<li><?php echo sprintf(
			__( 'The current domain organisation for this site is: "%1$s" (ID: %2$s)', 'civicrm-admin-utilities' ),
			'<span class="cau_domain_org_name">' . $domain_org['name'] . '</span>',
			'<span class="cau_domain_org_id">' . $domain_org['id'] . '</span>'
		); ?></li>

	</ul>

	<?php if ( $multisite ) : ?>

		<hr />

		<form method="post" id="civicrm_admin_utilities_multidomain_form" action="<?php echo $this->page_submit_url_get(); ?>">

			<?php wp_nonce_field( 'civicrm_admin_utilities_multidomain_action', 'civicrm_admin_utilities_multidomain_nonce' ); ?>

			<h3><?php echo sprintf ( __( 'Edit "%s" Domain', 'civicrm-admin-utilities' ), $domain['name'] ); ?></h3>

			<p><?php _e( 'Edit this Domain with caution - it could cause problems if you make edits to a Domain which is not completely new and unused. You have been warned.', 'civicrm-admin-utilities' ); ?></p>

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
								<?php if ( $domain_group['id'] !== 0 ) : ?>
									<option value="<?php echo $domain_group['id']; ?>" selected="selected"><?php echo $domain_group['name']; ?></option>
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
								<?php if ( $domain_org['id'] !== 0 ) : ?>
									<option value="<?php echo $domain_org['id']; ?>" selected="selected"><?php echo $domain_org['name']; ?></option>
								<?php endif; ?>
							</select>
						</td>
					</tr>

				</table>

			</div>

			<div class="cau-domain-submit">
				<p class="submit">
					<input class="button-primary" type="submit" id="civicrm_admin_utilities_multidomain_submit" name="civicrm_admin_utilities_multidomain_submit" value="<?php _e( 'Save Changes', 'civicrm-admin-utilities' ); ?>" />
				</p>
			</div>

		</form>

	<?php endif; ?>

</div><!-- /.wrap -->
