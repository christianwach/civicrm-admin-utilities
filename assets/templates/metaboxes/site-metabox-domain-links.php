<?php
/**
 * Site Settings Domain tab "Useful Links" metabox Template.
 *
 * Handles markup for the Site Settings Domain tab "Useful Links" metabox.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 0.8.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/metaboxes/site-metabox-domain-submit.php -->
<ul>
	<?php /* phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
	<li><a href="<?php echo $metabox['args']['domain_url']; ?>"><?php esc_html_e( 'Multi Site Settings', 'civicrm-admin-utilities' ); ?></a></li>
	<li><a href="<?php echo $metabox['args']['domain_org_url']; ?>"><?php esc_html_e( 'Domain Organization Settings', 'civicrm-admin-utilities' ); ?></a></li>
	<?php if ( ! empty( $metabox['args']['domain_group_url'] ) ) : ?>
		<li><a href="<?php echo $metabox['args']['domain_group_url']; ?>"><?php esc_html_e( 'Domain Group Settings', 'civicrm-admin-utilities' ); ?></a></li>
	<?php endif; ?>
	<?php /* phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
</ul>
