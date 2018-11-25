<?php

/**
 * CiviCRM Admin Utilities Multidomain Class.
 *
 * A class that encapsulates Multidomain admin functionality.
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
	 * @var array $multidomain_page The reference to the multidomain settings page.
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
		add_action( 'civicrm_admin_utilities_loaded', array( $this, 'initialise' ) );

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

		// Add Domain subpage to Single Site Settings menu.
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		// Filter the list of single site subpages and add multidomain page.
		add_filter( 'civicrm_admin_utilities_subpages', array( $this, 'admin_subpages_filter' ) );

		// Filter the list of single site page URLs and add multidomain page URL.
		add_filter( 'civicrm_admin_utilities_page_urls', array( $this, 'page_urls_filter' ) );

		// Filter the "show tabs" flag for setting templates.
		add_filter( 'civicrm_admin_utilities_show_tabs', array( $this, 'page_show_tabs' ) );

		// Add tab to setting templates.
		add_filter( 'civicrm_admin_utilities_settings_nav_tabs', array( $this, 'page_add_tab' ), 10, 2 );

	}



	//##########################################################################




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
		 * @param str The default capability for access to menu items.
		 * @return str The modified capability for access to menu items.
		 */
		$capability = apply_filters( 'civicrm_admin_utilities_admin_menu_cap', 'manage_options' );

		// Check user permissions.
		if ( ! current_user_can( $capability ) ) return;

		// Add Domain page.
		$this->multidomain_page = add_submenu_page(
			'civicrm_admin_utilities_parent', // Parent slug.
			__( 'CiviCRM Admin Utilities: Domain', 'civicrm-admin-utilities' ), // Page title.
			__( 'Domain', 'civicrm-admin-utilities' ), // Menu title.
			$capability, // Required caps.
			'civicrm_admin_utilities_multidomain', // Slug name.
			array( $this, 'page_multidomain' ) // Callback.
		);

		// Ensure correct menu item is highlighted.
		add_action( 'admin_head-' . $this->multidomain_page, array( $this->plugin->single, 'admin_menu_highlight' ), 50 );

		// Add help text.
		add_action( 'admin_head-' . $this->multidomain_page, array( $this, 'admin_head' ), 50 );

		// Add scripts and styles.
		//add_action( 'admin_print_scripts-' . $this->multidomain_page, array( $this, 'page_multidomain_js' ) );
		//add_action( 'admin_print_styles-' . $this->multidomain_page, array( $this, 'page_multidomain_css' ) );

		// Try and update options.
		$saved = $this->settings_update_router();

	}



	/**
	 * Append the multidomain settings page to single site subpages.
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

		// Add multidomain settings page.
		$subpages[] = 'civicrm_admin_utilities_multidomain';

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
		$pages = array(
			$this->multidomain_page,
		);

		// Kick out if not our screen.
		if ( ! in_array( $screen->id, $pages ) ) return $screen;

		// Add a tab - we can add more later.
		$screen->add_help_tab( array(
			'id'      => 'civicrm_admin_utilities_multidomain',
			'title'   => __( 'CiviCRM Admin Utilities Domain', 'civicrm-admin-utilities' ),
			'content' => $this->admin_help_get(),
		));

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



	//##########################################################################



	/**
	 * Show our multidomain settings page.
	 *
	 * @since 0.5.4
	 */
	public function page_multidomain() {

		/**
		 * Set capability but allow overrides.
		 *
		 * @since 0.5.4
		 *
		 * @param str The default capability for access to menu items.
		 * @return str The modified capability for access to menu items.
		 */
		$capability = apply_filters( 'civicrm_admin_utilities_admin_menu_cap', 'manage_options' );

		// Check user permissions.
		if ( ! current_user_can( $capability ) ) return;

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) return;

		// Get admin page URLs.
		$urls = $this->plugin->single->page_get_urls();

		// Get CiviCRM domain ID from config.
		$domain_id = CRM_Core_Config::domainID();

		// Get domain name.
		$domain_name = $this->domain_name_get( $domain_id );

		// Get CiviCRM domain group ID.
		$domain_group_id = defined( 'CIVICRM_DOMAIN_GROUP_ID' ) ? CIVICRM_DOMAIN_GROUP_ID : 0;

		// Get domain group name.
		$domain_group_name = $this->domain_group_name_get( $domain_group_id );

		// Get CiviCRM domain org ID.
		$domain_org_id = defined( 'CIVICRM_DOMAIN_ORG_ID' ) ? CIVICRM_DOMAIN_ORG_ID : 0;

		// Get domain org name.
		$domain_org_name = $this->domain_org_name_get( $domain_org_id );

		// Include template file.
		include( CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/site-multidomain.php' );

	}



	/**
	 * Append the multidomain settings page URL to single site subpage URLs.
	 *
	 * @since 0.5.4
	 *
	 * @param array $urls The existing list of URLs.
	 * @return array $urls The modified list of URLs.
	 */
	public function page_urls_filter( $urls ) {

		// Add multidomain settings page.
		$urls['multidomain'] = menu_page_url( 'civicrm_admin_utilities_multidomain', false );

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



	//##########################################################################



	/**
	 * Get the name of the domain for a given ID.
	 *
	 * @since 0.5.4
	 *
	 * @param int $domain_id The ID of the domain.
	 * @return str $name The name of the domain on success, error message otherwise.
	 */
	public function domain_name_get( $domain_id ) {

		// Get domain info.
		$domain_info = civicrm_api( 'domain', 'getsingle', array(
			'version' => 3,
			'id' => $domain_id,
		));

		// Assign error message or name depending on query success.
		if ( ! empty( $domain_info['is_error'] ) AND $domain_info['is_error'] == 1 ) {
			$name = $domain_info['error_message'];
		} else {
			$name = $domain_info['name'];
		}

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'domain_id' => $domain_id,
			'domain_info' => $domain_info,
			//'backtrace' => $trace,
		), true ) );
		*/

		// --<
		return $name;

	}



	/**
	 * Get the name of the domain group for a given ID.
	 *
	 * @since 0.5.4
	 *
	 * @param int $domain_group_id The ID of the domain group.
	 * @return str $name The name of the domain group on success, error message otherwise.
	 */
	public function domain_group_name_get( $domain_group_id ) {

		// Get domain group info.
		$domain_group_info = civicrm_api( 'group', 'getsingle', array(
			'version' => 3,
			'id' => $domain_group_id,
		));

		// Assign error message or name depending on query success.
		if ( ! empty( $domain_group_info['is_error'] ) AND $domain_group_info['is_error'] == 1 ) {
			$name = $domain_group_info['error_message'];
		} else {
			$name = $domain_group_info['title'];
		}

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'domain_group_id' => $domain_group_id,
			'domain_group_info' => $domain_group_info,
			//'backtrace' => $trace,
		), true ) );
		*/

		// --<
		return $name;

	}



	/**
	 * Get the name of the domain org for a given ID.
	 *
	 * @since 0.5.4
	 *
	 * @param int $domain_org_id The ID of the domain org.
	 * @return str $name The name of the domain org on success, error message otherwise.
	 */
	public function domain_org_name_get( $domain_org_id ) {

		// Get domain org info.
		$domain_org_info = civicrm_api( 'contact', 'getsingle', array(
			'version' => 3,
			'id' => $domain_org_id,
		));

		// Assign error message or name depending on query success.
		if ( ! empty( $domain_org_info['is_error'] ) AND $domain_org_info['is_error'] == 1 ) {
			$name = $domain_org_info['error_message'];
		} else {
			$name = $domain_org_info['display_name'];
		}

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'domain_org_id' => $domain_org_id,
			'domain_org_info' => $domain_org_info,
			//'backtrace' => $trace,
		), true ) );
		*/

		// --<
		return $name;

	}



	//##########################################################################



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

	 	// was the "Domain" form submitted?
		if ( isset( $_POST['civicrm_admin_utilities_multidomain_submit'] ) ) {
			return $this->settings_multidomain_update();
		}

		// --<
		return $result;

	}



	/**
	 * Update options supplied by our Multidomain admin page.
	 *
	 * @since 0.5.4
	 *
	 * @return bool True if successful, false otherwise (always true at present).
	 */
	public function settings_multidomain_update() {

		// Check that we trust the source of the data.
		check_admin_referer( 'civicrm_admin_utilities_multidomain_action', 'civicrm_admin_utilities_multidomain_nonce' );

		// TODO: Functional procedure here.

		// --<
		return true;

	}



} // Class ends.



