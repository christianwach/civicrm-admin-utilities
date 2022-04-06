<?php
/**
 * Single Site Users Class.
 *
 * Handles User admin functionality in a Single Site context.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.9
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Admin Utilities Users Class.
 *
 * A class that encapsulates User admin functionality in a Single Site context.
 *
 * @since 0.9
 */
class CiviCRM_Admin_Utilities_Single_Users {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.9
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * Page identifier.
	 *
	 * @since 0.9
	 * @access public
	 * @var str $page The identifier for which Users Page is displayed.
	 */
	public $page = 'user_table';

	/**
	 * Users Page slug.
	 *
	 * @since 0.9
	 * @access public
	 * @var str $users_page_slug The slug of the Users Page.
	 */
	public $users_page_slug = 'civicrm_au_users';

	/**
	 * Users Listing page "hook".
	 *
	 * @since 0.9
	 * @access public
	 * @var str $users_page The reference to the Users Listing page.
	 */
	public $users_page;

	/**
	 * User Table object.
	 *
	 * @since 0.9
	 * @access public
	 * @var CAU_Single_Users_List_Table $user_table The User Table object.
	 */
	public $user_table;

	/**
	 * Stepper option name.
	 *
	 * @since 0.9
	 * @access public
	 * @var str $step_option The Stepper option name.
	 */
	public $step_option = '_cau_user_sync_offset';

	/**
	 * Step count.
	 *
	 * @since 0.9
	 * @access public
	 * @var str $step_count The number of Users to process per (AJAX) request.
	 */
	public $step_count = 20;



	/**
	 * Constructor.
	 *
	 * @since 0.9
	 *
	 * @param object $plugin The plugin object.
	 */
	public function __construct( $plugin ) {

		// Store reference to plugin.
		$this->plugin = $plugin;

		// Initialise when plugin is loaded.
		add_action( 'civicrm_admin_utilities_loaded', [ $this, 'initialise' ] );

	}



	/**
	 * Initialise this object.
	 *
	 * @since 0.9
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.9
	 */
	public function register_hooks() {

		// Filter the "per_page" screen option.
		if ( is_admin() && ! empty( $_REQUEST['page'] ) && $this->users_page_slug == $_REQUEST['page'] ) {
			add_filter( 'set-screen-option', [ $this, 'admin_screen_options' ], 10, 3 );
		}

		// Hide some columns by default.
		add_filter( 'default_hidden_columns', [ $this, 'admin_screen_columns' ], 10, 2 );

		// Add "Manage Users" subpage to Single Site Settings menu.
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );

