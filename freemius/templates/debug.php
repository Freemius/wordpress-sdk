<h2><?php _efs( 'plugin-installs' ) ?> / <?php _efs( 'sites' ) ?></h2>
<?php
	/**
	 * @var FS_Site[] $sites
	 */
	$sites = $VARS['sites'];
?>
<table id="fs_installs" class="widefat">
	<thead>
	<tr>
		<th><?php _efs( 'id' ) ?></th>
		<th><?php _efs( 'plugin' ) ?></th>
		<th><?php _efs( 'plan' ) ?></th>
		<th><?php _efs( 'public-key' ) ?></th>
		<th><?php _efs( 'secret-key' ) ?></th>
	</tr>
	</thead>
	<tbody>
	<?php foreach ( $sites as $plugin_basename => $site ) : ?>
		<tr>
			<td><?php echo $site->id ?></td>
			<td><?php echo dirname( $plugin_basename ) ?></td>
			<td><?php
					echo is_object( $site->plan ) ? $site->plan->name : ''
				?></td>
			<td><?php echo $site->public_key ?></td>
			<td><?php echo $site->secret_key ?></td>
		</tr>
	<?php endforeach ?>
	</tbody>
</table>
<?php
	$addons = $VARS['addons'];
?>
<?php foreach ( $addons as $plugin_id => $plugin_addons ) : ?>
	<h2><?php printf( __fs( 'addons-of-x' ), $plugin_id ) ?></h2>
	<table id="fs_addons" class="widefat">
		<thead>
		<tr>
			<th><?php _efs( 'id' ) ?></th>
			<th><?php _efs( 'title' ) ?></th>
			<th><?php _efs( 'slug' ) ?></th>
			<th><?php _efs( 'version' ) ?></th>
			<th><?php _efs( 'public-key' ) ?></th>
			<th><?php _efs( 'secret-key' ) ?></th>
		</tr>
		</thead>
		<tbody>
		<?php
			/**
			 * @var FS_Plugin[] $plugin_addons
			 */
			foreach ( $plugin_addons as $addon ) : ?>
				<tr>
					<td><?php echo $addon->id ?></td>
					<td><?php echo $addon->title ?></td>
					<td><?php echo $addon->slug ?></td>
					<td><?php echo $addon->version ?></td>
					<td><?php echo $addon->public_key ?></td>
					<td><?php echo $addon->secret_key ?></td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
<?php endforeach ?>
<h2><?php _efs( 'users' ) ?></h2>
<?php
	/**
	 * @var FS_User[] $users
	 */
	$users = $VARS['users'];
?>
<table id="fs_users" class="widefat">
	<thead>
	<tr>
		<th><?php _efs( 'id' ) ?></th>
		<th><?php _efs( 'name' ) ?></th>
		<th><?php _efs( 'email' ) ?></th>
		<th><?php _efs( 'verified' ) ?></th>
		<th><?php _efs( 'public-key' ) ?></th>
		<th><?php _efs( 'secret-key' ) ?></th>
	</tr>
	</thead>
	<tbody>
	<?php foreach ( $users as $user_id => $user ) : ?>
		<tr>
			<td><?php echo $user->id ?></td>
			<td><?php echo $user->get_name() ?></td>
			<td><?php echo $user->email ?></td>
			<td><?php echo json_encode( $user->is_verified ) ?></td>
			<td><?php echo $user->public_key ?></td>
			<td><?php echo $user->secret_key ?></td>
		</tr>
	<?php endforeach ?>
	</tbody>
</table>
<br><br>
<form action="" method="POST">
	<input type="hidden" name="fs_action" value="delete_all_accounts">
	<?php wp_nonce_field( 'delete_all_accounts' ) ?>
	<button class="button button-primary"
	        onclick="if (confirm('<?php _efs( 'delete-all-confirm' ) ?>')) this.parentNode.submit(); return false;"><?php _efs( 'delete-all-accounts' ) ?></button>
</form>