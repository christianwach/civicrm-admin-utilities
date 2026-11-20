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

		// Define vars.
		var checkbox = $('#civicrm_admin_utilities_styles_admin'),
			theme_preview = $('#theme-compare-dashboard'),
			afforms_select = $('#civicrm_admin_utilities_afforms');

		// Init 2020 only when theme is not selected.
		if ( ! checkbox.prop( 'checked' ) ) {

			// Show the theme preview.
			theme_preview.show();

			// Init 2020 on theme preview images.
			theme_preview.twentytwenty({
				default_offset_pct: 0.8
			});

		}

		// Enable Select2.
		afforms_select.select2();

   	});

} )( jQuery );
