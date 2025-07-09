/**
 * Javascript for CiviCRM Admin Utilities Network Domain page.
 *
 * Implements functionality on the plugin's Network Domain page.
 *
 * @package CiviCRM_Admin_Utilities
 */

/**
 * Pass the jQuery shortcut in.
 *
 * @since 1.0.9
 *
 * @param {Object} $ The jQuery object.
 */
( function( $ ) {

	/**
	 * Act on document ready.
	 *
	 * @since 1.0.9
	 */
	$(document).ready( function() {

		// Declare vars.
		var site = $('.cau_site_id_select');

		console.log( 'sites', CAU_Network_Domain.settings.data );

		/**
		 * WordPress Site Select2 init.
		 *
		 * @since 1.0.9
		 */
		site.select2({

			data: CAU_Network_Domain.settings.data,
			placeholder: CAU_Network_Domain.localisation.placeholder,
			allowClear: true

		});

	});

	/**
	 * Select2 format results for display in dropdown.
	 *
	 * @since 1.0.9
	 *
	 * @param {Object} data The results data.
	 * @return {String} markup The results markup.
	 */
	function format_result( data ) {

		// Bail if still loading.
		if ( data.loading ) {
			return data.name;
		}

		// Declare vars.
		var markup;

		// Construct basic info.
		markup = '<div style="clear:both;">' +
				'<div class="select2_results_name">' +
					'<span style="font-weight:600;">' + data.name + '</span></em>' +
				'</div>';

		// Add description, if available.
		if (data.description) {
			markup += '<div class="select2_results_description" style="font-size:.9em;line-height:1.4;">'
						+ data.description +
					'</div>';
		}

		// Close markup.
		markup += '</div>';

		// --<
		return markup;

	}

	/**
	 * Select2 format response.
	 *
	 * @since 1.0.9
	 *
	 * @param {Object} data The results data.
	 * @return {String} The expected response.
	 */
	function format_response( data ) {
		return data.name || data.text;
	}

} )( jQuery );
