<h2><?php _e('Plugins Installs', 'freemius') ?> / <?php _ex('Sites', 'like websites', 'freemius') ?></h2>
<?php
	/**
	 * @var FS_Site[] $sites
	 */
	$sites = $VARS['sites'];
?>
<table id="fs_installs" class="widefat">
	<thead>
	<tr>
		<th>ID</th>
		<th>Plugin</th>
		<th>Plan</th>
		<th>Public Key</th>
		<th>Secret Key</th>
	</tr>
	</thead>
	<tbody>
<?php foreach ($sites as $plugin_basename => $site) : ?>
		<tr>
			<td><?php echo $site->id ?></td>
			<td><?php echo dirname($plugin_basename) ?></td>
			<td><?php
					echo is_object($site->plan) ? $site->plan->name : ''
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
<?php foreach ($addons as $plugin_id => $plugin_addons) : ?>
<h2><?php printf(__('Add Ons of Plugin %s', 'freemius'), $plugin_id) ?></h2>
<table id="fs_addons" class="widefat">
	<thead>
	<tr>
		<th>ID</th>
		<th>Title</th>
		<th>Slug</th>
		<th>Version</th>
		<th>Public Key</th>
		<th>Secret Key</th>
	</tr>
	</thead>
	<tbody>
	<?php
		/**
		 * @var FS_Plugin[] $plugin_addons
		 */
		foreach ($plugin_addons as $addon) : ?>
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
<h2><?php _e('Users', 'freemius') ?></h2>
<?php
	/**
	 * @var FS_User[] $users
	 */
	$users = $VARS['users'];
?>
<table id="fs_users" class="widefat">
	<thead>
	<tr>
		<th>ID</th>
		<th>Name</th>
		<th>Email</th>
		<th>Verified</th>
		<th>Public Key</th>
		<th>Secret Key</th>
	</tr>
	</thead>
	<tbody>
	<?php foreach ($users as $user_id => $user) : ?>
		<tr>
			<td><?php echo $user->id ?></td>
			<td><?php echo $user->get_name() ?></td>
			<td><?php echo $user->email ?></td>
			<td><?php echo json_encode($user->is_verified) ?></td>
			<td><?php echo $user->public_key ?></td>
			<td><?php echo $user->secret_key ?></td>
		</tr>
	<?php endforeach ?>
	</tbody>
</table>
<br><br>
<form action="" method="POST">
	<input type="hidden" name="fs_action" value="delete_all_accounts">
	<?php wp_nonce_field('delete_all_accounts') ?>
	<button class="button button-primary" onclick="if (confirm('<?php _e('Are you sure you want to delete the all Freemius data?', 'freemius') ?>')) this.parentNode.submit(); return false;"><?php _e('Delete All Accounts', 'freemius') ?></button>
</form>