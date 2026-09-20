<?php
/**
 * WordPress Class.
 *
 * Handles WordPress integration.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 1.1.2
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * WordPress Class.
 *
 * This class provides WordPress integration.
 *
 * @since 1.1.2
 */
class CAU_WordPress {

	/**
	 * Plugin object.
	 *
	 * @since 1.1.2
	 * @access public
	 * @var CiviCRM_Admin_Utilities
	 */
	public $plugin;

	/**
	 * Constructor.
	 *
	 * @since 1.1.2
	 *
	 * @param CiviCRM_Admin_Utilities $plugin The plugin object.
	 */
	public function __construct( $plugin ) {

		// Store reference.
		$this->plugin = $plugin;

		// Boot when plugin is loaded.
		add_action( 'civicrm_admin_utilities_loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialises this object.
	 *
	 * @since 1.1.2
	 */
	public function initialise() {

		// Only do this once.
		static $done;
		if ( isset( $done ) && true === $done ) {
			return;
		}

		// Bootstrap this class.
		$this->include_files();
		$this->setup_objects();

		/**
		 * Fires when this class is loaded.
		 *
		 * @since 1.1.2
		 */
		do_action( 'cau/class/civicrm/loaded' );

		// We're done.
		$done = true;

	}

	/**
	 * Include files.
	 *
	 * @since 1.1.2
	 */
	private function include_files() {}

	/**
	 * Set up objects.
	 *
	 * @since 1.1.2
	 */
	private function setup_objects() {}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets the WordPress version.
	 *
	 * @since 1.1.2
	 *
	 * @return string $version The WordPress version.
	 */
	public function wp_version_get() {

		// Try and get the true WordPress version.
		if ( function_exists( 'wp_get_wp_version' ) ) {
			$version = wp_get_wp_version();
		} else {
			// Fall back to global which may be modified.
			global $wp_version;
			$version = $wp_version;
		}

		// --<
		return $version;

	}

	/**
	 * Gets the WordPress major version.
	 *
	 * A major WordPress version is actually a dot version, i.e. 6.9, 7.0, 7.1, etc.
	 *
	 * @since 1.1.2
	 *
	 * @return string $version The WordPress major version.
	 */
	public function wp_version_major_get() {

		// Try and get the true WordPress version.
		$core_version = $this->wp_version_get();

		// Nightly build versions have two hyphens and a commit number.
		if ( preg_match( '/-\w+-\d+/', $core_version ) ) {

			// Retrieve the major version number.
			preg_match( '/^\d+.\d+/', $core_version, $major_version );
			$version = $major_version[0];

		} else {

			// Build the WordPress major version.
			$current = explode( '.', $core_version );
			$version = $current[0] . '.' . $current[1];

		}

		// --<
		return $version;

	}

}
