<?php
/**
 * Settings Page abstract class.
 *
 * Handles common Settings Page functionality.
 *
 * @package CiviCRM_Admin_Utilities
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Settings Page abstract class.
 *
 * A class that encapsulates common Settings Page functionality. The Page can be
 * styled as a "Settings" or "Dashboard" page depending on requirements. It can
 * be either a "Parent" Page which sits directly below the "CiviCRM" menu item or
 * a "Sub-page" which is accessible via a tab on the Parent Page.
 *
 * You can implement this functionality by extending this class in your plugin
 * and configuring it to suit your needs or you can copy this class to your own
 * plugin, give it a unique class name, modify the template paths, and then extend
 * it. Up to you :)
 *
 * If you copy this class to your plugin, you will also need to copy across the
 * template files in "assets/templates/wordpress/settings" and modify the package,
 * translation slug and "@since" tags for your plugin.
 *
 * @since 1.0.2
 */
abstract class CAU_Settings_Page_Base {

	/**
	 * Hook prefix common to all Settings Pages.
	 *
	 * @since 1.0.2
	 * @access public
	 * @var string
	 */
	public $hook_prefix_common = '';

	/**
	 * Hook prefix.
	 *
	 * @since 1.0.2
	 * @access public
	 * @var string
	 */
	public $hook_prefix = '';

	/**
	 * Hook priority.
	 *
	 * @since 1.0.2
	 * @access public
	 * @var string
	 */
	public $hook_priority = 15;

	/**
	 * Plugin name.
	 *
	 * @since 1.0.2
	 * @access protected
	 * @var string
	 */
	protected $plugin_name = '';

	/**
	 * Parent Page object.
	 *
	 * @since 0.2.1
	 * @access public
	 * @var object
	 */
	public $page_parent;

	/**
	 * Settings Page context.
	 *
	 * Either "civicrm_page_" or "admin_page_". Default "civicrm_page_".
	 *
	 * @since 1.0.2
	 * @access protected
	 * @var string
	 */
	protected $page_context = 'civicrm_page_';

	/**
	 * Settings Page layout.
	 *
	 * Either "settings" or "dashboard". Default "settings".
	 *
	 * @since 1.0.2
	 * @access protected
	 * @var string
	 */
	protected $page_layout = 'settings';

	/**
	 * Settings Page handle.
	 *
	 * @since 1.0.2
	 * @access public
	 * @var string
	 */
	public $page_handle = '';

	/**
	 * Settings Page slug.
	 *
	 * @since 1.0.2
	 * @access public
	 * @var string
	 */
	public $page_slug = '';

	/**
	 * Settings Page title.
	 *
	 * @since 1.0.2
	 * @access protected
	 * @var string
	 */
	protected $page_title = '';

	/**
	 * Settings Page menu label.
	 *
	 * @since 1.0.2
	 * @access protected
	 * @var string
	 */
	protected $page_menu_label = '';

	/**
	 * Settings Page help label.
	 *
	 * @since 1.0.2
	 * @access protected
	 * @var string
	 */
	protected $page_help_label = '';

	/**
	 * Settings Page tab label.
	 *
	 * @since 1.0.2
	 * @access protected
	 * @var string
	 */
	protected $page_tab_label = '';

	/**
	 * Settings Page "Submit" Metabox label.
	 *
	 * @since 1.0.2
	 * @access protected
	 * @var string
	 */
	protected $metabox_submit_title = '';

	/**
	 * Absolute path to the plugin directory.
	 *
	 * @since 1.0.2
	 * @access protected
	 * @var string
	 */
	protected $path_plugin = '';

	/**
	 * Relative path to the template directory.
	 *
	 * @since 1.0.2
	 * @access protected
	 * @var string
	 */
	protected $path_template = 'assets/templates/wordpress/settings/';

	/**
	 * Relative path to the Page template directory.
	 *
	 * @since 1.0.2
	 * @access protected
	 * @var string
	 */
	protected $path_page = 'pages/';

	/**
	 * Relative path to the Metabox template directory.
	 *
	 * @since 1.0.2
	 * @access protected
	 * @var string
	 */
	protected $path_metabox = 'metaboxes/';

	/**
	 * Relative path to the Help template directory.
	 *
	 * @since 1.0.2
	 * @access protected
	 * @var string
	 */
	protected $path_help = 'help/';

	/**
	 * The name of the form nonce element.
	 *
	 * @since 1.0.2
	 * @access protected
	 * @var string
	 */
	protected $form_nonce_field = 'settings_nonce';

