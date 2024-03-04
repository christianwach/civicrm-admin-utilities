<?php
/**
 * Single Site Users List Table class.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.9
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * List table class for Single Site Users admin page.
 *
 * @since 0.9
 */
class CAU_Single_Users_List_Table extends WP_Users_List_Table {

	/**
	 * The type of view currently being displayed.
	 *
	 * E.g. "All", "In CiviCRM", "Not in CiviCRM"...
	 *
	 * @since 0.9
	 * @var string
	 */
	public $view = 'all';

	/**
	 * User counts.
	 *
	 * @since 0.9
	 * @var array
	 */
	public $user_counts = [
		'all'            => 0,
		'in_civicrm'     => 0,
		'not_in_civicrm' => 0,
	];

	/**
	 * Items retrieved by the query.
	 *
	 * @since 0.9
	 * @var array
	 */
	public $items = [];

	/**
	 * Post counts.
	 *
	 * @since 0.9
	 * @var array
	 */
	public $post_counts = [];

	/**
	 * Reference array.
	 *
	 * An array of links between User IDs and Contact IDs where the key is the
	 * User ID and the value is the Contact ID.
	 *
	 * @since 0.9
	 * @var array
	 */
	public $linked_ids = [];

	/**
	 * Constructor.
	 *
	 * @since 0.9
	 */
	public function __construct() {

		// Define singular and plural labels, as well as whether we support AJAX.
		$options = [
			'ajax'     => false,
			'plural'   => 'users',
			'singular' => 'user',
			'screen'   => get_current_screen()->id,
		];

		parent::__construct( $options );

	}

