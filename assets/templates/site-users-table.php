<?php
/**
 * Site Users Table Template.
 *
 * Handles markup for the Site Users Table.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.9
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/site-users-table.php -->
<div class="wrap">

	<h1><?php esc_html_e( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ); ?></h1>

	<h2 class="nav-tab-wrapper">
		<a href="<?php echo esc_url( $urls['settings'] ); ?>" class="nav-tab"><?php esc_html_e( 'Settings', 'civicrm-admin-utilities' ); ?></a>
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
		<?php

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$message_class = ( ! empty( $_REQUEST['error'] ) ) ? 'error' : 'updated';

		?>
		<div class="<?php echo esc_attr( $message_class ); ?> notice is-dismissible">
			<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
			<p><?php echo implode( "<br/>\n", $messages ); ?></p>
		</div>
	<?php endif; ?>

	<?php $this->user_table->views(); ?>

	<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
	<form id="civicrm-au-users-form" action="<?php echo $this->page_submit_url_get(); ?>" method="get">

		<?php

		/**
		 * Allow others to add markup (like the search query) at the top of the form.
		 *
		 * @since 0.9
		 */
		do_action( 'cau/single_users/user_table/form/start' );

		?>

		<?php

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_REQUEST['s'] ) ) :

			$span_content = sprintf(
				/* translators: %s: Search query. */
				esc_html__( 'Search results for: %s', 'civicrm-admin-utilities' ),
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'<strong>' . wp_html_excerpt( esc_html( wp_unslash( $_REQUEST['s'] ) ), 50 ) . '</strong>'
			);

			?>
			<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
			<span class="subtitle"><?php echo $span_content; ?></span>
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
