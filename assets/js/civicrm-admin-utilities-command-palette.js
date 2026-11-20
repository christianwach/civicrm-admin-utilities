/**
 * Adapt CiviCRM's prepared navigation tree to native WordPress commands.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 1.1.3
 */

/* global cauCommandPalette, wp */
wp.domReady( async function () {

	try {

		const response = await fetch( cauCommandPalette.url, {
			credentials: 'same-origin',
			// CiviCRM requires this header for AJAX requests.
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		} );

		if ( ! response.ok ) {
			throw new Error( 'Menu request returned ' + response.status );
		}

		const data = await response.json();
		if ( ! Array.isArray( data.menu ) ) {
			throw new Error( 'Invalid menu response' );
		}

		const { registerCommand } = wp.data.dispatch( wp.commands.store );

		// Avoid listing the same destination more than once.
		const destinations = new Set();

		/**
		 * Walk nested menus and register each link with its full menu path.
		 *
		 * @since 1.1.3
		 *
		 * @param {Array} items The array of menu items.
		 * @param {Array} parents The array of labels.
		 */
		function registerItems( items, parents ) {

			items.forEach( function ( item ) {

				const labels = item.label ? parents.concat( item.label ) : parents;

				// Menu actions such as logout and JavaScript callbacks are not navigation commands.
				if ( item.url && ! item.url.startsWith( '#' ) && ! item.attr?.onclick ) {

					// Core menu URLs are escaped for HTML links; navigation needs plain URL text.
					const url = new URL( wp.htmlEntities.decodeEntities( item.url ), cauCommandPalette.url );
					if ( [ 'http:', 'https:' ].includes( url.protocol ) && ! destinations.has( url.href ) && item.name !== 'Log out' ) {
						destinations.add( url.href );
						registerCommand( {
							name: 'cau/navigation/' + encodeURIComponent( url.href ),
							label: labels.join( ' > ' ),
							category: 'view',
							callback: function ( { close } ) {
								close();
								window.location.assign( url.href );
							}
						} );
					}

				}

				if ( Array.isArray( item.child ) ) {
					registerItems( item.child, labels );
				}

			} );
		}

		// Trigger registration.
		registerItems( data.menu, [ 'CiviCRM' ] );

	} catch ( error ) {

		// Leave WordPress's own commands available if CiviCRM cannot respond.
		console.warn( 'CiviCRM menu search could not load.', error );

	}

} );
