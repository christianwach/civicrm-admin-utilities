<?php /*
--------------------------------------------------------------------------------
Plugin Name: CiviCRM Admin Utilities
Plugin URI: https://github.com/christianwach/civicrm-admin-utilities
Description: Optionally modifies CiviCRM's behaviour and appearance in single site and multisite installs.
Author: Christian Wach
Version: 0.5.4
Author URI: http://haystack.co.uk
Text Domain: civicrm-admin-utilities
Domain Path: /languages
Depends: CiviCRM
--------------------------------------------------------------------------------
*/



// Set our version here
define( 'CIVICRM_ADMIN_UTILITIES_VERSION', '0.5.3' );

// Trigger logging of 'civicrm_pre' and 'civicrm_post'
if ( ! defined( 'CIVICRM_ADMIN_UTILITIES_DEBUG' ) ) {
	define( 'CIVICRM_ADMIN_UTILITIES_DEBUG', false );
}

// Store reference to this file
if ( ! defined( 'CIVICRM_ADMIN_UTILITIES_FILE' ) ) {
	define( 'CIVICRM_ADMIN_UTILITIES_FILE', __FILE__ );
}

// Store URL to this plugin's directory
if ( ! defined( 'CIVICRM_ADMIN_UTILITIES_URL' ) ) {
	define( 'CIVICRM_ADMIN_UTILITIES_URL', plugin_dir_url( CIVICRM_ADMIN_UTILITIES_FILE ) );
}
// Store PATH to this plugin's directory
if ( ! defined( 'CIVICRM_ADMIN_UTILITIES_PATH' ) ) {
	define( 'CIVICRM_ADMIN_UTILITIES_PATH', plugin_dir_path( CIVICRM_ADMIN_UTILITIES_FILE ) );
}



/**
 * CiviCRM Admin Utilities Class.
 *
 * A class that encapsulates plugin functionality.
 *
 * @since 0.1
 */
class CiviCRM_Admin_Utilities {

	/**
	 * Admin object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $admin The admin object.
	 */
	public $admin;



	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Enable translation
		add_action( 'plugins_loaded', array( $this, 'enable_translation' ) );

