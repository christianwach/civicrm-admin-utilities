<?php
/**
 * Network Settings "Site Settings" Page Template.
 *
 * Handles markup for the Network Settings "Site Settings" page.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.8.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/network-settings-site.php -->
<div class="wrap">

	<h1><?php esc_html_e( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ); ?></h1>

	<?php

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['updated'] ) && isset( $_GET['page'] ) ) {
		add_settings_error( 'cau', 'settings_updated', __( 'Settings saved.', 'civicrm-admin-utilities' ), 'success' );
	}

	settings_errors();

	?>

	<div class="updated">
		<?php if ( $this->plugin->is_civicrm_network_activated() ) : ?>
			<p><?php esc_html_e( 'CiviCRM is network-activated.', 'civicrm-admin-utilities' ); ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'CiviCRM is not network-activated.', 'civicrm-admin-utilities' ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( $show_tabs ) : ?>
		<h1 class="nav-tab-wrapper">
			<a href="<?php echo esc_url( $urls['settings_network'] ); ?>" class="nav-tab"><?php esc_html_e( 'Network Settings', 'civicrm-admin-utilities' ); ?></a>
			<a href="<?php echo esc_url( $urls['settings_site'] ); ?>" class="nav-tab nav-tab-active"><?php esc_html_e( 'Site Settings', 'civicrm-admin-utilities' ); ?></a>
			<?php

			/**
			 * Allow others to add tabs.
			 *
			 * @since 0.5.4
			 *
			 * @param array $urls The array of subpage URLs.
			 * @param str The key of the active tab in the subpage URLs array.
			 */
			do_action( 'civicrm_admin_utilities_network_nav_tabs', $urls, 'settings' );

			?>
		</h1>
	<?php else : ?>
		<hr />
	<?php endif; ?>

	<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
	<form method="post" id="civicrm_admin_utilities_settings_form" action="<?php echo $this->page_submit_url_get( 'cau_network_site' ); ?>">

		<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
		<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
		<?php wp_nonce_field( 'cau_network_site_action', 'cau_network_site_nonce' ); ?>

		<div id="poststuff">

			<div id="post-body" class="metabox-holder columns-<?php echo esc_attr( $columns ); ?>">

				<div id="post-body-content">
					<div class="notice notice-warning inline" style="margin: 0;">
						<p><strong style="text-transform: uppercase;"><?php esc_html_e( 'Please Note:', 'civicrm-admin-utilities' ); ?></strong> <?php esc_html_e( 'The settings that you choose below will be used as the initial default settings for CiviCRM Admin Utilities on each sub-site where CiviCRM is activated. Each of these sub-sites has its own CiviCRM Admin Utilities settings page where these settings can be overridden for that particular sub-site.', 'civicrm-admin-utilities' ); ?></p>
					</div>
				</div><!-- #post-body-content -->

				<div id="postbox-container-1" class="postbox-container">
					<?php do_meta_boxes( $screen->id, 'side', null ); ?>
				</div>

				<div id="postbox-container-2" class="postbox-container">
					<?php do_meta_boxes( $screen->id, 'normal', null ); ?>
					<?php do_meta_boxes( $screen->id, 'advanced', null ); ?>
				</div>

			</div><!-- #post-body -->
			<br class="clear">

		</div><!-- #poststuff -->

	</form>

</div><!-- /.wrap -->
