<?php
/**
 * Network Settings "Domain Info" metabox Template.
 *
 * Handles markup for the Network Settings "Domain Info" metabox.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.8.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/metaboxes/network-metabox-domain-info.php -->
<?php if ( ! $metabox['args']['multisite'] ) : ?>
	<div class="updated error inline" style="background-color: #f7f7f7;">
		<p>
		<?php

		printf(
			/* translators: 1: Opening anchor tag, 2: Closing anchor tag. */
			esc_html__( 'It is recommended that you install and activate the %1$sCiviCRM Multisite%2$s extension to work with multiple Domains in CiviCRM.', 'civicrm-admin-utilities' ),
			'<a href="https://civicrm.org/extensions/multisite-permissioning" target="_blank">',
			'</a>'
		);

		?>
		</p>
	</div>
<?php endif; ?>

<?php if ( ! empty( $metabox['args']['domains'] ) ) : ?>

<table class="form-table">

	<?php foreach ( $metabox['args']['domains'] as $civicrm_domain ) : ?>

	<tr>
		<th scope="row">
			<?php echo esc_html( $civicrm_domain['name'] ); ?>
		</th>

		<td>
			<?php

			echo sprintf(
				/* translators: %s: The ID of the CiviCRM Domain. */
				esc_html__( 'ID %s', 'civicrm-admin-utilities' ),
				'<span class="cau_domain_id">' . esc_html( $civicrm_domain['id'] ) . '</span>'
			);

			?>
		</td>
	</tr>

	<?php endforeach; ?>

</table>

<?php endif; ?>