		// Add Contact link to Single Site User listings.
		add_filter( 'cau/single_users/user_table/row_actions', [ $this->plugin->single, 'user_actions' ], 9, 2 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Handle save/update of screen options for the Single Users page.
	 *
	 * @since 0.9
	 *
	 * @param string $value Will always be false unless another plugin filters it first.
	 * @param string $option The screen option name.
	 * @param string $new_value The screen option form value.
	 * @return string|int The option value. False to abandon update.
	 */
	public function admin_screen_options( $value, $option, $new_value ) {

		// Bail if not our page.
		if ( 'admin_page_' . $this->users_page_slug . '_per_page' != $option ) {
			return $value;
		}

		// Set the per page value.
		$new_value = (int) $new_value;
		if ( $new_value < 1 || $new_value > 999 ) {
			return $value;
		}

		// --<
		return $new_value;

	}



	/**
	 * Set the default visibility of the list table columns.
	 *
	 * @since 0.9
	 *
	 * @param array $hidden The existing array of hidden columns.
	 * @param WP_Screen $screen The current screen object.
	 * @param array $hidden The modified array of hidden columns.
	 */
	public function admin_screen_columns( $hidden, $screen ) {

		// Bail if this is not our screen.
		if ( ! isset( $screen->id ) || $this->users_page !== $screen->id ) {
			return $hidden;
		}

		// Seems reasonable to hide these initially.
		$hidden[] = 'user_id';
		$hidden[] = 'contact_id';

		// --<
		return $hidden;

	}



	// -------------------------------------------------------------------------



	/**
	 * Add admin menu item(s) for this plugin.
	 *
	 * @since 0.9
	 */
	public function admin_menu() {

		/**
		 * Set capability but allow overrides.
		 *
		 * @since 0.9
		 *
		 * @param str The default capability for access to user page.
		 * @return str The modified capability for access to user page.
		 */
		$capability = apply_filters( 'civicrm_admin_utilities_page_user_cap', 'manage_options' );

		// Check user permissions.
		if ( ! current_user_can( $capability ) ) {
			return;
		}

		// Add User page.
		$this->users_page = add_submenu_page(
			'cau_parent', // Parent slug.
			__( 'Manage Users', 'civicrm-admin-utilities' ), // Page title.
			__( 'Manage Users', 'civicrm-admin-utilities' ), // Menu title.
			$capability, // Required caps.
			$this->users_page_slug, // Slug name.
			[ $this, 'page_users' ] // Callback.
		);

		// Register our form submit hander.
		add_action( 'load-' . $this->users_page, [ $this, 'form_submitted' ] );

		// Hook into early action for our page init handler.
		add_action( 'load-' . $this->users_page, [ $this, 'page_init' ] );

		// Ensure correct menu item is highlighted.
		add_action( 'admin_head-' . $this->users_page, [ $this->plugin->single, 'admin_menu_highlight' ], 50 );

		// Add help text.
		add_action( 'admin_head-' . $this->users_page, [ $this, 'admin_head' ], 50 );

		// Add scripts and styles.
		//add_action( 'admin_print_scripts-' . $this->users_page, [ $this, 'page_users_js' ] );
		add_action( 'admin_print_styles-' . $this->users_page, [ $this, 'page_users_css' ] );

		// Filter the list of Single Site subpages and add users page.
		add_filter( 'civicrm_admin_utilities_subpages', [ $this, 'admin_subpages_filter' ] );

		// Filter the list of Single Site page URLs and add users page URL.
		add_filter( 'civicrm_admin_utilities_page_urls', [ $this, 'page_urls_filter' ] );

		// Filter the "show tabs" flag for setting templates.
		add_filter( 'civicrm_admin_utilities_show_tabs', [ $this, 'page_show_tabs' ] );

		// Add tab to setting templates.
		add_filter( 'civicrm_admin_utilities_settings_nav_tabs', [ $this, 'page_add_tab' ], 10, 2 );

	}



	/**
	 * Append the Users Listing page to Single Site subpages.
	 *
	 * This ensures that the correct parent menu item is highlighted for our
	 * User subpage in Single Site installs.
	 *
	 * @since 0.9
	 *
	 * @param array $subpages The existing list of subpages.
	 * @return array $subpages The modified list of subpages.
	 */
	public function admin_subpages_filter( $subpages ) {

		// Add Users Listing page.
		$subpages[] = $this->users_page_slug;

		// --<
		return $subpages;

	}



	/**
	 * Initialise plugin help.
	 *
	 * @since 0.9
	 */
	public function admin_head() {

		// Get screen object.
		$screen = get_current_screen();

		// Pass to method in this class.
		$this->admin_help( $screen );

	}



	/**
	 * Adds help copy to admin page.
	 *
	 * @since 0.9
	 *
	 * @param object $screen The existing WordPress screen object.
	 * @return object $screen The amended WordPress screen object.
	 */
	public function admin_help( $screen ) {

		// Init page IDs.
		$pages = [
			$this->users_page,
		];

		// Kick out if not our screen.
		if ( ! in_array( $screen->id, $pages ) ) {
			return $screen;
		}

		// Add a tab - we can add more later.
		$screen->add_help_tab( [
			'id'      => $this->users_page_slug,
			'title'   => __( 'Manage Users', 'civicrm-admin-utilities' ),
			'content' => $this->admin_help_get(),
		] );

		// --<
		return $screen;

	}



	/**
	 * Get help text.
	 *
	 * @since 0.9
	 *
	 * @return string $help The help text formatted as HTML.
	 */
	public function admin_help_get() {

		// Get help markup from template.
		ob_start();
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/site-users-table-help.php';
		$help = ob_get_contents();
		ob_end_clean();

		// --<
		return $help;

	}



	// -------------------------------------------------------------------------



	/**
	 * Initialise our Users Listing page.
	 *
	 * @since 0.9
	 */
	public function page_init() {

		// Default to index page.
		if ( $this->page === 'user_table' ) {

			// Include the WordPress list table class.
			if ( ! class_exists( 'WP_List_Table' ) ) {
				require ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
			}

			// Include the WordPress Users list table class.
			if ( ! class_exists( 'WP_Users_List_Table' ) ) {
				require ABSPATH . 'wp-admin/includes/class-wp-users-list-table.php';
			}

			// Include our List Table class.
			include CIVICRM_ADMIN_UTILITIES_PATH . 'includes/class-cau-users-list-table.php';

			// Create the Users list table.
			$this->user_table = new CAU_Single_Users_List_Table();

			// Add the "per_page" screen option.
			add_screen_option( 'per_page', [
				'label' => _x( 'Users', 'Users per page (screen options)', 'civicrm-admin-utilities' ),
			] );

		}

	}



	/**
	 * Show our Users page.
	 *
	 * @since 0.9
	 */
	public function page_users() {

		/**
		 * Set capability but allow overrides.
		 *
		 * @since 0.9
		 *
		 * @param str The default capability for access to user page.
		 * @return str The modified capability for access to user page.
		 */
		$capability = apply_filters( 'civicrm_admin_utilities_page_user_cap', 'manage_options' );

		// Check user permissions.
		if ( ! current_user_can( $capability ) ) {
			return;
		}

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return;
		}

		// Default to index page.
		if ( $this->page === 'user_table' ) {
			$this->page_users_table();

		// Check for Bulk User Sync.
		} elseif ( $this->page === 'user_sync' ) {
			$this->page_users_sync();

		}

	}



