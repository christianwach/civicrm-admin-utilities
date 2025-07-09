<?php
/**
 * CiviCRM Class.
 *
 * Handles CiviCRM integration.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 1.0.9
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Class.
 *
 * This class provides CiviCRM integration.
 *
 * @since 1.0.9
 */
class CAU_CiviCRM {

	/**
	 * Plugin object.
	 *
	 * @since 1.0.9
	 * @access public
	 * @var CiviCRM_Admin_Utilities
	 */
	public $plugin;

	/**
	 * UFMatch object.
	 *
	 * @since 0.6.8
	 * @access public
	 * @var CAU_CiviCRM_UFMatch
	 */
	public $ufmatch;

	/**
	 * Domain object.
	 *
	 * @since 1.0.9
	 * @access public
	 * @var CAU_CiviCRM_Domain
	 */
	public $domain;

	/**
	 * Constructor.
	 *
	 * @since 1.0.9
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
	 * @since 1.0.9
	 */
	public function initialise() {

		// Bootstrap this class.
		$this->include_files();
		$this->setup_objects();

		/**
		 * Fires when this class is loaded.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/class/civicrm/loaded' );

	}

	/**
	 * Include files.
	 *
	 * @since 1.0.9
	 */
	public function include_files() {

		// Include class files.
		require CIVICRM_ADMIN_UTILITIES_PATH . 'includes/classes/civicrm/class-civicrm-ufmatch.php';
		require CIVICRM_ADMIN_UTILITIES_PATH . 'includes/classes/civicrm/class-civicrm-domain.php';

	}

	/**
	 * Set up objects.
	 *
	 * @since 1.0.9
	 */
	public function setup_objects() {

		// Initialise objects.
		$this->ufmatch = new CAU_CiviCRM_UFMatch( $this );
		$this->domain  = new CAU_CiviCRM_Domain( $this );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Check if CiviCRM is initialised.
	 *
	 * @since 0.1
	 * @since 1.0.9 Moved to this class.
	 *
	 * @return bool True if CiviCRM initialised, false otherwise.
	 */
	public function is_initialised() {

		// Init only when CiviCRM is fully installed.
		if ( ! defined( 'CIVICRM_INSTALLED' ) ) {
			return false;
		}
		if ( ! CIVICRM_INSTALLED ) {
			return false;
		}

		// Bail if no CiviCRM init function.
		if ( ! function_exists( 'civi_wp' ) ) {
			return false;
		}

		// Try and initialise CiviCRM.
		return civi_wp()->initialize();

	}

	/**
	 * Checks if CiviCRM is network activated.
	 *
	 * @since 0.5.4
	 * @since 1.0.9 Moved to this class.
	 *
	 * @return bool $civicrm_network_active True if network activated, false otherwise.
	 */
	public function is_network_activated() {

		// Only need to test once.
		static $civicrm_network_active;

		// Have we done this already?
		if ( isset( $civicrm_network_active ) ) {
			return $civicrm_network_active;
		}

		// If not Multisite, it cannot be.
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
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		// Get path from 'plugins' directory to CiviCRM's directory.
		$civicrm = plugin_basename( CIVICRM_PLUGIN_FILE );

		// Test if network active.
		$civicrm_network_active = is_plugin_active_for_network( $civicrm );

		// --<
		return $civicrm_network_active;

	}

	/**
	 * Checks the installed version of CiviCRM.
	 *
	 * @since 1.0.9
	 *
	 * @return string|bool $version The version if CiviCRM initialised, false otherwise.
	 */
	public function version_get() {

		static $version = false;

		// Bail if no CiviCRM.
		if ( ! $this->is_initialised() ) {
			return false;
		}

		// Return early if already calculated.
		if ( false !== $version ) {
			return $version;
		}

		// Get installed CiviCRM version.
		$version = CRM_Utils_System::version();

		// --<
		return $version;

	}

	/**
	 * Gets the CiviCRM config if available.
	 *
	 * @since 1.0.9
	 *
	 * @return CRM_Core_Config|bool $config The CiviCRM config if available, false on failure.
	 */
	public function config_get() {

		// Bail if no CiviCRM.
		if ( ! $this->is_initialised() ) {
			return false;
		}

		// Let's grab the instance.
		$config = CRM_Core_Config::singleton();

		// --<
		return $config;

	}

	/**
	 * Gets the value of a CiviCRM Setting.
	 *
	 * @since 1.0.9
	 *
	 * @param string $name The name of the CiviCRM Setting.
	 * @param int    $domain_id The ID of the CiviCRM Domain.
	 * @return mixed $setting The value of the CiviCRM Setting, or false on failure.
	 */
	public function setting_get( $name, $domain_id = 0 ) {

		// Bail if no CiviCRM.
		if ( ! $this->is_initialised() ) {
			return false;
		}

		try {

			// Call the API.
			$query = \Civi\Api4\Setting::get( false )
				->addSelect( $name );

			// Maybe specify a Domain ID.
			if ( ! empty( $domain_id ) ) {
				$query->setDomainId( $domain_id );
			}

			// Call the API.
			$result = $query->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'setting'   => $name,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Return empty array if not found.
		if ( 0 === $result->count() ) {
			return false;
		}

		// The formatted result is what we're after.
		$setting_data = $result->first();

		// Sanity check.
		if ( ! array_key_exists( 'value', $setting_data ) ) {
			return false;
		}

		// Maybe convert special CiviCRM array-like format.
		$setting = $this->array_to_unpadded( $setting_data['value'] );

		// --<
		return $setting;

	}

	/**
	 * Gets the value of a CiviCRM Setting.
	 *
	 * @since 1.0.9
	 *
	 * @param string $name The name of the CiviCRM Setting.
	 * @param mixed  $value The value of the CiviCRM Setting.
	 * @param int    $domain_id The ID of the CiviCRM Domain.
	 * @return mixed $setting The array of CiviCRM Setting data, or false on failure.
	 */
	public function setting_set( $name, $value, $domain_id = 0 ) {

		// Bail if no CiviCRM.
		if ( ! $this->is_initialised() ) {
			return false;
		}

		try {

			// Call the API.
			$query = \Civi\Api4\Setting::set( false )
				->addValue( $name, $value );

			// Maybe specify a Domain ID.
			if ( ! empty( $domain_id ) ) {
				$query->setDomainId( $domain_id );
			}

			// Call the API.
			$result = $query->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'setting'   => $name,
				'value'     => $value,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Return empty array if not found.
		if ( 0 === $result->count() ) {
			return false;
		}

		// The formatted result is what we're after.
		$setting = $result->first();

		// --<
		return $setting;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Checks if a CiviCRM Component is active.
	 *
	 * @since 1.0.9
	 *
	 * @param string $component The name of the CiviCRM Component, e.g. 'CiviContribute'.
	 * @return bool $active True if the Component is active, false otherwise.
	 */
	public function component_is_enabled( $component = '' ) {

		// Bail if no CiviCRM.
		if ( ! $this->is_initialised() ) {
			return false;
		}

		// Get the Component array. CiviCRM handles caching.
		$components = CRM_Core_Component::getEnabledComponents();

		// Check for active Component.
		$active = array_key_exists( $component, $components );

		// --<
		return $active;

	}

	/**
	 * Gets the data for a given CiviCRM Extension.
	 *
	 * @since 1.0.9
	 *
	 * @param string $extension_key The fully qualified name (key) of the CiviCRM Extension, e.g. "org.civicoop.emailapi".
	 * @param string $status The status of the CiviCRM Extension.
	 * @return array|bool $extension The array of Extension data, an empty array if not found, or false on error.
	 */
	public function extension_get( $extension_key = '', $status = '' ) {

		// Bail if no CiviCRM.
		if ( ! $this->is_initialised() ) {
			return false;
		}

		try {

			// Build common query.
			$query = \Civi\Api4\Extension::get( false )
				->addSelect( '*' )
				->addWhere( 'key', '=', $extension_key )
				->setLimit( 1 );

			// Maybe add status.
			if ( ! empty( $status ) ) {
				$query->addWhere( 'status', '=', $status );
			}

			// Call the API.
			$result = $query->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'extension' => $extension_key,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Return empty array if not found.
		if ( 0 === $result->count() ) {
			return [];
		}

		// The first result is what we're after.
		$extension = $result->first();

		// --<
		return $extension;

	}

	/**
	 * Checks if an Extension is installed and enabled.
	 *
	 * @since 1.0.9
	 *
	 * @param string $extension_key The fully qualified name (key) of the CiviCRM Extension, e.g. "org.civicoop.emailapi".
	 * @return array|bool $extension The array of Extension data if enabled, an empty array if not enabled, or false on error.
	 */
	public function extension_is_enabled( $extension_key = '' ) {

		// Find out if the Extension is installed.
		$extension = $this->extension_get( $extension_key, 'installed' );

		// --<
		return $extension;

	}

	/**
	 * Gets the array of CiviCRM Extensions.
	 *
	 * @since 1.0.9
	 *
	 * @param string $status The status of the CiviCRM Extension.
	 * @return array $extensions The array of enabled Extensions, an empty array if none found, or false on error.
	 */
	public function extensions_get( $status = '' ) {

		// Bail if no CiviCRM.
		if ( ! $this->is_initialised() ) {
			return false;
		}

		try {

			// Build common query.
			$query = \Civi\Api4\Extension::get( false )
				->addSelect( '*' );

			// Maybe add status.
			if ( ! empty( $status ) ) {
				$query->addWhere( 'status', '=', $status );
			}

			// Call the API.
			$result = $query->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Return empty array if not found.
		if ( 0 === $result->count() ) {
			return [];
		}

		// We only need the ArrayObject.
		$extensions = $result->getArrayCopy();

		// --<
		return $extensions;

	}

	/**
	 * Gets the Extensions that are enabled in CiviCRM.
	 *
	 * @since 1.0.9
	 *
	 * @return array $enabled_extensions The array of enabled Extensions, an empty array if none found, or false on error.
	 */
	public function extensions_get_enabled() {

		// Get the Extensions that are enabled.
		$enabled_extensions = $this->extensions_get( 'enabled' );

		// --<
		return $enabled_extensions;

	}

	/**
	 * Gets the active CiviCRM Autocomplete Options.
	 *
	 * @since 1.0.9
	 *
	 * @param string $type The type of Autocomplete Options to return.
	 * @return array $autocomplete_options The active CiviCRM Autocomplete Options, or false on failure.
	 */
	public function autocomplete_options_get( $type = 'contact_autocomplete_options' ) {

		// Bail if no CiviCRM.
		if ( ! $this->is_initialised() ) {
			return false;
		}

		// Init return.
		$autocomplete_options = [];

		// Get the list of autocomplete options.
		$autocomplete_values = CRM_Core_BAO_Setting::valueOptions(
			CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
			$type
		);

		// Filter out the inactive ones.
		// TODO: check how this works.
		// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
		$autocomplete_options = array_keys( $autocomplete_values, '1' );

		// --<
		return $autocomplete_options;

	}

	/**
	 * Gets the link to a CiviCRM admin screen.
	 *
	 * @since 1.0.9
	 *
	 * @param string $path The CiviCRM path.
	 * @param string $params The CiviCRM parameters.
	 * @return string $link The URL of the CiviCRM page.
	 */
	public function link_admin_get( $path = '', $params = null ) {

		// Init link.
		$link = '';

		// Bail if no CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $link;
		}

		// Use CiviCRM to construct link.
		$link = CRM_Utils_System::url(
			$path, // Path to the resource.
			$params, // Params to pass to resource.
			true, // Force an absolute link.
			null, // Fragment (#anchor) to append.
			true, // Encode special HTML characters.
			false, // CMS front end.
			true // CMS back end.
		);

		// --<
		return $link;

	}

	/**
	 * Converts an array to a CiviCRM array-like string.
	 *
	 * @since 1.0.9
	 *
	 * @param mixed $value The existing value.
	 * @return mixed $value The cleaned value.
	 */
	public function array_to_padded( $value ) {

		// Return original value if no CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $value;
		}

		// Convert if the value is an array.
		if ( is_array( $value ) ) {
			$value = CRM_Utils_Array::implodePadded( $value );
		}

		// --<
		return $value;

	}

	/**
	 * Converts a CiviCRM array-like string to a true array.
	 *
	 * @since 1.0.9
	 *
	 * @param mixed $value The existing value.
	 * @return mixed $value The cleaned value.
	 */
	public function array_to_unpadded( $value ) {

		// Return original value if no CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $value;
		}

		// Convert if the value has the special CiviCRM array-like format.
		if ( is_string( $value ) && false !== strpos( $value, CRM_Core_DAO::VALUE_SEPARATOR ) ) {
			$value = CRM_Utils_Array::explodePadded( $value );
		}

		// --<
		return $value;

	}

	/**
	 * De-nullifies CiviCRM data.
	 *
	 * @since 1.0.9
	 *
	 * @param mixed  $value The value to denullify.
	 * @param string $type The empty variable type to return. Default 'null'.
	 * @return mixed $value The denullified value.
	 */
	public function denullify( $value, $type = 'null' ) {

		// Bail if not an inconsistent CiviCRM "empty-ish" value.
		if ( 'null' !== $value && 'NULL' !== $value ) {
			return $value;
		}

		// Set appropriate type.
		switch ( $type ) {
			case 'null':
				$value = null;
				break;
			case 'string':
				$value = '';
				break;
			case 'int':
				$value = 0;
				break;
			case 'bool':
				$value = false;
				break;
			case 'array':
				$value = [];
				break;
			default:
				$value = null;
		}

		// --<
		return $value;

	}

}
