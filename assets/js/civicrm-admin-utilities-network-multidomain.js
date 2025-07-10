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
		var sites = $('.cau_site_id_select');

		/**
		 * WordPress Site Select2 init.
		 *
		 * @since 1.0.9
		 */
		sites.select2({
			data: CAU_Network_Domain.settings.sites,
			placeholder: CAU_Network_Domain.localisation.placeholder,
			allowClear: true
		});

		/**
		 * Remove options for used Sites.
		 *
		 * @since 1.0.9
		 */
		sites.each( function(i) {

			var selected = $(this).find(':selected').val(),
				select = $(this),
				option;

			// Remove each used Site in turn.
			if ( CAU_Network_Domain.settings.used.length ) {
				$.each( CAU_Network_Domain.settings.used, function( index, value ) {
					option = select.find( "option[value='" + value + "']" );
					if ( option.length ) {
						if ( value != selected ) {
							CAU_Network_Domain.settings.options[ option.val() ] = option;
							option.detach();
						}
					}
				});
			}

		});

		/**
		 * WordPress Site Select2 change handler.
		 *
		 * @since 1.0.9
		 *
		 * @param {Object} event The Select2 event object.
		 */
		sites.on( 'select2:selecting', function ( event ) {

			// Sanity check.
			if ( ! ( event instanceof $.Event ) ) {
				return;
			}

			// Save previous value for later use.
			CAU_Network_Domain.settings.bridge = $(this).val();

		});

		/**
		 * WordPress Site Select2 change handler.
		 *
		 * @since 1.0.9
		 *
		 * @param {Object} event The Select2 event object.
		 */
		sites.on( 'select2:select', function ( event ) {

			// Sanity check.
			if ( ! ( event instanceof $.Event ) ) {
				return;
			}

			var current, current_val, previous_val, previous = false, selected;

			current = $(this);
			current_val = $(this).val();
			previous_val = CAU_Network_Domain.settings.bridge;
			if ( CAU_Network_Domain.settings.options.length ) {
				previous = CAU_Network_Domain.settings.options[ previous_val ];
			}

			sites.each( function(i) {

				// Remove current from all but this.
				selected = $(this).find(':selected').val()
				if ( current_val != selected ) {
					option = $(this).find( "option[value='" + current_val + "']" );
					if ( option.length ) {
						CAU_Network_Domain.settings.options[ option.val() ] = option;
						option.detach();
					}
				}

				// Make previous available to all that don't have it.
				option = $(this).find( "option[value='" + previous_val + "']" );
				if ( ! option.length ) {
					$(this).append( previous ).trigger( 'change' );
				}

			});

			// Reset bridging variable.
			CAU_Network_Domain.settings.bridge = 0;

		});

	});

} )( jQuery );
