<?php
/**
 * CiviCRM Menu Class.
 *
 * Handles enqueuing styles to make the CiviCRM Menu compatible with WordPress.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 1.1.2
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Menu Class.
 *
 * A class that encapsulates making the CiviCRM Menu compatible with WordPress.
 *
 * @since 1.1.2
 */
class CAU_CiviCRM_Menu {

	/**
	 * Plugin object.
	 *
	 * @since 1.1.2
	 * @access public
	 * @var CiviCRM_Admin_Utilities
	 */
	public $plugin;

	/**
	 * CiviCRM object.
	 *
	 * @since 1.1.2
	 * @access public
	 * @var CAU_CiviCRM
	 */
	public $civicrm;

	/**
	 * Constructor.
	 *
	 * @since 1.1.2
	 *
	 * @param CAU_CiviCRM $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Store references.
		$this->civicrm = $parent;
		$this->plugin  = $parent->plugin;

		// Initialise when the CiviCRM class is loaded.
		add_action( 'cau/class/civicrm/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialise this object.
	 *
	 * @since 1.1.2
	 */
	public function initialise() {

		// Only do this once.
		static $done;
		if ( isset( $done ) && true === $done ) {
			return;
		}

		// Register hooks.
		$this->register_hooks();

		// We're done.
		$done = true;

	}

	/**
	 * Register hooks.
	 *
	 * @since 1.1.2
	 */
	private function register_hooks() {

		// Enqueue styles for CiviCRM Menu.
		add_action( 'admin_print_styles', [ $this, 'menu_css_enqueue' ] );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Determines if the Keyboard Accessible Menu Extension is being used.
	 *
	 * @since 0.4.3
	 * @since 0.5.4 Moved from plugin class.
	 * @since 1.1.2 Moved to menu class.
	 *
	 * @return bool True if KAM Extension is active, false otherwise.
	 */
	public function kam_is_active() {

		// Get the CiviCRM version.
		$civicrm_version = $this->civicrm->version_get();

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

		// Bail if no KAM function.
		if ( ! function_exists( 'kam_civicrm_coreResourceList' ) ) {
			return false;
		}

		// The KAM Extension must be present.
		return true;

	}

	/**
	 * Enqueues the CiviCRM Menu stylesheet.
	 *
	 * @since 1.1.2
	 */
	public function menu_css_enqueue() {

		// Bail if disabled.
		if ( $this->plugin->single->setting_get( 'prettify_menu', '0' ) === '0' ) {
			return;
		}

		// Enqueue different styles per major version of WordPress.
		$wp_major_version = $this->plugin->wordpress->wp_version_major_get();

		// Set default pre-KAM CSS file.
		$css = 'civicrm-admin-utilities-menu.css';

		// Use specific CSS file for KAM if active.
		if ( $this->kam_is_active() ) {
			if ( '6.9' === $wp_major_version ) {
				// WordPress 6.9 only.
				$css = 'civicrm-admin-utilities-kam-6-9-plus.css';
			} elseif ( '7.0' === $wp_major_version ) {
				// WordPress 7.0 only.
				$css = 'civicrm-admin-utilities-kam-7-plus.css';
			} elseif ( version_compare( $wp_major_version, '7.0.99999', '>' ) ) {
				// WordPress 7.1+.
				$css = 'civicrm-admin-utilities-kam-7-1-plus.css';
			} else {
				// Versions up to WordPress 6.9.
				$css = 'civicrm-admin-utilities-kam.css';
			}
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

}
