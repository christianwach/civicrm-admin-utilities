<?php
/**
 * CiviCRM Admin Utilities
 *
 * Plugin Name:       CiviCRM Admin Utilities
 * Description:       Optionally modifies CiviCRM's behaviour and appearance in single site and multisite installs.
 * Plugin URI:        https://github.com/christianwach/civicrm-admin-utilities
 * GitHub Plugin URI: https://github.com/christianwach/civicrm-admin-utilities
 * Version:           1.0.9a
 * Author:            Christian Wach
 * Author URI:        https://haystack.co.uk
 * Text Domain:       civicrm-admin-utilities
 * Domain Path:       /languages
 *
 * @package CiviCRM_Admin_Utilities
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Set our version here.
define( 'CIVICRM_ADMIN_UTILITIES_VERSION', '1.0.9a' );

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
	 * @var object
	 */
	public $single;

	/**
	 * Single Site Users object.
	 *
	 * @since 0.9
	 * @access public
	 * @var object
	 */
	public $single_users;

	/**
	 * CiviCRM Theme object.
	 *
	 * @since 0.7.4
	 * @access public
	 * @var object
	 */
	public $theme;

	/**
	 * Multisite WordPress object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var object
	 */
	public $multisite;

	/**
	 * Multidomain CiviCRM object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var object
	 */
	public $multidomain;

	/**
	 * CiviCRM object.
	 *
	 * @since 1.0.9
	 * @access public
	 * @var CAU_CiviCRM
	 */
	public $civicrm;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Initialise after CiviCRM.
		add_action( 'plugins_loaded', [ $this, 'initialise' ], 20 );

	}

	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.3.4
	 */
	public function initialise() {

		// Only do this once.
		static $done;
		if ( isset( $done ) && true === $done ) {
			return;
		}

		// ALways enable translation.
		add_action( 'init', [ $this, 'enable_translation' ] );

		// Bail if CiviCRM plugin is not present.
		if ( ! function_exists( 'civi_wp' ) ) {
			return;
		}

		// Bail if CiviCRM is not fully installed.
		if ( ! defined( 'CIVICRM_INSTALLED' ) || ! CIVICRM_INSTALLED ) {
			return;
		}

		// Bootstrap plugin.
		$this->include_files();
		$this->setup_objects();
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
	private function include_files() {

		// Load our common classes.
		require CIVICRM_ADMIN_UTILITIES_PATH . 'includes/classes/civicrm/class-civicrm.php';

		// Load our admin utility classes.
		require CIVICRM_ADMIN_UTILITIES_PATH . 'includes/admin/class-cau-admin-batch.php';
		require CIVICRM_ADMIN_UTILITIES_PATH . 'includes/admin/class-cau-admin-stepper.php';
		require CIVICRM_ADMIN_UTILITIES_PATH . 'includes/admin/class-cau-page-settings-base.php';

		// Load our Single Site classes.
		require CIVICRM_ADMIN_UTILITIES_PATH . 'includes/civicrm-admin-utilities-single.php';
		require CIVICRM_ADMIN_UTILITIES_PATH . 'includes/civicrm-admin-utilities-single-users.php';

		// Load our Theme "Extension" class.
		require CIVICRM_ADMIN_UTILITIES_PATH . 'assets/civicrm/cautheme/civicrm-admin-utilities-theme.php';

		// Load our Multisite and Multidomain classes.
		if ( is_multisite() ) {
			require CIVICRM_ADMIN_UTILITIES_PATH . 'includes/civicrm-admin-utilities-multisite.php';
			require CIVICRM_ADMIN_UTILITIES_PATH . 'includes/admin/multidomain/class-admin-multidomain-loader.php';
		}

	}

	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.3.4
	 */
	private function setup_objects() {

		// Always instantiate common classes.
		$this->civicrm = new CAU_CiviCRM( $this );

		// Always instantiate Single Site classes.
		$this->single       = new CiviCRM_Admin_Utilities_Single( $this );
		$this->single_users = new CiviCRM_Admin_Utilities_Single_Users( $this );

		// Always instantiate Theme class.
		$this->theme = new CiviCRM_Admin_Utilities_Theme( $this );

		// Maybe instantiate Multisite classes.
		if ( is_multisite() ) {
			$this->multisite   = new CiviCRM_Admin_Utilities_Multisite( $this );
			$this->multidomain = new CAU_Admin_Multidomain_Loader( $this );
		}

	}

	/**
	 * Register hooks.
	 *
	 * If global-scope hooks are needed, add them here.
	 *
	 * @since 0.1
	 * @since 0.5.4 All hooks moved to relevant classes.
	 */
	private function register_hooks() {

	}

	/**
	 * Load translation files.
	 *
	 * @since 0.1
	 */
	public function enable_translation() {

		// Enable translation.
		// phpcs:ignore WordPress.WP.DeprecatedParameters.Load_plugin_textdomainParam2Found
		load_plugin_textdomain(
			'civicrm-admin-utilities', // Unique name.
			false, // Deprecated argument.
			dirname( plugin_basename( __FILE__ ) ) . '/languages/' // Relative path to files.
		);

	}

	// -------------------------------------------------------------------------

	/**
	 * Perform plugin activation tasks.
	 *
	 * @since 0.8
	 */
	public function activate() {

	}

	/**
	 * Perform plugin deactivation tasks.
	 *
	 * @since 0.8
	 */
	public function deactivate() {

		// Maybe init.
		$this->initialise();

		// Maybe deactivate our CiviCRM Theme.
		if ( ! empty( $this->theme ) ) {
			$this->theme->deactivate_theme();
		}

	}

	// -------------------------------------------------------------------------

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

		// If not Multisite, it cannot be.
		if ( ! is_multisite() ) {
			$is_network_active = false;
			return $is_network_active;
		}

		// Make sure plugin file is included when outside admin.
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		// Get path from 'plugins' directory to this plugin.
		$this_plugin = plugin_basename( CIVICRM_ADMIN_UTILITIES_FILE );

		// Test if network active.
		$is_network_active = is_plugin_active_for_network( $this_plugin );

		// --<
		return $is_network_active;

	}

	/**
	 * Write to the error log.
	 *
	 * @since 1.0.5
	 *
	 * @param array $data The data to write to the log file.
	 */
	public function log_error( $data = [] ) {

		// Skip if not debugging.
		if ( ! defined( 'WP_DEBUG' ) || false === WP_DEBUG ) {
			return;
		}

		// Skip if empty.
		if ( empty( $data ) ) {
			return;
		}

		// Format data.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$error = print_r( $data, true );

		// Write to log file.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $error );

	}

}

