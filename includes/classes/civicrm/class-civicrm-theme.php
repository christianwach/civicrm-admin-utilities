<?php
/**
 * Theme Class.
 *
 * Handles a RiverLea compatible Theme called "Wellow Brook".
 *
 * @package CiviCRM_Admin_Utilities
 * @since 1.1.2
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Theme Class.
 *
 * A class that encapsulates the Wellow Brook theme.
 *
 * @since 1.1.2
 */
class CAU_CiviCRM_Theme {

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
	 * Theme "slug".
	 *
	 * @since 1.1.2
	 * @access public
	 * @var string
	 */
	public $slug = 'wellowbrook';

	/**
	 * CiviCRM meets requirements flag.
	 *
	 * @since 1.1.2
	 * @access public
	 * @var bool
	 */
	public $version_okay = false;

	/**
	 * RiverLea enabled flag.
	 *
	 * @since 1.1.2
	 * @access public
	 * @var bool
	 */
	public $riverlea = false;

	/**
	 * Constructor.
	 *
	 * @since 1.1.2
	 *
	 * @param CAU_CiviCRM $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Cannot be installed if WordPress does not meet requirements.
		if ( ! $this->wp_version_okay() ) {
			return;
		}

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
		$this->include_files();
		$this->register_hooks();

		// We're done.
		$done = true;

	}

	/**
	 * Include files.
	 *
	 * @since 1.1.2
	 */
	private function include_files() {

		// Load our Resolver class.
		require CIVICRM_ADMIN_UTILITIES_PATH . 'includes/classes/civicrm/class-civicrm-theme-resolver.php';

	}

