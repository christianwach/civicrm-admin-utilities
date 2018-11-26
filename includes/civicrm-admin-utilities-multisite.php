<?php

/**
 * CiviCRM Admin Utilities Multisite Class.
 *
 * A class that encapsulates Multisite admin functionality.
 *
 * @since 0.5.4
 */
class CiviCRM_Admin_Utilities_Multisite {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * The installed version of the plugin.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var str $plugin_version The plugin version. (numeric string)
	 */
	public $plugin_version;

	/**
	 * Network parent page reference.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $network_parent_page The reference to the network parent page.
	 */
	public $network_parent_page;

	/**
	 * Network settings page reference.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $network_settings_page The reference to the network settings page.
	 */
	public $network_settings_page;

	/**
	 * Network Settings data.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $settings The plugin network settings data.
	 */
	public $settings = array();



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

		// If this plugin is network activated.
		if ( $this->plugin->is_network_activated() ) {

			/*
			 * Override Single Site default settings.
			 *
			 * This filter must be added prior to `register_hooks()` because the
			 * Single Site class will have already loaded its settings by then.
			 */
			add_filter( 'civicrm_admin_utilities_settings_default', array( $this, 'settings_override' ) );

		}

	}



	/**
	 * Initialise this object.
	 *
	 * @since 0.5.4
	 */
	public function initialise() {

		// Assign installed plugin version.
		$this->plugin_version = $this->option_get( 'civicrm_admin_utilities_version', false );

		// Do upgrade tasks.
		$this->upgrade_tasks();

		// Store version for later reference if there has been a change.
		if ( $this->plugin_version != CIVICRM_ADMIN_UTILITIES_VERSION ) {
			$this->store_version();
		}

		// Store default settings if none exist.
		if ( ! $this->option_exists( 'civicrm_admin_utilities_settings' ) ) {
			$this->option_set( 'civicrm_admin_utilities_settings', $this->settings_get_defaults() );
		}

		// Load settings array.
		$this->settings = $this->option_get( 'civicrm_admin_utilities_settings', $this->settings );

		// Settings upgrade tasks.
		$this->upgrade_settings();

		// Register hooks.
		$this->register_hooks();

	}



	/**
	 * Store the plugin version.
	 *
	 * @since 0.3.4
	 * @since 0.5.4 Version is stored in network settings.
	 */
	public function store_version() {

		// Store version.
		$this->option_set( 'civicrm_admin_utilities_version', CIVICRM_ADMIN_UTILITIES_VERSION );

	}



	/**
	 * Utility to do stuff when an upgrade is required.
	 *
	 * @since 0.3.4
	 * @since 0.5.4 Moved from admin class.
	 */
	public function upgrade_tasks() {

		// If this is a new install (or an upgrade from a version prior to 0.3.4).
		if ( $this->plugin_version === false ) {

			// Delete the legacy "installed" option.
			$this->delete_legacy_option();

			// Maybe move settings.
			$this->maybe_move_settings();

		}

		/*
		// For future upgrades, use something like the following.
		if ( version_compare( CIVICRM_ADMIN_UTILITIES_VERSION, '0.3.4', '>=' ) ) {
			// Do something
		}
		*/

	}



	/**
	 * Delete the legacy "installed" option.
	 *
	 * @since 0.3.4
	 * @since 0.5.4 Only deletes network option.
	 */
	public function delete_legacy_option() {

		// Delete the legacy option if it exists.
		if ( $this->option_exists( 'civicrm_admin_utilities_installed' ) ) {
			$this->option_delete( 'civicrm_admin_utilities_installed' );
		}

	}



	/**
	 * Move the settings to the correct location.
	 *
	 * This only applies to multisite instances and only when the plugin is not
	 * network-activated.
	 *
	 * There is a conundrum here, however:
	 *
	 * If this plugin is active on more than one site, then it will only be the
	 * first site where the plugin loads that gets the migrated settings. Other
	 * sites will need to reconfigure their settings for this plugin since they
	 * will have been reset to the defaults.
	 *
	 * @since 0.3.4
	 * @since 0.5.4 Moved from admin class.
	 */
	public function maybe_move_settings() {

		// Bail if network activated.
		if ( $this->plugin->is_network_activated() ) return;

		// Get current settings.
		$settings = get_site_option( 'civicrm_admin_utilities_settings', 'fefdfdjgrkj' );

		// If we have some.
		if ( $settings != 'fefdfdjgrkj' ) {

			// Save them where they are supposed to be.
			$this->option_set( 'civicrm_admin_utilities_settings', $settings );

			// Delete the "global" site option.
			//delete_site_option( 'civicrm_admin_utilities_settings' );

		}

	}



	/**
	 * Utility to do stuff when a settings upgrade is required.
	 *
	 * @since 0.4.1
	 * @since 0.5.4 Moved from admin class and made network-specific.
	 */
	public function upgrade_settings() {

		// Don't save by default.
		$save = false;

		// CSS settings may not exist.
		if ( ! $this->setting_exists( 'css_default' ) ) {

			// Add them from defaults.
			$settings = $this->settings_get_defaults();
			$this->setting_set( 'css_default', $settings['css_default'] );
			$this->setting_set( 'css_navigation', $settings['css_navigation'] );
			$this->setting_set( 'css_shoreditch', $settings['css_shoreditch'] );
			$save = true;

		}

		// Shoreditch Bootstrap CSS setting may not exist.
		if ( ! $this->setting_exists( 'css_bootstrap' ) ) {

			// Add it from defaults.
			$settings = $this->settings_get_defaults();
			$this->setting_set( 'css_bootstrap', $settings['css_bootstrap'] );
			$save = true;

		}

		// Custom CSS setting may not exist.
		if ( ! $this->setting_exists( 'css_custom' ) ) {

			// Add it from defaults.
			$settings = $this->settings_get_defaults();
			$this->setting_set( 'css_custom', $settings['css_custom'] );
			$save = true;

		}

		// Custom CSS Public setting may not exist.
		if ( ! $this->setting_exists( 'css_custom_public' ) ) {

			// Add it from defaults.
			$settings = $this->settings_get_defaults();
			$this->setting_set( 'css_custom_public', $settings['css_custom_public'] );
			$save = true;

		}

		// CiviCRM Default CSS setting may not exist.
		if ( ! $this->setting_exists( 'css_admin' ) ) {

			// Add it from defaults.
			$settings = $this->settings_get_defaults();
			$this->setting_set( 'css_admin', $settings['css_admin'] );
			$save = true;

		}

		// Restrict settings access setting may not exist.
		if ( ! $this->setting_exists( 'restrict_settings_access' ) ) {

			// Add it from defaults.
			$settings = $this->settings_get_defaults();
			$this->setting_set( 'restrict_settings_access', $settings['restrict_settings_access'] );
			$save = true;

		}

		// Restrict administer CiviCRM setting may not exist.
		if ( ! $this->setting_exists( 'restrict_administer' ) ) {

			// Add it from defaults.
			$settings = $this->settings_get_defaults();
			$this->setting_set( 'restrict_administer', $settings['restrict_administer'] );
			$save = true;

		}

		// Save settings if need be.
		if ( $save === true ) {
			$this->settings_save();
		}

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.5.4
	 */
	public function register_hooks() {

		// If CiviCRM is network activated.
		if ( $this->plugin->is_civicrm_network_activated() ) {

			// Hook in after the CiviCRM menu hook has been registered.
			add_action( 'init', array( $this, 'civicrm_on_main_site_only' ), 20 );

		}

		// Add admin page to Network Settings menu.
		add_action( 'network_admin_menu', array( $this, 'network_admin_menu' ), 30 );

		// Maybe restrict access to site settings pages.
		add_filter( 'civicrm_admin_utilities_admin_menu_cap', array( $this, 'page_access_cap' ), 10, 2 );

		// Filter CiviCRM Permissions.
		add_action( 'civicrm_permission_check', array( $this, 'permission_check' ), 10, 2 );

		// Maybe switch to main site for Shortcuts Menu.
		// TODO: Are there any situations where we'd like to switch?
		//add_action( 'civicrm_admin_utilities_menu_before', array( $this, 'shortcuts_menu_switch_to' ) );
		//add_action( 'civicrm_admin_utilities_menu_after', array( $this, 'shortcuts_menu_switch_back' ) );

	}



	//##########################################################################



	/**
	 * Add network admin menu item(s) for this plugin.
	 *
	 * @since 0.5.4
	 */
	public function network_admin_menu() {

		// We must be network admin in multisite.
		if ( ! is_super_admin() ) return;

		// Set capability.
		$capability = 'manage_network_plugins';

		// Add the parent page to the Network Settings menu.
		$this->network_parent_page = add_submenu_page(
			'settings.php', // target menu
			__( 'CiviCRM Admin Utilities: Settings', 'civicrm-admin-utilities' ), // Page title.
			__( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ), // Menu title.
			$capability, // Required caps.
			'civicrm_admin_utilities_network_parent', // Slug name.
			array( $this, 'page_network_settings' ) // Callback.
		);

		// Add help text.
		add_action( 'admin_head-' . $this->network_parent_page, array( $this, 'network_admin_head' ), 50 );

		// Add scripts and styles.
		//add_action( 'admin_print_scripts-' . $this->network_parent_page, array( $this, 'network_admin_js' ) );
		//add_action( 'admin_print_styles-' . $this->network_parent_page, array( $this, 'network_admin_css' ) );

		// Add settings page.
		$this->network_settings_page = add_submenu_page(
			'civicrm_admin_utilities_network_parent', // Parent slug.
			__( 'CiviCRM Admin Utilities: Settings', 'civicrm-admin-utilities' ), // Page title.
			__( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ), // Menu title.
			$capability, // Required caps.
			'civicrm_admin_utilities_network_settings', // Slug name.
			array( $this, 'page_network_settings' ) // Callback.
		);

		// Ensure correct menu item is highlighted.
		add_action( 'admin_head-' . $this->network_settings_page, array( $this, 'network_menu_highlight' ), 50 );

		// Add help text.
		add_action( 'admin_head-' . $this->network_settings_page, array( $this, 'network_admin_head' ), 50 );

		// Add scripts and styles.
		//add_action( 'admin_print_scripts-' . $this->network_settings_page, array( $this, 'network_admin_js' ) );
		//add_action( 'admin_print_styles-' . $this->network_settings_page, array( $this, 'network_admin_css' ) );

		// Try and update options.
		$saved = $this->settings_update_router();

	}



	/**
	 * Highlight the plugin's parent menu item.
	 *
	 * Regardless of the actual admin screen we are on, we need the parent menu
	 * item to be highlighted so that the appropriate menu is open by default
	 * when the subpage is viewed.
	 *
	 * @since 0.5.4
	 *
	 * @global string $plugin_page The current plugin page.
	 * @global string $submenu_file The current submenu.
	 */
	public function network_menu_highlight() {

		global $plugin_page, $submenu_file;

		// Define subpages.
		$subpages = array(
			'civicrm_admin_utilities_network_settings',
		);

		/**
		 * Filter the list of network subpages.
		 *
		 * @since 0.5.4
		 *
		 * @param array $subpages The existing list of network subpages.
		 * @return array $subpages The modified list of network subpages.
		 */
		$subpages = apply_filters( 'civicrm_admin_utilities_network_subpages', $subpages );

		// This tweaks the Settings subnav menu to show only one menu item.
		if ( in_array( $plugin_page, $subpages ) ) {
			$plugin_page = 'civicrm_admin_utilities_network_parent';
			$submenu_file = 'civicrm_admin_utilities_network_parent';
		}

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
		$pages = array(
			$this->network_parent_page . '-network',
			$this->network_settings_page . '-network',
		);

		// Kick out if not our screen.
		if ( ! in_array( $screen->id, $pages ) ) return $screen;

		// Add a tab - we can add more later.
		$screen->add_help_tab( array(
			'id'      => 'civicrm_admin_utilities_network',
			'title'   => __( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ),
			'content' => $this->network_admin_help_get(),
		));

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



	//##########################################################################



	/**
	 * Show our network settings page.
	 *
	 * @since 0.5.4
	 */
	public function page_network_settings() {

		// Disallow if not network admin in multisite.
		if ( is_network_admin() AND ! is_super_admin() ) {
			wp_die( __( 'You do not have permission to access this page.', 'civicrm-admin-utilities' ) );
		}

		// Check user permissions.
		if ( ! current_user_can( 'manage_network_plugins' ) ) return;

		// Get admin page URLs.
		$urls = $this->page_get_network_urls();

		// If CiviCRM is network activated.
		if ( $this->plugin->is_civicrm_network_activated() ) {

			// Init main site only checkbox.
			$main_site_only = '';
			if ( $this->setting_get( 'main_site_only', '0' ) == '1' ) {
				$main_site_only = ' checked="checked"';
			}

			// Init settings access checkbox.
			$restrict_settings_access = '';
			if ( $this->setting_get( 'restrict_settings_access', '0' ) == '1' ) {
				$restrict_settings_access = ' checked="checked"';
			}

		}

		// Init administer CiviCRM checkbox.
		$restrict_administer = '';
		if ( $this->setting_get( 'restrict_administer', '0' ) == '1' ) {
			$restrict_administer = ' checked="checked"';
		}

		// Init menu CSS checkbox.
		$prettify_menu = '';
		if ( $this->setting_get( 'prettify_menu', '0' ) == '1' ) {
			$prettify_menu = ' checked="checked"';
		}

		// Init admin CSS checkbox.
		$admin_css = '';
		if ( $this->setting_get( 'css_admin', '0' ) == '1' ) {
			$admin_css = ' checked="checked"';
		}

		// Init default CSS checkbox.
		$default_css = '';
		if ( $this->setting_get( 'css_default', '0' ) == '1' ) {
			$default_css = ' checked="checked"';
		}

		// Init navigation CSS checkbox.
		$navigation_css = '';
		if ( $this->setting_get( 'css_navigation', '0' ) == '1' ) {
			$navigation_css = ' checked="checked"';
		}

		// Init Shoreditch CSS checkbox.
		$shoreditch_css = '';
		if ( $this->setting_get( 'css_shoreditch', '0' ) == '1' ) {
			$shoreditch_css = ' checked="checked"';
		}

		// Init Shoreditch Bootstrap CSS checkbox.
		$bootstrap_css = '';
		if ( $this->setting_get( 'css_bootstrap', '0' ) == '1' ) {
			$bootstrap_css = ' checked="checked"';
		}

		// Init custom CSS checkbox.
		$custom_css = '';
		if ( $this->setting_get( 'css_custom', '0' ) == '1' ) {
			$custom_css = ' checked="checked"';
		}

		// Init custom CSS on front end checkbox.
		$custom_public_css = '';
		if ( $this->setting_get( 'css_custom_public', '0' ) == '1' ) {
			$custom_public_css = ' checked="checked"';
		}

		// Init access form checkbox.
		$prettify_access = '';
		if ( $this->setting_get( 'prettify_access', '0' ) == '1' ) {
			$prettify_access = ' checked="checked"';
		}

		// Init admin bar checkbox.
		$admin_bar = '';
		if ( $this->setting_get( 'admin_bar', '0' ) == '1' ) {
			$admin_bar = ' checked="checked"';
		}

		// Get selected post types.
		$selected_types = $this->setting_get( 'post_types', array() );

		// Get post type options.
		$options = $this->plugin->single->post_type_options_get( $selected_types );

		// Do not show tabs by default.
		$show_tabs = false;

		// Include template.
		include( CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/network-settings.php' );

	}



	/**
	 * Get network admin page URLs.
	 *
	 * @since 0.5.4
	 *
	 * @return array $network_urls The array of network admin page URLs.
	 */
	public function page_get_network_urls() {

		// Only calculate once.
		if ( isset( $this->network_urls ) ) {
			return $this->network_urls;
		}

		// Init return.
		$this->network_urls = array();

		// Get admin page URLs via our adapted method.
		$this->network_urls['settings'] = $this->network_menu_page_url( 'civicrm_admin_utilities_network_settings', false );

		/**
		 * Filter the list of network URLs.
		 *
		 * @since 0.5.4
		 *
		 * @param array $urls The existing list of network URLs.
		 * @return array $urls The modified list of network URLs.
		 */
		$this->network_urls = apply_filters( 'civicrm_admin_utilities_network_page_urls', $this->network_urls );

		// --<
		return $this->network_urls;

	}



	/**
	 * Maybe restrict access to site settings pages.
	 *
	 * @since 0.5.4
	 *
	 * @param str $capability The existing access capability.
	 * @return str $capability The modified access capability.
	 */
	public function page_access_cap( $capability ) {

		// Assign network admin capability if we are restricting access.
		if ( $this->setting_get( 'restrict_settings_access', '0' ) == '1' ) {
			$capability = 'manage_network_plugins';
		}

		// --<
		return $capability;

	}



	//##########################################################################



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
	 * Do not load CiviCRM on sites other than the main site.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class.
	 */
	public function civicrm_on_main_site_only() {

		// Bail if disabled.
		if ( $this->setting_get( 'main_site_only', '0' ) == '0' ) return;

		// If not on main site.
		if ( ! is_main_site() ) {

			// Unhook CiviCRM's menu item, but allow CiviCRM to load.
			remove_action( 'admin_menu', array( civi_wp(), 'add_menu_items' ) );

			// Remove notice.
			remove_action( 'admin_notices', array( civi_wp(), 'show_setup_warning' ) );

			// Remove CiviCRM shortcode button.
			add_action( 'admin_head', array( $this->plugin->single, 'civi_button_remove' ) );

			// Remove Shortcuts Menu from WordPress admin bar.
			remove_action( 'admin_bar_menu', array( $this->plugin->single, 'shortcuts_menu_add' ), 2000 );

			// Remove this plugin's menu item.
			remove_action( 'admin_menu', array( $this->plugin->single, 'admin_menu' ) );

		}

	}



	//##########################################################################



	/**
	 * Maybe switch to main site before configuring Shortcuts Menu.
	 *
	 * @since 0.5.4
	 */
	public function shortcuts_menu_switch_to() {

		// Get current site data.
		$current_site = get_current_site();

		// Switch to the main site.
		switch_to_blog( $current_site->blog_id );

	}



	/**
	 * Maybe switch back to current site after configuring Shortcuts Menu.
	 *
	 * @since 0.5.4
	 */
	public function shortcuts_menu_switch_back() {

		// Switch back to current blog.
		restore_current_blog();

	}



	//##########################################################################



	/**
	 * Filter CiviCRM permissions.
	 *
	 * We filter permissions in order to prevent Site Admins from administering
	 * CiviCRM in situations where that's not desired. It has to be done via a
	 * filter since there's no way to edit the capabilities of site admins in
	 * CiviCRM at present.
	 *
	 * @since 0.5.4
	 *
	 * @param str $permission The requested permission.
	 * @param bool $granted True if permission granted, false otherwise.
	 */
	public function permission_check( $permission, &$granted ) {

		// Only check "administer CiviCRM".
		if ( $permission !== 'administer CiviCRM' ) return;

		// Bail if we're not restricting.
		if ( $this->setting_get( 'restrict_administer', '0' ) == '0' ) return;

		// Get current user.
		$user = wp_get_current_user();

	    // Sanity check.
		if ( ! ( $user instanceof WP_User ) ) return;

		// Are we a network admin?
		if ( $user->has_cap( 'manage_network_plugins' ) ) return;

		// Disallow everyone else.
		$granted = 0;

	}



	//##########################################################################



	/**
	 * Get default network settings values for this plugin.
	 *
	 * In a multisite context, these defaults are used for both network defaults
	 * and individual sites.
	 *
	 * @since 0.5.4
	 *
	 * @return array $settings The default values for this plugin.
	 */
	public function settings_get_defaults() {

		// Init return.
		$settings = array();

		// Do not restrict to main site only.
		$settings['main_site_only'] = '0';

		// Allow site admins access to site settings page.
		$settings['restrict_settings_access'] = '0';

		// Allow site admins to administer CiviCRM.
		$settings['restrict_administer'] = '0';

		// Prettify menu.
		$settings['prettify_menu'] = '1';

		// Restrict CSS files from front-end.
		$settings['css_default'] = '0'; // Load default.
		$settings['css_navigation'] = '1'; // Do not load CiviCRM menu.
		$settings['css_shoreditch'] = '0'; // Load Shoreditch.
		$settings['css_bootstrap'] = '0'; // Load Shoreditch Bootstrap.
		$settings['css_custom'] = '0'; // Load Custom Stylesheet on front-end.
		$settings['css_custom_public'] = '0'; // Load Custom Stylesheet on admin.

		// Override default CiviCRM CSS in wp-admin.
		$settings['css_admin'] = '0'; // Do not override by default.

		// Fix WordPress Access Control table.
		$settings['prettify_access'] = '1';

		// Do not assume WordPress Access Control table is fixed.
		$settings['access_fixed'] = '0';

		// Init post types with defaults.
		$settings['post_types'] = array( 'post', 'page' );

		// Add menu to admin bar.
		$settings['admin_bar'] = '1';

		/**
		 * Filter default network settings.
		 *
		 * @since 0.5.4
		 *
		 * @param array $settings The array of default network settings.
		 * @return array $settings The modified array of default network settings.
		 */
		$settings = apply_filters( 'civicrm_admin_utilities_network_settings_default', $settings );

		// --<
		return $settings;

	}



	/**
	 * Override Single Site settings on first load.
	 *
	 * @since 0.5.4
	 *
	 * @param array $settings The array of default settings.
	 * @return array $settings The modified array of default settings.
	 */
	public function settings_override( $settings ) {

		// Get network settings with fallback to defaults if empty (e.g. on activation).
		$network_settings = $this->option_get( 'civicrm_admin_utilities_settings', $this->settings_get_defaults() );

		// Override Single Site values with the values from Network.
		if ( ! empty( $network_settings ) ) {
			foreach( $settings AS $key => $setting ) {
				if ( ! empty( $network_settings[$key] ) ) {
					$settings[$key] = $network_settings[$key];
				}
			}
		}

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

		// Init return.
		$result = false;

		// was the "Network Settings" form submitted?
		if ( isset( $_POST['civicrm_admin_utilities_network_settings_submit'] ) ) {
			return $this->settings_network_update();
		}

		// --<
		return $result;

	}



	/**
	 * Update options supplied by our Network Settings admin page.
	 *
	 * @since 0.5.4
	 *
	 * @return bool True if successful, false otherwise (always true at present).
	 */
	public function settings_network_update() {

		// Check that we trust the source of the data.
		check_admin_referer( 'civicrm_admin_utilities_network_settings_action', 'civicrm_admin_utilities_network_settings_nonce' );

		// Init vars.
		$civicrm_admin_utilities_main_site = '';
		$civicrm_admin_utilities_restrict_settings_access = '';
		$civicrm_admin_utilities_restrict_administer = '';
		$civicrm_admin_utilities_menu = '';
		$civicrm_admin_utilities_access = '';
		$civicrm_admin_utilities_post_types = array();
		$civicrm_admin_utilities_admin_bar = '';
		$civicrm_admin_utilities_styles_default = '';
		$civicrm_admin_utilities_styles_nav = '';
		$civicrm_admin_utilities_styles_shoreditch = '';
		$civicrm_admin_utilities_styles_bootstrap = '';
		$civicrm_admin_utilities_styles_custom = '';
		$civicrm_admin_utilities_styles_custom_public = '';
		$civicrm_admin_utilities_styles_admin = '';

		// Get variables.
		extract( $_POST );

		// Should we remove the visible traces of CiviCRM on sub-sites?
		if ( $civicrm_admin_utilities_main_site == '1' ) {
			$this->setting_set( 'main_site_only', '1' );
		} else {
			$this->setting_set( 'main_site_only', '0' );
		}

		// Should we restrict access to site settings pages?
		if ( $civicrm_admin_utilities_restrict_settings_access == '1' ) {
			$this->setting_set( 'restrict_settings_access', '1' );
		} else {
			$this->setting_set( 'restrict_settings_access', '0' );
		}

		// Should we restrict administer CiviCRM capability?
		if ( $civicrm_admin_utilities_restrict_administer == '1' ) {
			$this->setting_set( 'restrict_administer', '1' );
		} else {
			$this->setting_set( 'restrict_administer', '0' );
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

		// Did we ask to fix the access form?
		if ( $civicrm_admin_utilities_access == '1' ) {
			$this->setting_set( 'prettify_access', '1' );
		} else {
			$this->setting_set( 'prettify_access', '0' );
		}

		// Which post types are we enabling the CiviCRM button on?
		if ( count( $civicrm_admin_utilities_post_types ) > 0 ) {

			// Sanitise array.
			array_walk(
				$civicrm_admin_utilities_post_types,
				function( &$item ) {
					$item = esc_sql( trim( $item ) );
				}
			);

			// Set option.
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

		// Save options.
		$this->settings_save();

		// --<
		return true;

	}



	//##########################################################################



	/**
	 * Save array as site option.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class and made network-specific.
	 *
	 * @return bool Success or failure.
	 */
	public function settings_save() {

		// Save array as network option.
		return $this->option_set( 'civicrm_admin_utilities_settings', $this->settings );

	}



	/**
	 * Check whether a specified setting exists.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class and made network-specific.
	 *
	 * @param string $setting_name The name of the setting.
	 * @return bool Whether or not the setting exists.
	 */
	public function setting_exists( $setting_name = '' ) {

		// Test for empty.
		if ( $setting_name == '' ) {
			die( __( 'You must supply a setting to setting_exists()', 'civicrm-admin-utilities' ) );
		}

		// Get existence of setting in array.
		return array_key_exists( $setting_name, $this->settings );

	}



	/**
	 * Return a value for a specified setting.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class and made network-specific.
	 *
	 * @param string $setting_name The name of the setting.
	 * @param mixed $default The default value if the setting does not exist.
	 * @return mixed The setting or the default.
	 */
	public function setting_get( $setting_name = '', $default = false ) {

		// Test for empty.
		if ( $setting_name == '' ) {
			die( __( 'You must supply a setting to setting_get()', 'civicrm-admin-utilities' ) );
		}

		// Get setting.
		return ( array_key_exists( $setting_name, $this->settings ) ) ? $this->settings[$setting_name] : $default;

	}



	/**
	 * Sets a value for a specified setting.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class and made network-specific.
	 *
	 * @param string $setting_name The name of the setting.
	 * @param mixed $value The value of the setting.
	 */
	public function setting_set( $setting_name = '', $value = '' ) {

		// Test for empty.
		if ( $setting_name == '' ) {
			die( __( 'You must supply a setting to setting_set()', 'civicrm-admin-utilities' ) );
		}

		// Test for other than string.
		if ( ! is_string( $setting_name ) ) {
			die( __( 'You must supply the setting as a string to setting_set()', 'civicrm-admin-utilities' ) );
		}

		// Set setting.
		$this->settings[$setting_name] = $value;

	}



	/**
	 * Deletes a specified setting.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class and made network-specific.
	 *
	 * @param string $setting_name The name of the setting.
	 */
	public function setting_delete( $setting_name = '' ) {

		// Test for empty.
		if ( $setting_name == '' ) {
			die( __( 'You must supply a setting to setting_delete()', 'civicrm-admin-utilities' ) );
		}

		// Unset setting.
		unset( $this->settings[$setting_name] );

	}



	//##########################################################################



	/**
	 * Test existence of a specified network option.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class and made network-specific.
	 *
	 * @param str $option_name The name of the option.
	 * @return bool $exists Whether or not the option exists.
	 */
	public function option_exists( $option_name = '' ) {

		// Test for empty.
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_exists()', 'civicrm-admin-utilities' ) );
		}

		// Test by getting option with unlikely default.
		if ( $this->option_get( $option_name, 'fenfgehgefdfdjgrkj' ) == 'fenfgehgefdfdjgrkj' ) {
			return false;
		} else {
			return true;
		}

	}



	/**
	 * Return a value for a specified network option.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class and made network-specific.
	 *
	 * @param str $option_name The name of the option.
	 * @param str $default The default value of the option if it has no value.
	 * @return mixed $value the value of the option.
	 */
	public function option_get( $option_name = '', $default = false ) {

		// Test for empty.
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_get()', 'civicrm-admin-utilities' ) );
		}

		// Get network option.
		$value = get_site_option( $option_name, $default );

		// --<
		return $value;

	}



	/**
	 * Set a value for a specified network option.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class and made network-specific.
	 *
	 * @param str $option_name The name of the option.
	 * @param mixed $value The value to set the option to.
	 * @return bool $success True if the value of the option was successfully updated.
	 */
	public function option_set( $option_name = '', $value = '' ) {

		// Test for empty.
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_set()', 'civicrm-admin-utilities' ) );
		}

		// Update network option.
		return update_site_option( $option_name, $value );

	}



	/**
	 * Delete a specified network option.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class and made network-specific.
	 *
	 * @param str $option_name The name of the option.
	 * @return bool $success True if the value of the option was successfully deleted.
	 */
	public function option_delete( $option_name = '' ) {

		// Test for empty.
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_delete()', 'civicrm-admin-utilities' ) );
		}

		// Delete network option.
		return delete_site_option( $option_name );

	}



} // Class ends.



