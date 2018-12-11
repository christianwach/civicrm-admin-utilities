<!-- assets/templates/network-multidomain.php -->
<div class="wrap">

	<h1><?php _e( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ); ?></h1>

	<h2 class="nav-tab-wrapper">
		<a href="<?php echo $urls['settings']; ?>" class="nav-tab"><?php _e( 'Settings', 'civicrm-admin-utilities' ); ?></a>
		<?php

		/**
		 * Allow others to add tabs.
		 *
		 * @since 0.5.4
		 *
		 * @param array $urls The array of subpage URLs.
		 * @param str The key of the active tab in the subpage URLs array.
		 */
		do_action( 'civicrm_admin_utilities_network_nav_tabs', $urls, 'multidomain' );

		?>
	</h2>

	<h3 class="wp-heading-inline"><?php _e( 'CiviCRM Domains', 'civicrm-admin-utilities' ); ?></h3> 		<?php if ( $multisite ) : ?><a href="#cau-create-new" class="page-title-action"><?php _e( 'Add new', 'civicrm-admin-utilities' ); ?></a><? endif; ?>

	<div class="updated">
		<?php if ( $this->plugin->is_civicrm_network_activated() ) : ?>
			<p><?php _e( 'CiviCRM is network-activated.', 'civicrm-admin-utilities' ); ?></p>
		<?php else : ?>
			<p><?php _e( 'CiviCRM is not network-activated.', 'civicrm-admin-utilities' ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( ! $multisite ) : ?>
		<div class="updated error">
			<p><?php _e( 'It is recommended that you install and activate the <a href="https://civicrm.org/extensions/multisite-permissioning" target="_blank">CiviCRM Multisite</a> extension to work with multiple Domains in CiviCRM.', 'civicrm-admin-utilities' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $domains ) ) : ?>
		<ul>
		<?php foreach( $domains AS $domain ) : ?>
			<li><?php echo sprintf(
				__( '"%1$s" (ID: %2$s)', 'civicrm-admin-utilities' ),
				'<span class="cau_domain_name">' . $domain['name'] . '</span>',
				'<span class="cau_domain_id">' . $domain['id'] . '</span>'
			); ?></li>
		<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( $multisite ) : ?>

		<hr />

		<form method="post" id="civicrm_admin_utilities_network_multidomain_form" action="<?php echo $this->page_submit_url_get(); ?>">

			<?php wp_nonce_field( 'civicrm_admin_utilities_network_multidomain_action', 'civicrm_admin_utilities_network_multidomain_nonce' ); ?>

				<div class="cau-domain-create">

					<h3 id="cau-create-new"><?php _e( 'Create a new Domain', 'civicrm-admin-utilities' ); ?></h3>

					<p><?php _e( 'If you want a WordPress subsite to have a separate Domain, create one before you activate the CiviCRM plugin on that WordPress subsite.', 'civicrm-admin-utilities' ); ?></p>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label class="civicrm_admin_utilities_settings_label" for="cau_domain_name">
									<?php _e( 'Domain Name', 'civicrm-admin-utilities' ); ?>
								</label>
							</th>
							<td>
								<input id="cau_domain_name" name="cau_domain_name" class="cau_text_input" value="" />
							</td>
						</tr>
					</table>

					<p class="submit">
						<input class="button-primary" type="submit" id="civicrm_admin_utilities_network_multidomain_submit" name="civicrm_admin_utilities_network_multidomain_submit" value="<?php _e( 'Submit', 'civicrm-admin-utilities' ); ?>" />
					</p>

				</div>

		</form>

	<?php endif; ?>

</div><!-- /.wrap -->
