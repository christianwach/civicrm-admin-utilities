<?php
/**
 * Multidomain Single Site Class.
 *
 * Handles Multidomain functionality on Single Sites.
 *
 * @package CiviCRM_Admin_Utilities
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Multidomain Single Site Class.
 *
 * A class that encapsulates functionality on Single Sites.
 *
 * @since 1.0.9
 */
class CAU_Admin_Multidomain_Page_Site extends CAU_Admin_Multidomain_Page_Base {

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

		// Add Domain subpage to Single Site Settings menu.
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );

		// Add meta boxes to Single Site Domain subpage.
		add_action( 'cau/multidomain/settings/add_meta_boxes', [ $this, 'meta_boxes_add' ], 11, 1 );

	}

	// -------------------------------------------------------------------------


	/**
	 * Add admin menu item(s) for this plugin.
	 *
	 * @since 1.0.9
	 */
	public function admin_menu() {

		/**
		 * Set capability but allow overrides.
		 *
		 * @since 1.0.9
		 *
		 * @param str The default capability for access to domain page.
		 */
		$capability = apply_filters( 'civicrm_admin_utilities_page_domain_cap', 'manage_options' );

		// Check user permissions.
		if ( ! current_user_can( $capability ) ) {
			return;
		}

		// Add Domain page.
		$this->page_handle = add_submenu_page(
			'cau_parent', // Parent slug.
			__( 'CiviCRM Admin Utilities - Domain', 'civicrm-admin-utilities' ), // Page title.
			__( 'Domain', 'civicrm-admin-utilities' ), // Menu title.
			$capability, // Required caps.
			'cau_multidomain', // Slug name.
			[ $this, 'page_render' ] // Callback.
		);

		// Register our form submit hander.
		add_action( 'load-' . $this->page_handle, [ $this, 'form_submitted' ] );

		// Ensure correct menu item is highlighted.
		add_action( 'admin_head-' . $this->page_handle, [ $this->plugin->single, 'admin_menu_highlight' ], 50 );

		// Add help text.
		add_action( 'admin_head-' . $this->page_handle, [ $this, 'admin_head' ], 50 );

		// Add scripts and styles.
		add_action( 'admin_print_scripts-' . $this->page_handle, [ $this, 'admin_scripts' ] );
		add_action( 'admin_print_styles-' . $this->page_handle, [ $this, 'admin_styles' ] );

		// Filter the list of Single Site subpages and add Multidomain page.
		add_filter( 'civicrm_admin_utilities_subpages', [ $this, 'page_subpages_filter' ] );

		// Filter the list of Single Site page URLs and add Multidomain page URL.
		add_filter( 'civicrm_admin_utilities_page_urls', [ $this, 'page_urls_filter' ] );

		// Filter the "show tabs" flag for setting templates.
		add_filter( 'civicrm_admin_utilities_show_tabs', [ $this, 'page_show_tabs' ] );

		// Add tab to setting templates.
		add_filter( 'civicrm_admin_utilities_settings_nav_tabs', [ $this, 'page_add_tab' ], 10, 2 );

	}

	/**
	 * Initialise plugin help.
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
	 * Enqueue stylesheets for the Site Domain page.
	 *
	 * @since 1.0.9
	 */
	public function admin_styles() {

		// Register Select2 styles.
		wp_register_style(
			'cau_site_domain_select2_css',
			set_url_scheme( 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css' ),
			false,
			'4.0.13',
			'all'
		);

		// Enqueue styles.
		wp_enqueue_style( 'cau_site_domain_select2_css' );

		// Add page-specific stylesheet.
		wp_enqueue_style(
			'cau_site_domain_css',
			plugins_url( 'assets/css/civicrm-admin-utilities-site-multidomain.css', CIVICRM_ADMIN_UTILITIES_FILE ),
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
			'cau_site_domain_select2_js',
			set_url_scheme( 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js' ),
			[ 'jquery' ],
			'4.0.13',
			true
		);

		// Enqueue Select2 script.
		wp_enqueue_script( 'cau_site_domain_select2_js' );

		// Enqueue our Javascript plus dependencies.
		wp_enqueue_script(
			'cau_site_domain_js',
			plugins_url( 'assets/js/civicrm-admin-utilities-site-multidomain.js', CIVICRM_ADMIN_UTILITIES_FILE ),
			[ 'jquery', 'cau_site_domain_select2_js' ],
			CIVICRM_ADMIN_UTILITIES_VERSION, // Version.
			true
		);

		$domain = $this->domain_get_info();

		// Localisation array.
		$vars = [
			'localisation' => [
				'placeholder_group' => esc_html__( 'Select a Domain Group', 'civicrm-admin-utilities' ),
				'placeholder_org'   => esc_html__( 'Select a Domain Organisation', 'civicrm-admin-utilities' ),
			],
			'settings'     => [
				'ajaxurl'       => admin_url( 'admin-ajax.php' ),
				'blog_id'       => get_current_blog_id(),
				'domain_id'     => $domain['id'],
				'domain_org_id' => $domain['contact_id'],
			],
		];

		// Localise with WordPress function.
		wp_localize_script(
			'cau_site_domain_js',
			'CAU_Site_Domain',
			$vars
		);

	}

	/**
	 * Adds help copy to admin page.
	 *
	 * @since 1.0.9
	 *
	 * @param object $screen The existing WordPress screen object.
	 * @return object $screen The amended WordPress screen object.
	 */
	public function admin_help( $screen ) {

		// Init page IDs.
		$pages = [
			$this->page_handle,
		];

		// Kick out if not our screen.
		if ( ! in_array( $screen->id, $pages, true ) ) {
			return $screen;
		}

		// Build tab args.
		$args = [
			'id'      => 'cau_multidomain',
			'title'   => __( 'CiviCRM Admin Utilities Domain', 'civicrm-admin-utilities' ),
			'content' => $this->admin_help_get(),
		];

		// Add a tab - we can add more later.
		$screen->add_help_tab( $args );

		// --<
		return $screen;

	}

	/**
	 * Get help text.
	 *
	 * @since 1.0.9
	 *
	 * @return string $help The help text formatted as HTML.
	 */
	public function admin_help_get() {

		// Build path to help template.
		$template = CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/wordpress/settings/help/page-multidomain-site-help.php';

		// Use contents of help template.
		ob_start();
		require_once $template;
		$help = ob_get_clean();

		// --<
		return $help;

	}

	// -------------------------------------------------------------------------

	/**
	 * Show our Multidomain Settings page.
	 *
	 * @since 1.0.9
	 */
	public function page_render() {

		/**
		 * Set capability but allow overrides.
		 *
		 * @since 1.0.9
		 *
		 * @param str The default capability for access to domain page.
		 */
		$capability = apply_filters( 'civicrm_admin_utilities_page_domain_cap', 'manage_options' );

		// Check user permissions.
		if ( ! current_user_can( $capability ) ) {
			return;
		}

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->civicrm->is_initialised() ) {
			return;
		}

		// Get admin page URLs.
		$urls = $this->plugin->single->page_get_urls();

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
		do_action( 'cau/multidomain/settings/add_meta_boxes', $screen->id, null );

		// Grab columns.
		$columns = ( 1 === $screen->get_columns() ? '1' : '2' );

		// Include template file.
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/site-multidomain.php';

	}

	/**
	 * Append the Multidomain Settings page to Single Site subpages.
	 *
	 * This ensures that the correct parent menu item is highlighted for our
	 * Multidomain subpage in Single Site installs.
	 *
	 * @since 1.0.9
	 *
	 * @param array $subpages The existing list of subpages.
	 * @return array $subpages The modified list of subpages.
	 */
	public function page_subpages_filter( $subpages ) {

		// Add Multidomain Settings page.
		$subpages[] = 'cau_multidomain';

		// --<
		return $subpages;

	}

	/**
	 * Append the Multidomain Settings page URL to Single Site subpage URLs.
	 *
	 * @since 1.0.9
	 *
	 * @param array $urls The existing list of URLs.
	 * @return array $urls The modified list of URLs.
	 */
	public function page_urls_filter( $urls ) {

		// Add Multidomain Settings page.
		$urls['multidomain'] = menu_page_url( 'cau_multidomain', false );

		// --<
		return $urls;

	}

	/**
	 * Show subpage tabs on settings pages.
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
	 * Add subpage tab to tabs on settings pages.
	 *
	 * @since 1.0.9
	 *
	 * @param array $urls The array of subpage URLs.
	 * @param str   $active_tab The key of the active tab in the subpage URLs array.
	 */
	public function page_add_tab( $urls, $active_tab ) {

		// Define title.
		$title = __( 'Domain', 'civicrm-admin-utilities' );

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
	 * Register meta boxes.
	 *
	 * @since 1.0.9
	 *
	 * @param str $screen_id The Admin Page Screen ID.
	 */
	public function meta_boxes_add( $screen_id ) {

		// Define valid Screen IDs.
		$screen_ids = [
			'admin_page_cau_multidomain',
		];

		// Bail if not the Screen ID we want.
		if ( ! in_array( $screen_id, $screen_ids, true ) ) {
			return;
		}

		// Bail if user does not have permission.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Kick out if no CiviCRM.
		if ( ! $this->plugin->civicrm->is_initialised() ) {
			return;
		}

		// Init data to pass to meta boxes.
		$data = [];

		/*
		 * Check if "Multisite" is enabled for this Domain.
		 *
		 * * Before CiviCRM 6.4.0, the setting was called "is_enabled".
		 * * Since CiviCRM 6.4.0, the setting is called "multisite_is_enabled".
		 */
		$data['enabled'] = \Civi::settings()->get( 'multisite_is_enabled' ) || \Civi::settings()->get( 'is_enabled' );

		// Get domain name.
		$data['domain'] = $this->domain_get_info();

		// Get domain group name.
		$data['domain_group'] = $this->domain_group_get_info();

		// Get domain org data.
		$data['domain_org'] = $this->domain_org_get_info();

		// Get the mapped WordPress Site ID.
		$data['domain_mapped'] = 0;
		if ( ! empty( $data['domain']['id'] ) ) {
			$data['site_id']       = $this->multidomain->mapping_site_get( (int) $data['domain']['id'] );
			$data['domain_mapped'] = ! empty( $data['site_id'] ) ? true : false;
		}

		// Check if "CiviCRM Multisite" extension is active.
		$data['multisite'] = false;
		if ( ! empty( $this->plugin->civicrm->extension_is_enabled( 'org.civicrm.multisite' ) ) ) {
			$data['multisite'] = true;
		}

		// Check if required CiviCRM action exists.
		$data['action_exists'] = false;
		if ( $this->multidomain->action_exists ) {
			$data['action_exists'] = true;
		}

		// Get the "Organization Address and Contact Info" page URL.
		$data['domain_org_url'] = $this->plugin->single->get_link( 'civicrm/admin/domain', 'action=update&reset=1' );

		// Get the Domain Group settings URL.
		if ( ! empty( $data['domain_group']['id'] ) ) {
			$data['domain_group_url'] = $this->plugin->single->get_link( 'civicrm/group/edit', 'reset=1&action=update&id=' . $data['domain_group']['id'] );
		}

		// Get the "Multi Site Settings" page URL.
		$data['domain_url'] = $this->plugin->single->get_link( 'civicrm/admin/setting/preferences/multisite', 'reset=1' );

		// Get the "Settings - Resource URLs" page URL.
		$data['resource_url'] = $this->plugin->single->get_link( 'civicrm/admin/setting/url', 'reset=1' );

		/*
		 * Get the Paths and URLs for this Domain.
		 *
		 * These paths and URLs are normally defined in the "civicrm.setting.php" file.
		 * With multiple CiviCRM Domains, they need to be loaded dynamically for each
		 * Domain to work correctly.
		 *
		 * @see CAU_Admin_Multidomain::civicrm_after_settings_file_load()
		 */
		$domain_id     = ! empty( $data['domain']['id'] ) ? (int) $data['domain']['id'] : 0;
		$data['paths'] = $this->plugin->multidomain->paths_get( $domain_id );

		// Create "Domain Info" metabox.
		add_meta_box(
			'civicrm_au_domain_info',
			__( 'CiviCRM Domain Information', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_info_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core', // Vertical placement: options are 'core', 'high', 'low'.
			$data
		);

		// Bail if "multisite" is not present and enabled in CiviCRM.
		if ( false === $data['multisite'] || false === $data['enabled'] ) {
			return;
		}

		// Create "Edit Domain" metabox.
		add_meta_box(
			'civicrm_au_domain_edit',
			__( 'Edit Domain', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_edit_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core', // Vertical placement: options are 'core', 'high', 'low'.
			$data
		);

		// Check if required CiviCRM action exists.
		if ( $this->multidomain->action_exists ) {

			// Create "Domain Paths and URLs" metabox.
			add_meta_box(
				'civicrm_au_domain_paths',
				__( 'Domain Paths and URLs', 'civicrm-admin-utilities' ),
				[ $this, 'meta_box_paths_render' ], // Callback.
				$screen_id, // Screen ID.
				'normal', // Column: options are 'normal' and 'side'.
				'core', // Vertical placement: options are 'core', 'high', 'low'.
				$data
			);

		}

		// Create Submit metabox.
		add_meta_box(
			'submitdiv',
			__( 'Settings', 'civicrm-admin-utilities' ),
			[ $this, 'meta_box_submit_render' ], // Callback.
			$screen_id, // Screen ID.
			'side', // Column: options are 'normal' and 'side'.
			'core', // Vertical placement: options are 'core', 'high', 'low'.
			$data
		);

	}

	/**
	 * Render a "Domain Info" meta box on Admin screen.
	 *
	 * @since 1.0.9
	 *
	 * @param mixed $unused Unused param.
	 * @param array $metabox Array containing id, title, callback, and args elements.
	 */
	public function meta_box_info_render( $unused = null, $metabox = [] ) {
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/site-metabox-domain-info.php';
	}

	/**
	 * Render "Edit Domain" meta box on Admin screen.
	 *
	 * @since 1.0.9
	 *
	 * @param mixed $unused Unused param.
	 * @param array $metabox Array containing id, title, callback, and args elements.
	 */
	public function meta_box_edit_render( $unused = null, $metabox = [] ) {
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/site-metabox-domain-edit.php';
	}

	/**
	 * Render "Domain Paths and URLs" meta box on Admin screen.
	 *
	 * @since 1.0.9
	 *
	 * @param mixed $unused Unused param.
	 * @param array $metabox Array containing id, title, callback, and args elements.
	 */
	public function meta_box_paths_render( $unused = null, $metabox = [] ) {
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/site-metabox-domain-paths.php';
	}

	/**
	 * Render a Submit meta box on Admin screen.
	 *
	 * @since 1.0.9
	 *
	 * @param mixed $unused Unused param.
	 * @param array $metabox Array containing id, title, callback, and args elements.
	 */
	public function meta_box_submit_render( $unused = null, $metabox = [] ) {
		include CIVICRM_ADMIN_UTILITIES_PATH . 'assets/templates/metaboxes/site-metabox-domain-submit.php';
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
		$target_url = menu_page_url( 'cau_multidomain', false );

		// --<
		return $target_url;

	}

	/**
	 * Route settings updates to relevant methods.
	 *
	 * @since 1.0.9
	 */
	public function form_submitted() {

		// Was the "Domain" form submitted?
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['cau_multidomain_submit'] ) ) {
			$this->form_save();
			$url = menu_page_url( 'cau_multidomain', false );
			$this->form_redirect( $url );
		}

	}

	/**
	 * Applies updates from the data supplied by our Multidomain Settings page.
	 *
	 * @since 1.0.9
	 */
	public function form_save() {

		// Check that we trust the source of the data.
		check_admin_referer( 'cau_multidomain_action', 'cau_multidomain_nonce' );

		// Sanitise "Edit Domain" inputs.
		$domain_mapped   = isset( $_POST['cau_domain_mapped'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['cau_domain_mapped'] ) ) : 0;
		$domain_org_id   = isset( $_POST['cau_domain_org_select'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['cau_domain_org_select'] ) ) : 0;
		$domain_group_id = isset( $_POST['cau_domain_group_select'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['cau_domain_group_select'] ) ) : 0;
		$name_overwrite  = isset( $_POST['cau_domain_name_select'] ) ? sanitize_text_field( wp_unslash( $_POST['cau_domain_name_select'] ) ) : 'keep';

		// We need the existing Domain and Domain Group before we make changes.
		$existing_domain       = $this->plugin->civicrm->domain->get_by_id();
		$existing_domain_group = $this->plugin->civicrm->domain->group_get_by_id();

		// Get the existing IDs.
		$existing_domain_id       = ! empty( $existing_domain['id'] ) ? (int) $existing_domain['id'] : 0;
		$existing_domain_org_id   = ! empty( $existing_domain['contact_id'] ) ? (int) $existing_domain['contact_id'] : 0;
		$existing_domain_group_id = ! empty( $existing_domain_group['id'] ) ? (int) $existing_domain_group['id'] : 0;

		// We can't have an empty Domain Organisation ID.
		if ( empty( $domain_org_id ) ) {
			$domain_org_id = $existing_domain_org_id;
		}

		// Check if the Domain Organisation is changing.
		$domain_org_changing = false;
		if ( $existing_domain_org_id !== $domain_org_id ) {
			$domain_org_changing = true;
		}

		// Check if the Domain Group is changing.
		$domain_group_changing = false;
		if ( $existing_domain_group_id !== $domain_group_id ) {
			$domain_group_changing = true;
		}

		// Build args array from retrieved data.
		$args = [
			'domain_mapped'            => $domain_mapped,
			'domain_org_id'            => $domain_org_id,
			'domain_group_id'          => $domain_group_id,
			'name_overwrite'           => $name_overwrite,
			'existing_domain'          => $existing_domain,
			'existing_domain_group'    => $existing_domain_group,
			'existing_domain_id'       => $existing_domain_id,
			'existing_domain_org_id'   => $existing_domain_org_id,
			'existing_domain_group_id' => $existing_domain_group_id,
			'domain_org_changing'      => $domain_org_changing,
			'domain_group_changing'    => $domain_group_changing,
		];

		/**
		 * Fires just before a Domain data is updated.
		 *
		 * @since 1.0.9
		 *
		 * @param array $args The array of data from this process.
		 */
		do_action( 'cau/multidomain/settings/form_save/pre', $args );

		// Update the Domain Organisation if it is changing.
		if ( $domain_org_changing && ! empty( $existing_domain_id ) ) {
			$overwrite = 'overwrite' === $name_overwrite ? true : false;
			$domain    = $this->plugin->civicrm->domain->org_update( $existing_domain_id, $domain_org_id, $overwrite );
			if ( $this->multidomain->action_exists ) {
				$this->multidomain->reference_data_update( $existing_domain_id, 'org_id', $domain_org_id );
			}
		}

		// Update the Domain Group if it is changing.
		if ( $domain_group_changing ) {
			// Clearing the Domain Group setting requires passing an empty string.
			$domain_group_id_setting = ! empty( $domain_group_id ) ? $domain_group_id : '';
			$this->plugin->civicrm->setting_set( 'domain_group_id', $domain_group_id_setting );
			// All Contacts in the previous Domain Group must be reassigned to the new Domain Group.
			$this->plugin->civicrm->domain->group_contacts_update( $existing_domain_group_id, $domain_group_id );
			if ( $this->multidomain->action_exists ) {
				$this->multidomain->reference_data_update( $existing_domain_id, 'group_id', $domain_group_id );
			}
		}

		// Create, update or delete the "GroupOrganization" if we didn't get an error.
		$this->group_orgs_update( $args );

		// Add or remove mapping between CiviCRM Domain and WordPress Site.
		if ( $this->multidomain->action_exists ) {
			if ( empty( $domain_mapped ) && ! empty( $existing_domain_id ) ) {
				$this->multidomain->mapping_site_remove( $existing_domain_id, get_current_blog_id() );
			} else {
				$this->multidomain->mapping_site_assign( $existing_domain_id, get_current_blog_id() );
			}
		}

		// Update reference data.
		if ( $this->multidomain->action_exists ) {
			if ( empty( $domain_mapped ) && ! empty( $existing_domain_id ) ) {
				$this->multidomain->reference_data_remove( $existing_domain_id, [ 'site_id' ] );
			} else {
				$this->multidomain->reference_data_update( $existing_domain_id, 'site_id', get_current_blog_id() );
			}
		}

		// Check if required CiviCRM action exists.
		if ( $this->multidomain->action_exists ) {

			// Sanitise "Domain Paths" inputs.
			$core_url       = isset( $_POST['cau_civicrm_core_url'] ) ? sanitize_text_field( wp_unslash( $_POST['cau_civicrm_core_url'] ) ) : '';
			$extensions_dir = isset( $_POST['cau_civicrm_extensions_dir'] ) ? sanitize_text_field( wp_unslash( $_POST['cau_civicrm_extensions_dir'] ) ) : '';
			$extensions_url = isset( $_POST['cau_civicrm_extensions_url'] ) ? sanitize_text_field( wp_unslash( $_POST['cau_civicrm_extensions_url'] ) ) : '';

			// Enforce trailing slashes - or not.
			$core_url       = untrailingslashit( $core_url );
			$extensions_dir = ! empty( $extensions_dir ) ? trailingslashit( $extensions_dir ) : '';
			$extensions_url = ! empty( $extensions_url ) ? trailingslashit( $extensions_url ) : '';

			// Wrap them in an array.
			$paths = [
				'core_url'        => $core_url,
				'extensions_url'  => $extensions_url,
				'extensions_path' => $extensions_dir,
			];

			// Assign them to the current Domain ID.
			if ( ! empty( $existing_domain_id ) ) {
				$this->plugin->multidomain->paths_set( $existing_domain_id, $paths );
			}

		}

		// Save the settings.
		$this->plugin->multisite->settings_save();

		/**
		 * Fires when the Single Site Multidomain update process is finished.
		 *
		 * @since 1.0.9
		 *
		 * @param array $args The array of data from this process.
		 */
		do_action( 'cau/multidomain/settings/form_saved', $args );

	}

	// -------------------------------------------------------------------------

	/**
	 * Updates the "GroupOrganization" data for the form save process.
	 *
	 * @since 1.0.9
	 *
	 * @param array $args The array of data from the form save process.
	 */
	private function group_orgs_update( $args ) {

		// Get the existing "GroupOrganization" entry.
		$existing_group_org = [];
		if ( ! empty( $args['existing_domain_group_id'] ) && ! empty( $args['existing_domain_org_id'] ) ) {
			$existing_group_org = $this->plugin->civicrm->domain->group_org_get( $args['existing_domain_group_id'], $args['existing_domain_org_id'] );
		}

		// Bail on error.
		if ( false === $existing_group_org ) {
			return;
		}

		// If there's a new Domain Group ID.
		if ( ! empty( $args['domain_group_id'] ) ) {

			// Create or update if there's an existing "GroupOrganization" entry.
			if ( empty( $existing_group_org ) ) {

				// Create a "GroupOrganization" entry for the Domain Group.
				$group_org = $this->plugin->civicrm->domain->group_org_create( $args['domain_group_id'], $args['domain_org_id'] );

				// Only handle Orphaned Groups when the action exists.
				if ( $this->multidomain->action_exists ) {

					// Get all Groups that may have shared an old Domain Organisation.
					$group_ids = $this->multidomain->groups_orphaned_get( $args['existing_domain_id'] );
					if ( ! empty( $group_ids ) ) {

						// Create their "GroupOrganization" entries.
						$errors = [];
						foreach ( $group_ids as $group_id ) {
							$group_org = $this->plugin->civicrm->domain->group_org_create( $group_id, $args['domain_org_id'] );
							if ( empty( $group_org ) ) {
								$errors[] = $group_id;
							}
						}

						// Delete the saved Orphaned Groups data.
						$this->multidomain->groups_orphaned_remove( $args['existing_domain_id'] );
						if ( ! empty( $errors ) ) {
							// We could try again later, or show something in the UI.
							$this->multidomain->groups_orphaned_set( $args['existing_domain_id'], $errors );
						}

					}

				}

			} else {

				// Update the "GroupOrganization" entry for the Domain Group.
				$group_org = $this->plugin->civicrm->domain->group_org_update( (int) $existing_group_org['id'], $args['domain_group_id'], $args['domain_org_id'] );

				// Handle any Groups that share the old Domain Organisation.
				$group_orgs = $this->plugin->civicrm->domain->group_orgs_get( null, $args['existing_domain_org_id'] );
				if ( ! empty( $group_orgs ) ) {
					// Update them so that they share the new Domain Organisation.
					$group_org_ids = wp_list_pluck( $group_orgs, 'id' );
					$group_orgs    = $this->plugin->civicrm->domain->group_orgs_contact_update( $group_org_ids, $args['domain_org_id'] );
				}

			}

		} elseif ( ! empty( $existing_group_org['id'] ) ) {

			// Delete the "GroupOrganization" entry for the Domain Group.
			$group_org = $this->plugin->civicrm->domain->group_org_delete( (int) $existing_group_org['id'] );

			// Handle any Groups that share the current Domain Organisation.
			$group_orgs = $this->plugin->civicrm->domain->group_orgs_get( null, $args['domain_org_id'] );
			if ( ! empty( $group_orgs ) ) {

				// Only save Orphaned Groups when the action exists.
				if ( $this->multidomain->action_exists ) {
					$group_ids = wp_list_pluck( $group_orgs, 'group_id' );
					$this->multidomain->groups_orphaned_set( $args['existing_domain_id'], $group_ids );
				}

				// Delete their "GroupOrganization" entries.
				$group_org_ids = wp_list_pluck( $group_orgs, 'id' );
				$group_orgs    = $this->plugin->civicrm->domain->group_orgs_delete( $group_org_ids );

			}

		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the Domain data for the current Domain.
	 *
	 * @since 1.0.9
	 *
	 * @return array $domain The domain data, with error message on failure.
	 */
	private function domain_get_info() {

		// Init return.
		$domain = [
			'id'         => 0,
			'contact_id' => 0,
		];

		// Get the current CiviCRM Domain ID.
		$domain_id = $this->plugin->civicrm->domain->id_get_current();

		// Bail on error.
		if ( false === $domain_id ) {
			$domain['name'] = __( 'There was an error fetching the Domain ID', 'civicrm-admin-utilities' );
			return $domain;
		}

		// Return if nothing found.
		if ( 0 === $domain_id ) {
			$domain['name'] = __( 'Could not find the Domain ID', 'civicrm-admin-utilities' );
			return $domain;
		}

		// Get the Domain data.
		$domain_info = $this->plugin->civicrm->domain->get_by_id( $domain_id );

		// Bail on error.
		if ( false === $domain_info ) {
			$domain['name'] = __( 'There was an error fetching the Domain', 'civicrm-admin-utilities' );
			return $domain;
		}

		// Return if nothing found.
		if ( empty( $domain_info ) ) {
			$domain['name'] = __( 'Could not find the Domain', 'civicrm-admin-utilities' );
			return $domain;
		}

		// Populate return array with the items we want.
		$domain['id']         = (int) $domain_id;
		$domain['name']       = $domain_info['name'];
		$domain['contact_id'] = (int) $domain_info['contact_id'];
		$domain['version']    = $domain_info['version'];

		// --<
		return $domain;

	}

	/**
	 * Gets the Domain Group data for the current Domain.
	 *
	 * @since 1.0.9
	 *
	 * @return array $domain_group The domain group data, with error message on failure.
	 */
	private function domain_group_get_info() {

		// Init return.
		$domain_group = [ 'id' => 0 ];

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->civicrm->is_initialised() ) {
			$domain_org['name'] = __( 'Failed to initialise CiviCRM', 'civicrm-admin-utilities' );
			return $domain_org;
		}

		// Try and find the current Domain Group ID.
		$domain_group_id = $this->plugin->civicrm->domain->group_id_get_current();

		// Bail if we don't find one.
		if ( 0 === $domain_group_id ) {
			$domain_group['name'] = __( 'Could not find a Domain Group ID', 'civicrm-admin-utilities' );
			return $domain_group;
		}

		// Get Domain Group info.
		$domain_group_info = $this->plugin->civicrm->domain->group_get_by_id( $domain_group_id );

		// Bail if there's an error.
		if ( false === $domain_group_info ) {
			$domain_group['name'] = __( 'There was an error fetching the Domain Group', 'civicrm-admin-utilities' );
			return $domain_group;
		}

		// Bail if we don't find one.
		if ( empty( $domain_group_info ) ) {
			$domain_org['name'] = __( 'Could not find the Domain Group', 'civicrm-admin-utilities' );
			return $domain_group;
		}

		// Populate return array with the items we want.
		$domain_group['id']   = (int) $domain_group_id;
		$domain_group['name'] = $domain_group_info['title'];

		// --<
		return $domain_group;

	}

	/**
	 * Gets the Domain Organisation data for the current Domain.
	 *
	 * @since 1.0.9
	 *
	 * @return str $domain_org The Domain Organisation data, with error message on failure.
	 */
	private function domain_org_get_info() {

		// Init return.
		$domain_org = [ 'id' => 0 ];

		// Get the current CiviCRM Domain Organisation ID.
		$domain_org_id = $this->plugin->civicrm->domain->org_id_get_current();

		// Bail on error.
		if ( false === $domain_org_id ) {
			$domain_org['name'] = __( 'There was an error fetching the Domain Organisation ID', 'civicrm-admin-utilities' );
			return $domain_org;
		}

		// Return if nothing found.
		if ( 0 === $domain_org_id ) {
			$domain_org['name'] = __( 'Could not find the Domain Organisation ID', 'civicrm-admin-utilities' );
			return $domain_org;
		}

		// Get the Domain Organisation data.
		$domain_org_info = $this->plugin->civicrm->domain->org_get_by_id( $domain_org_id );

		// Bail on error.
		if ( false === $domain_org_info ) {
			$domain['name'] = __( 'There was an error fetching the Domain Organisation', 'civicrm-admin-utilities' );
			return $domain;
		}

		// Return if nothing found.
		if ( empty( $domain_org_info ) ) {
			$domain['name'] = __( 'Could not find the Domain Organisation', 'civicrm-admin-utilities' );
			return $domain;
		}

		// Populate return array with the items we want.
		$domain_org['id']   = $domain_org_id;
		$domain_org['name'] = $domain_org_info['display_name'];

		// --<
		return $domain_org;

	}

}
