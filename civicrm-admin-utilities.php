<?php /*
--------------------------------------------------------------------------------
Plugin Name: CiviCRM Admin Utilities
Plugin URI: http://haystack.co.uk
Description: Custom code to modify CiviCRM's behaviour
Author: Christian Wach
Version: 0.2.2
Author URI: http://haystack.co.uk
Text Domain: civicrm-admin-utilities
Domain Path: /languages
Depends: CiviCRM
--------------------------------------------------------------------------------
*/



// set our version here
define( 'CIVICRM_ADMIN_UTILITIES_VERSION', '0.2.2' );

// store reference to this file
if ( !defined( 'CIVICRM_ADMIN_UTILITIES_FILE' ) ) {
	define( 'CIVICRM_ADMIN_UTILITIES_FILE', __FILE__ );
}

// store URL to this plugin's directory
if ( !defined( 'CIVICRM_ADMIN_UTILITIES_URL' ) ) {
	define( 'CIVICRM_ADMIN_UTILITIES_URL', plugin_dir_url( CIVICRM_ADMIN_UTILITIES_FILE ) );
}
// store PATH to this plugin's directory
if ( !defined( 'CIVICRM_ADMIN_UTILITIES_PATH' ) ) {
	define( 'CIVICRM_ADMIN_UTILITIES_PATH', plugin_dir_path( CIVICRM_ADMIN_UTILITIES_FILE ) );
}



/**
 * Class definition
 */

class CiviCRM_Admin_Utilities {



	/**
	 * Properties
	 */

	// Admin class
	public $admin;



	/**
	 * Initialises this object
	 *
	 * @return object
	 */
	function __construct() {

		// load our Admin utility class
		require( CIVICRM_ADMIN_UTILITIES_PATH . 'civicrm-admin-utilities-admin.php' );

		// instantiate
		$this->admin = new CiviCRM_Admin_Utilities_Admin();

		// use translation files
		add_action( 'plugins_loaded', array( $this, 'enable_translation' ) );

		// add actions for plugin init on CiviCRM init
		add_action( 'civicrm_instance_loaded', array( $this, 'register_civi_hooks' ) );

		// --<
		return $this;

	}



	/**
	 * Do stuff on plugin activation
	 *
	 * @return void
	 */
	public function activate() {

		// admin stuff that needs to be done on activation
		$this->admin->activate();

	}



	/**
	 * Do stuff on plugin deactivation
	 *
	 * @return void
	 */
	public function deactivate() {

		// admin stuff that needs to be done on deactivation
		$this->admin->deactivate();

	}



	/**
	 * Load translation files
	 *
	 * @return void
	 */
	public function enable_translation() {

		// there are no translations as yet, here for completeness
		load_plugin_textdomain(

			// unique name
			'civicrm-admin-utilities',

			// deprecated argument
			false,

			// relative path to directory containing translation files
			dirname( plugin_basename( __FILE__ ) ) . '/languages/'

		);

	}



	//##########################################################################



	/**
	 * Register hooks on CiviCRM plugin init
	 *
	 * @return void
	 */
	public function register_civi_hooks() {

		// kill CiviCRM shortcode button
		add_action( 'admin_head', array( $this, 'kill_civi_button' ) );

		// allow plugins to register php and template directories
		add_action( 'civicrm_config', array( $this, 'register_directories' ), 10, 1 );

		// run after the CiviCRM menu hook has been registered
		add_action( 'init', array( $this, 'civicrm_only_on_main_site_please' ) );

		// admin style tweaks
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

	}



	/**
	 * Register directories that CiviCRM searches for php and template files
	 *
	 * @param object $config The CiviCRM config object
	 * @return void
	 */
	public function register_directories( &$config ) {

		// bail if disabled
		if ( $this->admin->setting_get( 'prettify_menu' ) == '0' ) return;

		// define our custom path
		$custom_path = CIVICRM_ADMIN_UTILITIES_PATH . 'civicrm_custom_templates';

		// kick out if no CiviCRM
		if ( ! $this->admin->is_active() ) return;

		// get template instance
		$template = CRM_Core_Smarty::singleton();

		// add our custom template directory
		$template->addTemplateDir( $custom_path );

		// register template directories
		$template_include_path = $custom_path . PATH_SEPARATOR . get_include_path();
		set_include_path( $template_include_path );

	}



	/**
	 * Admin style tweaks
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts() {

		// bail if disabled
		if ( $this->admin->setting_get( 'prettify_menu' ) == '0' ) return;

		// add custom stylesheet
		wp_enqueue_style(

			'civicrm_admin_utilities_admin_tweaks',
			plugins_url( 'civicrm-admin-utilities.css', CIVICRM_ADMIN_UTILITIES_FILE ),
			false,
			CIVICRM_ADMIN_UTILITIES_VERSION, // version
			'all' // media

		);

	}



	/**
	 * Do not load the CiviCRM shortcode button unless we explicitly enable it
	 *
	 * @return void
	 */
	public function kill_civi_button() {

		// get screen
		$screen = get_current_screen();

		// get chosen post types
		$selected_types = $this->admin->setting_get( 'post_types' );

		// is this a post type we want to allow the button on?
		if ( ! in_array( $screen->post_type, $selected_types ) ) {

			// remove
			$this->civi_button_remove();

		}

	}



	/**
	 * Prevent the loading of the CiviCRM shortcode button
	 *
	 * @return void
	 */
	public function civi_button_remove() {

		// get Civi object
		$civi = civi_wp();

		// do we have the modal object?
		if ( isset( $civi->modal ) AND is_object( $civi->modal ) ) {

			// not chosen, so remove Civi's actions
			remove_action( 'media_buttons_context', array( $civi->modal, 'add_form_button' ) );
			remove_action( 'media_buttons', array( $civi->modal, 'add_form_button' ), 100 );
			remove_action( 'admin_enqueue_scripts', array( $civi->modal, 'add_form_button_js' ) );
			remove_action( 'admin_footer', array( $civi->modal, 'add_form_button_html' ) );

		} else {

			// not chosen, so remove Civi's actions
			remove_action( 'media_buttons_context', array( $civi, 'add_form_button' ) );
			remove_action( 'media_buttons', array( $civi, 'add_form_button' ), 100 );
			remove_action( 'admin_enqueue_scripts', array( $civi, 'add_form_button_js' ) );
			remove_action( 'admin_footer', array( $civi, 'add_form_button_html' ) );

		}

	}



	/**
	 * Do not load the CiviCRM on sites other than the main site
	 *
	 * @return void
	 */
	public function civicrm_only_on_main_site_please() {

		// bail if disabled
		if ( $this->admin->setting_get( 'main_site_only' ) == '0' ) return;

		// if not on main site
		if ( is_multisite() AND ! is_main_site() ) {

			// unhook menu item, but allow Civi to load
			remove_action( 'admin_menu', array( civi_wp(), 'add_menu_items' ) );

			// remove CiviCRM shortcode button
			add_action( 'admin_head', array( $this, 'civi_button_remove' ) );

		}

	}



} // class ends



// init plugin
global $civicrm_admin_utilities;
$civicrm_admin_utilities = new CiviCRM_Admin_Utilities;

// activation
register_activation_hook( __FILE__, array( $civicrm_admin_utilities, 'activate' ) );

// deactivation
register_deactivation_hook( __FILE__, array( $civicrm_admin_utilities, 'deactivate' ) );

// uninstall will use the 'uninstall.php' method when fully built
// see: http://codex.wordpress.org/Function_Reference/register_uninstall_hook