	/**
	 * Register hooks.
	 *
	 * @since 1.1.2
	 */
	private function register_hooks() {

		// Toggle theme activation.
		add_action( 'cau/theme/wellow/enabled', [ $this, 'wellowbrook_enable' ] );
		add_action( 'cau/theme/wellow/disabled', [ $this, 'wellowbrook_disable' ] );

		// Implement theme when installed.
		add_action( 'civicrm_themes', [ $this, 'register_theme' ], 10 );
		add_action( 'civicrm_alterBundle', [ $this, 'modify_bundle' ], 100, 1 );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Checks if the system meets requirements.
	 *
	 * @since 1.1.2
	 *
	 * @return bool $is_capable True if the system meets requirements, false otherwise.
	 */
	public function is_capable() {

		// Not capable if WordPress does not meet requirements.
		if ( ! $this->wp_version_okay() ) {
			return false;
		}

		// Not capable if CiviCRM does not meet requirements.
		if ( ! $this->civicrm_version_okay() ) {
			return false;
		}

		// Okay, we're good.
		return true;

	}

	/**
	 * Checks if WordPress meets requirements.
	 *
	 * @since 1.1.2
	 *
	 * @return bool $version_okay True if WordPress meets requirements, false otherwise.
	 */
	public function wp_version_okay() {

		// Init return.
		$version_okay = false;

		// Cannot be installed if WordPress is less than 7.0.
		global $wp_version;
		if ( ! version_compare( $wp_version, '7.0', '>=' ) ) {
			$version_okay = false;
		} else {
			$version_okay = true;
		}

		// --<
		return $version_okay;

	}

	/**
	 * Checks if CivCRM meets requirements.
	 *
	 * @since 1.1.2
	 *
	 * @return bool $version_okay True if CivCRM meets requirements, false otherwise.
	 */
	public function civicrm_version_okay() {

		// No need to check more than once.
		if ( true === $this->version_okay ) {
			return true;
		}

		// Cannot be called during "civicrm_config".
		if ( doing_action( 'civicrm_config' ) ) {
			return false;
		}

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Set flag based on whether CiviCRM meets requirements.
		$version = CRM_Utils_System::version();
		if ( version_compare( $version, '6.14.1', '>=' ) ) {
			$this->version_okay = true;
		} else {
			$this->version_okay = false;
		}

		// --<
		return $this->version_okay;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Checks if RiverLea is enabled.
	 *
	 * @since 1.1.2
	 *
	 * @return bool $riverlea True if RiverLea is enabled, false otherwise.
	 */
	public function riverlea_enabled() {

		// No need to check more than once.
		if ( true === $this->riverlea ) {
			return true;
		}

		// Cannot be called during "civicrm_config".
		if ( doing_action( 'civicrm_config' ) ) {
			return false;
		}

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Set flag based on RiverLea whether is enabled.
		if ( $this->civicrm->extension_is_enabled( 'riverlea' ) ) {
			$this->riverlea = true;
		} else {
			$this->riverlea = false;
		}

		// --<
		return $this->riverlea;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets the active backend theme key.
	 *
	 * @since 1.1.2
	 *
	 * @return string|bool $theme_key The active theme key, or false on failure.
	 */
	public function theme_active_get() {

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Using CiviCRM service includes the "civicrm_activeTheme" hook.
		$theme_key = Civi::service( 'themes' )->getActiveThemeKey();

		// --<
		return $theme_key;

	}

	/**
	 * Sets the active backend theme key.
	 *
	 * @since 1.1.2
	 *
	 * @param string $theme_key The theme key to set as the active backend theme.
	 */
	public function theme_active_set( $theme_key ) {

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Set the backend theme directly.
		Civi::settings()->set( 'theme_backend', $theme_key );

	}

	/**
	 * Enables or disables our Theme.
	 *
	 * @since 1.1.2
	 *
	 * @param string $action The action to perform  - either 'enable' or 'disable'.
	 */
	public function theme_active_toggle( $action = 'enable' ) {

		// Bail if CiviCRM does not meet requirements.
		if ( ! $this->civicrm_version_okay() ) {
			return false;
		}

		// Bail if Wellow Brook is being enabled and RiverLea is not enabled.
		if ( 'enable' === $action && ! $this->riverlea_enabled() ) {
			return false;
		}

		// Bail when disabling and the backend Theme is not Wellow Brook.
		$theme = $this->theme_active_get();
		if ( 'disable' === $action && $theme !== $this->slug ) {
			return;
		}

		// Set the backend theme.
		$theme_key = 'enable' === $action ? $this->slug : 'default';
		$this->theme_active_set( $theme_key );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Enables the Wellow Brook theme.
	 *
	 * @since 1.1.2
	 *
	 * @return array|bool $stream The array of RiverLea Theme data, or false on failure.
	 */
	public function wellowbrook_enable() {

		// Enable RiverLea if not already enabled.
		if ( empty( $this->civicrm->extension_is_enabled( 'riverlea' ) ) ) {
			if ( ! $this->civicrm->extension_enable( 'riverlea' ) ) {
				return false;
			}
		}

		// Install Wellow Brook.
		$stream = $this->wellowbrook_install();

		// Make Wellow Brook the active theme.
		$this->theme_active_toggle( 'enable' );

		// --<
		return $stream;

	}

	/**
	 * Disables the Wellow Brook theme.
	 *
	 * @since 1.1.2
	 *
	 * @return int|bool $stream_id The ID of the deleted RiverLea Theme, or false on failure.
	 */
	public function wellowbrook_disable() {

		// Uninstall Wellow Brook.
		$stream_id = $this->wellowbrook_uninstall();

		/*
		// Disable RiverLea if not already disabled.
		if ( ! empty( $this->civicrm->extension_is_enabled( 'riverlea' ) ) ) {
			if ( ! $this->civicrm->extension_disable( 'riverlea' ) ) {
				return false;
			}
		}
		*/

		// Make CiviCRM default the active theme.
		$this->theme_active_toggle( 'disable' );

		// --<
		return $stream_id;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Checks if Wellow Brook is installed.
	 *
	 * @since 1.1.2
	 *
	 * @return array|bool $theme The array of RiverLea Theme data, or false if not found.
	 */
	public function wellowbrook_installed() {

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Cannot be installed if CiviCRM does not meet requirements.
		if ( ! $this->civicrm_version_okay() ) {
			return false;
		}

		// Cannot be installed if RiverLea is not enabled.
		if ( ! $this->riverlea_enabled() ) {
			return false;
		}

		try {

			$result = \Civi\Api4\RiverleaStream::get( false )
				->addSelect( '*' )
				->addWhere( 'name', '=', $this->slug )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== (int) $result->count() ) {
			return false;
		}

		// We only need the first item.
		$theme = $result->first();

		// --<
		return $theme;

	}

	/**
	 * Installs the Wellow Brook theme.
	 *
	 * @since 1.1.2
	 *
	 * @return array|bool $stream The array of RiverLea Theme data, or false on failure.
	 */
	public function wellowbrook_install() {

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Cannot be installed if CiviCRM does not meet requirements.
		if ( ! $this->civicrm_version_okay() ) {
			return false;
		}

		// Cannot be installed if RiverLea is not enabled.
		if ( ! $this->riverlea_enabled() ) {
			return false;
		}

		// Skip if already installed.
		$stream = $this->wellowbrook_installed();
		if ( $stream ) {
			return $stream;
		}

		try {

			$result = \Civi\Api4\RiverleaStream::create( false )
				->addValue( 'name', $this->slug )
				->addValue( 'label', __( 'Wellow Brook (CAU)', 'civicrm-admin-utilities' ) )
				->addValue( 'description', __( 'Gives CiviCRM a look-and-feel that is closer to WordPress 7.0+ admin screens.', 'civicrm-admin-utilities' ) )
				->addValue( 'is_reserved', true )
				->addValue( 'extension', 'riverlea' )
				->addValue( 'file_prefix', 'streams/' . $this->slug . '/' )
				->addValue( 'css_file', '_main.css' )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== (int) $result->count() ) {
			return false;
		}

		// We only need the first item.
		$stream = $result->first();

		// --<
		return $stream;

	}

	/**
	 * Uninstalls the Wellow Brook theme.
	 *
	 * @since 1.1.2
	 *
	 * @return int|bool $stream_id The ID of the deleted RiverLea Theme, true if already deleted, or false on failure.
	 */
	public function wellowbrook_uninstall() {

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Cannot be uninstalled if RiverLea is not enabled.
		if ( ! $this->riverlea_enabled() ) {
			return false;
		}

		// Skip if not installed.
		$stream = $this->wellowbrook_installed();
		if ( empty( $stream ) ) {
			return true;
		}

		try {

			$result = \Civi\Api4\RiverleaStream::delete( false )
				->addWhere( 'name', '=', $this->slug )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== (int) $result->count() ) {
			return false;
		}

		// We only need the first item.
		$stream = $result->first();

		// We only need the ID.
		$stream_id = $stream['id'] ?? false;
		if ( false !== $stream_id ) {
			$stream_id = (int) $stream_id;
		}

		// --<
		return $stream_id;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Register our Theme.
	 *
	 * @since 1.1.2
	 *
	 * @param array $themes The array of themes.
	 */
	public function register_theme( &$themes ) {

		// Cannot be registered if Wellow Brook is not installed.
		if ( ! $this->wellowbrook_installed() ) {
			return;
		}

		// Add setup to themes array.
		$themes[ $this->slug ] = [
			'ext'          => $this->slug,
			'title'        => __( 'Wellow Brook (CAU)', 'civicrm-admin-utilities' ),
			'help'         => __( 'Gives CiviCRM a look-and-feel that is closer to WordPress', 'civicrm-admin-utilities' ),
			'url_callback' => 'CAU_CiviCRM_Theme_Resolver::resolve',
			'search_order' => [
				$this->slug,
				'_riverlea_core_',
				'_fallback_',
			],
		];

	}

	/**
	 * Maybe modify a bundle.
	 *
	 * @since 1.1.2
	 *
	 * @param CRM_Core_Resources_Bundle $bundle The bundle of Theme resources.
	 */
	public function modify_bundle( CRM_Core_Resources_Bundle $bundle ) {

		// Ignore unless Wellow Brook is installed.
		if ( ! $this->wellowbrook_installed() ) {
			return;
		}

		// Ignore unless Wellow Brook is enabled.
		$theme = Civi::service( 'themes' )->getActiveThemeKey();
		if ( 'wellowbrook' !== $theme ) {
			return;
		}

		// Ignore unless requesting Core Resources.
		if ( 'coreStyles' !== $bundle->name ) {
			return;
		}

		// Define version.
		$version = '?version=' . CIVICRM_ADMIN_UTILITIES_VERSION;
		if ( false !== CIVICRM_ADMIN_UTILITIES_DEBUG && defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
			$version .= '-' . time();
		}

		// Init lowest weight.
		$weight = 300;

		// Define files to include.
		$files = [
			'main',
			'tables',
			'forms',
			'buttons',
			'feedback',
			'tabs',
			'dialogs',
			'metaboxes',
		];

		// Include them in turn.
		foreach ( $files as $file ) {

			// Build filename.
			$filename = '_' . $file . '-wp.css' . $version;

			// Build snippet.
			$snippet = [
				'styleUrl' => CIVICRM_ADMIN_UTILITIES_URL . 'assets/civicrm/streams/' . $this->slug . '/css/' . $filename,
				'weight'   => $weight++,
				'region'   => 'html-header',
			];

			// Add CiviCRM stylesheet.
			$bundle->add( $snippet );

		}

	}

}
