<?php /*
--------------------------------------------------------------------------------
Plugin Name: CiviCRM Admin Utilities
Plugin URI: https://github.com/christianwach/civicrm-admin-utilities
Description: Optionally modifies CiviCRM's behaviour and appearance in single site and multisite installs.
Author: Christian Wach
Version: 0.4.1
Author URI: http://haystack.co.uk
Text Domain: civicrm-admin-utilities
Domain Path: /languages
Depends: CiviCRM
--------------------------------------------------------------------------------
*/



// set our version here
define( 'CIVICRM_ADMIN_UTILITIES_VERSION', '0.4.1' );

// trigger logging of 'civicrm_pre' and 'civicrm_post'
if ( ! defined( 'CIVICRM_ADMIN_UTILITIES_DEBUG' ) ) {
	define( 'CIVICRM_ADMIN_UTILITIES_DEBUG', false );
}

// store reference to this file
if ( ! defined( 'CIVICRM_ADMIN_UTILITIES_FILE' ) ) {
	define( 'CIVICRM_ADMIN_UTILITIES_FILE', __FILE__ );
}

// store URL to this plugin's directory
if ( ! defined( 'CIVICRM_ADMIN_UTILITIES_URL' ) ) {
	define( 'CIVICRM_ADMIN_UTILITIES_URL', plugin_dir_url( CIVICRM_ADMIN_UTILITIES_FILE ) );
}
// store PATH to this plugin's directory
if ( ! defined( 'CIVICRM_ADMIN_UTILITIES_PATH' ) ) {
	define( 'CIVICRM_ADMIN_UTILITIES_PATH', plugin_dir_path( CIVICRM_ADMIN_UTILITIES_FILE ) );
}



/**
 * CiviCRM Admin Utilities Class.
 *
 * A class that encapsulates plugin functionality.
 *
 * @since 0.1
 */
class CiviCRM_Admin_Utilities {

	/**
	 * Admin object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $admin The admin object.
	 */
	public $admin;



	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// enable translation
		add_action( 'plugins_loaded', array( $this, 'enable_translation' ) );

