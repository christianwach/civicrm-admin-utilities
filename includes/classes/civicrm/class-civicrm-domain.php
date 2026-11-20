<?php
/**
 * Domain Class.
 *
 * Handles User-Contact matching functionality.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 1.0.9
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Domain Class.
 *
 * A class that encapsulates User-Contact matching functionality.
 *
 * @since 1.0.9
 */
class CAU_CiviCRM_Domain {

	/**
	 * Plugin object.
	 *
	 * @since 1.0.9
	 * @access public
	 * @var CiviCRM_Admin_Utilities
	 */
	public $plugin;

	/**
	 * CiviCRM object.
	 *
	 * @since 1.0.9
	 * @access public
	 * @var CAU_CiviCRM
	 */
	public $civicrm;

	/**
	 * Constructor.
	 *
	 * @since 1.0.9
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
	 *
	 * @since 1.0.9
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.9
	 */
	public function register_hooks() {

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the Domains registered in CiviCRM.
	 *
	 * @since 1.0.9
	 *
	 * @return array $domains The array of Domains registered in CiviCRM.
	 */
	public function get_all() {

		// Init return array.
		$domains = [];

		// Bail if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return $domains;
		}

		try {

			// Call the API.
			$result = \Civi\Api4\Domain::get( false )
				->addSelect( '*' )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Return if nothing found.
		if ( 0 === $result->count() ) {
			return [];
		}

		// We only need the ArrayObject.
		$domains = $result->getArrayCopy();

		// --<
		return $domains;

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the current CiviCRM Domain ID.
	 *
	 * @since 1.0.9
	 *
	 * @return int|bool $domain_id The Domain ID, numeric zero if not found, false on error.
	 */
	public function id_get_current() {

		// Bail if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Get CiviCRM Domain Group ID from constant, if set.
		$domain_id = defined( 'CIVICRM_DOMAIN_ID' ) ? (int) CIVICRM_DOMAIN_ID : 0;
		if ( 0 !== $domain_id ) {
			return $domain_id;
		}

		// If this fails, try to get from CiviCRM config.
		$domain_id = CRM_Core_Config::domainID();
		if ( 0 !== (int) $domain_id ) {
			return (int) $domain_id;
		}

		// Return not found.
		return 0;

	}

	/**
	 * Gets the data for a CiviCRM Domain.
	 *
	 * @since 1.0.9
	 *
	 * @param int $domain_id The ID of the Domain.
	 * @return array|bool $domain The array of Domain data, empty array if not found, false on error.
	 */
	public function get_by_id( $domain_id = 0 ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// If looking for the current Domain.
		if ( 0 === $domain_id ) {

			// Get the current CiviCRM Domain ID.
			$domain_id = $this->id_get_current();

			// Bail on error or nothing found.
			if ( empty( $domain_id ) ) {
				return false;
			}

		}

		try {

			// Call the API.
			$result = \Civi\Api4\Domain::get( false )
				->addSelect( '*' )
				->addWhere( 'id', '=', $domain_id )
				->setLimit( 1 )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'domain_id' => $domain_id,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Return if nothing found.
		if ( 0 === $result->count() ) {
			return [];
		}

		// The first result is what we're after.
		$domain = $result->first();

		// --<
		return $domain;

	}

	/**
	 * Creates a Domain with the "MultisiteDomain" API.
	 *
	 * This uses the API Entity supplied by the "CiviCRM Multisite" extension.
	 * The supplied name will be used as the name of both the Domain, the Domain
	 * Group and the Domain Organisation which will be auto-created by the same
	 * call. Additionally, the extension installs a menu for the Domain.
	 *
	 * @since 1.0.9
	 *
	 * @param str $name The name of the Domain.
	 * @return int|bool $domain_id The ID of the new Domain on success, false otherwise.
	 */
	public function multisite_create( $name ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Bail if "CiviCRM Multisite" extension is not active.
		$enabled = $this->plugin->civicrm->extension_is_enabled( 'org.civicrm.multisite' );
		if ( empty( $enabled ) ) {
			return false;
		}

		/**
		 * Fires just before a new "MultiDomain" is created.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/civcrm/domain/multisite_create/pre' );

		// Build params.
		$params = [
			'version'          => 3,
			'sequential'       => 1,
			'name'             => $name,
			'is_transactional' => 'FALSE',
		];

		// Create domain.
		$result = civicrm_api( 'MultisiteDomain', 'create', $params );

		/**
		 * Fires just after a new "MultiDomain" has been created.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/civcrm/domain/multisite_create/post' );

		// Sanity check.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'params'    => $params,
				'result'    => $result,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Bail if empty.
		if ( empty( $result['values'] ) ) {
			return false;
		}

		// The array of new Domain data should be the only item.
		$domain = array_pop( $result['values'] );

		// Grab the Domain ID.
		$domain_id = ! empty( $domain['id'] ) ? (int) $domain['id'] : false;

		// --<
		return $domain_id;

	}

	/**
	 * Deletes a Domain created with the "MultisiteDomain" API.
	 *
	 * This is a non-functional placeholder that simply details what the process
	 * of deleting a Domain might be.
	 *
	 * Neither CiviCRM nor the Multisite Extension has code through which a
	 * Domain can be deleted - most likely because it is an operation that could
	 * delete critical data. Therefore we would ideally check the Domain before
	 * deleting it to make sure there is no data that is associated with it -
	 * otherwise things get complicated very quickly.
	 *
	 * There is a multi-stage process to reverse what has been created via the
	 * domain_create() method above. The stages are:
	 *
	 * 1) Delete the Navigation Menu.
	 * 2) Delete the Setting which defines the Domain Group.
	 * 3) Delete the Domain Group.
	 * 4) Delete the Domain Org.
	 * 5) Delete the Domain.
	 *
	 * @since 1.0.9
	 *
	 * @param int $id The ID of the Domain.
	 * @return int|bool The ID of the deleted Domain, or false on failure.
	 */
	public function multisite_delete( $id ) {
		return false;
	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the current Domain Group ID.
	 *
	 * The priority for determining the ID of the Domain Group is as follows:
	 *
	 * 1) Check "CIVICRM_DOMAIN_GROUP_ID" constant.
	 * 2) Check "domain_group_id" setting via API.
	 * 3) Check for Group with the same name as the Domain. (Yes really)
	 *
	 * I'm not persuaded that (3) is good practice - it seems a very brittle
	 * way of storing this relationship. However CiviCRM Core uses that as a
	 * way to get the Group ID so it needs to remain here too. In conclusion,
	 * therefore, only the "domain_group_id" setting should be trusted as the
	 * source of the canonical Domain Group ID.
	 *
	 * The reason there is some commented-out code to look for a unique
	 * "GroupOrganization" linkage via the API is that MultisiteDomain.create
	 * makes such a link between the Domain Group and Domain Org. However it
	 * may not be a unique entry and could therefore be misleading.
	 *
	 * @see CRM_Core_BAO_Domain::getGroupId()
	 *
	 * @since 1.0.9
	 *
	 * @return int|bool $domain_id The Domain Group ID, numeric zero if not found, false on error.
	 */
	public function group_id_get_current() {

		// Get CiviCRM Domain Group ID from constant, if set.
		$domain_group_id = defined( 'CIVICRM_DOMAIN_GROUP_ID' ) ? (int) CIVICRM_DOMAIN_GROUP_ID : 0;
		if ( 0 !== $domain_group_id ) {
			return $domain_group_id;
		}

		// If this fails, try to get it from the CiviCRM setting in the current Domain.
		$domain_group_id = $this->plugin->civicrm->setting_get( 'domain_group_id' );
		if ( 0 !== (int) $domain_group_id ) {
			return (int) $domain_group_id;
		}

		// If this fails, check for Group with the name of the Domain.
		$domain = $this->get_by_id();

		// Bail on error or nothing found.
		if ( empty( $domain ) ) {
			return false;
		}

		// Get the Group with the name of the Domain.
		$domain_group = $this->group_get_by_name( $domain['name'] );

		// If we were successful, cast as integer and return the ID.
		if ( ! empty( $domain_group['id'] ) ) {
			$domain_group_id = (int) $domain_group['id'];
			return $domain_group_id;
		}

		/*
		// Build params.
		$params = [
			'version'         => 3,
			'sequential'      => 1,
			'organization_id' => $domain['contact_id'],
		];

		// Get result from "GroupOrganization".
		$result = civicrm_api( 'GroupOrganization', 'getsingle', $params );

		// If there is only a single linkage, cast as integer and return the ID.
		if ( ! empty( $result['group_id'] ) ) {
			$domain_group_id = absint( $result['group_id'] );
			return $domain_group_id;
		}
		*/

		// Return not found.
		return 0;

	}

	/**
	 * Gets the CiviCRM Domain Group ID for a given Domain ID.
	 *
	 * @since 1.0.9
	 *
	 * @param int $domain_id The ID of the Domain.
	 * @return int|bool $domain_group_id The Domain Group ID, numeric zero if not found, false on error.
	 */
	public function group_id_get_for_domain( $domain_id = 0 ) {

		// If looking for the current Domain Group.
		if ( 0 === $domain_id ) {

			// Get the current CiviCRM Domain Group ID.
			$domain_group_id = $this->group_id_get_current();

			// Bail on error or nothing found.
			if ( empty( $domain_group_id ) ) {
				return false;
			}

			// Return found ID.
			return $domain_group_id;

		}

		// Get it from the CiviCRM setting in the desired Domain.
		$domain_group_id = $this->plugin->civicrm->setting_get( 'domain_group_id', $domain_id );
		if ( 0 !== (int) $domain_group_id ) {
			return (int) $domain_group_id;
		}

		// If this fails, check for Group with the name of the Domain.
		$domain = $this->get_by_id();

		// Bail on error or nothing found.
		if ( empty( $domain ) ) {
			return false;
		}

		// Get the Group with the name of the Domain.
		$domain_group = $this->group_get_by_name( $domain['name'] );

		// If we were successful, cast as integer and return the ID.
		if ( ! empty( $domain_group['id'] ) ) {
			$domain_group_id = (int) $domain_group['id'];
			return $domain_group_id;
		}

		/*
		// Build params.
		$params = [
			'version'         => 3,
			'sequential'      => 1,
			'organization_id' => $domain['contact_id'],
		];

		// Get result from "GroupOrganization".
		$result = civicrm_api( 'GroupOrganization', 'getsingle', $params );

		// If there is only a single linkage, cast as integer and return the ID.
		if ( ! empty( $result['group_id'] ) ) {
			$domain_group_id = absint( $result['group_id'] );
			return $domain_group_id;
		}
		*/

		// Return not found.
		return 0;

	}

	/**
	 * Gets the data for a CiviCRM Domain Group for a given ID.
	 *
	 * @since 1.0.9
	 *
	 * @param int $domain_group_id The ID of the Domain Group.
	 * @return array|bool $domain_group The array of Domain Group data, empty array if not found, false on error.
	 */
	public function group_get_by_id( $domain_group_id = 0 ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// If looking for the current Domain Group.
		if ( 0 === $domain_group_id ) {

			// Get the current CiviCRM Domain Group ID.
			$domain_group_id = $this->group_id_get_current();

			// Bail on error or nothing found.
			if ( empty( $domain_group_id ) ) {
				return false;
			}

		}

		try {

			// Call the API.
			$result = \Civi\Api4\Group::get( false )
				->addSelect( '*' )
				->addWhere( 'id', '=', $domain_group_id )
				->setLimit( 1 )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'          => __METHOD__,
				'domain_group_id' => $domain_group_id,
				'error'           => $e->getMessage(),
				'backtrace'       => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Return if nothing found.
		if ( 0 === $result->count() ) {
			return [];
		}

		// The first result is what we're after.
		$domain_group = $result->first();

		// --<
		return $domain_group;

	}

	/**
	 * Gets the data for a CiviCRM Domain Group for a given Group name.
	 *
	 * @since 1.0.9
	 *
	 * @param int $name The name of the Domain Group.
	 * @return array|bool $domain_group The array of Domain Group data, empty array if not found, false on error.
	 */
	public function group_get_by_name( $name = '' ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		try {

			// Call the API.
			$result = \Civi\Api4\Group::get( false )
				->addSelect( '*' )
				->addWhere( 'title', '=', $name )
				->setLimit( 1 )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'name'      => $name,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Return early if nothing found.
		if ( 0 === $result->count() ) {
			return [];
		}

		// The first result is what we're after.
		$domain_group = $result->first();

	}

	/**
	 * Gets the data for a CiviCRM Domain Group for a given Domain ID.
	 *
	 * @since 1.0.9
	 *
	 * @param int $domain_id The ID of the Domain.
	 * @return array|bool $domain_group The array of Domain Group data, empty array if not found, false on error.
	 */
	public function group_get_for_domain( $domain_id = 0 ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// If looking for the current Domain Group.
		if ( 0 === $domain_id ) {

			// Get the current CiviCRM Domain Group ID.
			$domain_group_id = $this->group_id_get_current();

			// Bail on error or nothing found.
			if ( empty( $domain_group_id ) ) {
				return false;
			}

		} else {

			// Get it from the CiviCRM setting in the desired Domain.
			$domain_group_id = $this->plugin->civicrm->setting_get( 'domain_group_id', $domain_id );

		}

		try {

			// Call the API.
			$result = \Civi\Api4\Group::get( false )
				->addSelect( '*' )
				->addWhere( 'id', '=', $domain_group_id )
				->setLimit( 1 )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'          => __METHOD__,
				'domain_group_id' => $domain_group_id,
				'error'           => $e->getMessage(),
				'backtrace'       => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Return if nothing found.
		if ( 0 === $result->count() ) {
			return [];
		}

		// The first result is what we're after.
		$domain_group = $result->first();

		// --<
		return $domain_group;

	}

	/**
	 * Creates a Domain Group.
	 *
	 * @since 1.0.9
	 */
	public function group_create() {
		// Nothing to see yet.
	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the current CiviCRM Domain Organisation ID.
	 *
	 * @since 1.0.9
	 *
	 * @return int|bool $domain_id The Domain Organisation ID, numeric zero if not found, false on error.
	 */
	public function org_id_get_current() {

		// Get CiviCRM Domain Organisation ID from constant, if set.
		$domain_org_id = defined( 'CIVICRM_DOMAIN_ORG_ID' ) ? (int) CIVICRM_DOMAIN_ORG_ID : 0;
		if ( 0 !== $domain_org_id ) {
			return $domain_org_id;
		}

		// If this fails, try to get it from the Domain.
		$domain = $this->get_by_id();

		// Bail on error or nothing found.
		if ( empty( $domain ) ) {
			return false;
		}

		// Get the ID from the Domain.
		$domain_org_id = isset( $domain['contact_id'] ) ? (int) $domain['contact_id'] : 0;
		if ( 0 !== $domain_org_id ) {
			return $domain_org_id;
		}

		// Return not found.
		return 0;

	}

	/**
	 * Gets the data for a CiviCRM Domain Organisation for a given ID.
	 *
	 * @since 1.0.9
	 *
	 * @param int $domain_org_id The ID of the Domain Organisation.
	 * @return array|bool $domain_org The array of Domain Organisation data, empty array if not found, false on error.
	 */
	public function org_get_by_id( $domain_org_id = 0 ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// If looking for the current Domain Organisation.
		if ( 0 === $domain_org_id ) {

			// Get the current CiviCRM Domain Organisation ID.
			$domain_org_id = $this->org_id_get_current();

			// Bail on error or nothing found.
			if ( empty( $domain_org_id ) ) {
				return false;
			}

		}

		try {

			// Call the API.
			$result = \Civi\Api4\Contact::get( false )
				->addSelect( '*' )
				->addWhere( 'id', '=', $domain_org_id )
				->setLimit( 1 )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'        => __METHOD__,
				'domain_org_id' => $domain_org_id,
				'error'         => $e->getMessage(),
				'backtrace'     => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Return if nothing found.
		if ( 0 === $result->count() ) {
			return [];
		}

		// The first result is what we're after.
		$domain_org = $result->first();

		// --<
		return $domain_org;

	}

	/**
	 * Creates a Domain Organisation.
	 *
	 * @since 1.0.9
	 */
	public function org_create() {
		// Nothing to see yet.
	}

	/**
	 * Updates a Domain with a given Domain Organisation ID.
	 *
	 * @since 1.0.9
	 *
	 * @param int   $domain_id The ID of the Domain.
	 * @param int   $domain_org_id The ID of the Domain Organisation.
	 * @param bool  $overwrite Whether or not to overwrite Domain name with the Domain Organisation.
	 * @param array $domain_org The array of data for the Domain Organisation. Optional.
	 * @return array|int $domain The array of new Domain data on success, false otherwise.
	 */
	public function org_update( $domain_id, $domain_org_id, $overwrite = false, $domain_org = [] ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Get the Domain information when not supplied.
		if ( $overwrite && empty( $domain_org ) ) {
			$domain_org = $this->plugin->civicrm->ufmatch->contact_get_by_id( $domain_org_id );
		}

		/**
		 * Fires just before a Domain Organisation is updated.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/civcrm/domain/org_update/pre' );

		try {

			// Build the query to update the Domain.
			$query = \Civi\Api4\Domain::update( false )
				->addWhere( 'id', '=', $domain_id )
				->addValue( 'contact_id', $domain_org_id );

			// Maybe update the Domaon Name.
			if ( $overwrite && ! empty( $domain_org['display_name'] ) ) {
				$query->addValue( 'name', $domain_org['display_name'] );
			}

			// Call the API to update the Domain.
			$result = $query->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'        => __METHOD__,
				'domain_id'     => $domain_id,
				'domain_org_id' => $domain_org_id,
				'error'         => $e->getMessage(),
				'backtrace'     => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
		}

		/**
		 * Fires just after a Domain Organisation has been updated.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/civcrm/domain/org_update/post' );

		// Return on error.
		if ( isset( $log ) ) {
			return false;
		}

		// Return if not exactly one row.
		if ( 1 !== $result->count() ) {
			return false;
		}

		// The first result is what we're after.
		$domain = $result->first();

		// --<
		return $domain;

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the "Group Organisations" for a optional Group ID or Organisation ID.
	 *
	 * @since 1.0.9
	 *
	 * @param int $group_id The ID of the CiviCRM Group.
	 * @param int $contact_id The ID of the CiviCRM Organisation.
	 * @return array|bool $group_org The array of "GroupOrganization" data, empty array if not found, false on error.
	 */
	public function group_orgs_get( $group_id = 0, $contact_id = 0 ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		try {

			// Build common query.
			$query = \Civi\Api4\GroupOrganization::get( false )
				->setLimit( 1 );

			// Maybe add Group ID.
			if ( ! empty( $group_id ) ) {
				$query->addWhere( 'group_id', '=', $group_id );
			}

			// Maybe add Organisation ID.
			if ( ! empty( $contact_id ) ) {
				$query->addWhere( 'organization_id', '=', $contact_id );
			}

			// Call the API.
			$result = $query->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'     => __METHOD__,
				'group_id'   => $group_id,
				'contact_id' => $contact_id,
				'error'      => $e->getMessage(),
				'backtrace'  => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Return if nothing found.
		if ( 0 === $result->count() ) {
			return [];
		}

		// We only need the ArrayObject.
		$group_orgs = $result->getArrayCopy();

		// --<
		return $group_orgs;

	}

	/**
	 * Reassigns all "Group Organisations" for a Domain Group to a new Domain Group.
	 *
	 * @since 1.0.9
	 *
	 * @param array $group_org_ids The array of "Group Organisation" IDs.
	 * @param int   $contact_id The ID of the Domain Organisation.
	 * @return array|bool $group_org The array of "GroupOrganization" data, or false on error.
	 */
	public function group_orgs_contact_update( $group_org_ids, $contact_id ) {

		// Sanity check Group Organisation IDs.
		if ( empty( $group_org_ids ) || ! is_array( $group_org_ids ) ) {
			return false;
		}

		// Sanity check Domain Organisation.
		if ( empty( $contact_id ) || ! is_numeric( $contact_id ) ) {
			return false;
		}

		// Bail if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		/**
		 * Fires just before all "Group Organisations" for a Domain Group are reassigned.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/civcrm/domain/group_orgs_contact_update/pre' );

		try {

			// Call the API.
			$result = \Civi\Api4\GroupOrganization::update( false )
				->addWhere( 'id', 'IN', $group_org_ids )
				->addValue( 'organization_id', $contact_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'          => __METHOD__,
				'group_org_ids'   => $group_org_ids,
				'organization_id' => $contact_id,
				'error'           => $e->getMessage(),
				'backtrace'       => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
		}

		/**
		 * Fires just after all "Group Organisations" for a Domain Group have been reassigned.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/civcrm/domain/group_orgs_contact_update/post' );

		// Return on error.
		if ( isset( $log ) ) {
			return false;
		}

		// Return if not exactly one row.
		if ( 1 !== $result->count() ) {
			return false;
		}

		// The first result is what we're after.
		$group_org = $result->first();

		// --<
		return $group_org;

	}

	/**
	 * Deletes the "Group Organisations" for a set of Group Organisation IDs.
	 *
	 * @since 1.0.9
	 *
	 * @param array $group_org_ids The array of CiviCRM "Group Organisation" IDs.
	 * @return array|bool $group_orgs The IDs of the deleted "GroupOrganizations", or false on error.
	 */
	public function group_orgs_delete( $group_org_ids ) {

		// Sanity check.
		if ( empty( $group_org_ids ) || ! is_array( $group_org_ids ) ) {
			return false;
		}

		// Bail if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		/**
		 * Fires just before the "Group Organisations" for a set of Group Organisation IDs are deleted.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/civcrm/domain/group_orgs_delete/pre' );

		try {

			// Call the API.
			$result = \Civi\Api4\GroupOrganization::delete( false )
				->addWhere( 'id', 'IN', $group_org_ids )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'        => __METHOD__,
				'group_org_ids' => $group_org_ids,
				'error'         => $e->getMessage(),
				'backtrace'     => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
		}

		/**
		 * Fires just after the "Group Organisations" for a set of Group Organisation IDs have been deleted.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/civcrm/domain/group_orgs_delete/post' );

		// Return on error.
		if ( isset( $log ) ) {
			return false;
		}

		// Return failure if no result.
		if ( 0 === $result->count() ) {
			return false;
		}

		// We only need the ArrayObject.
		$group_orgs = $result->getArrayCopy();

		// --<
		return $group_orgs;

	}

	/**
	 * Gets the data for a "Group Organisation" for a given Group ID and Organisation ID.
	 *
	 * @since 1.0.9
	 *
	 * @param int $group_id The ID of the CiviCRM Group.
	 * @param int $contact_id The ID of the CiviCRM Organisation.
	 * @return array|bool $group_org The array of "GroupOrganization" data, empty array if not found, false on error.
	 */
	public function group_org_get( $group_id = 0, $contact_id = 0 ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		try {

			// Build common query.
			$query = \Civi\Api4\GroupOrganization::get( false )
				->setLimit( 1 );

			// Maybe add Group ID.
			if ( ! empty( $group_id ) ) {
				$query->addWhere( 'group_id', '=', $group_id );
			}

			// Maybe add Organisation ID.
			if ( ! empty( $contact_id ) ) {
				$query->addWhere( 'organization_id', '=', $contact_id );
			}

			// Call the API.
			$result = $query->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'     => __METHOD__,
				'group_id'   => $group_id,
				'contact_id' => $contact_id,
				'error'      => $e->getMessage(),
				'backtrace'  => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Return if nothing found.
		if ( 0 === $result->count() ) {
			return [];
		}

		// The first result is what we're after.
		$group_org = $result->first();

		// --<
		return $group_org;

	}

	/**
	 * Creates a "Group Organisation" for a given Group ID and Organisation ID.
	 *
	 * @since 1.0.9
	 *
	 * @param int $group_id The ID of the CiviCRM Group.
	 * @param int $contact_id The ID of the CiviCRM Organisation.
	 * @return array|bool $group_org The array of "GroupOrganization" data, empty array if not found, false on error.
	 */
	public function group_org_create( $group_id, $contact_id ) {

		// Sanity checks.
		if ( empty( $group_id ) || ! is_numeric( $group_id ) ) {
			return false;
		}
		if ( empty( $contact_id ) || ! is_numeric( $contact_id ) ) {
			return false;
		}

		// Bail if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		/**
		 * Fires just before a "Group Organisation" is created.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/civcrm/domain/group_org_create/pre' );

		try {

			// Call the API.
			$result = \Civi\Api4\GroupOrganization::create( false )
				->addValue( 'group_id', $group_id )
				->addValue( 'organization_id', $contact_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'     => __METHOD__,
				'group_id'   => $group_id,
				'contact_id' => $contact_id,
				'error'      => $e->getMessage(),
				'backtrace'  => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
		}

		/**
		 * Fires just after a "Group Organisation" has been created.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/civcrm/domain/group_org_create/post' );

		// Return on error.
		if ( isset( $log ) ) {
			return false;
		}

		// Return if nothing found.
		if ( 0 === $result->count() ) {
			return false;
		}

		// The first result is what we're after.
		$group_org = $result->first();

		// --<
		return $group_org;

	}

	/**
	 * Updates a "Group Organisation" for a given ID.
	 *
	 * @since 1.0.9
	 *
	 * @param int $group_org_id The ID of the CiviCRM "Group Organisation".
	 * @param int $group_id The ID of the CiviCRM Group.
	 * @param int $contact_id The ID of the CiviCRM Organisation.
	 * @return array|bool $group_org The array of "GroupOrganization" data, empty array if not found, false on error.
	 */
	public function group_org_update( $group_org_id, $group_id, $contact_id ) {

		// Sanity checks.
		if ( empty( $group_org_id ) || ! is_numeric( $group_org_id ) ) {
			return false;
		}
		if ( empty( $group_id ) || ! is_numeric( $group_id ) ) {
			return false;
		}
		if ( empty( $contact_id ) || ! is_numeric( $contact_id ) ) {
			return false;
		}

		// Bail if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		/**
		 * Fires just before a "Group Organisation" is updated.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/civcrm/domain/group_org_update/pre' );

		try {

			// Call the API.
			$result = \Civi\Api4\GroupOrganization::update( false )
				->addWhere( 'id', '=', $group_org_id )
				->addValue( 'group_id', $group_id )
				->addValue( 'organization_id', $contact_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'     => __METHOD__,
				'group_id'   => $group_id,
				'contact_id' => $contact_id,
				'error'      => $e->getMessage(),
				'backtrace'  => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
		}

		/**
		 * Fires just after a "Group Organisation" has been updated.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/civcrm/domain/group_org_update/post' );

		// Return on error.
		if ( isset( $log ) ) {
			return false;
		}

		// Return if not exactly one row.
		if ( 1 !== $result->count() ) {
			return false;
		}

		// The first result is what we're after.
		$group_org = $result->first();

		// --<
		return $group_org;

	}

	/**
	 * Deletes the "Group Organisation" for a given Group Organisation ID.
	 *
	 * @since 1.0.9
	 *
	 * @param int $group_org_id The ID of the CiviCRM "Group Organisation".
	 * @return array|bool $group_org_id The ID of the deleted "GroupOrganization", or false on error.
	 */
	public function group_org_delete( $group_org_id ) {

		// Sanity check.
		if ( empty( $group_org_id ) || ! is_numeric( $group_org_id ) ) {
			return false;
		}

		// Bail if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		/**
		 * Fires just before a "Group Organisation" is deleted.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/civcrm/domain/group_org_delete/pre' );

		try {

			// Call the API.
			$result = \Civi\Api4\GroupOrganization::delete( false )
				->addWhere( 'id', '=', $group_org_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'       => __METHOD__,
				'group_org_id' => $group_org_id,
				'error'        => $e->getMessage(),
				'backtrace'    => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
		}

		/**
		 * Fires just after a "Group Organisation" has been deleted.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/civcrm/domain/group_org_delete/post' );

		// Return on error.
		if ( isset( $log ) ) {
			return false;
		}

		// Return failure if no result.
		if ( 0 === $result->count() ) {
			return false;
		}

		// The first result is what we're after.
		$group_org = $result->first();

		// Cast as boolean.
		$group_org_id = (int) $group_org['id'];

		// --<
		return $group_org_id;

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets all the Contact IDs in a Domain Group.
	 *
	 * @since 1.0.9
	 *
	 * @param int $domain_group_id The ID of the Domain Group.
	 * @return array|bool $group_contact_ids The array of Contact IDs in the Domain Group.
	 */
	public function group_contact_ids_get( $domain_group_id ) {

		// Sanity check.
		if ( empty( $domain_group_id ) ) {
			return false;
		}

		// Bail if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		try {

			// Call the API.
			$result = \Civi\Api4\GroupContact::get( false )
				->addSelect( 'contact_id' )
				->addWhere( 'group_id', '=', $domain_group_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Return if nothing found.
		if ( 0 === $result->count() ) {
			return [];
		}

		// We only need the ArrayObject.
		$group_contact_ids = $result->getArrayCopy();

		// --<
		return $group_contact_ids;

	}

	/**
	 * Assigns a Contact to a Domain Group.
	 *
	 * @since 1.0.9
	 *
	 * @param int $domain_group_id The ID of the Domain Group.
	 * @param int $contact_id The ID of the CiviCRM Contact.
	 * @return array|bool $group_contact The array of Group Contact data, or false on failure.
	 */
	public function group_contact_create( $domain_group_id, $contact_id ) {

		// Sanity check.
		if ( empty( $domain_group_id ) || empty( $contact_id ) ) {
			return false;
		}

		// Bail if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		/**
		 * Fires just before a Contact is assigned to a Domain Group.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/civcrm/domain/group_contact_create/pre' );

		try {

			// Call the API.
			$result = \Civi\Api4\GroupContact::create( false )
				->addValue( 'group_id', '=', $domain_group_id )
				->addValue( 'contact_id', '=', $contact_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
		}

		/**
		 * Fires just after a Contact has been assigned to a Domain Group.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/civcrm/domain/group_contact_create/post' );

		// Return on error.
		if ( isset( $log ) ) {
			return false;
		}

		// Return failure if no result.
		if ( 0 === $result->count() ) {
			return false;
		}

		// Return if nothing found.
		if ( 0 === $result->count() ) {
			return false;
		}

		// The first result is what we're after.
		$group_contact = $result->first();

		// --<
		return $group_contact;

	}

	/**
	 * Reassigns all Contacts in a Domain Group to a new Domain Group.
	 *
	 * @since 1.0.9
	 *
	 * @param int $old_id The ID of the existing Domain Group.
	 * @param int $new_id The ID of the new Domain Group.
	 */
	public function group_contacts_update( $old_id, $new_id ) {

		// Bail if there is no old Domain Group.
		if ( empty( $old_id ) || ! is_numeric( $old_id ) ) {
			return false;
		}

		// Bail if there is no new Domain Group.
		if ( empty( $new_id ) || ! is_numeric( $new_id ) ) {
			return false;
		}

		// Bail if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Get all Contact IDs in the previous Domain Group.
		$group_contact_ids = $this->group_contact_ids_get( $old_id );

		// Bail if there are none.
		if ( empty( $group_contact_ids ) ) {
			return [];
		}

		// Get just the Contact IDs.
		$contact_ids = wp_list_pluck( $group_contact_ids, 'contact_id' );

		/**
		 * Fires just beforee all Contacts in a Domain Group are reassigned.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/civcrm/domain/group_contacts_update/pre' );

		try {

			// Call the API.
			$result = \Civi\Api4\GroupContact::update( false )
				->addWhere( 'group_id', '=', $old_id )
				->addWhere( 'contact_id', 'IN', $contact_ids )
				->addValue( 'group_id', $new_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'old_id'    => $old_id,
				'new_id'    => $new_id,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
		}

		/**
		 * Fires just after all Contacts in a Domain Group have been reassigned.
		 *
		 * @since 1.0.9
		 */
		do_action( 'cau/civcrm/domain/group_contacts_update/post' );

		// Return on error.
		if ( isset( $log ) ) {
			return false;
		}

		// Return if nothing found.
		if ( 0 === $result->count() ) {
			return false;
		}

		// We only need the ArrayObject.
		$group_contacts = $result->getArrayCopy();

		// --<
		return $group_contacts;

	}

}