		// Initialise
		add_action( 'plugins_loaded', array( $this, 'initialise' ) );

	}



	/**
	 * Load translation files.
	 *
	 * @since 0.1
	 */
	public function enable_translation() {

		// Enable translation
		load_plugin_textdomain(
			'civicrm-admin-utilities', // Unique name
			false, // Deprecated argument
			dirname( plugin_basename( __FILE__ ) ) . '/languages/' // Relative path to files
		);

	}



	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.3.4
	 */
	public function initialise() {

		// Init only when CiviCRM is fully installed
		if ( ! defined( 'CIVICRM_INSTALLED' ) ) return;
		if ( ! CIVICRM_INSTALLED ) return;

		// Include files
		$this->include_files();

		// Set up objects and references
		$this->setup_objects();

		// Finally, register hooks
		$this->register_hooks();

	}



	/**
	 * Include files.
	 *
	 * @since 0.3.4
	 */
	public function include_files() {

		// Only do this once
		static $done;
		if ( isset( $done ) AND $done === true ) return;

		// Load our Admin utility class
		require( CIVICRM_ADMIN_UTILITIES_PATH . 'includes/civicrm-admin-utilities-admin.php' );

		// We're done
		$done = true;

	}



	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.3.4
	 */
	public function setup_objects() {

		// Only do this once
		static $done;
		if ( isset( $done ) AND $done === true ) return;

		// Initialise objects
		$this->admin = new CiviCRM_Admin_Utilities_Admin();

		// We're done
		$done = true;

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.1
	 */
	public function register_hooks() {

		// Bail if CiviCRM plugin is not present
		if ( ! function_exists( 'civi_wp' ) ) return;

		// Kill CiviCRM shortcode button
		add_action( 'admin_head', array( $this, 'kill_civi_button' ) );

		// Register template directory for menu amends
		add_action( 'civicrm_config', array( $this, 'register_menu_directory' ), 10, 1 );

		// Run after the CiviCRM menu hook has been registered
		add_action( 'init', array( $this, 'civicrm_only_on_main_site_please' ) );

		// Style tweaks for CiviCRM
		add_action( 'admin_print_styles', array( $this, 'admin_scripts_enqueue' ) );

		// Add admin bar item
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_add' ), 2000 );

		// Filter the WordPress Permissions Form
		add_action( 'civicrm_config', array( $this, 'register_access_directory' ), 10, 1 );
		add_action( 'civicrm_buildForm', array( $this, 'fix_permissions_form' ), 10, 2 );

		// Hook in just before CiviCRM does to disable resources
		add_action( 'admin_head', array( $this, 'resources_disable' ), 9 );
		add_action( 'wp_head', array( $this, 'resources_disable' ), 9 );

		// If the debugging flag is set
		if ( CIVICRM_ADMIN_UTILITIES_DEBUG === true ) {

			// Log pre and post database operations
			add_action( 'civicrm_pre', array( $this, 'trace_pre' ), 10, 4 );
			add_action( 'civicrm_post', array( $this, 'trace_post' ), 10, 4 );
			add_action( 'civicrm_postProcess', array( $this, 'trace_postProcess' ), 10, 2 );

		}

		/**
		 * Broadcast that this plugin is now loaded.
		 *
		 * @since 0.3.4
		 */
		do_action( 'civicrm_admin_utilities_loaded' );

	}



	//##########################################################################



	/**
	 * Register directory that CiviCRM searches for the menu template file.
	 *
	 * @since 0.3.2
	 *
	 * @param object $config The CiviCRM config object.
	 */
	public function register_menu_directory( &$config ) {

		// Bail if disabled
		if ( $this->admin->setting_get( 'prettify_menu', '0' ) == '0' ) return;

		// Kick out if no CiviCRM
		if ( ! $this->admin->civicrm_is_active() ) return;

		// Get template instance
		$template = CRM_Core_Smarty::singleton();

		// Get current version
		$version = CRM_Utils_System::version();

		// Define our custom path based on CiviCRM version
		if ( version_compare( $version, '5.5', '>=' ) ) {
			$custom_path = CIVICRM_ADMIN_UTILITIES_PATH . 'civicrm_nav_template';
		} else {
			$custom_path = CIVICRM_ADMIN_UTILITIES_PATH . 'civicrm_custom_templates';
		}

		// Add our custom template directory
		$template->addTemplateDir( $custom_path );

		// Register template directories
		$template_include_path = $custom_path . PATH_SEPARATOR . get_include_path();
		set_include_path( $template_include_path );

	}



	/**
	 * Register directory that CiviCRM searches for the WordPress Access Control template file.
	 *
	 * @since 0.3.2
	 *
	 * @param object $config The CiviCRM config object.
	 */
	public function register_access_directory( &$config ) {

		// Bail if disabled
		if ( $this->admin->setting_get( 'prettify_access', '0' ) == '0' ) return;

		// Kick out if no CiviCRM
		if ( ! $this->admin->civicrm_is_active() ) return;

		// Bail if CiviCRM has been fixed
		if ( $this->admin->access_form_fixed() ) return;

		// Get template instance
		$template = CRM_Core_Smarty::singleton();

		// Define our custom path
		$custom_path = CIVICRM_ADMIN_UTILITIES_PATH . 'civicrm_access_templates';

		// Add our custom template directory
		$template->addTemplateDir( $custom_path );

		// Register template directories
		$template_include_path = $custom_path . PATH_SEPARATOR . get_include_path();
		set_include_path( $template_include_path );

	}



	/**
	 * Admin style tweaks.
	 *
	 * @since 0.1
	 */
	public function admin_scripts_enqueue() {

		// Bail if disabled
		if ( $this->admin->setting_get( 'prettify_menu', '0' ) == '1' ) {

			// Set default CSS file
			$css = 'civicrm-admin-utilities-menu.css';

			// Use specific CSS file for Shoreditch if active
			if ( $this->shoreditch_is_active() ) {

				// But not when prettifying CiviCRM admin
				if ( $this->admin->setting_get( 'css_admin', '0' ) == '0' ) {
					$css = 'civicrm-admin-utilities-shoreditch.css';
				}

			}

			// Use specific CSS file for KAM if active
			if ( $this->kam_is_active() ) {
				$css = 'civicrm-admin-utilities-kam.css';
			}

			// Add menu stylesheet
			wp_enqueue_style(
				'civicrm_admin_utilities_admin_tweaks',
				plugins_url( 'assets/css/' . $css, CIVICRM_ADMIN_UTILITIES_FILE ),
				null,
				CIVICRM_ADMIN_UTILITIES_VERSION, // Version
				'all' // Media
			);

		}

		// Maybe load core override stylesheet
		if ( $this->admin->setting_get( 'css_admin', '0' ) == '1' ) {

			// Add core override stylesheet
			wp_enqueue_style(
				'civicrm_admin_utilities_admin_override',
				plugins_url( 'assets/css/civicrm-admin-utilities-admin.css', CIVICRM_ADMIN_UTILITIES_FILE ),
				null,
				CIVICRM_ADMIN_UTILITIES_VERSION, // Version
				'all' // Media
			);

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
	 */
	public function resources_disable() {

		// Kick out if no CiviCRM
		if ( ! $this->admin->civicrm_is_active() ) return;

		// Only on back-end
		if ( is_admin() ) {

			// Maybe disable core stylesheet
			if ( $this->admin->setting_get( 'css_admin', '0' ) == '1' ) {

				// Disable core stylesheet
				$this->resource_disable( 'civicrm', 'css/civicrm.css' );

				// Also disable Shoreditch if present
				if ( $this->shoreditch_is_active() ) {
					$this->resource_disable( 'org.civicrm.shoreditch', 'css/custom-civicrm.css' );
				}

			}

			// Maybe disable custom stylesheet (not provided by Shoreditch)
			if ( $this->admin->setting_get( 'css_custom_public', '0' ) == '1' ) {
				$this->custom_css_disable();
			}

		// Only on front-end
		} else {

			// Maybe disable core stylesheet
			if ( $this->admin->setting_get( 'css_default', '0' ) == '1' ) {
				$this->resource_disable( 'civicrm', 'css/civicrm.css' );
			}

			// Maybe disable navigation stylesheet (there's no menu on the front-end)
			if ( $this->admin->setting_get( 'css_navigation', '0' ) == '1' ) {
				$this->resource_disable( 'civicrm', 'css/civicrmNavigation.css' );
			}

			// If Shoreditch present
			if ( $this->shoreditch_is_active() ) {

				// Maybe disable Shoreditch stylesheet
				if ( $this->admin->setting_get( 'css_shoreditch', '0' ) == '1' ) {
					$this->resource_disable( 'org.civicrm.shoreditch', 'css/custom-civicrm.css' );
				}

				// Maybe disable Shoreditch Bootstrap stylesheet
				if ( $this->admin->setting_get( 'css_bootstrap', '0' ) == '1' ) {
					$this->resource_disable( 'org.civicrm.shoreditch', 'css/bootstrap.css' );
				}

			} else {

				// Maybe disable custom stylesheet (not provided by Shoreditch)
				if ( $this->admin->setting_get( 'css_custom', '0' ) == '1' ) {
					$this->custom_css_disable();
				}

			}

		}

	}



	/**
	 * Disable a resource enqueued by CiviCRM.
	 *
	 * @since 0.4.1
	 *
	 * @param str $extension The name of the extension e.g. 'org.civicrm.shoreditch'. Default is CiviCRM core.
	 * @param str $file The relative path to the resource. Default is CiviCRM core stylesheet.
	 */
	public function resource_disable( $extension = 'civicrm', $file = 'css/civicrm.css' ) {

		// Kick out if no CiviCRM
		if ( ! $this->admin->civicrm_is_active() ) return;

		// Get the resource URL
		$url = $this->resource_get_url( $extension, $file );

		// Kick out if not enqueued
		if ( $url === false ) return;

		// Set to disabled
		CRM_Core_Region::instance('html-header')->update( $url, array( 'disabled' => TRUE ) );

	}



	/**
	 * Get the URL of a resource if it is enqueued by CiviCRM.
	 *
	 * @since 0.4.3
	 *
	 * @param str $extension The name of the extension e.g. 'org.civicrm.shoreditch'. Default is CiviCRM core.
	 * @param str $file The relative path to the resource. Default is CiviCRM core stylesheet.
	 * @return bool|str $url The URL if the resource is enqueued, false otherwise.
	 */
	public function resource_get_url( $extension = 'civicrm', $file = 'css/civicrm.css' ) {

		// Kick out if no CiviCRM
		if ( ! $this->admin->civicrm_is_active() ) return false;

		// Get registered URL
		$url = CRM_Core_Resources::singleton()->getUrl( $extension, $file, TRUE );

		// Get registration data from region
		$registration = CRM_Core_Region::instance( 'html-header' )->get( $url );

		// Bail if not registered
		if ( empty( $registration ) ) return false;

		// Is enqueued
		return $url;

	}



	/**
	 * Disable any custom CSS file enqueued by CiviCRM.
	 *
	 * @since 0.4.2
	 */
	public function custom_css_disable() {

		// Kick out if no CiviCRM
		if ( ! $this->admin->civicrm_is_active() ) return;

		// Get CiviCRM config
		$config = CRM_Core_Config::singleton();

		// Bail if there's no custom CSS file
		if ( empty( $config->customCSSURL ) ) return;

		// Get registered URL
		$url = CRM_Core_Resources::singleton()->addCacheCode( $config->customCSSURL );

		// Get registration data from region
		$registration = CRM_Core_Region::instance('html-header')->get( $url );

		// Bail if not registered
		if ( empty ( $registration ) ) return;

		// Set to disabled
		CRM_Core_Region::instance('html-header')->update( $url, array( 'disabled' => TRUE ) );

	}



	/**
	 * Determine if the Shoreditch CSS file is being used.
	 *
	 * @since 0.3.4
	 *
	 * @return bool $shoreditch True if Shoreditch CSS file is used, false otherwise.
	 */
	public function shoreditch_is_active() {

		// Assume not
		$shoreditch = false;

		// Init CiviCRM
		if ( ! $this->admin->civicrm_is_active() ) return $shoreditch;

		// Get the current Custom CSS URL
		$config = CRM_Core_Config::singleton();

		// Has the Shoreditch CSS been activated?
		if ( strstr( $config->customCSSURL, 'org.civicrm.shoreditch' ) !== false ) {

			// Shoreditch CSS is active
			$shoreditch = true;

		}

		// --<
		return $shoreditch;

	}



	/**
	 * Determine if the Keyboard Accessible Menu Extension is being used.
	 *
	 * @since 0.4.3
	 *
	 * @return bool $kam True if KAM Extension is active, false otherwise.
	 */
	public function kam_is_active() {

		// Init return
		$kam = false;

		// Kick out if no CiviCRM
		if ( ! $this->admin->civicrm_is_active() ) return $kam;

		// Kick out if no KAM function
		if ( ! function_exists( 'kam_civicrm_coreResourceList' ) ) return $kam;

		// KAM is present
		$kam = true;

		// --<
		return $kam;

	}



	/**
	 * Do not load the CiviCRM shortcode button unless we explicitly enable it.
	 *
	 * @since 0.1
	 */
	public function kill_civi_button() {

		// Get screen
		$screen = get_current_screen();

		// Prevent warning if screen not defined
		if ( empty( $screen ) ) return;

		// Bail if there's no post type
		if ( empty( $screen->post_type ) ) return;

		// Get chosen post types
		$selected_types = $this->admin->setting_get( 'post_types', array() );

		// Remove button if this is not a post type we want to allow the button on
		if ( ! in_array( $screen->post_type, $selected_types ) ) {
			$this->civi_button_remove();
		}

	}



	/**
	 * Prevent the loading of the CiviCRM shortcode button.
	 *
	 * @since 0.1
	 */
	public function civi_button_remove() {

		// Get Civi object
		$civi = civi_wp();

		// Do we have the modal object?
		if ( isset( $civi->modal ) AND is_object( $civi->modal ) ) {

			// Remove current CiviCRM actions
			remove_action( 'media_buttons_context', array( $civi->modal, 'add_form_button' ) );
			remove_action( 'media_buttons', array( $civi->modal, 'add_form_button' ), 100 );
			remove_action( 'admin_enqueue_scripts', array( $civi->modal, 'add_form_button_js' ) );
			remove_action( 'admin_footer', array( $civi->modal, 'add_form_button_html' ) );

			// Also remove core resources
			remove_action( 'admin_head', array( $civi, 'wp_head' ), 50 );
			remove_action( 'load-post.php', array( $civi->modal, 'add_core_resources' ) );
			remove_action( 'load-post-new.php', array( $civi->modal, 'add_core_resources' ) );
			remove_action( 'load-page.php', array( $civi->modal, 'add_core_resources' ) );
			remove_action( 'load-page-new.php', array( $civi->modal, 'add_core_resources' ) );

		} else {

			// Remove legacy CiviCRM actions
			remove_action( 'media_buttons_context', array( $civi, 'add_form_button' ) );
			remove_action( 'media_buttons', array( $civi, 'add_form_button' ), 100 );
			remove_action( 'admin_enqueue_scripts', array( $civi, 'add_form_button_js' ) );
			remove_action( 'admin_footer', array( $civi, 'add_form_button_html' ) );

		}

	}



	/**
	 * Do not load CiviCRM on sites other than the main site.
	 *
	 * @since 0.1
	 */
	public function civicrm_only_on_main_site_please() {

		// Bail if disabled
		if ( $this->admin->setting_get( 'main_site_only', '0' ) == '0' ) return;

		// If not on main site
		if ( is_multisite() AND ! is_main_site() ) {

			// Unhook menu item, but allow Civi to load
			remove_action( 'admin_menu', array( civi_wp(), 'add_menu_items' ) );

			// Remove CiviCRM shortcode button
			add_action( 'admin_head', array( $this, 'civi_button_remove' ) );

			// Remove notice
			remove_action( 'admin_notices', array( civi_wp(), 'show_setup_warning' ) );

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
	 * The compromise made here is to default to switching to the main site
	 * and offer a filter for developers to override this plugin's behaviour.
	 *
	 * @since 0.3
	 */
	public function admin_bar_add() {

		// Bail if admin bar not enabled
		if ( $this->admin->setting_get( 'admin_bar', '0' ) == '0' ) return;

		// Bail if user cannot access CiviCRM
		if ( ! current_user_can( 'access_civicrm' ) ) return;

		/**
		 * Filter the switch-to-blog process for the menu.
		 *
		 * Note to developers: if you have enabled CiviCRM on subsites in your
		 * multisite install, use the following code to disable the switch:
		 *
		 * add_filter( 'civicrm_admin_utilities_menu_switch', __return_false );
		 *
		 * If you need more granular control over whether to switch to the main
		 * site or not, create a callback method and inspect the $current_site
		 * object for whether the appropriate conditions are met.
		 *
		 * @since 0.3
		 */
		$switch = apply_filters( 'civicrm_admin_utilities_menu_switch', true );

		// If it's multisite, then switch to main site
		$switch_back = false;
		if ( is_multisite() AND ! is_main_site() AND $switch ) {

			// Bail if CiviCRM is disabled on subsites
			if ( $this->admin->setting_get( 'main_site_only', '0' ) == '1' ) return;

			// Get current site data
			$current_site = get_current_site();

			// Switch to the main site and set flag
			switch_to_blog( $current_site->blog_id );
			$switch_back = true;

		}

		// Access admin bar
		global $wp_admin_bar;

		// Init CiviCRM or bail
		if ( ! $this->admin->civicrm_is_active() ) return;

		// Get component info
		$components = CRM_Core_Component::getEnabledComponents();

		// Define a menu parent ID
		$id = 'civicrm-admin-utils';

		// Add parent
		$wp_admin_bar->add_menu( array(
			'id' => $id,
			'title' => __( 'CiviCRM', 'civicrm-admin-utilities' ),
			'href' => admin_url( 'admin.php?page=CiviCRM' ),
		) );

		// Dashboard
		$wp_admin_bar->add_menu( array(
			'id' => 'cau-1',
			'parent' => $id,
			'title' => __( 'CiviCRM Dashboard', 'civicrm-admin-utilities' ),
			'href' => admin_url( 'admin.php?page=CiviCRM' ),
		) );

		// Search
		$wp_admin_bar->add_menu( array(
			'id' => 'cau-2',
			'parent' => $id,
			'title' => __( 'Advanced Search', 'civicrm-admin-utilities' ),
			'href' => $this->get_link( 'civicrm/contact/search/advanced', 'reset=1' ),
		) );

		// Groups
		$wp_admin_bar->add_menu( array(
			'id' => 'cau-3',
			'parent' => $id,
			'title' => __( 'Manage Groups', 'civicrm-admin-utilities' ),
			'href' => $this->get_link( 'civicrm/group', 'reset=1' ),
		) );

		// Contributions
		if ( array_key_exists( 'CiviContribute', $components ) ) {
			if ( $this->check_permission( 'access CiviContribute' ) ) {
				$wp_admin_bar->add_menu( array(
					'id' => 'cau-4',
					'parent' => $id,
					'title' => __( 'Contribution Dashboard', 'civicrm-admin-utilities' ),
					'href' => $this->get_link( 'civicrm/contribute', 'reset=1' ),
				) );
			}
		}

		// Membership
		if ( array_key_exists( 'CiviMember', $components ) ) {
			if ( $this->check_permission( 'access CiviMember' ) ) {
				$wp_admin_bar->add_menu( array(
					'id' => 'cau-5',
					'parent' => $id,
					'title' => __( 'Membership Dashboard', 'civicrm-admin-utilities' ),
					'href' => $this->get_link( 'civicrm/member', 'reset=1' ),
				) );
			}
		}

		// Events
		if ( array_key_exists( 'CiviEvent', $components ) ) {
			if ( $this->check_permission( 'access CiviEvent' ) ) {
				$wp_admin_bar->add_menu( array(
					'id' => 'cau-6',
					'parent' => $id,
					'title' => __( 'Events Dashboard', 'civicrm-admin-utilities' ),
					'href' => $this->get_link( 'civicrm/event', 'reset=1' ),
				) );
			}
		}

		// Mailings
		if ( array_key_exists( 'CiviMail', $components ) ) {
			if ( $this->check_permission( 'access CiviMail' ) ) {
				$wp_admin_bar->add_menu( array(
					'id' => 'cau-7',
					'parent' => $id,
					'title' => __( 'Mailings Sent and Scheduled', 'civicrm-admin-utilities' ),
					'href' => $this->get_link( 'civicrm/mailing/browse/scheduled', 'reset=1&scheduled=true' ),
				) );
			}
		}

		// Reports
		if ( array_key_exists( 'CiviReport', $components ) ) {
			if ( $this->check_permission( 'access CiviReport' ) ) {
				$wp_admin_bar->add_menu( array(
					'id'     => 'cau-8',
					'parent' => $id,
					'title'  => __( 'Report Listing', 'civicrm-admin-utilities' ),
					'href'   => $this->get_link( 'civicrm/report/list', '&reset=1' ),
				) );
			}
		}

		// Cases
		if ( array_key_exists( 'CiviCase', $components ) ) {
			if ( CRM_Case_BAO_Case::accessCiviCase() ) {
				$wp_admin_bar->add_menu( array(
					'id' => 'cau-9',
					'parent' => $id,
					'title' => __( 'Cases Dashboard', 'civicrm-admin-utilities' ),
					'href' => $this->get_link( 'civicrm/case', 'reset=1' ),
				) );
			}
		}

		// Admin console
		if ( $this->check_permission( 'administer CiviCRM' ) ) {
			$wp_admin_bar->add_menu( array(
				'id' => 'cau-10',
				'parent' => $id,
				'title' => __( 'Admin Console', 'civicrm-admin-utilities' ),
				'href' => $this->get_link( 'civicrm/admin', 'reset=1' ),
			) );
		}

		/**
		 * Fire action so that others can manipulate this menu.
		 *
		 * @since 0.3
		 *
		 * @param bool $switch Whether or not a switch to the main site has been made
		 */
		do_action( 'civicrm_admin_utilities_menu_after', $switch );

		// If it's multisite, then switch back to current blog
		if ( $switch_back ) {
			restore_current_blog();
		}

	}



	/**
	 * Get a CiviCRM admin link.
	 *
	 * @since 0.3
	 *
	 * @param str $path The CiviCRM path.
	 * @param str $params The CiviCRM parameters.
	 * @return string $link The URL of the CiviCRM page.
	 */
	public function get_link( $path = '', $params = null ) {

		// Init link
		$link = '';

		// Init CiviCRM or bail
		if ( ! $this->admin->civicrm_is_active() ) return $link;

		// Use CiviCRM to construct link
		$link = CRM_Utils_System::url(
			$path,
			$params,
			TRUE,
			NULL,
			TRUE,
			FALSE,
			TRUE
		);

		// --<
		return $link;

	}



	/**
	 * Check a CiviCRM permission.
	 *
	 * @since 0.3
	 *
	 * @param str $permission The permission string.
	 * @return bool $permitted True if allowed, false otherwise.
	 */
	public function check_permission( $permission ) {

		// Always deny if CiviCRM is not active
		if ( ! $this->admin->civicrm_is_active() ) return false;

		// Deny by default
		$permitted = false;

		// Check CiviCRM permissions
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
	 *
	 * @param string $formName The name of the form.
	 * @param CRM_Core_Form $form The form object.
	 */
	public function fix_permissions_form( $formName, &$form ) {

		// Bail if disabled
		if ( $this->admin->setting_get( 'prettify_access', '0' ) == '0' ) return;

		// Bail if CiviCRM has been fixed
		if ( $this->admin->access_form_fixed() ) return;

		// Bail if not the form we want
		if ( $formName != 'CRM_ACL_Form_WordPress_Permissions' ) return;

		// Get vars
		$vars = $form->get_template_vars();

		// Bail if $permDesc does not exist
		if ( ! isset( $vars['permDesc'] ) ) return;

		// Build replacement for permDesc array
		foreach( $vars['rolePerms'] AS $role => $perms ) {
			foreach( $perms AS $name => $title ) {
				$permissions[$name] = $title;
			}
		}

		// Build array keyed by permission
		$table = array();
		foreach( $permissions AS $perm => $label ) {

			// Init row with permission description
			$table[$perm] = array(
				'label' => $label,
				'roles' => array(),
			);

			// Add permission label and role names
			foreach( $vars['roles'] AS $key => $label ) {
				if ( isset( $vars['permDesc'][$perm] ) ) {
					$table[$perm]['desc'] = $vars['permDesc'][$perm];
				}
				$table[$perm]['roles'][] = $key;
			}

		}

		// Assign to form
		$form->assign( 'table', $table );

		// Camelcase dammit
		CRM_Utils_System::setTitle( __( 'WordPress Access Control', 'civicrm-admin-utilities' ) );

	}



	//##########################################################################



	/**
	 * Utility for tracing calls to hook_civicrm_pre.
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



} // Class ends



// Init plugin
global $civicrm_admin_utilities;
$civicrm_admin_utilities = new CiviCRM_Admin_Utilities;

/**
 * Utility to get a reference to this plugin.
 *
 * @since 0.3.4
 *
 * @return object $civicrm_admin_utilities The plugin reference.
 */
function civicrm_au() {

	// Return instance
	global $civicrm_admin_utilities;
	return $civicrm_admin_utilities;

}



// Uninstall will use the 'uninstall.php' method when fully built
// See: http://codex.wordpress.org/Function_Reference/register_uninstall_hook



/**
 * Utility to add link to settings page.
 *
 * @since 0.3
 *
 * @param array $links The existing links array.
 * @param str $file The name of the plugin file.
 * @return array $links The modified links array.
 */
function civicrm_admin_utilities_action_links( $links, $file ) {

	// Add links only when CiviCRM is fully installed
	if ( ! defined( 'CIVICRM_INSTALLED' ) ) return $links;
	if ( ! CIVICRM_INSTALLED ) return $links;

	// Add settings link
	if ( $file == plugin_basename( dirname( __FILE__ ) . '/civicrm-admin-utilities.php' ) ) {

		// Add settings link if network activated and viewing network admin
		if ( civicrm_au()->admin->is_network_activated() AND is_network_admin() ) {
			$link = add_query_arg( array( 'page' => 'civicrm_admin_utilities' ), network_admin_url( 'settings.php' ) );
			$links[] = '<a href="' . esc_url( $link ) . '">' . esc_html__( 'Settings', 'civicrm-admin-utilities' ) . '</a>';
		}

		// Add settings link if not network activated and not viewing network admin
		if ( ! civicrm_au()->admin->is_network_activated() AND ! is_network_admin() ) {
			$link = add_query_arg( array( 'page' => 'civicrm_admin_utilities' ), admin_url( 'options-general.php' ) );
			$links[] = '<a href="' . esc_url( $link ) . '">' . esc_html__( 'Settings', 'civicrm-admin-utilities' ) . '</a>';
		}

		// Always add Paypal link
		$paypal = 'https://www.paypal.me/interactivist';
		$links[] = '<a href="' . $paypal . '" target="_blank">' . __( 'Donate!', 'civicrm-admin-utilities' ) . '</a>';

	}

	// --<
	return $links;

}

// Add filters for the above
add_filter( 'network_admin_plugin_action_links', 'civicrm_admin_utilities_action_links', 10, 2 );
add_filter( 'plugin_action_links', 'civicrm_admin_utilities_action_links', 10, 2 );