	/**
	 * The name of the form nonce value.
	 *
	 * @since 1.0.2
	 * @access protected
	 * @var string
	 */
	protected $form_nonce_action = 'settings_action';

	/**
	 * The "name" and "id" of the form.
	 *
	 * @since 1.0.2
	 * @access protected
	 * @var string
	 */
	protected $form_id = 'settings_form';

	/**
	 * The "name" and "id" of the form's submit input element.
	 *
	 * @since 1.0.2
	 * @access protected
	 * @var string
	 */
	protected $form_submit_id = 'settings_submit';

	/**
	 * Class constructor.
	 *
	 * @since 1.0.2
	 */
	public function __construct() {

		// Configure page type.
		if ( ! empty( $this->page_parent ) ) {
			$this->hook_prefix_common = $this->page_parent->hook_prefix_common;
			$this->page_context       = 'admin_page_';
		}

		// Build form attributes.
		$this->form_nonce_field  = $this->hook_prefix . '_' . $this->form_nonce_field;
		$this->form_nonce_action = $this->hook_prefix . '_' . $this->form_nonce_action;
		$this->form_id           = $this->hook_prefix . '_' . $this->form_id;
		$this->form_submit_id    = $this->hook_prefix . '_' . $this->form_submit_id;

		// Add init actions.
		add_action( 'init', [ $this, 'initialise' ] );
		add_action( 'init', [ $this, 'register_hooks_common' ] );
		add_action( 'init', [ $this, 'register_hooks' ] );

	}

	/**
	 * Initialise this object.
	 *
	 * @since 1.0.2
	 */
	public function initialise() {}

	/**
	 * Registers common hooks.
	 *
	 * @since 1.0.2
	 */
	public function register_hooks_common() {

		// Add Page hooks.
		if ( empty( $this->page_parent ) ) {

			// Add Parent Page to CiviCRM menu.
			add_action( 'admin_menu', [ $this, 'admin_menu' ], $this->hook_priority );

		} else {

			// Add Sub-page to Settings Page.
			add_action( $this->page_parent->hook_prefix . '/settings/page/admin_menu', [ $this, 'admin_menu' ], $this->hook_priority, 2 );

			// Make sure Tabs are shown.
			add_filter( $this->page_parent->hook_prefix . '/settings/page/show_tabs', '__return_true' );
			add_filter( $this->hook_prefix . '/settings/page/show_tabs', '__return_true' );

		}

		// Render the Page Tab.
		add_action( $this->hook_prefix_common . '/settings/page/tabs', [ $this, 'page_tab_render' ] );

		// Add meta boxes to this Sub-page.
		add_action( $this->hook_prefix . '/settings/page/add_meta_boxes', [ $this, 'meta_boxes_add' ], 11 );
		add_action( $this->hook_prefix . '/settings/page/meta_boxes_added', [ $this, 'meta_boxes_register' ], 10, 2 );

		// Listen for form redirection requests.
		add_action( $this->hook_prefix . '/settings/form/redirect', [ $this, 'form_redirect' ] );

	}

	/**
	 * Registers Settings Page hooks.
	 *
	 * @since 1.0.2
	 */
	public function register_hooks() {

	}

	// -------------------------------------------------------------------------

	/**
	 * Adds the Menu Item.
	 *
	 * @since 1.0.2
	 *
	 * @param string $page_handle The handle of the Parent Page.
	 * @param string $page_slug The slug of the Parent Page.
	 */
	public function admin_menu( $page_handle = '', $page_slug = '' ) {

		// Must be network admin in Multisite.
		if ( is_multisite() && ! is_super_admin() ) {
			return;
		}

		// Check User permissions.
		$capability = $this->page_capability();
		if ( false === $capability ) {
			return;
		}

		// Attach to CiviCRM menu item unless Sub-page.
		if ( empty( $page_slug ) ) {
			$page_slug = 'CiviCRM';
		}

		// Add the Menu Item.
		$this->page_handle = add_submenu_page(
			$page_slug, // Parent slug.
			$this->page_title, // Page title.
			$this->page_menu_label, // Menu label.
			$capability, // Required caps.
			$this->page_slug, // Slug name.
			[ $this, 'page_render' ] // Callback.
		);

		// Register our form submit hander.
		add_action( 'load-' . $this->page_handle, [ $this, 'form_submitted' ] );

		/*
		 * Add styles and scripts only on our Settings Page.
		 * @see wp-admin/admin-header.php
		 */
		add_action( 'admin_head-' . $this->page_handle, [ $this, 'admin_head' ] );
		add_action( 'admin_print_styles-' . $this->page_handle, [ $this, 'admin_styles' ] );
		add_action( 'admin_print_scripts-' . $this->page_handle, [ $this, 'admin_scripts' ] );

		// Add help text.
		add_action( 'load-' . $this->page_handle, [ $this, 'admin_help' ], 50 );

		// Ensure correct menu item is highlighted.
		add_action( 'admin_head-' . $this->page_handle, [ $this, 'admin_menu_highlight' ], 50 );

		/**
		 * Fires when the Settings Page has been added.
		 *
		 * @since 1.0.2
		 *
		 * @param string $page_handle The handle of the Settings Page.
		 * @param string $page_slug The slug of the Settings Page.
		 */
		do_action( $this->hook_prefix . '/settings/page/admin_menu', $this->page_handle, $this->page_slug );

	}

