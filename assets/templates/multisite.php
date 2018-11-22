<!-- assets/templates/multisite.php -->
<div class="wrap">

	<h1 class="nav-tab-wrapper">
		<a href="<?php echo $urls['settings']; ?>" class="nav-tab"><?php _e( 'Settings', 'civicrm-admin-utilities' ); ?></a>
		<a href="<?php echo $urls['multisite']; ?>" class="nav-tab nav-tab-active"><?php _e( 'Multisite', 'civicrm-admin-utilities' ); ?></a>
	</h1>

	<?php

	// If we've updated, show message
	if ( $this->is_network_activated() AND isset( $_GET['updated'] ) AND $_GET['updated'] == 'true' ) {
		echo '<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible">' .
				'<p><strong>' . __( 'Settings saved.', 'civicrm-admin-utilities' ) . '</strong></p>' .
				'<button type="button" class="notice-dismiss">' .
					'<span class="screen-reader-text">' . __( 'Dismiss this notice.', 'civicrm-admin-utilities' ) . '</span>' .
				'</button>' .
			 '</div>';
	}

	?>

	<form method="post" id="civicrm_admin_utilities_multisite_form" action="<?php echo $this->admin_form_url_get(); ?>">

		<?php wp_nonce_field( 'civicrm_admin_utilities_multisite_action', 'civicrm_admin_utilities_multisite_nonce' ); ?>

		<h3><?php _e( 'Multisite Options', 'civicrm-admin-utilities' ); ?></h3>

		<p><?php _e( 'In multisite, CiviCRM currently loads on every sub-site. This may not be what you want - especially when multisite uses subdirectories - because CiviCRM makes assumptions about the path to WordPress admin and as a result the CiviCRM menu always bounces users to the main site. Furthermore, public-facing pages will not distinguish between sub-sites and the main site and will always appear on the main site. So check this option to restrict the appearance of the CiviCRM menu item and CiviCRM shortcode button to the main site only.', 'civicrm-admin-utilities' ); ?></p>

		<table class="form-table">

			<tr>
				<th scope="row"><?php _e( 'Restrict CiviCRM', 'civicrm-admin-utilities' ); ?></th>
				<td>
					<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_main_site" id="civicrm_admin_utilities_main_site" value="1"<?php echo $main_site_only; ?> />
					<label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_main_site"><?php _e( 'Restrict CiviCRM to main site only.', 'civicrm-admin-utilities' ); ?></label>
				</td>
			</tr>

		</table>

		<p class="submit">
			<input class="button-primary" type="submit" id="civicrm_admin_utilities_multisite_submit" name="civicrm_admin_utilities_multisite_submit" value="<?php _e( 'Save Changes', 'civicrm-admin-utilities' ); ?>" />
		</p>

	</form>

</div><!-- /.wrap -->



