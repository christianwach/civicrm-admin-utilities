<?php /*
--------------------------------------------------------------------------------
Plugin Name: CiviCRM Admin Utilities
Plugin URI: https://github.com/christianwach/civicrm-admin-utilities
Description: Optionally modifies CiviCRM's behaviour and appearance in single site and multisite installs.
Author: Christian Wach
Version: 0.6.1
Author URI: http://haystack.co.uk
Text Domain: civicrm-admin-utilities
Domain Path: /languages
Depends: CiviCRM
--------------------------------------------------------------------------------
*/



// Set our version here.
define( 'CIVICRM_ADMIN_UTILITIES_VERSION', '0.6.1' );

// Trigger logging of 'civicrm_pre' and 'civicrm_post'.
if ( ! defined( 'CIVICRM_ADMIN_UTILITIES_DEBUG' ) ) {
	define( 'CIVICRM_ADMIN_UTILITIES_DEBUG', false );
}

// Store reference to this file.
if ( ! defined( 'CIVICRM_ADMIN_UTILITIES_FILE' ) ) {
	define( 'CIVICRM_ADMIN_UTILITIES_FILE', __FILE__ );
}

// Store URL to this plugin's directory.
if ( ! defined( 'CIVICRM_ADMIN_UTILITIES_URL' ) ) {
	define( 'CIVICRM_ADMIN_UTILITIES_URL', plugin_dir_url( CIVICRM_ADMIN_UTILITIES_FILE ) );
}
// Store PATH to this plugin's directory.
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
	 * Single Site WordPress object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var object $single The single site object.
	 */
	public $single;

	/**
	 * Multisite WordPress object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var object $admin The multisite object.
	 */
	public $multisite;

	/**
	 * Multidomain CiviCRM object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var object $admin The multidomain object.
	 */
	public $multidomain;



	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Initialise.
		add_action( 'plugins_loaded', array( $this, 'initialise' ) );

	}



	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.3.4
	 */
	public function initialise() {

		// Only do this once.
		static $done;
		if ( isset( $done ) AND $done === true ) return;

		// Enable translation.
		$this->enable_translation();

		// Init only when CiviCRM is fully installed.
		if ( ! defined( 'CIVICRM_INSTALLED' ) ) return;
		if ( ! CIVICRM_INSTALLED ) return;

		// Bail if CiviCRM plugin is not present.
		if ( ! function_exists( 'civi_wp' ) ) return;

		// Include files.
		$this->include_files();

		// Set up objects and references.
		$this->setup_objects();

		// Finally, register hooks.
		$this->register_hooks();

		/**
		 * Broadcast that this plugin is now loaded.
		 *
		 * @since 0.3.4
		 */
		do_action( 'civicrm_admin_utilities_loaded' );

		// We're done.
		$done = true;

	}



	/**
	 * Include files.
	 *
	 * @since 0.3.4
	 */
	public function include_files() {

		// Load our Single Site class.
		require( CIVICRM_ADMIN_UTILITIES_PATH . 'includes/civicrm-admin-utilities-single.php' );

		// Load our Multisite class.
		if ( is_multisite() ) {
			require( CIVICRM_ADMIN_UTILITIES_PATH . 'includes/civicrm-admin-utilities-multisite.php' );
			require( CIVICRM_ADMIN_UTILITIES_PATH . 'includes/civicrm-admin-utilities-multidomain.php' );
		}

	}



	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.3.4
	 */
	public function setup_objects() {

		// Always instantiate Single Site class.
		$this->single = new CiviCRM_Admin_Utilities_Single( $this );

		// Maybe instantiate Multisite classes.
		if ( is_multisite() ) {
			$this->multisite = new CiviCRM_Admin_Utilities_Multisite( $this );
			$this->multidomain = new CiviCRM_Admin_Utilities_Multidomain( $this );
		}

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.1
	 * @since 0.5.4 All hooks moved to relevant classes.
	 */
	public function register_hooks() {

		// If global-scope hooks are needed, add them here.

	}



	/**
	 * Load translation files.
	 *
	 * @since 0.1
	 */
	public function enable_translation() {

		// Enable translation
		load_plugin_textdomain(
			'civicrm-admin-utilities', // Unique name.
			false, // Deprecated argument.
			dirname( plugin_basename( __FILE__ ) ) . '/languages/' // Relative path to files.
		);

	}



	//##########################################################################



	/**
	 * Check if this plugin is network activated.
	 *
	 * @since 0.3.4
	 * @since 0.5.4 Moved to this class.
	 *
	 * @return bool $is_network_active True if network activated, false otherwise.
	 */
	public function is_network_activated() {

		// Only need to test once.
		static $is_network_active;

		// Have we done this already?
		if ( isset( $is_network_active ) ) {
			return $is_network_active;
		}

		// If not multisite, it cannot be.
		if ( ! is_multisite() ) {
			$is_network_active = false;
			return $is_network_active;
		}

		// Make sure plugin file is included when outside admin.
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		// Get path from 'plugins' directory to this plugin.
		$this_plugin = plugin_basename( CIVICRM_ADMIN_UTILITIES_FILE );

		// Test if network active.
		$is_network_active = is_plugin_active_for_network( $this_plugin );

		// --<
		return $is_network_active;

	}



	/**
	 * Check if CiviCRM is initialised.
	 *
	 * @since 0.1
	 * @since 0.5.4 Moved to this class.
	 *
	 * @return bool True if CiviCRM initialised, false otherwise.
	 */
	public function is_civicrm_initialised() {

		// Bail if no CiviCRM init function.
		if ( ! function_exists( 'civi_wp' ) ) return false;

		// Try and initialise CiviCRM.
		return civi_wp()->initialize();

	}



	/**
	 * Check if CiviCRM is network activated.
	 *
	 * @since 0.5.4
	 *
	 * @return bool $civicrm_network_active True if network activated, false otherwise.
	 */
	public function is_civicrm_network_activated() {

		// Only need to test once.
		static $civicrm_network_active;

		// Have we done this already?
		if ( isset( $civicrm_network_active ) ) {
			return $civicrm_network_active;
		}

		// If not multisite, it cannot be.
		if ( ! is_multisite() ) {
			$civicrm_network_active = false;
			return $civicrm_network_active;
		}

		// If CiviCRM's constant is not defined, we'll never know.
		if ( ! defined( 'CIVICRM_PLUGIN_FILE' ) ) {
			$civicrm_network_active = false;
			return $civicrm_network_active;
		}

		// Make sure plugin file is included when outside admin.
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		// Get path from 'plugins' directory to CiviCRM's directory.
		$civicrm = plugin_basename( CIVICRM_PLUGIN_FILE );

		// Test if network active
		$civicrm_network_active = is_plugin_active_for_network( $civicrm );

		// --<
		return $civicrm_network_active;

	}



} // Class ends.



