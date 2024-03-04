<?php
/**
 * Multisite Class.
 *
 * Handles Multisite functionality.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Admin Utilities Multisite Class.
 *
 * A class that encapsulates Multisite admin functionality.
 *
 * @since 0.5.4
 */
class CiviCRM_Admin_Utilities_Multisite {

	/**
	 * Plugin object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var object
	 */
	public $plugin;

	/**
	 * The installed version of the plugin.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var string
	 */
	public $plugin_version;

	/**
	 * Network parent page reference.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var string
	 */
	public $network_parent_page;

	/**
	 * Network settings page reference.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var string
	 */
	public $network_settings_page;

	/**
	 * Network site settings page reference.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var string
	 */
	public $network_site_settings_page;

	/**
	 * Network Settings data.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array
	 */
	public $settings = [];

	/**
	 * Network URLs.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array
	 */
	public $network_urls;

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

		/*
		 * Override Single Site default settings.
		 *
		 * This filter must be added prior to `register_hooks()` because the
		 * Single Site class will have already loaded its settings by then.
		 */
		add_filter( 'civicrm_admin_utilities_settings_default', [ $this, 'settings_override' ] );

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
		if ( CIVICRM_ADMIN_UTILITIES_VERSION !== $this->plugin_version ) {
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
		if ( false === $this->plugin_version ) {

			// Delete the legacy "installed" option.
			$this->delete_legacy_option();

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
	 * Utility to do stuff when a settings upgrade is required.
	 *
	 * @since 0.4.1
	 * @since 0.5.4 Moved from admin class and made network-specific.
	 */
	public function upgrade_settings() {

		// Don't save by default.
		$save = false;

		// Restrict settings access setting may not exist.
		if ( ! $this->setting_exists( 'restrict_settings_access' ) ) {

			// Add it from defaults.
			if ( ! isset( $settings ) ) {
				$settings = $this->settings_get_defaults();
			}
			$this->setting_set( 'restrict_settings_access', $settings['restrict_settings_access'] );
			$save = true;

		}

		// Restrict domain access setting may not exist.
		if ( ! $this->setting_exists( 'restrict_domain_access' ) ) {

			// Add it from defaults.
			if ( ! isset( $settings ) ) {
				$settings = $this->settings_get_defaults();
			}
			$this->setting_set( 'restrict_domain_access', $settings['restrict_domain_access'] );
			$save = true;

		}

		// Restrict administer CiviCRM setting may not exist.
		if ( ! $this->setting_exists( 'restrict_administer' ) ) {

			// Add it from defaults.
			if ( ! isset( $settings ) ) {
				$settings = $this->settings_get_defaults();
			}
			$this->setting_set( 'restrict_administer', $settings['restrict_administer'] );
			$save = true;

		}

		// Hide CiviCRM setting may not exist.
		if ( ! $this->setting_exists( 'hide_civicrm' ) ) {

			// Add it from defaults.
			if ( ! isset( $settings ) ) {
				$settings = $this->settings_get_defaults();
			}
			$this->setting_set( 'hide_civicrm', $settings['hide_civicrm'] );
			$save = true;

		}

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
			if ( ! isset( $settings ) ) {
				$settings = $this->settings_get_defaults();
			}
			$this->setting_set( 'css_bootstrap', $settings['css_bootstrap'] );
			$save = true;

		}

		// Custom CSS setting may not exist.
		if ( ! $this->setting_exists( 'css_custom' ) ) {

			// Add it from defaults.
			if ( ! isset( $settings ) ) {
				$settings = $this->settings_get_defaults();
			}
			$this->setting_set( 'css_custom', $settings['css_custom'] );
			$save = true;

		}

		// Custom CSS Public setting may not exist.
		if ( ! $this->setting_exists( 'css_custom_public' ) ) {

			// Add it from defaults.
			if ( ! isset( $settings ) ) {
				$settings = $this->settings_get_defaults();
			}
			$this->setting_set( 'css_custom_public', $settings['css_custom_public'] );
			$save = true;

		}

		// Override CiviCRM Default CSS setting may not exist.
		if ( ! $this->setting_exists( 'css_admin' ) ) {

			// Add it from defaults.
			if ( ! isset( $settings ) ) {
				$settings = $this->settings_get_defaults();
			}
			$this->setting_set( 'css_admin', $settings['css_admin'] );
			$save = true;

		}

		// Suppress Email setting may not exist.
		if ( ! $this->setting_exists( 'email_suppress' ) ) {

			// Add it from defaults.
			if ( ! isset( $settings ) ) {
				$settings = $this->settings_get_defaults();
			}
			$this->setting_set( 'email_suppress', $settings['email_suppress'] );
			$save = true;

		}

		// Hide "Manage Groups" menu item setting may not exist.
		if ( ! $this->setting_exists( 'admin_bar_groups' ) ) {

			// Add it from defaults.
			if ( ! isset( $settings ) ) {
				$settings = $this->settings_get_defaults();
			}
			$this->setting_set( 'admin_bar_groups', $settings['admin_bar_groups'] );
			$save = true;

		}

		// Fix Contact Soft Delete setting may not exist.
		if ( ! $this->setting_exists( 'fix_soft_delete' ) ) {

			// Add it from defaults.
			if ( ! isset( $settings ) ) {
				$settings = $this->settings_get_defaults();
			}
			$this->setting_set( 'fix_soft_delete', $settings['fix_soft_delete'] );
			$save = true;

		}

		// Dashboard Title setting may not exist.
		if ( ! $this->setting_exists( 'dashboard_title' ) ) {

			// Add it from defaults.
			if ( ! isset( $settings ) ) {
				$settings = $this->settings_get_defaults();
			}
			$this->setting_set( 'dashboard_title', $settings['dashboard_title'] );
			$save = true;

		}

		// Fix API timezone setting may not exist.
		if ( ! $this->setting_exists( 'fix_api_timezone' ) ) {

			// Add it from defaults.
			if ( ! isset( $settings ) ) {
				$settings = $this->settings_get_defaults();
			}
			$this->setting_set( 'fix_api_timezone', $settings['fix_api_timezone'] );
			$save = true;

		}

		// Save settings if need be.
		if ( true === $save ) {
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
			add_action( 'init', [ $this, 'civicrm_on_main_site_only' ], 20 );

		}

		// Add admin page to Network Settings menu.
		add_action( 'network_admin_menu', [ $this, 'network_admin_menu' ], 30 );

		// Add our meta boxes.
		add_action( 'cau/network/settings/add_meta_boxes', [ $this, 'network_settings_meta_boxes_add' ], 11, 1 );
		add_action( 'cau/network/settings/site/add_meta_boxes', [ $this, 'network_site_meta_boxes_add' ], 11, 1 );

		// Maybe restrict access to site settings page.
		add_filter( 'civicrm_admin_utilities_page_settings_cap', [ $this, 'page_settings_cap' ], 10, 2 );

		// Maybe restrict access to site domain page.
		add_filter( 'civicrm_admin_utilities_page_domain_cap', [ $this, 'page_domain_cap' ], 10, 2 );

		// Filter CiviCRM Permissions.
		add_action( 'civicrm_permission_check', [ $this, 'permission_check' ], 10, 2 );
		add_filter( 'civicrm/admin/settings/cap', [ $this, 'permission_check_subpages' ] );
		add_filter( 'civicrm/admin/integration/cap', [ $this, 'permission_check_subpages' ] );

		// Maybe filter restrict-to-main-site template variable.
		add_filter( 'civicrm_admin_utilities_page_settings_restricted', [ $this, 'page_settings_restricted' ], 10, 1 );

		/*
		// Maybe switch to main site for Shortcuts Menu.
		// TODO: Are there any situations where we'd like to switch?
		add_action( 'civicrm_admin_utilities_menu_before', [ $this, 'shortcuts_menu_switch_to' ] );
		add_action( 'civicrm_admin_utilities_menu_after', [ $this, 'shortcuts_menu_switch_back' ] );
		*/

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

		// Set capability.
		$capability = 'manage_network_plugins';

		// Add the parent page to the Network Admin "Settings" menu.
		$this->network_parent_page = add_submenu_page(
			'settings.php', // Target menu.
			__( 'CiviCRM Admin Utilities: Settings', 'civicrm-admin-utilities' ), // Page title.
			__( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ), // Menu title.
			$capability, // Required caps.
			'cau_network_parent', // Slug name.
			[ $this, 'page_network_settings' ] // Callback.
		);

		// Register our form submit hander.
		add_action( 'load-' . $this->network_parent_page, [ $this, 'settings_update_router' ] );

		// Add help text.
		add_action( 'admin_head-' . $this->network_parent_page, [ $this, 'network_admin_head' ], 50 );

		// Add scripts and styles.
		add_action( 'admin_print_scripts-' . $this->network_parent_page, [ $this, 'page_network_settings_js' ] );

		// Add "Network Settings" sub-page.
		$this->network_settings_page = add_submenu_page(
			'cau_network_parent', // Parent slug.
			__( 'CiviCRM Admin Utilities: Settings', 'civicrm-admin-utilities' ), // Page title.
			__( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ), // Menu title.
			$capability, // Required caps.
			'cau_network_settings', // Slug name.
			[ $this, 'page_network_settings' ] // Callback.
		);

		// Register our form submit hander.
		add_action( 'load-' . $this->network_settings_page, [ $this, 'settings_update_router' ] );

		// Ensure correct menu item is highlighted.
		add_action( 'admin_head-' . $this->network_settings_page, [ $this, 'network_menu_highlight' ], 50 );

		// Add help text.
		add_action( 'admin_head-' . $this->network_settings_page, [ $this, 'network_admin_head' ], 50 );

		// Add scripts and styles.
		add_action( 'admin_print_scripts-' . $this->network_settings_page, [ $this, 'page_network_settings_js' ] );

		// Add "Site Settings" sub-page.
		$this->network_site_settings_page = add_submenu_page(
			'cau_network_parent', // Parent slug.
			__( 'CiviCRM Admin Utilities: Settings', 'civicrm-admin-utilities' ), // Page title.
			__( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ), // Menu title.
			$capability, // Required caps.
			'cau_network_site', // Slug name.
			[ $this, 'page_network_site_settings' ] // Callback.
		);

		// Register our form submit hander.
		add_action( 'load-' . $this->network_site_settings_page, [ $this, 'settings_update_router' ] );

		// Ensure correct menu item is highlighted.
		add_action( 'admin_head-' . $this->network_site_settings_page, [ $this, 'network_menu_highlight' ], 50 );

		// Add help text.
		add_action( 'admin_head-' . $this->network_site_settings_page, [ $this, 'network_admin_head' ], 50 );

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
			$this->network_parent_page . '-network',
			$this->network_settings_page . '-network',
			$this->network_site_settings_page . '-network',
		];

		// Kick out if not our screen.
		if ( ! in_array( $screen->id, $pages, true ) ) {
			return $screen;
		}

		// Build tab args.
		$args = [
			'id'      => 'civicrm_admin_utilities_network',
			'title'   => __( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ),
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

	/**
	 * Get the URL to access a particular menu page.
	 *
	 * The URL based on the slug it was registered with. If the slug hasn't been
	 * registered properly no url will be returned.
	 *
	 * @since 0.5.4
	 *
	 * @param string $menu_slug The slug name to refer to this menu by (should be unique for this menu).
	 * @param bool   $echo Whether or not to echo the url - default is true.
	 * @return string $url The URL.
	 */
	public function network_menu_page_url( $menu_slug, $echo = true ) {

		global $_parent_pages;

		if ( isset( $_parent_pages[ $menu_slug ] ) ) {
			$parent_slug = $_parent_pages[ $menu_slug ];
			if ( $parent_slug && ! isset( $_parent_pages[ $parent_slug ] ) ) {
				$url = network_admin_url( add_query_arg( 'page', $menu_slug, $parent_slug ) );
			} else {
				$url = network_admin_url( 'admin.php?page=' . $menu_slug );
			}
		} else {
			$url = '';
		}

		$url = esc_url( $url );

		if ( $echo ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $url;
		}

		// --<
		return $url;

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

		// We need to override these to highlight the correct item.
		global $plugin_page, $submenu_file;

		// Define subpages.
		$subpages = [
			'cau_network_settings',
			'cau_network_site',
		];

		/**
		 * Filter the list of network subpages.
		 *
		 * @since 0.5.4
		 *
		 * @param array $subpages The existing list of network subpages.
		 */
		$subpages = apply_filters( 'civicrm_admin_utilities_network_subpages', $subpages );

		// This tweaks the Settings subnav menu to show only one menu item.
		if ( in_array( $plugin_page, $subpages, true ) ) {
			// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
			$plugin_page  = 'cau_network_parent';
			$submenu_file = 'cau_network_parent';
			// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Register meta boxes for our "Network Settings" page.
	 *
	 * @since 0.8.1
	 *
	 * @param str $screen_id The Admin Page Screen ID.
	 */
	public function network_settings_meta_boxes_add( $screen_id ) {

		// Define valid Screen IDs.
		$screen_ids = [
			'settings_page_cau_network_parent-network',
			'admin_page_cau_network_settings-network',
		];

		// Bail if not the Screen ID we want.
		if ( ! in_array( $screen_id, $screen_ids, true ) ) {
			return;
		}

		// Bail if user does not have permission.
		if ( ! current_user_can( 'manage_network_plugins' ) ) {
			return;
		}

		// Create Submit metabox.
		add_meta_box(
			'submitdiv',
			__( 'Settings', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_settings_submit_render' ], // Callback.
			$screen_id, // Screen ID.
			'side', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

		// Create CiviCRM Network Settings metabox.
		add_meta_box(
			'civicrm_au_network_settings',
			__( 'CiviCRM Network Settings', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_network_settings_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

	}

	/**
	 * Render a Submit meta box for our "Network Settings" page.
	 *
	 * @since 0.8.1
	 */
	public function meta_box_settings_submit_render() {

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/network-metabox-settings-submit.php';

	}

	/**
	 * Render CiviCRM Network Settings meta box on Admin screen.
	 *
	 * @since 0.8.1
	 */
	public function meta_box_network_settings_render() {

		// Init visibility flag for sections dependent on CiviCRM restriction.
		$civicrm_restricted = '';

		// If CiviCRM is network activated.
		if ( $this->plugin->is_civicrm_network_activated() ) {

			// Init main site only checkbox.
			$main_site_only = 0;
			if ( $this->setting_get( 'main_site_only', '0' ) === '1' ) {
				$main_site_only = 1;
			}

			// Maybe override visibility flag for sections dependent on CiviCRM restriction.
			if ( ! empty( $main_site_only ) ) {
				$civicrm_restricted = ' style="display: none;"';
			}

		}

		// Init visibility flag for sections dependent on Settings Page restriction.
		$settings_restricted = '';

		// Init settings access checkbox.
		$restrict_settings_access = 0;
		if ( $this->setting_get( 'restrict_settings_access', '0' ) === '1' ) {
			$restrict_settings_access = 1;
		}

		// Maybe override visibility flag for sections dependent on CiviCRM restriction.
		if ( ! empty( $restrict_settings_access ) ) {
			$settings_restricted = ' style="display: none;"';
		}

		// Init domain access checkbox.
		$restrict_domain_access = 0;
		if ( $this->setting_get( 'restrict_domain_access', '0' ) === '1' ) {
			$restrict_domain_access = 1;
		}

		// Init administer CiviCRM checkbox.
		$restrict_administer = 0;
		if ( $this->setting_get( 'restrict_administer', '0' ) === '1' ) {
			$restrict_administer = 1;
		}

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/network-metabox-network-settings.php';

	}

	// -------------------------------------------------------------------------

	/**
	 * Register meta boxes for our "Site Settings" page.
	 *
	 * @since 0.8.1
	 *
	 * @param str $screen_id The Admin Page Screen ID.
	 */
	public function network_site_meta_boxes_add( $screen_id ) {

		// Define valid Screen IDs.
		$screen_ids = [
			'admin_page_cau_network_site-network',
		];

		// Bail if not the Screen ID we want.
		if ( ! in_array( $screen_id, $screen_ids, true ) ) {
			return;
		}

		// Bail if user does not have permission.
		if ( ! current_user_can( 'manage_network_plugins' ) ) {
			return;
		}

		// Create Submit metabox.
		add_meta_box(
			'submitdiv',
			__( 'Settings', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_site_submit_render' ], // Callback.
			$screen_id, // Screen ID.
			'side', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

		// Create CiviCRM Access metabox.
		add_meta_box(
			'civicrm_au_access',
			__( 'CiviCRM Access', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_access_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

		// Create CiviCRM Admin Appearance metabox.
		add_meta_box(
			'civicrm_au_appearance',
			__( 'CiviCRM Admin Appearance', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_appearance_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

		// Create CiviCRM Stylesheets metabox.
		add_meta_box(
			'civicrm_au_stylesheets',
			__( 'CiviCRM Stylesheets', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_stylesheets_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

		// Create CiviCRM Contacts & WordPress Users metabox.
		add_meta_box(
			'civicrm_au_contacts',
			__( 'CiviCRM Contacts &amp; WordPress Users', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_contacts_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

		// Create Admin Bar Options metabox.
		add_meta_box(
			'civicrm_au_admin_bar',
			__( 'Admin Bar Options', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_admin_bar_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

		// Create Post Type Options metabox.
		add_meta_box(
			'civicrm_au_post_types',
			__( 'Post Type Options', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_post_types_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

		// Create Other Fixes metabox.
		add_meta_box(
			'civicrm_au_fixes',
			__( 'Other Fixes', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_fixes_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

	}

	/**
	 * Render a Submit meta box for our "Site Settings" page.
	 *
	 * @since 0.8.1
	 */
	public function meta_box_site_submit_render() {

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/network-metabox-site-submit.php';

	}

	/**
	 * Render CiviCRM Access meta box on Admin screen.
	 *
	 * @since 0.8.1
	 */
	public function meta_box_access_render() {

		// Init Hide CiviCRM checkbox.
		$hide_civicrm = 0;
		if ( $this->setting_get( 'hide_civicrm', '0' ) === '1' ) {
			$hide_civicrm = 1;
		}

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/site-metabox-access.php';

	}

	/**
	 * Render CiviCRM Admin Appearance meta box on Admin screen.
	 *
	 * @since 0.8.1
	 */
	public function meta_box_appearance_render() {

		// Init "Dashboard Title" checkbox.
		$dashboard_title = 0;
		if ( $this->setting_get( 'dashboard_title', '0' ) === '1' ) {
			$dashboard_title = 1;
		}

		// Init menu CSS checkbox.
		$prettify_menu = 0;
		if ( $this->setting_get( 'prettify_menu', '0' ) === '1' ) {
			$prettify_menu = 1;
		}

		// Init admin CSS checkbox.
		$admin_css     = 0;
		$theme_preview = '';
		if ( $this->setting_get( 'css_admin', '0' ) === '1' ) {
			$admin_css = 1;
		}

		// Always hide theme preview.
		$theme_preview = ' display: none;';

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/site-metabox-appearance.php';

	}

	/**
	 * Render CiviCRM Stylesheets meta box on Admin screen.
	 *
	 * @since 0.8.1
	 */
	public function meta_box_stylesheets_render() {

		// Init default CSS checkbox.
		$default_css = 0;
		if ( $this->setting_get( 'css_default', '0' ) === '1' ) {
			$default_css = 1;
		}

		// Init navigation CSS checkbox.
		$navigation_css = 0;
		if ( $this->setting_get( 'css_navigation', '0' ) === '1' ) {
			$navigation_css = 1;
		}

		// Init custom CSS checkbox.
		$custom_css = 0;
		if ( $this->setting_get( 'css_custom', '0' ) === '1' ) {
			$custom_css = 1;
		}

		// Init custom CSS on front end checkbox.
		$custom_public_css = 0;
		if ( $this->setting_get( 'css_custom_public', '0' ) === '1' ) {
			$custom_public_css = 1;
		}

		// Init Shoreditch CSS checkbox.
		$shoreditch_css = 0;
		if ( $this->setting_get( 'css_shoreditch', '0' ) === '1' ) {
			$shoreditch_css = 1;
		}

		// Init Shoreditch Bootstrap CSS checkbox.
		$bootstrap_css = 0;
		if ( $this->setting_get( 'css_bootstrap', '0' ) === '1' ) {
			$bootstrap_css = 1;
		}

		// Always show Shoreditch options.
		$shoreditch = true;

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/site-metabox-stylesheets.php';

	}

	/**
	 * Render CiviCRM Contacts & WordPress Users meta box on Admin screen.
	 *
	 * @since 0.8.1
	 */
	public function meta_box_contacts_render() {

		// Init suppress email checkbox.
		$email_suppress = 0;
		if ( $this->setting_get( 'email_suppress', '0' ) === '1' ) {
			$email_suppress = 1;
		}

		// Init "Fix Soft Delete" checkbox.
		$fix_soft_delete = 0;
		if ( $this->setting_get( 'fix_soft_delete', '0' ) === '1' ) {
			$fix_soft_delete = 1;
		}

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/site-metabox-contacts.php';

	}

	/**
	 * Render Admin Bar Options meta box on Admin screen.
	 *
	 * @since 0.8.1
	 */
	public function meta_box_admin_bar_render() {

		// Init admin bar checkbox.
		$admin_bar = 0;
		if ( $this->setting_get( 'admin_bar', '0' ) === '1' ) {
			$admin_bar = 1;
		}

		// Init hide "Manage Groups" admin bar menu item checkbox.
		$admin_bar_groups = 0;
		if ( $this->setting_get( 'admin_bar_groups', '0' ) === '1' ) {
			$admin_bar_groups = 1;
		}

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/site-metabox-admin-bar.php';

	}

	/**
	 * Render Post Type Options meta box on Admin screen.
	 *
	 * @since 0.8.1
	 */
	public function meta_box_post_types_render() {

		// Get CPTs with admin UI.
		$args = [
			'public'  => true,
			'show_ui' => true,
		];

		$output   = 'objects'; // Names or objects, note names is the default.
		$operator = 'and'; // Operator may be 'and' or 'or'.

		// Get post types.
		$post_types = get_post_types( $args, $output, $operator );

		// Init outputs.
		$output  = [];
		$options = '';

		// Get chosen post types.
		if ( empty( $selected_types ) ) {
			$selected_types = $this->setting_get( 'post_types', [] );
		}

		// Sanity check.
		if ( count( $post_types ) > 0 ) {

			foreach ( $post_types as $post_type ) {

				// Filter only those which have an editor.
				if ( post_type_supports( $post_type->name, 'editor' ) ) {

					$checked = '';
					if ( in_array( $post_type->name, $selected_types, true ) ) {
						$checked = ' checked="checked"';
					}

					// Add checkbox.
					$output[] = '<p><input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_post_types[]" value="' . esc_attr( $post_type->name ) . '"' . $checked . ' /> <label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_post_types">' . esc_html( $post_type->labels->singular_name ) . ' (' . esc_html( $post_type->name ) . ')</label></p>';

				}

			}

			// Implode.
			$options = implode( "\n", $output );

		}

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/site-metabox-post-types.php';

	}

	/**
	 * Render Other Fixes meta box on Admin screen.
	 *
	 * @since 1.0.1
	 */
	public function meta_box_fixes_render() {

		// Init fix API timezone checkbox.
		$fix_api_timezone = 0;
		if ( $this->setting_get( 'fix_api_timezone', '0' ) === '1' ) {
			$fix_api_timezone = 1;
		}

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/site-metabox-fixes.php';

	}

	// -------------------------------------------------------------------------

	/**
	 * Show our network settings page.
	 *
	 * @since 0.5.4
	 */
	public function page_network_settings() {

		// Disallow if not network admin in Multisite.
		if ( is_network_admin() && ! is_super_admin() ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wp_die( __( 'You do not have permission to access this page.', 'civicrm-admin-utilities' ) );
		}

		// Check user permissions.
		if ( ! current_user_can( 'manage_network_plugins' ) ) {
			return;
		}

		// Get admin page URLs.
		$urls = $this->page_get_network_urls();

		/**
		 * Do not show tabs by default but allow overrides.
		 *
		 * @since 0.6.2
		 *
		 * @param bool False by default - do not show tabs.
		 */
		$show_tabs = apply_filters( 'civicrm_admin_utilities_network_show_tabs', false );

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
		do_action( 'cau/network/settings/add_meta_boxes', $screen->id, null );

		// Grab columns.
		$columns = ( 1 === (int) $screen->get_columns() ? '1' : '2' );

		// Include template.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/network-settings.php';

	}

	/**
	 * Enqueue Javascript on the Network Admin Settings page.
	 *
	 * @since 0.5.4
	 */
	public function page_network_settings_js() {

		// Add Javascript plus dependencies.
		wp_enqueue_script(
			'civicrm_admin_utilities_network_settings_js',
			plugins_url( 'assets/js/civicrm-admin-utilities-network-settings.js', CIVICRM_ADMIN_UTILITIES_FILE ),
			[ 'jquery' ],
			CIVICRM_ADMIN_UTILITIES_VERSION, // Version.
			true
		);

	}

	// -------------------------------------------------------------------------

	/**
	 * Show our "Site Settings" page.
	 *
	 * @since 0.8.1
	 */
	public function page_network_site_settings() {

		// Disallow if not network admin in Multisite.
		if ( is_network_admin() && ! is_super_admin() ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wp_die( __( 'You do not have permission to access this page.', 'civicrm-admin-utilities' ) );
		}

		// Check user permissions.
		if ( ! current_user_can( 'manage_network_plugins' ) ) {
			return;
		}

		// Get admin page URLs.
		$urls = $this->page_get_network_urls();

		/**
		 * Do not show tabs by default but allow overrides.
		 *
		 * @since 0.6.2
		 *
		 * @param bool False by default - do not show tabs.
		 */
		$show_tabs = apply_filters( 'civicrm_admin_utilities_network_show_tabs', false );

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
		do_action( 'cau/network/settings/site/add_meta_boxes', $screen->id, null );

		// Grab columns.
		$columns = ( 1 === (int) $screen->get_columns() ? '1' : '2' );

		// Include template.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/network-settings-site.php';

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
		$this->network_urls = [];

		// Get admin page URLs via our adapted method.
		$this->network_urls['settings_network'] = $this->network_menu_page_url( 'cau_network_settings', false );
		$this->network_urls['settings_site']    = $this->network_menu_page_url( 'cau_network_site', false );

		/**
		 * Filter the list of network URLs.
		 *
		 * @since 0.5.4
		 *
		 * @param array $urls The existing list of network URLs.
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
	public function page_settings_cap( $capability ) {

		// Assign network admin capability if we are restricting access.
		if ( $this->setting_get( 'restrict_settings_access', '0' ) === '1' ) {
			$capability = 'manage_network_plugins';
		}

		// --<
		return $capability;

	}

	/**
	 * Maybe filter restrict-to-main-site template variable.
	 *
	 * @since 0.6.8
	 *
	 * @param bool $restricted True if CiviCRM is restricted to main site only, false otherwise.
	 * @return bool $restricted The modified value.
	 */
	public function page_settings_restricted( $restricted ) {

		// Pass on restricted setting as boolean.
		if ( $this->setting_get( 'main_site_only', '0' ) === '1' ) {
			$restricted = true;
		} else {
			$restricted = false;
		}

		// But always show on main site.
		if ( is_main_site() ) {
			$restricted = false;
		}

		// --<
		return $restricted;

	}

	/**
	 * Maybe restrict access to site domain pages.
	 *
	 * @since 0.5.4
	 *
	 * @param str $capability The existing access capability.
	 * @return str $capability The modified access capability.
	 */
	public function page_domain_cap( $capability ) {

		// Assign network admin capability if we are restricting access.
		if ( $this->setting_get( 'restrict_domain_access', '0' ) === '1' ) {
			$capability = 'manage_network_plugins';
		}

		// --<
		return $capability;

	}

	/**
	 * Get the URL for the form action.
	 *
	 * @since 0.5.4
	 *
	 * @param string $slug The slug of the menu page.
	 * @return string $target_url The URL for the admin form action.
	 */
	public function page_submit_url_get( $slug = 'cau_network_settings' ) {

		// Sanitise admin page URL.
		$target_url = $this->network_menu_page_url( $slug, false );

		// --<
		return $target_url;

	}

	// -------------------------------------------------------------------------

	/**
	 * Do not load CiviCRM on sites other than the main site.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class.
	 */
	public function civicrm_on_main_site_only() {

		// Bail if disabled.
		if ( $this->setting_get( 'main_site_only', '0' ) === '0' ) {
			return;
		}

		// If not on main site.
		if ( ! is_main_site() ) {

			// Hide the CiviCRM UI elements.
			$this->plugin->single->hide_civicrm_ui();

			// Remove CiviCRM shortcode button.
			add_action( 'admin_head', [ $this->plugin->single, 'civi_button_remove' ] );

			// Remove Shortcuts Menu from WordPress admin bar.
			remove_action( 'admin_bar_menu', [ $this->plugin->single, 'shortcuts_menu_add' ], 2000 );

			// Remove this plugin's menu item.
			remove_action( 'admin_menu', [ $this->plugin->single, 'admin_menu' ] );

		}

	}

	// -------------------------------------------------------------------------

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

	// -------------------------------------------------------------------------

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
	 * @param str  $permission The requested permission.
	 * @param bool $granted True if permission granted, false otherwise.
	 */
	public function permission_check( $permission, &$granted ) {

		// Only check "administer CiviCRM".
		if ( strtolower( $permission ) !== 'administer civicrm' ) {
			return;
		}

		// Bail if we're not restricting.
		if ( $this->setting_get( 'restrict_administer', '0' ) === '0' ) {
			return;
		}

		// Get current user.
		$user = wp_get_current_user();

		// Sanity check.
		if ( ! ( $user instanceof WP_User ) ) {
			return;
		}

		// Always allow network admins.
		if ( $user->has_cap( 'manage_network_plugins' ) ) {
			$granted = 1;
			return;
		}

		// Disallow everyone else.
		$granted = 0;

	}

	/**
	 * Filter the capability for viewing the CiviCRM Settings and Integration pages.
	 *
	 * @since 0.9.1
	 *
	 * @param str $capability The default access capability.
	 * @return str $capability The modified access capability.
	 */
	public function permission_check_subpages( $capability ) {

		// Bail if we're not restricting.
		if ( $this->setting_get( 'restrict_administer', '0' ) === '0' ) {
			return $capability;
		}

		// Get current user.
		$user = wp_get_current_user();

		// Sanity check.
		if ( ! ( $user instanceof WP_User ) ) {
			return;
		}

		// Always allow network admins.
		if ( $user->has_cap( 'manage_network_plugins' ) ) {
			return $capability;
		}

		// Disallow everyone else.
		return 'manage_network_plugins';

	}

	/**
	 * Check if Site Admins have access to the "Plugins" menu on a Site.
	 *
	 * This is determined by the checkbox at the foot of the "Network Settings"
	 * page in WordPress network admin.
	 *
	 * @since 0.5.4
	 *
	 * @return bool $granted True if permission granted, false otherwise.
	 */
	public function site_admin_has_plugins_menu_access() {

		// Init return.
		$granted = false;

		// Override if individual Site Admins can see the "Plugins" menu.
		$menu_perms = get_site_option( 'menu_items', [] );
		if ( ! empty( $menu_perms['plugins'] ) ) {
			$granted = true;
		}

		// --<
		return $granted;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get default network settings values for this plugin.
	 *
	 * In a Multisite context, these defaults are used for both network defaults
	 * and individual sites.
	 *
	 * @since 0.5.4
	 *
	 * @return array $settings The default values for this plugin.
	 */
	public function settings_get_defaults() {

		// Init return.
		$settings = [];

		// Do not restrict to main site only.
		$settings['main_site_only'] = '0';

		// Allow site admins access to site settings page.
		$settings['restrict_settings_access'] = '0';

		// Do not allow site admins access to site domain page.
		$settings['restrict_domain_access'] = '1';

		// Allow site admins to administer CiviCRM.
		$settings['restrict_administer'] = '0';

		// Hide CiviCRM.
		$settings['hide_civicrm'] = '0';

		// Do not alter Dashboard Title by default to keep existing behaviour.
		$settings['dashboard_title'] = '0';

		// Prettify menu.
		$settings['prettify_menu'] = '1';

		// Override default CiviCRM CSS in wp-admin.
		$settings['css_admin'] = '0'; // Do not override by default.

		// Restrict CSS files from front-end.
		$settings['css_default']       = '0'; // Load default.
		$settings['css_navigation']    = '1'; // Do not load CiviCRM menu.
		$settings['css_custom']        = '0'; // Load Custom Stylesheet on front-end.
		$settings['css_custom_public'] = '0'; // Load Custom Stylesheet on admin.
		$settings['css_shoreditch']    = '0'; // Load Shoreditch.
		$settings['css_bootstrap']     = '0'; // Load Shoreditch Bootstrap.

		// Suppress notification email.
		$settings['email_suppress'] = '0'; // Do not suppress by default.

		// Do not fix Contact Soft Delete by default to keep existing behaviour.
		$settings['fix_soft_delete'] = '0';

		// Add menu to admin bar.
		$settings['admin_bar'] = '1';

		// Do not hide "Manage Groups" menu item from Shortcuts Menu.
		$settings['admin_bar_groups'] = '0';

		// Init post types with defaults.
		$settings['post_types'] = [ 'post', 'page' ];

		// Fix API timezone by default.
		$settings['fix_api_timezone'] = '1';

		/**
		 * Filter default network settings.
		 *
		 * @since 0.5.4
		 *
		 * @param array $settings The array of default network settings.
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
			foreach ( $settings as $key => $setting ) {
				if ( ! empty( $network_settings[ $key ] ) ) {
					$settings[ $key ] = $network_settings[ $key ];
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
	 */
	public function settings_update_router() {

		// Was the "Network Settings" form submitted?
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['cau_network_settings_submit'] ) ) {
			$this->settings_network_update();
			$this->settings_update_redirect( 'cau_network_settings' );
		}

		// Was the "Site Settings" form submitted?
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['cau_network_site_submit'] ) ) {
			$this->settings_site_update();
			$this->settings_update_redirect( 'cau_network_site' );
		}

	}

	/**
	 * Form redirection handler.
	 *
	 * @since 1.0.1
	 *
	 * @param string $slug The slug of the menu page.
	 */
	public function settings_update_redirect( $slug = 'cau_network_settings' ) {

		// Get the Site Settings Page URL.
		$url = $this->page_submit_url_get( $slug );

		// Our array of arguments.
		$args = [ 'updated' => 'true' ];

		// Redirect to our Settings Page.
		wp_safe_redirect( add_query_arg( $args, $url ) );
		exit;

	}

	/**
	 * Update options supplied by our "Network Settings" admin page.
	 *
	 * @since 0.5.4
	 */
	public function settings_network_update() {

		// Check that we trust the source of the data.
		check_admin_referer( 'cau_network_settings_action', 'cau_network_settings_nonce' );

		// Retrieve variables from POST.
		$prefix          = 'civicrm_admin_utilities_';
		$main_site       = isset( $_POST[ $prefix . 'main_site' ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $prefix . 'main_site' ] ) ) : 0;
		$settings_access = isset( $_POST[ $prefix . 'restrict_settings_access' ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $prefix . 'restrict_settings_access' ] ) ) : 0;
		$domain_access   = isset( $_POST[ $prefix . 'restrict_domain_access' ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $prefix . 'restrict_domain_access' ] ) ) : 0;
		$administer      = isset( $_POST[ $prefix . 'restrict_administer' ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $prefix . 'restrict_administer' ] ) ) : 0;

		// Should we remove the visible traces of CiviCRM on sub-sites?
		if ( 1 === $main_site ) {
			$this->setting_set( 'main_site_only', '1' );
		} else {
			$this->setting_set( 'main_site_only', '0' );
		}

		// Should we restrict access to site settings pages?
		if ( 1 === $settings_access ) {
			$this->setting_set( 'restrict_settings_access', '1' );
		} else {
			$this->setting_set( 'restrict_settings_access', '0' );
		}

		// Should we restrict access to site domain pages?
		if ( 1 === $domain_access ) {
			$this->setting_set( 'restrict_domain_access', '1' );
		} else {
			$this->setting_set( 'restrict_domain_access', '0' );
		}

		// Should we restrict administer CiviCRM capability?
		if ( 1 === $administer ) {
			$this->setting_set( 'restrict_administer', '1' );
		} else {
			$this->setting_set( 'restrict_administer', '0' );
		}

		// Save options.
		$this->settings_save();

		/**
		 * Broadcast that the Network Settings update process is finished.
		 *
		 * @since 0.8.1
		 */
		do_action( 'civicrm_admin_utilities_settings_network_updated' );

	}

	/**
	 * Update options supplied by our "Site Settings" admin page.
	 *
	 * @since 0.5.4
	 */
	public function settings_site_update() {

		// Check that we trust the source of the data.
		check_admin_referer( 'cau_network_site_action', 'cau_network_site_nonce' );

		// Retrieve variables from POST.
		$prefix               = 'civicrm_admin_utilities_';
		$hide_civicrm         = isset( $_POST[ $prefix . 'hide_civicrm' ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $prefix . 'hide_civicrm' ] ) ) : 0;
		$dashboard_title      = isset( $_POST[ $prefix . 'dashboard_title' ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $prefix . 'dashboard_title' ] ) ) : 0;
		$menu                 = isset( $_POST[ $prefix . 'menu' ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $prefix . 'menu' ] ) ) : 0;
		$styles_admin         = isset( $_POST[ $prefix . 'styles_admin' ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $prefix . 'styles_admin' ] ) ) : 0;
		$styles_default       = isset( $_POST[ $prefix . 'styles_default' ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $prefix . 'styles_default' ] ) ) : 0;
		$styles_nav           = isset( $_POST[ $prefix . 'styles_nav' ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $prefix . 'styles_nav' ] ) ) : 0;
		$styles_custom        = isset( $_POST[ $prefix . 'styles_custom' ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $prefix . 'styles_custom' ] ) ) : 0;
		$styles_custom_public = isset( $_POST[ $prefix . 'styles_custom_public' ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $prefix . 'styles_custom_public' ] ) ) : 0;
		$styles_shoreditch    = isset( $_POST[ $prefix . 'styles_shoreditch' ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $prefix . 'styles_shoreditch' ] ) ) : 0;
		$styles_bootstrap     = isset( $_POST[ $prefix . 'styles_bootstrap' ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $prefix . 'styles_bootstrap' ] ) ) : 0;
		$email_suppress       = isset( $_POST[ $prefix . 'email_suppress' ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $prefix . 'email_suppress' ] ) ) : 0;
		$fix_soft_delete      = isset( $_POST[ $prefix . 'fix_soft_delete' ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $prefix . 'fix_soft_delete' ] ) ) : 0;
		$admin_bar            = isset( $_POST[ $prefix . 'admin_bar' ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $prefix . 'admin_bar' ] ) ) : 0;
		$admin_bar_groups     = isset( $_POST[ $prefix . 'admin_bar_groups' ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $prefix . 'admin_bar_groups' ] ) ) : 0;
		$fix_api_timezone     = isset( $_POST[ $prefix . 'fix_api_timezone' ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $prefix . 'fix_api_timezone' ] ) ) : 0;

		// Retrieve Post Types array.
		$post_types = filter_input( INPUT_POST, $prefix . 'post_types', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( empty( $post_types ) ) {
			$post_types = [];
		}

		// Did we ask to hide CiviCRM?
		if ( 1 === $hide_civicrm ) {
			$this->setting_set( 'hide_civicrm', '1' );
		} else {
			$this->setting_set( 'hide_civicrm', '0' );
		}

		// Did we ask to prettify Dashboard Title?
		if ( 1 === $dashboard_title ) {
			$this->setting_set( 'dashboard_title', '1' );
		} else {
			$this->setting_set( 'dashboard_title', '0' );
		}

		// Did we ask to prettify the menu?
		if ( 1 === $menu ) {
			$this->setting_set( 'prettify_menu', '1' );
		} else {
			$this->setting_set( 'prettify_menu', '0' );
		}

		// Did we ask to override CiviCRM Default styleheet?
		if ( 1 === $styles_admin ) {
			$this->setting_set( 'css_admin', '1' );
		} else {
			$this->setting_set( 'css_admin', '0' );
		}

		// Did we ask to prevent default styleheet?
		if ( 1 === $styles_default ) {
			$this->setting_set( 'css_default', '1' );
		} else {
			$this->setting_set( 'css_default', '0' );
		}

		// Did we ask to prevent navigation styleheet?
		if ( 1 === $styles_nav ) {
			$this->setting_set( 'css_navigation', '1' );
		} else {
			$this->setting_set( 'css_navigation', '0' );
		}

		// Did we ask to prevent CiviCRM custom styleheet from front-end?
		if ( 1 === $styles_custom ) {
			$this->setting_set( 'css_custom', '1' );
		} else {
			$this->setting_set( 'css_custom', '0' );
		}

		// Did we ask to prevent CiviCRM custom styleheet from admin?
		if ( 1 === $styles_custom_public ) {
			$this->setting_set( 'css_custom_public', '1' );
		} else {
			$this->setting_set( 'css_custom_public', '0' );
		}

		// Did we ask to prevent Shoreditch styleheet?
		if ( 1 === $styles_shoreditch ) {
			$this->setting_set( 'css_shoreditch', '1' );
		} else {
			$this->setting_set( 'css_shoreditch', '0' );
		}

		// Did we ask to prevent Shoreditch Bootstrap styleheet?
		if ( 1 === $styles_bootstrap ) {
			$this->setting_set( 'css_bootstrap', '1' );
		} else {
			$this->setting_set( 'css_bootstrap', '0' );
		}

		// Did we ask to suppress Notification Emails?
		if ( 1 === $email_suppress ) {
			$this->setting_set( 'email_suppress', '1' );
		} else {
			$this->setting_set( 'email_suppress', '0' );
		}

		// Did we ask to fix Contact Soft Delete?
		if ( 1 === $fix_soft_delete ) {
			$this->setting_set( 'fix_soft_delete', '1' );
		} else {
			$this->setting_set( 'fix_soft_delete', '0' );
		}

		// Did we ask to add the shortcuts menu to the admin bar?
		if ( 1 === $admin_bar ) {
			$this->setting_set( 'admin_bar', '1' );
		} else {
			$this->setting_set( 'admin_bar', '0' );
		}

		// Did we ask to hide the "Manage Groups" menu item from the shortcuts menu?
		if ( 1 === $admin_bar_groups ) {
			$this->setting_set( 'admin_bar_groups', '1' );
		} else {
			$this->setting_set( 'admin_bar_groups', '0' );
		}

		// Which post types are we enabling the CiviCRM button on?
		if ( ! empty( $post_types ) ) {

			// Sanitise array.
			array_walk(
				$post_types,
				function( &$item ) {
					$item = sanitize_text_field( wp_unslash( $item ) );
				}
			);

			// Set option.
			$this->setting_set( 'post_types', $post_types );

		} else {
			$this->setting_set( 'post_types', [] );
		}

		// Did we ask to fix API timezone?
		if ( 1 === $fix_api_timezone ) {
			$this->setting_set( 'fix_api_timezone', '1' );
		} else {
			$this->setting_set( 'fix_api_timezone', '0' );
		}

		// Save options.
		$this->settings_save();

		/**
		 * Broadcast that the Site Settings update process is finished.
		 *
		 * @since 0.8.1
		 */
		do_action( 'civicrm_admin_utilities_settings_site_updated' );

	}

	// -------------------------------------------------------------------------

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
	public function setting_exists( $setting_name ) {

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
	 * @param mixed  $default The default value if the setting does not exist.
	 * @return mixed The setting or the default.
	 */
	public function setting_get( $setting_name, $default = false ) {

		// Get setting.
		return ( array_key_exists( $setting_name, $this->settings ) ) ? $this->settings[ $setting_name ] : $default;

	}

	/**
	 * Sets a value for a specified setting.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class and made network-specific.
	 *
	 * @param string $setting_name The name of the setting.
	 * @param mixed  $value The value of the setting.
	 */
	public function setting_set( $setting_name, $value = '' ) {

		// Set setting.
		$this->settings[ $setting_name ] = $value;

	}

	/**
	 * Deletes a specified setting.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class and made network-specific.
	 *
	 * @param string $setting_name The name of the setting.
	 */
	public function setting_delete( $setting_name ) {

		// Unset setting.
		unset( $this->settings[ $setting_name ] );

	}

	// -------------------------------------------------------------------------

	/**
	 * Test existence of a specified network option.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class and made network-specific.
	 *
	 * @param str $option_name The name of the option.
	 * @return bool $exists Whether or not the option exists.
	 */
	public function option_exists( $option_name ) {

		// Test by getting option with unlikely default.
		if ( $this->option_get( $option_name, 'fenfgehgefdfdjgrkj' ) === 'fenfgehgefdfdjgrkj' ) {
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
	public function option_get( $option_name, $default = false ) {

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
	 * @param str   $option_name The name of the option.
	 * @param mixed $value The value to set the option to.
	 * @return bool $success True if the value of the option was successfully updated.
	 */
	public function option_set( $option_name, $value = '' ) {

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
	public function option_delete( $option_name ) {

		// Delete network option.
		return delete_site_option( $option_name );

	}

}
