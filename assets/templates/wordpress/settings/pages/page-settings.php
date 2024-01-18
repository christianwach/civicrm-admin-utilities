<?php
/**
 * Settings Page template.
 *
 * Handles markup for the Settings Page.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 1.0.2
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- <?php echo esc_html( $this->path_template . $this->path_page ); ?>page-settings.php -->
<div class="wrap">

	<h1><?php echo esc_html( $this->plugin_name ); ?></h1>

	<?php

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['updated'] ) && isset( $_GET['page'] ) ) {
		add_settings_error( $this->hook_prefix, 'settings_updated', __( 'Settings saved.', 'civicrm-admin-utilities' ), 'success' );
	}

	settings_errors();

	?>

	<?php if ( $show_tabs ) : ?>
		<h2 class="nav-tab-wrapper">
			<?php

			/**
			 * Renders the Page Tabs.
			 *
			 * @since 1.0.2
			 */
			do_action( $this->hook_prefix_common . '/settings/page/tabs' );

			?>
		</h2>
	<?php else : ?>
		<hr />
	<?php endif; ?>

	<?php

	/**
	 * Fires before the form is rendered.
	 *
	 * @since 1.0.2
	 */
	do_action( $this->hook_prefix . '/settings/page/form/before' );

	?>

	<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
	<form method="post" id="<?php echo esc_attr( $this->form_id ); ?>" action="<?php echo $this->form_submit_url_get(); ?>">

		<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
		<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
		<?php wp_nonce_field( $this->form_nonce_action, $this->form_nonce_field ); ?>

		<div id="poststuff">

			<div id="post-body" class="metabox-holder columns-<?php echo esc_attr( $columns ); ?>">

				<!--<div id="post-body-content">
				</div>--><!-- #post-body-content -->

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

	<?php

	/**
	 * Fires after the form is rendered.
	 *
	 * @since 1.0.2
	 */
	do_action( $this->hook_prefix . '/settings/page/form/after' );

	?>

</div><!-- /.wrap -->
