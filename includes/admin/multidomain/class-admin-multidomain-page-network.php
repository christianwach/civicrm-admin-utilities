<?php
/**
 * Multidomain Class.
 *
 * Handles Multidomain functionality.
 *
 * @package CiviCRM_Admin_Utilities
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Admin Utilities Multidomain Class.
 *
 * A class that encapsulates Multidomain Settings page functionality.
 *
 * @since 1.0.9
 */
class CAU_Admin_Multidomain_Page_Network extends CAU_Admin_Multidomain_Page_Base {

	/**
	 * Constructor.
	 *
	 * @since 1.0.9
	 *
	 * @param CiviCRM_Admin_Utilities_Multidomain $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Store references.
		$this->multidomain = $parent;
		$this->plugin      = $parent->plugin;

		// Bootstrap parent.
		parent::__construct();

	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.9
	 */
	public function register_hooks() {

		// Add Domain subpage to Network Settings menu.
		add_action( 'network_admin_menu', [ $this, 'admin_menu' ], 30 );

		// Add meta boxes to Network Domain subpage.
		add_action( 'cau/multidomain/network/settings/add_meta_boxes', [ $this, 'meta_boxes_add' ], 11, 1 );

	}

	// -------------------------------------------------------------------------

	/**
	 * Add network admin menu item(s) for this plugin.
	 *
	 * @since 1.0.9
	 */
	public function admin_menu() {

		// We must be network admin in Multisite.
		if ( ! is_super_admin() ) {
			return;
		}

		// Add settings page.
		$this->page_handle = add_submenu_page(
			'cau_network_parent', // Parent slug.
			__( 'CiviCRM Admin Utilities: Domain', 'civicrm-admin-utilities' ), // Page title.
			__( 'CiviCRM Admin Utilities', 'civicrm-admin-utilities' ), // Menu title.
			'manage_network_plugins', // Required caps.
			'cau_network_multidomain', // Slug name.
			[ $this, 'page_render' ] // Callback.
		);

		// Register our form submit hander.
		add_action( 'load-' . $this->page_handle, [ $this, 'form_submitted' ] );

		// Ensure correct menu item is highlighted.
		add_action( 'admin_head-' . $this->page_handle, [ $this->plugin->multisite, 'network_menu_highlight' ], 50 );

		// Add help text.
		add_action( 'admin_head-' . $this->page_handle, [ $this, 'admin_head' ], 50 );

		// Add scripts and styles.
		add_action( 'admin_print_scripts-' . $this->page_handle, [ $this, 'admin_scripts' ] );
		add_action( 'admin_print_styles-' . $this->page_handle, [ $this, 'admin_styles' ] );

		// Filter the list of Single Site subpages and add Multidomain page.
		add_filter( 'civicrm_admin_utilities_network_subpages', [ $this, 'page_subpages_filter' ] );

		// Filter the list of network page URLs and add Multidomain page URL.
		add_filter( 'civicrm_admin_utilities_network_page_urls', [ $this, 'page_urls_filter' ] );

		// Filter the "show tabs" flag for setting templates.
		add_filter( 'civicrm_admin_utilities_network_show_tabs', [ $this, 'page_show_tabs' ] );

		// Add tab to setting templates.
		add_filter( 'civicrm_admin_utilities_network_nav_tabs', [ $this, 'page_add_tab' ], 10, 2 );

	}

