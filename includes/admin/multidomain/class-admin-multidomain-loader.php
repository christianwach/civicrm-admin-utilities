<?php
/**
 * Multidomain Admin Class.
 *
 * Handles Multidomain admin functionality.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Multidomain Admin Class.
 *
 * A class that encapsulates Multidomain admin functionality.
 *
 * @since 0.5.4
 * @since 1.0.9 Renamed.
 */
class CAU_Admin_Multidomain_Loader {

	/**
	 * Plugin object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var CiviCRM_Admin_Utilities
	 */
	public $plugin;

	/**
	 * Network object.
	 *
	 * @since 1.0.9
	 * @access public
	 * @var CAU_Admin_Multidomain_Network
	 */
	public $network;

	/**
	 * Single Site object.
	 *
	 * @since 1.0.9
	 * @access public
	 * @var CAU_Admin_Multidomain_Site
	 */
	public $site;

	/**
	 * Constructor.
	 *
	 * @since 0.5.4
	 *
	 * @param CiviCRM_Admin_Utilities $plugin The plugin object.
	 */
	public function __construct( $plugin ) {

		// Store reference to plugin.
		$this->plugin = $plugin;

		// Initialise when plugin is loaded.
		add_action( 'civicrm_admin_utilities_loaded', [ $this, 'initialise' ] );

		/*
		 * Append to Multisite default settings.
		 *
		 * This filter must be added prior to `register_hooks()` because the
		 * Multisite class will have already loaded its settings by then.
		 */
		add_filter( 'civicrm_admin_utilities_network_settings_default', [ $this, 'settings_get_defaults' ] );

		/*
		 * Upgrade Multisite default settings.
		 *
		 * This filter must be added prior to `register_hooks()` because the
		 * Multisite class will have already loaded its settings by then.
		 */
		add_filter( 'cau/network/settings/upgrade', [ $this, 'settings_upgrade' ] );

	}

