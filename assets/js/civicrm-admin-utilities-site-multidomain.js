/**
 * Javascript for CiviCRM Admin Utilities Site Domain page.
 *
 * Implements functionality on the plugin's Site Domain page.
 *
 * @package CiviCRM_Admin_Utilities
 */

/**
 * Pass the jQuery shortcut in.
 *
 * @since 0.6.2
 *
 * @param {Object} $ The jQuery object.
 */
( function( $ ) {

	/**
	 * Act on document ready.
	 *
	 * @since 0.6.2
	 */
	$(document).ready( function() {

		// Declare vars.
		var group = $('#cau_domain_group_select'),
			org = $('#cau_domain_org_select');

		/**
		 * Domain Group Select2 init.
		 *
		 * @since 0.6.2
		 */
		group.select2({

			// AJAX action.
			ajax: {
				method: 'POST',
				url: CAU_Site_Domain.settings.ajaxurl,
				dataType: 'json',
				delay: 250,
				data: function( params ) {
					return {
						s: params.term, // Search term.
						action: 'cau_domain_groups_get',
						page: params.page,
						blog_id: CAU_Site_Domain.settings.blog_id,
						_ajax_nonce: group.data( 'security' )
					};
				},
				processResults: function( data, page ) {
					// Parse the results into the format expected by Select2.
					// Since we are using custom formatting functions, we do not
					// need to alter the remote JSON data.
					return {
						results: data
					};
				},
				cache: true
			},

			// Settings.
			escapeMarkup: function( markup ) {
				// Let our custom formatter do the work.
				return markup;
			},
			minimumInputLength: 3,
			templateResult: format_result,
			templateSelection: format_response

		});

		/**
		 * Domain Org Select2 init.
		 *
		 * @since 0.6.2
		 */
		org.select2({

			// AJAX action.
			ajax: {
				method: 'POST',
				url: CAU_Site_Domain.settings.ajaxurl,
				dataType: 'json',
				delay: 250,
				data: function( params ) {
					return {
						s: params.term, // Search term.
						action: 'cau_domain_orgs_get',
						page: params.page,
						blog_id: CAU_Site_Domain.settings.blog_id,
						_ajax_nonce: org.data( 'security' )
					};
				},
				processResults: function( data, page ) {
					// Parse the results into the format expected by Select2.
					// Since we are using custom formatting functions, we do not
					// need to alter the remote JSON data.
					return {
						results: data
					};
				},
				cache: true
			},

			// Settings.
			escapeMarkup: function( markup ) {
				// Let our custom formatter do the work.
				return markup;
			},
			minimumInputLength: 3,
			templateResult: format_result,
			templateSelection: format_response

		});

	});

	/**
	 * Select2 format results for display in dropdown.
	 *
	 * @since 0.6.2
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

		// Construct basic group info.
		markup = '<div style="clear:both;">' +
				'<div class="select2_results_name">' +
					'<span style="font-weight:600;">' + data.name + '</span></em>' +
				'</div>';

		// Add group description, if available.
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
	 * @since 0.6.2
	 *
	 * @param {Object} data The results data.
	 * @return {String} The expected response.
	 */
	function format_response( data ) {
		return data.name || data.text;
	}

} )( jQuery );
