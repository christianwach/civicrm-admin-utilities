<?php
/**
 * Theme Resolver Class.
 *
 * Handles resolving resources for the "Wellow Brook" Theme.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 1.1.2
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Wellow Brook Theme Resolver class.
 *
 * A class for resolving resources for the "Wellow Brook" Theme.
 *
 * @see Civi\Core\Themes\Resolvers
 *
 * @package CiviCRM_Admin_Utilities
 * @since 1.1.2
 */
class CAU_CiviCRM_Theme_Resolver {

	/**
	 * Define the base resource for this Theme.
	 *
	 * @since 1.1.2
	 *
	 * @param \Civi\Core\Themes $themes The theming subsystem.
	 * @param string            $theme_key The active/desired Theme key.
	 * @param string            $css_ext The extension for which we want a themed CSS file (e.g. "civicrm").
	 * @param string            $css_file File name (e.g. "css/bootstrap.css").
	 * @return array|string List of CSS URLs, or PASSTHRU.
	 */
	public static function resolve( $themes, $theme_key, $css_ext, $css_file ) {

		// Use existing logic when not RiverLea.
		if ( 'riverlea' !== $css_ext ) {

			// Use RiverLea Core.
			$theme = $themes->get( '_riverlea_core_' );

			$res = \Civi::resources();

			$file = '';
			if ( isset( $theme['prefix'] ) ) {
				$file .= $theme['prefix'];
			}

			$file .= $themes->cssId( $theme['ext'], $css_file );

			$file = $res->filterMinify( $theme['ext'], $file );

			// Return file URL if found.
			if ( $res->getPath( $theme['ext'], $file ) ) {
				return [ $res->getUrl( $theme['ext'], $file, true ) ];
			}

			// Return fallback.
			return \Civi\Core\Themes::PASSTHRU;

		}

		// Return fallback.
		return \Civi\Core\Themes::PASSTHRU;

	}

}
