<?php
/**
 * Multidomain Class.
 *
 * Handles Multidomain functionality.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Admin Utilities Multidomain Class.
 *
 * A class that encapsulates Multidomain Settings page functionality.
 *
 * @since 0.5.4
 */
class CiviCRM_Admin_Utilities_Multidomain {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * Multidomain Settings page reference.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $multidomain_page The reference to the Multidomain Settings page.
	 */
	public $multidomain_page;



	/**
	 * Constructor.
	 *
	 * @since 0.5.4
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
	 * @since 0.5.4
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.5.4
	 */
	public function register_hooks() {

		// Add Domain subpage to Network Settings menu.
		add_action( 'network_admin_menu', [ $this, 'network_admin_menu' ], 30 );

		// Add meta boxes to Network Domain subpage.
		add_action( 'add_meta_boxes', [ $this, 'network_meta_boxes_add' ], 11, 1 );

		// Add Domain subpage to Single Site Settings menu.
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );

		// Add meta boxes to Single Site Domain subpage.
		add_action( 'add_meta_boxes', [ $this, 'meta_boxes_add' ], 11, 1 );

		// Add Domains AJAX handler.
		add_action( 'wp_ajax_cau_domains_get', [ $this, 'domains_ajax_get' ] );

		// Add Domain Groups AJAX handler.
		add_action( 'wp_ajax_cau_domain_groups_get', [ $this, 'domain_groups_ajax_get' ] );

