<?php

//use CRM_CAUTheme_ExtensionUtil as E;

/**
 * CiviCRM Admin Utilities Theme class.
 *
 * A class for encapsulating a "CiviCRM Admin Utilities" Theme.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.7.4
 */
class CiviCRM_Admin_Utilities_Theme {

  /**
   * Plugin object.
   *
   * @since 0.7.4
   * @access public
   * @var object
   */
  public $plugin;

  /**
   * Theme "slug".
   *
   * @since 0.7.4
   * @access public
   * @var string
   */
  public $slug = 'cautheme';

  /**
   * Constructor.
   *
   * @since 0.7.4
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
   * @since 0.7.4
   */
  public function initialise() {

    // Load our Resolver class.
    require CIVICRM_ADMIN_UTILITIES_PATH . 'assets/civicrm/cautheme/civicrm-admin-utilities-resolver.php';

    // Register hooks.
    add_action( 'civicrm_themes', [ $this, 'register_theme' ], 10, 1 );
    add_action( 'civicrm_alterBundle', [ $this, 'modify_bundle' ], 10, 1 );
    add_action( 'civicrm_admin_utilities_styles_admin', [ $this, 'toggle' ], 10, 1 );

    // Listen for changes to the Theme setting.
    add_action( 'civicrm_postSave_civicrm_setting', [ $this, 'settings_change' ], 10 );

    // Make sure that the Theme is enabled if the setting is enabled.
    add_action( 'civicrm_config', [ $this, 'activate_theme' ], 10 );

  }

  /**
   * Check if we want to allow the Theme functionality in this class.
   *
   * @since 0.7.4
   *
   * @return bool $allowed True if we do, false otherwise.
   */
  public function is_allowed() {

		// Bail if no CiviCRM.
		if ( ! doing_action( 'civicrm_config' ) ) {
      if ( ! $this->plugin->is_civicrm_initialised() ) {
        return false;
      }
		}

    // Only do this once.
    static $allowed;
    if ( isset( $allowed ) ) {
      return $allowed;
    }

    // Ignore anything but 5.31+.
    $version = CRM_Utils_System::version();
		$parts = explode( '.', $version );
		$major_version = $parts[0] . '.' . $parts[1];

    if ( version_compare( $major_version, '5.31', '>=' ) ) {
      $allowed = true;
    } else {
      $allowed = false;
    }

    // --<
    return $allowed;

  }

  /**
   * Register our Theme.
   *
   * @since 0.7.4
   *
   * @param array $themes The array of themes.
   */
  public function register_theme( &$themes ) {

    // Ignore anything but 5.31+.
    if ( ! $this->is_allowed() ) {
      return;
    }

    // Add setup to themes array.
    $themes[$this->slug] = [
      'ext' => $this->slug,
      'title' => __ ( 'Radstock (CAU)', 'civicrm-admin-utilities' ),
      'help' => __( 'Gives CiviCRM a look-and-feel that is closer to WordPress', 'civicrm-admin-utilities' ),
      'url_callback' => 'CiviCRM_Admin_Utilities_Resolver::resolve',
      'search_order' => [
        $this->slug,
        'greenwich',
        '_fallback_',
      ],
      'excludes' => [
        'css/civicrm.css',
      ],
    ];

  }

  /**
   * Maybe modify a bundle.
   *
   * @since 0.7.4
   *
   * @param object $bundle The bundle of Theme resources.
   */
  public function modify_bundle( CRM_Core_Resources_Bundle $bundle ) {

    // Ignore anything but 5.31+.
    if ( ! $this->is_allowed() ) {
      return;
    }

    // Get the Theme identifier.
    $theme = Civi::service( 'themes' )->getActiveThemeKey();

    // Add in the Bootstrap resources from the "Greenwich" Theme.
    switch( $theme . ':' . $bundle->name ) {
      case 'cautheme:bootstrap3' :
        $bundle->clear();

        // We have to add the URLs directly because CiviCRM fails to resolve paths.
        $bundle->addStyleUrl( CRM_Greenwich_ExtensionUtil::url( 'dist/bootstrap3.css' ) );
        $bundle->addScriptUrl( CRM_Greenwich_ExtensionUtil::url( 'extern/bootstrap3/assets/javascripts/bootstrap.min.js' ) );
        $bundle->addScriptUrl( CRM_Greenwich_ExtensionUtil::url( 'js/noConflict.js' ) );

        break;
    }

  }

  /**
   * Is the current Theme our Theme?
   *
   * @since 0.8.1
   *
   * @return bool True if the CAU Theme is active, false otherwise.
   */
  public function is_cau_theme() {

		// If it's our Theme, return true.
    $theme = $this->get_theme();
		if ( $theme == $this->slug ) {
			return true;
		}

		// Fall back to false.
		return false;

  }