	/**
	 * Initialise this object.
	 *
	 * @since 0.5.4
	 */
	public function initialise() {

		// Bootstrap this class.
		$this->include_files();
		$this->setup_objects();
		$this->register_hooks();

		/**
		 * Fires when this class is loaded.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/multidomain/loaded' );

	}

	/**
	 * Include files.
	 *
	 * @since 1.0.9
	 */
	public function include_files() {

		// Include class files.
		require CIVICRM_ADMIN_UTILITIES_PATH . 'includes/admin/multidomain/class-admin-multidomain-page-base.php';
		require CIVICRM_ADMIN_UTILITIES_PATH . 'includes/admin/multidomain/class-admin-multidomain-page-site.php';
		require CIVICRM_ADMIN_UTILITIES_PATH . 'includes/admin/multidomain/class-admin-multidomain-page-network.php';

	}

	/**
	 * Set up objects.
	 *
	 * @since 1.0.9
	 */
	public function setup_objects() {

		// Initialise objects.
		$this->network = new CAU_Admin_Multidomain_Page_Network( $this );
		$this->site    = new CAU_Admin_Multidomain_Page_Site( $this );

	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.9
	 */
	public function register_hooks() {

		// Listen for events before and after loading the CiviCRM settings file.
		add_action( 'civicrm_before_settings_file_load', [ $this, 'settings_file_load_pre' ], 10 );
		add_action( 'civicrm_after_settings_file_load', [ $this, 'settings_file_load_post' ], 10 );

		/*
		// Listen for changes to the CiviCRM Multisite setting.
		add_action( 'civicrm_postSave_civicrm_setting', [ $this, 'setting_changed_multisite' ], 10 );
		*/

		// Listen for changes to the CiviCRM Domain Group ID setting.
		add_action( 'civicrm_postSave_civicrm_setting', [ $this, 'setting_changed_domain_group' ], 10 );

		// Listen for changes to the CiviCRM Domain Organisation ID.
		add_action( 'civicrm_post', [ $this, 'setting_changed_domain_org' ], 10, 4 );

	}

	// -------------------------------------------------------------------------

	/**
	 * Acts on changes to the CiviCRM Domain Organisation ID.
	 *
	 * @since 1.0.9
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function setting_changed_domain_org( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not a Domain.
		if ( 'Domain' !== $object_name ) {
			return;
		}

		// Bail if it's not a Domain object.
		if ( ! ( $object_ref instanceof CRM_Core_BAO_Domain ) ) {
			return;
		}

		// Make sure Domain ID is an integer.
		$domain_id = (int) $object_id;

		// Make sure there is a Contact ID property.
		if ( ! isset( $object_ref->contact_id ) ) {
			return;
		}

		// Update the reference data for this Domain.
		$data = $this->reference_data_get( $domain_id );
		if ( empty( $object_ref->contact_id ) ) {
			$this->reference_data_remove( $domain_id, [ 'org_id' ], true );
		} else {
			$changing = false;
			if ( empty( $data['org_id'] ) ) {
				$changing = true;
			} elseif ( (int) $data['org_id'] !== (int) $object_ref->contact_id ) {
				$changing = true;
			}
			if ( $changing ) {
				$this->reference_data_update( $domain_id, 'org_id', $object_ref->contact_id, true );
			}
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Append Multidomain settings on first load.
	 *
	 * @since 1.0.9
	 *
	 * @param array $settings The array of default settings.
	 * @return array $settings The modified array of default settings.
	 */
	public function settings_get_defaults( $settings = [] ) {

		// Keep a record of WordPress Site - CiviCRM Domain mappings.
		$settings['multidomain_mappings'] = [
			'wp-to-cv' => [],
			'cv-to-wp' => [],
		];

		// Keep a record of CiviCRM Domain paths and URLs.
		$settings['multidomain_paths'] = [];

		// Keep a record of Group IDs that have been orphaned.
		$settings['multidomain_groups_orphaned'] = [];

		// Keep a reference array of data per CiviCRM Domain.
		$settings['multidomain_reference'] = [];

		/**
		 * Filters the default Multidomain settings.
		 *
		 * @since 1.0.9
		 *
		 * @param array $settings The array of default network and Multidomain settings.
		 */
		$settings = apply_filters( 'cau/multidomain/settings/default', $settings );

		// --<
		return $settings;

	}

	/**
	 * Upgrade Multidomain settings on plugin upgrade.
	 *
	 * @since 1.0.9
	 *
	 * @param array $save The flag for whether or not to save the settings.
	 * @return array $save The modified flag for whether or not to save the settings.
	 */
	public function settings_upgrade( $save ) {

		// WordPress Site - CiviCRM Domain mappings may not exist.
		if ( ! $this->plugin->multisite->setting_exists( 'multidomain_mappings' ) ) {

			// Add it from defaults.
			$settings = $this->settings_get_defaults();
			$this->plugin->multisite->setting_set( 'multidomain_mappings', $settings['multidomain_mappings'] );
			$save = true;

		}

		// CiviCRM Domain Paths and URLs may not exist.
		if ( ! $this->plugin->multisite->setting_exists( 'multidomain_paths' ) ) {

			// Add it from defaults.
			$settings = $this->settings_get_defaults();
			$this->plugin->multisite->setting_set( 'multidomain_paths', $settings['multidomain_paths'] );
			$save = true;

		}

		// Orphaned Group IDs may not exist.
		if ( ! $this->plugin->multisite->setting_exists( 'multidomain_groups_orphaned' ) ) {

			// Add it from defaults.
			$settings = $this->settings_get_defaults();
			$this->plugin->multisite->setting_set( 'multidomain_groups_orphaned', $settings['multidomain_paths'] );
			$save = true;

		}

		// CiviCRM reference data may not exist.
		if ( ! $this->plugin->multisite->setting_exists( 'multidomain_reference' ) ) {

			// Add it from defaults.
			$settings = $this->settings_get_defaults();
			$this->plugin->multisite->setting_set( 'multidomain_reference', $settings['multidomain_reference'] );
			$save = true;

		}

		// --<
		return $save;

	}

	// -------------------------------------------------------------------------

	/**
	 * Set any CiviCRM constants before the CiviCRM settings file is loaded.
	 *
	 * @since 1.0.9
	 */
	public function settings_file_load_pre() {

		// Get the Domain ID assigned to this WordPress Site.
		$domain_id = $this->mapping_domain_get( get_current_blog_id() );

		// Bail if our mapping isn't being used.
		if ( empty( $domain_id ) ) {
			return;
		}

		// Set Base URL.
		if ( ! defined( 'CIVICRM_UF_BASEURL' ) ) {
			define( 'CIVICRM_UF_BASEURL', untrailingslashit( home_url() ) );
		}

		// Set Domain ID if we have one.
		if ( ! defined( 'CIVICRM_DOMAIN_ID' ) ) {
			define( 'CIVICRM_DOMAIN_ID', $domain_id );
		}

	}

	/**
	 * Override any CiviCRM settings after the CiviCRM settings file has been loaded.
	 *
	 * @since 1.0.9
	 */
	public function settings_file_load_post() {

		global $civicrm_setting, $civicrm_paths;

		// Get the Domain ID assigned to this WordPress Site.
		$domain_id = $this->mapping_domain_get( get_current_blog_id() );

		// Bail if our mapping isn't being used.
		if ( empty( $domain_id ) ) {
			return;
		}

		/*
		 * Get the Paths and URLs for this Domain.
		 *
		 * These paths and URLs are normally defined in the "civicrm.setting.php" file.
		 * With multiple CiviCRM Domains, they need to be loaded dynamically for each
		 * Domain to work correctly.
		 *
		 * @see CAU_Admin_Multidomain::civicrm_after_settings_file_load()
		 */
		$paths = $this->plugin->multidomain->paths_get( $domain_id );

		// Set known CiviCRM paths.
		$civicrm_paths['wp.frontend.base']['url'] = trailingslashit( home_url() );
		$civicrm_paths['wp.backend.base']['url']  = trailingslashit( admin_url() );

		// Set CiviCRM settings.
		if ( ! empty( $paths['core_url'] ) ) {
			$civicrm_setting['domain']['userFrameworkResourceURL'] = $paths['core_url'];
		}
		if ( ! empty( $paths['extensions_url'] ) ) {
			$civicrm_setting['domain']['extensionsURL'] = $paths['extensions_url'];
		}
		if ( ! empty( $paths['extensions_path'] ) ) {
			$civicrm_setting['domain']['extensionsDir'] = $paths['extensions_path'];
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Listens for a change in the CiviCRM "Enable Multi Site Configuration" setting.
	 *
	 * @since 1.0.9
	 *
	 * @param CRM_Core_DAO_Setting $dao The CiviCRM database access object.
	 */
	public function setting_changed_multisite( $dao ) {

		// Bail if not a setting.
		if ( ! ( $dao instanceof CRM_Core_DAO_Setting ) ) {
			return;
		}

		// Make sure there is a setting name.
		if ( ! isset( $dao->name ) ) {
			return;
		}

		// Bail if not the "Enable Multi Site Configuration" setting.
		if ( 'is_enabled' !== $dao->name && 'multisite_is_enabled' !== $dao->name ) {
			return;
		}

		// Make sure there is a setting value.
		if ( ! isset( $dao->value ) ) {
			return;
		}

		// Unserialise if needed.
		$value = maybe_unserialize( $dao->value );

		// Denullify if needed.
		$value = $this->plugin->civicrm->denullify( $value, 'bool' );

		// We need the WordPress Site ID.
		$site_id = get_current_blog_id();

		/*
		// When enabling the setting.
		if ( ! empty( $value ) ) {
			// Assign this CiviCRM Domain to the current WordPress Site.
		} else {
			// Sever the link between this CiviCRM Domain and the current WordPress Site.
		}
		*/

	}

	/**
	 * Listens for a change in the CiviCRM "Multisite Domain Group" setting.
	 *
	 * @since 1.0.9
	 *
	 * @param CRM_Core_DAO_Setting $dao The CiviCRM database access object.
	 */
	public function setting_changed_domain_group( $dao ) {

		// Bail if not a setting.
		if ( ! ( $dao instanceof CRM_Core_DAO_Setting ) ) {
			return;
		}

		// Make sure there is a setting name.
		if ( ! isset( $dao->name ) ) {
			return;
		}

		// Bail if not the "Multisite Domain Group" setting.
		if ( 'domain_group_id' !== $dao->name ) {
			return;
		}

		// Unserialise if needed.
		$value = maybe_unserialize( $dao->value );

		// Denullify if needed.
		$value = $this->plugin->civicrm->denullify( $value, 'bool' );

		// Get the Domain ID.
		$domain_id = ! empty( $dao->domain_id ) ? (int) $dao->domain_id : false;
		if ( empty( $domain_id ) ) {
			return;
		}

		// Update the reference data for this Domain.
		$data = $this->reference_data_get( $domain_id );
		if ( empty( $value ) ) {
			$this->reference_data_remove( $domain_id, [ 'group_id' ], true );
		} else {
			$changing = false;
			if ( empty( $data['group_id'] ) ) {
				$changing = true;
			} elseif ( (int) $data['group_id'] !== (int) $value ) {
				$changing = true;
			}
			if ( $changing ) {
				$this->reference_data_update( $domain_id, 'group_id', $value, true );
			}
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the Path and URL data for the current Domain.
	 *
	 * These paths and URLs are normally defined in the "civicrm.setting.php" file.
	 * With multiple CiviCRM Domains, they need to be loaded dynamically for each
	 * Domain to work correctly.
	 *
	 * @see CAU_Admin_Multidomain::civicrm_after_settings_file_load()
	 *
	 * @since 1.0.9
	 *
	 * @return array $paths The array of Paths and URLs.
	 */
	public function paths_get_defaults() {

		global $civicrm_setting, $civicrm_paths;

		// Init defaults.
		$defaults = [];

		// Get Paths and URLs from "civicrm.settings.php".
		$defaults['core_url']        = isset( $civicrm_setting['domain']['userFrameworkResourceURL'] ) ? $civicrm_setting['domain']['userFrameworkResourceURL'] : '';
		$defaults['extensions_url']  = isset( $civicrm_setting['domain']['extensionsURL'] ) ? $civicrm_setting['domain']['extensionsURL'] : '';
		$defaults['extensions_path'] = isset( $civicrm_setting['domain']['extensionsDir'] ) ? $civicrm_setting['domain']['extensionsDir'] : '';

		/*
		 * Let's set a default Core URL using the same logic as CiviCRM.
		 *
		 * This is non-critical, so setting a reasonable default may help speed up config
		 * in standard installs.
		 *
		 * @see civicrm/civicrm/setup/plugins/init/WordPress.civi-setup.php
		 * @see _civicrm_wordpress_plugin_file
		 */
		if ( defined( 'CIVICRM_PLUGIN_FILE' ) ) {
			$defaults['core_url'] = plugin_dir_url( CIVICRM_PLUGIN_FILE ) . 'civicrm';
		}

		// --<
		return $defaults;

	}

	/**
	 * Gets the Path and URL data for all CiviCRM Domains.
	 *
	 * @since 1.0.9
	 *
	 * @return array $paths The Path and URL data for all CiviCRM Domains.
	 */
	public function paths_get_all() {

		// Get all data.
		$paths = $this->plugin->multisite->setting_get( 'multidomain_paths', [] );

		/**
		 * Filter the paths and URLs for all Domains.
		 *
		 * @since 1.0.9
		 *
		 * @param array $paths The Path and URL data for all CiviCRM Domains.
		 */
		$paths = apply_filters( 'cau/multidomain/domain/paths/get_all', $paths );

		// --<
		return $paths;

	}

	/**
	 * Clears the Path and URL data for all CiviCRM Domains.
	 *
	 * @since 1.0.9
	 *
	 * @param bool $save Passing true saves the option immediately, false does not.
	 */
	public function paths_remove_all( $save = false ) {

		// Clear all mappings.
		$settings = $this->settings_get_defaults();
		$this->plugin->multisite->setting_set( 'multidomain_paths', $settings['multidomain_paths'] );

		// Maybe save.
		if ( true === $save ) {
			$this->plugin->multisite->settings_save();
		}

	}

	/**
	 * Gets the Path and URL data for a given CiviCRM Domain ID.
	 *
	 * @since 1.0.9
	 *
	 * @param int $domain_id The numeric ID of the CiviCRM Domain.
	 * @return array $paths The array of Path and URL data for the CiviCRM Domain.
	 */
	public function paths_get( $domain_id = 0 ) {

		// Sanity check.
		if ( empty( $domain_id ) ) {
			return [];
		}

		// Data integrity.
		$domain_id = (int) $domain_id;

		// Get all stored data.
		$setting = $this->paths_get_all();

		// Try to get paths by key.
		$paths = ! empty( $setting[ $domain_id ] ) ? $setting[ $domain_id ] : [];

		// Get default paths from "civicrm.setting.php".
		$defaults = $this->paths_get_defaults();

		// Merge saved Paths and URLs with defaults.
		$paths = wp_parse_args( $paths, $defaults );

		/**
		 * Filter the paths and URLs for this Domain.
		 *
		 * @since 1.0.9
		 *
		 * @param array $paths The Path and URL data for all CiviCRM Domains.
		 * @param int   $domain_id The numeric ID of the CiviCRM Domain.
		 */
		$paths = apply_filters( 'cau/multidomain/domain/paths/get', $paths, $domain_id );

		// --<
		return $paths;

	}

	/**
	 * Sets the Path and URL data for a given CiviCRM Domain ID.
	 *
	 * @since 1.0.9
	 *
	 * @param int   $domain_id The numeric ID of the CiviCRM Domain.
	 * @param array $paths The Path and URL data for all CiviCRM Domains.
	 * @param bool  $save Passing true saves the option immediately, false does not.
	 */
	public function paths_set( $domain_id, $paths, $save = false ) {

		// Bail if we have no Domain ID.
		if ( empty( $domain_id ) ) {
			return;
		}

		// Data integrity.
		$domain_id = (int) $domain_id;

		// Get all stored data.
		$setting = $this->paths_get_all();

		/**
		 * Filter the paths and URLs for this Domain before they are saved.
		 *
		 * @since 1.0.9
		 *
		 * @param array $paths The Path and URL data for all CiviCRM Domains.
		 * @param int   $domain_id The numeric ID of the CiviCRM Domain.
		 */
		$paths = apply_filters( 'cau/multidomain/domain/paths/set', $paths, $domain_id );

		// Always overwrite.
		$setting[ $domain_id ] = $paths;

		// Store updated array.
		$this->plugin->multisite->setting_set( 'multidomain_paths', $setting );

		// Maybe save.
		if ( true === $save ) {
			$this->plugin->multisite->settings_save();
		}

	}

	/**
	 * Deletes the Path and URL data for a given CiviCRM Domain ID.
	 *
	 * @since 1.0.9
	 *
	 * @param int  $domain_id The numeric ID of the CiviCRM Domain.
	 * @param bool $save Passing true saves the option immediately, false does not.
	 */
	public function paths_remove( $domain_id, $save = false ) {

		// Data integrity.
		$domain_id = (int) $domain_id;

		// Get all stored data.
		$setting = $this->paths_get_all();

		// Always clear.
		unset( $setting[ $domain_id ] );

		// Store updated array.
		$this->plugin->multisite->setting_set( 'multidomain_paths', $setting );

		// Maybe save.
		if ( true === $save ) {
			$this->plugin->multisite->settings_save();
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the array of WordPress Site to CiviCRM Domain mappings.
	 *
	 * The approach here is probably overly cautious, since the correspondences are
	 * likely to be one-to-one, but it is, in theory, possible to have multiple
	 * CiviCRM Domains active on a single WordPress Site or a single CiviCRM Domain
	 * assigned to multiple WordPress Sites. I'm not sure why you'd want to do that,
	 * but it's possible. So...
	 *
	 * (a) A CiviCRM Domain needs to know which WordPress Site IDs it is assigned to.
	 * (b) WordPress Site needs to know the CiviCRM Domain IDs which are assigned to it.
	 *
	 * We use a WordPress network option to store an array with two keys - one for
	 * WordPress-to-CiviCRM queries and one for CiviCRM-to-WordPress queries.
	 *
	 * We can query the data by WordPress Site ID and retrieve the CiviCRM Domain IDs.
	 * The 'wp-to-cv' array looks like:
	 *
	 * array(
	 *   $site_id => array(
	 *     $domain_id,
	 *   ),
	 *   $site_id => array(
	 *     $domain_id,
	 *     $domain_id,
	 *     $domain_id,
	 *   ),
	 *   ...
	 * )
	 *
	 * In the reverse situation, we store a similar array keyed by CiviCRM Domain ID.
	 * The 'cv-to-wp' array looks like:
	 *
	 * array(
	 *   $domain_id => array(
	 *     $site_id,
	 *   ),
	 *   $domain_id => array(
	 *     $site_id,
	 *     $site_id,
	 *     $site_id,
	 *   ),
	 *   ...
	 * )
	 *
	 * @since 1.0.9
	 *
	 * @param string $direction The mapping direction - "wp-to-cv", "cv-to-wp" or empty for both.
	 * @return array $mappings The array of Site <-> Domain mapping data.
	 */
	public function mapping_get_all( $direction = '' ) {

		// Get all mappings.
		$mappings = $this->plugin->multisite->setting_get( 'multidomain_mappings', [] );

		// Maybe return a sub-array.
		if ( ! empty( $direction ) ) {
			if ( 'wp-to-cv' === $direction ) {
				return $mappings['wp-to-cv'];
			}
			if ( 'cv-to-wp' === $direction ) {
				return $mappings['wp-to-cv'];
			}
		}

		// Return all.
		return $mappings;

	}

	/**
	 * Clears the array of WordPress Site to CiviCRM Domain mappings.
	 *
	 * @since 1.0.9
	 *
	 * @param bool $save Passing true saves the option immediately, false does not.
	 */
	public function mapping_remove_all( $save = false ) {

		// Clear all mappings.
		$settings = $this->settings_get_defaults();
		$this->plugin->multisite->setting_set( 'multidomain_mappings', $settings['multidomain_mappings'] );

		// Maybe save.
		if ( true === $save ) {
			$this->plugin->multisite->settings_save();
		}

	}

	/**
	 * Gets a set of CiviCRM Domains for a WordPress Site.
	 *
	 * @since 1.0.9
	 *
	 * @param int $site_id The numeric ID of the WordPress Site.
	 */
	public function mapping_domains_get( $site_id ) {

		// Sanity check.
		if ( empty( $site_id ) ) {
			return false;
		}

		// Data integrity.
		$site_id = (int) $site_id;

		// Get stored mappings.
		$mappings = $this->mapping_get_all();

		// Get the full array.
		if ( array_key_exists( $site_id, $mappings['wp-to-cv'] ) ) {
			$domains = $mappings['wp-to-cv'][ $site_id ];
		}

		// Return false if empty.
		if ( empty( $domains ) ) {
			return false;
		}

		// If there's only one item, return it.
		if ( 1 === count( $domains ) ) {
			return array_pop( $domains );
		}

		// Return full array.
		return $domains;

	}

	/**
	 * Gets a CiviCRM Domain for a WordPress Site.
	 *
	 * This is an alias of `self::mapping_domains_get()`.
	 *
	 * @since 1.0.9
	 *
	 * @param int $site_id The numeric ID of the WordPress Site.
	 * @return array|int|bool $domain_ids The array of Domain IDs when there are more than one,
	 *                                    the Domain ID when there is only one,
	 *                                    or false if there are none assigned.
	 */
	public function mapping_domain_get( $site_id ) {
		return $this->mapping_domains_get( (int) $site_id );
	}

	/**
	 * Assigns a set of CiviCRM Domains to a WordPress Site.
	 *
	 * @since 1.0.9
	 *
	 * @param int   $site_id The numeric ID of the WordPress Site.
	 * @param array $domain_ids The array of CiviCRM Domain IDs.
	 * @param bool  $save Passing true saves the option immediately, false does not.
	 */
	public function mapping_domains_assign( $site_id, $domain_ids, $save = false ) {

		// Sanity check.
		if ( empty( $site_id ) || empty( $domain_ids ) ) {
			return;
		}

		// Data integrity.
		$site_id    = (int) $site_id;
		$domain_ids = array_map( 'intval', $domain_ids );

		// Get stored mappings.
		$mappings = $this->mapping_get_all();

		// Maybe initialise array for Site ID.
		if ( ! array_key_exists( $site_id, $mappings['wp-to-cv'] ) ) {
			$mappings['wp-to-cv'][ $site_id ] = [];
		}

		// Handle CiviCRM Domain IDs in turn.
		if ( ! empty( $domain_ids ) ) {
			foreach ( $domain_ids as $domain_id ) {

				// Maybe initialise array for CiviCRM Domain ID.
				if ( ! array_key_exists( $domain_id, $mappings['cv-to-wp'] ) ) {
					$mappings['cv-to-wp'][ $domain_id ] = [];
				}

				// Maybe add Site ID to the CiviCRM Domain ID key.
				if ( ! in_array( $site_id, $mappings['cv-to-wp'][ $domain_id ], true ) ) {
					$mappings['cv-to-wp'][ $domain_id ][] = $site_id;
				}

				// Maybe add Domain ID to the WordPress Site ID key.
				if ( ! in_array( $domain_id, $mappings['wp-to-cv'][ $site_id ], true ) ) {
					$mappings['wp-to-cv'][ $site_id ][] = $domain_id;
				}

			}
		}

		// Store updated array.
		$this->plugin->multisite->setting_set( 'multidomain_mappings', $mappings );

		// Maybe save.
		if ( true === $save ) {
			$this->plugin->multisite->settings_save();
		}

		/**
		 * Fires when the Site-Domain mappings have been saved.
		 *
		 * @since 1.0.9
		 *
		 * @param int   $site_id The numeric ID of the WordPress Site.
		 * @param array $domain_ids The array of CiviCRM Domain IDs.
		 */
		do_action( 'cau/multidomain/mappings/domains/assigned', $site_id, $domain_ids );

	}

	/**
	 * Assigns a CiviCRM Domain to a WordPress Site.
	 *
	 * @since 1.0.9
	 *
	 * @param int  $site_id The numeric ID of the WordPress Site.
	 * @param int  $domain_id The numeric ID of the CiviCRM Domain.
	 * @param bool $save Passing true saves the option immediately, false does not.
	 */
	public function mapping_domain_assign( $site_id, $domain_id, $save = false ) {
		$this->mapping_domains_assign( (int) $site_id, [ (int) $domain_id ], $save );
	}

	/**
	 * Removes a set of CiviCRM Domains from a WordPress Site.
	 *
	 * @since 1.0.9
	 *
	 * @param int   $site_id The numeric ID of the WordPress Site.
	 * @param array $domain_ids The array of CiviCRM Domain IDs.
	 * @param bool  $save Passing true saves the option immediately, false does not.
	 */
	public function mapping_domains_remove( $site_id, $domain_ids, $save = false ) {

		// Sanity check.
		if ( empty( $site_id ) || empty( $domain_ids ) ) {
			return;
		}

		// Data integrity.
		$site_id    = (int) $site_id;
		$domain_ids = array_map( 'intval', $domain_ids );

		// Get stored mappings.
		$mappings = $this->mapping_get_all();

		// Handle CiviCRM Domain IDs in turn.
		if ( ! empty( $domain_ids ) ) {
			foreach ( $domain_ids as $domain_id ) {

				// Maybe remove CiviCRM Domain ID from the WordPress Site ID array.
				if ( array_key_exists( $site_id, $mappings['wp-to-cv'] ) ) {
					if ( in_array( $domain_id, $mappings['wp-to-cv'][ $site_id ], true ) ) {
						$mappings['wp-to-cv'][ $site_id ] = array_diff( $mappings['wp-to-cv'][ $site_id ], [ $domain_id ] );
					}
				}

				// Maybe remove the WordPress Site ID array if it's empty.
				if ( empty( $mappings['wp-to-cv'][ $site_id ] ) ) {
					unset( $mappings['wp-to-cv'][ $site_id ] );
				}

				// Maybe remove WordPress Site ID from the CiviCRM Domain ID key.
				if ( array_key_exists( $domain_id, $mappings['cv-to-wp'] ) ) {
					if ( in_array( $site_id, $mappings['cv-to-wp'][ $domain_id ], true ) ) {
						$mappings['cv-to-wp'][ $domain_id ] = array_diff( $mappings['cv-to-wp'][ $domain_id ], [ $site_id ] );
					}
				}

				// Maybe remove the CiviCRM Domain ID array if it's empty.
				if ( empty( $mappings['cv-to-wp'][ $domain_id ] ) ) {
					unset( $mappings['cv-to-wp'][ $domain_id ] );
				}

			}
		}

		// Store updated array.
		$this->plugin->multisite->setting_set( 'multidomain_mappings', $mappings );

		// Maybe save.
		if ( true === $save ) {
			$this->plugin->multisite->settings_save();
		}

		/**
		 * Fires when the Site-Domain mappings have been removed.
		 *
		 * @since 1.0.9
		 *
		 * @param int   $site_id The numeric ID of the WordPress Site.
		 * @param array $domain_ids The array of CiviCRM Domain IDs.
		 */
		do_action( 'cau/multidomain/mappings/domains/removed', $domain_id, $site_ids );

	}

	/**
	 * Removes a CiviCRM Domain from a WordPress Site.
	 *
	 * @since 1.0.9
	 *
	 * @param int  $site_id The numeric ID of the WordPress Site.
	 * @param int  $domain_id The numeric ID of the CiviCRM Domain.
	 * @param bool $save Passing true saves the option immediately, false does not.
	 */
	public function mapping_domain_remove( $site_id, $domain_id, $save = false ) {
		$this->mapping_domains_remove( (int) $site_id, [ (int) $domain_id ], $save );
	}

	/**
	 * Gets a set of WordPress Sites for a CiviCRM Domain.
	 *
	 * @since 1.0.9
	 *
	 * @param int $domain_id The numeric ID of the CiviCRM Domain.
	 * @return array|int|bool $site_ids The array of WordPress Site IDs.
	 */
	public function mapping_sites_get( $domain_id ) {

		// Sanity check.
		if ( empty( $domain_id ) ) {
			return false;
		}

		// Data integrity.
		$domain_id = (int) $domain_id;

		// Get stored mappings.
		$mappings = $this->mapping_get_all();

		// Get the full array.
		if ( array_key_exists( $domain_id, $mappings['cv-to-wp'] ) ) {
			$site_ids = $mappings['cv-to-wp'][ $domain_id ];
		}

		// Return false if empty.
		if ( empty( $site_ids ) ) {
			return false;
		}

		// If there's only one item, return it.
		if ( 1 === count( $site_ids ) ) {
			return (int) array_pop( $site_ids );
		}

		// Return full array.
		return $site_ids;

	}

	/**
	 * Gets a WordPress Site for a CiviCRM Domain.
	 *
	 * This is an alias of `self::mapping_sites_get()`.
	 *
	 * @since 1.0.9
	 *
	 * @param int $domain_id The numeric ID of the CiviCRM Domain.
	 * @return array|int|bool $site_ids The array of Site IDs when there are more than one,
	 *                                  the Site ID when there is only one,
	 *                                  or false if there are none assigned.
	 */
	public function mapping_site_get( $domain_id ) {
		return $this->mapping_sites_get( (int) $domain_id );
	}

	/**
	 * Assigns a set of WordPress Sites to a CiviCRM Domain.
	 *
	 * @since 1.0.9
	 *
	 * @param int   $domain_id The numeric ID of the CiviCRM Domain.
	 * @param array $site_ids The array of WordPress Site IDs.
	 * @param bool  $save Passing true saves the option immediately, false does not.
	 */
	public function mapping_sites_assign( $domain_id, $site_ids, $save = false ) {

		// Sanity check.
		if ( empty( $domain_id ) || empty( $site_ids ) ) {
			return;
		}

		// Data integrity.
		$domain_id = (int) $domain_id;
		$site_ids  = array_map( 'intval', $site_ids );

		// Get stored mappings.
		$mappings = $this->mapping_get_all();

		// Maybe initialise array for CiviCRM Domain ID.
		if ( ! array_key_exists( $domain_id, $mappings['cv-to-wp'] ) ) {
			$mappings['cv-to-wp'][ $domain_id ] = [];
		}

		// Handle WordPress Site IDs in turn.
		if ( ! empty( $site_ids ) ) {
			foreach ( $site_ids as $site_id ) {

				// Maybe initialise array for WordPress Site ID.
				if ( ! array_key_exists( $site_id, $mappings['wp-to-cv'] ) ) {
					$mappings['wp-to-cv'][ $site_id ] = [];
				}

				// Maybe add CiviCRM Domain ID to the WordPress Site ID key.
				if ( ! in_array( $domain_id, $mappings['wp-to-cv'][ $site_id ], true ) ) {
					$mappings['wp-to-cv'][ $site_id ][] = $domain_id;
				}

				// Maybe add WordPress Site ID to the CiviCRM Domain ID key.
				if ( ! in_array( $site_id, $mappings['cv-to-wp'][ $domain_id ], true ) ) {
					$mappings['cv-to-wp'][ $domain_id ][] = $site_id;
				}

			}
		}

		// Store updated array.
		$this->plugin->multisite->setting_set( 'multidomain_mappings', $mappings );

		// Maybe save.
		if ( true === $save ) {
			$this->plugin->multisite->settings_save();
		}

		/**
		 * Fires when the Site-Domain mappings have been saved.
		 *
		 * @since 1.0.9
		 *
		 * @param int   $domain_id The numeric ID of the CiviCRM Domain.
		 * @param array $site_ids The array of WordPress Site IDs.
		 */
		do_action( 'cau/multidomain/mappings/sites/assigned', $domain_id, $site_ids );

	}

	/**
	 * Assigns a WordPress Site to a CiviCRM Domain.
	 *
	 * @since 1.0.9
	 *
	 * @param int  $domain_id The numeric ID of the CiviCRM Domain.
	 * @param int  $site_id The numeric ID of the WordPress Site.
	 * @param bool $save Passing true saves the option immediately, false does not.
	 */
	public function mapping_site_assign( $domain_id, $site_id, $save = false ) {
		$this->mapping_sites_assign( (int) $domain_id, [ (int) $site_id ], $save );
	}

	/**
	 * Removes a set of WordPress Sites from a CiviCRM Domains.
	 *
	 * @since 1.0.9
	 *
	 * @param int   $domain_id The numeric ID of the CiviCRM Domain.
	 * @param array $site_ids The array of WordPress Site IDs.
	 * @param bool  $save Passing true saves the option immediately, false does not.
	 */
	public function mapping_sites_remove( $domain_id, $site_ids, $save = false ) {

		// Sanity check.
		if ( empty( $domain_id ) || empty( $site_ids ) ) {
			return;
		}

		// Data integrity.
		$domain_id = (int) $domain_id;
		$site_ids  = array_map( 'intval', $site_ids );

		// Get stored mappings.
		$mappings = $this->mapping_get_all();

		// Handle WordPress Site IDs in turn.
		if ( ! empty( $site_ids ) ) {
			foreach ( $site_ids as $site_id ) {

				// Maybe remove WordPress Site ID from the CiviCRM Domain ID array.
				if ( array_key_exists( $domain_id, $mappings['cv-to-wp'] ) ) {
					if ( in_array( $site_id, $mappings['cv-to-wp'][ $domain_id ], true ) ) {
						$mappings['cv-to-wp'][ $domain_id ] = array_diff( $mappings['cv-to-wp'][ $domain_id ], [ $site_id ] );
					}
				}

				// Maybe remove the CiviCRM Domain ID array if it's empty.
				if ( empty( $mappings['cv-to-wp'][ $domain_id ] ) ) {
					unset( $mappings['cv-to-wp'][ $domain_id ] );
				}

				// Maybe remove CiviCRM Domain ID from the WordPress Site ID key.
				if ( array_key_exists( $site_id, $mappings['wp-to-cv'] ) ) {
					if ( in_array( $domain_id, $mappings['wp-to-cv'][ $site_id ], true ) ) {
						$mappings['wp-to-cv'][ $site_id ] = array_diff( $mappings['wp-to-cv'][ $site_id ], [ $domain_id ] );
					}
				}

				// Maybe remove the WordPress Site ID array if it's empty.
				if ( empty( $mappings['wp-to-cv'][ $site_id ] ) ) {
					unset( $mappings['wp-to-cv'][ $site_id ] );
				}

			}
		}

		// Store updated array.
		$this->plugin->multisite->setting_set( 'multidomain_mappings', $mappings );

		// Maybe save.
		if ( true === $save ) {
			$this->plugin->multisite->settings_save();
		}

		/**
		 * Fires when the Site-Domain mappings have been removed.
		 *
		 * @since 1.0.9
		 *
		 * @param int   $domain_id The numeric ID of the CiviCRM Domain.
		 * @param array $site_ids The array of WordPress Site IDs.
		 */
		do_action( 'cau/multidomain/mappings/sites/removed', $domain_id, $site_ids );

	}

	/**
	 * Removes a WordPress Site from a CiviCRM Domain.
	 *
	 * @since 1.0.9
	 *
	 * @param int  $domain_id The numeric ID of the CiviCRM Domain.
	 * @param int  $site_id The numeric ID of the WordPress Site.
	 * @param bool $save Passing true saves the option immediately, false does not.
	 */
	public function mapping_site_remove( $domain_id, $site_id, $save = false ) {
		$this->mapping_sites_remove( (int) $domain_id, [ (int) $site_id ], $save );
	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the Orphaned Groups data for all CiviCRM Domain Groups.
	 *
	 * When a Domain Group is deleted for a CiviCRM Domain, all CiviCRM Groups that
	 * share the same Domain Organisation lose their "GroupOrganization" entries.
	 * This means that they cannot be reassigned to the same new Domain Organisation
	 * if a new Domain Group is chosen.
	 *
	 * The array stored here contains all the Groups IDs that had links to the same
	 * Domain Organisation as the deleted Domain Group. It's keyed by Domain ID in
	 * case a new Domain Group is chosen for that Domain.
	 *
	 * @since 1.0.9
	 *
	 * @return array $paths The Orphaned Groups data for all CiviCRM Domain Groups.
	 */
	public function groups_orphaned_get_all() {

		// Get all stored data.
		$paths = $this->plugin->multisite->setting_get( 'multidomain_groups_orphaned', [] );

		// --<
		return $paths;

	}

	/**
	 * Clears the Orphaned Groups data for all CiviCRM Domain Groups.
	 *
	 * @since 1.0.9
	 *
	 * @param bool $save Passing true saves the option immediately, false does not.
	 */
	public function groups_orphaned_remove_all( $save = false ) {

		// Clear all data and save.
		$settings = $this->settings_get_defaults();
		$this->plugin->multisite->setting_set( 'multidomain_groups_orphaned', $settings['multidomain_groups_orphaned'] );

		// Maybe save.
		if ( true === $save ) {
			$this->plugin->multisite->settings_save();
		}

	}

	/**
	 * Gets the Orphaned Groups data for a given CiviCRM Domain ID.
	 *
	 * @since 1.0.9
	 *
	 * @param int $domain_id The numeric ID of the CiviCRM Domain.
	 * @return array $group_ids The array of Orphaned Groups data for the CiviCRM Domain.
	 */
	public function groups_orphaned_get( $domain_id = 0 ) {

		// Sanity check.
		if ( empty( $domain_id ) ) {
			return [];
		}

		// Data integrity.
		$domain_id = (int) $domain_id;

		// Get all stored data.
		$setting = $this->groups_orphaned_get_all();

		// Try to get Gorup IDs by key.
		$group_ids = ! empty( $setting[ $domain_id ] ) ? $setting[ $domain_id ] : [];

		// --<
		return $group_ids;

	}

	/**
	 * Sets the Orphaned Groups data for a given CiviCRM Domain ID.
	 *
	 * @since 1.0.9
	 *
	 * @param int   $domain_id The numeric ID of the CiviCRM Domain.
	 * @param array $group_ids The array of Orphaned Group IDs.
	 * @param bool  $save Passing true saves the option immediately, false does not.
	 */
	public function groups_orphaned_set( $domain_id, $group_ids, $save = false ) {

		// Bail if we have no Domain ID.
		if ( empty( $domain_id ) ) {
			return;
		}

		// Data integrity.
		$domain_id = (int) $domain_id;
		$group_ids = array_map( 'intval', $group_ids );

		// Get all stored data.
		$setting = $this->groups_orphaned_get_all();

		// Always overwrite.
		$setting[ $domain_id ] = $group_ids;

		// Store updated array.
		$this->plugin->multisite->setting_set( 'multidomain_groups_orphaned', $setting );

		// Maybe save.
		if ( true === $save ) {
			$this->plugin->multisite->settings_save();
		}

	}

	/**
	 * Deletes the Orphaned Groups data for a given CiviCRM Domain ID.
	 *
	 * @since 1.0.9
	 *
	 * @param int  $domain_id The numeric ID of the CiviCRM Domain.
	 * @param bool $save Passing true saves the option immediately, false does not.
	 */
	public function groups_orphaned_remove( $domain_id, $save = false ) {

		// Data integrity.
		$domain_id = (int) $domain_id;

		// Get all stored data.
		$setting = $this->groups_orphaned_get_all();

		// Always clear.
		unset( $setting[ $domain_id ] );

		// Store updated array.
		$this->plugin->multisite->setting_set( 'multidomain_groups_orphaned', $setting );

		// Maybe save.
		if ( true === $save ) {
			$this->plugin->multisite->settings_save();
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the reference data for all CiviCRM Domains.
	 *
	 * @since 1.0.9
	 *
	 * @return array $reference_data The reference data for all CiviCRM Domains.
	 */
	public function reference_data_get_all() {

		// Get all data.
		$reference_data = $this->plugin->multisite->setting_get( 'multidomain_reference', [] );

		/**
		 * Filter the reference data for all Domains.
		 *
		 * @since 1.0.9
		 *
		 * @param array $reference_data The reference data for all CiviCRM Domains.
		 */
		$reference_data = apply_filters( 'cau/multidomain/domain/reference/get_all', $reference_data );

		// --<
		return $reference_data;

	}

	/**
	 * Clears the reference data for all CiviCRM Domains.
	 *
	 * @since 1.0.9
	 *
	 * @param bool $save Passing true saves the option immediately, false does not.
	 */
	public function reference_data_remove_all( $save = false ) {

		// Clear all mappings and save.
		$settings = $this->settings_get_defaults();
		$this->plugin->multisite->setting_set( 'multidomain_reference', $settings['multidomain_reference'] );

		// Maybe save.
		if ( true === $save ) {
			$this->plugin->multisite->settings_save();
		}

	}

	/**
	 * Gets the reference data for a given CiviCRM Domain ID.
	 *
	 * @since 1.0.9
	 *
	 * @param int $domain_id The numeric ID of the CiviCRM Domain.
	 * @return array $reference_data The array of reference data for the CiviCRM Domain.
	 */
	public function reference_data_get( $domain_id = 0 ) {

		// Sanity check.
		if ( empty( $domain_id ) ) {
			return [];
		}

		// Data integrity.
		$domain_id = (int) $domain_id;

		// Get all stored reference data.
		$setting = $this->reference_data_get_all();

		// Try to get reference data by key.
		$reference_data = ! empty( $setting[ $domain_id ] ) ? $setting[ $domain_id ] : [];

		/**
		 * Filters the reference data for this Domain when it is loaded.
		 *
		 * @since 1.0.9
		 *
		 * @param array $reference_data The reference data for all CiviCRM Domains.
		 * @param int   $domain_id The numeric ID of the CiviCRM Domain.
		 */
		$reference_data = apply_filters( 'cau/multidomain/domain/reference/get', $reference_data, $domain_id );

		// --<
		return $reference_data;

	}

	/**
	 * Sets the reference data for a given CiviCRM Domain ID.
	 *
	 * @since 1.0.9
	 *
	 * @param int   $domain_id The numeric ID of the CiviCRM Domain.
	 * @param array $reference_data The array of reference data for the CiviCRM Domain.
	 * @param bool  $save Passing true saves the option immediately, false does not.
	 */
	public function reference_data_set( $domain_id, $reference_data, $save = false ) {

		// Bail if we have no Domain ID.
		if ( empty( $domain_id ) ) {
			return;
		}

		// Data integrity.
		$domain_id = (int) $domain_id;

		// Get all stored reference data.
		$setting = $this->reference_data_get_all();

		/**
		 * Filters the reference data for this Domain before it is saved.
		 *
		 * @since 1.0.9
		 *
		 * @param array $reference_data The reference data for all CiviCRM Domains.
		 * @param int   $domain_id The numeric ID of the CiviCRM Domain.
		 */
		$reference_data = apply_filters( 'cau/multidomain/domain/reference/set', $reference_data, $domain_id );

		// Always overwrite.
		$setting[ $domain_id ] = $reference_data;

		// Store updated array.
		$this->plugin->multisite->setting_set( 'multidomain_reference', $setting );

		// Maybe save.
		if ( true === $save ) {
			$this->plugin->multisite->settings_save();
		}

	}

	/**
	 * Updates a key-value pair in the reference data for a given CiviCRM Domain ID.
	 *
	 * @since 1.0.9
	 *
	 * @param int    $domain_id The numeric ID of the CiviCRM Domain.
	 * @param string $key The array key to update.
	 * @param mixed  $value The value to assign to the array key.
	 * @param bool   $save Passing true saves the option immediately, false does not.
	 */
	public function reference_data_update( $domain_id, $key, $value, $save = false ) {

		// Bail if we have no Domain ID.
		if ( empty( $domain_id ) ) {
			return;
		}

		// Data integrity.
		$domain_id = (int) $domain_id;

		// Get stored reference data for this Domain ID.
		$data = $this->reference_data_get( $domain_id );

		// Always apply value.
		$data[ $key ] = $value;

		// Store updated array.
		$this->reference_data_set( $domain_id, $data, $save );

	}

	/**
	 * Deletes the reference data for a given CiviCRM Domain ID.
	 *
	 * @since 1.0.9
	 *
	 * @param int   $domain_id The numeric ID of the CiviCRM Domain.
	 * @param array $keys The array of keys to remove. When empty, removes the entry for the Domain ID.
	 * @param bool  $save Passing true saves the option immediately, false does not.
	 */
	public function reference_data_remove( $domain_id, $keys = [], $save = false ) {

		// Data integrity.
		$domain_id = (int) $domain_id;

		// Get all stored reference data.
		$setting = $this->reference_data_get_all();

		// Clear either key-value pairs or entire array.
		if ( empty( $keys ) ) {
			unset( $setting[ $domain_id ] );
		} else {
			foreach ( $keys as $key ) {
				unset( $setting[ $domain_id ][ $key ] );
			}
		}

		// Store updated array.
		$this->plugin->multisite->setting_set( 'multidomain_reference', $setting );

		// Maybe save.
		if ( true === $save ) {
			$this->plugin->multisite->settings_save();
		}

	}

}
