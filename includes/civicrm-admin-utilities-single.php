<?php
/**
 * Single Site Class.
 *
 * Handles Single Site functionality.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Admin Utilities Single Site Class.
 *
 * A class that encapsulates Single Site admin functionality.
 *
 * @since 0.5.4
 */
class CiviCRM_Admin_Utilities_Single {

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
	 * Parent page reference.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array
	 */
	public $parent_page;

	/**
	 * Settings page reference.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array
	 */
	public $settings_page;

	/**
	 * Settings data.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array
	 */
	public $settings = [];

	/**
	 * Admin page URLs.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array
	 */
	public $urls = [];

	/**
	 * Suppress notification email flag.
	 *
	 * @since 0.6.5
	 * @access public
	 * @var bool
	 */
	private $email_sync;

	/**
	 * Soft delete direction flag.
	 *
	 * @since 0.6.8
	 * @access private
	 * @var string
	 */
	private $direction;

	/**
	 * Upgrade flag.
	 *
	 * @since 0.7.4
	 * @access public
	 * @var bool
	 */
	public $is_upgrade = false;

	/**
	 * Saved timezone.
	 *
	 * @since 1.0.1
	 * @access public
	 * @var string
	 */
	public $php_timezone = '';

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
		if ( false === $this->plugin_version ) {

			// Delete the legacy "installed" option.
			$this->delete_legacy_option();

		}

		// If this is an upgrade.
		if ( CIVICRM_ADMIN_UTILITIES_VERSION !== $this->plugin_version ) {
			$this->is_upgrade = true;
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

		// List of Afforms outside content setting may not exist.
		if ( ! $this->setting_exists( 'afforms' ) ) {

			// Add it from defaults.
			if ( ! isset( $settings ) ) {
				$settings = $this->settings_get_defaults();
			}
			$this->setting_set( 'afforms', $settings['afforms'] );
			$save = true;

		}

		// If this is an upgrade.
		if ( $this->is_upgrade ) {

			// Always check Theme sync on upgrade.
			$this->setting_set( 'theme_sync', '0' );
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

		// Add admin page to Settings menu.
		add_action( 'admin_menu', [ $this, 'admin_menu' ], 11 );

		// Add our meta boxes.
		add_action( 'cau/single/admin/add_meta_boxes', [ $this, 'meta_boxes_add' ], 11, 1 );

		// Kill CiviCRM shortcode button.
		add_action( 'admin_head', [ $this, 'kill_civi_button' ] );

		// Register template directory for menu amends.
		add_action( 'civicrm_config', [ $this, 'register_menu_directory' ], 10, 1 );

		// Style tweaks for CiviCRM.
		add_action( 'admin_print_styles', [ $this, 'admin_scripts_enqueue' ] );

		// Add Shortcuts Menu to WordPress admin bar.
		add_action( 'admin_bar_menu', [ $this, 'shortcuts_menu_add' ], 2000 );

		// Hook in just before CiviCRM does to disable resources.
		add_action( 'admin_head', [ $this, 'resources_disable' ], 9 );
		add_action( 'wp_head', [ $this, 'resources_disable' ], 9 );

		// Add contact link to the 'user-edit.php' page.
		add_action( 'personal_options', [ $this, 'profile_extras' ] );

		// Add contact link to User listings.
		add_filter( 'user_row_actions', [ $this, 'user_actions' ], 9, 2 );

		// Intercept email updates in CiviCRM.
		add_action( 'civicrm_pre', [ $this, 'email_pre_update' ], 10, 4 );

		// Intercept email updates in CiviCRM WP Profile Sync.
		add_action( 'civicrm_wp_profile_sync_primary_email_pre_update', [ $this, 'email_cwps_pre_update' ], 10, 2 );

		// Maybe suppress notification emails.
		add_filter( 'send_email_change_email', [ $this, 'email_suppress' ], 10, 3 );

		// Hook in after the CiviCRM menu hook has been registered.
		add_action( 'init', [ $this, 'hide_civicrm' ], 20 );

		// Listen for when a Contact is about to be moved in or out of Trash.
		add_action( 'civicrm_pre', [ $this, 'contact_soft_delete_pre' ], 10, 4 );

		// Listen for when a Contact has been moved in or out of Trash.
		add_action( 'civicrm_post', [ $this, 'contact_soft_delete_post' ], 10, 4 );

		// Listen for Dashboard view.
		add_action( 'civicrm_config', [ $this, 'dashboard_init' ], 10, 1 );

		// Add callback for CiviCRM Processor Params.
		add_action( 'civicrm_alterPaymentProcessorParams', [ $this, 'paypal_params' ], 10, 3 );

		// Listen for API calls.
		add_action( 'civicrm_config', [ $this, 'api_timezone_sync' ], 10, 1 );

		// Add Afform Angular modules when required.
		add_action( 'wp', [ $this, 'afform_scripts' ] );

		// If the debugging flag is set.
		if ( CIVICRM_ADMIN_UTILITIES_DEBUG === true ) {

			// Log pre and post database operations.
			add_action( 'civicrm_pre', [ $this, 'trace_pre' ], 10, 4 );
			add_action( 'civicrm_post', [ $this, 'trace_post' ], 10, 4 );
			add_action( 'civicrm_postProcess', [ $this, 'trace_post_process' ], 10, 2 );

		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Add link to CiviCRM Contact on the Users screen.
	 *
	 * @since 0.6.8
	 *
	 * @param str     $actions The existing actions to display for this user row.
	 * @param WP_User $user The user object displayed in this row.
	 * @return str $actions The modified actions to display for this user row.
	 */
	public function user_actions( $actions, $user ) {

		// Bail if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return $actions;
		}

		// Bail if we can't edit this user.
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return $actions;
		}

		// Bail if user cannot access CiviCRM.
		if ( ! current_user_can( 'access_civicrm' ) ) {
			return $actions;
		}

		// Get contact ID.
		$contact_id = $this->plugin->ufmatch->contact_id_get_by_user_id( $user->ID );

		// Bail if we don't get one for some reason.
		if ( false === $contact_id ) {
			return $actions;
		}

		// Check with CiviCRM that this Contact can be viewed.
		$allowed = CRM_Contact_BAO_Contact_Permission::allow( $contact_id, CRM_Core_Permission::VIEW );

		// Bail if we don't get permission.
		if ( ! $allowed ) {
			return $actions;
		}

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

		// Bail if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return;
		}

		// Bail if we can't edit this user.
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}

		// Bail if user cannot access CiviCRM.
		if ( ! current_user_can( 'access_civicrm' ) ) {
			return;
		}

		// Get contact ID.
		$contact_id = $this->plugin->ufmatch->contact_id_get_by_user_id( $user->ID );

		// Bail if we don't get one for some reason.
		if ( false === $contact_id ) {
			return;
		}

		// Check with CiviCRM that this Contact can be viewed.
		$allowed = CRM_Contact_BAO_Contact_Permission::allow( $contact_id, CRM_Core_Permission::VIEW );

		// Bail if we don't get permission.
		if ( ! $allowed ) {
			return;
		}

		// Get the link to the Contact.
		$link = $this->get_link( 'civicrm/contact/view', 'reset=1&cid=' . $contact_id );

