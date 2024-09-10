<?php
/**
 * Stepper class.
 *
 * Handles "stepping" or "chunking" functionality.
 *
 * @package CiviCRM_Admin_Utilities
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Stepper class.
 *
 * A class that encapsulates "stepping" or "chunking" functionality.
 *
 * @since 1.0.2
 */
class CAU_Admin_Stepper {

	/**
	 * Stepper identifier.
	 *
	 * @since 1.0.2
	 * @access public
	 * @var string
	 */
	public $identifier = '';

	/**
	 * Stepper option name.
	 *
	 * @since 1.0.2
	 * @access public
	 * @var string
	 */
	private $option_name = '';

	/**
	 * Stepper option suffix.
	 *
	 * @since 1.0.2
	 * @access public
	 * @var string
	 */
	private $option_suffix = '_stepper_offset';

	/**
	 * How many items to process per step.
	 *
	 * @since 1.0.2
	 * @access public
	 * @var array
	 */
	private $step_count = 25;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.2
	 *
	 * @param string $identifier The unique identifier for the stepper.
	 */
	public function __construct( $identifier ) {

		// Save properties.
		$this->identifier  = $identifier;
		$this->option_name = $identifier . $this->option_suffix;

	}

	/**
	 * Checks if the stepper option exists.
	 *
	 * When it does, it means that there is a stepping operation in progress.
	 *
	 * @since 1.0.2
	 *
	 * @return bool $exists True if the offset exists, false otherwise.
	 */
	public function exists() {

		// Test for an impossible value.
		if ( 'fgffgs' === get_option( $this->option_name, 'fgffgs' ) ) {
			return false;
		}

		// Exists.
		return true;

	}

	/**
	 * Initialises the stepper.
	 *
	 * @since 1.0.2
	 *
	 * @return integer $offset The offset of the stepper.
	 */
	public function initialise() {

		// Start at the beginning if the offset doesn't exist.
		if ( ! $this->exists() ) {
			$offset = 0;
			$this->update( $offset );
		} else {
			$offset = $this->get();
		}

		// --<
		return $offset;

	}

	/**
	 * Sets the next value of the stepper.
	 *
	 * @since 1.0.2
	 *
	 * @return integer $next The next value for the stepper.
	 */
	public function next() {

		// Get the next value of the stepper.
		$next = $this->next_get();

		// Increment offset option.
		$this->update( $next );

		// --<
		return $next;

	}

	/**
	 * Gets the next value of the stepper.
	 *
	 * @since 1.0.2
	 *
	 * @return integer $next The next value for the stepper.
	 */
	public function next_get() {

		// Get the current offset.
		$offset = $this->get();

		// Initialise if none exists.
		if ( false === $offset ) {
			$offset = $this->initialise();
		}

		// Increment by the step count.
		$next = $offset + $this->step_count_get();

		// --<
		return $next;

	}

	/**
	 * Gets the current value of the stepper.
	 *
	 * @since 1.0.2
	 *
	 * @return integer|bool $offset The offset of the stepper, or false if it does not exist.
	 */
	public function get() {

		// Test for an impossible value.
		if ( ! $this->exists() ) {
			return false;
		}

		// Get the current offset value.
		$offset = (int) get_option( $this->option_name, '0' );

		// --<
		return $offset;

	}

	/**
	 * Sets the value of the stepper.
	 *
	 * The value is stored as a string to disambiguate "0" and "false".
	 *
	 * @since 1.0.2
	 *
	 * @param integer $to The numeric value for the stepper.
	 */
	public function update( $to ) {

		// Set the offset option.
		update_option( $this->option_name, (string) $to );

	}

	/**
	 * Delete the stepper.
	 *
	 * @since 1.0.2
	 */
	public function delete() {

		// Delete the option to start from the beginning.
		delete_option( $this->option_name );

	}

	// -------------------------------------------------------------------------

	/**
	 * Sets the step count.
	 *
	 * @since 1.0.2
	 *
	 * @param integer $step_count The step count.
	 */
	public function step_count_set( $step_count ) {
		$this->step_count = (int) $step_count;
	}

	/**
	 * Gets the current step count.
	 *
	 * @since 1.0.2
	 *
	 * @return integer $step_count The step count.
	 */
	public function step_count_get() {
		return $this->step_count;
	}

}
