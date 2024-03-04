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
	 * Plugin object.
	 *
	 * @since 0.9
	 * @access public
	 * @var object
	 */
	public $plugin;

	/**
	 * Page identifier.
	 *
	 * @since 0.9
	 * @access public
	 * @var string
	 */
	public $page = 'user_table';

	/**
	 * Users Page slug.
	 *
	 * @since 0.9
	 * @access public
	 * @var string
	 */
	public $users_page_slug = 'civicrm_au_users';

	/**
	 * Users Listing page "hook".
	 *
	 * @since 0.9
	 * @access public
	 * @var string
	 */
	public $users_page;

	/**
	 * User Table object.
	 *
	 * @since 0.9
	 * @access public
	 * @var CAU_Single_Users_List_Table
	 */
	public $user_table;

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
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( is_admin() && ! empty( $_REQUEST['page'] ) && $this->users_page_slug === $_REQUEST['page'] ) {
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
		if ( 'admin_page_' . $this->users_page_slug . '_per_page' !== $option ) {
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
	 * @param array     $hidden The existing array of hidden columns.
	 * @param WP_Screen $screen The current screen object.
	 * @return array $hidden The modified array of hidden columns.
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

		// Hook into early action for our page init handler.
		add_action( 'load-' . $this->users_page, [ $this, 'page_init' ] );

		// Ensure correct menu item is highlighted.
		add_action( 'admin_head-' . $this->users_page, [ $this->plugin->single, 'admin_menu_highlight' ], 50 );

		// Add help text.
		add_action( 'admin_head-' . $this->users_page, [ $this, 'admin_head' ], 50 );

		// Add scripts and styles.
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
		if ( ! in_array( $screen->id, $pages, true ) ) {
			return $screen;
		}

		// Build tab args.
		$args = [
			'id'      => $this->users_page_slug,
			'title'   => __( 'Manage Users', 'civicrm-admin-utilities' ),
			'content' => $this->admin_help_get(),
		];

		// Add a tab - we can add more later.
		$screen->add_help_tab( $args );

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
		if ( 'user_table' === $this->page ) {

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
			add_screen_option(
				'per_page',
				[ 'label' => _x( 'Users', 'Users per page (screen options)', 'civicrm-admin-utilities' ) ]
			);

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
		if ( 'user_table' === $this->page ) {
			$this->page_users_table();
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
		 */
		$messages = apply_filters( 'cau/single_users/user_table/messages', [] );

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/site-users-table.php';

	}

	/**
	 * Enqueue stylesheets for the Site User page.
	 *
	 * @since 0.9
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
	 * @param str   $active_tab The key of the active tab in the subpage URLs array.
	 */
	public function page_add_tab( $urls, $active_tab ) {

		// Define title.
		$title = __( 'Manage Users', 'civicrm-admin-utilities' );

		// Default to inactive.
		$active = '';

		// Make active if it's our subpage.
		if ( 'users' === $active_tab ) {
			$active = ' nav-tab-active';
		}

		// Render tab. Users Listing page URL is already escaped.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<a href="' . $urls['users'] . '" class="nav-tab' . esc_attr( $active ) . '">' . esc_html( $title ) . '</a>' . "\n";

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

}
