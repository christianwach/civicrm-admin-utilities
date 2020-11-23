<!-- assets/templates/metaboxes/network-metabox-domain-info.php -->
<?php if ( ! $metabox['args']['multisite'] ) : ?>
	<div class="updated error inline" style="background-color: #f7f7f7;">
		<p><?php _e( 'It is recommended that you install and activate the <a href="https://civicrm.org/extensions/multisite-permissioning" target="_blank">CiviCRM Multisite</a> extension to work with multiple Domains in CiviCRM.', 'civicrm-admin-utilities' ); ?></p>
	</div>
<?php endif; ?>

<?php if ( ! empty( $metabox['args']['domains'] ) ) : ?>

<table class="form-table">

	<?php foreach( $metabox['args']['domains'] AS $domain ) : ?>

	<tr>
		<th scope="row">
			<?php echo $domain['name']; ?>
		</th>

		<td>
			<?php echo sprintf(
				__( 'ID %s', 'civicrm-admin-utilities' ),
				'<span class="cau_domain_id">' . $domain['id'] . '</span>'
			); ?>
		</td>
	</tr>

	<?php endforeach; ?>

</table>

<?php endif; ?>