	/**
	 * Show our User Table page.
	 *
	 * @since 0.9
	 */
	public function page_users_table() {

		// Get admin page URLs.
		$urls = $this->plugin->single->page_get_urls();

		// Get current screen.
		$screen = get_current_screen();

		// Prepare the items for display.
		$this->user_table->prepare_items();

		/**
		 * Allow others to add messages.
		 *
		 * @since 0.9
		 *
		 * @param array $messages The array of messages.
		 * @return array $messages The modified array of messages.
		 */
		$messages = apply_filters( 'cau/single_users/user_table/messages', [] );

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/site-users-table.php';

	}



	/**
	 * Show our User Sync page.
	 *
	 * @since 0.9
	 */
	public function page_users_sync() {

		// Grab the User IDs.
		$user_ids = wp_parse_id_list( $_GET['allusers'] );

		// Get all UFMatch records.
		$ufmatch_all = $this->plugin->ufmatch->entry_ids_get_all();

		// Strip out just the Contact IDs that are relevant.
		$query_ids = [];
		foreach( $ufmatch_all AS $ufmatch ) {
			if ( ! in_array( $ufmatch['uf_id'], $user_ids ) ) {
				continue;
			}
			$query_ids[$ufmatch['uf_id']] = $ufmatch['contact_id'];
		}

		// Grab the data for the corresponding Contacts.
		$result = $this->plugin->ufmatch->contacts_get( [ 'id' => [ 'IN' => $query_ids ] ] );

		// Construct a flipped array.
		$linked_ids = [];
		if ( ! empty( $query_ids ) ) {
			$linked_ids = array_flip( $query_ids );
		}

		// Extract the Contacts.
		$contacts = [];
		if ( ! empty( $result ) ) {
			foreach( $result AS $contact ) {
				$user_id = $linked_ids[$contact['id']];
				$contacts[$user_id] = $contact;
			}
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'result' => $result,
			'contacts' => $contacts,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Let's have an array for the template to separate the tables.
		$users_with_contacts = [];
		if ( ! empty( $contacts ) ) {
			$users_with_contacts = array_keys( $contacts );
		}

		// Define query args.
		$args = [
			'include' => $users_with_contacts,
			'fields' => 'all_with_meta',
		];

		// Query the Users with Contacts.
		$user_search = new WP_User_Query( $args );
		$users_with = $user_search->get_results();

		// Remove the Users with Contacts from the User IDs array.
		$users_without_contacts = array_diff( $user_ids, $users_with_contacts );

		// Define query args.
		$args = [
			'include' => $users_without_contacts,
			'fields' => 'all_with_meta',
		];

		// Query the Users with Contacts.
		$user_search = new WP_User_Query( $args );
		$users_without = $user_search->get_results();

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'user_ids' => $user_ids,
			'users_all' => $user_all,
			//'backtrace' => $trace,
		], true ) );
		*/

		///*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'users_with_contacts' => $users_with_contacts,
			'users_without_contacts' => $users_without_contacts,
			//'backtrace' => $trace,
		], true ) );
		//*/

		// Grab the Dedupe Rules.
		$dedupe_rules = $this->plugin->ufmatch->dedupe_rules_get( 'Individual' );

		// Get admin page URLs.
		$urls = $this->plugin->single->page_get_urls();

		/**
		 * Allow others to add messages.
		 *
		 * @since 0.9
		 *
		 * @param array $messages The array of messages.
		 * @return array $messages The modified array of messages.
		 */
		$messages = apply_filters( 'cau/single_users/user_sync/messages', [] );

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/site-users-sync.php';

	}



	/**
	 * Enqueue stylesheets for the Site User page.
	 *
	 * since 0.9
	 */
	public function page_users_css() {

		// Add page-specific stylesheet.
		wp_enqueue_style(
			'cau_site_users',
			plugins_url( 'assets/css/civicrm-admin-utilities-users.css', CIVICRM_ADMIN_UTILITIES_FILE ),
			[],
			CIVICRM_ADMIN_UTILITIES_VERSION, // Version.
			'all' // Media.
		);

	}



	/**
	 * Enqueue Javascripts on the Site User page.
	 *
	 * since 0.9
	 */
	public function page_users_js() {

		// Enqueue our Javascript plus dependencies.
		wp_enqueue_script(
			'cau_site_user_js',
			plugins_url( 'assets/js/civicrm-admin-utilities-site-users.js', CIVICRM_ADMIN_UTILITIES_FILE ),
			[ 'jquery' ],
			CIVICRM_ADMIN_UTILITIES_VERSION // version
		);

		// Localisation array.
		$vars = [
			'localisation' => [],
			'settings' => [
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'blog_id' => get_current_blog_id(),
			],
		];

		// Localise with WordPress function.
		wp_localize_script(
			'cau_site_user_js',
			'CAU_Site_User',
			$vars
		);

	}



	/**
	 * Append the Users Listing page URL to Single Site subpage URLs.
	 *
	 * @since 0.9
	 *
	 * @param array $urls The existing list of URLs.
	 * @return array $urls The modified list of URLs.
	 */
	public function page_urls_filter( $urls ) {

		// Add Users Listing page.
		$urls['users'] = menu_page_url( $this->users_page_slug, false );

		// --<
		return $urls;

	}



	/**
	 * Show subpage tabs on settings pages.
	 *
	 * @since 0.9
	 *
	 * @param bool $show_tabs True if tabs are shown, false otherwise.
	 * @return bool $show_tabs True if tabs are to be shown, false otherwise.
	 */
	public function page_show_tabs( $show_tabs ) {

		// Always show tabs.
		$show_tabs = true;

		// --<
		return $show_tabs;

	}



	/**
	 * Add subpage tab to tabs on settings pages.
	 *
	 * @since 0.9
	 *
	 * @param array $urls The array of subpage URLs.
	 * @param str The key of the active tab in the subpage URLs array.
	 */
	public function page_add_tab( $urls, $active_tab ) {

		// Define title.
		$title = __( 'Manage Users', 'civicrm-admin-utilities' );

		// Default to inactive.
		$active = '';

		// Make active if it's our subpage.
		if ( $active_tab === 'users' ) {
			$active = ' nav-tab-active';
		}

		// Render tab.
		echo '<a href="' . $urls['users'] . '" class="nav-tab' . $active . '">' . $title . '</a>' . "\n";

	}



	/**
	 * Get the URL of the Users Page.
	 *
	 * @since 0.4
	 *
	 * @return string $url The URL of the Users Page.
	 */
	public function page_url_get() {

		// Get Settings Page URL.
		$url = menu_page_url( $this->users_page_slug, false );

		/**
		 * Filter the Users Page URL.
		 *
		 * @since 0.9
		 *
		 * @param array $url The default Users Page URL.
		 * @return array $url The modified Users Page URL.
		 */
		$url = apply_filters( 'cau/single_users/page/settings/url', $url );

		// --<
		return $url;

	}



	/**
	 * Get the Users Page submit URL.
	 *
	 * @since 0.9
	 *
	 * @return string $url The Users Page submit URL.
	 */
	public function page_submit_url_get() {

		// Get Settings Page URL.
		$url = menu_page_url( $this->users_page_slug, false );

		/**
		 * Filter the Users Page submit URL.
		 *
		 * @since 0.9
		 *
		 * @param array $url The default Users Page submit URL.
		 * @return array $url The modified Users Page submit URL.
		 */
		$url = apply_filters( 'cau/single_users/page/settings/submit_url', $url );

		// --<
		return $url;

	}



	// -------------------------------------------------------------------------



	/**
	 * Perform actions when the form has been submitted.
	 *
	 * @since 0.9
	 */
	public function form_submitted() {

	 	// Was the "Stop Sync" button pressed?
		if ( isset( $_POST['cau_user_sync_stop'] ) ) {
			$this->form_stepped_offset_delete();
		}

		// Was the "User Sync" form submitted?
		if ( isset( $_POST['cau_user_sync_submit'] ) ) {
			$this->form_sync_users();
		}

		// Get the selected bulk action.
		$action = $this->form_bulk_action();

		// Bail if not properly called.
		if ( empty( $action ) OR $action == '-1' ) {
			return;
		}

		/**
		 * Let others know about our bulk action.
		 *
		 * @since 0.9
		 *
		 * @param string $action The bulk action being performed.
		 */
		do_action( 'cau/single_users/user_table/bulk_action', $action );

		///*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'action' => $action,
			//'backtrace' => $trace,
		], true ) );
		//*/

		// Did we ask to sync Users to CiviCRM?
		if ( 'sync_to_civicrm' === $action ) {

			// See if we trust the source.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );
			if ( ! wp_verify_nonce( $nonce, 'bulk-users' ) ) {
				wp_die( __( 'Authentication failed.', 'civicrm-admin-utilities' ) );
			}

			// Show User Sync page if we have selected Users.
			if ( ! empty( $_GET['allusers'] ) ) {
				$this->page = 'user_sync';
			} else {
				wp_safe_redirect( $this->page_url_get() );
				exit;
			}

		}

	}



	/**
	 * Sync the Users to their respective Contacts
	 *
	 * @since 0.9
	 */
	public function form_sync_users() {

		// Check that we trust the source of the request.
		check_admin_referer( 'cau_user_sync_action', 'cau_user_sync_nonce' );

		/**
		 * Let other plugins know that we're about to sync users.
		 *
		 * @since 0.9
		 */
		do_action( 'cau/single_users/user_sync/pre' );

		// Sync Users to CiviCRM.

		/**
		 * Let other plugins know that we've synced the users.
		 *
		 * @since 0.9
		 */
		do_action( 'cau/single_users/user_sync/post' );

	}



	/**
	 * Get the currently selected bulk action.
	 *
	 * WP_List_Tables have bulk actions at the top and at the bottom of the tables,
	 * and the inputs have different keys in the $_REQUEST array. This function
	 * reconciles the two values and returns a single action being performed.
	 *
	 * @since 0.9
	 *
	 * @return string $action The selected bulk action, or '-1' if none selected.
	 */
	public function form_bulk_action() {

		// Grab the top action.
		$action = ! empty( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

		// If the bottom action is set, override the top action.
		if ( ! empty( $_REQUEST['action2'] ) && $_REQUEST['action2'] != '-1' ) {
			$action = $_REQUEST['action2'];
		}

		// --<
		return $action;

	}



	// -------------------------------------------------------------------------



	/**
	 * Initialise the synchronisation stepper.
	 *
	 * @since 0.9
	 */
	public function form_stepped_offset_init() {

		// If the offset value doesn't exist.
		if ( 'fgffgs' == get_option( $this->step_option, 'fgffgs' ) ) {

			// Start at the beginning.
			$offset = 0;
			add_option( $this->step_option, '0' );

		} else {

			// Use the existing value.
			$offset = (int) get_option( $this->step_option, '0' );

		}

		// --<
		return $offset;

	}



	/**
	 * Update the synchronisation stepper.
	 *
	 * @since 0.9
	 *
	 * @param string $to The value for the stepper.
	 */
	public function form_stepped_offset_update( $to ) {

		// Increment offset option.
		update_option( $this->step_option, (string) $to );

	}



	/**
	 * Delete the synchronisation stepper.
	 *
	 * @since 0.9
	 */
	public function form_stepped_offset_delete() {

		// Delete the option to start from the beginning.
		delete_option( $this->step_option );

	}



	/**
	 * Get the User Sync step count.
	 *
	 * @since 0.9
	 *
	 * @param int $step_count The numeric step count.
	 */
	public function form_step_count_get() {

		/**
		 * Filter the step count.
		 *
		 * @since 0.9
		 *
		 * @param int $step_count The default step count.
		 * @return int $step_count The filtered step count.
		 */
		return apply_filters( 'cau/single_user/user_sync/step_counts/get', $this->step_count );

	}



} // Class ends.