// Init plugin.
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

	// Return instance.
	global $civicrm_admin_utilities;
	return $civicrm_admin_utilities;

}



// Uninstall uses the 'uninstall.php' method.
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

	// Add links only when CiviCRM is fully installed.
	if ( ! defined( 'CIVICRM_INSTALLED' ) ) return $links;
	if ( ! CIVICRM_INSTALLED ) return $links;

	// Bail if CiviCRM plugin is not present.
	if ( ! function_exists( 'civi_wp' ) ) return $links;

	// Add settings link.
	if ( $file == plugin_basename( dirname( __FILE__ ) . '/civicrm-admin-utilities.php' ) ) {

		// Add settings link if network activated and viewing network admin.
		if ( civicrm_au()->is_network_activated() AND is_network_admin() ) {
			$link = add_query_arg( array( 'page' => 'civicrm_admin_utilities_network_parent' ), network_admin_url( 'settings.php' ) );
			$links[] = '<a href="' . esc_url( $link ) . '">' . esc_html__( 'Settings', 'civicrm-admin-utilities' ) . '</a>';
		}

		// Add settings link if not network activated and not viewing network admin.
		if ( ! civicrm_au()->is_network_activated() AND ! is_network_admin() ) {
			$link = add_query_arg( array( 'page' => 'civicrm_admin_utilities_parent' ), admin_url( 'options-general.php' ) );
			$links[] = '<a href="' . esc_url( $link ) . '">' . esc_html__( 'Settings', 'civicrm-admin-utilities' ) . '</a>';
		}

		// Always add Paypal link.
		$paypal = 'https://www.paypal.me/interactivist';
		$links[] = '<a href="' . $paypal . '" target="_blank">' . __( 'Donate!', 'civicrm-admin-utilities' ) . '</a>';

	}

	// --<
	return $links;

}

// Add filters for the above.
add_filter( 'network_admin_plugin_action_links', 'civicrm_admin_utilities_action_links', 10, 2 );
add_filter( 'plugin_action_links', 'civicrm_admin_utilities_action_links', 10, 2 );



