/**
 * Javascript for CiviCRM Admin Utilities Site Settings page.
 *
 * Implements visibility toggles on the plugin's Network Settings page.
 *
 * @package CiviCRM_Admin_Utilities
 */



/**
 * Pass the jQuery shortcut in.
 *
 * @since 0.7
 *
 * @param {Object} $ The jQuery object.
 */
( function( $ ) {

	/**
	 * Act on document ready.
	 *
	 * @since 0.7
	 */
	$(document).ready( function() {

		// Init 2020 on theme preview images.
		$( '#theme-compare-dashboard' ).twentytwenty({
			default_offset_pct: 0.8
		});

   	});

} )( jQuery );

