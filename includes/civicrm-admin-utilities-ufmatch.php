<?php
/**
 * UFMatch Class.
 *
 * Handles User-Contact matching functionality.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.6.8
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Admin Utilities UFMatch Class.
 *
 * A class that encapsulates User-Contact matching functionality.
 *
 * @since 0.6.8
 */
class CiviCRM_Admin_Utilities_UFMatch {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.6.8
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;



	/**
	 * Constructor.
	 *
	 * @since 0.6.8
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
	 * @since 0.6.8
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.6.8
	 */
	public function register_hooks() {

		// Retain UFMatch data on "soft delete".
		add_action( 'civicrm_admin_utilities_contact_pre_trashed', [ $this, 'entries_get' ], 10, 1 );
		add_action( 'civicrm_admin_utilities_contact_post_trashed', [ $this, 'entries_restore' ], 10, 1 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get a Contact's Details.
	 *
	 * @since 0.1
	 *
	 * @param int $contact_id The numeric ID of the Contact.
	 * @param array|bool $contact The array of Contact data, or false if none.
	 */
	public function contact_get_by_id( $contact_id ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return false;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'id' => $contact_id,
		];

		// Get Contact details via API.
		$contact = civicrm_api( 'Contact', 'getsingle', $params );

		// Log and bail on failure.
		if ( isset( $contact['is_error'] ) AND $contact['is_error'] == '1' ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'contact_id' => $contact_id,
				'params' => $params,
				'contact' => $contact,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// --<
		return $contact;

	}



	/**
	 * Get a CiviCRM Contact ID for a given WordPress User ID.
	 *
	 * By default, CiviCRM will return the matching Contact ID in the current
	 * Domain only. Pass a numeric Domain ID and only that Domain will be queried.
	 *
	 * Sometimes, however, we need to know if there is a matching Contact in
	 * *any* Domain - if so, pass a string such as "all" for "$domain_id" and
	 * all Domains will be searched for a matching Contact.
	 *
	 * @since 0.1
	 *
	 * @param int $user_id The numeric ID of the WordPress user.
	 * @param int|str $domain_id The Domain ID (defaults to current Domain ID) or a string to search all Domains.
	 * @return int|bool $contact_id The CiviCRM contact ID, or false on failure.
	 */
	public function contact_id_get_by_user_id( $user_id, $domain_id = '' ) {

		// Bail if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return false;
		}

		// Get UFMatch entry.
		$entry = $this->entry_get_by_user_id( $user_id, $domain_id );

		// Bail if we didn't get one.
		if ( $entry === false ) {
			return false;
		}

		// Get the Contact ID if present.
		if ( ! empty( $entry['contact_id'] ) ) {
			return absint( $entry['contact_id'] );
		}

		// Get the Contact ID from the returned array.
		if ( empty( $entry['contact_id'] ) ) {
			foreach( $entry AS $item ) {
				return absint( $item['contact_id'] );
			}
		}

		// Fall back to false.
		return false;

	}



	/**
	 * Get a CiviCRM Contact for a given WordPress user ID.
	 *
	 * @since 0.1
	 *
	 * @param int $user_id The numeric ID of the WordPress User.
	 * @param int|str $domain_id The Domain ID (defaults to current Domain ID) or a string to search all Domains.
	 * @return array|bool $contact The CiviCRM Contact data, or false on failure.
	 */
	public function contact_get_by_user_id( $user_id, $domain_id = '' ) {

		// Get the contact ID.
		$contact_id = $this->contact_id_get_by_user_id( $user_id, $domain_id );

		// Bail if we didn't get one.
		if ( $contact_id === false ) {
			return false;
		}

		// Get Contact data.
		$contact = $this->contact_get_by_id( $contact_id );

		// --<
		return $contact;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get a WordPress User ID given a CiviCRM Contact ID.
	 *
	 * @since 0.6.8
	 *
	 * @param int $contact_id The numeric ID of the CiviCRM Contact.
	 * @param int|str $domain_id The Domain ID (defaults to current Domain ID) or a string to search all Domains.
	 * @return WP_User|bool $user The WordPress User object, or false on failure.
	 */
	public function user_id_get_by_contact_id( $contact_id, $domain_id = '' ) {

		// Get UFMatch entry (or entries).
		$entry = $this->entry_get_by_contact_id( $contact_id, $domain_id );

		// Bail if we didn't get one.
		if ( $entry === false ) {
			return false;
		}

		// Get the User ID if a single UFMatch item is returned.
		if ( ! empty( $entry['uf_id'] ) ) {
			return absint( $entry['uf_id'] );
		}

		// Get the User ID from the returned array.
		if ( empty( $entry['uf_id'] ) ) {
			foreach( $entry AS $item ) {
				return absint( $item['uf_id'] );
			}
		}

		// Fall back to false.
		return false;

	}



	/**
	 * Get a WordPress User given a CiviCRM Contact ID.
	 *
	 * @since 0.6.8
	 *
	 * @param int $contact_id The numeric ID of the CiviCRM Contact.
	 * @param int|str $domain_id The Domain ID (defaults to current Domain ID) or a string to search all Domains.
	 * @return WP_User|bool $user The WordPress User object, or false on failure.
	 */
	public function user_get_by_contact_id( $contact_id, $domain_id = '' ) {

		// Get WordPress User ID.
		$user_id = $this->user_id_get_by_contact_id( $contact_id, $domain_id );

		// Bail if we didn't get one.
		if ( $user_id === false ) {
			return false;
		}

		// Get User object.
		$user = new WP_User( $user_id );

		// --<
		return $user;

	}



	// -------------------------------------------------------------------------



	/**
	 * Act when a Contact is about to be moved into the Trash.
	 *
	 * @since 0.6.8
	 *
	 * @param array $contact The Contact data array.
	 */
	public function entries_get( $contact ) {

		// Get all UFMatch entries for this Contact.
		$entries = $this->entry_get_by_contact_id( $contact['id'], 'all' );

		// Bail if we didn't get any.
		if ( empty( $entries ) ) {
			return;
		}

		// Assign to a property for processing in `entries_restore()` below.
		$this->ufmatch_entries = $entries;

	}



	/**
	 * Act when a Contact has been moved into the Trash.
	 *
	 * @since 0.6.8
	 *
	 * @param CRM_Contact_DAO_Contact $contact The Contact object.
	 */
	public function entries_restore( $contact ) {

		// Bail if there are no entries to restore.
		if ( ! isset( $this->ufmatch_entries ) ) {
			return;
		}

		// Create single UFMatch entry if a single UFMatch item is returned.
		if ( ! empty( $this->ufmatch_entries['uf_id'] ) ) {
			$contact_id = absint( $this->ufmatch_entries['contact_id'] );
			$user_id = absint( $this->ufmatch_entries['uf_id'] );
			$domain_id = absint( $this->ufmatch_entries['domain_id'] );
			$this->entry_create( $contact_id, $user_id, $this->ufmatch_entries['uf_name'], $domain_id );
		}

		// Create multiple UFMatch entries if an array is returned.
		if ( empty( $this->ufmatch_entries['uf_id'] ) ) {
			foreach( $this->ufmatch_entries AS $entry ) {
				$contact_id = absint( $entry['contact_id'] );
				$user_id = absint( $entry['uf_id'] );
				$domain_id = absint( $entry['domain_id'] );
				$this->entry_create( $contact_id, $user_id, $entry['uf_name'], $domain_id );
			}
		}

	}



	// -------------------------------------------------------------------------



	/**
	 * Create a link between a WordPress User and a CiviCRM Contact.
	 *
	 * This method optionally allows a Domain ID to be specified.
	 *
	 * @since 0.6.8
	 *
	 * @param int $contact_id The numeric ID of the CiviCRM Contact.
	 * @param int $user_id The numeric ID of the WordPress User.
	 * @param str $username The WordPress username.
	 * @param int $domain_id The CiviCRM Domain ID (defaults to current Domain ID).
	 * @return array|bool The UFMatch data on success, or false on failure.
	 */
	public function entry_create( $contact_id, $user_id, $username, $domain_id = '' ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return false;
		}

		// Sanity checks.
		if ( ! is_numeric( $contact_id ) OR ! is_numeric( $user_id ) ) {
			return false;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'uf_id' => $user_id,
			'uf_name' => $username,
			'contact_id' => $contact_id,
		];

		// Maybe add Domain ID.
		if ( ! empty( $domain_id ) ) {
			$params['domain_id'] = $domain_id;
		}

		// Create record via API.
		$result = civicrm_api( 'UFMatch', 'create', $params );

		// Log and bail on failure.
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// --<
		return $result;

	}



	/**
	 * Delete the link between a WordPress User and a CiviCRM Contact.
	 *
	 * @since 0.6.8
	 *
	 * @param int $ufmatch_id The numeric ID of the UFMatch entry.
	 * @return array|bool The UFMatch data on success, or false on failure.
	 */
	public function entry_delete( $ufmatch_id ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return false;
		}

		// Sanity checks.
		if ( ! is_numeric( $ufmatch_id ) ) {
			return false;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'id' => $ufmatch_id,
		];

		// Create record via API.
		$result = civicrm_api( 'UFMatch', 'delete', $params );

		// Log and bail on failure.
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// --<
		return $result;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the UFMatch data for a given CiviCRM Contact ID.
	 *
	 * This method optionally allows a Domain ID to be specified.
	 * If no Domain ID is passed, then we default to current Domain ID.
	 * If a Domain ID is passed as a string, then we search all Domain IDs.
	 *
	 * @since 0.6.8
	 *
	 * @param int $contact_id The numeric ID of the CiviCRM Contact.
	 * @param int|str $domain_id The CiviCRM Domain ID (defaults to current Domain ID).
	 * @return array|bool The UFMatch data on success, or false on failure.
	 */
	public function entry_get_by_contact_id( $contact_id, $domain_id = '' ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return false;
		}

		// Sanity checks.
		if ( ! is_numeric( $contact_id ) ) {
			return false;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'contact_id' => $contact_id,
		];

		// If no Domain ID is specified, default to current Domain ID.
		if ( empty( $domain_id ) ) {
			$params['domain_id'] = CRM_Core_Config::domainID();
		}

		// Maybe add Domain ID if passed as an integer.
		if ( ! empty( $domain_id ) AND is_numeric( $domain_id ) ) {
			$params['domain_id'] = $domain_id;
		}

		// Get all UFMatch records via API.
		$result = civicrm_api( 'UFMatch', 'get', $params );

		// Log and bail on failure.
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'user_id' => $user_id,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// Return the entry data if there's only one.
		if ( ! empty( $result['values'] ) AND count( $result['values'] ) === 1 ) {
			return array_pop( $result['values'] );
		}

		// Return the entries array if there are more than one.
		if ( ! empty( $result['values'] ) AND count( $result['values'] ) > 1 ) {
			return $result['values'];
		}

		// Fall back to false.
		return false;

	}



