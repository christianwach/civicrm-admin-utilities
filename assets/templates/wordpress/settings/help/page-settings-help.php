<?php
/**
 * Settings Help template.
 *
 * Handles markup for Settings Help.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 1.0.2
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- <?php echo esc_html( $this->path_template . $this->path_help ); ?>page-settings-help.php -->
<p><?php esc_html_e( 'Settings: For further information about using this plugin, please refer to the readme file that is supplied with it.', 'civicrm-admin-utilities' ); ?></p>
