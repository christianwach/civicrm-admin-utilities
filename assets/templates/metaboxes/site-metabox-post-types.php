<?php
/**
 * Site "Post Types" metabox Template.
 *
 * Handles markup for the Site "Post Types" metabox.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.8.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/templates/metaboxes/site-metabox-post-types.php -->
<table class="form-table">

	<tr>
		<th scope="row"><?php esc_html_e( 'Shortcode Button', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
			<?php echo $options; ?>
			<p class="description"><?php esc_html_e( 'Select which post types you want the CiviCRM shortcode button to appear on.', 'civicrm-admin-utilities' ); ?></p>
		</td>
	</tr>

</table>
