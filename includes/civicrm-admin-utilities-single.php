<?php

/**
 * CiviCRM Admin Utilities Single Site Class.
 *
 * A class that encapsulates Single Site admin functionality.
 *
 * @since 0.5.4
 */
class CiviCRM_Admin_Utilities_Single {

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
	 * Parent page reference.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $parent_page The reference to the parent page.
	 */
	public $parent_page;

	/**
	 * Settings page reference.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $settings_page The reference to the settings page.
	 */
	public $settings_page;

	/**
	 * Settings data.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $settings The plugin settings data.
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
	 * @since 0.5.4 Moved from admin class.
	 */
	public function store_version() {

		// Store version.
		$this->option_set( 'civicrm_admin_utilities_version', CIVICRM_ADMIN_UTILITIES_VERSION );

	}



	/**
	 * Utility to do stuff when an upgrade is required.
	 *
	 * @since 0.3.4
	 * @since 0.5.4 Moved from admin class and made site-specific.
	 */
	public function upgrade_tasks() {

		// If this is a new install (or an upgrade from a version prior to 0.3.4).
		if ( $this->plugin_version === false ) {

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
	 * @since 0.5.4 Moved from admin class and made site-specific.
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
	 * @since 0.5.4 Moved from admin class and made site-specific.
	 */
	public function upgrade_settings() {

		// Don't save by default.
		$save = false;

		// Hide CiviCRM setting may not exist.
		if ( ! $this->setting_exists( 'hide_civicrm' ) ) {

			// Add it from defaults.
			$settings = $this->settings_get_defaults();
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

		// Add admin page to Settings menu.
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		// Kill CiviCRM shortcode button.
		add_action( 'admin_head', array( $this, 'kill_civi_button' ) );

		// Register template directory for menu amends.
		add_action( 'civicrm_config', array( $this, 'register_menu_directory' ), 10, 1 );

		// Style tweaks for CiviCRM.
		add_action( 'admin_print_styles', array( $this, 'admin_scripts_enqueue' ) );

		// Add Shortcuts Menu to WordPress admin bar.
		add_action( 'admin_bar_menu', array( $this, 'shortcuts_menu_add' ), 2000 );

		// Filter the WordPress Permissions Form.
		add_action( 'civicrm_config', array( $this, 'register_access_directory' ), 10, 1 );
		add_action( 'civicrm_buildForm', array( $this, 'fix_permissions_form' ), 10, 2 );

		// Hook in just before CiviCRM does to disable resources.
		add_action( 'admin_head', array( $this, 'resources_disable' ), 9 );
		add_action( 'wp_head', array( $this, 'resources_disable' ), 9 );

		// Add contact link to the 'user-edit.php' page.
		add_action( 'personal_options', array( $this, 'profile_extras' ) );

		// Add contact link to User listings.
		add_filter( 'user_row_actions', array( $this, 'user_actions' ), 9, 2 );

		// Intercept email updates in CiviCRM.
		add_action( 'civicrm_pre', array( $this, 'email_pre_update' ), 10, 4 );

		// Maybe suppress notification emails.
		add_filter( 'send_email_change_email', array( $this, 'email_suppress' ), 10, 3 );

		// Hook in after the CiviCRM menu hook has been registered.
		add_action( 'init', array( $this, 'hide_civicrm' ), 20 );

		// Listen for when a Contact is about to be moved in or out of Trash.
		add_action( 'civicrm_pre', array( $this, 'contact_soft_delete_pre' ), 10, 4 );

		// Listen for when a Contact has been moved in or out of Trash.
		add_action( 'civicrm_post', array( $this, 'contact_soft_delete_post' ), 10, 4 );

		// If the debugging flag is set.
		if ( CIVICRM_ADMIN_UTILITIES_DEBUG === true ) {

			// Log pre and post database operations.
			add_action( 'civicrm_pre', array( $this, 'trace_pre' ), 10, 4 );
			add_action( 'civicrm_post', array( $this, 'trace_post' ), 10, 4 );
			add_action( 'civicrm_postProcess', array( $this, 'trace_postProcess' ), 10, 2 );

		}

	}



	//##########################################################################



	/**
	 * Add link to CiviCRM Contact on the Users screen.
	 *
	 * @since 0.6.8
	 *
	 * @param str $actions The existing actions to display for this user row.
	 * @param WP_User $user The user object displayed in this row.
	 * @return str $actions The modified actions to display for this user row.
	 */
	public function user_actions( $actions, $user ) {

		// Bail if we can't edit this user.
		if ( ! current_user_can( 'edit_user', $user->ID ) ) return $actions;

		// Bail if user cannot access CiviCRM.
		if ( ! current_user_can( 'access_civicrm' ) ) return $actions;

		// Perform further checks if we can't view all contacts.
		if ( ! $this->check_permission( 'view all contacts' ) ) {

			//  Get current user.
			$current_user = wp_get_current_user();

			// Is this their profile?
			if ( $user->ID === $current_user->ID ) {

				// Bail if they can't view their own contact.
				if ( ! $this->check_permission( 'view my contact' ) ) return $actions;

			} else {

				// Not allowed.
				return $actions;

			}

		}

		// Get contact ID.
		$contact_id = $this->plugin->ufmatch->contact_id_get_by_user_id( $user->ID );

		// Bail if we don't get one for some reason.
		if ( $contact_id === false ) return $actions;

		// Get the link to the Contact.
		$link = $this->get_link( 'civicrm/contact/view', 'reset=1&cid=' . $contact_id );

		// Add link to actions.
		$actions['civicrm'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $link ),
			esc_html__( 'CiviCRM', 'civicrm-admin-utilities' )
		);

		// --<
		return $actions;

	}



	/**
	 * Add link to CiviCRM Contact on User Edit screen.
	 *
	 * @since 0.6.3
	 *
	 * @param object $user The displayed WordPress user object.
	 */
	public function profile_extras( $user ) {

		// Bail if we can't edit this user.
		if ( ! current_user_can( 'edit_user', $user->ID ) ) return;

		// Bail if user cannot access CiviCRM.
		if ( ! current_user_can( 'access_civicrm' ) ) return;

		// Perform further checks if we can't view all contacts.
		if ( ! $this->check_permission( 'view all contacts' ) ) {

			//  Get current user.
			$current_user = wp_get_current_user();

			// Is this their profile?
			if ( $user->ID === $current_user->ID ) {

				// Bail if they can't view their own contact.
				if ( ! $this->check_permission( 'view my contact' ) ) return;

			} else {

				// Not allowed.
				return;

			}

		}

		// Get contact ID.
		$contact_id = $this->plugin->ufmatch->contact_id_get_by_user_id( $user->ID );

		// Bail if we don't get one for some reason.
		if ( $contact_id === false ) return;

		// Get the link to the Contact.
		$link = $this->get_link( 'civicrm/contact/view', 'reset=1&cid=' . $contact_id );

		// Include template.
		include( CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/user-edit.php' );

	}



	/**
	 * Set property when a CiviCRM contact's primary email address is updated.
	 *
	 * @since 0.6.5
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function email_pre_update( $op, $objectName, $objectId, $objectRef ) {

		// Target our operation.
		if ( $op != 'edit' ) return;

		// Target our object type.
		if ( $objectName != 'Email' ) return;

		// Bail if we have no email.
		if ( ! isset( $objectRef['email'] ) ) return;

		// Set a property to check in `email_suppress()` below.
		$this->email_sync = true;

	}



	/**
	 * Suppress notification email when WordPress user email changes.
	 *
	 * @since 0.6.5
	 *
	 * @param bool $send Whether to send the email.
	 * @param array $user The original user array.
	 * @param array $userdata The updated user array.
	 */
	public function email_suppress( $send, $user, $userdata ) {

		// Bail if email suppression is not enabled.
		if ( $this->setting_get( 'email_suppress', '0' ) == '0' ) return $send;

		// Did this change originate with CiviCRM?
		if ( isset( $this->email_sync ) AND $this->email_sync === true ) {

			// Unset property.
			unset( $this->email_sync );

			// Do not notify.
			$send = false;

		}

		// --<
		return $send;

	}



	//##########################################################################



	/**
	 * Maybe hide CiviCRM on this site.
	 *
	 * @since 0.6.8
	 */
	public function hide_civicrm() {

		// Bail if not multisite.
		if ( ! is_multisite() ) return;

		// Bail if disabled.
		if ( $this->setting_get( 'hide_civicrm', '0' ) == '0' ) return;

		// Unhook CiviCRM's menu item, but allow CiviCRM to load.
		remove_action( 'admin_menu', array( civi_wp(), 'add_menu_items' ) );

		// Remove notice.
		remove_action( 'admin_notices', array( civi_wp(), 'show_setup_warning' ) );

		// Remove CiviCRM shortcode button.
		add_action( 'admin_head', array( $this, 'civi_button_remove' ) );

		// Remove Shortcuts Menu from WordPress admin bar.
		remove_action( 'admin_bar_menu', array( $this, 'shortcuts_menu_add' ), 2000 );

	}



	//##########################################################################



	/**
	 * Add an admin menu item(s) for this plugin.
	 *
	 * @since 0.5.4
	 */
	public function admin_menu() {

		/**
		 * Set capability but allow overrides.
		 *
		 * @since 0.5.4
		 *
		 * @param str The default capability for access to settings page.
		 * @return str The modified capability for access to settings page.
		 */
		$capability = apply_filters( 'civicrm_admin_utilities_page_settings_cap', 'manage_options' );

		// Check user permissions.
		if ( ! current_user_can( $capability ) ) return;

		// Add the admin page to the Settings menu.
		$this->parent_page = add_options_page(
			__( 'CiviCRM Admin Utilities: Settings', 'civicrm-admin-utilities' ),
			__( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ),
			$capability,
			'civicrm_admin_utilities_parent',
			array( $this, 'page_settings' )
		);

		// Add help text.
		add_action( 'admin_head-' . $this->parent_page, array( $this, 'admin_head' ), 50 );

		// Add scripts and styles.
		add_action( 'admin_print_styles-' . $this->parent_page, array( $this, 'admin_css' ) );
		add_action( 'admin_print_scripts-' . $this->parent_page, array( $this, 'admin_js' ) );

		// Add settings page
		$this->settings_page = add_submenu_page(
			'civicrm_admin_utilities_parent', // Parent slug.
			__( 'CiviCRM Admin Utilities: Settings', 'civicrm-admin-utilities' ), // Page title.
			__( 'Settings', 'civicrm-admin-utilities' ), // Menu title.
			$capability, // Required caps.
			'civicrm_admin_utilities_settings', // Slug name.
			array( $this, 'page_settings' ) // Callback.
		);

		// Ensure correct menu item is highlighted.
		add_action( 'admin_head-' . $this->settings_page, array( $this, 'admin_menu_highlight' ), 50 );

		// Add help text.
		add_action( 'admin_head-' . $this->settings_page, array( $this, 'admin_head' ), 50 );

		// Add scripts and styles.
		add_action( 'admin_print_styles-' . $this->settings_page, array( $this, 'admin_css' ) );
		add_action( 'admin_print_scripts-' . $this->settings_page, array( $this, 'admin_js' ) );

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
	public function admin_menu_highlight() {

		global $plugin_page, $submenu_file;

		// Define subpages.
		$subpages = array(
		 	'civicrm_admin_utilities_settings',
		);

		/**
		 * Filter the list of subpages.
		 *
		 * @since 0.5.4
		 *
		 * @param array $subpages The existing list of subpages.
		 * @return array $subpages The modified list of subpages.
		 */
		$subpages = apply_filters( 'civicrm_admin_utilities_subpages', $subpages );

		// This tweaks the Settings subnav menu to show only one menu item.
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
			$this->parent_page,
			$this->settings_page,
		);

		// Kick out if not our screen.
		if ( ! in_array( $screen->id, $pages ) ) return $screen;

		// Add a tab - we can add more later.
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

		// Stub help text, to be developed further.
		$help = '<p>' . __( 'Site Settings: For further information about using CiviCRM Admin Utilities, please refer to the readme.txt file that comes with this plugin.', 'civicrm-admin-utilities' ) . '</p>';

		// --<
		return $help;

	}



	/**
	 * Enqueue stylesheet for this plugin's "Site Settings" page.
	 *
	 * @since 0.7
	 */
	public function admin_css() {

		// Add twentytwenty stylesheet.
		wp_enqueue_style(
			'civicrm_admin_utilities_2020_css',
			plugins_url( 'assets/js/twentytwenty/css/twentytwenty.css', CIVICRM_ADMIN_UTILITIES_FILE ),
			false,
			CIVICRM_ADMIN_UTILITIES_VERSION, // Version.
			'all' // Media.
		);

	}



	/**
	 * Enqueue scripts for this plugin's "Site Settings" page.
	 *
	 * @since 0.7
	 */
	public function admin_js() {

		// Enqueue 2020 move script.
		wp_enqueue_script(
			'civicrm_admin_utilities_2020_move_js',
			plugins_url( 'assets/js/twentytwenty/js/jquery.event.move.js', CIVICRM_ADMIN_UTILITIES_FILE ),
			array( 'jquery' ),
			CIVICRM_ADMIN_UTILITIES_VERSION // Version.
		);

		// Enqueue 2020 script.
		wp_enqueue_script(
			'civicrm_admin_utilities_2020_js',
			plugins_url( 'assets/js/twentytwenty/js/jquery.twentytwenty.js', CIVICRM_ADMIN_UTILITIES_FILE ),
			array( 'civicrm_admin_utilities_2020_move_js' ),
			CIVICRM_ADMIN_UTILITIES_VERSION // Version.
		);

		// Enqueue our "Site Settings" page script.
		wp_enqueue_script(
			'civicrm_admin_utilities_js',
			plugins_url( 'assets/js/civicrm-admin-utilities-site-settings.js', CIVICRM_ADMIN_UTILITIES_FILE ),
			array( 'civicrm_admin_utilities_2020_js' ),
			CIVICRM_ADMIN_UTILITIES_VERSION // Version.
		);

	}



	//##########################################################################



	/**
	 * Show our settings page.
	 *
	 * @since 0.5.4
	 */
	public function page_settings() {

		/**
		 * Set capability but allow overrides.
		 *
		 * @since 0.5.4
		 *
		 * @param str The default capability for access to settings page.
		 * @return str The modified capability for access to settings page.
		 */
		$capability = apply_filters( 'civicrm_admin_utilities_page_settings_cap', 'manage_options' );

		// Check user permissions
		if ( ! current_user_can( $capability ) ) return;

		// Get admin page URLs.
		$urls = $this->page_get_urls();

		/**
		 * Set restricted-to-main-site template variable but allow overrides.
		 *
		 * This variable is set to "restricted" by default so that the relevant
		 * section of the form does not show up when not in multisite.
		 *
		 * @since 0.6.8
		 *
		 * @param bool The default template variable - restricted by default.
		 * @return bool The modified template variable.
		 */
		$restricted = apply_filters( 'civicrm_admin_utilities_page_settings_restricted', true );

		// Init Hide CiviCRM checkbox.
		$hide_civicrm = '';
		if ( $this->setting_get( 'hide_civicrm', '0' ) == '1' ) {
			$hide_civicrm = ' checked="checked"';
		}

		// Init menu CSS checkbox.
		$prettify_menu = '';
		if ( $this->setting_get( 'prettify_menu', '0' ) == '1' ) {
			$prettify_menu = ' checked="checked"';
		}

		// Init admin CSS checkbox and theme preview visibility.
		$admin_css = '';
		$theme_preview = '';
		if ( $this->setting_get( 'css_admin', '0' ) == '1' ) {
			$admin_css = ' checked="checked"';
			$theme_preview = ' display: none;';
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

		// Check if Shoreditch CSS is present.
		if ( $this->shoreditch_is_active() ) {

			// Set flag.
			$shoreditch = true;

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

		} else {

			// Set flag.
			$shoreditch = false;

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

		}

		// Init suppress email checkbox.
		$email_suppress = '';
		if ( $this->setting_get( 'email_suppress', '0' ) == '1' ) {
			$email_suppress = ' checked="checked"';
		}

		// Assume access form has been fixed.
		$access_form_fixed = true;

		// If CiviCRM has not been fixed.
		if ( ! $this->access_form_fixed() ) {

			// Set flag.
			$access_form_fixed = false;

			// Init access form checkbox.
			$prettify_access = '';
			if ( $this->setting_get( 'prettify_access', '0' ) == '1' ) {
				$prettify_access = ' checked="checked"';
			}

		}

		// Init admin bar checkbox.
		$admin_bar = '';
		if ( $this->setting_get( 'admin_bar', '0' ) == '1' ) {
			$admin_bar = ' checked="checked"';
		}

		// Init hide "Manage Groups" admin bar menu item checkbox.
		$admin_bar_groups = '';
		if ( $this->setting_get( 'admin_bar_groups', '0' ) == '1' ) {
			$admin_bar_groups = ' checked="checked"';
		}

		// Init "Fix Soft Delete" checkbox.
		$fix_soft_delete = '';
		if ( $this->setting_get( 'fix_soft_delete', '0' ) == '1' ) {
			$fix_soft_delete = ' checked="checked"';
		}

		// Get post type options.
		$options = $this->post_type_options_get();

		// Init administer CiviCRM flag.
		$administer_civicrm = false;

		// Override if this user can administer CiviCRM.
		if ( $this->check_permission( 'administer CiviCRM' ) ) {
			$administer_civicrm = true;
		}

		/**
		 * Do not show tabs by default but allow overrides.
		 *
		 * @since 0.5.4
		 *
		 * @param bool False by default - do not show tabs.
		 * @return bool Modified flag for whether or not to show tabs.
		 */
		$show_tabs = apply_filters( 'civicrm_admin_utilities_show_tabs', false );

		// Include template.
		include( CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/site-settings.php' );

	}



	/**
	 * Get admin page URLs.
	 *
	 * @since 0.5.4
	 *
	 * @return array $urls The array of admin page URLs.
	 */
	public function page_get_urls() {

		// Only calculate once.
		if ( isset( $this->urls ) ) {
			return $this->urls;
		}

		// Init return.
		$this->urls = array();

		// Get admin page URLs.
		$this->urls['settings'] = menu_page_url( 'civicrm_admin_utilities_settings', false );

		/**
		 * Filter the list of URLs.
		 *
		 * @since 0.5.4
		 *
		 * @param array $urls The existing list of URLs.
		 * @return array $urls The modified list of URLs.
		 */
		$this->urls = apply_filters( 'civicrm_admin_utilities_page_urls', $this->urls );

		// --<
		return $this->urls;

	}



	//##########################################################################



	/**
	 * Get post type options.
	 *
	 * @since 0.1
	 * @since 0.5.4 Return checkboxes as HTML.
	 * @since 0.5.4 Moved from plugin class and made site-specific.
	 *
	 * @param array $selected_types The selected post types.
	 * @return str $options The post type options rendered as checkboxes.
	 */
	public function post_type_options_get( $selected_types = array() ) {

		// Get CPTs with admin UI.
		$args = array(
			'public'   => true,
			'show_ui' => true,
		);

		$output = 'objects'; // Names or objects, note names is the default.
		$operator = 'and'; // Operator may be 'and' or 'or'.

		// Get post types.
		$post_types = get_post_types( $args, $output, $operator );

		// Init outputs.
		$output = array();
		$options = '';

		// Get chosen post types.
		if ( empty( $selected_types ) ) {
			$selected_types = $this->setting_get( 'post_types', array() );
		}

		// Sanity check.
		if ( count( $post_types ) > 0 ) {

			foreach( $post_types AS $post_type ) {

				// Filter only those which have an editor.
				if ( post_type_supports( $post_type->name, 'editor' ) ) {

					$checked = '';
					if ( in_array( $post_type->name, $selected_types ) ) {
						$checked = ' checked="checked"';
					}

					// Add checkbox.
					$output[] = '<p><input type="checkbox" class="settings-checkbox" name="civicrm_admin_utilities_post_types[]" value="' . esc_attr( $post_type->name ) . '"' . $checked . ' /> <label class="civicrm_admin_utilities_settings_label" for="civicrm_admin_utilities_post_types">' . esc_html( $post_type->labels->singular_name ) . ' (' . esc_html( $post_type->name ) . ')</label></p>';

				}

			}

			// Implode.
			$options = implode( "\n", $output );

		}

		// --<
		return $options;

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



	/**
	 * Check if CiviCRM's WordPress Access Control template has been fixed.
	 *
	 * @since 0.3.2
	 * @since 0.5.4 Moved from plugin class.
	 *
	 * @return bool $fixed True if fixed, false otherwise.
	 */
	public function access_form_fixed() {

		// Always true if already fixed in CiviCRM.
		if ( $this->setting_get( 'access_fixed', '0' ) == '1' ) return true;

		// Avoid recalculation.
		if ( isset( $this->fixed ) ) {
			return $this->fixed;
		}

		// Do nothing if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			$this->setting_set( 'access_fixed', '1' );
			$this->settings_save();
			$this->fixed = true;
			return $this->fixed;
		}

		// Init property.
		$this->fixed = false;

		// Get current version.
		$version = CRM_Utils_System::version();

		// Find major version.
		$parts = explode( '.', $version );
		$major_version = $parts[0] . '.' . $parts[1];

		// CiviCRM 4.6 is LTS and may have the fix back-ported at some point.
		if ( version_compare( $major_version, '4.6', '=' ) ) {
			//if ( version_compare( $version, '4.6.38', '>=' ) ) $this->fixed = true;
		} else {
			if ( version_compare( $version, '4.7.30', '>=' ) ) $this->fixed = true;
		}

		// Save setting if fixed.
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
	 * @since 0.5.4 Moved from admin class.
	 */
	public function clear_caches() {

		// Bail if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) return;

		// Access config object.
		$config = CRM_Core_Config::singleton();

		// Clear database cache.
		CRM_Core_Config::clearDBCache();

		// Cleanup the "templates_c" directory.
		$config->cleanup( 1, true );

		// Cleanup the session object.
		$session = CRM_Core_Session::singleton();
		$session->reset( 1 );

	}



	//##########################################################################



	/**
	 * Register directory that CiviCRM searches for the menu template file.
	 *
	 * @since 0.3.2
	 * @since 0.5.4 Moved from plugin class.
	 *
	 * @param object $config The CiviCRM config object.
	 */
	public function register_menu_directory( &$config ) {

		// Bail if disabled.
		if ( $this->setting_get( 'prettify_menu', '0' ) == '0' ) return;

		// Kick out if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) return;

		// Get template instance.
		$template = CRM_Core_Smarty::singleton();

		// Get current version.
		$version = CRM_Utils_System::version();

		// Define our custom path based on CiviCRM version.
		if ( version_compare( $version, '5.5', '>=' ) ) {
			$custom_path = CIVICRM_ADMIN_UTILITIES_PATH . 'civicrm_nav_template';
		} else {
			$custom_path = CIVICRM_ADMIN_UTILITIES_PATH . 'civicrm_custom_templates';
		}

		// Add our custom template directory.
		$template->addTemplateDir( $custom_path );

		// Register template directories.
		$template_include_path = $custom_path . PATH_SEPARATOR . get_include_path();
		set_include_path( $template_include_path );

	}



	/**
	 * Register directory that CiviCRM searches for the WordPress Access Control template file.
	 *
	 * @since 0.3.2
	 * @since 0.5.4 Moved from plugin class.
	 *
	 * @param object $config The CiviCRM config object.
	 */
	public function register_access_directory( &$config ) {

		// Bail if disabled.
		if ( $this->setting_get( 'prettify_access', '0' ) == '0' ) return;

		// Kick out if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) return;

		// Bail if CiviCRM has been fixed.
		if ( $this->access_form_fixed() ) return;

		// Get template instance.
		$template = CRM_Core_Smarty::singleton();

		// Define our custom path.
		$custom_path = CIVICRM_ADMIN_UTILITIES_PATH . 'civicrm_access_templates';

		// Add our custom template directory.
		$template->addTemplateDir( $custom_path );

		// Register template directories.
		$template_include_path = $custom_path . PATH_SEPARATOR . get_include_path();
		set_include_path( $template_include_path );

	}



	/**
	 * Admin style tweaks.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from plugin class.
	 */
	public function admin_scripts_enqueue() {

		// Bail if disabled.
		if ( $this->setting_get( 'prettify_menu', '0' ) == '1' ) {

			// Set default CSS file.
			$css = 'civicrm-admin-utilities-menu.css';

			// Use specific CSS file for KAM if active.
			if ( $this->kam_is_active() ) {
				$css = 'civicrm-admin-utilities-kam.css';
			}

			// Add menu stylesheet.
			wp_enqueue_style(
				'civicrm_admin_utilities_admin_tweaks',
				plugins_url( 'assets/css/' . $css, CIVICRM_ADMIN_UTILITIES_FILE ),
				null,
				CIVICRM_ADMIN_UTILITIES_VERSION, // Version.
				'all' // Media.
			);

		}

		// Use specific CSS file for Shoreditch if active.
		if ( $this->shoreditch_is_active() ) {

			// But not when prettifying CiviCRM admin.
			if ( $this->setting_get( 'css_admin', '0' ) == '0' ) {

				// Add Shoreditch stylesheet.
				wp_enqueue_style(
					'civicrm_admin_utilities_shoreditch_tweaks',
					plugins_url( 'assets/css/civicrm-admin-utilities-shoreditch.css', CIVICRM_ADMIN_UTILITIES_FILE ),
					null,
					CIVICRM_ADMIN_UTILITIES_VERSION, // Version.
					'all' // Media.
				);

			}

		}

		// Maybe load core override stylesheet.
		if ( $this->setting_get( 'css_admin', '0' ) == '1' ) {

			// Add core override stylesheet.
			wp_enqueue_style(
				'civicrm_admin_utilities_admin_override',
				plugins_url( 'assets/css/civicrm-admin-utilities-admin.css', CIVICRM_ADMIN_UTILITIES_FILE ),
				null,
				CIVICRM_ADMIN_UTILITIES_VERSION, // Version.
				'all' // Media.
			);

			// Amend styles when WordPress 5.3+ is detected.
			global $wp_version;
			if ( version_compare( $wp_version, '5.2.99999', '>' ) ) {
				wp_enqueue_style(
					'civicrm_admin_utilities_admin_override_53plus',
					plugins_url( 'assets/css/civicrm-admin-utilities-admin-5-3-plus.css', CIVICRM_ADMIN_UTILITIES_FILE ),
					array( 'civicrm_admin_utilities_admin_override' ),
					CIVICRM_ADMIN_UTILITIES_VERSION, // Version.
					'all' // Media.
				);
			}

			/**
			 * Broadcast that we are loading a custom CiviCRM stylesheet.
			 *
			 * @since 0.4.2
			 */
			do_action( 'civicrm_admin_utilities_admin_overridden' );

		}

	}



	//##########################################################################



	/**
	 * Disable CiviCRM resources from front-end.
	 *
	 * @since 0.4.1
	 * @since 0.5.4 Moved from plugin class.
	 */
	public function resources_disable() {

		// Kick out if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) return;

		// Only on back-end.
		if ( is_admin() ) {

			// Maybe disable core stylesheet.
			if ( $this->setting_get( 'css_admin', '0' ) == '1' ) {

				// Disable core stylesheet.
				$this->resource_disable( 'civicrm', 'css/civicrm.css' );

				// Also disable Shoreditch if present.
				if ( $this->shoreditch_is_active() ) {
					$this->resource_disable( 'org.civicrm.shoreditch', 'css/custom-civicrm.css' );
				}

			}

			// Maybe disable custom stylesheet (not provided by Shoreditch).
			if ( $this->setting_get( 'css_custom_public', '0' ) == '1' ) {
				$this->custom_css_disable();
			}

		// Only on front-end.
		} else {

			// Maybe disable core stylesheet.
			if ( $this->setting_get( 'css_default', '0' ) == '1' ) {
				$this->resource_disable( 'civicrm', 'css/civicrm.css' );
			}

			// Maybe disable navigation stylesheet (there's no menu on the front-end).
			if ( $this->setting_get( 'css_navigation', '0' ) == '1' ) {
				$this->resource_disable( 'civicrm', 'css/civicrmNavigation.css' );
			}

			// If Shoreditch present.
			if ( $this->shoreditch_is_active() ) {

				// Maybe disable Shoreditch stylesheet.
				if ( $this->setting_get( 'css_shoreditch', '0' ) == '1' ) {
					$this->resource_disable( 'org.civicrm.shoreditch', 'css/custom-civicrm.css' );
				}

				// Maybe disable Shoreditch Bootstrap stylesheet.
				if ( $this->setting_get( 'css_bootstrap', '0' ) == '1' ) {
					$this->resource_disable( 'org.civicrm.shoreditch', 'css/bootstrap.css' );
				}

			} else {

				// Maybe disable custom stylesheet (not provided by Shoreditch).
				if ( $this->setting_get( 'css_custom', '0' ) == '1' ) {
					$this->custom_css_disable();
				}

			}

		}

	}



	/**
	 * Disable a resource enqueued by CiviCRM.
	 *
	 * @since 0.4.1
	 * @since 0.5.4 Moved from plugin class.
	 *
	 * @param str $extension The name of the extension e.g. 'org.civicrm.shoreditch'. Default is CiviCRM core.
	 * @param str $file The relative path to the resource. Default is CiviCRM core stylesheet.
	 */
	public function resource_disable( $extension = 'civicrm', $file = 'css/civicrm.css' ) {

		// Kick out if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) return;

		// Get the resource URL.
		$url = $this->resource_get_url( $extension, $file );

		// Kick out if not enqueued.
		if ( $url === false ) return;

		// Set to disabled.
		CRM_Core_Region::instance('html-header')->update( $url, array( 'disabled' => true ) );

	}



	/**
	 * Get the URL of a resource if it is enqueued by CiviCRM.
	 *
	 * @since 0.4.3
	 * @since 0.5.4 Moved from plugin class.
	 *
	 * @param str $extension The name of the extension e.g. 'org.civicrm.shoreditch'. Default is CiviCRM core.
	 * @param str $file The relative path to the resource. Default is CiviCRM core stylesheet.
	 * @return bool|str $url The URL if the resource is enqueued, false otherwise.
	 */
	public function resource_get_url( $extension = 'civicrm', $file = 'css/civicrm.css' ) {

		// Kick out if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) return false;

		// Get registered URL.
		$url = CRM_Core_Resources::singleton()->getUrl( $extension, $file, true );

		// Get registration data from region.
		$registration = CRM_Core_Region::instance( 'html-header' )->get( $url );

		// Bail if not registered.
		if ( empty( $registration ) ) return false;

		// Is enqueued.
		return $url;

	}



	/**
	 * Disable any custom CSS file enqueued by CiviCRM.
	 *
	 * @since 0.4.2
	 * @since 0.5.4 Moved from plugin class.
	 */
	public function custom_css_disable() {

		// Kick out if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) return;

		// Get CiviCRM config.
		$config = CRM_Core_Config::singleton();

		// Bail if there's no custom CSS file.
		if ( empty( $config->customCSSURL ) ) return;

		// Get registered URL.
		$url = CRM_Core_Resources::singleton()->addCacheCode( $config->customCSSURL );

		// Get registration data from region.
		$registration = CRM_Core_Region::instance('html-header')->get( $url );

		// Bail if not registered.
		if ( empty ( $registration ) ) return;

		// Set to disabled.
		CRM_Core_Region::instance('html-header')->update( $url, array( 'disabled' => true ) );

	}



	/**
	 * Determine if the Shoreditch CSS file is being used.
	 *
	 * @since 0.3.4
	 * @since 0.5.4 Moved from plugin class.
	 *
	 * @return bool $shoreditch True if Shoreditch CSS file is used, false otherwise.
	 */
	public function shoreditch_is_active() {

		// Assume not.
		$shoreditch = false;

		// Init CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) return $shoreditch;

		// Get the current Custom CSS URL.
		$config = CRM_Core_Config::singleton();

		// Has the Shoreditch CSS been activated?
		if ( strstr( $config->customCSSURL, 'org.civicrm.shoreditch' ) !== false ) {

			// Shoreditch CSS is active.
			$shoreditch = true;

		}

		// --<
		return $shoreditch;

	}



	/**
	 * Determine if the Keyboard Accessible Menu Extension is being used.
	 *
	 * @since 0.4.3
	 * @since 0.5.4 Moved from plugin class.
	 *
	 * @return bool True if KAM Extension is active, false otherwise.
	 */
	public function kam_is_active() {

		// Kick out if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) return false;

		// Get current version of CiviCRM.
		$civicrm_version = CRM_Utils_System::version();

		// Init parsed version.
		$version = $civicrm_version;

		// We only need the major and minor parts.
		$version_tmp = explode( '.', $civicrm_version );
		if ( isset( $version_tmp[1] ) ) {
			$version = $version_tmp[0] . '.' . $version_tmp[1];
		}

		// KAM is included in core from 5.12 onwards.
		if ( version_compare( $version, '5.12', '>=' ) ) {
			return true;
		}

		// Kick out if no KAM function.
		if ( ! function_exists( 'kam_civicrm_coreResourceList' ) ) {
			return false;
		}

		// KAM must be present.
		return true;

	}



	/**
	 * Do not load the CiviCRM shortcode button unless we explicitly enable it.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from plugin class.
	 */
	public function kill_civi_button() {

		// Get screen
		$screen = get_current_screen();

		// Prevent warning if screen not defined.
		if ( empty( $screen ) ) return;

		// Bail if there's no post type.
		if ( empty( $screen->post_type ) ) return;

		// Get chosen post types.
		$selected_types = $this->setting_get( 'post_types', array() );

		// Remove button if this is not a post type we want to allow the button on.
		if ( ! in_array( $screen->post_type, $selected_types ) ) {
			$this->civi_button_remove();
		}

	}



	/**
	 * Prevent the loading of the CiviCRM shortcode button.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from plugin class.
	 */
	public function civi_button_remove() {

		// Kick out if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) return;

		// Get CiviCRM object.
		$civi = civi_wp();

		// Do we have the modal object?
		if ( isset( $civi->modal ) AND is_object( $civi->modal ) ) {

			// Remove current CiviCRM actions.
			remove_action( 'media_buttons_context', array( $civi->modal, 'add_form_button' ) );
			remove_action( 'media_buttons', array( $civi->modal, 'add_form_button' ), 100 );
			remove_action( 'admin_enqueue_scripts', array( $civi->modal, 'add_form_button_js' ) );
			remove_action( 'admin_footer', array( $civi->modal, 'add_form_button_html' ) );

			// Also remove core resources.
			remove_action( 'admin_head', array( $civi, 'wp_head' ), 50 );
			remove_action( 'load-post.php', array( $civi->modal, 'add_core_resources' ) );
			remove_action( 'load-post-new.php', array( $civi->modal, 'add_core_resources' ) );
			remove_action( 'load-page.php', array( $civi->modal, 'add_core_resources' ) );
			remove_action( 'load-page-new.php', array( $civi->modal, 'add_core_resources' ) );

		} else {

			// Remove legacy CiviCRM actions.
			remove_action( 'media_buttons_context', array( $civi, 'add_form_button' ) );
			remove_action( 'media_buttons', array( $civi, 'add_form_button' ), 100 );
			remove_action( 'admin_enqueue_scripts', array( $civi, 'add_form_button_js' ) );
			remove_action( 'admin_footer', array( $civi, 'add_form_button_html' ) );

		}

	}



	//##########################################################################



	/**
	 * Add a CiviCRM menu to the WordPress admin bar.
	 *
	 * There is some complexity here because some developers enable CiviCRM on
	 * subsites by hacking civicrm.settings.php to return appropriate settings
	 * depending on the domain being requested.
	 *
	 * This is quite valid, but does present a problem for generating this menu
	 * because the default install does not actually work at all on subsites
	 * when network-enabled. Hence the option in this plugin that restricts
	 * CiviCRM to the main site only.
	 *
	 * The compromise made here is to have "before" and "after" actions through
	 * which callbacks can switch to the main site and back again afterwards.
	 *
	 * @see CiviCRM_Admin_Utilities_Multisite::shortcuts_menu_switch_to()
	 * @see CiviCRM_Admin_Utilities_Multisite::shortcuts_menu_switch_back()
	 *
	 * @since 0.3
	 * @since 0.5.4 Moved from plugin class.
	 */
	public function shortcuts_menu_add() {

		// Bail if admin bar not enabled.
		if ( $this->setting_get( 'admin_bar', '0' ) == '0' ) return;

		// Kick out if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) return;

		// Bail if user cannot access CiviCRM.
		if ( ! current_user_can( 'access_civicrm' ) ) return;

		/**
		 * Fires before Shortcuts Menu has been defined.
		 *
		 * @since 0.5.4
		 */
		do_action( 'civicrm_admin_utilities_menu_before' );

		// Access WordPress admin bar.
		global $wp_admin_bar;

		// Init CiviCRM or bail.
		if ( ! $this->plugin->is_civicrm_initialised() ) return;

		// Get component info.
		$components = CRM_Core_Component::getEnabledComponents();

		// Define a menu parent ID.
		$id = 'civicrm-admin-utils';

		// Add parent.
		$wp_admin_bar->add_node( array(
			'id' => $id,
			'title' => __( 'CiviCRM', 'civicrm-admin-utilities' ),
			'href' => admin_url( 'admin.php?page=CiviCRM' ),
		) );

		// Dashboard.
		$wp_admin_bar->add_node( array(
			'id' => 'cau-1',
			'parent' => $id,
			'title' => __( 'CiviCRM Dashboard', 'civicrm-admin-utilities' ),
			'href' => admin_url( 'admin.php?page=CiviCRM' ),
		) );

		// Search.
		$wp_admin_bar->add_node( array(
			'id' => 'cau-2',
			'parent' => $id,
			'title' => __( 'Advanced Search', 'civicrm-admin-utilities' ),
			'href' => $this->get_link( 'civicrm/contact/search/advanced', 'reset=1' ),
		) );

		// Maybe hide "Manage Groups" menu item.
		if ( $this->setting_get( 'admin_bar_groups', '0' ) == '1' ) {
			add_filter( 'civicrm_admin_utilities_manage_groups_menu_item', '__return_false' );
		}

		/**
		 * Allow or deny access to the "Manage Groups" item.
		 *
		 * This now has a setting per site which adds a callback to this filter
		 * at the default priority. See above.
		 *
		 * @see https://github.com/christianwach/civicrm-admin-utilities/issues/8
		 *
		 * @since 0.6.3
		 *
		 * @param bool True allows access by default.
		 * @return bool Modified access flag - return boolean "false" to deny.
		 */
		$allowed = apply_filters( 'civicrm_admin_utilities_manage_groups_menu_item', true );

		// Groups.
		if ( $allowed ) {
			$wp_admin_bar->add_node( array(
				'id' => 'cau-3',
				'parent' => $id,
				'title' => __( 'Manage Groups', 'civicrm-admin-utilities' ),
				'href' => $this->get_link( 'civicrm/group', 'reset=1' ),
			) );
		}

		// Contributions.
		if ( array_key_exists( 'CiviContribute', $components ) ) {
			if ( $this->check_permission( 'access CiviContribute' ) ) {
				$wp_admin_bar->add_node( array(
					'id' => 'cau-4',
					'parent' => $id,
					'title' => __( 'Contribution Dashboard', 'civicrm-admin-utilities' ),
					'href' => $this->get_link( 'civicrm/contribute', 'reset=1' ),
				) );
			}
		}

		// Membership.
		if ( array_key_exists( 'CiviMember', $components ) ) {
			if ( $this->check_permission( 'access CiviMember' ) ) {
				$wp_admin_bar->add_node( array(
					'id' => 'cau-5',
					'parent' => $id,
					'title' => __( 'Membership Dashboard', 'civicrm-admin-utilities' ),
					'href' => $this->get_link( 'civicrm/member', 'reset=1' ),
				) );
			}
		}

		// Events.
		if ( array_key_exists( 'CiviEvent', $components ) ) {
			if ( $this->check_permission( 'access CiviEvent' ) ) {
				$wp_admin_bar->add_node( array(
					'id' => 'cau-6',
					'parent' => $id,
					'title' => __( 'Events Dashboard', 'civicrm-admin-utilities' ),
					'href' => $this->get_link( 'civicrm/event', 'reset=1' ),
				) );
			}
		}

		// Mailings.
		if ( array_key_exists( 'CiviMail', $components ) ) {
			if ( $this->check_permission( 'access CiviMail' ) ) {
				$wp_admin_bar->add_node( array(
					'id' => 'cau-7',
					'parent' => $id,
					'title' => __( 'Mailings Sent and Scheduled', 'civicrm-admin-utilities' ),
					'href' => $this->get_link( 'civicrm/mailing/browse/scheduled', 'reset=1&scheduled=true' ),
				) );
			}
		}

		// Reports.
		if ( array_key_exists( 'CiviReport', $components ) ) {
			if ( $this->check_permission( 'access CiviReport' ) ) {
				$wp_admin_bar->add_node( array(
					'id'     => 'cau-8',
					'parent' => $id,
					'title'  => __( 'Report Listing', 'civicrm-admin-utilities' ),
					'href'   => $this->get_link( 'civicrm/report/list', '&reset=1' ),
				) );
			}
		}

		// Cases.
		if ( array_key_exists( 'CiviCase', $components ) ) {
			if ( CRM_Case_BAO_Case::accessCiviCase() ) {
				$wp_admin_bar->add_node( array(
					'id' => 'cau-9',
					'parent' => $id,
					'title' => __( 'Cases Dashboard', 'civicrm-admin-utilities' ),
					'href' => $this->get_link( 'civicrm/case', 'reset=1' ),
				) );
			}
		}

		// Admin console.
		if ( $this->check_permission( 'administer CiviCRM' ) ) {
			$wp_admin_bar->add_node( array(
				'id' => 'cau-10',
				'parent' => $id,
				'title' => __( 'Admin Console', 'civicrm-admin-utilities' ),
				'href' => $this->get_link( 'civicrm/admin', 'reset=1' ),
			) );
		}

		/**
		 * Set capability but allow overrides.
		 *
		 * @since 0.5.4
		 * @since 0.6.1 Added here to check access to menu item.
		 *
		 * @param str The default capability for access to settings page.
		 * @return str The modified capability for access to settings page.
		 */
		$capability = apply_filters( 'civicrm_admin_utilities_page_settings_cap', 'manage_options' );

		// Admin Utilities settings page.
		if ( current_user_can( $capability ) ) {
			$wp_admin_bar->add_node( array(
				'id' => 'cau-11',
				'parent' => $id,
				'title' => __( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ),
				'href' => admin_url( 'admin.php?page=civicrm_admin_utilities_settings' ),
			) );
		}

		/**
		 * Fires after Shortcuts Menu has been defined.
		 *
		 * @since 0.3
		 */
		do_action( 'civicrm_admin_utilities_menu_after' );

	}



	/**
	 * Get a CiviCRM admin link.
	 *
	 * @since 0.3
	 * @since 0.5.4 Moved from plugin class.
	 *
	 * @param str $path The CiviCRM path.
	 * @param str $params The CiviCRM parameters.
	 * @return string $link The URL of the CiviCRM page.
	 */
	public function get_link( $path = '', $params = null ) {

		// Init link.
		$link = '';

		// Init CiviCRM or bail.
		if ( ! $this->plugin->is_civicrm_initialised() ) return $link;

		// Use CiviCRM to construct link.
		$link = CRM_Utils_System::url(
			$path,
			$params,
			true,
			null,
			true,
			false,
			true
		);

		// --<
		return $link;

	}



	/**
	 * Check a CiviCRM permission.
	 *
	 * @since 0.3
	 * @since 0.5.4 Moved from plugin class.
	 *
	 * @param str $permission The permission string.
	 * @return bool $permitted True if allowed, false otherwise.
	 */
	public function check_permission( $permission ) {

		// Always deny if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) return false;

		// Deny by default.
		$permitted = false;

		// Check CiviCRM permissions.
		if ( CRM_Core_Permission::check( $permission ) ) {
			$permitted = true;
		}

		/**
		 * Return permission but allow overrides.
		 *
		 * @since 0.3
		 *
		 * @param bool $permitted True if allowed, false otherwise.
		 * @param str $permission The CiviCRM permission string.
		 * @return bool $permitted True if allowed, false otherwise.
		 */
		return apply_filters( 'civicrm_admin_utilities_permitted', $permitted, $permission );

	}



	/**
	 * Fixes the WordPress Access Control form by building a single table.
	 *
	 * @since 0.3
	 * @since 0.5.4 Moved from plugin class.
	 *
	 * @param string $formName The name of the form.
	 * @param CRM_Core_Form $form The form object.
	 */
	public function fix_permissions_form( $formName, &$form ) {

		// Bail if disabled.
		if ( $this->setting_get( 'prettify_access', '0' ) == '0' ) return;

		// Bail if CiviCRM has been fixed.
		if ( $this->access_form_fixed() ) return;

		// Bail if not the form we want.
		if ( $formName != 'CRM_ACL_Form_WordPress_Permissions' ) return;

		// Get vars.
		$vars = $form->get_template_vars();

		// Bail if $permDesc does not exist.
		if ( ! isset( $vars['permDesc'] ) ) return;

		// Build replacement for permDesc array.
		foreach( $vars['rolePerms'] AS $role => $perms ) {
			foreach( $perms AS $name => $title ) {
				$permissions[$name] = $title;
			}
		}

		// Build array keyed by permission.
		$table = array();
		foreach( $permissions AS $perm => $label ) {

			// Init row with permission description.
			$table[$perm] = array(
				'label' => $label,
				'roles' => array(),
			);

			// Add permission label and role names.
			foreach( $vars['roles'] AS $key => $label ) {
				if ( isset( $vars['permDesc'][$perm] ) ) {
					$table[$perm]['desc'] = $vars['permDesc'][$perm];
				}
				$table[$perm]['roles'][] = $key;
			}

		}

		// Assign to form.
		$form->assign( 'table', $table );

		// Camelcase dammit.
		CRM_Utils_System::setTitle( __( 'WordPress Access Control', 'civicrm-admin-utilities' ) );

	}



	//##########################################################################



	/**
	 * Before a Contact is updated, establish if they are being moved "to the
	 * Trash" or "from the Trash".
	 *
	 * @since 0.6.8
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function contact_soft_delete_pre( $op, $objectName, $objectId, $objectRef ) {

		// Bail if our conditions are not met.
		if ( $op !== 'update' ) return; // Uh oh! 'update' not 'edit'!
		$contact_types = array( 'Individual', 'Household', 'Organization' );
		if ( ! in_array( $objectName, $contact_types ) ) return;

		// Bail if disabled.
		if ( $this->setting_get( 'fix_soft_delete', '0' ) == '0' ) return;

		// Get the Contact's data.
		$result = civicrm_api( 'Contact', 'get', array(
			'version' => 3,
			'sequential' => 1,
			'id' => $objectId,
		));

		// Log and bail if there's an error.
		if ( ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) OR $result['count'] == 0 ) {
			$e = new Exception;
			$trace = $e->getTraceAsString();
			error_log( print_r( array(
				'method' => __METHOD__,
				'result' => $result,
				'backtrace' => $trace,
			), true ) );
			return;
		}

		// Get the Contact data.
		$contact_data = array_pop( $result['values'] );

		// Init direction with arbitrary value.
		$this->direction = 'none';

		// If the Contact was not in the Trash, then its being moved to Trash.
		if ( isset( $objectRef['is_deleted'] ) AND $objectRef['is_deleted'] == '1' ) {
			if ( $contact_data['contact_is_deleted'] == '0' ) {
				$this->direction = 'trashed';
			}
		}

		// If the Contact was in the Trash, then its being moved out of the Trash.
		if ( ! isset( $objectRef['is_deleted'] ) OR $objectRef['is_deleted'] == '0' ) {
			if ( $contact_data['contact_is_deleted'] == '1' ) {
				$this->direction = 'untrashed';
			}
		}

		// Sanity check.
		if ( $this->direction === 'none' ) return;

		/**
		 * Broadcast that a Contact is about to be moved into or out of the Trash.
		 *
		 * This produces two actions:
		 *
		 * `civicrm_admin_utilities_contact_pre_trashed`
		 * `civicrm_admin_utilities_contact_pre_untrashed`
		 *
		 * @since 0.6.8
		 *
		 * @param array $contact_data The Contact data array.
		 */
		do_action( 'civicrm_admin_utilities_contact_pre_' . $this->direction, $contact_data );

	}



	/**
	 * Act when a Contact has been moved in or out of Trash.
	 *
	 * @since 0.6.8
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function contact_soft_delete_post( $op, $objectName, $objectId, $objectRef ) {

		// Bail if our conditions are not met.
		if ( $op !== 'update' ) return; // Uh oh! 'update' not 'edit'!
		$contact_types = array( 'Individual', 'Household', 'Organization' );
		if ( ! in_array( $objectName, $contact_types ) ) return;

		// Bail if disabled.
		if ( $this->setting_get( 'fix_soft_delete', '0' ) == '0' ) return;

		// Sanity check.
		if ( $this->direction === 'none' ) return;

		/**
		 * Broadcast that a Contact has been moved into or out of the Trash.
		 *
		 * This produces two actions:
		 *
		 * `civicrm_admin_utilities_contact_post_trashed`
		 * `civicrm_admin_utilities_contact_post_untrashed`
		 *
		 * @since 0.6.8
		 *
		 * @param CRM_Contact_DAO_Contact $objectRef The Contact data object.
		 */
		do_action( 'civicrm_admin_utilities_contact_post_' . $this->direction, $objectRef );

	}



	//##########################################################################



	/**
	 * Utility for tracing calls to hook_civicrm_pre.
	 *
	 * @since 0.5.4 Moved from plugin class.
	 *
	 * @param string $op the type of database operation.
	 * @param string $objectName the type of object.
	 * @param integer $objectId the ID of the object.
	 * @param object $objectRef the object.
	 */
	public function trace_pre( $op, $objectName, $objectId, $objectRef ) {

		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
			'backtrace' => $trace,
		), true ) );

	}



	/**
	 * Utility for tracing calls to hook_civicrm_post.
	 *
	 * @since 0.5.4 Moved from plugin class.
	 *
	 * @param string $op the type of database operation.
	 * @param string $objectName the type of object.
	 * @param integer $objectId the ID of the object.
	 * @param object $objectRef the object.
	 */
	public function trace_post( $op, $objectName, $objectId, $objectRef ) {

		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
			'backtrace' => $trace,
		), true ) );

	}



	/**
	 * Utility for tracing calls to hook_civicrm_postProcess.
	 *
	 * @since 0.5.4 Moved from plugin class.
	 *
	 * @param string $formName The name of the form.
	 * @param object $form The form object.
	 */
	public function trace_postProcess( $formName, &$form ) {

		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'formName' => $formName,
			'form' => $form,
			'backtrace' => $trace,
		), true ) );

	}



	//##########################################################################



	/**
	 * Get default settings values for this plugin.
	 *
	 * @since 0.5.4
	 *
	 * @return array $settings The default values for this plugin.
	 */
	public function settings_get_defaults() {

		// Init return.
		$settings = array();

		// Hide CiviCRM.
		$settings['hide_civicrm'] = '0';

		// Prettify menu.
		$settings['prettify_menu'] = '1';

		// Restrict CSS files from front-end.
		$settings['css_default'] = '0'; // Load default.
		$settings['css_navigation'] = '1'; // Do not load CiviCRM menu.
		$settings['css_shoreditch'] = '0'; // Load Shoreditch.
		$settings['css_bootstrap'] = '0'; // Load Shoreditch Bootstrap.
		$settings['css_custom'] = '0'; // Load Custom Stylesheet on front-end.
		$settings['css_custom_public'] = '0'; // Load Custom Stylesheet on admin.

		// Override CiviCRM Default in wp-admin.
		$settings['css_admin'] = '0'; // Load CiviCRM Default Stylesheet.

		// Override default CiviCRM CSS in wp-admin.
		$settings['css_admin'] = '0'; // Do not override by default.

		// Suppress notification email.
		$settings['email_suppress'] = '0'; // Do not suppress by default.

		// Fix WordPress Access Control table.
		$settings['prettify_access'] = '1';

		// Do not assume WordPress Access Control table is fixed.
		$settings['access_fixed'] = '0';

		// Init post types with defaults.
		$settings['post_types'] = array( 'post', 'page' );

		// Add Shortcuts Menu to admin bar.
		$settings['admin_bar'] = '1';

		// Do not hide "Manage Groups" menu item from Shortcuts Menu.
		$settings['admin_bar_groups'] = '0';

		// Do not fix Contact Soft Delete by default to keep existing behaviour.
		$settings['fix_soft_delete'] = '0';

		/**
		 * Filter default settings.
		 *
		 * @since 0.5.4
		 *
		 * @param array $settings The array of default settings.
		 * @return array $settings The modified array of default settings.
		 */
		$settings = apply_filters( 'civicrm_admin_utilities_settings_default', $settings );

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

		// was the "Settings" form submitted?
		if ( isset( $_POST['civicrm_admin_utilities_settings_submit'] ) ) {
			return $this->settings_update();
		}

		// --<
		return $result;

	}



	/**
	 * Update options supplied by our Settings admin page.
	 *
	 * @since 0.5.4
	 * @since 0.5.4 Moved from admin class and made site-specific.
	 *
	 * @return bool True if successful, false otherwise (always true at present).
	 */
	public function settings_update() {

		// Check that we trust the source of the data.
		check_admin_referer( 'civicrm_admin_utilities_settings_action', 'civicrm_admin_utilities_settings_nonce' );

		// Init vars.
		$civicrm_admin_utilities_hide_civicrm = '';
		$civicrm_admin_utilities_menu = '';
		$civicrm_admin_utilities_access = '';
		$civicrm_admin_utilities_post_types = array();
		$civicrm_admin_utilities_cache = '';
		$civicrm_admin_utilities_admin_bar = '';
		$civicrm_admin_utilities_admin_bar_groups = '';
		$civicrm_admin_utilities_fix_soft_delete = '';
		$civicrm_admin_utilities_styles_default = '';
		$civicrm_admin_utilities_styles_nav = '';
		$civicrm_admin_utilities_styles_shoreditch = '';
		$civicrm_admin_utilities_styles_bootstrap = '';
		$civicrm_admin_utilities_styles_custom = '';
		$civicrm_admin_utilities_styles_custom_public = '';
		$civicrm_admin_utilities_styles_admin = '';
		$civicrm_admin_utilities_email_suppress = '';

		// Get variables.
		extract( $_POST );

		// Init force cache-clearing flag.
		$force = false;

		// Get existing menu setting.
		$existing_menu = $this->setting_get( 'prettify_menu', '0' );
		if ( $civicrm_admin_utilities_menu != $existing_menu ) {
			$force = true;
		}

		// Did we ask to hide CiviCRM?
		if ( $civicrm_admin_utilities_hide_civicrm == '1' ) {
			$this->setting_set( 'hide_civicrm', '1' );
		} else {
			$this->setting_set( 'hide_civicrm', '0' );
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

		// Did we ask to suppress Notification Emails?
		if ( $civicrm_admin_utilities_email_suppress == '1' ) {
			$this->setting_set( 'email_suppress', '1' );
		} else {
			$this->setting_set( 'email_suppress', '0' );
		}

		// Get existing access setting.
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

		// Did we ask to hide the "Manage Groups" menu item from the shortcuts menu?
		if ( $civicrm_admin_utilities_admin_bar_groups == '1' ) {
			$this->setting_set( 'admin_bar_groups', '1' );
		} else {
			$this->setting_set( 'admin_bar_groups', '0' );
		}

		// Did we ask to fix Contact Soft Delete?
		if ( $civicrm_admin_utilities_fix_soft_delete == '1' ) {
			$this->setting_set( 'fix_soft_delete', '1' );
		} else {
			$this->setting_set( 'fix_soft_delete', '0' );
		}

		// Save options.
		$this->settings_save();

		// Clear caches if asked to - or if forced to do so.
		if ( $civicrm_admin_utilities_cache == '1' OR $force ) {
			$this->clear_caches();
		}

		// --<
		return true;

	}



	//##########################################################################



	/**
	 * Save array as option.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class and made site-specific.
	 *
	 * @return bool Success or failure.
	 */
	public function settings_save() {

		// Save array as option.
		return $this->option_set( 'civicrm_admin_utilities_settings', $this->settings );

	}



	/**
	 * Check whether a specified setting exists.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class and made site-specific.
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
	 * @since 0.5.4 Moved from admin class and made site-specific.
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
	 * @since 0.5.4 Moved from admin class and made site-specific.
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
	 * @since 0.5.4 Moved from admin class and made site-specific.
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
	 * Test existence of a specified option.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class and made site-specific.
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
	 * Return a value for a specified option.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class and made site-specific.
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

		// Get option.
		$value = get_option( $option_name, $default );

		// --<
		return $value;

	}



	/**
	 * Set a value for a specified option.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class and made site-specific.
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

		// Update option
		return update_option( $option_name, $value );

	}



	/**
	 * Delete a specified option.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class and made site-specific.
	 *
	 * @param str $option_name The name of the option.
	 * @return bool $success True if the option was successfully deleted.
	 */
	public function option_delete( $option_name = '' ) {

		// Test for empty.
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_delete()', 'civicrm-admin-utilities' ) );
		}

		// Delete option
		return delete_option( $option_name );

	}



} // Class ends.



