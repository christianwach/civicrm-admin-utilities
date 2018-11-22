<?php

/**
 * CiviCRM Admin Utilities Admin Class.
 *
 * A class that encapsulates admin functionality.
 *
 * @since 0.1
 */
class CiviCRM_Admin_Utilities_Admin {

	/**
	 * Plugin version.
	 *
	 * @since 0.3.4
	 * @access public
	 * @var str $plugin_version The plugin version. (numeric string)
	 */
	public $plugin_version;

	/**
	 * Settings page reference.
	 *
	 * @since 0.1
	 * @access public
	 * @var array $settings_page The reference to the settings page.
	 */
	public $settings_page;

	/**
	 * Multisite Page.
	 *
	 * @since 0.1
	 * @access public
	 * @var str $multisite_page The multisite page.
	 */
	public $multisite_page;

	/**
	 * Settings data.
	 *
	 * @since 0.1
	 * @access public
	 * @var array $settings The plugin settings data.
	 */
	public $settings = array();



	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Initialise
		add_action( 'civicrm_admin_utilities_loaded', array( $this, 'initialise' ) );

	}



	/**
	 * Initialise this object.
	 *
	 * @since 0.1
	 */
	public function initialise() {

		// Assign plugin version
		$this->plugin_version = $this->option_get( 'civicrm_admin_utilities_version', false );

		// Do upgrade tasks
		$this->upgrade_tasks();

		// Store version for later reference if there has been a change
		if ( $this->plugin_version != CIVICRM_ADMIN_UTILITIES_VERSION ) {
			$this->store_version();
		}

		// Store default settings if none exist
		if ( ! $this->option_exists( 'civicrm_admin_utilities_settings' ) ) {
			$this->option_set( 'civicrm_admin_utilities_settings', $this->settings_get_defaults() );
		}

		// Load settings array
		$this->settings = $this->option_get( 'civicrm_admin_utilities_settings', $this->settings );

		// Settings upgrade tasks
		$this->upgrade_settings();

		// Register hooks
		$this->register_hooks();

	}



	/**
	 * Utility to do stuff when an upgrade is required.
	 *
	 * @since 0.3.4
	 */
	public function upgrade_tasks() {

		// If this is a new install (or an upgrade from a version prior to 0.3.4)
		if ( $this->plugin_version === false ) {

			// Delete the legacy "installed" option
			$this->delete_installed_option();

			// Maybe move settings
			$this->maybe_move_settings();

		}

		/*
		// For future upgrades, use something like the following
		if ( version_compare( CIVICRM_ADMIN_UTILITIES_VERSION, '0.3.4', '>=' ) ) {
			// Do something
		}
		*/

	}



	/**
	 * Delete the legacy "installed" option.
	 *
	 * @since 0.3.4
	 */
	public function delete_installed_option() {

		// In multisite, this will delete the "global" site option, whilst in
		// Single site, it will delete the "local" blog option
		if ( 'fefdfdjgrkj' != get_site_option( 'civicrm_admin_utilities_installed', 'fefdfdjgrkj' ) ) {
			delete_site_option( 'civicrm_admin_utilities_installed' );
		}

		// Bail if single site
		if ( ! is_multisite() ) return;

		// We also need to look at the "local" blog options in multisite
		if ( 'fefdfdjgrkj' != get_option( 'civicrm_admin_utilities_installed', 'fefdfdjgrkj' ) ) {
			delete_option( 'civicrm_admin_utilities_installed' );
		}

	}



	/**
	 * Move the settings to the correct location.
	 *
	 * This only applies to multisite instances and only when the plugin is not
	 * network activated. There is a conundrum here, however:
	 *
	 * If this plugin is active on more than one site, then it will only be the
	 * first site where the plugin loads that gets the migrated settings. Other
	 * sites will need to reconfigure their settings for this plugin since they
	 * will have been reset to the defaults.
	 *
	 * @since 0.3.4
	 */
	public function maybe_move_settings() {

		// Bail if single site
		if ( ! is_multisite() ) return;

		// Bail if network activated
		if ( $this->is_network_activated() ) return;

		// Get current settings
		$settings = get_site_option( 'civicrm_admin_utilities_settings', 'fefdfdjgrkj' );

		// If we have some
		if ( $settings != 'fefdfdjgrkj' ) {

			// Save them where they are supposed to be
			$this->option_set( 'civicrm_admin_utilities_settings', $settings );

			// Delete the "global" site option
			delete_site_option( 'civicrm_admin_utilities_settings' );

		}

	}



	/**
	 * Utility to do stuff when a settings upgrade is required.
	 *
	 * @since 0.4.1
	 */
	public function upgrade_settings() {

		// CSS settings may not exist
		if ( ! $this->setting_exists( 'css_default' ) ) {

			// Add them from defaults
			$settings = $this->settings_get_defaults();
			$this->setting_set( 'css_default', $settings['css_default'] );
			$this->setting_set( 'css_navigation', $settings['css_navigation'] );
			$this->setting_set( 'css_shoreditch', $settings['css_shoreditch'] );
			$this->settings_save();

		}

		// Shoreditch Bootstrap CSS setting may not exist
		if ( ! $this->setting_exists( 'css_bootstrap' ) ) {

			// Add it from defaults
			$settings = $this->settings_get_defaults();
			$this->setting_set( 'css_bootstrap', $settings['css_bootstrap'] );
			$this->settings_save();

		}

		// Custom CSS setting may not exist
		if ( ! $this->setting_exists( 'css_custom' ) ) {

			// Add it from defaults
			$settings = $this->settings_get_defaults();
			$this->setting_set( 'css_custom', $settings['css_custom'] );
			$this->settings_save();

		}

		// Custom CSS Public setting may not exist
		if ( ! $this->setting_exists( 'css_custom_public' ) ) {

			// Add it from defaults
			$settings = $this->settings_get_defaults();
			$this->setting_set( 'css_custom_public', $settings['css_custom_public'] );
			$this->settings_save();

		}

		// Override  CiviCRM Default CSS setting may not exist
		if ( ! $this->setting_exists( 'css_admin' ) ) {

			// Add it from defaults
			$settings = $this->settings_get_defaults();
			$this->setting_set( 'css_admin', $settings['css_admin'] );
			$this->settings_save();

		}

	}



	/**
	 * Store the plugin version.
	 *
	 * @since 0.3.4
	 */
	public function store_version() {

		// Store version
		$this->option_set( 'civicrm_admin_utilities_version', CIVICRM_ADMIN_UTILITIES_VERSION );

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.3.4
	 */
	public function register_hooks() {

		// If multisite and network activated
		if ( $this->is_network_activated() ) {

			// Add admin page to Network menu
			add_action( 'network_admin_menu', array( $this, 'admin_menu' ), 30 );

		} else {

			// Add admin page to menu
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		}

	}



	//##########################################################################



	/**
	 * Add an admin page for this plugin.
	 *
	 * @since 0.1
	 */
	public function admin_menu() {

		// We must be network admin in multisite
		if ( is_multisite() AND ! is_super_admin() ) return;

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) return;

		// Multisite and network activated?
		if ( $this->is_network_activated() ) {

			// Add the admin page to the Network Settings menu
			$this->parent_page = add_submenu_page(
				'settings.php',
				__( 'CiviCRM Admin Utilities: Settings', 'civicrm-admin-utilities' ),
				__( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ),
				'manage_options',
				'civicrm_admin_utilities_parent',
				array( $this, 'page_settings' )
			);

		} else {

			// Add the admin page to the Settings menu
			$this->parent_page = add_options_page(
				__( 'CiviCRM Admin Utilities: Settings', 'civicrm-admin-utilities' ),
				__( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ),
				'manage_options',
				'civicrm_admin_utilities_parent',
				array( $this, 'page_settings' )
			);

		}

		// Add scripts and styles
		add_action( 'admin_head-' . $this->parent_page, array( $this, 'admin_head' ), 50 );
		//add_action( 'admin_print_styles-' . $this->parent_page, array( $this, 'admin_css' ) );

		// Add settings page
		$this->settings_page = add_submenu_page(
			'civicrm_admin_utilities_parent', // parent slug
			__( 'CiviCRM Admin Utilities: Settings', 'civicrm-admin-utilities' ), // page title
			__( 'Settings', 'civicrm-admin-utilities' ), // menu title
			'manage_options', // required caps
			'civicrm_admin_utilities_settings', // slug name
			array( $this, 'page_settings' ) // callback
		);

		// Add scripts and styles
		add_action( 'admin_head-' . $this->settings_page, array( $this, 'admin_menu_highlight' ), 50 );
		add_action( 'admin_head-' . $this->settings_page, array( $this, 'admin_head' ), 50 );
		//add_action( 'admin_print_styles-' . $this->settings_page, array( $this, 'admin_css' ) );

		// Add Multisite page
		$this->multisite_page = add_submenu_page(
			'civicrm_admin_utilities_parent', // parent slug
			__( 'CiviCRM Admin Utilities: Manual Sync', 'civicrm-admin-utilities' ), // page title
			__( 'Manual Sync', 'civicrm-admin-utilities' ), // menu title
			'manage_options', // required caps
			'civicrm_admin_utilities_multisite', // slug name
			array( $this, 'page_multisite' ) // callback
		);

		// Add scripts and styles
		add_action( 'admin_head-' . $this->multisite_page, array( $this, 'admin_menu_highlight' ), 50 );
		add_action( 'admin_head-' . $this->multisite_page, array( $this, 'admin_head' ), 50 );
		/*
		add_action( 'admin_print_scripts-' . $this->multisite_page, array( $this, 'admin_js_multisite_page' ) );
		add_action( 'admin_print_styles-' . $this->multisite_page, array( $this, 'admin_css' ) );
		add_action( 'admin_print_styles-' . $this->multisite_page, array( $this, 'admin_css_multisite_page' ) );
		*/

		// Try and update options
		$saved = $this->settings_update_router();

	}



	/**
	 * Tell WordPress to highlight the plugin's menu item, regardless of which
	 * actual admin screen we are on.
	 *
	 * @since 0.5.4
	 *
	 * @global string $plugin_page The current plugin page.
	 * @global array $submenu The current submenu.
	 */
	public function admin_menu_highlight() {

		global $plugin_page, $submenu_file;

		// define subpages
		$subpages = array(
		 	'civicrm_admin_utilities_settings',
		 	'civicrm_admin_utilities_multisite',
		 );

		// This tweaks the Settings subnav menu to show only one menu item
		if ( in_array( $plugin_page, $subpages ) ) {
			$plugin_page = 'civicrm_admin_utilities_parent';
			$submenu_file = 'civicrm_admin_utilities_parent';
		}

	}



	/**
	 * Initialise plugin help.
	 *
	 * @since 0.5.4
	 */
	public function admin_head() {

		// Get screen object
		$screen = get_current_screen();

		// Pass to method in this class
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

		// Init suffix
		$page = '';

		// The page ID is different in multisite
		if ( $this->is_network_activated() ) {
			$page = '-network';
		}

		// Init page IDs
		$pages = array(
			$this->settings_page . $page,
			$this->multisite_page . $page,
		);

		// Kick out if not our screen
		if ( ! in_array( $screen->id, $pages ) ) return $screen;

		// Add a tab - we can add more later
		$screen->add_help_tab( array(
			'id'      => 'civicrm_admin_utilities',
			'title'   => __( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ),
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

		// Stub help text, to be developed further...
		$help = '<p>' . __( 'For further information about using CiviCRM Admin Utilities, please refer to the readme.txt file that comes with this plugin.', 'civicrm-admin-utilities' ) . '</p>';

		// --<
		return $help;

	}



	//##########################################################################



	/**
	 * Show our settings page.
	 *
	 * @since 0.1
	 */
	public function page_settings() {

		// We must be network admin in multisite
		if ( is_multisite() AND ! is_super_admin() ) {

			// Disallow
			wp_die( __( 'You do not have permission to access this page.', 'civicrm-admin-utilities' ) );

		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) return;

		// Get admin page URLs
		$urls = $this->page_get_urls();

		// Init menu CSS checkbox
		$prettify_menu = '';
		if ( $this->setting_get( 'prettify_menu', '0' ) == '1' ) {
			$prettify_menu = ' checked="checked"';
		}

		// Init admin CSS checkbox
		$admin_css = '';
		if ( $this->setting_get( 'css_admin', '0' ) == '1' ) {
			$admin_css = ' checked="checked"';
		}

		// Init default CSS checkbox
		$default_css = '';
		if ( $this->setting_get( 'css_default', '0' ) == '1' ) {
			$default_css = ' checked="checked"';
		}

		// Init navigation CSS checkbox
		$navigation_css = '';
		if ( $this->setting_get( 'css_navigation', '0' ) == '1' ) {
			$navigation_css = ' checked="checked"';
		}

		// Check if Shoreditch CSS is present
		global $civicrm_admin_utilities;
		if ( $civicrm_admin_utilities->shoreditch_is_active() ) {

			// Set flag
			$shoreditch = true;

			// Init Shoreditch CSS checkbox
			$shoreditch_css = '';
			if ( $this->setting_get( 'css_shoreditch', '0' ) == '1' ) {
				$shoreditch_css = ' checked="checked"';
			}

			// Init Shoreditch Bootstrap CSS checkbox
			$bootstrap_css = '';
			if ( $this->setting_get( 'css_bootstrap', '0' ) == '1' ) {
				$bootstrap_css = ' checked="checked"';
			}

		} else {

			// Set flag
			$shoreditch = false;

			// Init custom CSS checkbox
			$custom_css = '';
			if ( $this->setting_get( 'css_custom', '0' ) == '1' ) {
				$custom_css = ' checked="checked"';
			}

			// Init custom CSS on front end checkbox
			$custom_public_css = '';
			if ( $this->setting_get( 'css_custom_public', '0' ) == '1' ) {
				$custom_public_css = ' checked="checked"';
			}

		}

		// Assume access form has been fixed
		$access_form_fixed = true;

		// If CiviCRM has not been fixed
		if ( ! $this->access_form_fixed() ) {

			// Set flag
			$access_form_fixed = false;

			// Init access form checkbox
			$prettify_access = '';
			if ( $this->setting_get( 'prettify_access', '0' ) == '1' ) {
				$prettify_access = ' checked="checked"';
			}

		}

		// Init admin bar checkbox
		$admin_bar = '';
		if ( $this->setting_get( 'admin_bar', '0' ) == '1' ) {
			$admin_bar = ' checked="checked"';
		}

		// Get post type options
		$options = $this->post_type_options_get();

		// Do not show tabs by default
		$show_tabs = false;

		// Check if we need to show tabs
		if ( is_multisite() ) {
			$show_tabs = true;
		}

		// include template file
		include( CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/settings.php' );
	}



	/**
	 * Show our multisite settings page.
	 *
	 * @since 0.1
	 */
	public function page_multisite() {

		// Bail if not network activated
		//if ( ! $this->is_network_activated() ) return;

		// We must be network admin in multisite
		if ( is_multisite() AND ! is_super_admin() ) {

			// Disallow
			wp_die( __( 'You do not have permission to access this page.', 'civicrm-admin-utilities' ) );

		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) return;

		// Get admin page URLs
		$urls = $this->page_get_urls();

		// Init checkbox
		$main_site_only = '';
		if ( $this->setting_get( 'main_site_only', '0' ) == '1' ) {
			$main_site_only = ' checked="checked"';
		}

		// Include template file
		include( CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/multisite.php' );

	}



	/**
	 * Get admin page URLs.
	 *
	 * @since 0.1
	 *
	 * @return array $admin_urls The array of admin page URLs.
	 */
	public function page_get_urls() {

		// only calculate once
		if ( isset( $this->urls ) ) { return $this->urls; }

		// init return
		$this->urls = array();

		// multisite?
		if ( $this->is_network_activated() ) {

			// get admin page URLs via our adapted method
			$this->urls['settings'] = $this->network_menu_page_url( 'civicrm_admin_utilities_settings', false );
			$this->urls['multisite'] = $this->network_menu_page_url( 'civicrm_admin_utilities_multisite', false );

		} else {

			// get admin page URLs
			$this->urls['settings'] = menu_page_url( 'civicrm_admin_utilities_settings', false );
			$this->urls['multisite'] = menu_page_url( 'civicrm_admin_utilities_multisite', false );

		}

		// --<
		return $this->urls;

	}



	//##########################################################################



	/**
	 * Test if CiviCRM plugin is active.
	 *
	 * @since 0.1
	 *
	 * @return bool True if CiviCRM active, false otherwise.
	 */
	public function civicrm_is_active() {

		// Bail if no CiviCRM init function
		if ( ! function_exists( 'civi_wp' ) ) return false;

		// Try and init CiviCRM
		return civi_wp()->initialize();

	}



	/**
	 * Test if this plugin is network activated.
	 *
	 * @since 0.3.4
	 *
	 * @return bool $is_network_active True if network activated, false otherwise.
	 */
	public function is_network_activated() {

		// Only need to test once
		static $is_network_active;

		// Have we done this already?
		if ( isset( $is_network_active ) ) return $is_network_active;

		// If not multisite, it cannot be
		if ( ! is_multisite() ) {

			// Set flag
			$is_network_active = false;

			// Kick out
			return $is_network_active;

		}

		// Make sure plugin file is included when outside admin
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		// Get path from 'plugins' directory to this plugin
		$this_plugin = plugin_basename( CIVICRM_ADMIN_UTILITIES_FILE );

		// Test if network active
		$is_network_active = is_plugin_active_for_network( $this_plugin );

		// --<
		return $is_network_active;

	}



	/**
	 * Get the URL to access a particular menu page.
	 *
	 * The URL based on the slug it was registered with. If the slug hasn't been
	 * registered properly no url will be returned.
	 *
	 * @since 0.5.4
	 *
	 * @param string $menu_slug The slug name to refer to this menu by (should be unique for this menu).
	 * @param bool $echo Whether or not to echo the url - default is true.
	 * @return string $url The URL.
	 */
	public function network_menu_page_url( $menu_slug, $echo = true ) {

		global $_parent_pages;

		if ( isset( $_parent_pages[$menu_slug] ) ) {
			$parent_slug = $_parent_pages[$menu_slug];
			if ( $parent_slug && ! isset( $_parent_pages[$parent_slug] ) ) {
				$url = network_admin_url( add_query_arg( 'page', $menu_slug, $parent_slug ) );
			} else {
				$url = network_admin_url( 'admin.php?page=' . $menu_slug );
			}
		} else {
			$url = '';
		}

		$url = esc_url( $url );

		if ( $echo ) echo $url;

		// --<
		return $url;

	}



	/**
	 * Get post type options.
	 *
	 * @since 0.1
	 * @since 0.5.4 Return checkboxes as HTML.
	 *
	 * @return str $options The post type options rendered as checkboxes.
	 */
	public function post_type_options_get() {

		// Get CPTs with admin UI
		$args = array(
			'public'   => true,
			'show_ui' => true,
		);

		$output = 'objects'; // Names or objects, note names is the default
		$operator = 'and'; // 'and' or 'or'

		// Get post types
		$post_types = get_post_types( $args, $output, $operator );

		// Init outputs
		$output = array();
		$options = '';

		// Get chosen post types
		$selected_types = $this->setting_get( 'post_types', array() );

		// Sanity check
		if ( count( $post_types ) > 0 ) {

			foreach( $post_types AS $post_type ) {

				// Filter only those which have an editor
				if ( post_type_supports( $post_type->name, 'editor' ) ) {

					$checked = '';
					if ( in_array( $post_type->name, $selected_types ) ) {
						$checked = ' checked="checked"';
					}

					// Add checkbox
					$output[] = '<p><input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_post_types[]" value="' . esc_attr( $post_type->name ) . '"' . $checked . ' /> <label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_post_types">' . esc_html( $post_type->labels->singular_name ) . ' (' . esc_html( $post_type->name ) . ')</label></p>';

				}

			}

			// Implode
			$options = implode( "\n", $output );

		}

		// --<
		return $options;

	}



	/**
	 * Get the URL for the form action.
	 *
	 * @since 0.1
	 *
	 * @return string $target_url The URL for the admin form action.
	 */
	public function admin_form_url_get() {

		// Sanitise admin page url
		$target_url = $_SERVER['REQUEST_URI'];
		$url_array = explode( '&', $target_url );

		if ( ! empty( $url_array ) ) {

			// Strip flag if present
			$url_raw = str_replace( '&amp;updated=true', '', $url_array[0] );

			// Rebuild
			$target_url = htmlentities( $url_raw . '&updated=true' );

		}

		// --<
		return $target_url;

	}



	/**
	 * Check if CiviCRM's WordPress Access Control template has been fixed.
	 *
	 * @since 0.3.2
	 *
	 * @return bool $fixed True if fixed, false otherwise.
	 */
	public function access_form_fixed() {

		// Always true if already fixed in CiviCRM
		if ( $this->setting_get( 'access_fixed', '0' ) == '1' ) return true;

		// Avoid recalculation
		if ( isset( $this->fixed ) ) return $this->fixed;

		// Init property
		$this->fixed = false;

		// Get current version
		$version = CRM_Utils_System::version();

		// Find major version
		$parts = explode( '.', $version );
		$major_version = $parts[0] . '.' . $parts[1];

		// CiviCRM 4.6 is LTS and may have the fix back-ported at some point
		if ( version_compare( $major_version, '4.6', '=' ) ) {
			//if ( version_compare( $version, '4.6.38', '>=' ) ) $this->fixed = true;
		} else {
			if ( version_compare( $version, '4.7.30', '>=' ) ) $this->fixed = true;
		}

		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'version' => $version,
			'major_version' => $major_version,
			//'backtrace' => $trace,
		), true ) );

		// Save setting if fixed
		if ( $this->fixed ) {
			$this->setting_set( 'access_fixed', '1' );
			$this->settings_save();
		}

		// --<
		return $this->fixed;

	}



	/**
	 * Clear CiviCRM caches.
	 *
	 * Another way to do this might be:
	 * CRM_Core_Invoke::rebuildMenuAndCaches(TRUE);
	 *
	 * @since 0.1
	 */
	public function clear_caches() {

		// Init or die
		if ( ! $this->civicrm_is_active() ) return;

		// Access config object
		$config = CRM_Core_Config::singleton();

		// Clear db cache
		CRM_Core_Config::clearDBCache();

		// Cleanup the templates_c directory
		$config->cleanup( 1, TRUE );

		// Cleanup the session object
		$session = CRM_Core_Session::singleton();
		$session->reset( 1 );

	}



	//##########################################################################



	/**
	 * Get default settings values for this plugin.
	 *
	 * @since 0.1
	 *
	 * @return array $settings The default values for this plugin.
	 */
	public function settings_get_defaults() {

		// Init return
		$settings = array();

		// Do not restrict to main site only
		$settings['main_site_only'] = '0';

		// Prettify menu
		$settings['prettify_menu'] = '1';

		// Restrict CSS files from front-end
		$settings['css_default'] = '0'; // Load default
		$settings['css_navigation'] = '1'; // Do not load CiviCRM menu
		$settings['css_shoreditch'] = '0'; // Load Shoreditch
		$settings['css_bootstrap'] = '0'; // Load Shoreditch Bootstrap
		$settings['css_custom'] = '0'; // Load Custom Stylesheet on front-end
		$settings['css_custom_public'] = '0'; // Load Custom Stylesheet on admin

		// Override CiviCRM Default in wp-admin
		$settings['css_admin'] = '0'; // Load CiviCRM Default Stylesheet

		// Override default CiviCRM CSS in wp-admin
		$settings['css_admin'] = '0'; // Do not override by default

		// Fix WordPress Access Control table
		$settings['prettify_access'] = '1';

		// Do not assume WordPress Access Control table is fixed
		$settings['access_fixed'] = '0';

		// Init post types with defaults
		$settings['post_types'] = array( 'post', 'page' );

		// Add menu to admin bar
		$settings['admin_bar'] = '1';

		// --<
		return $settings;

	}



	/**
	 * Route settings updates to relevant methods.
	 *
	 * @since 0.5.4
	 *
	 * @return bool $result True on success, false otherwise.
	 */
	public function settings_update_router() {

		// Init return
		$result = false;

		// was the "Settings" form submitted?
		if ( isset( $_POST['civicrm_admin_utilities_settings_submit'] ) ) {
			$result = $this->settings_general_update();
		}

	 	// was the "Multisite" form submitted?
		if ( isset( $_POST['civicrm_admin_utilities_multisite_submit'] ) ) {
			$result = $this->settings_multisite_update();
		}

		// --<
		return $result;

	}



	/**
	 * Update options supplied by our Settings admin page.
	 *
	 * @since 0.5.4
	 */
	public function settings_general_update() {

		// Check that we trust the source of the data
		check_admin_referer( 'civicrm_admin_utilities_settings_action', 'civicrm_admin_utilities_settings_nonce' );

		// Init vars
		$civicrm_admin_utilities_menu = '';
		$civicrm_admin_utilities_access = '';
		$civicrm_admin_utilities_post_types = array();
		$civicrm_admin_utilities_cache = '';
		$civicrm_admin_utilities_admin_bar = '';
		$civicrm_admin_utilities_styles_default = '';
		$civicrm_admin_utilities_styles_nav = '';
		$civicrm_admin_utilities_styles_shoreditch = '';
		$civicrm_admin_utilities_styles_bootstrap = '';
		$civicrm_admin_utilities_styles_custom = '';
		$civicrm_admin_utilities_styles_custom_public = '';
		$civicrm_admin_utilities_styles_admin = '';

		// Get variables
		extract( $_POST );

		// Init force cache-clearing flag
		$force = false;

		// Get existing menu setting
		$existing_menu = $this->setting_get( 'prettify_menu', '0' );
		if ( $civicrm_admin_utilities_menu != $existing_menu ) {
			$force = true;
		}

		// Did we ask to prettify the menu?
		if ( $civicrm_admin_utilities_menu == '1' ) {
			$this->setting_set( 'prettify_menu', '1' );
		} else {
			$this->setting_set( 'prettify_menu', '0' );
		}

		// Did we ask to prevent default styleheet?
		if ( $civicrm_admin_utilities_styles_default == '1' ) {
			$this->setting_set( 'css_default', '1' );
		} else {
			$this->setting_set( 'css_default', '0' );
		}

		// Did we ask to prevent navigation styleheet?
		if ( $civicrm_admin_utilities_styles_nav == '1' ) {
			$this->setting_set( 'css_navigation', '1' );
		} else {
			$this->setting_set( 'css_navigation', '0' );
		}

		// Did we ask to prevent Shoreditch styleheet?
		if ( $civicrm_admin_utilities_styles_shoreditch == '1' ) {
			$this->setting_set( 'css_shoreditch', '1' );
		} else {
			$this->setting_set( 'css_shoreditch', '0' );
		}

		// Did we ask to prevent Shoreditch Bootstrap styleheet?
		if ( $civicrm_admin_utilities_styles_bootstrap == '1' ) {
			$this->setting_set( 'css_bootstrap', '1' );
		} else {
			$this->setting_set( 'css_bootstrap', '0' );
		}

		// Did we ask to prevent CiviCRM custom styleheet from front-end?
		if ( $civicrm_admin_utilities_styles_custom == '1' ) {
			$this->setting_set( 'css_custom', '1' );
		} else {
			$this->setting_set( 'css_custom', '0' );
		}

		// Did we ask to prevent CiviCRM custom styleheet from admin?
		if ( $civicrm_admin_utilities_styles_custom_public == '1' ) {
			$this->setting_set( 'css_custom_public', '1' );
		} else {
			$this->setting_set( 'css_custom_public', '0' );
		}

		// Did we ask to override CiviCRM Default styleheet?
		if ( $civicrm_admin_utilities_styles_admin == '1' ) {
			$this->setting_set( 'css_admin', '1' );
		} else {
			$this->setting_set( 'css_admin', '0' );
		}

		// Get existing access setting
		$existing_access = $this->setting_get( 'prettify_access', '0' );
		if ( $civicrm_admin_utilities_access != $existing_access ) {
			$force = true;
		}

		// Did we ask to fix the access form?
		if ( $civicrm_admin_utilities_access == '1' ) {
			$this->setting_set( 'prettify_access', '1' );
		} else {
			$this->setting_set( 'prettify_access', '0' );
		}

		// Which post types are we enabling the CiviCRM button on?
		if ( count( $civicrm_admin_utilities_post_types ) > 0 ) {

			// Sanitise array
			array_walk(
				$civicrm_admin_utilities_post_types,
				function( &$item ) {
					$item = esc_sql( trim( $item ) );
				}
			);

			// Set option
			$this->setting_set( 'post_types', $civicrm_admin_utilities_post_types );

		} else {
			$this->setting_set( 'post_types', array() );
		}

		// Did we ask to add the shortcuts menu to the admin bar?
		if ( $civicrm_admin_utilities_admin_bar == '1' ) {
			$this->setting_set( 'admin_bar', '1' );
		} else {
			$this->setting_set( 'admin_bar', '0' );
		}

		// Save options
		$this->settings_save();

		// Clear caches if asked to - or if forced to do so
		if ( $civicrm_admin_utilities_cache == '1' OR $force ) {
			$this->clear_caches();
		}

		// --<
		return true;

	}



	/**
	 * Update options supplied by our Multisite admin page.
	 *
	 * @since 0.5.4
	 */
	public function settings_multisite_update() {

		// Check that we trust the source of the data
		check_admin_referer( 'civicrm_admin_utilities_multisite_action', 'civicrm_admin_utilities_multisite_nonce' );

		// Init vars
		$civicrm_admin_utilities_main_site = '';

		// Get variables
		extract( $_POST );

		// Did we ask to remove the CiviCRM menu on sub-sites?
		if ( $civicrm_admin_utilities_main_site == '1' ) {
			$this->setting_set( 'main_site_only', '1' );
		} else {
			$this->setting_set( 'main_site_only', '0' );
		}

		// Save options
		$this->settings_save();

		// --<
		return true;

	}



	/**
	 * Save array as site option.
	 *
	 * @since 0.1
	 *
	 * @return bool Success or failure.
	 */
	public function settings_save() {

		// Save array as site option
		return $this->option_set( 'civicrm_admin_utilities_settings', $this->settings );

	}



	/**
	 * Check whether a specified setting exists.
	 *
	 * @since 0.1
	 *
	 * @param string $setting_name The name of the setting.
	 * @return bool Whether or not the setting exists.
	 */
	public function setting_exists( $setting_name = '' ) {

		// Test for null
		if ( $setting_name == '' ) {
			die( __( 'You must supply a setting to setting_exists()', 'civicrm-admin-utilities' ) );
		}

		// Get existence of setting in array
		return array_key_exists( $setting_name, $this->settings );

	}



	/**
	 * Return a value for a specified setting.
	 *
	 * @since 0.1
	 *
	 * @param string $setting_name The name of the setting.
	 * @param mixed $default The default value if the setting does not exist.
	 * @return mixed The setting or the default.
	 */
	public function setting_get( $setting_name = '', $default = false ) {

		// Test for null
		if ( $setting_name == '' ) {
			die( __( 'You must supply a setting to setting_get()', 'civicrm-admin-utilities' ) );
		}

		// Get setting
		return ( array_key_exists( $setting_name, $this->settings ) ) ? $this->settings[$setting_name] : $default;

	}



	/**
	 * Sets a value for a specified setting.
	 *
	 * @since 0.1
	 *
	 * @param string $setting_name The name of the setting.
	 * @param mixed $value The value of the setting.
	 */
	public function setting_set( $setting_name = '', $value = '' ) {

		// Test for null
		if ( $setting_name == '' ) {
			die( __( 'You must supply a setting to setting_set()', 'civicrm-admin-utilities' ) );
		}

		// Test for other than string
		if ( ! is_string( $setting_name ) ) {
			die( __( 'You must supply the setting as a string to setting_set()', 'civicrm-admin-utilities' ) );
		}

		// Set setting
		$this->settings[$setting_name] = $value;

	}



	/**
	 * Deletes a specified setting.
	 *
	 * @since 0.1
	 *
	 * @param string $setting_name The name of the setting.
	 */
	public function setting_delete( $setting_name = '' ) {

		// Test for null
		if ( $setting_name == '' ) {
			die( __( 'You must supply a setting to setting_delete()', 'civicrm-admin-utilities' ) );
		}

		// Unset setting
		unset( $this->settings[$setting_name] );

	}



	/**
	 * Test existence of a specified site option.
	 *
	 * @since 0.1
	 *
	 * @param str $option_name The name of the option.
	 * @return bool $exists Whether or not the option exists.
	 */
	public function option_exists( $option_name = '' ) {

		// Test for empty
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_exists()', 'civicrm-admin-utilities' ) );
		}

		// Test by getting option with unlikely default
		if ( $this->option_get( $option_name, 'fenfgehgefdfdjgrkj' ) == 'fenfgehgefdfdjgrkj' ) {
			return false;
		} else {
			return true;
		}

	}



	/**
	 * Return a value for a specified site option.
	 *
	 * @since 0.1
	 *
	 * @param str $option_name The name of the option.
	 * @param str $default The default value of the option if it has no value.
	 * @return mixed $value the value of the option.
	 */
	public function option_get( $option_name = '', $default = false ) {

		// Test for empty
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_get()', 'civicrm-admin-utilities' ) );
		}

		// If multisite and network activated
		if ( $this->is_network_activated() ) {

			// Get site option
			$value = get_site_option( $option_name, $default );

		} else {

			// Get option
			$value = get_option( $option_name, $default );

		}

		// --<
		return $value;

	}



	/**
	 * Set a value for a specified site option.
	 *
	 * @since 0.1
	 *
	 * @param str $option_name The name of the option.
	 * @param mixed $value The value to set the option to.
	 * @return bool $success True if the value of the option was successfully saved.
	 */
	public function option_set( $option_name = '', $value = '' ) {

		// Test for empty
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_set()', 'civicrm-admin-utilities' ) );
		}

		// If multisite and network activated
		if ( $this->is_network_activated() ) {

			// Update site option
			return update_site_option( $option_name, $value );

		} else {

			// Update option
			return update_option( $option_name, $value );

		}

	}



	/**
	 * Delete a specified site option.
	 *
	 * @since 0.1
	 *
	 * @param str $option_name The name of the option.
	 * @return bool $success True if the value of the option was successfully deleted.
	 */
	public function option_delete( $option_name = '' ) {

		// Test for empty
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_delete()', 'civicrm-admin-utilities' ) );
		}

		// If multisite and network activated
		if ( $this->is_network_activated() ) {

			// Delete site option
			return delete_site_option( $option_name );

		} else {

			// Delete option
			return delete_option( $option_name );

		}

	}



} // Class ends



