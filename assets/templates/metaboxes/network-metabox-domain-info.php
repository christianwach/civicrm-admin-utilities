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
	<div class="notice notice-warning inline">
		<p>
			<?php esc_html_e( 'Each CiviCRM Domain should be assigned to a unique WordPress Site. This is not enforced, but it is strongly recommended.', 'civicrm-admin-utilities' ); ?><br>
			<?php esc_html_e( 'If you follow a link to a CiviCRM Domain and you see "Sorry, you are not allowed to access this page" then it means you have not yet enabled CiviCRM on that Site.', 'civicrm-admin-utilities' ); ?>
		</p>
	</div>

	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'CiviCRM Domain', 'civicrm-admin-utilities' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Domain ID', 'civicrm-admin-utilities' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Domain Group', 'civicrm-admin-utilities' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Domain Organisation', 'civicrm-admin-utilities' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Assigned to WordPress Site', 'civicrm-admin-utilities' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $metabox['args']['domains'] as $civicrm_domain ) : ?>
			<tr>
				<th scope="row">
					<?php

					// Get settings page link.
					$cau_screen_url = '';
					if ( ! empty( $civicrm_domain['site_id'] ) ) {
						$cau_screen_url = get_admin_url( (int) $civicrm_domain['site_id'], 'admin.php?page=cau_multidomain' );
					}

					// Make Domain name a link if we have one.
					$domain_name = esc_html( stripslashes( $civicrm_domain['name'] ) );
					if ( ! empty( $cau_screen_url ) ) {
						$domain_name = sprintf(
							'<a href="%s">%s</a>',
							$cau_screen_url,
							$domain_name
						);
					}

					// phpcs:ignore
					echo $domain_name;

					?>
				</th>
				<td style="vertical-align: middle;"><?php echo esc_html( $civicrm_domain['domain_id'] ); ?></td>
				<td style="vertical-align: middle;"><?php echo esc_html( $civicrm_domain['domain_group'] ); ?></td>
				<td style="vertical-align: middle;"><?php echo esc_html( $civicrm_domain['domain_org'] ); ?></td>
				<td>
					<select id="cau_site_id-<?php echo esc_attr( $civicrm_domain['domain_id'] ); ?>" name="cau_site_id-<?php echo esc_attr( $civicrm_domain['domain_id'] ); ?>" class="cau_site_id_select" style="min-width: 15em;">
						<?php if ( ! empty( $civicrm_domain['site_id'] ) ) : ?>
							<option value="<?php echo esc_attr( $civicrm_domain['site_id'] ); ?>" selected="selected"><?php echo esc_html( $civicrm_domain['site_name'] ); ?></option>
						<?php else : ?>
							<option value="" selected="selected"><?php esc_html_e( 'Select a WordPress Site', 'civicrm-admin-utilities' ); ?></option>
						<?php endif; ?>
					</select>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
