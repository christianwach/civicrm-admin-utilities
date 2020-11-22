<!-- assets/templates/templates/metaboxes/site-metabox-misc.php -->
<p><?php _e( 'Shortcuts to some CiviCRM admin pages that have no menu items.', 'civicrm-admin-utilities' ); ?></p>

<ul>
	<li>
		<a href="<?php echo admin_url( 'admin.php?page=CiviCRM&q=' . urlencode( 'civicrm/menu/rebuild' ) . '?reset=1' ); ?>"><?php _e( 'Rebuild the CiviCRM menu', 'civicrm-admin-utilities' ); ?></a>
	</li>
	<li>
		<a href="<?php echo admin_url( 'admin.php?page=CiviCRM&q=' . urlencode( 'civicrm/menu/rebuild' ) . '?reset=1&triggerRebuild=1' ); ?>"><?php _e( 'Rebuild the CiviCRM database triggers', 'civicrm-admin-utilities' ); ?></a>
	</li>
	<li>
		<a href="<?php echo admin_url( 'admin.php?page=CiviCRM&q=' . urlencode( 'civicrm/upgrade' ) . '&reset=1' ); ?>"><?php _e( 'Upgrade CiviCRM', 'civicrm-admin-utilities' ); ?></a><br>
		<span class="description"><?php _e( 'Please note: you need to update the CiviCRM plugin folder first.', 'civicrm-admin-utilities' ); ?></span>
	</li>
</ul>
