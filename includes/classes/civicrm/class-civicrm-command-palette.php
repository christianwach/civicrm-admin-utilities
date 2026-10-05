<?php
/**
 * CiviCRM Command Palette Class.
 *
 * Exposes CiviCRM navigation in the WordPress command palette.
 *
 * @package CiviCRM_Admin_Utilities
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Command Palette Class.
 *
 * Keeps command palette integration separate from CiviCRM menu styling.
 */
class CAU_CiviCRM_Command_Palette {

	/**
	 * Plugin object.
	 *
	 * @access public
	 * @var CiviCRM_Admin_Utilities
	 */
	public $plugin;

	/**
	 * CiviCRM object.
	 *
	 * @access public
	 * @var CAU_CiviCRM
	 */
	public $civicrm;

	/**
	 * Constructor.
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
	 */
	private function register_hooks() {

		add_action( 'admin_enqueue_scripts', [ $this, 'command_palette_enqueue' ], 20 );
		add_action( 'admin_bar_menu', [ $this, 'command_palette_label' ], 2001 );

	}

	/**
	 * Add CiviCRM menu links to WordPress's built-in command palette.
	 */
	public function command_palette_enqueue() {
		if ( ! wp_script_is( 'wp-core-commands', 'enqueued' ) ) {
			return;
		}

		$settings = is_network_admin() ? $this->plugin->multisite : $this->plugin->single;
		if ( '1' !== $settings->setting_get( 'command_palette', '0' ) ) {
			return;
		}

		// Respect CAU's restriction of CiviCRM to the main site.
		if ( is_multisite() && ! is_main_site() && $this->civicrm->is_network_activated()
			&& '1' === $this->plugin->multisite->setting_get( 'main_site_only', '0' ) ) {
			return;
		}
		if ( ! $this->civicrm->is_initialised() || ! CRM_Core_Permission::check( 'access CiviCRM' ) ) {
			return;
		}
		$contact_id = CRM_Core_Session::getLoggedInContactID();
		if ( ! $contact_id ) {
			return;
		}

		// Match core's user, locale and menu cache variation.
		$query = http_build_query( [
			'code'   => CRM_Core_BAO_Navigation::getCacheKey( $contact_id ),
			'locale' => CRM_Core_I18n::getLocale(),
			'cid'    => $contact_id,
		] );
		$url = CRM_Utils_System::url( 'civicrm/ajax/navmenu', $query, true, null, false, false, true );

		// Load the JavaScript that registers CiviCRM menu links as WordPress commands.
		wp_enqueue_script(
			'civicrm-admin-utilities-command-palette',
			plugins_url( 'assets/js/civicrm-admin-utilities-command-palette.js', CIVICRM_ADMIN_UTILITIES_FILE ),
			[ 'wp-commands', 'wp-data', 'wp-dom-ready', 'wp-html-entities' ],
			CIVICRM_ADMIN_UTILITIES_VERSION,
			true
		);
		// WordPress's toolbar icon rule overrides Dashicons, so target this icon explicitly.
		wp_add_inline_style( 'admin-bar', '#wpadminbar #wp-admin-bar-command-palette .ab-icon.dashicons-menu:before { content: "\\f333"; }' );
		// Define cauCommandPalette.url before the script loads so it knows where to fetch CiviCRM's menu JSON.
		wp_add_inline_script(
			'civicrm-admin-utilities-command-palette',
			'const cauCommandPalette = ' . wp_json_encode( [ 'url' => $url ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';',
			'before'
		);
	}

	/**
	 * Label the WordPress command palette while preserving its keyboard shortcut.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar The WordPress admin bar.
	 */
	public function command_palette_label( $wp_admin_bar ) {
		if ( ! wp_script_is( 'civicrm-admin-utilities-command-palette', 'enqueued' ) ) {
			return;
		}
		$node = $wp_admin_bar->get_node( 'command-palette' );
		if ( $node ) {
			$node->title = str_replace( 'class="ab-icon"', 'class="ab-icon dashicons-menu"', $node->title );
			$node->title = str_replace( '<kbd>', esc_html__( 'Menu', 'civicrm-admin-utilities' ) . ' <kbd>', $node->title );
			$wp_admin_bar->add_node( (array) $node );
		}
	}


}
