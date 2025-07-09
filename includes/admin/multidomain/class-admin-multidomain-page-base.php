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
 * Multidomain Base Class.
 *
 * A class that encapsulates common Multidomain Settings page functionality.
 *
 * @since 1.0.9
 */
abstract class CAU_Admin_Multidomain_Page_Base {

	/**
	 * Plugin object.
	 *
	 * @since 1.0.9
	 * @access public
	 * @var CiviCRM_Admin_Utilities
	 */
	public $plugin;

	/**
	 * Multisite object.
	 *
	 * @since 1.0.9
	 * @access public
	 * @var CiviCRM_Admin_Utilities_Multisite
	 */
	public $multisite;

	/**
	 * Multidomain object.
	 *
	 * @since 1.0.9
	 * @access public
	 * @var CAU_Admin_Multidomain_Loader
	 */
	public $multidomain;

	/**
	 * Settings page reference.
	 *
	 * @since 1.0.9
	 * @access public
	 * @var string
	 */
	public $page_handle;

	/**
	 * Constructor.
	 *
	 * @since 1.0.9
	 */
	public function __construct() {

		// Initialise when multidomain is loaded.
		add_action( 'cau/multidomain/loaded', [ $this, 'initialise' ] );
		add_action( 'cau/multidomain/loaded', [ $this, 'register_hooks' ] );
		add_action( 'cau/multidomain/loaded', [ $this, 'register_hooks_common' ] );

	}

	/**
	 * Initialise this object.
	 *
	 * @since 1.0.9
	 */
	public function initialise() {}

	/**
	 * Register common hooks.
	 *
	 * @since 1.0.9
	 */
	public function register_hooks_common() {

		/*
		// Add Domains AJAX handler.
		add_action( 'wp_ajax_cau_domains_get', [ $this, 'domains_ajax_get' ] );
		*/

		// Add Domain Groups AJAX handler.
		add_action( 'wp_ajax_cau_domain_groups_get', [ $this, 'domain_groups_ajax_get' ] );

		// Add Domain Orgs AJAX handler.
		add_action( 'wp_ajax_cau_domain_orgs_get', [ $this, 'domain_orgs_ajax_get' ] );

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the Domains registered in CiviCRM.
	 *
	 * Not used.
	 *
	 * @since 1.0.9
	 */
	public function domains_ajax_get() {

		// Init return.
		$json = [];

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->civicrm->is_initialised() ) {
			wp_send_json( $json );
		}

		// Since this is an AJAX request, check security.
		$result = check_ajax_referer( 'cau_domains', false, false );
		if ( false === $result ) {
			wp_send_json( $json );
		}

		// Sanitise search input.
		$search = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';

		try {

			// Call the API.
			$result = \Civi\Api4\Domain::get( false )
				->addSelect( '*' )
				->addWhere( 'name', 'LIKE', '%' . $search . '%' )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			wp_send_json( $json );
		}

		// Return if nothing found.
		if ( 0 === $result->count() ) {
			wp_send_json( $json );
		}

		// Loop through our Domains.
		foreach ( $result as $domain ) {

			// Add domain data to output array.
			$json[] = [
				'id'          => (int) $domain['id'],
				'name'        => esc_html( stripslashes( $domain['name'] ) ),
				'description' => isset( $domain['description'] ) ? esc_html( $domain['description'] ) : '',
			];

		}

		// Send data.
		wp_send_json( $json );

	}

	/**
	 * Get the Domain Groups registered in CiviCRM.
	 *
	 * @since 1.0.9
	 */
	public function domain_groups_ajax_get() {

		// Init return.
		$json = [];

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->civicrm->is_initialised() ) {
			wp_send_json( $json );
		}

		// This is an AJAX request, so check security.
		$result = check_ajax_referer( 'cau_domain_group', false, false );
		if ( false === $result ) {
			wp_send_json( $json );
		}

		// Prevent existing Domain Groups from being selected.
		$domains_data = $this->multidomain->reference_data_get_all();
		$used_groups  = ! empty( $domains_data ) ? array_column( $domains_data, 'group_id' ) : [];

		// Sanitise search input.
		$search = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';

		try {

			// Call the API.
			$result = \Civi\Api4\Group::get( false )
				->addSelect( '*' )
				->addWhere( 'id', 'NOT IN', $used_groups )
				->addWhere( 'title', 'LIKE', '%' . $search . '%' )
				->addWhere( 'visibility', '=', 'User and User Admin Only' )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			wp_send_json( $json );
		}

		// Return if nothing found.
		if ( 0 === $result->count() ) {
			wp_send_json( $json );
		}

		// Loop through our Groups.
		foreach ( $result as $group ) {

			// Add group data to output array.
			$json[] = [
				'id'          => (int) $group['id'],
				'name'        => esc_html( stripslashes( $group['title'] ) ),
				'description' => '',
			];

		}

		// Send data.
		wp_send_json( $json );

	}

	/**
	 * Get the Domain Orgs registered in CiviCRM.
	 *
	 * @since 1.0.9
	 */
	public function domain_orgs_ajax_get() {

		// Init return.
		$json = [];

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->civicrm->is_initialised() ) {
			wp_send_json( $json );
		}

		// This is an AJAX request, so check security.
		$result = check_ajax_referer( 'cau_domain_org', false, false );
		if ( false === $result ) {
			wp_send_json( $json );
		}

		// Prevent existing Domain Organisations from being selected.
		$domains_data = $this->multidomain->reference_data_get_all();
		$used_orgs    = ! empty( $domains_data ) ? array_column( $domains_data, 'org_id' ) : [];

		// Sanitise search input.
		$search = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';

		try {

			// Call the API.
			$result = \Civi\Api4\Contact::get( false )
				->addSelect( '*' )
				->addWhere( 'id', 'NOT IN', $used_orgs )
				->addWhere( 'contact_type', '=', 'Organization' )
				->addWhere( 'organization_name', 'LIKE', '%' . $search . '%' )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			wp_send_json( $json );
		}

		// Return if nothing found.
		if ( 0 === $result->count() ) {
			wp_send_json( $json );
		}

		// Loop through our Domain Organisations.
		foreach ( $result as $org ) {

			// Add org data to output array.
			$json[] = [
				'id'          => (int) $org['id'],
				'name'        => esc_html( stripslashes( $org['display_name'] ) ),
				'description' => '',
			];

		}

		// Send data.
		wp_send_json( $json );

	}

	// -------------------------------------------------------------------------

	/**
	 * Form redirection handler.
	 *
	 * @since 1.0.9
	 *
	 * @param string $url The menu page URL.
	 */
	public function form_redirect( $url ) {

		// Our array of arguments.
		$args = [ 'updated' => 'true' ];

		// Redirect to our Settings Page.
		wp_safe_redirect( add_query_arg( $args, $url ) );
		exit;

	}

}