  /**
   * Get the current Theme.
   *
   * @since 0.8
   *
   * @return str $theme The current Theme "slug", empty otherwise.
   */
  public function get_theme() {

    // Ignore anything but 5.31+.
    if ( ! $this->is_allowed() ) {
      return '';
    }

    // Switch setting to our Theme or the default.
    $params = [
      'version' => 3,
      'name' => 'theme_backend',
      'group' => 'CiviCRM Preferences',
    ];

    // Get the setting.
    $theme = civicrm_api( 'Setting', 'getvalue', $params );

    // --<
    return $theme;

  }

  /**
   * Maybe enable our Theme.
   *
   * @since 0.7.4
   */
  public function activate_theme() {

    // Ignore anything but 5.31+.
    if ( ! $this->is_allowed() ) {
      return;
    }

    // Ignore when our Admin Theme is not enabled.
    if ( $this->plugin->single->setting_get( 'css_admin', '1' ) == '0' ) {
      return;
    }

    // Ignore if we've done this before.
    if ( $this->plugin->single->setting_get( 'theme_sync', '1' ) == '0' ) {
      return;
    }

    // Get the setting.
    $theme = $this->get_theme();

		// Bail if it's our Theme.
		if ( $theme == $this->slug ) {
			return;
		}

		// Set it to our Theme.
		$this->toggle();

		// Set a flag in our plugin settings.
		$this->plugin->single->setting_set( 'theme_sync', '1' );
		$this->plugin->single->settings_save();

  }

  /**
   * Maybe disable our Theme.
   *
   * @since 0.8
   */
  public function deactivate_theme() {

    // Ignore anything but 5.31+.
    if ( ! $this->is_allowed() ) {
      return;
    }

    // Ignore when our Admin Theme is not enabled.
    if ( $this->plugin->single->setting_get( 'css_admin', '1' ) == '0' ) {
      return;
    }

    // Get the setting.
    $theme = $this->get_theme();

		// Bail if it's not our Theme.
		if ( $theme != $this->slug ) {
			return;
		}

		// Set it to the default Theme.
		$this->toggle( 'disable' );

		// Set a flag in our plugin settings.
		$this->plugin->single->setting_set( 'theme_sync', '0' );
		$this->plugin->single->settings_save();

  }

  /**
   * Enable or disable our Theme.
   *
   * @since 0.7.4
   *
   * @param str $action The action to perform  - either 'enable' or 'disable'.
   */
  public function toggle( $action = 'enable' ) {

    // Ignore anything but 5.31+.
    if ( ! $this->is_allowed() ) {
      return;
    }

    // Bail when disabling and the backend Theme is not ours.
    $theme = $this->get_theme();
		if ( 'disable' === $action && $theme !== $this->slug ) {
			return;
		}

    // Prevent reverse sync.
    remove_action( 'civicrm_postSave_civicrm_setting', [ $this, 'settings_change' ], 10 );

    // Switch setting to our Theme or the default.
    $params = [
      'version' => 3,
      'theme_backend' => ( $action === 'enable' ) ? $this->slug : 'default',
    ];

    // Save the setting.
    $result = civicrm_api( 'Setting', 'create', $params );

    // Reinstate reverse sync.
    add_action( 'civicrm_postSave_civicrm_setting', [ $this, 'settings_change' ], 10 );

  }

  /**
   * Reverse sync with the CiviCRM Admin Utilities setting.
   *
   * When CiviCRM settings are saved, this method is called post-save. It then
   * reverse-syncs to the internal setting depending on whether the Theme that
   * is chosen is ours.
   *
   * @since 0.7.4
   *
   * @param obj $dao The CiviCRM database access object.
   */
  public function settings_change( $dao ) {

    // Bail if not a setting.
    if ( ! ( $dao instanceOf CRM_Core_DAO_Setting ) ) {
      return;
    }

    // Bail if not the Theme setting.
    if ( ! isset( $dao->name ) || $dao->name != 'theme_backend' ) {
      return;
    }

    // Set or unset the internal setting.
    if ( isset( $dao->value ) && $this->slug == unserialize( $dao->value ) ) {
      if ( $this->plugin->single->setting_get( 'css_admin', '1' ) == '0' ) {
        $this->plugin->single->setting_set( 'css_admin', '1' );
        $this->plugin->single->settings_save();
      }
    } else {
      if ( $this->plugin->single->setting_get( 'css_admin', '0' ) == '1' ) {
        $this->plugin->single->setting_set( 'css_admin', '0' );
        $this->plugin->single->settings_save();
      }
    }

  }

}
