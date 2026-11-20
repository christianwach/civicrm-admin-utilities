<?php
/**
 * Site "Form Builder" metabox Template.
 *
 * Handles markup for the Site "Access" metabox.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 1.0.7
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/templates/metaboxes/site-metabox-afform.php -->
<table class="form-table">

	<?php

	/**
	 * Before Afform section.
	 *
	 * @since 1.0.7
	 */
	do_action( 'cau/admin/metabox/afform/pre' );

	?>
	<tr>
		<th scope="row"><?php esc_html_e( 'Form Builder forms outside content', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<select name="civicrm_admin_utilities_afforms[]" id="civicrm_admin_utilities_afforms" multiple="multiple" style="min-width: 50%;">
				<?php foreach ( $afforms as $afform ) : ?>
					<option value="<?php echo esc_attr( $afform['name'] ); ?>" <?php selected( in_array( $afform['name'], $used, true ), true ); ?>><?php echo esc_html( ( ! empty( $afform['title'] ) ? $afform['title'] : __( 'Untitled', 'civicrm-admin-utilities' ) ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<p class="description">
				<?php

				printf(
					/* translators: 1: The opening code tag, 2: The closing code tag. */
					esc_html__( 'If you have added any Form Builder Blocks to places outside the content of a Post or Page (for example as a Widget or using %1$sdo_shortcode()%2$s in a template) then you will need to select the Forms here in order for them to render properly. If your theme does not fire the %1$sget_header%2$s action, then use the %1$scivicrm_gutenberg_blocks_afform_hook%2$s filter to return a suitable substitute hook.', 'civicrm-admin-utilities' ),
					'<code>',
					'</code>'
				);

				?>
			</p>
		</td>
	</tr>
	<?php

	/**
	 * After Afform section.
	 *
	 * @since 1.0.7
	 */
	do_action( 'cau/admin/metabox/afform/post' );

	?>

</table>