	/**
	 * Get the UFMatch data for a given WordPress User ID.
	 *
	 * This method optionally allows a Domain ID to be specified.
	 * If no Domain ID is passed, then we default to current Domain ID.
	 * If a Domain ID is passed as a string, then we search all Domain IDs.
	 *
	 * @since 0.6.8
	 *
	 * @param int $user_id The numeric ID of the WordPress User.
	 * @param int|str $domain_id The CiviCRM Domain ID (defaults to current Domain ID).
	 * @return array|bool The UFMatch data on success, or false on failure.
	 */
	public function entry_get_by_user_id( $user_id, $domain_id = '' ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return false;
		}

		// Sanity checks.
		if ( ! is_numeric( $user_id ) ) {
			return false;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'uf_id' => $user_id,
		];

		// If no Domain ID is specified, default to current Domain ID.
		if ( empty( $domain_id ) ) {
			$params['domain_id'] = CRM_Core_Config::domainID();
		}

		// Maybe add Domain ID if passed as an integer.
		if ( ! empty( $domain_id ) AND is_numeric( $domain_id ) ) {
			$params['domain_id'] = $domain_id;
		}

		// Get all UFMatch records via API.
		$result = civicrm_api( 'UFMatch', 'get', $params );

		// Log and bail on failure.
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'user_id' => $user_id,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// Return the entry data if there's only one.
		if ( ! empty( $result['values'] ) AND count( $result['values'] ) === 1 ) {
			return array_pop( $result['values'] );
		}