		// Include template.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/user-edit.php';

	}

	// -------------------------------------------------------------------------

	/**
	 * Set property when a CiviCRM contact's primary email address is updated.
	 *
	 * @since 0.6.5
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function email_pre_update( $op, $object_name, $object_id, $object_ref ) {

		// Target our operation.
		if ( 'edit' !== $op ) {
			return;
		}

		// Target our object type.
		if ( 'Email' !== $object_name ) {
			return;
		}

		// Bail if we have no Email address.
		if ( empty( $object_ref['email'] ) ) {
			return;
		}

		// Get the existing Email record.
		$email = $this->email_get_by_id( $object_id );

		// Bail if this is not the Primary Email.
		if ( 1 !== (int) $email->is_primary ) {
			return;
		}

		// Set a property to check in `email_suppress()` below.
		$this->email_sync = true;

	}

	/**
	 * Set property when a CiviCRM contact's primary email address is updated by
	 * the CiviCRM WordPress Profile Sync plugin.
	 *
	 * @since 0.8
	 *
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function email_cwps_pre_update( $object_id, $object_ref ) {

		// Set a property to check in `email_suppress()` below.
		$this->email_sync = true;

	}

	/**
	 * Suppress notification email when WordPress user email changes.
	 *
	 * @since 0.6.5
	 *
	 * @param bool  $send Whether to send the email.
	 * @param array $user The original user array.
	 * @param array $userdata The updated user array.
	 */
	public function email_suppress( $send, $user, $userdata ) {

		// Bail if email suppression is not enabled.
		if ( $this->setting_get( 'email_suppress', '0' ) === '0' ) {
			return $send;
		}

		// Did this change originate with CiviCRM?
		if ( isset( $this->email_sync ) && true === $this->email_sync ) {

			// Unset property.
			unset( $this->email_sync );

			// Do not notify.
			$send = false;

		}

		// --<
		return $send;

	}

	/**
	 * Get a CiviCRM Email record by its ID.
	 *
	 * @since 0.8
	 *
	 * @param integer $email_id The numeric ID of the CiviCRM Email record.
	 * @return object|bool $email The CiviCRM Email record, or false on failure.
	 */
	public function email_get_by_id( $email_id ) {

		// Init return.
		$email = false;

		// Get the requested Email record.
		$params = [
			'version' => 3,
			'id'      => $email_id,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'Email', 'get', $params );

		// Bail on failure.
		if ( isset( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $email;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $email;
		}

		// The result set should contain only one item.
		$email = (object) array_pop( $result['values'] );

		// --<
		return $email;

	}

	// -------------------------------------------------------------------------

	/**
	 * Maybe hide CiviCRM on this site.
	 *
	 * @since 0.6.8
	 */
	public function hide_civicrm() {

		// Bail if not Multisite.
		if ( ! is_multisite() ) {
			return;
		}

		// Bail if disabled.
		if ( $this->setting_get( 'hide_civicrm', '0' ) === '0' ) {
			return;
		}

		// Hide the CiviCRM UI elements.
		$this->hide_civicrm_ui();

		// Remove CiviCRM shortcode button.
		add_action( 'admin_head', [ $this, 'civi_button_remove' ] );

		// Remove Shortcuts Menu from WordPress admin bar.
		remove_action( 'admin_bar_menu', [ $this, 'shortcuts_menu_add' ], 2000 );

	}

	/**
	 * Hide the CiviCRM UI.
	 *
	 * @since 0.8.3
	 */
	public function hide_civicrm_ui() {

		// Get CiviCRM object.
		$civi = civi_wp();

		// Do we have the admin object?
		if ( isset( $civi->admin ) && is_object( $civi->admin ) ) {

			// Unhook CiviCRM's menu item, but allow CiviCRM to load.
			remove_action( 'admin_menu', [ $civi->admin, 'add_menu_items' ], 9 );

			// Remove notice.
			remove_action( 'admin_notices', [ $civi->admin, 'show_setup_warning' ] );

			// Also remove the "Quick Add" meta box.
			remove_action( 'wp_dashboard_setup', [ $civi->admin->metabox_quick_add, 'meta_box_add' ] );
			remove_action( 'admin_enqueue_scripts', [ $civi->admin->metabox_quick_add, 'enqueue_js' ] );
			remove_action( 'admin_enqueue_scripts', [ $civi->admin->metabox_quick_add, 'enqueue_css' ] );
			remove_action( 'admin_init', [ $civi->admin->metabox_quick_add, 'form_submitted' ] );
			remove_action( 'wp_ajax_civicrm_contact_add', [ $civi->admin->metabox_quick_add, 'ajax_contact_add' ] );

		} else {

			// Unhook CiviCRM's menu item, but allow CiviCRM to load.
			remove_action( 'admin_menu', [ civi_wp(), 'add_menu_items' ] );

			// Remove notice.
			remove_action( 'admin_notices', [ civi_wp(), 'show_setup_warning' ] );

		}

	}

	// -------------------------------------------------------------------------

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
		 */
		$capability = apply_filters( 'civicrm_admin_utilities_page_settings_cap', 'manage_options' );

		// Check user permissions.
		if ( ! current_user_can( $capability ) ) {
			return;
		}

		// Add the admin page to the CiviCRM menu.
		$this->parent_page = add_submenu_page(
			'CiviCRM', // Parent slug.
			__( 'CiviCRM Admin Utilities: Settings', 'civicrm-admin-utilities' ),
			__( ' Admin Utilities', 'civicrm-admin-utilities' ),
			$capability,
			'cau_parent',
			[ $this, 'page_settings' ]
		);

		// Register our form submit hander.
		add_action( 'load-' . $this->parent_page, [ $this, 'settings_update_router' ] );

		// Add help text.
		add_action( 'admin_head-' . $this->parent_page, [ $this, 'admin_head' ], 50 );

		// Add scripts and styles.
		add_action( 'admin_print_styles-' . $this->parent_page, [ $this, 'admin_css' ] );
		add_action( 'admin_print_scripts-' . $this->parent_page, [ $this, 'admin_js' ] );

		// Add settings page.
		$this->settings_page = add_submenu_page(
			'cau_parent', // Parent slug.
			__( 'CiviCRM Admin Utilities: Settings', 'civicrm-admin-utilities' ), // Page title.
			__( 'Settings', 'civicrm-admin-utilities' ), // Menu title.
			$capability, // Required caps.
			'cau_settings', // Slug name.
			[ $this, 'page_settings' ] // Callback.
		);

		// Register our form submit hander.
		add_action( 'load-' . $this->settings_page, [ $this, 'settings_update_router' ] );

		// Ensure correct menu item is highlighted.
		add_action( 'admin_head-' . $this->settings_page, [ $this, 'admin_menu_highlight' ], 50 );

		// Add help text.
		add_action( 'admin_head-' . $this->settings_page, [ $this, 'admin_head' ], 50 );

		// Add scripts and styles.
		add_action( 'admin_print_styles-' . $this->settings_page, [ $this, 'admin_css' ] );
		add_action( 'admin_print_scripts-' . $this->settings_page, [ $this, 'admin_js' ] );

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

		// We need to override these to highlight the correct item.
		global $plugin_page, $submenu_file;

		// Define subpages.
		$subpages = [
			'cau_settings',
		];

		/**
		 * Filter the list of subpages.
		 *
		 * @since 0.5.4
		 *
		 * @param array $subpages The existing list of subpages.
		 */
		$subpages = apply_filters( 'civicrm_admin_utilities_subpages', $subpages );

		// This tweaks the Settings subnav menu to show only one menu item.
		if ( in_array( $plugin_page, $subpages, true ) ) {
			// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
			$plugin_page  = 'cau_parent';
			$submenu_file = 'cau_parent';
			// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
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
			$this->parent_page,
			$this->settings_page,
		];

		// Kick out if not our screen.
		if ( ! in_array( $screen->id, $pages, true ) ) {
			return $screen;
		}

		// Build tab args.
		$args = [
			'id'      => 'civicrm_admin_utilities',
			'title'   => __( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ),
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

		// Register Select2 styles.
		wp_register_style(
			'civicrm-au-afforms-select2-css',
			set_url_scheme( 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css' ),
			false,
			CIVICRM_ADMIN_UTILITIES_VERSION, // Version.
			'all' // Media.
		);

		// Enqueue Select2 styles.
		wp_enqueue_style( 'civicrm-au-afforms-select2-css' );

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
			[ 'jquery' ],
			CIVICRM_ADMIN_UTILITIES_VERSION, // Version.
			true
		);

		// Enqueue 2020 script.
		wp_enqueue_script(
			'civicrm_admin_utilities_2020_js',
			plugins_url( 'assets/js/twentytwenty/js/jquery.twentytwenty.js', CIVICRM_ADMIN_UTILITIES_FILE ),
			[ 'civicrm_admin_utilities_2020_move_js' ],
			CIVICRM_ADMIN_UTILITIES_VERSION, // Version.
			true
		);

		// Enqueue our "Site Settings" page script.
		wp_enqueue_script(
			'civicrm_admin_utilities_js',
			plugins_url( 'assets/js/civicrm-admin-utilities-site-settings.js', CIVICRM_ADMIN_UTILITIES_FILE ),
			[ 'civicrm_admin_utilities_2020_js' ],
			CIVICRM_ADMIN_UTILITIES_VERSION, // Version.
			true
		);

		// Register Select2.
		wp_register_script(
			'civicrm-au-afforms-select2-js',
			set_url_scheme( 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js' ),
			[ 'jquery' ],
			CIVICRM_ADMIN_UTILITIES_VERSION,
			false
		);

		// Enqueue Select2.
		wp_enqueue_script( 'civicrm-au-afforms-select2-js' );

	}

	// -------------------------------------------------------------------------

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
		 */
		$capability = apply_filters( 'civicrm_admin_utilities_page_settings_cap', 'manage_options' );

		// Check user permissions.
		if ( ! current_user_can( $capability ) ) {
			return;
		}

		// Get admin page URLs.
		$urls = $this->page_get_urls();

		/**
		 * Do not show tabs by default but allow overrides.
		 *
		 * @since 0.5.4
		 *
		 * @param bool False by default - do not show tabs.
		 */
		$show_tabs = apply_filters( 'civicrm_admin_utilities_show_tabs', false );

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
		do_action( 'cau/single/admin/add_meta_boxes', $screen->id, null );

		// Grab columns.
		$columns = ( 1 === (int) $screen->get_columns() ? '1' : '2' );

		// Include template.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/site-settings.php';

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
		if ( ! empty( $this->urls ) ) {
			return $this->urls;
		}

		// Init return.
		$this->urls = [];

		// Get admin page URLs.
		$this->urls['settings'] = menu_page_url( 'cau_settings', false );

		/**
		 * Filter the list of URLs.
		 *
		 * @since 0.5.4
		 *
		 * @param array $urls The existing list of URLs.
		 */
		$this->urls = apply_filters( 'civicrm_admin_utilities_page_urls', $this->urls );

		// --<
		return $this->urls;

	}

	/**
	 * Get the URL for the form action.
	 *
	 * @since 0.5.4
	 *
	 * @return string $target_url The URL for the admin form action.
	 */
	public function page_submit_url_get() {

		// Use Settings page URL.
		$target_url = menu_page_url( 'cau_settings', false );

		// --<
		return $target_url;

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
			'civicrm_page_cau_parent',
			'admin_page_cau_settings',
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

		// Create Submit metabox.
		add_meta_box(
			'submitdiv',
			__( 'Settings', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_submit_render' ], // Callback.
			$screen_id, // Screen ID.
			'side', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

		/**
		 * Set restricted-to-main-site template variable but allow overrides.
		 *
		 * This variable is set to "restricted" by default so that the relevant
		 * section of the form does not show up when not in Multisite.
		 *
		 * @since 0.6.8
		 *
		 * @param bool The default template variable - restricted by default.
		 */
		$restricted = apply_filters( 'civicrm_admin_utilities_page_settings_restricted', true );

		// Show meta box if we're allowed to.
		if ( ! $restricted ) {

			// Create CiviCRM Access metabox.
			add_meta_box(
				'civicrm_au_access',
				__( 'CiviCRM Access', 'civicrm-admin-utilities' ),
				[ $this, 'meta_box_access_render' ], // Callback.
				$screen_id, // Screen ID.
				'normal', // Column: options are 'normal' and 'side'.
				'core' // Vertical placement: options are 'core', 'high', 'low'.
			);

		}

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

		// Create Form Builder metabox.
		add_meta_box(
			'civicrm_au_afform',
			__( 'Form Builder Blocks', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_afform_render' ], // Callback.
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

		// If this user can administer CiviCRM.
		if ( $this->check_permission( 'administer CiviCRM' ) ) {

			// Create Shortcuts metabox.
			add_meta_box(
				'civicrm_au_misc',
				__( 'Shortcuts', 'civicrm-admin-utilities' ),
				[ $this, 'meta_box_shortcuts_render' ], // Callback.
				$screen_id, // Screen ID.
				'side', // Column: options are 'normal' and 'side'.
				'low' // Vertical placement: options are 'core', 'high', 'low'.
			);

		}

	}

	/**
	 * Render a Submit meta box on Admin screen.
	 *
	 * @since 0.8.1
	 */
	public function meta_box_submit_render() {

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/site-metabox-submit.php';

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

		// Init admin CSS checkbox and Theme preview visibility.
		$admin_css     = 0;
		$theme_preview = '';
		if ( $this->setting_get( 'css_admin', '0' ) === '1' ) {
			$admin_css     = 1;
			$theme_preview = ' display: none;';
		}

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

		// Check if Shoreditch CSS is present.
		if ( $this->shoreditch_is_active() ) {

			// Set flag.
			$shoreditch = true;

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

		} else {

			// Set flag.
			$shoreditch = false;

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

		}

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

		// Get Post Type options.
		$options = $this->post_type_options_get();

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/site-metabox-post-types.php';

	}

	/**
	 * Render Form Builder meta box on Admin screen.
	 *
	 * @since 1.0.7
	 */
	public function meta_box_afform_render() {

		// Bail if CiviCRM fails to initialise.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return;
		}

		// Get all relevant Afforms.
		$afforms = \Civi\Api4\Afform::get( false )
			->addSelect( 'name', 'title', 'is_public' )
			->addWhere( 'type', 'IN', [ 'form', 'search' ] )
			->addWhere( 'is_public', '=', true )
			->execute();

		// Get the saved Afforms.
		$used = $this->setting_get( 'afforms', [] );

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/site-metabox-afform.php';

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

	/**
	 * Render Shortcuts meta box on Admin screen.
	 *
	 * @since 0.8.1
	 */
	public function meta_box_shortcuts_render() {

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/site-metabox-shortcuts.php';

	}

	// -------------------------------------------------------------------------

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
	public function post_type_options_get( $selected_types = [] ) {

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

		// --<
		return $options;

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
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return;
		}

		// Access config object.
		$config = CRM_Core_Config::singleton();

		// Clear database cache.
		$config->clearDBCache();

		// Cleanup the "templates_c" directory.
		$config->cleanup( 1, true );

		// Cleanup the session object.
		$session = CRM_Core_Session::singleton();
		$session->reset( 1 );

		// Call system flush.
		CRM_Utils_System::flushCache();

	}

	// -------------------------------------------------------------------------

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
		if ( $this->setting_get( 'prettify_menu', '0' ) === '0' ) {
			return;
		}

		// Get template instance.
		$template = CRM_Core_Smarty::singleton();

		// Get current version.
		$version = CRM_Utils_System::version();

		// Define our custom path based on CiviCRM version.
		if ( version_compare( $version, '5.5', '>=' ) ) {
			$custom_path = CIVICRM_ADMIN_UTILITIES_PATH . 'assets/civicrm/template-nav';
		} else {
			$custom_path = CIVICRM_ADMIN_UTILITIES_PATH . 'assets/civicrm/template-menu';
		}

		// Add our custom template directory.
		$template->addTemplateDir( $custom_path );

		// Register template directories.
		$template_include_path = $custom_path . PATH_SEPARATOR . get_include_path();
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path
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
		if ( $this->setting_get( 'prettify_menu', '0' ) === '1' ) {

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
			if ( $this->setting_get( 'css_admin', '0' ) === '0' ) {

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
		if ( $this->setting_get( 'css_admin', '0' ) === '1' ) {

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
					[ 'civicrm_admin_utilities_admin_override' ],
					CIVICRM_ADMIN_UTILITIES_VERSION, // Version.
					'all' // Media.
				);
			}

			// Amend styles when CiviCRM 5.27+ is detected.
			if ( $this->plugin->is_civicrm_initialised() ) {
				$version = CRM_Utils_System::version();
				if ( version_compare( $version, '5.27', '>=' ) ) {
					wp_enqueue_style(
						'civicrm_admin_utilities_admin_override_civi527plus',
						plugins_url( 'assets/css/civicrm-admin-utilities-admin-civi-5-27-plus.css', CIVICRM_ADMIN_UTILITIES_FILE ),
						[ 'civicrm_admin_utilities_admin_override' ],
						CIVICRM_ADMIN_UTILITIES_VERSION, // Version.
						'all' // Media.
					);
				}
			}

			// Amend styles when the CiviCRM Admin Utilities "theme" is active.
			if ( $this->plugin->theme->is_cau_theme() ) {

				// Theme amends for CiviCRM 5.31+.
				wp_enqueue_style(
					'civicrm_admin_utilities_admin_override_civi531plus',
					plugins_url( 'assets/css/civicrm-admin-utilities-admin-civi-5-31-plus.css', CIVICRM_ADMIN_UTILITIES_FILE ),
					[ 'civicrm_admin_utilities_admin_override' ],
					CIVICRM_ADMIN_UTILITIES_VERSION, // Version.
					'all' // Media.
				);

				// Amend styles when CiviCRM 5.69+ is detected.
				if ( $this->plugin->is_civicrm_initialised() ) {
					$version = CRM_Utils_System::version();
					if ( version_compare( $version, '5.69', '>=' ) ) {
						wp_enqueue_style(
							'civicrm_admin_utilities_admin_override_civi569plus',
							plugins_url( 'assets/css/civicrm-admin-utilities-admin-civi-5-69-plus.css', CIVICRM_ADMIN_UTILITIES_FILE ),
							[ 'civicrm_admin_utilities_admin_override_civi531plus' ],
							CIVICRM_ADMIN_UTILITIES_VERSION, // Version.
							'all' // Media.
						);
					}
				}

			}

			/**
			 * Broadcast that we are loading a custom CiviCRM stylesheet.
			 *
			 * @since 0.4.2
			 */
			do_action( 'civicrm_admin_utilities_admin_overridden' );

		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Disable CiviCRM resources from front-end.
	 *
	 * @since 0.4.1
	 * @since 0.5.4 Moved from plugin class.
	 */
	public function resources_disable() {

		// Kick out if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return;
		}

		if ( is_admin() ) {

			// Maybe disable core stylesheet.
			if ( $this->setting_get( 'css_admin', '0' ) === '1' ) {

				// Disable core stylesheet.
				$this->resource_disable( 'civicrm', 'css/civicrm.css' );

				// Also disable Shoreditch if present.
				if ( $this->shoreditch_is_active() ) {
					$this->resource_disable( 'org.civicrm.shoreditch', 'css/custom-civicrm.css' );
				}

			}

			// Maybe disable custom stylesheet (not provided by Shoreditch).
			if ( $this->setting_get( 'css_custom_public', '0' ) === '1' ) {
				$this->custom_css_disable();
			}

		} else {

			// Maybe disable core stylesheet.
			if ( $this->setting_get( 'css_default', '0' ) === '1' ) {
				$this->resource_disable( 'civicrm', 'css/civicrm.css' );
			}

			// Maybe disable navigation stylesheet (there's no menu on the front-end).
			if ( $this->setting_get( 'css_navigation', '0' ) === '1' ) {
				$this->resource_disable( 'civicrm', 'css/civicrmNavigation.css' );
			}

			// If Shoreditch present.
			if ( $this->shoreditch_is_active() ) {

				// Maybe disable Shoreditch stylesheet.
				if ( $this->setting_get( 'css_shoreditch', '0' ) === '1' ) {
					$this->resource_disable( 'org.civicrm.shoreditch', 'css/custom-civicrm.css' );
				}

				// Maybe disable Shoreditch Bootstrap stylesheet.
				if ( $this->setting_get( 'css_bootstrap', '0' ) === '1' ) {
					$this->resource_disable( 'org.civicrm.shoreditch', 'css/bootstrap.css' );
				}

			} else {

				// Maybe disable custom stylesheet (not provided by Shoreditch).
				if ( $this->setting_get( 'css_custom', '0' ) === '1' ) {
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
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return;
		}

		// Get the resource URL.
		$url = $this->resource_get_url( $extension, $file );

		// Kick out if not enqueued.
		if ( false === $url ) {
			return;
		}

		// Set to disabled.
		CRM_Core_Region::instance( 'html-header' )->update( $url, [ 'disabled' => true ] );

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
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return false;
		}

		// Get registered URL.
		$url = CRM_Core_Resources::singleton()->getUrl( $extension, $file, true );

		// Get registration data from region.
		$registration = CRM_Core_Region::instance( 'html-header' )->get( $url );

		// Bail if not registered.
		if ( empty( $registration ) ) {
			return false;
		}

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
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return;
		}

		// Get CiviCRM config.
		$config = CRM_Core_Config::singleton();

		// Bail if there's no custom CSS file.
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( empty( $config->customCSSURL ) ) {
			return;
		}

		// Get registered URL or bundle "name".
		$version = CRM_Utils_System::version();
		if ( version_compare( $version, '5.39', '<' ) ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$url = CRM_Core_Resources::singleton()->addCacheCode( $config->customCSSURL );
		} else {
			$url = 'civicrm:css/custom.css';
		}

		// Get registration data from region.
		$registration = CRM_Core_Region::instance( 'html-header' )->get( $url );

		// Bail if not registered.
		if ( empty( $registration ) ) {
			return;
		}

		// Set to disabled.
		CRM_Core_Region::instance( 'html-header' )->update( $url, [ 'disabled' => true ] );

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
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return $shoreditch;
		}

		// Get the current Custom CSS URL.
		$config = CRM_Core_Config::singleton();

		// Override return if the Shoreditch CSS has been activated.
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( strstr( $config->customCSSURL, 'org.civicrm.shoreditch' ) !== false ) {
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
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return false;
		}

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

		// Get screen.
		$screen = get_current_screen();

		// Prevent warning if screen not defined.
		if ( empty( $screen ) ) {
			return;
		}

		// Bail if there's no post type.
		if ( empty( $screen->post_type ) ) {
			return;
		}

		// Get chosen post types.
		$selected_types = $this->setting_get( 'post_types', [] );

		// Remove button if this is not a post type we want to allow the button on.
		if ( ! in_array( $screen->post_type, $selected_types, true ) ) {
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
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return;
		}

		// Get CiviCRM object.
		$civi = civi_wp();

		// Do we have the modal object?
		if ( isset( $civi->modal ) && is_object( $civi->modal ) ) {

			// Remove current CiviCRM actions.
			remove_action( 'media_buttons_context', [ $civi->modal, 'add_form_button' ] );
			remove_action( 'media_buttons', [ $civi->modal, 'add_form_button' ], 100 );
			remove_action( 'admin_enqueue_scripts', [ $civi->modal, 'add_form_button_js' ] );
			remove_action( 'admin_footer', [ $civi->modal, 'add_form_button_html' ] );

			// Also remove core resources.
			remove_action( 'admin_head', [ $civi, 'wp_head' ], 50 );
			remove_action( 'load-post.php', [ $civi->modal, 'add_core_resources' ] );
			remove_action( 'load-post-new.php', [ $civi->modal, 'add_core_resources' ] );
			remove_action( 'load-page.php', [ $civi->modal, 'add_core_resources' ] );
			remove_action( 'load-page-new.php', [ $civi->modal, 'add_core_resources' ] );

		} else {

			// Remove legacy CiviCRM actions.
			remove_action( 'media_buttons_context', [ $civi, 'add_form_button' ] );
			remove_action( 'media_buttons', [ $civi, 'add_form_button' ], 100 );
			remove_action( 'admin_enqueue_scripts', [ $civi, 'add_form_button_js' ] );
			remove_action( 'admin_footer', [ $civi, 'add_form_button_html' ] );

		}

	}

	// -------------------------------------------------------------------------

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
	 *
	 * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance, passed by reference.
	 */
	public function shortcuts_menu_add( $wp_admin_bar ) {

		// Bail if admin bar not enabled.
		if ( $this->setting_get( 'admin_bar', '0' ) === '0' ) {
			return;
		}

		// Kick out if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return;
		}

		// Bail if user cannot access CiviCRM.
		if ( ! current_user_can( 'access_civicrm' ) ) {
			return;
		}

		/**
		 * Fires before Shortcuts Menu has been defined.
		 *
		 * @since 0.5.4
		 */
		do_action( 'civicrm_admin_utilities_menu_before' );

		// Get component info.
		$components = CRM_Core_Component::getEnabledComponents();

		// Define a menu parent ID.
		$id = 'civicrm-admin-utils';

		// Add parent.
		$node = [
			'id'    => $id,
			'title' => __( 'CiviCRM', 'civicrm-admin-utilities' ),
			'href'  => admin_url( 'admin.php?page=CiviCRM' ),
		];
		$wp_admin_bar->add_node( $node );

		/**
		 * Fires at the top of the Shortcuts Menu.
		 *
		 * @since 0.7.1
		 * @since 1.0.5 Changed to `do_action_ref_array`.
		 * @since 1.0.5 Added `$wp_admin_bar` param.
		 *
		 * @param str $id The menu parent ID.
		 * @param array $components The active CiviCRM Conponents.
		 * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance, passed by reference.
		 */
		do_action_ref_array( 'civicrm_admin_utilities_menu_top', [ $id, $components, &$wp_admin_bar ] );

		// Dashboard.
		$node = [
			'id'     => 'cau-1',
			'parent' => $id,
			'title'  => __( 'CiviCRM Dashboard', 'civicrm-admin-utilities' ),
			'href'   => admin_url( 'admin.php?page=CiviCRM' ),
		];
		$wp_admin_bar->add_node( $node );

		// All Contacts.
		$node = [
			'id'     => 'cau-12',
			'parent' => $id,
			'title'  => __( 'All Contacts', 'civicrm-admin-utilities' ),
			'href'   => $this->get_link( 'civicrm/contact/search', 'force=true&reset=1' ),
		];
		$wp_admin_bar->add_node( $node );

		// Search.
		$node = [
			'id'     => 'cau-2',
			'parent' => $id,
			'title'  => __( 'Advanced Search', 'civicrm-admin-utilities' ),
			'href'   => $this->get_link( 'civicrm/contact/search/advanced', 'reset=1' ),
		];
		$wp_admin_bar->add_node( $node );

		// Maybe hide "Manage Groups" menu item.
		if ( $this->setting_get( 'admin_bar_groups', '0' ) === '1' ) {
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
		 */
		$allowed = apply_filters( 'civicrm_admin_utilities_manage_groups_menu_item', true );

		// Groups.
		if ( $allowed ) {
			$node = [
				'id'     => 'cau-3',
				'parent' => $id,
				'title'  => __( 'Manage Groups', 'civicrm-admin-utilities' ),
				'href'   => $this->get_link( 'civicrm/group', 'reset=1' ),
			];
			$wp_admin_bar->add_node( $node );
		}

		// Contributions.
		if ( array_key_exists( 'CiviContribute', $components ) ) {
			if ( $this->check_permission( 'access CiviContribute' ) ) {
				$node = [
					'id'     => 'cau-4',
					'parent' => $id,
					'title'  => __( 'Contribution Dashboard', 'civicrm-admin-utilities' ),
					'href'   => $this->get_link( 'civicrm/contribute', 'reset=1' ),
				];
				$wp_admin_bar->add_node( $node );
			}
		}

		// Membership.
		if ( array_key_exists( 'CiviMember', $components ) ) {
			if ( $this->check_permission( 'access CiviMember' ) ) {
				$node = [
					'id'     => 'cau-5',
					'parent' => $id,
					'title'  => __( 'Membership Dashboard', 'civicrm-admin-utilities' ),
					'href'   => $this->get_link( 'civicrm/member', 'reset=1' ),
				];
				$wp_admin_bar->add_node( $node );
			}
		}

		// Events.
		if ( array_key_exists( 'CiviEvent', $components ) ) {
			if ( $this->check_permission( 'access CiviEvent' ) ) {
				$node = [
					'id'     => 'cau-6',
					'parent' => $id,
					'title'  => __( 'Events Dashboard', 'civicrm-admin-utilities' ),
					'href'   => $this->get_link( 'civicrm/event', 'reset=1' ),
				];
				$wp_admin_bar->add_node( $node );
			}
		}

		// Mailings.
		if ( array_key_exists( 'CiviMail', $components ) ) {
			if ( $this->check_permission( 'access CiviMail' ) ) {
				$node = [
					'id'     => 'cau-7',
					'parent' => $id,
					'title'  => __( 'Mailings Sent and Scheduled', 'civicrm-admin-utilities' ),
					'href'   => $this->get_link( 'civicrm/mailing/browse/scheduled', 'reset=1&scheduled=true' ),
				];
				$wp_admin_bar->add_node( $node );
			}
		}

		// Reports.
		if ( array_key_exists( 'CiviReport', $components ) ) {
			if ( $this->check_permission( 'access CiviReport' ) ) {
				$node = [
					'id'     => 'cau-8',
					'parent' => $id,
					'title'  => __( 'Report Listing', 'civicrm-admin-utilities' ),
					'href'   => $this->get_link( 'civicrm/report/list', '&reset=1' ),
				];
				$wp_admin_bar->add_node( $node );
			}
		}

		// Cases.
		if ( array_key_exists( 'CiviCase', $components ) ) {
			if ( CRM_Case_BAO_Case::accessCiviCase() ) {
				$node = [
					'id'     => 'cau-9',
					'parent' => $id,
					'title'  => __( 'Cases Dashboard', 'civicrm-admin-utilities' ),
					'href'   => $this->get_link( 'civicrm/case', 'reset=1' ),
				];
				$wp_admin_bar->add_node( $node );
			}
		}

		// Admin console.
		if ( $this->check_permission( 'administer CiviCRM' ) ) {
			$node = [
				'id'     => 'cau-10',
				'parent' => $id,
				'title'  => __( 'Admin Console', 'civicrm-admin-utilities' ),
				'href'   => $this->get_link( 'civicrm/admin', 'reset=1' ),
			];
			$wp_admin_bar->add_node( $node );
		}

		// CiviCRM Extensions.
		if ( $this->check_permission( 'administer CiviCRM' ) ) {
			$node = [
				'id'     => 'cau-ext',
				'parent' => $id,
				'title'  => __( 'Manage Extensions', 'civicrm-admin-utilities' ),
				'href'   => $this->get_link( 'civicrm/admin/extensions', 'reset=1' ),
			];
			$wp_admin_bar->add_node( $node );
		}

		/**
		 * Filter capability to view Admin Utilities settings page.
		 *
		 * @since 0.5.4
		 * @since 0.6.1 Added here to check access to menu item.
		 *
		 * @param str The default capability for access to settings page.
		 */
		$capability = apply_filters( 'civicrm_admin_utilities_page_settings_cap', 'manage_options' );

		// Add link to Admin Utilities settings page.
		if ( current_user_can( $capability ) ) {
			$node = [
				'id'     => 'cau-11',
				'parent' => $id,
				'title'  => __( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ),
				'href'   => admin_url( 'admin.php?page=cau_settings' ),
			];
			$wp_admin_bar->add_node( $node );
		}

		/**
		 * Fires after Shortcuts Menu has been defined.
		 *
		 * @since 0.3
		 * @since 1.0.5 Changed to `do_action_ref_array`.
		 * @since 1.0.5 Added `$wp_admin_bar` param.
		 *
		 * @param str $id The menu parent ID.
		 * @param array $components The active CiviCRM Conponents.
		 * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance, passed by reference.
		 */
		do_action_ref_array( 'civicrm_admin_utilities_menu_after', [ $id, $components, &$wp_admin_bar ] );

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
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return $link;
		}

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
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return false;
		}

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
		 */
		return apply_filters( 'civicrm_admin_utilities_permitted', $permitted, $permission );

	}

	// -------------------------------------------------------------------------

	/**
	 * Before a Contact is updated, establish if they are being moved "to the
	 * Trash" or "from the Trash".
	 *
	 * @since 0.6.8
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function contact_soft_delete_pre( $op, $object_name, $object_id, $object_ref ) {

		// Uh oh! 'update' not 'edit'!
		if ( 'update' !== $op ) {
			return;
		}

		// Sanity check Contact Type.
		$contact_types = [ 'Individual', 'Household', 'Organization' ];
		if ( ! in_array( $object_name, $contact_types, true ) ) {
			return;
		}

		// Bail if disabled.
		if ( $this->setting_get( 'fix_soft_delete', '0' ) === '0' ) {
			return;
		}

		// Build params.
		$params = [
			'version'    => 3,
			'sequential' => 1,
			'id'         => $object_id,
		];

		// Get the Contact's data.
		$result = civicrm_api( 'Contact', 'get', $params );

		// Log and bail if there's an error.
		if ( ( isset( $result['is_error'] ) && 1 === (int) $result['is_error'] ) || 0 === (int) $result['count'] ) {
			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'result'    => $result,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			return;
		}

		// Get the Contact data.
		$contact_data = array_pop( $result['values'] );

		// Init direction with arbitrary value.
		$this->direction = 'none';

		// If the Contact was not in the Trash, then it's being moved to Trash.
		if ( isset( $object_ref['is_deleted'] ) && 1 === (int) $object_ref['is_deleted'] ) {
			if ( 0 === (int) $contact_data['contact_is_deleted'] ) {
				$this->direction = 'trashed';
			}
		}

		// If the Contact was in the Trash, then it's being moved out of the Trash.
		if ( ! isset( $object_ref['is_deleted'] ) || 0 === (int) $object_ref['is_deleted'] ) {
			if ( 1 === (int) $contact_data['contact_is_deleted'] ) {
				$this->direction = 'untrashed';
			}
		}

		// Sanity check.
		if ( 'none' === $this->direction ) {
			return;
		}

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function contact_soft_delete_post( $op, $object_name, $object_id, $object_ref ) {

		// Uh oh! 'update' not 'edit'!
		if ( 'update' !== $op ) {
			return;
		}

		// Sanity check Contact Type.
		$contact_types = [ 'Individual', 'Household', 'Organization' ];
		if ( ! in_array( $object_name, $contact_types, true ) ) {
			return;
		}

		// Bail if disabled.
		if ( $this->setting_get( 'fix_soft_delete', '0' ) === '0' ) {
			return;
		}

		// Sanity check.
		if ( 'none' === $this->direction ) {
			return;
		}

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
		 * @param CRM_Contact_DAO_Contact $object_ref The Contact data object.
		 */
		do_action( 'civicrm_admin_utilities_contact_post_' . $this->direction, $object_ref );

	}

	// -------------------------------------------------------------------------

	/**
	 * Add link to CiviCRM Contact on the Users screen.
	 *
	 * @since 0.6.8
	 *
	 * @param object $config The CiviCRM config object.
	 */
	public function dashboard_init( &$config ) {

		// Bail if disabled.
		if ( $this->setting_get( 'dashboard_title', '0' ) === '0' ) {
			return;
		}

		// Add callback for CiviCRM "dashboard" hook.
		Civi::service( 'dispatcher' )->addListener(
			'hook_civicrm_dashboard',
			[ $this, 'dashboard_title' ],
			-100 // Default priority.
		);

	}

	/**
	 * Add link to CiviCRM Contact on the Users screen.
	 *
	 * @since 0.6.8
	 *
	 * @param object $event The event object.
	 * @param string $hook The hook name.
	 */
	public function dashboard_title( $event, $hook ) {

		// Extract args for this hook.
		$params = $event->getHookValues();

		// The Contact ID is the first item.
		$contact_id = $params[0];

		// Define params to get Contact.
		$params = [
			'version'    => 3,
			'sequential' => 1,
			'id'         => $contact_id,
		];

		// Call the API.
		$result = civicrm_api( 'Contact', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return;
		}

		// The result set should contain only one item.
		$contact = array_pop( $result['values'] );

		// Build title.
		$title = sprintf(
			/* translators: %s: The Contact's first name */
			__( 'Hi %s, welcome to CiviCRM', 'civicrm-admin-utilities' ),
			$contact['first_name']
		);

		/**
		 * Filter the Dashboard Title.
		 *
		 * @since 0.7.1
		 *
		 * @param str $title The title to show on the CiviCRM Dashboard.
		 * @param array $contact The logged-in CiviCRM Contact data.
		 */
		$title = apply_filters( 'civicrm_admin_utilities_dashboard_title', $title, $contact );

		// Overwrite Dashboard title.
		CRM_Utils_System::setTitle( $title );

	}

	// -------------------------------------------------------------------------

	/**
	 * Filter the CiviCRM Processor Params.
	 *
	 * PayPal urlencodes the IPN Notify URL. For sites not using Clean URLs (or
	 * using Shortcodes in WordPress) this results in "%2F" becoming "%252F" and
	 * therefore incomplete transactions. We need to prevent that.
	 *
	 * This fix expires with CiviCRM 5.31.1 but supports all previous versions.
	 *
	 * @see https://lab.civicrm.org/dev/core/-/issues/1931
	 *
	 * @since 0.8
	 *
	 * @param object $payment_obj The Payment Processor object.
	 * @param array  $raw_params The original params.
	 * @param array  $cooked_params The built params.
	 */
	public function paypal_params( $payment_obj, &$raw_params, &$cooked_params ) {

		// Bail if this is fixed.
		if ( $this->paypal_fixed() ) {
			return;
		}

		// Bail if this version predates the need for a fix.
		if ( $this->paypal_predates_problem() ) {
			return;
		}

		// Bail if not the PayPal Processor.
		if ( ! ( $payment_obj instanceof CRM_Core_Payment_PayPalImpl ) ) {
			return;
		}

		// PayPal now rawurlencodes the IPN URL.
		$cooked_params['notify_url'] = rawurldecode( $cooked_params['notify_url'] );

	}

	/**
	 * Check if PayPal IPN URLs have been fixed.
	 *
	 * @since 0.8
	 *
	 * @return bool $fixed True if fixed, false otherwise.
	 */
	public function paypal_fixed() {

		// Always true if already fixed in CiviCRM.
		if ( $this->setting_get( 'paypal_fixed', '0' ) === '1' ) {
			return true;
		}

		// Bail if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return false;
		}

		// Only do this once.
		static $fixed;
		if ( isset( $fixed ) ) {
			return $fixed;
		}

		// Ignore anything but 5.31.1+.
		$version = CRM_Utils_System::version();
		if ( version_compare( $version, '5.31.1', '>=' ) ) {
			$fixed = true;
		} else {
			$fixed = false;
		}

		// Save setting if fixed.
		if ( $fixed ) {
			$this->setting_set( 'paypal_fixed', '1' );
			$this->settings_save();
		}

		// --<
		return $fixed;

	}

	/**
	 * Check if this version of CiviCRM predates the need for a fix.
	 *
	 * @since 0.8
	 *
	 * @return bool $predates True if CiviCRM predates the need for a fix, false otherwise.
	 */
	public function paypal_predates_problem() {

		// Bail if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return false;
		}

		// Only do this once.
		static $predates;
		if ( isset( $predates ) ) {
			return $predates;
		}

		// URLs were not encoded prior to 5.23.
		$version = CRM_Utils_System::version();
		if ( version_compare( $version, '5.23', '<' ) ) {
			$predates = true;
		} else {
			$predates = false;
		}

		// --<
		return $predates;

	}

	// -------------------------------------------------------------------------

	/**
	 * Listens for API calls and makes sure the timezone is set correctly.
	 *
	 * @since 1.0.1
	 *
	 * @param object $config The CiviCRM config object.
	 */
	public function api_timezone_sync( &$config ) {

		// Bail if disabled.
		if ( $this->setting_get( 'fix_api_timezone', '0' ) === '0' ) {
			return;
		}

		// The "$event->getActionName()" method was introduced in 5.39.0.
		$version = CRM_Utils_System::version();
		if ( version_compare( $version, '5.39.0', '<' ) ) {
			return;
		}

		// Add callback for CiviCRM "civi.api.prepare" hook.
		Civi::service( 'dispatcher' )->addListener(
			'civi.api.prepare',
			[ $this, 'api_timezone_set' ],
			-100 // Default priority.
		);

		// Add callback for CiviCRM "civi.api.respond" hook.
		Civi::service( 'dispatcher' )->addListener(
			'civi.api.respond',
			[ $this, 'api_timezone_reset' ],
			-100 // Default priority.
		);

	}

	/**
	 * Sets the timezone just before for API calls are made.
	 *
	 * @since 1.0.1
	 *
	 * @param object $event The event object.
	 * @param string $hook The hook name.
	 */
	public function api_timezone_set( $event, $hook ) {

		// Extract args for this hook.
		$action = $event->getActionName();

		// Bail if not an action that modifies the database.
		if ( ! in_array( $action, [ 'create', 'replace', 'validate', 'update', 'setvalue' ], true ) ) {
			return;
		}

		// Store current PHP timezone.
		if ( empty( $this->php_timezone ) ) {
			$this->php_timezone = date_default_timezone_get();
		}

		// Get the timezone defined by the WordPress Site.
		$site_timezone = $this->site_timezone_get();

		// Configure timezone for CiviCRM.
		if ( $site_timezone !== $this->php_timezone ) {
			// phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set
			date_default_timezone_set( $site_timezone );
			CRM_Core_Config::singleton()->userSystem->setMySQLTimeZone();
		}

	}

	/**
	 * Resets the timezone just after API calls have been made.
	 *
	 * @since 1.0.1
	 *
	 * @param object $event The event object.
	 * @param string $hook The hook name.
	 */
	public function api_timezone_reset( $event, $hook ) {

		// Extract args for this hook.
		$action = $event->getActionName();

		// Bail if not an action that modifies the database.
		if ( ! in_array( $action, [ 'create', 'replace', 'validate', 'update', 'setvalue' ], true ) ) {
			return;
		}

		// Restore current PHP timezone.
		if ( ! empty( $this->php_timezone ) ) {
			// phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set
			date_default_timezone_set( $this->php_timezone );
			$this->php_timezone = '';
		}

	}

	/**
	 * Returns the timezone string for the current site.
	 *
	 * If a timezone identifier is used, this method returns that.
	 * If an offset is used, tries to build a suitable timezone.
	 * If all else fails, uses UTC.
	 *
	 * This is a modified version of the "eo_get_blog_timezone" function in the
	 * Event Organiser plugin.
	 *
	 * @see https://github.com/stephenharris/Event-Organiser/blob/develop/includes/event-organiser-utility-functions.php#L352
	 *
	 * @since 1.0.1
	 *
	 * @return string $tzstring The site timezone string.
	 */
	public function site_timezone_get() {

		// Check our cached value first.
		$tzstring = wp_cache_get( 'civicrm_admin_utilities_timezone' );

		/**
		 * Filters the cached timezone string.
		 *
		 * @since 1.0.1
		 *
		 * @param string $tzstring The cached timezone string.
		 */
		$tzstring = apply_filters( 'civicrm_admin_utilities_timezone', $tzstring );

		// Build value if none is cached.
		if ( false === $tzstring ) {

			// Get relevant WordPress settings.
			$tzstring = get_option( 'timezone_string' );
			$offset   = get_option( 'gmt_offset' );

			/*
			 * Setting manual offsets should be discouraged.
			 *
			 * The IANA timezone database that provides PHP's timezone support
			 * uses (reversed) POSIX style signs.
			 *
			 * @see https://github.com/stephenharris/Event-Organiser/issues/287
			 * @see https://www.php.net/manual/en/timezones.others.php
			 * @see https://bugs.php.net/bug.php?id=45543
			 * @see https://bugs.php.net/bug.php?id=45528
			 */
			// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			if ( empty( $tzstring ) && 0 != $offset && floor( $offset ) == $offset ) {
				// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				$offset_string = $offset > 0 ? "-$offset" : '+' . absint( $offset );
				$tzstring      = 'Etc/GMT' . $offset_string;
			}

			// Default to 'UTC' if the timezone string is empty.
			if ( empty( $tzstring ) ) {
				$tzstring = 'UTC';
			}

			// Cache timezone string.
			wp_cache_set( 'civicrm_admin_utilities_timezone', $tzstring );

		}

		// --<
		return $tzstring;

	}

	// -------------------------------------------------------------------------

	/**
	 * Adds the callback for Afform Angular modules when required.
	 *
	 * @since 1.0.7
	 */
	public function afform_scripts() {

		/**
		 * Filters the hook through which loading of Angular modules is done.
		 *
		 * Some themes do not fire the "get_header" action. If that is the case, use this
		 * filter to return a suitable substitute hook, e.g. "wp_head".
		 *
		 * @since 1.0.7
		 *
		 * @param string $hook The default hook name. Default is 'get_header'.
		 */
		$hook = apply_filters( 'civicrm_admin_utilities_afform_hook', 'get_header' );

		// Add Afform Angular modules when required.
		add_action( $hook, [ $this, 'afform_scripts_load' ] );

	}

	/**
	 * Adds the Afform Angular modules when required.
	 *
	 * @since 1.0.7
	 */
	public function afform_scripts_load() {

		// Get the saved Afforms.
		$afforms = $this->setting_get( 'afforms', [] );
		if ( empty( $afforms ) ) {
			return;
		}

		// Bail if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return;
		}

		// Add the Angular modules for these Afforms.
		foreach ( $afforms as $afform ) {
			\Civi::service( 'angularjs.loader' )->addModules( $afform );
		}

		// Add CiviCRM callback if not already added.
		if ( ! has_action( 'wp_enqueue_scripts', [ civi_wp(), 'front_end_page_load' ] ) ) {
			add_action( 'wp_enqueue_scripts', [ civi_wp(), 'front_end_page_load' ], 100 );
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Utility for tracing calls to hook_civicrm_pre.
	 *
	 * @since 0.5.4 Moved from plugin class.
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function trace_pre( $op, $object_name, $object_id, $object_ref ) {

		$e     = new Exception();
		$trace = $e->getTraceAsString();
		$log   = [
			'method'      => __METHOD__,
			'op'          => $op,
			'object_name' => $object_name,
			'object_id'   => $object_id,
			'object_ref'  => $object_ref,
			'backtrace'   => $trace,
		];
		$this->plugin->log_error( $log );

	}

	/**
	 * Utility for tracing calls to hook_civicrm_post.
	 *
	 * @since 0.5.4 Moved from plugin class.
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function trace_post( $op, $object_name, $object_id, $object_ref ) {

		$e     = new Exception();
		$trace = $e->getTraceAsString();
		$log   = [
			'method'      => __METHOD__,
			'op'          => $op,
			'object_name' => $object_name,
			'object_id'   => $object_id,
			'object_ref'  => $object_ref,
			'backtrace'   => $trace,
		];
		$this->plugin->log_error( $log );

	}

	/**
	 * Utility for tracing calls to hook_civicrm_postProcess.
	 *
	 * @since 0.5.4 Moved from plugin class.
	 *
	 * @param string $form_name The name of the form.
	 * @param object $form The form object.
	 */
	public function trace_post_process( $form_name, &$form ) {

		$e     = new Exception();
		$trace = $e->getTraceAsString();
		$log   = [
			'method'    => __METHOD__,
			'form_name' => $form_name,
			'form'      => $form,
			'backtrace' => $trace,
		];
		$this->plugin->log_error( $log );

	}

	// -------------------------------------------------------------------------

	/**
	 * Get default settings values for this plugin.
	 *
	 * @since 0.5.4
	 *
	 * @return array $settings The default values for this plugin.
	 */
	public function settings_get_defaults() {

		// Init return.
		$settings = [];

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

		// Add Shortcuts Menu to admin bar.
		$settings['admin_bar'] = '1';

		// Do not hide "Manage Groups" menu item from Shortcuts Menu.
		$settings['admin_bar_groups'] = '0';

		// Init post types with defaults.
		$settings['post_types'] = [ 'post', 'page' ];

		// Fix API timezone by default.
		$settings['fix_api_timezone'] = '1';

		// List of Afforms outside content.
		$settings['afforms'] = [];

		/**
		 * Filter default settings.
		 *
		 * @since 0.5.4
		 *
		 * @param array $settings The array of default settings.
		 */
		$settings = apply_filters( 'civicrm_admin_utilities_settings_default', $settings );

		// --<
		return $settings;

	}

	/**
	 * Route settings updates to relevant methods.
	 *
	 * @since 0.5.4
	 */
	public function settings_update_router() {

		// Was the "Settings" form submitted?
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['civicrm_admin_utilities_settings_submit'] ) ) {
			$this->settings_update();
			$this->settings_update_redirect();
		}

	}

	/**
	 * Form redirection handler.
	 *
	 * @since 1.0.1
	 */
	public function settings_update_redirect() {

		// Get the Site Settings Page URL.
		$url = $this->page_submit_url_get();

		// Our array of arguments.
		$args = [ 'updated' => 'true' ];

		// Redirect to our Settings Page.
		wp_safe_redirect( add_query_arg( $args, $url ) );
		exit;

	}

	/**
	 * Update options supplied by our Settings admin page.
	 *
	 * @since 0.5.4
	 * @since 0.5.4 Moved from admin class and made site-specific.
	 */
	public function settings_update() {

		// Check that we trust the source of the data.
		check_admin_referer( 'civicrm_admin_utilities_settings_action', 'civicrm_admin_utilities_settings_nonce' );

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
		$flush_cache          = isset( $_POST[ $prefix . 'cache' ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $prefix . 'cache' ] ) ) : 0;

		// Retrieve Post Types array.
		$post_types = filter_input( INPUT_POST, $prefix . 'post_types', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( empty( $post_types ) ) {
			$post_types = [];
		}

		// Retrieve Afforms array.
		$afforms = filter_input( INPUT_POST, $prefix . 'afforms', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( empty( $afforms ) ) {
			$afforms = [];
		}

		// Init force cache-clearing flag.
		$force = false;

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

		// Get existing menu setting.
		$existing_menu = $this->setting_get( 'prettify_menu', '0' );
		if ( $menu !== (int) $existing_menu ) {
			$force = true;
		}

		// Did we ask to prettify the menu?
		if ( 1 === $menu ) {
			$this->setting_set( 'prettify_menu', '1' );
		} else {
			$this->setting_set( 'prettify_menu', '0' );
		}

		// Get existing Admin Theme setting.
		$existing_theme = $this->setting_get( 'css_admin', '0' );
		if ( $styles_admin !== (int) $existing_theme ) {
			$force = true;
		}

		// Did we ask to override CiviCRM Default styleheet?
		if ( 1 === $styles_admin ) {
			$this->setting_set( 'css_admin', '1' );

			/**
			 * Broadcast a change in Theme.
			 *
			 * @since 0.7.4
			 *
			 * @param str Identifies the action taken.
			 */
			do_action( 'civicrm_admin_utilities_styles_admin', 'enable' );

		} else {
			$this->setting_set( 'css_admin', '0' );

			/**
			 * Broadcast a change in Theme.
			 *
			 * @since 0.7.4
			 *
			 * @param str Identifies the action taken.
			 */
			do_action( 'civicrm_admin_utilities_styles_admin', 'disable' );

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

		// Which Form Builder forms are we auto-loading?
		if ( ! empty( $afforms ) ) {

			// Sanitise array.
			array_walk(
				$afforms,
				function( &$item ) {
					$item = sanitize_text_field( wp_unslash( $item ) );
				}
			);

			// Set option.
			$this->setting_set( 'afforms', $afforms );

		} else {
			$this->setting_set( 'afforms', [] );
		}

		// Save options.
		$this->settings_save();

		// Clear caches if asked to - or if forced to do so.
		if ( $flush_cache || $force ) {
			$this->clear_caches();
		}

		/**
		 * Broadcast that the settings update process is finished.
		 *
		 * @since 0.8.1
		 */
		do_action( 'civicrm_admin_utilities_single_settings_updated' );

	}

	// -------------------------------------------------------------------------

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
	public function setting_exists( $setting_name ) {

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
	 * @param mixed  $default The default value if the setting does not exist.
	 * @return mixed The setting or the default.
	 */
	public function setting_get( $setting_name, $default = false ) {

		// Get setting.
		return array_key_exists( $setting_name, $this->settings ) ? $this->settings[ $setting_name ] : $default;

	}

	/**
	 * Sets a value for a specified setting.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class and made site-specific.
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
	 * @since 0.5.4 Moved from admin class and made site-specific.
	 *
	 * @param string $setting_name The name of the setting.
	 */
	public function setting_delete( $setting_name ) {

		// Unset setting.
		unset( $this->settings[ $setting_name ] );

	}

	// -------------------------------------------------------------------------

	/**
	 * Test existence of a specified option.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class and made site-specific.
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
	 * Return a value for a specified option.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved from admin class and made site-specific.
	 *
	 * @param str $option_name The name of the option.
	 * @param str $default The default value of the option if it has no value.
	 * @return mixed $value the value of the option.
	 */
	public function option_get( $option_name, $default = false ) {

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
	 * @param str   $option_name The name of the option.
	 * @param mixed $value The value to set the option to.
	 * @return bool $success True if the value of the option was successfully updated.
	 */
	public function option_set( $option_name, $value = '' ) {

		// Update option.
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
	public function option_delete( $option_name ) {

		// Delete option.
		return delete_option( $option_name );

	}

}
