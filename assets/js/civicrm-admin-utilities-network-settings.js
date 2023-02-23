/**
 * Javascript for CiviCRM Admin Utilities Network Settings page.
 *
 * Implements visibility toggles on the plugin's Network Settings page.
 *
 * @package CiviCRM_Admin_Utilities
 */

/**
 * Pass the jQuery shortcut in.
 *
 * @since 0.5.4
 *
 * @param {Object} $ The jQuery object.
 */
( function( $ ) {

	/**
	 * Act on document ready.
	 *
	 * @since 0.5.4
	 */
	$(document).ready( function() {

		/**
		 * Toggle visibility of sections dependent on whether CiviCRM is
		 * restricted to the main site or not.
		 *
		 * @since 0.5.4
		 *
		 * @param {Object} e The click event object
		 */
		$('#civicrm_admin_utilities_main_site').click( function(e) {

			var current_on;

			// Detect checked.
			current_on = $(this).prop( 'checked' );

			// Toggle.
			if ( current_on ) {
				$('.civicrm-restricted').slideUp( 'slow' );
			} else {
				$('.civicrm-restricted').slideDown( 'slow' );
			}

		});

		/**
		 * Toggle visibility of sections dependent on whether access to the
		 * Settings Pages is restricted or not.
		 *
		 * @since 0.5.4
		 *
		 * @param {Object} e The click event object
		 */
		$('#civicrm_admin_utilities_restrict_settings_access').click( function(e) {

			var current_on;

			// Detect checked.
			current_on = $(this).prop( 'checked' );

			// Toggle.
			if ( current_on ) {
				$('.settings-restricted').slideUp( 'slow' );
			} else {
				$('.settings-restricted').slideDown( 'slow' );
			}

		});

	});

} )( jQuery );