		// initialise
		add_action( 'plugins_loaded', array( $this, 'initialise' ) );

	}



	/**
	 * Load translation files.
	 *
	 * @since 0.1
	 */
	public function enable_translation() {

		// enable translation
		load_plugin_textdomain(
			'civicrm-admin-utilities', // unique name
			false, // deprecated argument
			dirname( plugin_basename( __FILE__ ) ) . '/languages/' // relative path to files
		);

	}



	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.3.4
	 */
	public function initialise() {

		// init only when CiviCRM is fully installed
		if ( ! defined( 'CIVICRM_INSTALLED' ) ) return;
		if ( ! CIVICRM_INSTALLED ) return;

		// include files
		$this->include_files();

		// set up objects and references
		$this->setup_objects();

		// finally, register hooks
		$this->register_hooks();

	}



	/**
	 * Include files.
	 *
	 * @since 0.3.4
	 */
	public function include_files() {

		// only do this once
		static $done;
		if ( isset( $done ) AND $done === true ) return;

		// load our Admin utility class
		require( CIVICRM_ADMIN_UTILITIES_PATH . 'includes/civicrm-admin-utilities-admin.php' );

		// we're done
		$done = true;

	}



	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.3.4
	 */
	public function setup_objects() {

		// only do this once
		static $done;
		if ( isset( $done ) AND $done === true ) return;

		// initialise objects
		$this->admin = new CiviCRM_Admin_Utilities_Admin();

		// we're done
		$done = true;

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.1
	 */
	public function register_hooks() {

		// bail if CiviCRM plugin is not present
		if ( ! function_exists( 'civi_wp' ) ) return;

		// kill CiviCRM shortcode button
		add_action( 'admin_head', array( $this, 'kill_civi_button' ) );

		// register template directory for menu amends
		add_action( 'civicrm_config', array( $this, 'register_menu_directory' ), 10, 1 );

		// run after the CiviCRM menu hook has been registered
		add_action( 'init', array( $this, 'civicrm_only_on_main_site_please' ) );

		// style tweaks for CiviCRM
		add_action( 'admin_print_styles', array( $this, 'enqueue_admin_scripts' ) );

		// add admin bar item
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_add' ), 2000 );

		// filter the WordPress Permissions Form
		add_action( 'civicrm_config', array( $this, 'register_access_directory' ), 10, 1 );
		add_action( 'civicrm_buildForm', array( $this, 'fix_permissions_form' ), 10, 2 );

		// if the debugging flag is set
		if ( CIVICRM_ADMIN_UTILITIES_DEBUG === true ) {

			// log pre and post database operations
			add_action( 'civicrm_pre', array( $this, 'trace_pre' ), 10, 4 );
			add_action( 'civicrm_post', array( $this, 'trace_post' ), 10, 4 );
			add_action( 'civicrm_postProcess', array( $this, 'trace_postProcess' ), 10, 2 );

		}

		/**
		 * Broadcast that this plugin is now loaded.
		 *
		 * @since 0.3.4
		 */
		do_action( 'civicrm_admin_utilities_loaded' );

	}



	//##########################################################################



	/**
	 * Register directory that CiviCRM searches for the menu template file.
	 *
	 * @since 0.3.2
	 *
	 * @param object $config The CiviCRM config object.
	 */
	public function register_menu_directory( &$config ) {

		// bail if disabled
		if ( $this->admin->setting_get( 'prettify_menu', '0' ) == '0' ) return;

		// kick out if no CiviCRM
		if ( ! $this->admin->is_active() ) return;

		// get template instance
		$template = CRM_Core_Smarty::singleton();

		// get current version
		$version = CRM_Utils_System::version();

		// define our custom path based on CiviCRM version
		if ( version_compare( $version, '5.5', '>=' ) ) {
			$custom_path = CIVICRM_ADMIN_UTILITIES_PATH . 'civicrm_nav_template';
		} else {
			$custom_path = CIVICRM_ADMIN_UTILITIES_PATH . 'civicrm_custom_templates';
		}

		// add our custom template directory
		$template->addTemplateDir( $custom_path );

		// register template directories
		$template_include_path = $custom_path . PATH_SEPARATOR . get_include_path();
		set_include_path( $template_include_path );

	}



	/**
	 * Register directory that CiviCRM searches for the WordPress Access Control template file.
	 *
	 * @since 0.3.2
	 *
	 * @param object $config The CiviCRM config object.
	 */
	public function register_access_directory( &$config ) {

		// bail if disabled
		if ( $this->admin->setting_get( 'prettify_access', '0' ) == '0' ) return;

		// kick out if no CiviCRM
		if ( ! $this->admin->is_active() ) return;

		// bail if CiviCRM has been fixed
		if ( $this->admin->access_form_fixed() ) return;

		// get template instance
		$template = CRM_Core_Smarty::singleton();

		// define our custom path
		$custom_path = CIVICRM_ADMIN_UTILITIES_PATH . 'civicrm_access_templates';

		// add our custom template directory
		$template->addTemplateDir( $custom_path );

		// register template directories
		$template_include_path = $custom_path . PATH_SEPARATOR . get_include_path();
		set_include_path( $template_include_path );

	}



	/**
	 * Admin style tweaks.
	 *
	 * @since 0.1
	 */
	public function enqueue_admin_scripts() {

		// bail if disabled
		if ( $this->admin->setting_get( 'prettify_menu', '0' ) == '0' ) return;

		// set default CSS file
		$css = 'civicrm-admin-utilities.css';

		// use specific CSS file for Shoreditch if active
		if ( $this->shoreditch_css_active() ) {
			$css = 'civicrm-admin-utilities-shoreditch.css';
		}

		// add custom stylesheet
		wp_enqueue_style(
			'civicrm_admin_utilities_admin_tweaks',
			plugins_url( 'assets/css/' . $css, CIVICRM_ADMIN_UTILITIES_FILE ),
			null,
			CIVICRM_ADMIN_UTILITIES_VERSION, // version
			'all' // media
		);

	}



	/**
	 * Determine if the Shoreditch CSS file is being used.
	 *
	 * @since 0.3.4
	 *
	 * @return bool $shoreditch True if Shoreditch CSS file is used, false otherwise.
	 */
	public function shoreditch_css_active() {

		// assume not
		$shoreditch = false;

		// init CiviCRM
		if ( ! civi_wp()->initialize() ) return $shoreditch;

		// get the current Custom CSS URL
		$config = CRM_Core_Config::singleton();

		// has the Shoreditch CSS been activated?
		if ( strstr( $config->customCSSURL, 'org.civicrm.shoreditch' ) !== false ) {

			// Shoreditch CSS is active
			$shoreditch = true;

		}

		// --<
		return $shoreditch;

	}



	/**
	 * Do not load the CiviCRM shortcode button unless we explicitly enable it.
	 *
	 * @since 0.1
	 */
	public function kill_civi_button() {

		// get screen
		$screen = get_current_screen();

		// prevent warning if screen not defined
		if ( empty( $screen ) ) return;

		// bail if there's no post type
		if ( empty( $screen->post_type ) ) return;

		// get chosen post types
		$selected_types = $this->admin->setting_get( 'post_types', array() );

		// remove button if this is not a post type we want to allow the button on
		if ( ! in_array( $screen->post_type, $selected_types ) ) {
			$this->civi_button_remove();
		}

	}



	/**
	 * Prevent the loading of the CiviCRM shortcode button.
	 *
	 * @since 0.1
	 */
	public function civi_button_remove() {

		// get Civi object
		$civi = civi_wp();

		// do we have the modal object?
		if ( isset( $civi->modal ) AND is_object( $civi->modal ) ) {

			// remove current CiviCRM actions
			remove_action( 'media_buttons_context', array( $civi->modal, 'add_form_button' ) );
			remove_action( 'media_buttons', array( $civi->modal, 'add_form_button' ), 100 );
			remove_action( 'admin_enqueue_scripts', array( $civi->modal, 'add_form_button_js' ) );
			remove_action( 'admin_footer', array( $civi->modal, 'add_form_button_html' ) );

			// also remove core resources
			remove_action( 'admin_head', array( $civi, 'wp_head' ), 50 );
			remove_action( 'load-post.php', array( $civi->modal, 'add_core_resources' ) );
			remove_action( 'load-post-new.php', array( $civi->modal, 'add_core_resources' ) );
			remove_action( 'load-page.php', array( $civi->modal, 'add_core_resources' ) );
			remove_action( 'load-page-new.php', array( $civi->modal, 'add_core_resources' ) );

		} else {

			// remove legacy CiviCRM actions
			remove_action( 'media_buttons_context', array( $civi, 'add_form_button' ) );
			remove_action( 'media_buttons', array( $civi, 'add_form_button' ), 100 );
			remove_action( 'admin_enqueue_scripts', array( $civi, 'add_form_button_js' ) );
			remove_action( 'admin_footer', array( $civi, 'add_form_button_html' ) );

		}

	}



	/**
	 * Do not load CiviCRM on sites other than the main site.
	 *
	 * @since 0.1
	 */
	public function civicrm_only_on_main_site_please() {

		// bail if disabled
		if ( $this->admin->setting_get( 'main_site_only', '0' ) == '0' ) return;

		// if not on main site
		if ( is_multisite() AND ! is_main_site() ) {

			// unhook menu item, but allow Civi to load
			remove_action( 'admin_menu', array( civi_wp(), 'add_menu_items' ) );

			// remove CiviCRM shortcode button
			add_action( 'admin_head', array( $this, 'civi_button_remove' ) );

			// remove notice
			remove_action( 'admin_notices', array( civi_wp(), 'show_setup_warning' ) );

		}

	}



	//##########################################################################



	/**
	 * Add a CiviCRM menu to the WordPress admin bar.
	 *
	 * There is some complexity here because some developers enable CiviCRM on
	 * subsites by hacking civicrm.settings.php to return appropriate settings
	 * depending on the domain being requested.
	 *
	 * This is quite valid, but does present a problem for generating this menu
	 * because the default install does not actually work at all on subsites
	 * when network-enabled. Hence the option in this plugin that restricts
	 * CiviCRM to the main site only.
	 *
	 * The compromise made here is to default to switching to the main site
	 * and offer a filter for developers to override this plugin's behaviour.
	 *
	 * @since 0.3
	 */
	public function admin_bar_add() {

		// bail if admin bar not enabled
		if ( $this->admin->setting_get( 'admin_bar', '0' ) == '0' ) return;

		// bail if user cannot access CiviCRM
		if ( ! current_user_can( 'access_civicrm' ) ) return;

		/**
		 * Filter the switch-to-blog process for the menu.
		 *
		 * Note to developers: if you have enabled CiviCRM on subsites in your
		 * multisite install, use the following code to disable the switch:
		 *
		 * add_filter( 'civicrm_admin_utilities_menu_switch', __return_false );
		 *
		 * If you need more granular control over whether to switch to the main
		 * site or not, create a callback method and inspect the $current_site
		 * object for whether the appropriate conditions are met.
		 *
		 * @since 0.3
		 */
		$switch = apply_filters( 'civicrm_admin_utilities_menu_switch', true );

		// if it's multisite, then switch to main site
		$switch_back = false;
		if ( is_multisite() AND ! is_main_site() AND $switch ) {

			// bail if CiviCRM is disabled on subsites
			if ( $this->admin->setting_get( 'main_site_only', '0' ) == '1' ) return;

			// get current site data
			$current_site = get_current_site();

			// switch to the main site and set flag
			switch_to_blog( $current_site->blog_id );
			$switch_back = true;

		}

		// access admin bar
		global $wp_admin_bar;

		// init CiviCRM or bail
		if ( ! $this->admin->is_active() ) return;

		// get component info
		$components = CRM_Core_Component::getEnabledComponents();

		// define a menu parent ID
		$id = 'civicrm-admin-utils';

		// add parent
		$wp_admin_bar->add_menu( array(
			'id' => $id,
			'title' => __( 'CiviCRM', 'civicrm-admin-utilities' ),
			'href' => admin_url( 'admin.php?page=CiviCRM' ),
		) );

		// dashboard
		$wp_admin_bar->add_menu( array(
			'id' => 'cau-1',
			'parent' => $id,
			'title' => __( 'CiviCRM Dashboard', 'civicrm-admin-utilities' ),
			'href' => admin_url( 'admin.php?page=CiviCRM' ),
		) );

		// search
		$wp_admin_bar->add_menu( array(
			'id' => 'cau-2',
			'parent' => $id,
			'title' => __( 'Advanced Search', 'civicrm-admin-utilities' ),
			'href' => $this->get_link( 'civicrm/contact/search/advanced', 'reset=1' ),
		) );

		// groups
		$wp_admin_bar->add_menu( array(
			'id' => 'cau-3',
			'parent' => $id,
			'title' => __( 'Manage Groups', 'civicrm-admin-utilities' ),
			'href' => $this->get_link( 'civicrm/group', 'reset=1' ),
		) );

		// contributions
		if ( array_key_exists( 'CiviContribute', $components ) ) {
			if ( $this->check_permission( 'access CiviContribute' ) ) {
				$wp_admin_bar->add_menu( array(
					'id' => 'cau-4',
					'parent' => $id,
					'title' => __( 'Contribution Dashboard', 'civicrm-admin-utilities' ),
					'href' => $this->get_link( 'civicrm/contribute', 'reset=1' ),
				) );
			}
		}

		// membership
		if ( array_key_exists( 'CiviMember', $components ) ) {
			if ( $this->check_permission( 'access CiviMember' ) ) {
				$wp_admin_bar->add_menu( array(
					'id' => 'cau-5',
					'parent' => $id,
					'title' => __( 'Membership Dashboard', 'civicrm-admin-utilities' ),
					'href' => $this->get_link( 'civicrm/member', 'reset=1' ),
				) );
			}
		}

		// events
		if ( array_key_exists( 'CiviEvent', $components ) ) {
			if ( $this->check_permission( 'access CiviEvent' ) ) {
				$wp_admin_bar->add_menu( array(
					'id' => 'cau-6',
					'parent' => $id,
					'title' => __( 'Events Dashboard', 'civicrm-admin-utilities' ),
					'href' => $this->get_link( 'civicrm/event', 'reset=1' ),
				) );
			}
		}

		// mailings
		if ( array_key_exists( 'CiviMail', $components ) ) {
			if ( $this->check_permission( 'access CiviMail' ) ) {
				$wp_admin_bar->add_menu( array(
					'id' => 'cau-7',
					'parent' => $id,
					'title' => __( 'Mailings Sent and Scheduled', 'civicrm-admin-utilities' ),
					'href' => $this->get_link( 'civicrm/mailing/browse/scheduled', 'reset=1&scheduled=true' ),
				) );
			}
		}

		// reports
		if ( array_key_exists( 'CiviReport', $components ) ) {
			if ( $this->check_permission( 'access CiviReport' ) ) {
				$wp_admin_bar->add_menu( array(
					'id'     => 'cau-8',
					'parent' => $id,
					'title'  => __( 'Report Listing', 'civicrm-admin-utilities' ),
					'href'   => $this->get_link( 'civicrm/report/list', '&reset=1' ),
				) );
			}
		}

		// cases
		if ( array_key_exists( 'CiviCase', $components ) ) {
			if ( CRM_Case_BAO_Case::accessCiviCase() ) {
				$wp_admin_bar->add_menu( array(
					'id' => 'cau-9',
					'parent' => $id,
					'title' => __( 'Cases Dashboard', 'civicrm-admin-utilities' ),
					'href' => $this->get_link( 'civicrm/case', 'reset=1' ),
				) );
			}
		}

		// admin console
		if ( $this->check_permission( 'administer CiviCRM' ) ) {
			$wp_admin_bar->add_menu( array(
				'id' => 'cau-10',
				'parent' => $id,
				'title' => __( 'Admin Console', 'civicrm-admin-utilities' ),
				'href' => $this->get_link( 'civicrm/admin', 'reset=1' ),
			) );
		}

		/**
		 * Fire action so that others can manipulate this menu.
		 *
		 * @since 0.3
		 *
		 * @param bool $switch Whether or not a switch to the main site has been made
		 */
		do_action( 'civicrm_admin_utilities_menu_after', $switch );

		// if it's multisite, then switch back to current blog
		if ( $switch_back ) {
			restore_current_blog();
		}

	}



	/**
	 * Get a CiviCRM admin link.
	 *
	 * @since 0.3
	 *
	 * @param str $path The CiviCRM path.
	 * @param str $params The CiviCRM parameters.
	 * @return string $link The URL of the CiviCRM page.
	 */
	public function get_link( $path = '', $params = null ) {

		// init link
		$link = '';

		// init CiviCRM or bail
		if ( ! $this->admin->is_active() ) return $link;

		// use CiviCRM to construct link
		$link = CRM_Utils_System::url(
			$path,
			$params,
			TRUE,
			NULL,
			TRUE,
			FALSE,
			TRUE
		);

		// --<
		return $link;

	}



	/**
	 * Check a CiviCRM permission.
	 *
	 * @since 0.3
	 *
	 * @param str $permission The permission string.
	 * @return bool $permitted True if allowed, false otherwise.
	 */
	public function check_permission( $permission ) {

		// always deny if CiviCRM is not active
		if ( ! $this->admin->is_active() ) return false;

		// deny by default
		$permitted = false;

		// check CiviCRM permissions
		if ( CRM_Core_Permission::check( $permission ) ) {
			$permitted = true;
		}

		/**
		 * Return permission but allow overrides.
		 *
		 * @since 0.3
		 *
		 * @param bool $permitted True if allowed, false otherwise.
		 * @param str $permission The CiviCRM permission string.
		 * @return bool $permitted True if allowed, false otherwise.
		 */
		return apply_filters( 'civicrm_admin_utilities_permitted', $permitted, $permission );

	}



	/**
	 * Fixes the WordPress Access Control form by building a single table.
	 *
	 * @since 0.3
	 *
	 * @param string $formName The name of the form.
	 * @param CRM_Core_Form $form The form object.
	 */
	public function fix_permissions_form( $formName, &$form ) {

		// bail if disabled
		if ( $this->admin->setting_get( 'prettify_access', '0' ) == '0' ) return;

		// bail if CiviCRM has been fixed
		if ( $this->admin->access_form_fixed() ) return;

		// bail if not the form we want
		if ( $formName != 'CRM_ACL_Form_WordPress_Permissions' ) return;

		// get vars
		$vars = $form->get_template_vars();

		// bail if $permDesc does not exist
		if ( ! isset( $vars['permDesc'] ) ) return;

		// build replacement for permDesc array
		foreach( $vars['rolePerms'] AS $role => $perms ) {
			foreach( $perms AS $name => $title ) {
				$permissions[$name] = $title;
			}
		}

		// build array keyed by permission
		$table = array();
		foreach( $permissions AS $perm => $label ) {

			// init row with permission description
			$table[$perm] = array(
				'label' => $label,
				'roles' => array(),
			);

			// add permission label and role names
			foreach( $vars['roles'] AS $key => $label ) {
				if ( isset( $vars['permDesc'][$perm] ) ) {
					$table[$perm]['desc'] = $vars['permDesc'][$perm];
				}
				$table[$perm]['roles'][] = $key;
			}

		}

		// assign to form
		$form->assign( 'table', $table );

		// camelcase dammit
		CRM_Utils_System::setTitle( __( 'WordPress Access Control', 'civicrm-admin-utilities' ) );

	}



	//##########################################################################



	/**
	 * Utility for tracing calls to hook_civicrm_pre.
	 *
	 * @param string $op the type of database operation.
	 * @param string $objectName the type of object.
	 * @param integer $objectId the ID of the object.
	 * @param object $objectRef the object.
	 */
	public function trace_pre( $op, $objectName, $objectId, $objectRef ) {

		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
			'backtrace' => $trace,
		), true ) );

	}



	/**
	 * Utility for tracing calls to hook_civicrm_post.
	 *
	 * @param string $op the type of database operation.
	 * @param string $objectName the type of object.
	 * @param integer $objectId the ID of the object.
	 * @param object $objectRef the object.
	 */
	public function trace_post( $op, $objectName, $objectId, $objectRef ) {

		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
			'backtrace' => $trace,
		), true ) );

	}



	/**
	 * Utility for tracing calls to hook_civicrm_postProcess.
	 *
	 * @param string $formName The name of the form.
	 * @param object $form The form object.
	 */
	public function trace_postProcess( $formName, &$form ) {

		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'formName' => $formName,
			'form' => $form,
			'backtrace' => $trace,
		), true ) );

	}



} // class ends



