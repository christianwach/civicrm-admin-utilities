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
	 * Plugin object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var object
	 */
	public $plugin;

	/**
	 * Multidomain Settings page reference.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var string
	 */
	public $multidomain_page;

	/**
	 * Network Multidomain Settings page reference.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var string
	 */
	public $network_multidomain_page;

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
		add_action( 'cau/multidomain/network/settings/add_meta_boxes', [ $this, 'network_meta_boxes_add' ], 11, 1 );

		// Add Domain subpage to Single Site Settings menu.
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );

		// Add meta boxes to Single Site Domain subpage.
		add_action( 'cau/multidomain/settings/add_meta_boxes', [ $this, 'meta_boxes_add' ], 11, 1 );

		/*
		// Add Domains AJAX handler.
		add_action( 'wp_ajax_cau_domains_get', [ $this, 'domains_ajax_get' ] );
		*/

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

		// Register our form submit hander.
		add_action( 'load-' . $this->network_multidomain_page, [ $this, 'settings_update_router' ] );

		// Ensure correct menu item is highlighted.
		add_action( 'admin_head-' . $this->network_multidomain_page, [ $this->plugin->multisite, 'network_menu_highlight' ], 50 );

		// Add help text.
		add_action( 'admin_head-' . $this->network_multidomain_page, [ $this, 'network_admin_head' ], 50 );

		// Add scripts and styles.
		add_action( 'admin_print_styles-' . $this->network_multidomain_page, [ $this, 'page_network_multidomain_css' ] );

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
		if ( ! in_array( $screen->id, $pages, true ) ) {
			return $screen;
		}

		// Build tab args.
		$args = [
			'id'      => 'cau_network_multidomain',
			'title'   => __( 'CiviCRM Admin Utilities Domain', 'civicrm-admin-utilities' ),
			'content' => $this->network_admin_help_get(),
		];

		// Add a tab - we can add more later.
		$screen->add_help_tab( $args );

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
		if ( is_network_admin() && ! is_super_admin() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'civicrm-admin-utilities' ) );
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
		do_action( 'cau/multidomain/network/settings/add_meta_boxes', $screen->id, null );

		// Grab columns.
		$columns = ( 1 === $screen->get_columns() ? '1' : '2' );

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
			CIVICRM_ADMIN_UTILITIES_VERSION, // Version.
			'all' // Media.
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
	 * @param str   $active_tab The key of the active tab in the subpage URLs array.
	 */
	public function page_network_add_tab( $urls, $active_tab ) {

		// Define title.
		$title = __( 'Domains', 'civicrm-admin-utilities' );

		// Default to inactive.
		$active = '';

		// Make active if it's our subpage.
		if ( 'multidomain' === $active_tab ) {
			$active = ' nav-tab-active';
		}

		// Render tab. URL is already escaped.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<a href="' . $urls['multidomain'] . '" class="nav-tab' . esc_attr( $active ) . '">' . esc_html( $title ) . '</a>' . "\n";

	}

	/**
	 * Get the URL for the form action.
	 *
	 * @since 0.5.4
	 *
	 * @return string $target_url The URL for the admin form action.
	 */
	public function page_network_submit_url_get() {

		// Use Site Multi Domain admin page URL.
		$target_url = $this->plugin->multisite->network_menu_page_url( 'cau_network_multidomain', false );

		// --<
		return $target_url;

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
		if ( ! in_array( $screen_id, $screen_ids, true ) ) {
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
		if ( false === $data['multisite'] ) {
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
	public function meta_box_network_domain_info_render( $unused = null, $metabox = [] ) {

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
	public function meta_box_network_domain_create_render( $unused = null, $metabox = [] ) {

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

		// Register our form submit hander.
		add_action( 'load-' . $this->multidomain_page, [ $this, 'settings_update_router' ] );

		// Ensure correct menu item is highlighted.
		add_action( 'admin_head-' . $this->multidomain_page, [ $this->plugin->single, 'admin_menu_highlight' ], 50 );

		// Add help text.
		add_action( 'admin_head-' . $this->multidomain_page, [ $this, 'admin_head' ], 50 );

		// Add scripts and styles.
		add_action( 'admin_print_scripts-' . $this->multidomain_page, [ $this, 'page_multidomain_js' ] );
		add_action( 'admin_print_styles-' . $this->multidomain_page, [ $this, 'page_multidomain_css' ] );

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
		if ( ! in_array( $screen->id, $pages, true ) ) {
			return $screen;
		}

		// Build tab args.
		$args = [
			'id'      => 'civicrm_au_multidomain',
			'title'   => __( 'CiviCRM Admin Utilities Domain', 'civicrm-admin-utilities' ),
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
		if ( ! in_array( $screen_id, $screen_ids, true ) ) {
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

		// Build params.
		$params = [
			'version'   => 3,
			'domain_id' => $data['domain']['id'],
			'name'      => 'is_enabled',
			'group'     => 'Multi Site Preferences',
		];

		// Check if "Multisite" is enabled for this Domain.
		$data['enabled'] = civicrm_api( 'Setting', 'getvalue', $params );

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
		if ( false === $data['multisite'] || false === $data['enabled'] ) {
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
	public function meta_box_info_render( $unused = null, $metabox = [] ) {

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
	public function meta_box_edit_render( $unused = null, $metabox = [] ) {

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
		do_action( 'cau/multidomain/settings/add_meta_boxes', $screen->id, null );

		// Grab columns.
		$columns = ( 1 === $screen->get_columns() ? '1' : '2' );

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
			set_url_scheme( 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css' ),
			false,
			'4.0.13',
			'all'
		);

		// Enqueue styles.
		wp_enqueue_style( 'cau_site_domain_select2_css' );

		// Add page-specific stylesheet.
		wp_enqueue_style(
			'cau_site_domain_css',
			plugins_url( 'assets/css/civicrm-admin-utilities-site-multidomain.css', CIVICRM_ADMIN_UTILITIES_FILE ),
			false,
			CIVICRM_ADMIN_UTILITIES_VERSION, // Version.
			'all' // Media.
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
			set_url_scheme( 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js' ),
			[ 'jquery' ],
			'4.0.13',
			true
		);

		// Enqueue Select2 script.
		wp_enqueue_script( 'cau_site_domain_select2_js' );

		// Enqueue our Javascript plus dependencies.
		wp_enqueue_script(
			'cau_site_domain_js',
			plugins_url( 'assets/js/civicrm-admin-utilities-site-multidomain.js', CIVICRM_ADMIN_UTILITIES_FILE ),
			[ 'jquery', 'cau_site_domain_select2_js' ],
			CIVICRM_ADMIN_UTILITIES_VERSION, // Version.
			true
		);

		// Localisation array.
		$vars = [
			'localisation' => [],
			'settings'     => [
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
	 * @param str   $active_tab The key of the active tab in the subpage URLs array.
	 */
	public function page_add_tab( $urls, $active_tab ) {

		// Define title.
		$title = __( 'Domain', 'civicrm-admin-utilities' );

		// Default to inactive.
		$active = '';

		// Make active if it's our subpage.
		if ( 'multidomain' === $active_tab ) {
			$active = ' nav-tab-active';
		}

		// Render tab. URL is already escaped.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<a href="' . $urls['multidomain'] . '" class="nav-tab' . esc_attr( $active ) . '">' . esc_html( $title ) . '</a>' . "\n";

	}

	/**
	 * Get the URL for the form action.
	 *
	 * @since 0.5.4
	 *
	 * @return string $target_url The URL for the admin form action.
	 */
	public function page_multidomain_submit_url_get() {

		// Use Site Multi Domain admin page URL.
		$target_url = menu_page_url( 'civicrm_au_multidomain', false );

		// --<
		return $target_url;

	}

	// -------------------------------------------------------------------------

	/**
	 * Route settings updates to relevant methods.
	 *
	 * @since 0.5.4
	 */
	public function settings_update_router() {

		// Was the "Network Domain" form submitted?
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['cau_network_multidomain_submit'] ) ) {
			$this->settings_network_multidomain_update();
			$url = $this->plugin->multisite->network_menu_page_url( 'cau_network_multidomain', false );
			$this->settings_update_redirect( $url );
		}

		// Was the "Domain" form submitted?
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['cau_multidomain_submit'] ) ) {
			$this->settings_multidomain_update();
			$url = menu_page_url( 'civicrm_au_multidomain', false );
			$this->settings_update_redirect( $url );
		}

	}

	/**
	 * Form redirection handler.
	 *
	 * @since 1.0.1
	 *
	 * @param string $url The menu page URL.
	 */
	public function settings_update_redirect( $url ) {

		// Our array of arguments.
		$args = [ 'updated' => 'true' ];

		// Redirect to our Settings Page.
		wp_safe_redirect( add_query_arg( $args, $url ) );
		exit;

	}

	/**
	 * Update options supplied by our Network Multidomain Settings page.
	 *
	 * @since 0.6.2
	 */
	public function settings_network_multidomain_update() {

		// Check that we trust the source of the data.
		check_admin_referer( 'cau_network_multidomain_action', 'cau_network_multidomain_nonce' );

		// Sanitise input.
		$domain_name = isset( $_POST['cau_domain_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cau_domain_name'] ) ) : '';

		// Bail if we get nothing through.
		if ( empty( $domain_name ) ) {
			return;
		}

		// Okay, create domain.
		$result = $this->domain_create( $domain_name );

		// Maybe log errors.
		if ( ! is_int( $result ) ) {
			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'result'    => $result,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
		}

		/**
		 * Broadcast that the Network Multidomain update process is finished.
		 *
		 * @since 0.8.1
		 */
		do_action( 'civicrm_admin_utilities_network_multidomain_updated' );

	}

	/**
	 * Update options supplied by our Multidomain Settings page.
	 *
	 * @since 0.5.4
	 */
	public function settings_multidomain_update() {

		// Check that we trust the source of the data.
		check_admin_referer( 'cau_multidomain_action', 'cau_multidomain_nonce' );

		// Sanitise inputs.
		$domain_org_id   = isset( $_POST['cau_domain_org_select'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['cau_domain_org_select'] ) ) : '';
		$domain_group_id = isset( $_POST['cau_domain_group_select'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['cau_domain_group_select'] ) ) : '';

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

		// Build params.
		$params = [
			'version' => 3,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Get domains.
		$result = civicrm_api( 'Domain', 'get', $params );

		// Sanity check.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $domains;
		}

		// Loop through our domains.
		foreach ( $result['values'] as $domain ) {

			// Add domain data to return array.
			$domains[] = [
				'id'          => $domain['id'],
				'name'        => stripslashes( $domain['name'] ),
				'description' => isset( $domain['description'] ) ? $domain['description'] : '',
			];

		}

		// --<
		return $domains;

	}

	/**
	 * Get the Domains registered in CiviCRM.
	 *
	 * Not used.
	 *
	 * @since 0.6.2
	 */
	public function domains_ajax_get() {

		// Init return.
		$json = [];

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			wp_send_json( $json );
		}

		// Since this is an AJAX request, check security.
		$result = check_ajax_referer( 'cau_domains', false, false );
		if ( false === $result ) {
			wp_send_json( $json );
		}

		// Sanitise search input.
		$search = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';

		// Build params.
		$params = [
			'version' => 3,
			'name'    => [ 'LIKE' => '%' . $search . '%' ],
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Get domains.
		$domains = civicrm_api( 'Domain', 'get', $params );

		// Sanity check.
		if ( ! empty( $domains['is_error'] ) && 1 === (int) $domains['is_error'] ) {
			wp_send_json( $json );
		}

		// Loop through our domains.
		foreach ( $domains['values'] as $domain ) {

			// Add domain data to output array.
			$json[] = [
				'id'          => $domain['id'],
				'name'        => stripslashes( $domain['name'] ),
				'description' => isset( $domain['description'] ) ? $domain['description'] : '',
			];

		}

		// Send data.
		wp_send_json( $json );

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

		// If no parameter set.
		if ( 0 === $domain_id ) {

			// Get CiviCRM domain group ID from constant, if set.
			$domain_id = defined( 'CIVICRM_DOMAIN_ID' ) ? CIVICRM_DOMAIN_ID : 0;

			// If this fails, get it from config.
			if ( 0 === $domain_id ) {
				$domain_id = CRM_Core_Config::domainID();
			}

			// Bail if we still don't have one.
			if ( 0 === $domain_id ) {
				$domain['name'] = __( 'Could not find a Domain ID.', 'civicrm-admin-utilities' );
				return $domain;
			}

		}

		// Build params.
		$params = [
			'version' => 3,
			'id'      => $domain_id,
		];

		// Get domain info.
		$domain_info = civicrm_api( 'Domain', 'getsingle', $params );

		// Bail if there's an error.
		if ( ! empty( $domain_info['is_error'] ) && 1 === (int) $domain_info['is_error'] ) {
			$domain['name'] = $domain_info['error_message'];
			return $domain;
		}

		// Populate return array with the items we want.
		$domain['id']         = $domain_id;
		$domain['name']       = $domain_info['name'];
		$domain['contact_id'] = $domain_info['contact_id'];
		$domain['version']    = $domain_info['domain_version'];

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

		// Build params.
		$params = [
			'version'          => 3,
			'sequential'       => 1,
			'name'             => $name,
			'is_transactional' => 'FALSE',
		];

		// Create domain.
		$result = civicrm_api( 'MultisiteDomain', 'create', $params );

		// Sanity check.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $result['error_message'];
		}

		// Init ID with error message.
		$id = __( 'Domain ID not found.', 'civicrm-admin-utilities' );

		// Find ID of new Domain and override message with ID.
		if ( ! empty( $result['values'] ) ) {
			$domain = array_pop( $result['values'] );
			$id     = absint( $domain['id'] );
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

		// Init return.
		$json = [];

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			wp_send_json( $json );
		}

		// This is an AJAX request, so check security.
		$result = check_ajax_referer( 'cau_domain_group', false, false );
		if ( false === $result ) {
			wp_send_json( $json );
		}

		// Sanitise search input.
		$search = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';

		// Build params.
		$params = [
			'version'    => 3,
			'visibility' => 'User and User Admin Only',
			'title'      => [ 'LIKE' => '%' . $search . '%' ],
			'options'    => [
				'limit' => 0, // No limit.
			],
		];

		// Get domain groups.
		$groups = civicrm_api( 'Group', 'get', $params );

		// Sanity check.
		if ( ! empty( $groups['is_error'] ) && 1 === (int) $groups['is_error'] ) {
			wp_send_json( $json );
		}

		// Loop through our groups.
		foreach ( $groups['values'] as $group ) {

			// Add group data to output array.
			$json[] = [
				'id'          => $group['id'],
				'name'        => stripslashes( $group['title'] ),
				'description' => '',
			];

		}

		// Send data.
		wp_send_json( $json );

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

		// If no parameter set.
		if ( 0 === $domain_group_id ) {

			// Try and find the current Domain Group ID.
			$domain_group_id = $this->domain_group_id_get();

			// Bail if we don't find one.
			if ( 0 === $domain_group_id ) {
				$domain_group['name'] = __( 'Could not find a Domain Group ID.', 'civicrm-admin-utilities' );
				return $domain_group;
			}

		}

		// Build params.
		$params = [
			'version' => 3,
			'id'      => $domain_group_id,
		];

		// Get domain group info.
		$domain_group_info = civicrm_api( 'Group', 'getsingle', $params );

		// Bail if there's an error.
		if ( ! empty( $domain_group_info['is_error'] ) && 1 === (int) $domain_group_info['is_error'] ) {
			$domain_group['name'] = $domain_group_info['error_message'];
			return $domain_group;
		}

		// Populate return array with the items we want.
		$domain_group['id']   = $domain_group_id;
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
		if ( 0 === $domain['id'] ) {
			return $domain_group_id;
		}

		// Build params.
		$params = [
			'version'    => 3,
			'sequential' => 1,
			'domain_id'  => $domain['id'],
			'return'     => 'domain_group_id',
		];

		// Check "domain_group_id" setting.
		$result = civicrm_api( 'Setting', 'getsingle', $params );

		// If we were successful, cast as integer and return the ID.
		if ( ! empty( $result['domain_group_id'] ) && 0 !== (int) $result['domain_group_id'] ) {
			$domain_group_id = (int) $result['domain_group_id'];
			return $domain_group_id;
		}

		// Build params.
		$params = [
			'version'    => 3,
			'sequential' => 1,
			'title'      => $domain['name'],
		];

		// Check for Group with the name of the Domain.
		$result = civicrm_api( 'Group', 'getsingle', $params );

		// If we were successful, cast as integer and return the ID.
		if ( ! empty( $result['id'] ) ) {
			$domain_group_id = absint( $result['id'] );
			return $domain_group_id;
		}

		/*
		// Build params.
		$params = [
			'version'         => 3,
			'sequential'      => 1,
			'organization_id' => $domain['contact_id'],
		];

		// Get result from "GroupOrganization".
		$result = civicrm_api( 'GroupOrganization', 'getsingle', $params );

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
		if ( 0 === $group_id || ! is_numeric( $group_id ) ) {
			return false;
		}

		// Get domain data.
		$domain = $this->domain_get();

		// Bail if we don't have a domain.
		if ( 0 === $domain['id'] ) {
			return false;
		}

		// Get existing Domain Group data.
		$domain_group = $this->domain_group_get();

		// Build params.
		$params = [
			'version'    => 3,
			'sequential' => 1,
			'domain_id'  => $domain['id'],
			'return'     => 'domain_group_id',
		];

		// Check "domain_group_id" setting.
		$setting = civicrm_api( 'Setting', 'getsingle', $params );

		// Skip the Setting if there's no change.
		if ( isset( $setting['domain_group_id'] ) && $setting['domain_group_id'] !== $group_id ) {

			// Build params.
			$params = [
				'version'         => 3,
				'domain_id'       => $domain['id'],
				'domain_group_id' => absint( $group_id ),
			];

			// Set "domain_group_id" setting.
			$result = civicrm_api( 'Setting', 'create', $params );

			// Log if there's an error.
			if ( isset( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
				$e     = new Exception();
				$trace = $e->getTraceAsString();
				$log   = [
					'method'    => __METHOD__,
					'result'    => $result,
					'backtrace' => $trace,
				];
				$this->plugin->log_error( $log );
			}

		}

		// Build params.
		$params = [
			'version'         => 3,
			'group_id'        => absint( $group_id ),
			'organization_id' => $domain['contact_id'],
		];

		// Check if new Domain Group has a "GroupOrganization".
		$result = civicrm_api( 'GroupOrganization', 'getsingle', $params );

		// If it doesn't have one.
		if ( isset( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {

			// Build params.
			$params = [
				'version'         => 3,
				'group_id'        => absint( $group_id ),
				'organization_id' => $domain['contact_id'],
			];

			// Create new "GroupOrganization" entry.
			$result = civicrm_api( 'GroupOrganization', 'create', $params );

		}

		// Bail if there wasn't a previous Domain Group.
		if ( 0 === $domain_group['id'] ) {
			return $group_id;
		}

		// Build params.
		$params = [
			'version'    => 3,
			'sequential' => 1,
			'group_id'   => absint( $domain_group['id'] ),
		];

		// Get all "GroupOrganization" data for previous Domain Group.
		$result = civicrm_api( 'GroupOrganization', 'get', $params );

		// If the previous Domain Group had more than one "GroupOrganization".
		if ( isset( $result['count'] ) && absint( $result['count'] ) > 1 ) {

			// Init linkage ID.
			$linkage_id = 0;

			// Find the one that's tied to this Domain Org.
			foreach ( $result['values'] as $linkage ) {
				if ( (int) $linkage['organization_id'] === (int) $domain['contact_id'] ) {
					$linkage_id = $linkage['id'];
				}
			}

			// Remove it if we find it.
			if ( 0 !== $linkage_id ) {
				$params = [
					'version' => 3,
					'id'      => $linkage_id,
				];
				$result = civicrm_api( 'GroupOrganization', 'delete', $params );
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

		// Init return.
		$json = [];

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			wp_send_json( $json );
		}

		// This is an AJAX request, so check security.
		$result = check_ajax_referer( 'cau_domain_org', false, false );
		if ( false === $result ) {
			wp_send_json( $json );
		}

		// Sanitise search input.
		$search = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';

		// Build params.
		$params = [
			'version'           => 3,
			'contact_type'      => 'Organization',
			'organization_name' => [ 'LIKE' => '%' . $search . '%' ],
			'options'           => [
				'limit' => 0, // No limit.
			],
		];

		// Get domain orgs.
		$orgs = civicrm_api( 'Contact', 'get', $params );

		// Sanity check.
		if ( ! empty( $orgs['is_error'] ) && 1 === (int) $orgs['is_error'] ) {
			wp_send_json( $json );
		}

		// Loop through our orgs.
		foreach ( $orgs['values'] as $org ) {

			// Add org data to output array.
			$json[] = [
				'id'          => $org['contact_id'],
				'name'        => stripslashes( $org['display_name'] ),
				'description' => '',
			];

		}

		// Send data.
		wp_send_json( $json );

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
		if ( 0 === $domain_org_id ) {

			// Get CiviCRM domain org ID from constant, if set.
			$domain_org_id = defined( 'CIVICRM_DOMAIN_ORG_ID' ) ? CIVICRM_DOMAIN_ORG_ID : 0;

			// If this fails, get it from the domain.
			if ( 0 === $domain_org_id ) {

				// Get domain data.
				$domain = $this->domain_get();

				// If this fails, try and get it from the domain.
				if ( 0 !== $domain['id'] ) {
					$domain_org_id = isset( $domain['contact_id'] ) ? $domain['contact_id'] : 0;
				}

				// Bail if we still don't have one.
				if ( 0 === $domain_org_id ) {
					$domain_org['name'] = __( 'Could not find a Domain Org ID.', 'civicrm-admin-utilities' );
					return $domain_org;
				}

			}

		}

		// Build params.
		$params = [
			'version' => 3,
			'id'      => $domain_org_id,
		];

		// Get domain org info.
		$domain_org_info = civicrm_api( 'Contact', 'getsingle', $params );

		// Bail if there's an error.
		if ( ! empty( $domain_org_info['is_error'] ) && 1 === (int) $domain_org_info['is_error'] ) {
			$domain_org['name'] = $domain_org_info['error_message'];
			return $domain_org;
		}

		// Populate return array with the items we want.
		$domain_org['id']   = $domain_org_id;
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
		if ( 0 === $org_id || ! is_numeric( $org_id ) ) {
			return false;
		}

		// Get domain data.
		$domain = $this->domain_get();

		// Bail if we don't have a domain.
		if ( 0 === $domain['id'] ) {
			return false;
		}

		// Bail if there's no change.
		if ( (int) $domain['contact_id'] === (int) $org_id ) {
			return $org_id;
		}

		// Build params.
		$params = [
			'version'    => 3,
			'id'         => $domain['id'],
			'contact_id' => absint( $org_id ),
		];

		// Update Domain.
		$result = civicrm_api( 'Domain', 'create', $params );

		// TODO: Do we need to reassign all groups to this Org via "GroupOrganization" API?

		// --<
		return $org_id;

	}

}
