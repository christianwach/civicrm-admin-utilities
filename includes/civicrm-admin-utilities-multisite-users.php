<?php
/**
 * Multisite Users Class.
 *
 * Handles User admin functionality in a Multisite context.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.9
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Admin Utilities Multisite Users Class.
 *
 * A class that encapsulates User admin functionality in a Multisite context.
 *
 * @since 0.9
 */
class CiviCRM_Admin_Utilities_Multisite_Users {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.9
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * Users Listing page reference.
	 *
	 * @since 0.9
	 * @access public
	 * @var array $users_page The reference to the Users Listing page.
	 */
	public $users_page;



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

		// Add User subpage to Network Settings menu.
		add_action( 'network_admin_menu', [ $this, 'network_admin_menu' ], 30 );

		// Add meta boxes to Network User subpage.
		add_action( 'add_meta_boxes', [ $this, 'network_meta_boxes_add' ], 11, 1 );

		// Add User subpage to Multisite Settings menu.
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );

		// Add meta boxes to Multisite User subpage.
		add_action( 'add_meta_boxes', [ $this, 'meta_boxes_add' ], 11, 1 );

		/*
		// Add Users AJAX handler.
		add_action( 'wp_ajax_cau_users_get', [ $this, 'users_ajax_get' ] );

		// Add User Groups AJAX handler.
		add_action( 'wp_ajax_cau_user_groups_get', [ $this, 'user_groups_ajax_get' ] );

		// Add User Orgs AJAX handler.
		add_action( 'wp_ajax_cau_user_orgs_get', [ $this, 'user_orgs_ajax_get' ] );
		*/

	}



	// -------------------------------------------------------------------------




	/**
	 * Add network admin menu item(s) for this plugin.
	 *
	 * @since 0.9
	 */
	public function network_admin_menu() {

		// We must be network admin in Multisite.
		if ( ! is_super_admin() ) {
			return;
		}

		// Add Network Users Listing page.
		$this->network_users_page = add_submenu_page(
			'cau_network_parent', // Parent slug.
			__( 'CiviCRM Admin Utilities: User', 'civicrm-admin-utilities' ), // Page title.
			__( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ), // Menu title.
			'manage_network_plugins', // Required caps.
			'cau_network_users', // Slug name.
			[ $this, 'page_network_users' ] // Callback.
		);

		// Ensure correct menu item is highlighted.
		add_action( 'admin_head-' . $this->network_users_page, [ $this->plugin->multisite, 'network_menu_highlight' ], 50 );

		// Add help text.
		add_action( 'admin_head-' . $this->network_users_page, [ $this, 'network_admin_head' ], 50 );

		// Add scripts and styles.
		add_action( 'admin_print_styles-' . $this->network_users_page, [ $this, 'page_network_users_css' ] );
		//add_action( 'admin_print_scripts-' . $this->network_users_page, [ $this, 'page_network_users_js' ] );

		// Try and update options.
		//$saved = $this->settings_update_router();

		// Filter the list of Multisite subpages and add users page.
		add_filter( 'civicrm_admin_utilities_network_subpages', [ $this, 'network_admin_subpages_filter' ] );

		// Filter the list of network page URLs and add users page URL.
		add_filter( 'civicrm_admin_utilities_network_page_urls', [ $this, 'page_network_urls_filter' ] );

		// Filter the "show tabs" flag for setting templates.
		add_filter( 'civicrm_admin_utilities_network_show_tabs', [ $this, 'page_network_show_tabs' ] );

		// Add tab to setting templates.
		add_filter( 'civicrm_admin_utilities_network_nav_tabs', [ $this, 'page_network_add_tab' ], 10, 2 );

	}



	/**
	 * Append the Network Users Listing page to network subpages.
	 *
	 * This ensures that the correct parent menu item is highlighted for our
	 * User subpage in Multisite installs.
	 *
	 * @since 0.6.2
	 *
	 * @param array $subpages The existing list of subpages.
	 * @return array $subpages The modified list of subpages.
	 */
	public function network_admin_subpages_filter( $subpages ) {

		// Add Network Users Listing page.
		$subpages[] = 'cau_network_users';

		// --<
		return $subpages;

	}



	/**
	 * Initialise plugin help for network admin.
	 *
	 * @since 0.9
	 */
	public function network_admin_head() {

		// Get screen object.
		$screen = get_current_screen();

		// Pass to method in this class.
		$this->network_admin_help( $screen );

		// Enqueue WordPress scripts.
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'dashboard' );

	}



	/**
	 * Adds help copy to network admin page.
	 *
	 * @since 0.9
	 *
	 * @param object $screen The existing WordPress screen object.
	 * @return object $screen The amended WordPress screen object.
	 */
	public function network_admin_help( $screen ) {

		// Init page IDs.
		$pages = [
			$this->network_users_page . '-network',
		];

		// Kick out if not our screen.
		if ( ! in_array( $screen->id, $pages ) ) {
			return $screen;
		}

		// Add a tab - we can add more later.
		$screen->add_help_tab( [
			'id'      => 'cau_network_users',
			'title'   => __( 'CiviCRM Admin Utilities Users', 'civicrm-admin-utilities' ),
			'content' => $this->network_admin_help_get(),
		] );

		// --<
		return $screen;

	}



	/**
	 * Get help text for network admin.
	 *
	 * @since 0.9
	 *
	 * @return string $help The help text formatted as HTML.
	 */
	public function network_admin_help_get() {

		// Stub help text, to be developed further.
		$help = '<p>' . __( 'For further information about using CiviCRM Admin Utilities, please refer to the readme.txt file that comes with this plugin.', 'civicrm-admin-utilities' ) . '</p>';

		// --<
		return $help;

	}



	// -------------------------------------------------------------------------



	/**
	 * Show our Network Users Listing page.
	 *
	 * @since 0.6.2
	 */
	public function page_network_users() {

		// Disallow if not network admin in Multisite.
		if ( is_network_admin() AND ! is_super_admin() ) {
			wp_die( __( 'You do not have permission to access this page.', 'civicrm-admin-utilities' ) );
		}

		// Check user permissions.
		if ( ! current_user_can( 'manage_network_users' ) ) {
			return;
		}

		// Get admin page URLs.
		$urls = $this->plugin->multisite->page_get_network_urls();

		// Get current screen.
		$screen = get_current_screen();

		/**
		 * Allow meta boxes to be added to this screen.
		 *
		 * The Screen ID to use is: "civicrm_page_cwps_settings".
		 *
		 * @since 0.8.1
		 *
		 * @param str $screen_id The ID of the current screen.
		 */
		do_action( 'add_meta_boxes', $screen->id, null );

		// Grab columns.
		$columns = ( 1 == $screen->get_columns() ? '1' : '2' );

		// Get users.
		$users = $this->users_get();

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/network-users.php';

	}



	/**
	 * Enqueue stylesheet for the Network Admin User page.
	 *
	 * @since 0.6.2
	 */
	public function page_network_users_css() {

		// Add stylesheet.
		wp_enqueue_style(
			'civicrm_admin_utilities_network_users_css',
			plugins_url( 'assets/css/civicrm-admin-utilities-network-users.css', CIVICRM_ADMIN_UTILITIES_FILE ),
			false,
			CIVICRM_ADMIN_UTILITIES_VERSION, // version
			'all' // media
		);

	}



	/**
	 * Enqueue Javascript on the Network Admin User page.
	 *
	 * @since 0.6.2
	 */
	public function page_network_users_js() {

		// Add Javascript plus dependencies.
		wp_enqueue_script(
			'civicrm_admin_utilities_network_users_js',
			plugins_url( 'assets/js/civicrm-admin-utilities-network-users.js', CIVICRM_ADMIN_UTILITIES_FILE ),
			[ 'jquery' ],
			CIVICRM_ADMIN_UTILITIES_VERSION // version
		);

	}



	/**
	 * Append the users page URL to network subpage URLs.
	 *
	 * @since 0.6.2
	 *
	 * @param array $urls The existing list of URLs.
	 * @return array $urls The modified list of URLs.
	 */
	public function page_network_urls_filter( $urls ) {

		// Add Network Users Listing page.
		$urls['users'] = $this->plugin->multisite->network_menu_page_url( 'cau_network_users', false );

		// --<
		return $urls;

	}



	/**
	 * Show subpage tabs on network settings pages.
	 *
	 * @since 0.6.2
	 *
	 * @param bool $show_tabs True if tabs are shown, false otherwise.
	 * @return bool $show_tabs True if tabs are to be shown, false otherwise.
	 */
	public function page_network_show_tabs( $show_tabs ) {

		// Always show tabs.
		$show_tabs = true;

		// --<
		return $show_tabs;

	}



	/**
	 * Add subpage tab to tabs on network settings pages.
	 *
	 * @since 0.6.2
	 *
	 * @param array $urls The array of subpage URLs.
	 * @param str The key of the active tab in the subpage URLs array.
	 */
	public function page_network_add_tab( $urls, $active_tab ) {

		// Define title.
		$title = __( 'Users', 'civicrm-admin-utilities' );

		// Default to inactive.
		$active = '';

		// Make active if it's our subpage.
		if ( $active_tab === 'users' ) {
			$active = ' nav-tab-active';
		}

		// Render tab.
		echo '<a href="' . $urls['users'] . '" class="nav-tab' . $active . '">' . $title . '</a>' . "\n";

	}



	// -------------------------------------------------------------------------




	/**
	 * Register meta boxes for our Network "Users" page.
	 *
	 * @since 0.8.1
	 *
	 * @param str $screen_id The Admin Page Screen ID.
	 */
	public function network_meta_boxes_add( $screen_id ) {

		// Define valid Screen IDs.
		$screen_ids = [
			'admin_page_cau_network_users-network',
		];

		// Bail if not the Screen ID we want.
		if ( ! in_array( $screen_id, $screen_ids ) ) {
			return;
		}

		// Bail if user does not have permission.
		if ( ! current_user_can( 'manage_network_plugins' ) ) {
			return;
		}

		// Init data to pass to meta boxes.
		$data = [];

		// Get users.
		$data['users'] = $this->users_get();

		// Check if "CiviCRM Multisite" extension is active.
		$data['multisite'] = false;
		if ( $this->plugin->is_extension_enabled( 'org.civicrm.multisite' ) ) {
			$data['multisite'] = true;
		}

		// Create CiviCRM Network Settings metabox.
		add_meta_box(
			'civicrm_au_network_users',
			__( 'CiviCRM Users', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_network_user_info_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core', // Vertical placement: options are 'core', 'high', 'low'.
			$data
		);

		// Bail if "multisite" is not present.
		if ( $data['multisite'] === false ) {
			return;
		}

		// Create "Create User" metabox.
		add_meta_box(
			'civicrm_au_network_user_create',
			__( 'Create User', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_network_user_create_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core', // Vertical placement: options are 'core', 'high', 'low'.
			$data
		);

		// Create Submit metabox.
		add_meta_box(
			'submitdiv',
			__( 'Settings', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_network_submit_render' ], // Callback.
			$screen_id, // Screen ID.
			'side', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

	}



	/**
	 * Render a Submit meta box for our Network "User" page.
	 *
	 * @since 0.8.1
	 */
	public function meta_box_network_submit_render() {

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/network-metabox-user-submit.php';

	}



	/**
	 * Render "CiviCRM Users" meta box for our Network "User" page.
	 *
	 * @since 0.8.1
	 *
	 * @param mixed $unused Unused param.
	 * @param array $metabox Array containing id, title, callback, and args elements.
	 */
	public function meta_box_network_user_info_render( $unused = null, $metabox ) {

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/network-metabox-user-info.php';

	}



	/**
	 * Render "Create User" meta box for our Network "User" page.
	 *
	 * @since 0.8.1
	 *
	 * @param mixed $unused Unused param.
	 * @param array $metabox Array containing id, title, callback, and args elements.
	 */
	public function meta_box_network_user_create_render( $unused = null, $metabox ) {

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/network-metabox-user-create.php';

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
			__( 'CiviCRM Admin Utilities: User', 'civicrm-admin-utilities' ), // Page title.
			__( 'User', 'civicrm-admin-utilities' ), // Menu title.
			$capability, // Required caps.
			'civicrm_au_users', // Slug name.
			[ $this, 'page_users' ] // Callback.
		);

		// Ensure correct menu item is highlighted.
		add_action( 'admin_head-' . $this->users_page, [ $this->plugin->single, 'admin_menu_highlight' ], 50 );

		// Add help text.
		add_action( 'admin_head-' . $this->users_page, [ $this, 'admin_head' ], 50 );

		// Add scripts and styles.
		add_action( 'admin_print_scripts-' . $this->users_page, [ $this, 'page_users_js' ] );
		add_action( 'admin_print_styles-' . $this->users_page, [ $this, 'page_users_css' ] );

		// Try and update options.
		$saved = $this->settings_update_router();

		// Filter the list of Multisite subpages and add users page.
		add_filter( 'civicrm_admin_utilities_subpages', [ $this, 'admin_subpages_filter' ] );

		// Filter the list of Multisite page URLs and add users page URL.
		add_filter( 'civicrm_admin_utilities_page_urls', [ $this, 'page_urls_filter' ] );

		// Filter the "show tabs" flag for setting templates.
		add_filter( 'civicrm_admin_utilities_show_tabs', [ $this, 'page_show_tabs' ] );

		// Add tab to setting templates.
		add_filter( 'civicrm_admin_utilities_settings_nav_tabs', [ $this, 'page_add_tab' ], 10, 2 );

	}



	/**
	 * Append the Users Listing page to Multisite subpages.
	 *
	 * This ensures that the correct parent menu item is highlighted for our
	 * User subpage in Multisite installs.
	 *
	 * @since 0.9
	 *
	 * @param array $subpages The existing list of subpages.
	 * @return array $subpages The modified list of subpages.
	 */
	public function admin_subpages_filter( $subpages ) {

		// Add Users Listing page.
		$subpages[] = 'civicrm_au_users';

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

		// Enqueue WordPress scripts.
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'dashboard' );

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
			'id'      => 'civicrm_au_users',
			'title'   => __( 'CiviCRM Admin Utilities User', 'civicrm-admin-utilities' ),
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

		// Stub help text, to be developed further.
		$help = '<p>' . __( 'Users: For further information about using CiviCRM Admin Utilities, please refer to the readme.txt file that comes with this plugin.', 'civicrm-admin-utilities' ) . '</p>';

		// --<
		return $help;

	}



	// -------------------------------------------------------------------------



	/**
	 * Register meta boxes.
	 *
	 * @since 0.8.1
	 *
	 * @param str $screen_id The Admin Page Screen ID.
	 */
	public function meta_boxes_add( $screen_id ) {

		// Define valid Screen IDs.
		$screen_ids = [
			'admin_page_civicrm_au_users',
		];

		// Bail if not the Screen ID we want.
		if ( ! in_array( $screen_id, $screen_ids ) ) {
			return;
		}

		// Bail if user does not have permission.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Kick out if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return;
		}

		// Init data to pass to meta boxes.
		$data = [];

		// Get user name.
		$data['user'] = $this->user_get();

		// Get user group name.
		$data['user_group'] = $this->user_group_get();

		// Get user org data.
		$data['user_org'] = $this->user_org_get();

		// Check if "CiviCRM Multisite" extension is active.
		$data['multisite'] = false;
		if ( $this->plugin->is_extension_enabled( 'org.civicrm.multisite' ) ) {
			$data['multisite'] = true;
		}

		// Check if "Multisite" is enabled for this User.
		$data['enabled'] = civicrm_api( 'setting', 'getvalue', [
			'version' => 3,
			'user_id' => $data['user']['id'],
			'name' => 'is_enabled',
			'group' => 'Multi Site Preferences',
		] );

		// Get the "Multi Site Settings" page URL.
		$data['multisite_url'] = $this->plugin->single->get_link( 'civicrm/admin/setting/preferences/multisite', 'reset=1' );

		// Create "User Info" metabox.
		add_meta_box(
			'civicrm_au_user_info',
			__( 'CiviCRM User Information', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_info_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core', // Vertical placement: options are 'core', 'high', 'low'.
			$data
		);

		// Bail if "multisite" is not present and enabled in CiviCRM.
		if ( $data['multisite'] === false OR $data['enabled'] === false ) {
			return;
		}

		// Create "Edit User" metabox.
		add_meta_box(
			'civicrm_au_user_edit',
			__( 'Edit User', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_edit_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core', // Vertical placement: options are 'core', 'high', 'low'.
			$data
		);

		// Create Submit metabox.
		add_meta_box(
			'submitdiv',
			__( 'Settings', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_submit_render' ], // Callback.
			$screen_id, // Screen ID.
			'side', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

	}



	/**
	 * Render a "User Info" meta box on Admin screen.
	 *
	 * @since 0.8.1
	 *
	 * @param mixed $unused Unused param.
	 * @param array $metabox Array containing id, title, callback, and args elements.
	 */
	public function meta_box_info_render( $unused = null, $metabox ) {

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/site-metabox-user-info.php';

	}



	/**
	 * Render "Edit User" meta box on Admin screen.
	 *
	 * @since 0.8.1
	 *
	 * @param mixed $unused Unused param.
	 * @param array $metabox Array containing id, title, callback, and args elements.
	 */
	public function meta_box_edit_render( $unused = null, $metabox ) {

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/site-metabox-user-edit.php';

	}



	/**
	 * Render a Submit meta box on Admin screen.
	 *
	 * @since 0.8.1
	 */
	public function meta_box_submit_render() {

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/site-metabox-user-submit.php';

	}



	// -------------------------------------------------------------------------



	/**
	 * Show our Users Listing page.
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

		// Get admin page URLs.
		$urls = $this->plugin->single->page_get_urls();

		// Get current screen.
		$screen = get_current_screen();

		/**
		 * Allow meta boxes to be added to this screen.
		 *
		 * The Screen ID to use is: "civicrm_page_cwps_settings".
		 *
		 * @since 0.8.1
		 *
		 * @param str $screen_id The ID of the current screen.
		 */
		do_action( 'add_meta_boxes', $screen->id, null );

		// Grab columns.
		$columns = ( 1 == $screen->get_columns() ? '1' : '2' );

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/site-users.php';

	}



	/**
	 * Enqueue stylesheets for the Site User page.
	 *
	 * @since 0.6.2
	 */
	public function page_users_css() {

		// Register Select2 styles.
		wp_register_style(
			'cau_site_user_select2_css',
			set_url_scheme( 'http://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.min.css' )
		);

		// Enqueue styles.
		wp_enqueue_style( 'cau_site_user_select2_css' );

		// Add page-specific stylesheet.
		wp_enqueue_style(
			'cau_site_user_css',
			plugins_url( 'assets/css/civicrm-admin-utilities-site-users.css', CIVICRM_ADMIN_UTILITIES_FILE ),
			false,
			CIVICRM_ADMIN_UTILITIES_VERSION, // version
			'all' // media
		);

	}



	/**
	 * Enqueue Javascripts on the Site User page.
	 *
	 * @since 0.6.2
	 */
	public function page_users_js() {

		// Register Select2.
		wp_register_script(
			'cau_site_user_select2_js',
			set_url_scheme( 'http://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/select2.min.js' ),
			[ 'jquery' ]
		);

		// Enqueue Select2 script.
		wp_enqueue_script( 'cau_site_user_select2_js' );

		// Enqueue our Javascript plus dependencies.
		wp_enqueue_script(
			'cau_site_user_js',
			plugins_url( 'assets/js/civicrm-admin-utilities-site-users.js', CIVICRM_ADMIN_UTILITIES_FILE ),
			[ 'jquery', 'cau_site_user_select2_js' ],
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
	 * Append the Users Listing page URL to Multisite subpage URLs.
	 *
	 * @since 0.9
	 *
	 * @param array $urls The existing list of URLs.
	 * @return array $urls The modified list of URLs.
	 */
	public function page_urls_filter( $urls ) {

		// Add Users Listing page.
		$urls['users'] = menu_page_url( 'civicrm_au_users', false );

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
		$title = __( 'User', 'civicrm-admin-utilities' );

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
	 * Get the URL for the form action.
	 *
	 * @since 0.9
	 *
	 * @return string $target_url The URL for the admin form action.
	 */
	public function page_submit_url_get() {

		// Sanitise admin page url.
		$target_url = $_SERVER['REQUEST_URI'];
		$url_array = explode( '&', $target_url );

		// Strip flag, if present, and rebuild.
		if ( ! empty( $url_array ) ) {
			$url_raw = str_replace( '&amp;updated=true', '', $url_array[0] );
			$target_url = htmlentities( $url_raw . '&updated=true' );
		}

		// --<
		return $target_url;

	}



	// -------------------------------------------------------------------------



	/**
	 * Route settings updates to relevant methods.
	 *
	 * @since 0.9
	 *
	 * @return bool $result True on success, false otherwise.
	 */
	public function settings_update_router() {

		// Init return.
		$result = false;

	 	// Was the "Network User" form submitted?
		if ( isset( $_POST['cau_network_users_submit'] ) ) {
			return $this->settings_network_users_update();
		}

	 	// Was the "User" form submitted?
		if ( isset( $_POST['cau_users_submit'] ) ) {
			return $this->settings_users_update();
		}

		// --<
		return $result;

	}



	/**
	 * Update options supplied by our Network User admin page.
	 *
	 * @since 0.6.2
	 *
	 * @return bool True if successful, false otherwise (always true at present).
	 */
	public function settings_network_users_update() {

		// Check that we trust the source of the data.
		check_admin_referer( 'cau_network_users_action', 'cau_network_users_nonce' );

		// Sanitise input.
		$user_name = isset( $_POST['cau_user_name'] ) ? sanitize_text_field( $_POST['cau_user_name'] ) : '';

		// Bail if we get nothing through.
		if ( empty( $user_name ) ) {
			return false;
		}

		// Okay, create user.
		$result = $this->user_create( $user_name );

		// Maybe log errors.
		if ( ! is_int( $result ) ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
		}

		/**
		 * Broadcast that the Network User update process is finished.
		 *
		 * @since 0.8.1
		 */
		do_action( 'civicrm_admin_utilities_network_users_updated' );

		// --<
		return true;

	}



	/**
	 * Update options supplied by our User admin page.
	 *
	 * @since 0.9
	 *
	 * @return bool True if successful, false otherwise (always true at present).
	 */
	public function settings_users_update() {

		// Check that we trust the source of the data.
		check_admin_referer( 'cau_users_action', 'cau_users_nonce' );

		// Sanitise inputs.
		$user_org_id = isset( $_POST['cau_user_org_select'] ) ? absint( $_POST['cau_user_org_select'] ) : '';
		$user_group_id = isset( $_POST['cau_user_group_select'] ) ? absint( $_POST['cau_user_group_select'] ) : '';

		// Maybe set new User Org.
		$this->user_org_set( $user_org_id );

		// Maybe set new User Group.
		$this->user_group_set( $user_group_id );

		/**
		 * Broadcast that the Multisite User update process is finished.
		 *
		 * @since 0.8.1
		 */
		do_action( 'civicrm_admin_utilities_users_updated' );

		// --<
		return true;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the Users registered in CiviCRM.
	 *
	 * @since 0.6.2
	 *
	 * @return array $users The array of Users registered in CiviCRM.
	 */
	public function users_get() {

		// Init return array.
		$users = [];

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return $users;
		}

		// Get users.
		$result = civicrm_api( 'user', 'get', [
			'version' => 3,
		] );

		// Sanity check.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $users;
		}

		// Loop through our users.
		foreach( $result['values'] AS $user ) {

			// Add user data to return array.
			$users[] = [
				'id' => $user['id'],
				'name' => stripslashes( $user['name'] ),
				'description' => isset( $user['description'] ) ? $user['description'] : '',
			];

		}

		// --<
		return $users;

	}



	/**
	 * Get the Users registered in CiviCRM.
	 *
	 * @since 0.6.2
	 */
	public function users_ajax_get() {

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return;
		}

		// Init return.
		$json = [];

		// Sanitise search input.
		$search = isset( $_POST['s'] ) ? sanitize_text_field( $_POST['s'] ) : '';

		// Get users.
		$users = civicrm_api( 'user', 'get', [
			'version' => 3,
			'name' => array( 'LIKE' => '%' . $search . '%' ),
		] );

		// Sanity check.
		if ( ! empty( $users['is_error'] ) AND $users['is_error'] == 1 ) {
			return;
		}

		// Loop through our users.
		foreach( $users['values'] AS $user ) {

			// Add user data to output array.
			$json[] = [
				'id' => $user['id'],
				'name' => stripslashes( $user['name'] ),
				'description' => isset( $user['description'] ) ? $user['description'] : '',
			];

		}

		// Send data.
		$this->send_data( $json );

	}



	/**
	 * Get the User data for a given ID.
	 *
	 * @since 0.9
	 *
	 * @param int $user_id The ID of the user.
	 * @return str $user The user data, with error message on failure.
	 */
	public function user_get( $user_id = 0 ) {

		// Init return.
		$user = [ 'id' => 0 ];

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			$user['name'] = __( 'Failed to initialise CiviCRM.', 'civicrm-admin-utilities' );
			return $user;
		}

		// If no parameter set,
		if ( $user_id === 0 ) {

			// Get CiviCRM user group ID from constant, if set.
			$user_id = defined( 'CIVICRM_DOMAIN_ID' ) ? CIVICRM_DOMAIN_ID : 0;

			// If this fails, get it from config.
			if ( $user_id === 0 ) {
				$user_id = CRM_Core_Config::userID();
			}

			// Bail if we still don't have one.
			if ( $user_id === 0 ) {
				$user['name'] = __( 'Could not find a User ID.', 'civicrm-admin-utilities' );
				return $user;
			}

		}

		// Get user info.
		$user_info = civicrm_api( 'user', 'getsingle', [
			'version' => 3,
			'id' => $user_id,
		] );

		// Bail if there's an error.
		if ( ! empty( $user_info['is_error'] ) AND $user_info['is_error'] == 1 ) {
			$user['name'] = $user_info['error_message'];
			return $user;
		}

		// Populate return array with the items we want.
		$user['id'] = $user_id;
		$user['name'] = $user_info['name'];
		$user['contact_id'] = $user_info['contact_id'];
		$user['version'] = $user_info['user_version'];

		// --<
		return $user;

	}



	/**
	 * Create a User.
	 *
	 * This uses the API Entity supplied by the "CiviCRM Multisite" extension.
	 * The supplied name will be used as the name of both the User, the User
	 * Group and the User Organisation which will be auto-created by the same
	 * call. Additionally, the extension installs a menu for the User.
	 *
	 * @since 0.6.2
	 *
	 * @param str $name The name of the User.
	 * @return str|int The ID of the new User on success, error message otherwise.
	 */
	public function user_create( $name ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return __( 'Failed to initialise CiviCRM.', 'civicrm-admin-utilities' );
		}

		// Bail if "CiviCRM Multisite" extension is not active.
		if ( ! $this->plugin->is_extension_enabled( 'org.civicrm.multisite' ) ) {
			return __( 'CiviCRM Multisite extension must be enabled.', 'civicrm-admin-utilities' );
		}

		// Create user.
		$result = civicrm_api( 'MultisiteUser', 'create', [
			'version' => 3,
			'sequential' => 1,
			'name' => $name,
			'is_transactional' => 'FALSE',
		] );

		// Sanity check.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $result['error_message'];
		}

		// Init ID with error message.
		$id = __( 'User ID not found.', 'civicrm-admin-utilities' );

		// Find ID of new User and override message with ID.
		if ( ! empty( $result['values'] ) ) {
			$user = array_pop( $result['values'] );
			$id = absint( $user['id'] );
		}

		// --<
		return $id;

	}



	/**
	 * Get the User Groups registered in CiviCRM.
	 *
	 * @since 0.6.2
	 */
	public function user_groups_ajax_get() {

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return;
		}

		// Init return.
		$json = [];

		// Sanitise search input.
		$search = isset( $_POST['s'] ) ? sanitize_text_field( $_POST['s'] ) : '';

		// Get user groups.
		$groups = civicrm_api( 'group', 'get', [
			'version' => 3,
			'visibility' => 'User and User Admin Only',
			'title' => array( 'LIKE' => '%' . $search . '%' ),
		] );

		// Sanity check.
		if ( ! empty( $groups['is_error'] ) AND $groups['is_error'] == 1 ) {
			return;
		}

		// Loop through our groups.
		foreach( $groups['values'] AS $group ) {

			// Add group data to output array.
			$json[] = [
				'id' => $group['id'],
				'name' => stripslashes( $group['title'] ),
				'description' => '',
			];

		}

		// Send data.
		$this->send_data( $json );

	}



	/**
	 * Get the User Group data for a given ID.
	 *
	 * @since 0.9
	 *
	 * @param int $user_group_id The ID of the user group.
	 * @return array $user_group The user group data, with error message on failure.
	 */
	public function user_group_get( $user_group_id = 0 ) {

		// Init return.
		$user_group = [ 'id' => 0 ];

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			$user_group['name'] = __( 'Failed to initialise CiviCRM.', 'civicrm-admin-utilities' );
			return $user_group;
		}

		// If no parameter set,
		if ( $user_group_id === 0 ) {

			// Try and find the current User Group ID.
			$user_group_id = $this->user_group_id_get();

			// Bail if we don't find one.
			if ( $user_group_id === 0 ) {
				$user_group['name'] = __( 'Could not find a User Group ID.', 'civicrm-admin-utilities' );
				return $user_group;
			}

		}

		// Get user group info.
		$user_group_info = civicrm_api( 'group', 'getsingle', [
			'version' => 3,
			'id' => $user_group_id,
		] );

		// Bail if there's an error.
		if ( ! empty( $user_group_info['is_error'] ) AND $user_group_info['is_error'] == 1 ) {
			$user_group['name'] = $user_group_info['error_message'];
			return $user_group;
		}

		// Populate return array with the items we want.
		$user_group['id'] = $user_group_id;
		$user_group['name'] = $user_group_info['title'];

		// --<
		return $user_group;

	}



	/**
	 * Get the current User Group ID.
	 *
	 * The priority for determining the ID of the User Group is as follows:
	 *
	 * 1) Check "user_group_id" setting via API.
	 * 2) Check for Group with the same name as the User. (Yes really)
	 *
	 * I'm not persuaded that (2) is good practice - it seems a very brittle
	 * way of storing this relationship. However CiviCRM Core uses that as a
	 * way to get the Group ID so it needs to remain here too. In conclusion,
	 * therefore, only the "user_group_id" setting should be trusted as the
	 * source of the canonical User Group ID.
	 *
	 * The reason there is some commented-out code to look for a unique
	 * "GroupOrganization" linkage via the API is that MultisiteUser.create
	 * makes such a link between the User Group and User Org. However it
	 * is not a unique entry and is likely to be misleading.
	 *
	 * @see CRM_Core_BAO_User::getGroupId()
	 *
	 * @since 0.6.2
	 *
	 * @return int $user_group_id The user group ID, or 0 on failure.
	 */
	public function user_group_id_get() {

		// Set default return.
		$user_group_id = 0;

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return $user_group_id;
		}

		// Get user data.
		$user = $this->user_get();

		// Bail if we don't have a user.
		if ( $user['id'] === 0 ) {
			return $user_group_id;
		}

		// Check "user_group_id" setting.
		$result = civicrm_api( 'setting', 'getsingle', [
			'version' => 3,
			'sequential' => 1,
			'user_id' => $user['id'],
			'return' => 'user_group_id',
		] );

		// If we were successful, cast as integer and return the ID.
		if ( ! empty( $result['user_group_id'] ) AND $result['user_group_id'] != '0' ) {
			$user_group_id = absint( $result['user_group_id'] );
			return $user_group_id;
		}

		// Check for Group with the name of the User.
		$result = civicrm_api( 'group', 'getsingle', [
			'version' => 3,
			'sequential' => 1,
			'title' => $user['name'],
		] );

		// If we were successful, cast as integer and return the ID.
		if ( ! empty( $result['id'] ) ) {
			$user_group_id = absint( $result['id'] );
			return $user_group_id;
		}

		/*
		// Get result from "GroupOrganization".
		$result = civicrm_api( 'GroupOrganization', 'getsingle', [
			'version' => 3,
			'sequential' => 1,
			'organization_id' => $user['contact_id'],
		] );

		// If there is only a single linkage, cast as integer and return the ID.
		if ( ! empty( $result['group_id'] ) ) {
			$user_group_id = absint( $result['group_id'] );
			return $user_group_id;
		}
		*/

		// --<
		return $user_group_id;

	}



	/**
	 * Create a User Group.
	 *
	 * @since 0.6.2
	 */
	public function user_group_create() {

		// Nothing to see yet.

	}



	/**
	 * Set a Group as a User Group.
	 *
	 * @since 0.6.2
	 *
	 * @param int $group_id The ID of the Group.
	 * @return int|bool $group_id The ID of the Group, or false on failure.
	 */
	public function user_group_set( $group_id = 0 ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return false;
		}

		// Sanity check.
		if ( $group_id === 0 OR ! is_numeric( $group_id ) ) {
			return false;
		}

		// Get user data.
		$user = $this->user_get();

		// Bail if we don't have a user.
		if ( $user['id'] === 0 ) {
			return false;
		}

		// Get existing User Group data.
		$user_group = $this->user_group_get();

		// Check "user_group_id" setting.
		$setting = civicrm_api( 'setting', 'getsingle', [
			'version' => 3,
			'sequential' => 1,
			'user_id' => $user['id'],
			'return' => 'user_group_id',
		] );

		// Skip the Setting if there's no change.
		if ( isset( $setting['user_group_id'] ) AND $setting['user_group_id'] !== $group_id ) {

			// Set "user_group_id" setting.
			$result = civicrm_api( 'setting', 'create', [
				'version' => 3,
				'user_id' => $user['id'],
				'user_group_id' => absint( $group_id ),
			] );

			// Log if there's an error.
			if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {
				$e = new Exception();
				$trace = $e->getTraceAsString();
				error_log( print_r( [
					'method' => __METHOD__,
					'result' => $result,
					'backtrace' => $trace,
				], true ) );
			}

		}

		// Check if new User Group has a "GroupOrganization".
		$result = civicrm_api( 'GroupOrganization', 'getsingle', [
			'version' => 3,
			'group_id' => absint( $group_id ),
			'organization_id' => $user['contact_id'],
		] );

		// If it doesn't have one.
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {

			// Create new "GroupOrganization" entry.
			$result = civicrm_api( 'GroupOrganization', 'create', [
				'version' => 3,
				'group_id' => absint( $group_id ),
				'organization_id' => $user['contact_id'],
			] );

		}

		// Bail if there wasn't a previous User Group.
		if ( $user_group['id'] === 0 ) {
			return $group_id;
		}

		// Get all "GroupOrganization" data for previous User Group.
		$result = civicrm_api( 'GroupOrganization', 'get', [
			'version' => 3,
			'sequential' => 1,
			'group_id' => absint( $user_group['id'] ),
		] );

		// If the previous User Group had more than one "GroupOrganization".
		if ( isset( $result['count'] ) AND absint( $result['count'] ) > 1 ) {

			// Init linkage ID.
			$linkage_id = 0;

			// Find the one that's tied to this User Org.
			foreach( $result['values'] AS $linkage ) {
				if ( $linkage['organization_id'] == $user['contact_id'] ) {
					$linkage_id = $linkage['id'];
				}
			}

			// Remove it if we find it.
			if ( $linkage_id !== 0 ) {
				$result = civicrm_api( 'GroupOrganization', 'delete', [
					'version' => 3,
					'id' => $linkage_id,
				] );
			}

		}

		// --<
		return $group_id;

	}



	/**
	 * Get the User Orgs registered in CiviCRM.
	 *
	 * @since 0.6.2
	 */
	public function user_orgs_ajax_get() {

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return;
		}

		// Init return.
		$json = [];

		// Sanitise search input.
		$search = isset( $_POST['s'] ) ? sanitize_text_field( $_POST['s'] ) : '';

		// Get user orgs.
		$orgs = civicrm_api( 'contact', 'get', [
			'version' => 3,
			'contact_type' => "Organization",
			'organization_name' => array( 'LIKE' => '%' . $search . '%' ),
		] );

		// Sanity check.
		if ( ! empty( $orgs['is_error'] ) AND $orgs['is_error'] == 1 ) {
			return;
		}

		// Loop through our orgs.
		foreach( $orgs['values'] AS $org ) {

			// Add org data to output array.
			$json[] = [
				'id' => $org['contact_id'],
				'name' => stripslashes( $org['display_name'] ),
				'description' => '',
			];

		}

		// Send data.
		$this->send_data( $json );

	}



	/**
	 * Get user org data for a given ID.
	 *
	 * @since 0.9
	 *
	 * @param int $user_org_id The ID of the user org.
	 * @return str $user_org The user org data, with error message on failure.
	 */
	public function user_org_get( $user_org_id = 0 ) {

		// Init return.
		$user_org = [ 'id' => 0 ];

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			$user_org['name'] = __( 'Failed to initialise CiviCRM.', 'civicrm-admin-utilities' );
			return $user_org;
		}

		// If no parameter specified.
		if ( $user_org_id === 0 ) {

			// Get CiviCRM user org ID from constant, if set.
			$user_org_id = defined( 'CIVICRM_DOMAIN_ORG_ID' ) ? CIVICRM_DOMAIN_ORG_ID : 0;

			// If this fails, get it from the user.
			if ( $user_org_id === 0 ) {

				// Get user data.
				$user = $this->user_get();

				// If this fails, try and get it from the user.
				if ( $user['id'] !== 0 ) {
					$user_org_id = isset( $user['contact_id'] ) ? $user['contact_id'] : 0;
				}

				// Bail if we still don't have one.
				if ( $user_org_id === 0 ) {
					$user_org['name'] = __( 'Could not find a User Org ID.', 'civicrm-admin-utilities' );
					return $user_org;
				}

			}

		}

		// Get user org info.
		$user_org_info = civicrm_api( 'contact', 'getsingle', [
			'version' => 3,
			'id' => $user_org_id,
		] );

		// Bail if there's an error.
		if ( ! empty( $user_org_info['is_error'] ) AND $user_org_info['is_error'] == 1 ) {
			$user_org['name'] = $user_org_info['error_message'];
			return $user_org;
		}

		// Populate return array with the items we want.
		$user_org['id'] = $user_org_id;
		$user_org['name'] = $user_org_info['display_name'];

		// --<
		return $user_org;

	}



	/**
	 * Create a User Organisation.
	 *
	 * @since 0.6.2
	 */
	public function user_org_create() {

		// Nothing to see yet.

	}



	/**
	 * Set an Organisation as a User Organisation.
	 *
	 * @since 0.6.2
	 *
	 * @param int $org_id The ID of the Organisation.
	 * @return int|bool $org_id The ID of the Organisation, or false on failure.
	 */
	public function user_org_set( $org_id = 0 ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return false;
		}

		// Sanity check.
		if ( $org_id === 0 OR ! is_numeric( $org_id ) ) {
			return false;
		}

		// Get user data.
		$user = $this->user_get();

		// Bail if we don't have a user.
		if ( $user['id'] === 0 ) {
			return false;
		}

		// Bail if there's no change.
		if ( $user['contact_id'] == $org_id ) {
			return $org_id;
		}

		// Update User.
		$result = civicrm_api( 'user', 'create', [
			'version' => 3,
			'id' => $user['id'],
			'contact_id' => absint( $org_id ),
		] );

		// TODO: Do we need to reassign all groups to this Org via "GroupOrganization" API?

		// --<
		return $org_id;

	}



	// -------------------------------------------------------------------------



	/**
	 * Send JSON data to the browser.
	 *
	 * @since 0.6.2
	 *
	 * @param array $data The data to send.
	 */
	public function send_data( $data ) {

		// Bail if this not an AJAX request.
		if ( ! defined( 'DOING_AJAX' ) OR ! DOING_AJAX ) {
			return;
		}

		// Set reasonable headers.
		header('Content-type: text/plain');
		header("Cache-Control: no-cache");
		header("Expires: -1");

		// Echo and die.
		echo json_encode( $data );
		exit();

	}



} // Class ends.



