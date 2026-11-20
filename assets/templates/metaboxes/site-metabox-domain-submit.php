<?php
/**
 * Site Settings Domain tab "Settings Submit" metabox Template.
 *
 * Handles markup for the Site Settings Domain tab "Settings Submit" metabox.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.8.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/metaboxes/site-metabox-domain-submit.php -->
<div class="submitbox">
	<div id="minor-publishing">
		<div id="misc-publishing-actions">
			<div class="misc-pub-section">
				<ul style="margin: 0;">
					<?php /* phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
					<li><a href="<?php echo $metabox['args']['domain_url']; ?>"><?php esc_html_e( 'Multi Site Settings', 'civicrm-admin-utilities' ); ?></a></li>
					<li><a href="<?php echo $metabox['args']['resource_url']; ?>"><?php esc_html_e( 'CiviCRM Resource URLs', 'civicrm-admin-utilities' ); ?></a></li>
					<?php /* phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
				</ul>
			</div>
		</div>
		<div class="clear"></div>
	</div>

	<div id="major-publishing-actions">
		<div id="publishing-action">
			<?php submit_button( esc_html__( 'Update', 'civicrm-admin-utilities' ), 'primary', 'cau_multidomain_submit', false ); ?>
			<input type="hidden" name="action" value="update" />
		</div>
		<div class="clear"></div>
	</div>
</div>
