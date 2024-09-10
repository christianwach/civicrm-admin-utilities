<?php
/**
 * Batch class.
 *
 * Handles "batches" of "stepping" functionality.
 *
 * @package CiviCRM_Admin_Utilities
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Batch class.
 *
 * A class that encapsulates "batches" of "stepping" functionality.
 *
 * @since 1.0.2
 */
class CAU_Admin_Batch {

	/**
	 * Batch identifier.
	 *
	 * @since 1.0.2
	 * @access public
	 * @var string
	 */
	public $identifier = '';

	/**
	 * Batch option name.
	 *
	 * @since 1.0.2
	 * @access public
	 * @var string
	 */
	private $option_name = '';

	/**
	 * Batch option suffix.
	 *
	 * @since 1.0.2
	 * @access public
	 * @var string
	 */
	private $option_suffix = '_batch_offset';

	/**
	 * Current stepper object.
	 *
	 * @since 1.0.2
	 * @access public
	 * @var CAU_Admin_Stepper
	 */
	public $stepper;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.2
	 *
	 * @param string $identifier The unique identifier for the batch.
	 */
	public function __construct( $identifier ) {

		// Save properties.
		$this->identifier  = $identifier;
		$this->option_name = $identifier . $this->option_suffix;

		// Instantiate the stepper.
		$this->stepper = new CAU_Admin_Stepper( $identifier );

	}

	/**
	 * Checks if the batch option exists.
	 *
	 * When it does, it means that there is a batch operation in progress.
	 *
	 * @since 1.0.2
	 *
	 * @return bool $exists True if the option exists, false otherwise.
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
	 * Initialises the batch process.
	 *
	 * The batch offset is zero indexed, so the first batch is numeric 0.
	 *
	 * @since 1.0.2
	 *
	 * @return integer $offset The offset of the batch.
	 */
	public function initialise() {

		// Start at the beginning if the offset doesn't exist.
		if ( ! $this->exists() ) {
			$offset = 0;
			$this->update( $offset );
		} else {
			$offset = $this->get();
		}

		// Initialise the stepper.
		$this->stepper->initialise();

		// --<
		return $offset;

	}

	/**
	 * Sets the next offset for the batch.
	 *
	 * @since 1.0.2
	 *
	 * @return integer $next The next offset for the batch.
	 */
	public function next() {

		// Get the current offset.
		$offset = $this->get();

		// Initialise if none exists.
		if ( false === $offset ) {
			$offset = $this->initialise();
		}

		// Delete the expired stepper.
		$this->stepper->delete();

		// Move on to next batch.
		$next = $offset + 1;

		// Increment offset option.
		$this->update( $next );

		// Re-initialise.
		$this->initialise();

		// --<
		return $next;

	}

	/**
	 * Gets the current batch offset.
	 *
	 * @since 1.0.2
	 *
	 * @return integer|bool $offset The offset of the batch, or false if it does not exist.
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
	 * Sets the batch offset.
	 *
	 * The value is stored as a string to disambiguate "0" and "false".
	 *
	 * @since 1.0.2
	 *
	 * @param integer $to The numeric value for the batch.
	 */
	private function update( $to ) {

		// Set the offset option.
		update_option( $this->option_name, (string) $to );

	}

	/**
	 * Delete the batch.
	 *
	 * @since 1.0.2
	 */
	public function delete() {

		// Get the current offset.
		$offset = $this->get();

		// Delete the stepper.
		if ( ! isset( $this->stepper ) ) {
			$this->stepper = new CAU_Admin_Stepper( $this->identifier );
		}
		$this->stepper->delete();
		unset( $this->stepper );

		// Delete the option to start from the beginning.
		delete_option( $this->option_name );

	}

}