// Init plugin.
global $civicrm_admin_utilities;
$civicrm_admin_utilities = new CiviCRM_Admin_Utilities();

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

// Activation.
register_activation_hook( __FILE__, [ civicrm_au(), 'activate' ] );

// Deactivation.
register_deactivation_hook( __FILE__, [ civicrm_au(), 'deactivate' ] );

/*
 * Uninstall uses the 'uninstall.php' method.
 * @see https://developer.wordpress.org/reference/functions/register_uninstall_hook/
 */

/**
 * Utility to add link to settings page.
 *
 * @since 0.3
 *
 * @param array $links The existing links array.
 * @param str   $file The name of the plugin file.
 * @return array $links The modified links array.
 */
function civicrm_admin_utilities_action_links( $links, $file ) {

	// Bail if CiviCRM plugin is not present.
	if ( ! function_exists( 'civi_wp' ) ) {
		return $links;
	}

	// Add links only when CiviCRM is fully installed.
	if ( ! defined( 'CIVICRM_INSTALLED' ) ) {
		return $links;
	}
	if ( ! CIVICRM_INSTALLED ) {
		return $links;
	}

	// Add settings link.
	if ( plugin_basename( dirname( __FILE__ ) . '/civicrm-admin-utilities.php' ) === $file ) {

		// Add settings link if network activated and viewing network admin.
		if ( civicrm_au()->is_network_activated() && is_network_admin() ) {
			$link    = add_query_arg( [ 'page' => 'cau_network_parent' ], network_admin_url( 'settings.php' ) );
			$links[] = '<a href="' . esc_url( $link ) . '">' . esc_html__( 'Settings', 'civicrm-admin-utilities' ) . '</a>';
		}

		// Add settings link if not network activated and not viewing network admin.
		if ( ! civicrm_au()->is_network_activated() && ! is_network_admin() ) {
			$link    = add_query_arg( [ 'page' => 'cau_parent' ], admin_url( 'admin.php' ) );
			$links[] = '<a href="' . esc_url( $link ) . '">' . esc_html__( 'Settings', 'civicrm-admin-utilities' ) . '</a>';
		}

		// Always add Paypal link.
		$paypal  = 'https://www.paypal.me/interactivist';
		$links[] = '<a href="' . $paypal . '" target="_blank">' . __( 'Donate!', 'civicrm-admin-utilities' ) . '</a>';

	}

	// --<
	return $links;

}

// Add filters for the above.
add_filter( 'network_admin_plugin_action_links', 'civicrm_admin_utilities_action_links', 10, 2 );
add_filter( 'plugin_action_links', 'civicrm_admin_utilities_action_links', 10, 2 );