		// Return the entries array if there are more than one.
		if ( ! empty( $result['values'] ) AND count( $result['values'] ) > 1 ) {
			return $result['values'];
		}

		// Fall back to false.
		return false;

	}



	/**
	 * Get the UFMatch data for a given WordPress User email.
	 *
	 * This method optionally allows a Domain ID to be specified.
	 * If no Domain ID is passed, then we default to current Domain ID.
	 * If a Domain ID is passed as a string, then we search all Domain IDs.
	 *
	 * @since 0.6.8
	 *
	 * @param str $email The WordPress User's email address.
	 * @param int|str $domain_id The CiviCRM Domain ID (defaults to current Domain ID).
	 * @return array|bool The UFMatch data on success, or false on failure.
	 */
	public function entry_get_by_user_email( $email, $domain_id = '' ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return false;
		}

		// Sanity checks.
		if ( ! is_numeric( $contact_id ) ) {
			return false;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'uf_name' => $email,
		];

		// If no Domain ID is specified, default to current Domain ID.
		if ( empty( $domain_id ) ) {
			$params['domain_id'] = CRM_Core_Config::domainID();
		}

		// Maybe add Domain ID if passed as an integer.
		if ( ! empty( $domain_id ) AND is_numeric( $domain_id ) ) {
			$params['domain_id'] = $domain_id;
		}

		// Get all UFMatch records via API.
		$result = civicrm_api( 'UFMatch', 'get', $params );

		// Log and bail on failure.
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'user_id' => $user_id,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// Return the entry data if there's only one.
		if ( ! empty( $result['values'] ) AND count( $result['values'] ) === 1 ) {
			return array_pop( $result['values'] );
		}

		// Return the entries array if there are more than one.
		if ( ! empty( $result['values'] ) AND count( $result['values'] ) > 1 ) {
			return $result['values'];
		}

		// Fall back to false.
		return false;

	}



} // Class ends.



