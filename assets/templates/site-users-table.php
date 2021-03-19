<!-- assets/templates/site-users-table.php -->
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
		do_action( 'civicrm_admin_utilities_settings_nav_tabs', $urls, 'users' );

		?>
	</h2>

	<?php if ( ! empty( $messages ) ) : ?>
		<div class="<?php echo ( ! empty( $_REQUEST['error'] ) ) ? 'error' : 'updated'; ?> notice is-dismissible">
			<p><?php echo implode( "<br/>\n", $messages ); ?></p>
		</div>
	<?php endif; ?>

	<?php $this->user_table->views(); ?>

	<form id="civicrm-au-users-form" action="<?php echo $this->page_submit_url_get(); ?>" method="get">

		<?php

		/**
		 * Allow others to add markup (like the search query) at the top of the form.
		 *
		 * @since 0.9
		 */
		do_action( 'cau/single_users/user_table/form/start' );

		?>

		<?php if ( ! empty( $_REQUEST['s'] ) ) : ?>
			<span class="subtitle"><?php printf(
				/* translators: %s: Search query. */
				__( 'Search results for: %s', 'civicrm-admin-utilities' ),
				'<strong>' . wp_html_excerpt( esc_html( stripslashes( $_REQUEST['s'] ) ), 50 ) . '</strong>'
			); ?></span>
		<?php endif; ?>

		<?php $this->user_table->search_box( __( 'Search Users', 'civicrm-admin-utilities' ), 'civicrm_au_users' ); ?>

		<input type="hidden" name="page" value="<?php echo esc_attr( $this->users_page_slug ); ?>" />

		<?php $this->user_table->display(); ?>

		<?php

		/**
		 * Allow others to add markup at the bottom of the form.
		 *
		 * @since 0.9
		 */
		do_action( 'cau/single_users/user_table/form/end' );

		?>

	</form>

</div><!-- /.wrap -->
