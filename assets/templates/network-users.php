<!-- assets/templates/network-users.php -->
<div class="wrap">

	<h1><?php _e( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ); ?></h1>

	<div class="updated">
		<?php if ( $this->plugin->is_civicrm_network_activated() ) : ?>
			<p><?php _e( 'CiviCRM is network-activated.', 'civicrm-admin-utilities' ); ?></p>
		<?php else : ?>
			<p><?php _e( 'CiviCRM is not network-activated.', 'civicrm-admin-utilities' ); ?></p>
		<?php endif; ?>
	</div>

	<h2 class="nav-tab-wrapper">
		<a href="<?php echo $urls['settings_network']; ?>" class="nav-tab"><?php _e( 'Network Settings', 'civicrm-admin-utilities' ); ?></a>
		<a href="<?php echo $urls['settings_site']; ?>" class="nav-tab"><?php _e( 'Site Settings', 'civicrm-admin-utilities' ); ?></a>
		<?php

		/**
		 * Allow others to add tabs.
		 *
		 * @since 0.5.4
		 *
		 * @param array $urls The array of subpage URLs.
		 * @param str The key of the active tab in the subpage URLs array.
		 */
		do_action( 'civicrm_admin_utilities_network_nav_tabs', $urls, 'users' );

		?>
	</h2>

	<p><?php _e( 'Network Users', 'civicrm-admin-utilities' ); ?></p>

</div><!-- /.wrap -->
