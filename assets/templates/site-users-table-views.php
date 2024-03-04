<?php
/**
 * Site Users Table Views Template.
 *
 * Handles markup for the Site Users Table Views.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.9
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/site-users-table-views.php -->
<h2 class="screen-reader-text"><?php esc_html_e( 'Filter Users list', 'civicrm-admin-utilities' ); ?></h2>

<ul class="subsubsub">

	<li class="all">
		<?php $anchor_class = ( ( 'all' === $this->view ) ? 'current' : '' ); ?>
		<a href="<?php echo esc_url( $url_base ); ?>" class="<?php echo esc_attr( $anchor_class ); ?>">
			<?php

			printf(
				/* translators: %s is the placeholder for the count html tag `<span class="count"/>` */
				esc_html__( 'All %s', 'civicrm-admin-utilities' ),
				sprintf(
					'<span class="count">(%s)</span>',
					esc_html( number_format_i18n( $this->user_counts['all'] ) )
				)
			);

			?>
		</a> |
	</li>

	<li class="in_civicrm">
		<?php $anchor_class = ( ( 'in_civicrm' === $this->view ) ? 'current' : '' ); ?>
		<a href="<?php echo esc_url( add_query_arg( 'user_status', 'in_civicrm', $url_base ) ); ?>" class="<?php echo esc_attr( $anchor_class ); ?>">
			<?php

			printf(
				/* translators: %s is the placeholder for the count html `<span class="count"/>` */
				_n( 'In CiviCRM %s', 'In CiviCRM %s', $this->user_counts['in_civicrm'], 'civicrm-admin-utilities' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				sprintf(
					'<span class="count">(%s)</span>',
					esc_html( number_format_i18n( $this->user_counts['in_civicrm'] ) )
				)
			);

			?>
		</a> |
	</li>

	<li class="not_in_civicrm">
		<?php $anchor_class = ( ( 'not_in_civicrm' === $this->view ) ? 'current' : '' ); ?>
		<a href="<?php echo esc_url( add_query_arg( 'user_status', 'not_in_civicrm', $url_base ) ); ?>" class="<?php echo esc_attr( $anchor_class ); ?>">
			<?php

			printf(
				/* translators: %s is the placeholder for the count html `<span class="count"/>` */
				_n( 'Not in CiviCRM %s', 'Not in CiviCRM %s', $this->user_counts['not_in_civicrm'], 'civicrm-admin-utilities' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				sprintf(
					'<span class="count">(%s)</span>',
					esc_html( number_format_i18n( $this->user_counts['not_in_civicrm'] ) )
				)
			);

			?>
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
