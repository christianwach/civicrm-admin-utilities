<!-- assets/templates/multisite.php -->
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
		do_action( 'civicrm_admin_utilities_settings_nav_tabs', $urls, 'multisite' );

		?>
	</h2>

	<?php if ( isset( $_GET['updated'] ) AND $_GET['updated'] == 'true' ) : ?>
		<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible">
			<p><strong><?php _e( 'Settings saved.', 'civicrm-admin-utilities' ); ?></strong></p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text"><?php _e( 'Dismiss this notice.', 'civicrm-admin-utilities' ); ?></span>
			</button>
		</div>
	<?php endif; ?>

	<form method="post" id="civicrm_admin_utilities_multisite_form" action="<?php echo $this->page_submit_url_get(); ?>">

		<?php wp_nonce_field( 'civicrm_admin_utilities_multisite_action', 'civicrm_admin_utilities_multisite_nonce' ); ?>

		<h3><?php _e( 'Multisite Options', 'civicrm-admin-utilities' ); ?></h3>

		<p><?php _e( 'Create or edit the CiviCRM Domain here.', 'civicrm-admin-utilities' ); ?></p>

		<table class="form-table">

			<tr>
				<th scope="row"><?php _e( 'Domain ID', 'civicrm-admin-utilities' ); ?></th>
				<td>
					<p><?php echo $domain_id; ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php _e( 'Domain Group ID', 'civicrm-admin-utilities' ); ?></th>
				<td>
					<p><?php echo $domain_group_id; ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php _e( 'Domain Org ID', 'civicrm-admin-utilities' ); ?></th>
				<td>
					<p><?php echo $domain_org_id; ?></p>
				</td>
			</tr>

		</table>

		<p class="submit">
			<input class="button-primary" type="submit" id="civicrm_admin_utilities_multisite_submit" name="civicrm_admin_utilities_multisite_submit" value="<?php _e( 'Save Changes', 'civicrm-admin-utilities' ); ?>" />
		</p>

	</form>

</div><!-- /.wrap -->



