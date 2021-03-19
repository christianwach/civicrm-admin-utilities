<!-- assets/templates/site-users-table-views.php -->
<h2 class="screen-reader-text"><?php _e( 'Filter Users list', 'civicrm-admin-utilities' ); ?></h2>

<ul class="subsubsub">

	<li class="all">
		<a href="<?php echo esc_url( $url_base ); ?>" class="<?php if ( 'all' === $this->view ) echo 'current'; ?>">
			<?php printf(
				/* translators: %s is the placeholder for the count html tag `<span class="count"/>` */
				esc_html__( 'All %s', 'civicrm-admin-utilities' ),
				sprintf(
					'<span class="count">(%s)</span>',
					number_format_i18n( $this->user_counts['all'] )
				)
			); ?>
		</a> |
	</li>

	<li class="in_civicrm">
		<a href="<?php echo esc_url( add_query_arg( 'user_status', 'in_civicrm', $url_base ) ); ?>" class="<?php if ( 'in_civicrm' === $this->view ) echo 'current'; ?>">
			<?php printf(
				/* translators: %s is the placeholder for the count html `<span class="count"/>` */
				_n( 'In CiviCRM %s', 'In CiviCRM %s', $this->user_counts['in_civicrm'], 'civicrm-admin-utilities' ),
				sprintf(
					'<span class="count">(%s)</span>',
					number_format_i18n( $this->user_counts['in_civicrm'] )
				)
			); ?>
		</a> |
	</li>

	<li class="not_in_civicrm">
		<a href="<?php echo esc_url( add_query_arg( 'user_status', 'not_in_civicrm', $url_base ) ); ?>" class="<?php if ( 'not_in_civicrm' === $this->view ) echo 'current'; ?>">
			<?php printf(
				/* translators: %s is the placeholder for the count html `<span class="count"/>` */
				_n( 'Not in CiviCRM %s', 'Not in CiviCRM %s', $this->user_counts['not_in_civicrm'], 'civicrm-admin-utilities' ),
				sprintf(
					'<span class="count">(%s)</span>',
					number_format_i18n( $this->user_counts['not_in_civicrm'] )
				)
			); ?>
		</a>
	</li>

	<?php

	/**
	 * Fires inside listing of views so plugins can add their own.
	 *
	 * @since 0.9
	 *
	 * @param string $url_base The current URL base for view.
	 * @param CAU_Single_Users_List_Table $this The table object.
	 */
	do_action( 'cau/single_users/user_table/get_views', $url_base, $this );

	?>

</ul>
