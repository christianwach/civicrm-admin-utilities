<!-- assets/templates/metaboxes/network-metabox-domain-info.php -->
<?php if ( ! $metabox['args']['multisite'] ) : ?>
	<div class="updated error inline" style="background-color: #f7f7f7;">
		<p><?php _e( 'It is recommended that you install and activate the <a href="https://civicrm.org/extensions/multisite-permissioning" target="_blank">CiviCRM Multisite</a> extension to work with multiple Domains in CiviCRM.', 'civicrm-admin-utilities' ); ?></p>
	</div>
<?php endif; ?>

<?php if ( ! empty( $metabox['args']['domains'] ) ) : ?>
	<ul>
	<?php foreach( $metabox['args']['domains'] AS $domain ) : ?>
		<li><?php echo sprintf(
			__( '"%1$s" (ID: %2$s)', 'civicrm-admin-utilities' ),
			'<span class="cau_domain_name">' . $domain['name'] . '</span>',
			'<span class="cau_domain_id">' . $domain['id'] . '</span>'
		); ?></li>
	<?php endforeach; ?>
	</ul>
<?php endif; ?>