	/**
	 * Initialise plugin help for network admin.
	 *
	 * @since 1.0.9
	 */
	public function admin_head() {

		// Get screen object.
		$screen = get_current_screen();

		// Pass to method in this class.
		$this->admin_help( $screen );

		// Enqueue WordPress scripts.
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'dashboard' );

	}

	/**
	 * Enqueue stylesheet for the Network Admin Domain page.
	 *
	 * @since 1.0.9
	 */
	public function admin_styles() {

		// Register Select2 styles.
		wp_register_style(
			'cau_network_domain_select2_css',
			set_url_scheme( 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css' ),
			false,
			'4.0.13',
			'all'
		);

		// Enqueue styles.
		wp_enqueue_style( 'cau_network_domain_select2_css' );

		// Add stylesheet.
		wp_enqueue_style(
			'cau_network_domain_css',
			plugins_url( 'assets/css/civicrm-admin-utilities-network-multidomain.css', CIVICRM_ADMIN_UTILITIES_FILE ),
			false,
			CIVICRM_ADMIN_UTILITIES_VERSION, // Version.
			'all' // Media.
		);

	}

	/**
	 * Enqueue Javascripts on the Site Domain page.
	 *
	 * @since 1.0.9
	 */
	public function admin_scripts() {

		// Register Select2.
		wp_register_script(
			'cau_network_domain_select2_js',
			set_url_scheme( 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js' ),
			[ 'jquery' ],
			'4.0.13',
			true
		);

		// Enqueue Select2 script.
		wp_enqueue_script( 'cau_network_domain_select2_js' );

		// Enqueue our Javascript plus dependencies.
		wp_enqueue_script(
			'cau_network_domain_js',
			plugins_url( 'assets/js/civicrm-admin-utilities-network-multidomain.js', CIVICRM_ADMIN_UTILITIES_FILE ),
			[ 'jquery', 'cau_network_domain_select2_js' ],
			CIVICRM_ADMIN_UTILITIES_VERSION, // Version.
			true
		);

		// Get the Sites on the current Network.
		$sites = $this->sites_data_get();

		// Get the Sites that have already been assigned.
		$domains_data = $this->multidomain->reference_data_get_all();
		$sites_used   = ! empty( $domains_data ) ? array_column( $domains_data, 'site_id' ) : [];

		// Localisation array.
		$vars = [
			'localisation' => [
				'placeholder' => esc_html__( 'Select a WordPress Site', 'civicrm-admin-utilities' ),
			],
			'settings'     => [
				'sites'   => $sites,
				'used'    => $sites_used,
				'options' => [],
				'bridge'  => 0,
			],
		];

		// Localise with WordPress function.
		wp_localize_script(
			'cau_network_domain_js',
			'CAU_Network_Domain',
			$vars
		);

	}

	/**
	 * Adds help copy to network admin page.
	 *
	 * @since 1.0.9
	 *
	 * @param object $screen The existing WordPress screen object.
	 * @return object $screen The amended WordPress screen object.
	 */
	public function admin_help( $screen ) {

		// Init page IDs.
		$pages = [
			$this->page_handle . '-network',
		];

		// Kick out if not our screen.
		if ( ! in_array( $screen->id, $pages, true ) ) {
			return $screen;
		}

		// Build tab args.
		$args = [
			'id'      => 'cau_network_multidomain',
			'title'   => __( 'Domains', 'civicrm-admin-utilities' ),
			'content' => $this->admin_help_get(),
		];

		// Add a tab - we can add more later.
		$screen->add_help_tab( $args );

		// --<
		return $screen;

	}

	/**
	 * Get help text for network admin.
	 *
	 * @since 1.0.9
	 *
	 * @return string $help The help text formatted as HTML.
	 */
	public function admin_help_get() {

		// Build path to help template.
		$template = CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/wordpress/settings/help/page-multidomain-network-help.php';

		// Use contents of help template.
		ob_start();
		require_once $template;
		$help = ob_get_clean();

		// --<
		return $help;

	}

	// -------------------------------------------------------------------------

	/**
	 * Show our network Multidomain Settings page.
	 *
	 * @since 1.0.9
	 */
	public function page_render() {

		// Disallow if not network admin in Multisite.
		if ( is_network_admin() && ! is_super_admin() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'civicrm-admin-utilities' ) );
		}

		// Check user permissions.
		if ( ! current_user_can( 'manage_network_plugins' ) ) {
			return;
		}

		// Get admin page URLs.
		$urls = $this->plugin->multisite->page_get_network_urls();

		// Get current screen.
		$screen = get_current_screen();

		/**
		 * Allow meta boxes to be added to this screen.
		 *
		 * The Screen ID to use is: "civicrm_page_cwps_settings".
		 *
		 * @since 1.0.9
		 *
		 * @param str $screen_id The ID of the current screen.
		 */
		do_action( 'cau/multidomain/network/settings/add_meta_boxes', $screen->id, null );

		// Grab columns.
		$columns = ( 1 === $screen->get_columns() ? '1' : '2' );

		// Get domains.
		$domains = $this->plugin->civicrm->domain->get_all();

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/network-multidomain.php';

	}

	/**
	 * Append the Multidomain Settings page to network subpages.
	 *
	 * This ensures that the correct parent menu item is highlighted for our
	 * Multidomain subpage in Multisite installs.
	 *
	 * @since 1.0.9
	 *
	 * @param array $subpages The existing list of subpages.
	 * @return array $subpages The modified list of subpages.
	 */
	public function page_subpages_filter( $subpages ) {

		// Add Multidomain Settings page.
		$subpages[] = 'cau_network_multidomain';

		// --<
		return $subpages;

	}

	/**
	 * Append the Multidomain page URL to network subpage URLs.
	 *
	 * @since 1.0.9
	 *
	 * @param array $urls The existing list of URLs.
	 * @return array $urls The modified list of URLs.
	 */
	public function page_urls_filter( $urls ) {

		// Add Multidomain Settings page.
		$urls['multidomain'] = $this->plugin->multisite->network_menu_page_url( 'cau_network_multidomain', false );

		// --<
		return $urls;

	}

	/**
	 * Show subpage tabs on network settings pages.
	 *
	 * @since 1.0.9
	 *
	 * @param bool $show_tabs True if tabs are shown, false otherwise.
	 * @return bool $show_tabs True if tabs are to be shown, false otherwise.
	 */
	public function page_show_tabs( $show_tabs ) {

		// Always show tabs.
		$show_tabs = true;

		// --<
		return $show_tabs;

	}

	/**
	 * Add subpage tab to tabs on network settings pages.
	 *
	 * @since 1.0.9
	 *
	 * @param array $urls The array of subpage URLs.
	 * @param str   $active_tab The key of the active tab in the subpage URLs array.
	 */
	public function page_add_tab( $urls, $active_tab ) {

		// Define title.
		$title = __( 'Domains', 'civicrm-admin-utilities' );

		// Default to inactive.
		$active = '';

		// Make active if it's our subpage.
		if ( 'multidomain' === $active_tab ) {
			$active = ' nav-tab-active';
		}

		// Render tab. URL is already escaped.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<a href="' . $urls['multidomain'] . '" class="nav-tab' . esc_attr( $active ) . '">' . esc_html( $title ) . '</a>' . "\n";

	}

	// -------------------------------------------------------------------------


	/**
	 * Register meta boxes for our Network "Domains" page.
	 *
	 * @since 1.0.9
	 *
	 * @param str $screen_id The Admin Page Screen ID.
	 */
	public function meta_boxes_add( $screen_id ) {

		// Define valid Screen IDs.
		$screen_ids = [
			'admin_page_cau_network_multidomain-network',
		];

		// Bail if not the Screen ID we want.
		if ( ! in_array( $screen_id, $screen_ids, true ) ) {
			return;
		}

		// Bail if user does not have permission.
		if ( ! current_user_can( 'manage_network_plugins' ) ) {
			return;
		}

		// Get Domains.
		$data['domains'] = $this->domains_data_get();

		// Check if "CiviCRM Multisite" extension is active.
		$data['multisite'] = false;
		if ( ! empty( $this->plugin->civicrm->extension_is_enabled( 'org.civicrm.multisite' ) ) ) {
			$data['multisite'] = true;
		}

		/**
		 * Filters the array of data to be shared with all metaboxes.
		 *
		 * @since 1.0.9
		 *
		 * @param array $data The default array of metabox data.
		 * @param string $screen_id The Screen indentifier.
		 */
		$data = apply_filters( 'cau/multidomain/network/settings/page/meta_boxes_data', $data, $screen_id );

		// Create CiviCRM Network Settings metabox.
		add_meta_box(
			'civicrm_au_network_domains',
			__( 'CiviCRM Domains', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_domain_info_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core', // Vertical placement: options are 'core', 'high', 'low'.
			$data
		);

		// Bail if "multisite" is not present.
		if ( false === $data['multisite'] ) {
			return;
		}

		// Create "Create Domain" metabox.
		add_meta_box(
			'civicrm_au_network_domain_create',
			__( 'Create Domain', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_domain_create_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core', // Vertical placement: options are 'core', 'high', 'low'.
			$data
		);

		// Create Submit metabox.
		add_meta_box(
			'submitdiv',
			__( 'Settings', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_submit_render' ], // Callback.
			$screen_id, // Screen ID.
			'side', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

		/**
		 * Broadcast that the metaboxes have been added.
		 *
		 * @since 1.0.9
		 *
		 * @param string $screen_id The Screen indentifier.
		 * @param array $data The array of metabox data.
		 */
		do_action( 'cau/multidomain/network/settings/page/meta_boxes_added', $screen_id, $data );

	}

	/**
	 * Render a Submit meta box for our Network "Domain" page.
	 *
	 * @since 1.0.9
	 */
	public function meta_box_submit_render() {

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/network-metabox-domain-submit.php';

	}

	/**
	 * Render "CiviCRM Domains" meta box for our Network "Domain" page.
	 *
	 * @since 1.0.9
	 *
	 * @param mixed $unused Unused param.
	 * @param array $metabox Array containing id, title, callback, and args elements.
	 */
	public function meta_box_domain_info_render( $unused = null, $metabox = [] ) {

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/network-metabox-domain-info.php';

	}

	/**
	 * Render "Create Domain" meta box for our Network "Domain" page.
	 *
	 * @since 1.0.9
	 *
	 * @param mixed $unused Unused param.
	 * @param array $metabox Array containing id, title, callback, and args elements.
	 */
	public function meta_box_domain_create_render( $unused = null, $metabox = [] ) {

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/network-metabox-domain-create.php';

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the URL for the form action.
	 *
	 * @since 1.0.9
	 *
	 * @return string $target_url The URL for the admin form action.
	 */
	public function form_submit_url_get() {

		// Use Site Multi Domain admin page URL.
		$target_url = $this->plugin->multisite->network_menu_page_url( 'cau_network_multidomain', false );

		// --<
		return $target_url;

	}

	/**
	 * Route settings updates to relevant methods.
	 *
	 * @since 1.0.9
	 */
	public function form_submitted() {

		// Was the "Network Domain" form submitted?
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['cau_network_multidomain_submit'] ) ) {
			$this->form_save();
			$url = $this->plugin->multisite->network_menu_page_url( 'cau_network_multidomain', false );
			$this->form_redirect( $url );
		}

	}

	/**
	 * Applies updates from the data supplied by our Network Multidomain Settings page.
	 *
	 * @since 1.0.9
	 */
	private function form_save() {

		// Check that we trust the source of the data.
		check_admin_referer( 'cau_network_multidomain_action', 'cau_network_multidomain_nonce' );

		// Get data for all Domains.
		$domains = $this->domains_data_get();

		/**
		 * Fires when the Network Multidomain update process is finished.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/multidomain/network/settings/form_save/pre' );

		// Save mappings and reference data for each CiviCRM Domain.
		foreach ( $domains as $domain ) {

			// Make sure the Domain ID is an integer.
			$domain_id = (int) $domain['domain_id'];

			// Sanitise new Site ID input.
			$key     = 'cau_site_id-' . $domain_id;
			$site_id = isset( $_POST[ $key ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : false;

			// Update mappings.
			if ( empty( $site_id ) ) {
				$this->multidomain->mapping_site_remove( $domain_id, (int) $domain['site_id'] );
			} elseif ( $site_id !== (int) $domain['site_id'] ) {
				$this->multidomain->mapping_site_remove( $domain_id, (int) $domain['site_id'] );
				$this->multidomain->mapping_site_assign( $domain_id, $site_id );
			} else {
				$this->multidomain->mapping_site_assign( $domain_id, $site_id );
			}

			// Update Site ID in reference data.
			if ( empty( $site_id ) ) {
				$this->multidomain->reference_data_remove( $domain_id, [ 'site_id' ] );
			} else {
				$this->multidomain->reference_data_update( $domain_id, 'site_id', $site_id );
			}

			// Update Domain Group ID in reference data.
			if ( empty( $domain['domain_group_id'] ) ) {
				$this->multidomain->reference_data_remove( $domain_id, [ 'group_id' ] );
			} else {
				$this->multidomain->reference_data_update( $domain_id, 'group_id', (int) $domain['domain_group_id'] );
			}

			// Update Domain Organisation ID in reference data.
			if ( empty( $domain['domain_org_id'] ) ) {
				$this->multidomain->reference_data_remove( $domain_id, [ 'org_id' ] );
			} else {
				$this->multidomain->reference_data_update( $domain_id, 'org_id', (int) $domain['domain_org_id'] );
			}

		}

		// Save the settings.
		$this->plugin->multisite->settings_save();

		// Sanitise new Domain input.
		$domain_name = isset( $_POST['cau_domain_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cau_domain_name'] ) ) : '';

		// Maybe create a new Multisite Domain.
		if ( ! empty( $domain_name ) ) {

			// Use the APIv3 call provided by the CiviCRM Multisite Extension.
			$domain_id = $this->plugin->civicrm->domain->multisite_create( $domain_name );

			// When there's no error.
			if ( ! empty( $domain_id ) ) {

				// Get full Domain data.
				$domain = $this->plugin->civicrm->domain->get_by_id( $domain_id );

				// When there's no error.
				if ( ! empty( $domain ) ) {

					// Get Domain info.
					$site_id      = $this->multidomain->mapping_site_get( $domain_id );
					$domain_org   = $this->plugin->civicrm->domain->org_get_by_id( (int) $domain['contact_id'] );
					$domain_group = $this->plugin->civicrm->domain->group_get_for_domain( $domain_id );

					// Init reference data.
					$data = [];

					// Maybe add info.
					if ( ! empty( $site_id ) ) {
						$data['site_id'] = $site_id;
					}
					if ( ! empty( $domain_org['id'] ) ) {
						$data['org_id'] = (int) $domain_org['id'];
					}
					if ( ! empty( $domain_group['id'] ) ) {
						$data['group_id'] = (int) $domain_group['id'];
					}

					// Set reference data.
					$this->multidomain->reference_data_set( $domain_id, $data );

					// Save the paths data.
					$this->paths_data_save( $domain_id );

					// Save the settings.
					$this->plugin->multisite->settings_save();

				}

			}

		}

		/**
		 * Fires when the Network Multidomain update process is finished.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/multidomain/network/settings/form_saved' );

	}

	// -------------------------------------------------------------------------

	/**
	 * Try and set a default Extensions path for the new Domain.
	 *
	 * @since 1.0.9
	 *
	 * @param int $domain_id The ID of the Domain.
	 */
	private function paths_data_save( $domain_id ) {

		// Init data.
		$data = [];

		// Init path.
		$extensions_path = '';

		/*
		 * Fetch the current Domain, which should be the one for the main WordPress Site,
		 * since we're in Network admin.
		 */
		$current_domain = $this->plugin->civicrm->domain->get_by_id();

		// Try the path for the current Domain.
		if ( ! empty( $current_domain ) ) {
			$paths = $this->multidomain->paths_get( (int) $current_domain['id'] );
			if ( ! empty( $paths['extensions_path'] ) ) {
				$extensions_path = $paths['extensions_path'];
			}
		}

		// Try the config for the current Domain.
		if ( empty( $extensions_path ) ) {
			$config = $this->plugin->civicrm->config_get();
			if ( ! empty( $config ) ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$extensions_path = $config->extensionsDir;
			}
		}

		// Try the setting for the current Domain.
		if ( empty( $extensions_path ) ) {
			$extensions_path = $this->plugin->civicrm->setting_get( 'extensionsDir' );
		}

		// Set the Extensions path if we get something.
		if ( ! empty( $extensions_path ) ) {
			$data['extensions_path'] = $extensions_path;
		}

		// Set paths if we get some data..
		if ( ! empty( $data ) ) {
			$this->multidomain->paths_set( $domain_id, $data );
		}

	}

	/**
	 * Gets the data for all WordPress Sites.
	 *
	 * @since 1.0.9
	 *
	 * @return array $sites The array of WordPress Site objects, or empty array on failure.
	 */
	private function sites_data_get() {

		// Init Sites data.
		static $sites = [];

		// Return early if already queried.
		if ( ! empty( $sites ) ) {
			return $sites;
		}

		/*
		 * Get as many sites as we feel comfortable with.
		 *
		 * This might need to be based on `wp_is_large_network()` in future.
		 */
		$args = [
			'number' => 1000,
		];

		// Build the array of Site data.
		$query = get_sites( $args );
		foreach ( $query as $site ) {
			$sites[] = [
				'id'   => (int) $site->blog_id,
				'text' => $site->blogname,
			];
		}

		// --<
		return $sites;

	}

	/**
	 * Gets the Domain data for a given ID.
	 *
	 * @since 1.0.9
	 *
	 * @return array $domains The array of Domain data, or empty array on failure.
	 */
	private function domains_data_get() {

		// Init return array.
		$domains = [];

		// Get all CiviCRM Domains.
		$domains_info = $this->plugin->civicrm->domain->get_all();
		if ( empty( $domains_info ) ) {
			return $domains;
		}

		// Get the full array of Sites.
		$sites = $this->sites_data_get();

		// Build data array.
		foreach ( $domains_info as $domain ) {

			// Get the mapped WordPress Site ID.
			$site_id = $this->multidomain->mapping_site_get( (int) $domain['id'] );

			// Should we show a multi-select?
			if ( is_array( $site_id ) ) {
				$site_id = __( 'Cannot handle multiple WordPress Sites yet', 'civicrm-admin-utilities' );
			}

			// Find the Site name.
			$site_name = __( 'Cannot find Site name', 'civicrm-admin-utilities' );
			if ( is_int( $site_id ) ) {
				foreach ( $sites as $site ) {
					if ( $site['id'] === $site_id ) {
						$site_name = $site['text'];
						break;
					}
				}
			}

			// Get the name of the Domain Organisation.
			$domain_org_name = __( 'No Organisation Set', 'civicrm-admin-utilities' );
			$domain_org      = $this->plugin->civicrm->domain->org_get_by_id( (int) $domain['contact_id'] );
			if ( ! empty( $domain_org ) ) {
				$domain_org_name = $domain_org['display_name'];
			}

			// Get the name of the Domain Group.
			$domain_group_name = __( 'No Group Set', 'civicrm-admin-utilities' );
			$domain_group      = $this->plugin->civicrm->domain->group_get_for_domain( (int) $domain['id'] );
			if ( ! empty( $domain_group ) ) {
				$domain_group_name = $domain_group['title'];
			}

			// Populate info array.
			$domain_info = [
				'name'         => $domain['name'],
				'site_id'      => $site_id,
				'site_name'    => $site_name,
				'domain_id'    => (int) $domain['id'],
				'domain_group' => $domain_group_name,
				'domain_org'   => $domain_org_name,
			];

			// Maybe add Domain Group ID.
			if ( ! empty( $domain_group ) ) {
				$domain_info['domain_group_id'] = (int) $domain_group['id'];
			}

			// Maybe add Domain Organisation ID.
			if ( ! empty( $domain_org ) ) {
				$domain_info['domain_org_id'] = (int) $domain_org['id'];
			}

			// Populate return array with the items we want.
			$domains[] = $domain_info;

		}

		// --<
		return $domains;

	}

}
