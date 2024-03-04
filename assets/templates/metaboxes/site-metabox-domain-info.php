<?php
/**
 * Multidomain Domain Info metabox Template.
 *
 * Handles markup for the Multidomain Domain Info metabox.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.8.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/metaboxes/site-metabox-domain-info.php -->
<?php if ( ! $metabox['args']['multisite'] ) : ?>
	<div class="updated error inline" style="background-color: #f7f7f7;">
		<p>
		<?php

		printf(
			/* translators: 1: Opening anchor tag, 2: Closing anchor tag. */
			esc_html__( 'It is recommended that you install and activate the %1$sCiviCRM Multisite extension%2$s to work with multiple Domains in CiviCRM.', 'civicrm-admin-utilities' ),
			'<a href="https://civicrm.org/extensions/multisite-permissioning" target="_blank">',
			'</a>'
		);

		?>
		</p>
	</div>
<?php endif; ?>

<?php if ( ! $metabox['args']['enabled'] ) : ?>
	<div class="notice notice-warning inline" style="background-color: #f7f7f7;">
		<p>
		<?php

		printf(
			/* translators: %s: The URL of the CiviCRM setting page. */
			esc_html__( 'Multisite is not enabled on this CiviCRM Domain. Change <a href="%s">the setting in CiviCRM</a> to enable it.', 'civicrm-admin-utilities' ),
			esc_url( $metabox['args']['multisite_url'] )
		);

		?>
		</p>
	</div>
<?php endif; ?>

<table class="form-table">

	<tr>
		<th scope="row">
			<?php esc_html_e( 'Domain', 'civicrm-admin-utilities' ); ?>
		</th>

		<td>
			<?php

			echo sprintf(
				/* translators: 1: The Domain name, 2: The Domain ID. */
				esc_html__( '%1$s (ID %2$s)', 'civicrm-admin-utilities' ),
				'<span class="cau_domain_name">' . esc_html( $metabox['args']['domain']['name'] ) . '</span>',
				'<span class="cau_domain_id">' . esc_html( $metabox['args']['domain']['id'] ) . '</span>'
			);

			?>
		</td>
	</tr>

	<tr>
		<th scope="row">
			<?php esc_html_e( 'Domain Group', 'civicrm-admin-utilities' ); ?>
		</th>

		<td>
			<?php

			echo sprintf(
				/* translators: 1: The Domain Group name, 2: The Domain Group ID. */
				esc_html__( '%1$s (ID %2$s)', 'civicrm-admin-utilities' ),
				'<span class="cau_domain_group_name">' . esc_html( $metabox['args']['domain_group']['name'] ) . '</span>',
				'<span class="cau_domain_group_id">' . esc_html( $metabox['args']['domain_group']['id'] ) . '</span>'
			);

			?>
		</td>
	</tr>

	<tr>
		<th scope="row">
			<?php esc_html_e( 'Domain Organisation', 'civicrm-admin-utilities' ); ?>
		</th>

		<td>
			<?php

			echo sprintf(
				/* translators: 1: The Domain Organisation name, 2: The Domain Organisation ID. */
				esc_html__( '%1$s (ID %2$s)', 'civicrm-admin-utilities' ),
				'<span class="cau_domain_org_name">' . esc_html( $metabox['args']['domain_org']['name'] ) . '</span>',
				'<span class="cau_domain_org_id">' . esc_html( $metabox['args']['domain_org']['id'] ) . '</span>'
			);

			?>
		</td>
	</tr>

</table>