// init plugin
global $civicrm_admin_utilities;
$civicrm_admin_utilities = new CiviCRM_Admin_Utilities;

/**
 * Utility to get a reference to this plugin.
 *
 * @since 0.3.4
 *
 * @return object $civicrm_admin_utilities The plugin reference.
 */
function civicrm_au() {

	// return instance
	global $civicrm_admin_utilities;
	return $civicrm_admin_utilities;

}



// uninstall will use the 'uninstall.php' method when fully built
// see: http://codex.wordpress.org/Function_Reference/register_uninstall_hook



/**
 * Utility to add link to settings page.
 *
 * @since 0.3
 *
 * @param array $links The existing links array.
 * @param str $file The name of the plugin file.
 * @return array $links The modified links array.
 */
function civicrm_admin_utilities_action_links( $links, $file ) {

	// add links only when CiviCRM is fully installed
	if ( ! defined( 'CIVICRM_INSTALLED' ) ) return $links;
	if ( ! CIVICRM_INSTALLED ) return $links;

	// add settings link
	if ( $file == plugin_basename( dirname( __FILE__ ) . '/civicrm-admin-utilities.php' ) ) {

		// add settings link if network activated and viewing network admin
		if ( civicrm_au()->admin->is_network_activated() AND is_network_admin() ) {
			$link = add_query_arg( array( 'page' => 'civicrm_admin_utilities' ), network_admin_url( 'settings.php' ) );
			$links[] = '<a href="' . esc_url( $link ) . '">' . esc_html__( 'Settings', 'civicrm-admin-utilities' ) . '</a>';
		}

		// add settings link if not network activated and not viewing network admin
		if ( ! civicrm_au()->admin->is_network_activated() AND ! is_network_admin() ) {
			$link = add_query_arg( array( 'page' => 'civicrm_admin_utilities' ), admin_url( 'options-general.php' ) );
			$links[] = '<a href="' . esc_url( $link ) . '">' . esc_html__( 'Settings', 'civicrm-admin-utilities' ) . '</a>';
		}

		// always add Paypal link
		$paypal = 'https://www.paypal.me/interactivist';
		$links[] = '<a href="' . $paypal . '" target="_blank">' . __( 'Donate!', 'civicrm-admin-utilities' ) . '</a>';

	}

	// --<
	return $links;

}

// add filters for the above
add_filter( 'network_admin_plugin_action_links', 'civicrm_admin_utilities_action_links', 10, 2 );
add_filter( 'plugin_action_links', 'civicrm_admin_utilities_action_links', 10, 2 );