		// Add Domain Orgs AJAX handler.
		add_action( 'wp_ajax_cau_domain_orgs_get', [ $this, 'domain_orgs_ajax_get' ] );

	}



	// -------------------------------------------------------------------------




	/**
	 * Add network admin menu item(s) for this plugin.
	 *
	 * @since 0.5.4
	 */
	public function network_admin_menu() {

		// We must be network admin in Multisite.
		if ( ! is_super_admin() ) {
			return;
		}

		// Add settings page.
		$this->network_multidomain_page = add_submenu_page(
			'cau_network_parent', // Parent slug.
			__( 'CiviCRM Admin Utilities: Domain', 'civicrm-admin-utilities' ), // Page title.
			__( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ), // Menu title.
			'manage_network_plugins', // Required caps.
			'cau_network_multidomain', // Slug name.
			[ $this, 'page_network_multidomain' ] // Callback.
		);

		// Ensure correct menu item is highlighted.
		add_action( 'admin_head-' . $this->network_multidomain_page, [ $this->plugin->multisite, 'network_menu_highlight' ], 50 );

		// Add help text.
		add_action( 'admin_head-' . $this->network_multidomain_page, [ $this, 'network_admin_head' ], 50 );

		// Add scripts and styles.
		add_action( 'admin_print_styles-' . $this->network_multidomain_page, [ $this, 'page_network_multidomain_css' ] );
		//add_action( 'admin_print_scripts-' . $this->network_multidomain_page, [ $this, 'page_network_multidomain_js' ] );

		// Try and update options.
		$saved = $this->settings_update_router();

		// Filter the list of Single Site subpages and add Multidomain page.
		add_filter( 'civicrm_admin_utilities_network_subpages', [ $this, 'network_admin_subpages_filter' ] );

		// Filter the list of network page URLs and add Multidomain page URL.
		add_filter( 'civicrm_admin_utilities_network_page_urls', [ $this, 'page_network_urls_filter' ] );

		// Filter the "show tabs" flag for setting templates.
		add_filter( 'civicrm_admin_utilities_network_show_tabs', [ $this, 'page_network_show_tabs' ] );

		// Add tab to setting templates.
		add_filter( 'civicrm_admin_utilities_network_nav_tabs', [ $this, 'page_network_add_tab' ], 10, 2 );

	}



	/**
	 * Append the Multidomain Settings page to network subpages.
	 *
	 * This ensures that the correct parent menu item is highlighted for our
	 * Multidomain subpage in Multisite installs.
	 *
	 * @since 0.6.2
	 *
	 * @param array $subpages The existing list of subpages.
	 * @return array $subpages The modified list of subpages.
	 */
	public function network_admin_subpages_filter( $subpages ) {

		// Add Multidomain Settings page.
		$subpages[] = 'cau_network_multidomain';

		// --<
		return $subpages;

	}



	/**
	 * Initialise plugin help for network admin.
	 *
	 * @since 0.5.4
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
	 * @since 0.5.4
	 *
	 * @param object $screen The existing WordPress screen object.
	 * @return object $screen The amended WordPress screen object.
	 */
	public function network_admin_help( $screen ) {

		// Init page IDs.
		$pages = [
			$this->network_multidomain_page . '-network',
		];

		// Kick out if not our screen.
		if ( ! in_array( $screen->id, $pages ) ) {
			return $screen;
		}

		// Add a tab - we can add more later.
		$screen->add_help_tab( [
			'id'      => 'cau_network_multidomain',
			'title'   => __( 'CiviCRM Admin Utilities Domain', 'civicrm-admin-utilities' ),
			'content' => $this->network_admin_help_get(),
		] );

		// --<
		return $screen;

	}



	/**
	 * Get help text for network admin.
	 *
	 * @since 0.5.4
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
	 * Show our network Multidomain Settings page.
	 *
	 * @since 0.6.2
	 */
	public function page_network_multidomain() {

		// Disallow if not network admin in Multisite.
		if ( is_network_admin() AND ! is_super_admin() ) {
			wp_die( __( 'You do not have permission to access this page.', 'civicrm-admin-utilities' ) );
		}

		// Check user permissions.
		if ( ! current_user_can( 'manage_network_plugins' ) ) {
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

		// Get domains.
		$domains = $this->domains_get();

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/network-multidomain.php';

	}



	/**
	 * Enqueue stylesheet for the Network Admin Domain page.
	 *
	 * @since 0.6.2
	 */
	public function page_network_multidomain_css() {

		// Add stylesheet.
		wp_enqueue_style(
			'civicrm_admin_utilities_network_multidomain_css',
			plugins_url( 'assets/css/civicrm-admin-utilities-network-multidomain.css', CIVICRM_ADMIN_UTILITIES_FILE ),
			false,
			CIVICRM_ADMIN_UTILITIES_VERSION, // version
			'all' // media
		);

	}



	/**
	 * Enqueue Javascript on the Network Admin Domain page.
	 *
	 * @since 0.6.2
	 */
	public function page_network_multidomain_js() {

		// Add Javascript plus dependencies.
		wp_enqueue_script(
			'civicrm_admin_utilities_network_multidomain_js',
			plugins_url( 'assets/js/civicrm-admin-utilities-network-multidomain.js', CIVICRM_ADMIN_UTILITIES_FILE ),
			[ 'jquery' ],
			CIVICRM_ADMIN_UTILITIES_VERSION // version
		);

	}



	/**
	 * Append the Multidomain page URL to network subpage URLs.
	 *
	 * @since 0.6.2
	 *
	 * @param array $urls The existing list of URLs.
	 * @return array $urls The modified list of URLs.
	 */
	public function page_network_urls_filter( $urls ) {

		// Add Multidomain Settings page.
		$urls['multidomain'] = $this->plugin->multisite->network_menu_page_url( 'cau_network_multidomain', false );

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
		$title = __( 'Domains', 'civicrm-admin-utilities' );

		// Default to inactive.
		$active = '';

		// Make active if it's our subpage.
		if ( $active_tab === 'multidomain' ) {
			$active = ' nav-tab-active';
		}

		// Render tab.
		echo '<a href="' . $urls['multidomain'] . '" class="nav-tab' . $active . '">' . $title . '</a>' . "\n";

	}



	// -------------------------------------------------------------------------




	/**
	 * Register meta boxes for our Network "Domains" page.
	 *
	 * @since 0.8.1
	 *
	 * @param str $screen_id The Admin Page Screen ID.
	 */
	public function network_meta_boxes_add( $screen_id ) {

		// Define valid Screen IDs.
		$screen_ids = [
			'admin_page_cau_network_multidomain-network',
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

		// Get domains.
		$data['domains'] = $this->domains_get();

		// Check if "CiviCRM Multisite" extension is active.
		$data['multisite'] = false;
		if ( $this->plugin->is_extension_enabled( 'org.civicrm.multisite' ) ) {
			$data['multisite'] = true;
		}

		// Create CiviCRM Network Settings metabox.
		add_meta_box(
			'civicrm_au_network_domains',
			__( 'CiviCRM Domains', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_network_domain_info_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core', // Vertical placement: options are 'core', 'high', 'low'.
			$data
		);

		// Bail if "multisite" is not present.
		if ( $data['multisite'] === false ) {
			return;
		}

		// Create "Create Domain" metabox.
		add_meta_box(
			'civicrm_au_network_domain_create',
			__( 'Create Domain', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_network_domain_create_render' ], // Callback.
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
	 * Render a Submit meta box for our Network "Domain" page.
	 *
	 * @since 0.8.1
	 */
	public function meta_box_network_submit_render() {

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/network-metabox-domain-submit.php';

	}



	/**
	 * Render "CiviCRM Domains" meta box for our Network "Domain" page.
	 *
	 * @since 0.8.1
	 *
	 * @param mixed $unused Unused param.
	 * @param array $metabox Array containing id, title, callback, and args elements.
	 */
	public function meta_box_network_domain_info_render( $unused = null, $metabox ) {

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/network-metabox-domain-info.php';

	}



	/**
	 * Render "Create Domain" meta box for our Network "Domain" page.
	 *
	 * @since 0.8.1
	 *
	 * @param mixed $unused Unused param.
	 * @param array $metabox Array containing id, title, callback, and args elements.
	 */
	public function meta_box_network_domain_create_render( $unused = null, $metabox ) {

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/network-metabox-domain-create.php';

	}



	// -------------------------------------------------------------------------




	/**
	 * Add admin menu item(s) for this plugin.
	 *
	 * @since 0.5.4
	 */
	public function admin_menu() {

		/**
		 * Set capability but allow overrides.
		 *
		 * @since 0.5.4
		 *
		 * @param str The default capability for access to domain page.
		 * @return str The modified capability for access to domain page.
		 */
		$capability = apply_filters( 'civicrm_admin_utilities_page_domain_cap', 'manage_options' );

		// Check user permissions.
		if ( ! current_user_can( $capability ) ) {
			return;
		}

		// Add Domain page.
		$this->multidomain_page = add_submenu_page(
			'cau_parent', // Parent slug.
			__( 'CiviCRM Admin Utilities: Domain', 'civicrm-admin-utilities' ), // Page title.
			__( 'Domain', 'civicrm-admin-utilities' ), // Menu title.
			$capability, // Required caps.
			'civicrm_au_multidomain', // Slug name.
			[ $this, 'page_multidomain' ] // Callback.
		);

		// Ensure correct menu item is highlighted.
		add_action( 'admin_head-' . $this->multidomain_page, [ $this->plugin->single, 'admin_menu_highlight' ], 50 );

		// Add help text.
		add_action( 'admin_head-' . $this->multidomain_page, [ $this, 'admin_head' ], 50 );

		// Add scripts and styles.
		add_action( 'admin_print_scripts-' . $this->multidomain_page, [ $this, 'page_multidomain_js' ] );
		add_action( 'admin_print_styles-' . $this->multidomain_page, [ $this, 'page_multidomain_css' ] );

		// Try and update options.
		$saved = $this->settings_update_router();

		// Filter the list of Single Site subpages and add Multidomain page.
		add_filter( 'civicrm_admin_utilities_subpages', [ $this, 'admin_subpages_filter' ] );

		// Filter the list of Single Site page URLs and add Multidomain page URL.
		add_filter( 'civicrm_admin_utilities_page_urls', [ $this, 'page_urls_filter' ] );

		// Filter the "show tabs" flag for setting templates.
		add_filter( 'civicrm_admin_utilities_show_tabs', [ $this, 'page_show_tabs' ] );

		// Add tab to setting templates.
		add_filter( 'civicrm_admin_utilities_settings_nav_tabs', [ $this, 'page_add_tab' ], 10, 2 );

	}



	/**
	 * Append the Multidomain Settings page to Single Site subpages.
	 *
	 * This ensures that the correct parent menu item is highlighted for our
	 * Multidomain subpage in Single Site installs.
	 *
	 * @since 0.5.4
	 *
	 * @param array $subpages The existing list of subpages.
	 * @return array $subpages The modified list of subpages.
	 */
	public function admin_subpages_filter( $subpages ) {

		// Add Multidomain Settings page.
		$subpages[] = 'civicrm_au_multidomain';

		// --<
		return $subpages;

	}



	/**
	 * Initialise plugin help.
	 *
	 * @since 0.5.4
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
	 * @since 0.5.4
	 *
	 * @param object $screen The existing WordPress screen object.
	 * @return object $screen The amended WordPress screen object.
	 */
	public function admin_help( $screen ) {

		// Init page IDs.
		$pages = [
			$this->multidomain_page,
		];

		// Kick out if not our screen.
		if ( ! in_array( $screen->id, $pages ) ) {
			return $screen;
		}

		// Add a tab - we can add more later.
		$screen->add_help_tab( [
			'id'      => 'civicrm_au_multidomain',
			'title'   => __( 'CiviCRM Admin Utilities Domain', 'civicrm-admin-utilities' ),
			'content' => $this->admin_help_get(),
		] );

		// --<
		return $screen;

	}



	/**
	 * Get help text.
	 *
	 * @since 0.5.4
	 *
	 * @return string $help The help text formatted as HTML.
	 */
	public function admin_help_get() {

		// Stub help text, to be developed further.
		$help = '<p>' . __( 'Domain Settings: For further information about using CiviCRM Admin Utilities, please refer to the readme.txt file that comes with this plugin.', 'civicrm-admin-utilities' ) . '</p>';

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
			'admin_page_civicrm_au_multidomain',
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

		// Get domain name.
		$data['domain'] = $this->domain_get();

		// Get domain group name.
		$data['domain_group'] = $this->domain_group_get();

		// Get domain org data.
		$data['domain_org'] = $this->domain_org_get();

		// Check if "CiviCRM Multisite" extension is active.
		$data['multisite'] = false;
		if ( $this->plugin->is_extension_enabled( 'org.civicrm.multisite' ) ) {
			$data['multisite'] = true;
		}

		// Check if "Multisite" is enabled for this Domain.
		$data['enabled'] = civicrm_api( 'setting', 'getvalue', [
			'version' => 3,
			'domain_id' => $data['domain']['id'],
			'name' => 'is_enabled',
			'group' => 'Multi Site Preferences',
		] );

		// Get the "Multi Site Settings" page URL.
		$data['multisite_url'] = $this->plugin->single->get_link( 'civicrm/admin/setting/preferences/multisite', 'reset=1' );

		// Create "Domain Info" metabox.
		add_meta_box(
			'civicrm_au_domain_info',
			__( 'CiviCRM Domain Information', 'civicrm-admin-utilities' ),
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

		// Create "Edit Domain" metabox.
		add_meta_box(
			'civicrm_au_domain_edit',
			__( 'Edit Domain', 'civicrm-admin-utilities' ),
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
	 * Render a "Domain Info" meta box on Admin screen.
	 *
	 * @since 0.8.1
	 *
	 * @param mixed $unused Unused param.
	 * @param array $metabox Array containing id, title, callback, and args elements.
	 */
	public function meta_box_info_render( $unused = null, $metabox ) {

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/site-metabox-domain-info.php';

	}



	/**
	 * Render "Edit Domain" meta box on Admin screen.
	 *
	 * @since 0.8.1
	 *
	 * @param mixed $unused Unused param.
	 * @param array $metabox Array containing id, title, callback, and args elements.
	 */
	public function meta_box_edit_render( $unused = null, $metabox ) {

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/site-metabox-domain-edit.php';

	}



	/**
	 * Render a Submit meta box on Admin screen.
	 *
	 * @since 0.8.1
	 */
	public function meta_box_submit_render() {

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/site-metabox-domain-submit.php';

	}



	// -------------------------------------------------------------------------



	/**
	 * Show our Multidomain Settings page.
	 *
	 * @since 0.5.4
	 */
	public function page_multidomain() {

		/**
		 * Set capability but allow overrides.
		 *
		 * @since 0.5.4
		 *
		 * @param str The default capability for access to domain page.
		 * @return str The modified capability for access to domain page.
		 */
		$capability = apply_filters( 'civicrm_admin_utilities_page_domain_cap', 'manage_options' );

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
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/site-multidomain.php';

	}



	/**
	 * Enqueue stylesheets for the Site Domain page.
	 *
	 * @since 0.6.2
	 */
	public function page_multidomain_css() {

		// Register Select2 styles.
		wp_register_style(
			'cau_site_domain_select2_css',
			set_url_scheme( 'http://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.min.css' )
		);

		// Enqueue styles.
		wp_enqueue_style( 'cau_site_domain_select2_css' );

		// Add page-specific stylesheet.
		wp_enqueue_style(
			'cau_site_domain_css',
			plugins_url( 'assets/css/civicrm-admin-utilities-site-multidomain.css', CIVICRM_ADMIN_UTILITIES_FILE ),
			false,
			CIVICRM_ADMIN_UTILITIES_VERSION, // version
			'all' // media
		);

	}



	/**
	 * Enqueue Javascripts on the Site Domain page.
	 *
	 * @since 0.6.2
	 */
	public function page_multidomain_js() {

		// Register Select2.
		wp_register_script(
			'cau_site_domain_select2_js',
			set_url_scheme( 'http://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/select2.min.js' ),
			[ 'jquery' ]
		);

		// Enqueue Select2 script.
		wp_enqueue_script( 'cau_site_domain_select2_js' );

		// Enqueue our Javascript plus dependencies.
		wp_enqueue_script(
			'cau_site_domain_js',
			plugins_url( 'assets/js/civicrm-admin-utilities-site-multidomain.js', CIVICRM_ADMIN_UTILITIES_FILE ),
			[ 'jquery', 'cau_site_domain_select2_js' ],
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
			'cau_site_domain_js',
			'CAU_Site_Domain',
			$vars
		);

	}



	/**
	 * Append the Multidomain Settings page URL to Single Site subpage URLs.
	 *
	 * @since 0.5.4
	 *
	 * @param array $urls The existing list of URLs.
	 * @return array $urls The modified list of URLs.
	 */
	public function page_urls_filter( $urls ) {

		// Add Multidomain Settings page.
		$urls['multidomain'] = menu_page_url( 'civicrm_au_multidomain', false );

		// --<
		return $urls;

	}



	/**
	 * Show subpage tabs on settings pages.
	 *
	 * @since 0.5.4
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
	 * @since 0.5.4
	 *
	 * @param array $urls The array of subpage URLs.
	 * @param str The key of the active tab in the subpage URLs array.
	 */
	public function page_add_tab( $urls, $active_tab ) {

		// Define title.
		$title = __( 'Domain', 'civicrm-admin-utilities' );

		// Default to inactive.
		$active = '';

		// Make active if it's our subpage.
		if ( $active_tab === 'multidomain' ) {
			$active = ' nav-tab-active';
		}

		// Render tab.
		echo '<a href="' . $urls['multidomain'] . '" class="nav-tab' . $active . '">' . $title . '</a>' . "\n";

	}



	/**
	 * Get the URL for the form action.
	 *
	 * @since 0.5.4
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
	 * @since 0.5.4
	 *
	 * @return bool $result True on success, false otherwise.
	 */
	public function settings_update_router() {

		// Init return.
		$result = false;

	 	// Was the "Network Domain" form submitted?
		if ( isset( $_POST['cau_network_multidomain_submit'] ) ) {
			return $this->settings_network_multidomain_update();
		}

	 	// Was the "Domain" form submitted?
		if ( isset( $_POST['cau_multidomain_submit'] ) ) {
			return $this->settings_multidomain_update();
		}

		// --<
		return $result;

	}



	/**
	 * Update options supplied by our Network Multidomain Settings page.
	 *
	 * @since 0.6.2
	 *
	 * @return bool True if successful, false otherwise (always true at present).
	 */
	public function settings_network_multidomain_update() {

		// Check that we trust the source of the data.
		check_admin_referer( 'cau_network_multidomain_action', 'cau_network_multidomain_nonce' );

		// Sanitise input.
		$domain_name = isset( $_POST['cau_domain_name'] ) ? sanitize_text_field( $_POST['cau_domain_name'] ) : '';

		// Bail if we get nothing through.
		if ( empty( $domain_name ) ) {
			return false;
		}

		// Okay, create domain.
		$result = $this->domain_create( $domain_name );

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
		 * Broadcast that the Network Multidomain update process is finished.
		 *
		 * @since 0.8.1
		 */
		do_action( 'civicrm_admin_utilities_network_multidomain_updated' );

		// --<
		return true;

	}



	/**
	 * Update options supplied by our Multidomain Settings page.
	 *
	 * @since 0.5.4
	 *
	 * @return bool True if successful, false otherwise (always true at present).
	 */
	public function settings_multidomain_update() {

		// Check that we trust the source of the data.
		check_admin_referer( 'cau_multidomain_action', 'cau_multidomain_nonce' );

		// Sanitise inputs.
		$domain_org_id = isset( $_POST['cau_domain_org_select'] ) ? absint( $_POST['cau_domain_org_select'] ) : '';
		$domain_group_id = isset( $_POST['cau_domain_group_select'] ) ? absint( $_POST['cau_domain_group_select'] ) : '';

		// Maybe set new Domain Org.
		$this->domain_org_set( $domain_org_id );

		// Maybe set new Domain Group.
		$this->domain_group_set( $domain_group_id );

		/**
		 * Broadcast that the Single Site Multidomain update process is finished.
		 *
		 * @since 0.8.1
		 */
		do_action( 'civicrm_admin_utilities_multidomain_updated' );

		// --<
		return true;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the Domains registered in CiviCRM.
	 *
	 * @since 0.6.2
	 *
	 * @return array $domains The array of Domains registered in CiviCRM.
	 */
	public function domains_get() {

		// Init return array.
		$domains = [];

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return $domains;
		}

		// Get domains.
		$result = civicrm_api( 'domain', 'get', [
			'version' => 3,
		] );

		// Sanity check.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $domains;
		}

		// Loop through our domains.
		foreach( $result['values'] AS $domain ) {

			// Add domain data to return array.
			$domains[] = [
				'id' => $domain['id'],
				'name' => stripslashes( $domain['name'] ),
				'description' => isset( $domain['description'] ) ? $domain['description'] : '',
			];

		}

		// --<
		return $domains;

	}



	/**
	 * Get the Domains registered in CiviCRM.
	 *
	 * @since 0.6.2
	 */
	public function domains_ajax_get() {

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return;
		}

		// Init return.
		$json = [];

		// Sanitise search input.
		$search = isset( $_POST['s'] ) ? sanitize_text_field( $_POST['s'] ) : '';

		// Get domains.
		$domains = civicrm_api( 'domain', 'get', [
			'version' => 3,
			'name' => array( 'LIKE' => '%' . $search . '%' ),
		] );

		// Sanity check.
		if ( ! empty( $domains['is_error'] ) AND $domains['is_error'] == 1 ) {
			return;
		}

		// Loop through our domains.
		foreach( $domains['values'] AS $domain ) {

			// Add domain data to output array.
			$json[] = [
				'id' => $domain['id'],
				'name' => stripslashes( $domain['name'] ),
				'description' => isset( $domain['description'] ) ? $domain['description'] : '',
			];

		}

		// Send data.
		$this->send_data( $json );

	}



	/**
	 * Get the Domain data for a given ID.
	 *
	 * @since 0.5.4
	 *
	 * @param int $domain_id The ID of the domain.
	 * @return str $domain The domain data, with error message on failure.
	 */
	public function domain_get( $domain_id = 0 ) {

		// Init return.
		$domain = [ 'id' => 0 ];

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			$domain['name'] = __( 'Failed to initialise CiviCRM.', 'civicrm-admin-utilities' );
			return $domain;
		}

		// If no parameter set,
		if ( $domain_id === 0 ) {

			// Get CiviCRM domain group ID from constant, if set.
			$domain_id = defined( 'CIVICRM_DOMAIN_ID' ) ? CIVICRM_DOMAIN_ID : 0;

			// If this fails, get it from config.
			if ( $domain_id === 0 ) {
				$domain_id = CRM_Core_Config::domainID();
			}

			// Bail if we still don't have one.
			if ( $domain_id === 0 ) {
				$domain['name'] = __( 'Could not find a Domain ID.', 'civicrm-admin-utilities' );
				return $domain;
			}

		}

		// Get domain info.
		$domain_info = civicrm_api( 'domain', 'getsingle', [
			'version' => 3,
			'id' => $domain_id,
		] );

		// Bail if there's an error.
		if ( ! empty( $domain_info['is_error'] ) AND $domain_info['is_error'] == 1 ) {
			$domain['name'] = $domain_info['error_message'];
			return $domain;
		}

		// Populate return array with the items we want.
		$domain['id'] = $domain_id;
		$domain['name'] = $domain_info['name'];
		$domain['contact_id'] = $domain_info['contact_id'];
		$domain['version'] = $domain_info['domain_version'];

		// --<
		return $domain;

	}



	/**
	 * Create a Domain.
	 *
	 * This uses the API Entity supplied by the "CiviCRM Multisite" extension.
	 * The supplied name will be used as the name of both the Domain, the Domain
	 * Group and the Domain Organisation which will be auto-created by the same
	 * call. Additionally, the extension installs a menu for the Domain.
	 *
	 * @since 0.6.2
	 *
	 * @param str $name The name of the Domain.
	 * @return str|int The ID of the new Domain on success, error message otherwise.
	 */
	public function domain_create( $name ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return __( 'Failed to initialise CiviCRM.', 'civicrm-admin-utilities' );
		}

		// Bail if "CiviCRM Multisite" extension is not active.
		if ( ! $this->plugin->is_extension_enabled( 'org.civicrm.multisite' ) ) {
			return __( 'CiviCRM Multisite extension must be enabled.', 'civicrm-admin-utilities' );
		}

		// Create domain.
		$result = civicrm_api( 'MultisiteDomain', 'create', [
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
		$id = __( 'Domain ID not found.', 'civicrm-admin-utilities' );

		// Find ID of new Domain and override message with ID.
		if ( ! empty( $result['values'] ) ) {
			$domain = array_pop( $result['values'] );
			$id = absint( $domain['id'] );
		}

		// --<
		return $id;

	}



	/**
	 * Get the Domain Groups registered in CiviCRM.
	 *
	 * @since 0.6.2
	 */
	public function domain_groups_ajax_get() {

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return;
		}

		// Init return.
		$json = [];

		// Sanitise search input.
		$search = isset( $_POST['s'] ) ? sanitize_text_field( $_POST['s'] ) : '';

		// Get domain groups.
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
	 * Get the Domain Group data for a given ID.
	 *
	 * @since 0.5.4
	 *
	 * @param int $domain_group_id The ID of the domain group.
	 * @return array $domain_group The domain group data, with error message on failure.
	 */
	public function domain_group_get( $domain_group_id = 0 ) {

		// Init return.
		$domain_group = [ 'id' => 0 ];

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			$domain_group['name'] = __( 'Failed to initialise CiviCRM.', 'civicrm-admin-utilities' );
			return $domain_group;
		}

		// If no parameter set,
		if ( $domain_group_id === 0 ) {

			// Try and find the current Domain Group ID.
			$domain_group_id = $this->domain_group_id_get();

			// Bail if we don't find one.
			if ( $domain_group_id === 0 ) {
				$domain_group['name'] = __( 'Could not find a Domain Group ID.', 'civicrm-admin-utilities' );
				return $domain_group;
			}

		}

		// Get domain group info.
		$domain_group_info = civicrm_api( 'group', 'getsingle', [
			'version' => 3,
			'id' => $domain_group_id,
		] );

		// Bail if there's an error.
		if ( ! empty( $domain_group_info['is_error'] ) AND $domain_group_info['is_error'] == 1 ) {
			$domain_group['name'] = $domain_group_info['error_message'];
			return $domain_group;
		}

		// Populate return array with the items we want.
		$domain_group['id'] = $domain_group_id;
		$domain_group['name'] = $domain_group_info['title'];

		// --<
		return $domain_group;

	}



	/**
	 * Get the current Domain Group ID.
	 *
	 * The priority for determining the ID of the Domain Group is as follows:
	 *
	 * 1) Check "domain_group_id" setting via API.
	 * 2) Check for Group with the same name as the Domain. (Yes really)
	 *
	 * I'm not persuaded that (2) is good practice - it seems a very brittle
	 * way of storing this relationship. However CiviCRM Core uses that as a
	 * way to get the Group ID so it needs to remain here too. In conclusion,
	 * therefore, only the "domain_group_id" setting should be trusted as the
	 * source of the canonical Domain Group ID.
	 *
	 * The reason there is some commented-out code to look for a unique
	 * "GroupOrganization" linkage via the API is that MultisiteDomain.create
	 * makes such a link between the Domain Group and Domain Org. However it
	 * is not a unique entry and is likely to be misleading.
	 *
	 * @see CRM_Core_BAO_Domain::getGroupId()
	 *
	 * @since 0.6.2
	 *
	 * @return int $domain_group_id The domain group ID, or 0 on failure.
	 */
	public function domain_group_id_get() {

		// Set default return.
		$domain_group_id = 0;

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return $domain_group_id;
		}

		// Get domain data.
		$domain = $this->domain_get();

		// Bail if we don't have a domain.
		if ( $domain['id'] === 0 ) {
			return $domain_group_id;
		}

		// Check "domain_group_id" setting.
		$result = civicrm_api( 'setting', 'getsingle', [
			'version' => 3,
			'sequential' => 1,
			'domain_id' => $domain['id'],
			'return' => 'domain_group_id',
		] );

		// If we were successful, cast as integer and return the ID.
		if ( ! empty( $result['domain_group_id'] ) AND $result['domain_group_id'] != '0' ) {
			$domain_group_id = absint( $result['domain_group_id'] );
			return $domain_group_id;
		}

		// Check for Group with the name of the Domain.
		$result = civicrm_api( 'group', 'getsingle', [
			'version' => 3,
			'sequential' => 1,
			'title' => $domain['name'],
		] );

		// If we were successful, cast as integer and return the ID.
		if ( ! empty( $result['id'] ) ) {
			$domain_group_id = absint( $result['id'] );
			return $domain_group_id;
		}

		/*
		// Get result from "GroupOrganization".
		$result = civicrm_api( 'GroupOrganization', 'getsingle', [
			'version' => 3,
			'sequential' => 1,
			'organization_id' => $domain['contact_id'],
		] );

		// If there is only a single linkage, cast as integer and return the ID.
		if ( ! empty( $result['group_id'] ) ) {
			$domain_group_id = absint( $result['group_id'] );
			return $domain_group_id;
		}
		*/

		// --<
		return $domain_group_id;

	}



	/**
	 * Create a Domain Group.
	 *
	 * @since 0.6.2
	 */
	public function domain_group_create() {

		// Nothing to see yet.

	}



	/**
	 * Set a Group as a Domain Group.
	 *
	 * @since 0.6.2
	 *
	 * @param int $group_id The ID of the Group.
	 * @return int|bool $group_id The ID of the Group, or false on failure.
	 */
	public function domain_group_set( $group_id = 0 ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return false;
		}

		// Sanity check.
		if ( $group_id === 0 OR ! is_numeric( $group_id ) ) {
			return false;
		}

		// Get domain data.
		$domain = $this->domain_get();

		// Bail if we don't have a domain.
		if ( $domain['id'] === 0 ) {
			return false;
		}

		// Get existing Domain Group data.
		$domain_group = $this->domain_group_get();

		// Check "domain_group_id" setting.
		$setting = civicrm_api( 'setting', 'getsingle', [
			'version' => 3,
			'sequential' => 1,
			'domain_id' => $domain['id'],
			'return' => 'domain_group_id',
		] );

		// Skip the Setting if there's no change.
		if ( isset( $setting['domain_group_id'] ) AND $setting['domain_group_id'] !== $group_id ) {

			// Set "domain_group_id" setting.
			$result = civicrm_api( 'setting', 'create', [
				'version' => 3,
				'domain_id' => $domain['id'],
				'domain_group_id' => absint( $group_id ),
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

		// Check if new Domain Group has a "GroupOrganization".
		$result = civicrm_api( 'GroupOrganization', 'getsingle', [
			'version' => 3,
			'group_id' => absint( $group_id ),
			'organization_id' => $domain['contact_id'],
		] );

		// If it doesn't have one.
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {

			// Create new "GroupOrganization" entry.
			$result = civicrm_api( 'GroupOrganization', 'create', [
				'version' => 3,
				'group_id' => absint( $group_id ),
				'organization_id' => $domain['contact_id'],
			] );

		}

		// Bail if there wasn't a previous Domain Group.
		if ( $domain_group['id'] === 0 ) {
			return $group_id;
		}

		// Get all "GroupOrganization" data for previous Domain Group.
		$result = civicrm_api( 'GroupOrganization', 'get', [
			'version' => 3,
			'sequential' => 1,
			'group_id' => absint( $domain_group['id'] ),
		] );

		// If the previous Domain Group had more than one "GroupOrganization".
		if ( isset( $result['count'] ) AND absint( $result['count'] ) > 1 ) {

			// Init linkage ID.
			$linkage_id = 0;

			// Find the one that's tied to this Domain Org.
			foreach( $result['values'] AS $linkage ) {
				if ( $linkage['organization_id'] == $domain['contact_id'] ) {
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
	 * Get the Domain Orgs registered in CiviCRM.
	 *
	 * @since 0.6.2
	 */
	public function domain_orgs_ajax_get() {

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return;
		}

		// Init return.
		$json = [];

		// Sanitise search input.
		$search = isset( $_POST['s'] ) ? sanitize_text_field( $_POST['s'] ) : '';

		// Get domain orgs.
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
	 * Get domain org data for a given ID.
	 *
	 * @since 0.5.4
	 *
	 * @param int $domain_org_id The ID of the domain org.
	 * @return str $domain_org The domain org data, with error message on failure.
	 */
	public function domain_org_get( $domain_org_id = 0 ) {

		// Init return.
		$domain_org = [ 'id' => 0 ];

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			$domain_org['name'] = __( 'Failed to initialise CiviCRM.', 'civicrm-admin-utilities' );
			return $domain_org;
		}

		// If no parameter specified.
		if ( $domain_org_id === 0 ) {

			// Get CiviCRM domain org ID from constant, if set.
			$domain_org_id = defined( 'CIVICRM_DOMAIN_ORG_ID' ) ? CIVICRM_DOMAIN_ORG_ID : 0;

			// If this fails, get it from the domain.
			if ( $domain_org_id === 0 ) {

				// Get domain data.
				$domain = $this->domain_get();

				// If this fails, try and get it from the domain.
				if ( $domain['id'] !== 0 ) {
					$domain_org_id = isset( $domain['contact_id'] ) ? $domain['contact_id'] : 0;
				}

				// Bail if we still don't have one.
				if ( $domain_org_id === 0 ) {
					$domain_org['name'] = __( 'Could not find a Domain Org ID.', 'civicrm-admin-utilities' );
					return $domain_org;
				}

			}

		}

		// Get domain org info.
		$domain_org_info = civicrm_api( 'contact', 'getsingle', [
			'version' => 3,
			'id' => $domain_org_id,
		] );

		// Bail if there's an error.
		if ( ! empty( $domain_org_info['is_error'] ) AND $domain_org_info['is_error'] == 1 ) {
			$domain_org['name'] = $domain_org_info['error_message'];
			return $domain_org;
		}

		// Populate return array with the items we want.
		$domain_org['id'] = $domain_org_id;
		$domain_org['name'] = $domain_org_info['display_name'];

		// --<
		return $domain_org;

	}



	/**
	 * Create a Domain Organisation.
	 *
	 * @since 0.6.2
	 */
	public function domain_org_create() {

		// Nothing to see yet.

	}



	/**
	 * Set an Organisation as a Domain Organisation.
	 *
	 * @since 0.6.2
	 *
	 * @param int $org_id The ID of the Organisation.
	 * @return int|bool $org_id The ID of the Organisation, or false on failure.
	 */
	public function domain_org_set( $org_id = 0 ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return false;
		}

		// Sanity check.
		if ( $org_id === 0 OR ! is_numeric( $org_id ) ) {
			return false;
		}

		// Get domain data.
		$domain = $this->domain_get();

		// Bail if we don't have a domain.
		if ( $domain['id'] === 0 ) {
			return false;
		}

		// Bail if there's no change.
		if ( $domain['contact_id'] == $org_id ) {
			return $org_id;
		}

		// Update Domain.
		$result = civicrm_api( 'domain', 'create', [
			'version' => 3,
			'id' => $domain['id'],
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