	/**
	 * Prepare the users list for display.
	 *
	 * @since 0.9
	 *
	 * @global string $usersearch
	 */
	public function prepare_items() {

		global $usersearch;

		// Get total User count.
		$users_of_blog            = count_users();
		$this->user_counts['all'] = $users_of_blog['total_users'];

		// Get the search string if present.
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, WordPress.Security.NonceVerification.Recommended
		$usersearch = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';

		// Get the views param if present.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$user_status = isset( $_REQUEST['user_status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['user_status'] ) ) : 'all';

		// Set per page from the screen options.
		$per_page       = 'admin_page_' . civicrm_au()->single_users->users_page_slug . '_per_page';
		$users_per_page = $this->get_items_per_page( $per_page );

		// Get the page number.
		$paged = $this->get_pagenum();

		// Default query args.
		$args = [
			'number' => $users_per_page,
			'offset' => ( $paged - 1 ) * $users_per_page,
			'search' => $usersearch,
			'fields' => 'all_with_meta',
		];

		// Get all UFMatch records.
		$ufmatch_all = civicrm_au()->ufmatch->entry_ids_get_all();

		// Build a reference array.
		$ufmatch_linked = [];
		foreach ( $ufmatch_all as $ufmatch ) {
			$ufmatch_linked[ $ufmatch['uf_id'] ] = $ufmatch['contact_id'];
		}

		// Store it.
		$this->linked_ids = $ufmatch_linked;

		// Grab just the User IDs.
		$ufmatch_ids = wp_list_pluck( $ufmatch_all, 'uf_id' );

		// Restrict Users to those with a CiviCRM Contact.
		if ( 'in_civicrm' === $user_status || 'not_in_civicrm' === $user_status ) {

			// Restrict Users to those with a CiviCRM Contact.
			if ( 'in_civicrm' === $user_status ) {
				$args['include'] = $ufmatch_ids;
				$this->view      = 'in_civicrm';
			}

			// Restrict Users to those with no CiviCRM Contact.
			if ( 'not_in_civicrm' === $user_status ) {
				$args['exclude'] = $ufmatch_ids;
				$this->view      = 'not_in_civicrm';
			}

		}

		// Get User IDs with no role.
		$no_role_ids = wp_get_users_with_no_role();

		// Always exclude Users with no role.
		if ( empty( $args['exclude'] ) ) {
			$args['exclude'] = $no_role_ids;
		} else {
			$args['exclude'] = array_merge( $args['exclude'], $no_role_ids );
		}

		// Also configure search, if present.
		if ( '' !== $args['search'] ) {
			$args['search'] = '*' . $args['search'] . '*';
		}

		// Support ordering.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['orderby'] ) ) {
			$args['orderby'] = sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) );
		}
		if ( isset( $_REQUEST['order'] ) ) {
			$args['order'] = sanitize_text_field( wp_unslash( $_REQUEST['order'] ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		/**
		 * Filters the query arguments used to retrieve users for the current
		 * users list table.
		 *
		 * @since 0.9
		 *
		 * @param array $args Arguments passed to WP_User_Query to retrieve
		 *                    items for the current users list table.
		 * @param array $ufmatch_all The CiviCRM UFMatch results.
		 */
		$args = apply_filters( 'cau/single_users/user_table/query_args', $args, $ufmatch_all );

		// Query the user IDs for this page.
		$user_search = new WP_User_Query( $args );

		// Store results in properties for use elsewhere.
		$this->items = $user_search->get_results();

		// Get Post Counts for these results.
		$this->post_counts = count_many_users_posts( array_keys( $this->items ) );

		// Store the count of UFMatch results for this CiviCRM Domain.
		$this->user_counts['in_civicrm'] = count( $ufmatch_ids );

		/*
		 * Users held for moderation (e.g. by BuddyPress) may skew counts, so
		 * we need to do an additional query for Users not in CiviCRM.
		 */
		$query = [
			'number'  => $users_per_page,
			'exclude' => $ufmatch_ids,
		];

		// Do the query.
		$not_in_civicrm = new WP_User_Query( $query );

		// Right, let's find out how many.
		$users_not_in_civicrm                = $not_in_civicrm->get_results();
		$this->user_counts['not_in_civicrm'] = count( $users_not_in_civicrm );

		// Build pagination params.
		$pagination_args = [
			'total_items' => $user_search->get_total(),
			'per_page'    => $users_per_page,
		];

		// Apply them.
		$this->set_pagination_args( $pagination_args );

		// Build an array to pass to the action below.
		$params = [
			'items'       => $this->items,
			'post_counts' => $this->post_counts,
			'user_counts' => $this->user_counts,
			'ufmatch_all' => $ufmatch_all,
		];

		/**
		 * Let plugins know that the table has been prepared.
		 *
		 * @since 0.9
		 *
		 * @param array $params The data bundled as an array.
		 */
		do_action( 'cau/single_users/user_table/prepared_items', $params );

	}

	/**
	 * Get rid of the extra nav.
	 *
	 * By default, WP_Users_List_Table will add an extra nav to change a User's
	 * role. We don't need this, so overload that method.
	 *
	 * @since 0.9
	 *
	 * @param array $which The current table nav item.
	 */
	public function extra_tablenav( $which ) {

	}

	/**
	 * Get the list of views available on this table (e.g. "all", "public").
	 *
	 * @since 0.9
	 */
	public function get_views() {

		// Get base URL.
		$url_base = civicrm_au()->single_users->page_url_get();

		// Include our List Table views template.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/site-users-table-views.php';

	}

	/**
	 * Specific columns for our purposes.
	 *
	 * @since 0.9
	 *
	 * @return array
	 */
	public function get_columns() {

		// Define our columns.
		$columns = [
			// 'cb' => '<input type="checkbox" />',
			'username'      => __( 'Username', 'civicrm-admin-utilities' ),
			'name'          => __( 'Name', 'civicrm-admin-utilities' ),
			'email'         => __( 'Email', 'civicrm-admin-utilities' ),
			'creation_date' => __( 'User Since', 'civicrm-admin-utilities' ),
			'user_id'       => __( 'User ID', 'civicrm-admin-utilities' ),
			'contact_id'    => __( 'Contact ID', 'civicrm-admin-utilities' ),
			'post_counts'   => __( 'Posts', 'civicrm-admin-utilities' ),
		];

		/**
		 * Filters the Single Site Users columns.
		 *
		 * @since 0.9
		 *
		 * @param array $columns The default array of columns to display.
		 */
		return apply_filters( 'cau/single_users/user_table/columns', $columns );

	}

	/**
	 * Specific bulk actions.
	 *
	 * @since 0.9
	 */
	public function get_bulk_actions() {

		// phpcs:disable Squiz.PHP.NonExecutableCode.Unreachable

		// Disable for now.
		return;

		// Define our Bulk Actions.
		$actions = [
			'sync_to_civicrm' => _x( 'Sync to CiviCRM', 'Manage Users action', 'civicrm-admin-utilities' ),
		];

		/**
		 * Filters the Single Site Users bulk actions.
		 *
		 * @since 0.9
		 *
		 * @param array $actions The default array of bulk actions.
		 */
		return apply_filters( 'cau/single_users/user_table/bulk_actions', $actions );

		// phpcs:enable Squiz.PHP.NonExecutableCode.Unreachable

	}

	/**
	 * The text to show when no items are found.
	 *
	 * @since 0.9
	 */
	public function no_items() {

		// Show something.
		esc_html_e( 'No Users found.', 'civicrm-admin-utilities' );

	}

	/**
	 * The columns Users can be reordered by.
	 *
	 * @since 0.9
	 */
	public function get_sortable_columns() {

		// Default sortable columns.
		$columns = [
			'username'      => 'login',
			'email'         => 'email',
			'creation_date' => 'user_registered',
			'user_id'       => 'ID',
		];

		/**
		 * Filters the Single Site Users sortable columns.
		 *
		 * @since 0.9
		 *
		 * @param array $columns The default array of sortable columns.
		 */
		return apply_filters( 'cau/single_users/user_table/sortable_columns', $columns );

	}

	/**
	 * Display the table rows.
	 *
	 * @since 0.9
	 */
	public function display_rows() {

		// Init the row style.
		$style = '';

		// Process list items and write the rows to the screen.
		foreach ( $this->items as $user_id => $user_object ) {
			$style = ( ' class="alternate"' === $style ) ? '' : ' class="alternate"';
			echo "\n\t";
			$this->single_row( $user_object, $style );
		}

	}

	/**
	 * Display a User row.
	 *
	 * @since 0.9
	 *
	 * @see WP_List_Table::single_row() for explanation of params.
	 *
	 * @param object|null $user_object The User object.
	 * @param string      $style Styles for the row.
	 * @param string      $role Role to be assigned to user.
	 * @param int         $numposts Number of posts.
	 */
	public function single_row( $user_object = null, $style = '', $role = '', $numposts = 0 ) {

		// Open the table row.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<tr' . $style . ' id="user-' . esc_attr( $user_object->ID ) . '">';

		// Write columns to screen.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->single_row_columns( $user_object );

		// Close the table row.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</tr>';

	}

	/**
	 * Markup for the checkbox used to select items for bulk actions.
	 *
	 * @since 0.9
	 *
	 * @param object|null $user_object The User data object.
	 */
	public function column_cb( $user_object = null ) {

		// Define label.
		$label = sprintf(
			/* translators: accessibility text */
			__( 'Select user: %s', 'civicrm-admin-utilities' ),
			$user_object->user_login
		);

		?>
		<label class="screen-reader-text" for="user_<?php echo esc_attr( (int) $user_object->ID ); ?>"><?php echo esc_html( $label ); ?></label>
		<input type="checkbox" id="user_<?php echo intval( $user_object->ID ); ?>" name="allusers[]" value="<?php echo esc_attr( $user_object->ID ); ?>" />
		<?php

	}

	/**
	 * The row actions (sync_to_civicrm).
	 *
	 * @since 0.9
	 *
	 * @param object|null $user_object The User data object.
	 */
	public function column_username( $user_object = null ) {

		// Grab the User's avatar.
		$avatar = get_avatar( $user_object->user_email, 32 );

		/*
		// Define args for link.
		$args = [
			'page'      => 'bp-users',
			'user_id' => $user_object->ID,
			'action'    => 'sync_to_civicrm',
		];

		// Sync to CiviCRM link.
		$sync_to_civicrm_link = add_query_arg( $args, bp_get_admin_url( 'users.php' ) );

		echo $avatar . sprintf( '<strong><a href="%1$s" class="edit">%2$s</a></strong><br/>', esc_url( $sync_to_civicrm_link ), $user_object->user_login );
		*/

		// Construct link to "Edit User" screen.
		$edit_link = admin_url( 'user-edit.php?user_id=' . $user_object->ID );

		// Write username to screen. Avatar is already escaped.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $avatar . sprintf( '<strong><a href="%1$s" class="edit">%2$s</a></strong><br/>', esc_url( $edit_link ), esc_html( $user_object->user_login ) );

		// Init row actions.
		$actions = [];

		// Add edit link to actions.
		$actions['edit'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $edit_link ),
			esc_html__( 'Edit', 'civicrm-admin-utilities' )
		);

		/**
		 * Filters the row actions for each user in the list.
		 *
		 * @since 0.9
		 *
		 * @param array $actions Array of actions and corresponding links.
		 * @param object $user_object The User data object.
		 */
		$actions = apply_filters( 'cau/single_users/user_table/row_actions', $actions, $user_object );

		// Write row actions to screen.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->row_actions( $actions );

	}

	/**
	 * Display user name, if any.
	 *
	 * @since 0.9
	 *
	 * @param object|null $user_object The User data object.
	 */
	public function column_name( $user_object = null ) {

		// Write display name to screen.
		echo esc_html( $user_object->display_name );

	}

	/**
	 * Display User email.
	 *
	 * @since 0.9
	 *
	 * @param object|null $user_object The User data object.
	 */
	public function column_email( $user_object = null ) {

		// Write email to screen.
		echo esc_html( $user_object->user_email );

	}

	/**
	 * Display User creation date.
	 *
	 * @since 0.9
	 *
	 * @param object|null $user_object The User data object.
	 */
	public function column_creation_date( $user_object = null ) {

		// Write to screen.
		echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $user_object->user_registered ) ) );

	}

	/**
	 * Display User ID.
	 *
	 * @since 0.9
	 *
	 * @param object|null $user_object The User data object.
	 */
	public function column_user_id( $user_object = null ) {

		// Write to screen.
		echo esc_html( $user_object->ID );

	}

	/**
	 * Display Contact ID.
	 *
	 * @since 0.9
	 *
	 * @param object|null $user_object The User data object.
	 */
	public function column_contact_id( $user_object = null ) {

		// Write to screen.
		if ( ! empty( $this->linked_ids[ $user_object->ID ] ) ) {
			echo esc_html( $this->linked_ids[ $user_object->ID ] );
		} else {
			echo '<span class="dashicons dashicons-minus"></span>';
		}

	}

	/**
	 * Display User post counts.
	 *
	 * @since 0.9
	 *
	 * @param object|null $user_object The User data object.
	 */
	public function column_post_counts( $user_object = null ) {

		// Get the number of posts for this user.
		$post_count = ! empty( $this->post_counts[ $user_object->ID ] ) ? $this->post_counts[ $user_object->ID ] : 0;

		// Assume none.
		$markup = '0';

		// More than none needs markup.
		if ( $post_count > 0 ) {

			$markup = sprintf(
				'<a href="%s" class="edit"><span aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></a>',
				esc_url( "edit.php?author={$user_object->ID}" ),
				esc_html( $post_count ),
				sprintf(
					/* translators: %s: Number of posts. */
					_n( '%s post by this author', '%s posts by this author', $post_count, 'civicrm-admin-utilities' ),
					number_format_i18n( $post_count )
				)
			);

		}

		// Write to screen.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $markup;

	}

	/**
	 * Allow plugins to add custom columns.
	 *
	 * @since 0.9
	 *
	 * @param object|null $user_object The User data object.
	 * @param string      $column_name The column name.
	 * @return string
	 */
	public function column_default( $user_object = null, $column_name = '' ) {

		/**
		 * Filters the single site custom columns for plugins.
		 *
		 * @since 0.9
		 *
		 * @param string $column_name The column name.
		 * @param object $user_object The User data object.
		 */
		return apply_filters( 'cau/single_users/user_table/custom_column', '', $column_name, $user_object );

	}

}
