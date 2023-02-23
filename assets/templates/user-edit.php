<?php
/**
 * User Edit Table Row Template.
 *
 * Handles markup for the User Edit Table Row.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.6.3
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/user-edit.php -->
<tr>
	<th scope="row"><?php echo esc_html_e( 'CiviCRM Contact', 'civicrm-admin-utilities' ); ?></th>
	<td><a href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'View Contact in CiviCRM', 'civicrm-admin-utilities' ); ?></a></td>
</tr>
