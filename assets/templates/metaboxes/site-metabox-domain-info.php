<?php
/**
 * Site Settings Domain tab "CiviCRM Domain Information" metabox Template.
 *
 * Handles markup for the Site Settings Domain tab "CiviCRM Domain Information" metabox.
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

<?php if ( $metabox['args']['multisite'] && ! $metabox['args']['enabled'] ) : ?>
	<div class="notice notice-warning inline" style="background-color: #f7f7f7;">
		<p>
		<?php

		printf(
			/* translators: 1: The opening anchor tag, 2: The closing anchor tag. */
			esc_html__( 'Multisite is not enabled on this CiviCRM Domain. Change %1$sthe setting in CiviCRM%2$s to enable it.', 'civicrm-admin-utilities' ),
			'<a href="' . $metabox['args']['domain_url'] . '">', /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
			'</a>'
		);

		?>
		</p>
	</div>
<?php endif; ?>

<table class="form-table">

	<tr>
		<th scope="row"><?php esc_html_e( 'Domain', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<?php

			printf(
				/* translators: 1: The Domain name, 2: The Domain ID. */
				esc_html__( '%1$s (ID %2$s)', 'civicrm-admin-utilities' ),
				'<span class="cau_domain_name">' . esc_html( $metabox['args']['domain']['name'] ) . '</span>',
				'<span class="cau_domain_id">' . esc_html( $metabox['args']['domain']['id'] ) . '</span>'
			);

			?>
		</td>
	</tr>

	<tr>
		<th scope="row"><?php esc_html_e( 'Domain Group', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<?php

			// Make Domain Organisation name a link if we have one.
			$domain_group_name = esc_html( $metabox['args']['domain_group']['name'] );
			if ( ! empty( $metabox['args']['domain_group_url'] ) ) {
				$domain_group_name = sprintf(
					'<a href="%s">%s</a>',
					$metabox['args']['domain_group_url'],
					esc_html( $metabox['args']['domain_group']['name'] )
				);
			}

			printf(
				/* translators: 1: The Domain Group name, 2: The Domain Group ID. */
				esc_html__( '%1$s (ID %2$s)', 'civicrm-admin-utilities' ),
				'<span class="cau_domain_group_name">' . $domain_group_name . '</span>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				'<span class="cau_domain_group_id">' . esc_html( $metabox['args']['domain_group']['id'] ) . '</span>'
			);

			?>
		</td>
	</tr>

	<tr>
		<th scope="row"><?php esc_html_e( 'Domain Organisation', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<?php

			// Make Domain Organisation name a link if we have one.
			$domain_org_name = esc_html( $metabox['args']['domain_org']['name'] );
			if ( ! empty( $metabox['args']['domain_org_url'] ) ) {
				$domain_org_name = sprintf(
					'<a href="%s">%s</a>',
					$metabox['args']['domain_org_url'],
					esc_html( $metabox['args']['domain_org']['name'] )
				);
			}

			printf(
				/* translators: 1: The Domain Organisation name, 2: The Domain Organisation ID. */
				esc_html__( '%1$s (ID %2$s)', 'civicrm-admin-utilities' ),
				'<span class="cau_domain_org_name">' . $domain_org_name . '</span>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				'<span class="cau_domain_org_id">' . esc_html( $metabox['args']['domain_org']['id'] ) . '</span>'
			);

			?>
		</td>
	</tr>

</table>
