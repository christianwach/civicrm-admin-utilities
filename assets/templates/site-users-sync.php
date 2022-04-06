<!-- assets/templates/site-users-sync.php -->
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
		do_action( 'civicrm_admin_utilities_settings_nav_tabs', $urls, 'users' );

		?>
	</h2>

	<?php if ( ! empty( $messages ) ) : ?>
		<div class="<?php echo ( ! empty( $_REQUEST['error'] ) ) ? 'error' : 'updated'; ?> notice is-dismissible">
			<p><?php echo implode( "<br/>\n", $messages ); ?></p>
		</div>
	<?php endif; ?>

	<p><?php _e( 'A new CiviCRM Contact will be created for each WordPress User where one does not already exist. For WordPress Users that do not have a linked Contact record, use the Dedupe Rules dropdown to select how CiviCRM Admin Utilities should check for a record that might already exist.', 'civicrm-admin-utilities' ); ?></p>

	<form id="civicrm-au-users-form" action="<?php echo $this->page_submit_url_get(); ?>" method="get">

		<?php wp_nonce_field( 'cau_user_sync_action', 'cau_user_sync_nonce' ); ?>

		<?php

		/**
		 * Allow others to add markup (like the search query) at the top of the form.
		 *
		 * @since 0.9
		 */
		do_action( 'cau/single_users/user_sync/form/start' );

		?>

		<input type="hidden" name="page" value="<?php echo esc_attr( $this->users_page_slug ); ?>" />

		<!--
		<p><input type="submit" id="cau-user-sync-submit" name="cau-user-sync-submit" value="<?php if ( 'fgffgs' == get_option( '_cau_user_sync_offset', 'fgffgs' ) ) { esc_attr_e( 'Synchronize Now', 'civicrm-admin-utilities' ); } else { esc_attr_e( 'Continue Sync', 'civicrm-admin-utilities' ); } ?>" class="button-primary" /><?php if ( 'fgffgs' == get_option( '_cau_user_sync_offset', 'fgffgs' ) ) {} else { ?> <input type="submit" id="cau-user-sync-stop" name="cau-user-sync-stop" value="<?php esc_attr_e( 'Stop Sync', 'civicrm-admin-utilities' ); ?>" class="button-secondary" /><?php } ?></p>
		-->

		<?php if ( ! empty( $users_without ) ) : ?>

			<h3><?php _e( 'Users without a linked Contact', 'civicrm-admin-utilities' ); ?></h3>

			<p><select id="cau-user-sync-dedupe-rule" name="cau-user-sync-dedupe-rule">
				<option value=""><?php esc_html_e( '- Select a Dedupe Rule -', 'civicrm-admin-utilities' ) ?></option>
				<?php foreach( $dedupe_rules AS $type => $dedupe_rule ) : ?>
					<?php foreach( $dedupe_rule AS $dedupe_rule_id => $value ) : ?>
						<option value="<?php echo esc_attr( $dedupe_rule_id ); ?>"><?php echo esc_html( $value ); ?></option>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</select></p>

			<table cellspacing="0" class="wp-list-table widefat fixed striped">

				<thead>
					<tr>
						<th class="manage-column column-user-username" id="cau-username" scope="col"><?php esc_html_e( 'Username', 'civicrm-admin-utilities' ); ?></th>
						<th class="manage-column column-user-name" id="cau-name" scope="col"><?php esc_html_e( 'Name', 'civicrm-admin-utilities' ); ?></th>
						<th class="manage-column column-user-email" id="cau-email" scope="col"><?php esc_html_e( 'Email', 'civicrm-admin-utilities' ); ?></th>
						<th class="manage-column column-user-email" id="cau-email" scope="col"><?php esc_html_e( 'Suggested Sync', 'civicrm-admin-utilities' ); ?></th>
						<th class="manage-column column-contact-id" id="cau-contact-id" scope="col"><?php esc_html_e( 'Contact ID', 'civicrm-admin-utilities' ); ?></th>
						<th class="manage-column column-contact-name" id="cau-contact-name" scope="col"><?php esc_html_e( 'Contact Name', 'civicrm-admin-utilities' ); ?></th>
						<?php

						/**
						 * Allow extra columns to be added.
						 *
						 * @since 0.5
						 */
						do_action( 'cau/single_users/user_sync/users_without/th' );

						?>
					</tr>
				</thead>

				<tbody class="cwmp-feedback-list" id="the-comment-list">

					<?php foreach( $users_without AS $user ) : ?>

						<tr>
							<td class="comment column-comment column-primary"><strong><?php echo esc_html( $user->user_login ); ?></strong></td>
							<td><?php echo esc_html( $user->display_name ); ?></td>
							<td><?php echo esc_html( $user->user_email ); ?></td>
							<td>&rarr;</td>
							<td>
								<?php if ( ! empty( $contacts[$user->ID]['contact_id'] ) ) : ?>
									<?php echo esc_html( $contacts[$user->ID]['contact_id'] ); ?>
								<?php else : ?>
									<span class="dashicons dashicons-minus"></span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( ! empty( $contacts[$user->ID]['display_name'] ) ) : ?>
									<?php echo esc_html( $contacts[$user->ID]['display_name'] ); ?>
								<?php endif; ?>
							</td>
							<?php

							/**
							 * Allow extra fields to be added.
							 *
							 * @since 0.9
							 */
							do_action( 'cau/single_users/user_sync/users_without/td', $user );

							?>
						</tr>

					<?php endforeach; ?>

				</tbody>

			</table>

		<?php endif; ?>

		<?php if ( ! empty( $users_with ) ) : ?>

			<h3><?php _e( 'Users with a linked Contact', 'civicrm-admin-utilities' ); ?></h3>

			<table cellspacing="0" class="wp-list-table widefat fixed striped">

				<thead>
					<tr>
						<th class="manage-column column-user-username" id="cau-username" scope="col"><?php esc_html_e( 'Username', 'civicrm-admin-utilities' ); ?></th>
						<th class="manage-column column-user-name" id="cau-name" scope="col"><?php esc_html_e( 'Name', 'civicrm-admin-utilities' ); ?></th>
						<th class="manage-column column-user-email" id="cau-email" scope="col"><?php esc_html_e( 'Email', 'civicrm-admin-utilities' ); ?></th>
						<th class="manage-column column-user-email" id="cau-email" scope="col"><?php esc_html_e( 'Syncs To', 'civicrm-admin-utilities' ); ?></th>
						<th class="manage-column column-contact-id" id="cau-contact-id" scope="col"><?php esc_html_e( 'Contact ID', 'civicrm-admin-utilities' ); ?></th>
						<th class="manage-column column-contact-name" id="cau-contact-name" scope="col"><?php esc_html_e( 'Contact Name', 'civicrm-admin-utilities' ); ?></th>
						<?php

						/**
						 * Allow extra columns to be added.
						 *
						 * @since 0.5
						 */
						do_action( 'cau/single_users/user_sync/users_with/th' );

						?>
					</tr>
				</thead>

				<tbody class="cwmp-feedback-list" id="the-comment-list">

					<?php foreach( $users_with AS $user ) : ?>

						<tr>
							<td class="comment column-comment column-primary"><strong><?php echo esc_html( $user->user_login ); ?></strong></td>
							<td><?php echo esc_html( $user->display_name ); ?></td>
							<td><?php echo esc_html( $user->user_email ); ?></td>
							<td>&rarr;</td>
							<td>
								<?php if ( ! empty( $contacts[$user->ID]['contact_id'] ) ) : ?>
									<?php echo esc_html( $contacts[$user->ID]['contact_id'] ); ?>
								<?php else : ?>
									<span class="dashicons dashicons-minus"></span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( ! empty( $contacts[$user->ID]['display_name'] ) ) : ?>
									<?php echo esc_html( $contacts[$user->ID]['display_name'] ); ?>
								<?php endif; ?>
							</td>
							<?php

							/**
							 * Allow extra fields to be added.
							 *
							 * @since 0.9
							 */
							do_action( 'cau/single_users/user_sync/users_with/td', $user );

							?>
						</tr>

					<?php endforeach; ?>

				</tbody>

			</table>

		<?php endif; ?>

		<?php

		/**
		 * Allow others to add markup at the bottom of the form.
		 *
		 * @since 0.9
		 */
		do_action( 'cau/single_users/user_sync/form/end' );

		?>

	</form>

</div><!-- /.wrap -->
