<?php
/**
 * Site Settings "Multidomain" Help template.
 *
 * Handles markup for Site Settings "Multidomain" Help.
 *
 * @package CiviCRM_Admin_Utilities
 * @since 1.0.9
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/wordpress/settings/help/page-network-multidomain-help.php -->
<p>
	<?php

	printf(
		/* translators: 1: Opening anchor tag, 2: Closing anchor tag. */
		esc_html__( 'For detailed instructions on how to set up multiple CiviCRM Domains on WordPress Multisite, please refer to %1$sthe installation guide%2$s in the CiviCRM documentation.', 'civicrm-admin-utilities' ),
		'<a href="' . esc_url( 'https://docs.civicrm.org/installation/en/latest/multisite/wordpress/' ) . '" target="_blank">',
		'</a>'
	);

	?>
</p>
<p>
	<?php

	printf(
		/* translators: 1: Opening code tag, 2: Closing code tag. */
		esc_html__( 'If this is a new install, you can skip the instruction to "add %1$scivicrm.domains.php%2$s to same directory as %1$scivicrm.settings.php%2$s" because CiviCRM Admin Utilities will now handle this for you. It is also not necessary to edit your %1$scivicrm.settings.php%2$s file. Happy days.', 'civicrm-admin-utilities' ),
		'<code>',
		'</code>'
	);

	?>
</p>