	/**
	 * Highlights the parent menu item.
	 *
	 * Regardless of the actual screen we are on, we need the parent menu item
	 * to be highlighted so that the appropriate menu is open by default when
	 * the Sub-page is viewed.
	 *
	 * @since 1.0.2
	 *
	 * @global string $plugin_page The current plugin page.
	 * @global string $submenu_file The current submenu.
	 */
	public function admin_menu_highlight() {

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		global $plugin_page, $submenu_file;

		// Get Parent Page slug.
		$page_slug = empty( $this->page_parent ) ? $this->page_slug : $this->page_parent->page_slug;

		// This forces the menu to highlight the parent menu item.
		if ( $plugin_page === $this->page_slug ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$plugin_page = $page_slug;
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$submenu_file = $page_slug;
		}

	}

	/**
	 * Adds metabox scripts.
	 *
	 * @since 1.0.2
	 */
	public function admin_head() {

		// Enqueue WordPress scripts.
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'dashboard' );

	}

	/**
	 * Adds styles.
	 *
	 * @since 1.0.2
	 */
	public function admin_styles() {}

	/**
	 * Adds scripts.
	 *
	 * @since 1.0.2
	 */
	public function admin_scripts() {}

	/**
	 * Adds help copy to admin page.
	 *
	 * @since 1.0.2
	 */
	public function admin_help() {

		// Get screen object.
		$screen = get_current_screen();

		// Kick out if not our screen.
		if ( $screen->id !== $this->page_handle ) {
			return;
		}

		// Build tab args.
		$args = [
			'id'      => $this->hook_prefix,
			'title'   => $this->page_help_label,
			'content' => $this->admin_help_get(),
		];

		// Add a tab - we can add more later.
		$screen->add_help_tab( $args );

	}

	/**
	 * Gets the default help text.
	 *
	 * @since 1.0.2
	 *
	 * @return string $help The help text formatted as HTML.
	 */
	protected function admin_help_get() {

		// Build path to default help template.
		$template = $this->path_plugin . $this->path_template . $this->path_help . 'page-settings-help.php';
		if ( ! file_exists( $template ) ) {
			$template = CIVICRM_ADMIN_UTILITIES_PATH . $this->path_template . $this->path_help . 'page-settings-help.php';
		}

		// Use contents of help template.
		ob_start();
		require_once $template;
		$help = ob_get_clean();

		// --<
		return $help;

	}

	// -------------------------------------------------------------------------

	/**
	 * Checks the access capability for this Page.
	 *
	 * @since 1.0.2
	 *
	 * @return string|bool The capability if the current User has it, false otherwise.
	 */
	public function page_capability() {

		/**
		 * Set access capability but allow overrides.
		 *
		 * @since 1.0.2
		 *
		 * @param string The default capability for access to Settings Page.
		 */
		$capability = apply_filters( $this->hook_prefix . '/settings/page/cap', 'manage_options' );

		// Check User permissions.
		if ( ! current_user_can( $capability ) ) {
			return false;
		}

		// --<
		return $capability;

	}

	// -------------------------------------------------------------------------

	/**
	 * Renders the Settings Page.
	 *
	 * @since 1.0.2
	 */
	public function page_render() {

		// Check User permissions.
		if ( ! $this->page_capability() ) {
			return;
		}

		/**
		 * Do not show tabs by default but allow overrides.
		 *
		 * @since 1.0.2
		 *
		 * @param bool False by default - do not show tabs.
		 */
		$show_tabs = apply_filters( $this->hook_prefix . '/settings/page/show_tabs', false );

		// Get current screen.
		$screen = get_current_screen();

		/**
		 * Allows meta boxes to be added to this screen.
		 *
		 * The Screen ID to use is:
		 *
		 * * $this->page_context . $this->page_slug
		 *
		 * @since 1.0.2
		 *
		 * @param string $screen_id The ID of the current screen.
		 */
		do_action( $this->hook_prefix . '/settings/page/add_meta_boxes', $screen->id );

		// Configure layout.
		switch ( $this->page_layout ) {

			case 'dashboard':
				$template_name = 'page-dashboard.php';

				// Assign the column CSS class.
				$columns     = absint( $screen->get_columns() );
				$columns_css = '';
				if ( $columns ) {
					$columns_css = " columns-$columns";
				}
				break;

			case 'settings':
				$template_name = 'page-settings.php';

				// Assign columns.
				$columns = ( 1 === (int) $screen->get_columns() ? '1' : '2' );
				break;

		}

		// Build path to Page template.
		$template = $this->path_plugin . $this->path_template . $this->path_page . $template_name;
		if ( ! file_exists( $template ) ) {
			$template = CIVICRM_ADMIN_UTILITIES_PATH . $this->path_template . $this->path_page . $template_name;
		}

		// Include template.
		require_once $template;

	}

	/**
	 * Gets the URL of the Settings Page.
	 *
	 * @since 1.0.2
	 *
	 * @return string $url The URL of the Settings Page.
	 */
	public function page_url_get() {

		// Get Settings Page URL.
		$url = menu_page_url( $this->page_slug, false );

		/**
		 * Filter the Settings Page URL.
		 *
		 * @since 1.0.2
		 *
		 * @param string $url The default Settings Page URL.
		 */
		$url = apply_filters( $this->hook_prefix . '/settings/page/url', $url );

		// --<
		return $url;

	}

	/**
	 * Renders the Settings Page Tab.
	 *
	 * @since 1.0.2
	 */
	public function page_tab_render() {

		// Add active class on our screen.
		$classes = [ 'nav-tab' ];
		$screen  = get_current_screen();
		if ( $screen->id === $this->page_handle ) {
			$classes[] = 'nav-tab-active';
		}

		echo sprintf(
			'<a href="%1$s" class="%2$s">%3$s</a>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$this->page_url_get(),
			esc_attr( implode( ' ', $classes ) ),
			esc_html( $this->page_tab_label )
		);

	}

	// -------------------------------------------------------------------------

	/**
	 * Registers common meta boxes.
	 *
	 * @since 1.0.2
	 *
	 * @param string $screen_id The Admin Page Screen ID.
	 */
	public function meta_boxes_add( $screen_id ) {

		// Bail if not the Screen ID we want.
		if ( $screen_id !== $this->page_context . $this->page_slug ) {
			return;
		}

		// Check User permissions.
		if ( ! $this->page_capability() ) {
			return;
		}

		// Get common data.
		$data = $this->meta_boxes_data( $screen_id );

		// Configure page layout.
		if ( 'settings' === $this->page_layout ) {

			// Create "Submit" metabox.
			add_meta_box(
				'submitdiv',
				$this->metabox_submit_title, // Metabox title.
				[ $this, 'meta_box_submit_render' ], // Callback.
				$screen_id, // Screen ID.
				'side', // Column: options are 'normal' and 'side'.
				'core', // Vertical placement: options are 'core', 'high', 'low'.
				$data
			);

		}

		/**
		 * Broadcast that the metaboxes have been added.
		 *
		 * @since 1.0.2
		 *
		 * @param string $screen_id The Screen indentifier.
		 * @param array $data The array of metabox data.
		 */
		do_action( $this->hook_prefix . '/settings/page/meta_boxes_added', $screen_id, $data );

	}

	/**
	 * Registers Settings Page meta boxes.
	 *
	 * @since 1.0.2
	 *
	 * @param string $screen_id The Settings Page Screen ID.
	 * @param array  $data The array of metabox data.
	 */
	abstract public function meta_boxes_register( $screen_id, $data );

	/**
	 * Gets the array of data to be shared with all metaboxes.
	 *
	 * @since 1.0.5
	 *
	 * @param string $screen_id The Screen indentifier.
	 * @return array $data The array of data to be shared with all metaboxes.
	 */
	public function meta_boxes_data( $screen_id ) {

		/**
		 * Filters the array of data to be shared with all metaboxes.
		 *
		 * @since 0.5.0
		 *
		 * @param array $data The empty default array of metabox data.
		 * @param string $screen_id The Screen indentifier.
		 */
		$data = apply_filters( $this->hook_prefix . '/settings/page/meta_boxes_data', [], $screen_id );

		return $data;

	}

	/**
	 * Loads a metabox as closed by default.
	 *
	 * @since 1.0.2
	 *
	 * @param string[] $classes An array of postbox classes.
	 */
	public function meta_box_closed( $classes ) {

		// Add closed class.
		if ( is_array( $classes ) ) {
			if ( ! in_array( 'closed', $classes, true ) ) {
				$classes[] = 'closed';
			}
		}

		return $classes;

	}

	/**
	 * Renders a Submit metabox.
	 *
	 * @since 1.0.2
	 *
	 * @param mixed $unused Unused param.
	 * @param array $metabox Array containing id, title, callback, and args elements.
	 */
	public function meta_box_submit_render( $unused, $metabox ) {

		// Build path to "Submit" meta box template.
		$template = $this->path_plugin . $this->path_template . $this->path_metabox . 'metabox-settings-submit.php';
		if ( ! file_exists( $template ) ) {
			$template = CIVICRM_ADMIN_UTILITIES_PATH . $this->path_template . $this->path_metabox . 'metabox-settings-submit.php';
		}

		// Include template file.
		require_once $template;

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the URL for the Settings Page form action attribute.
	 *
	 * This happens to be the same as the Settings Page URL, but need not be.
	 *
	 * @since 1.0.2
	 *
	 * @return string $submit_url The URL for the Settings Page form action.
	 */
	public function form_submit_url_get() {

		// Get Settings Page submit URL.
		$submit_url = menu_page_url( $this->page_slug, false );

		/**
		 * Filter the Settings Page submit URL.
		 *
		 * @since 1.0.2
		 *
		 * @param string $submit_url The Settings Page submit URL.
		 */
		$submit_url = apply_filters( $this->hook_prefix . '/settings/form/submit_url', $submit_url );

		// --<
		return $submit_url;

	}

	/**
	 * Performs actions when the form has been submitted.
	 *
	 * @since 1.0.2
	 */
	public function form_submitted() {

		/**
		 * Filters the Form Submit identifier.
		 *
		 * Use this filter when a meta box has its own submit button.
		 *
		 * @since 1.0.2
		 *
		 * @param string $submit_id The Settings Page form submit ID.
		 */
		$submit_id = apply_filters( $this->hook_prefix . '/settings/form/submit_id', $this->form_submit_id );

		// Was the form submitted?
		if ( ! isset( $_POST[ $submit_id ] ) ) {
			return;
		}

		// Check that we trust the source of the data.
		check_admin_referer( $this->form_nonce_action, $this->form_nonce_field );

		/**
		 * Fires before the Settings have been saved.
		 *
		 * * Callbacks do not need to verify the nonce as this has already been done.
		 * * Callbacks should, however, implement their own data validation checks.
		 *
		 * @since 1.0.2
		 *
		 * @param string $submit_id The Settings Page form submit ID.
		 */
		do_action( $this->hook_prefix . '/settings/form/save_before', $submit_id );

		// Save settings.
		$this->form_save( $submit_id );

		/**
		 * Fires when the Settings have been saved.
		 *
		 * * Callbacks do not need to verify the nonce as this has already been done.
		 * * Callbacks should, however, implement their own data validation checks.
		 *
		 * @since 1.0.2
		 *
		 * @param string $submit_id The Settings Page form submit ID.
		 */
		do_action( $this->hook_prefix . '/settings/form/save_after', $submit_id );

		// Now redirect.
		$this->form_redirect( 'updated' );

	}

	/**
	 * Performs save actions when the form has been submitted.
	 *
	 * @since 1.0.2
	 *
	 * @param string $submit_id The Settings Page form submit ID.
	 */
	abstract protected function form_save( $submit_id );

	/**
	 * Redirects to the Settings page with an optional extra param.
	 *
	 * Also responds to redirection requests made by calling:
	 *
	 * do_action( $this->hook_prefix . '/settings/form/redirect' );
	 *
	 * @since 0.2.1
	 *
	 * @param string $mode Pass 'updated' to append the extra param.
	 */
	public function form_redirect( $mode = '' ) {

		// Get the Settings Page URL.
		$url = $this->page_url_get();

		// Maybe append param.
		$args = [];
		if ( 'updated' === $mode ) {
			$args['updated'] = 'true';
		}

		// Redirect to our Settings Page.
		wp_safe_redirect( add_query_arg( $args, $url ) );
		exit;

	}

}
