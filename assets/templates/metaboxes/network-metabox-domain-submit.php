<?php
/**
 * Network Settings "Domain Submit" metabox Template.
 *
 * Handles markup for the Network Settings "Domain Submit" metabox.
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
			</div>
		</div>
		<div class="clear"></div>
	</div>

	<div id="major-publishing-actions">
		<div id="publishing-action">
			<?php submit_button( esc_html__( 'Update', 'civicrm-admin-utilities' ), 'primary', 'cau_network_multidomain_submit', false ); ?>
			<input type="hidden" name="action" value="update" />
		</div>
		<div class="clear"></div>
	</div>
</div>
