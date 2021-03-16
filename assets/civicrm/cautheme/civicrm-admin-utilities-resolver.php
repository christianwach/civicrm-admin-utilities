<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * CiviCRM Admin Utilities Theme class.
 *
 * A class for resolving resource "CiviCRM Admin Utilities" Theme.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.7.4
 */
class CiviCRM_Admin_Utilities_Resolver {

  /**
   * Define the base resource for this Theme.
   *
   * @param \Civi\Core\Themes $themes
   *   The theming subsystem.
   * @param string $themeKey
   *   The active/desired Theme key.
   * @param string $cssExt
   *   The extension for which we want a themed CSS file (e.g. "civicrm").
   * @param string $cssFile
   *   File name (e.g. "css/bootstrap.css").
   * @return array|string
   *   List of CSS URLs, or PASSTHRU.
   */
  public static function resolve($themes, $themeKey, $cssExt, $cssFile) {

		// For the Core Theme, use existing logic.
		if ( $cssExt != 'cautheme' ) {

      // Get the Theme data.
      $theme = $themes->get( 'greenwich' );

      $res = Civi::resources();

      $file = '';
      if ( isset($theme['prefix'] ) ) {
        $file .= $theme['prefix'];
      }

      $file .= $themes->cssId( $theme['ext'], $cssFile );

      $file = $res->filterMinify( $theme['ext'], $file );

      // Return file URL if found.
      if ( $res->getPath( $theme['ext'], $file ) ) {
        return [ $res->getUrl( $theme['ext'], $file, TRUE ) ];
      }

      // Return fallback.
      return Civi\Core\Themes::PASSTHRU;

    }

    /*
     * Build our own paths and URLs here.
     *
     * At present, we don't actually need any because the plugin enqueues the
     * stylesheet(s) using the WordPress `wp_enqueue_style()` method.
     *
     * In future, it may be useful to migrate to CiviCRM's system.
     */

    //$url = [ CIVICRM_ADMIN_UTILITIES_URL . 'assets/civicrm/cautheme/' . $cssFile ];
    //return $url;

  }

}
