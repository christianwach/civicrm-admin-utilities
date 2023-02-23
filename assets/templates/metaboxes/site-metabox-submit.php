<?php
/**
 * Site Settings "Submit" metabox Template.
 *
 * Handles markup for the Site Settings "Submit" metabox.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.8.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/metaboxes/site-metabox-submit.php -->
<div class="submitbox">
	<div id="minor-publishing">
		<div id="misc-publishing-actions">
			<div class="misc-pub-section">
				<input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_cache" id="civicrm_admin_utilities_cache" value="1" /> <label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_cache"><?php esc_html_e( 'Clear CiviCRM caches.', 'civicrm-admin-utilities' ); ?></label>
			</div>
		</div>
		<div class="clear"></div>
	</div>

	<div id="major-publishing-actions">
		<div id="publishing-action">
			<?php submit_button( esc_html__( 'Update', 'civicrm-admin-utilities' ), 'primary', 'civicrm_admin_utilities_settings_submit', false ); ?>
			<input type="hidden" name="action" value="update" />
		</div>
		<div class="clear"></div>
	</div>
</div>
