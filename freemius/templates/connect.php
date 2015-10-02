<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.7
	 */

	wp_enqueue_script('jquery');
	wp_enqueue_script('json2');
	fs_enqueue_local_script('postmessage', 'nojquery.ba-postmessage.min.js');
	fs_enqueue_local_script('fs-postmessage', 'postmessage.js');

	fs_enqueue_local_style( 'fs_connect', '/admin/connect.css' );

	$slug = $VARS['slug'];
	$fs = fs($slug);
	$current_user = wp_get_current_user();

	$first_name = $current_user->user_firstname;
	if (empty($first_name))
		$first_name = $current_user->nickname;

	$site_url = get_site_url();
	$protocol_pos = strpos($site_url, '://');
	if (false !== $protocol_pos)
		$site_url = substr($site_url, $protocol_pos + 3);
?>
<div id="fs_connect" class="wrap<?php if (!$fs->enable_anonymous()) echo ' fs-anonymous-disabled' ?>">
	<div class="fs-visual">
		<b class="fs-site-icon"><i class="dashicons dashicons-wordpress"></i></b>
		<i class="dashicons dashicons-plus fs-first"></i>
		<div class="fs-plugin-icon">
			<object data="//plugins.svn.wordpress.org/<?php echo $slug ?>/assets/icon-128x128.png" type="image/png">
				<object data="//plugins.svn.wordpress.org/<?php echo $slug ?>/assets/icon-128x128.jpg" type="image/png">
					<object data="//plugins.svn.wordpress.org/<?php echo $slug ?>/assets/icon-256x256.png" type="image/png">
						<object data="//plugins.svn.wordpress.org/<?php echo $slug ?>/assets/icon-256x256.jpg" type="image/png">
							<img src="//wimg.freemius.com/plugin-icon.png" />
						</object>
					</object>
				</object>
			</object>
		</div>
		<i class="dashicons dashicons-plus fs-second"></i>
		<img class="fs-connect-logo" width="80" height="80" src="//img.freemius.com/connect-logo.png" />
	</div>
	<div class="fs-content">
		<p><?php
				echo $fs->apply_filters('connect_message', sprintf(
					_x('Hey %s,', 'greeting', 'freemius') . '<br>' .
					__('In order to enjoy all our features and functionality, %s needs to connect your user, %s at %s, to %s', 'freemius'),
					$first_name,
					'<b>' . $fs->get_plugin_name() . '</b>',
					'<b>' . $current_user->user_login . '</b>',
					'<a href="' . get_site_url() . '" target="_blank">' . $site_url . '</a>',
					'<a href="https://freemius.com/wordpress/" target="_blank">freemius.com</a>'
				));
			?></p>
	</div>
	<div class="fs-actions">
		<?php if ( $fs->enable_anonymous() ) : ?>
		<a href="<?php echo wp_nonce_url($fs->_get_admin_page_url('', array('fs_action' => $slug . '_skip_activation')), $slug . '_skip_activation') ?>" class="button button-secondary" tabindex="2"><?php _ex('Skip', 'verb', 'freemius') ?></a>
		<?php endif ?>
		<?php $fs_user = Freemius::_get_user_by_email($current_user->user_email) ?>
		<?php if (is_object($fs_user)) : ?>
			<form action="" method="POST">
				<input type="hidden" name="fs_action" value="<?php echo $slug ?>_activate_existing">
				<?php wp_nonce_field('activate_existing_' . $fs->get_public_key()) ?>
				<button class="button button-primary" tabindex="1" type="submit"><?php _ex('Allow & Continue', 'button label', 'freemius') ?> &nbsp;&#10140;</button>
			</form>
		<?php else : ?>
			<form method="post" action="<?php echo WP_FS__ADDRESS ?>/action/service/user/install/">
				<?php
					$params = array(
						'user_firstname' => $current_user->user_firstname,
						'user_lastname' => $current_user->user_lastname,
						'user_nickname' => $current_user-> user_nicename,
						'user_email' => $current_user->user_email,
						'plugin_slug' => $slug,
						'plugin_id' => $fs->get_id(),
						'plugin_public_key' => $fs->get_public_key(),
						'plugin_version' => $fs->get_plugin_version(),
						'return_url' => wp_nonce_url($fs->_get_admin_page_url(
							'',
							array('fs_action' => $slug . '_activate_new')
						), $slug . '_activate_new'),
						'account_url' => wp_nonce_url($fs->_get_admin_page_url(
							'account',
							array('fs_action' => 'sync_user')
						), 'sync_user'),
						'site_url' => get_site_url(),
						'site_name' => get_bloginfo('name'),
						'platform_version' => get_bloginfo('version'),
						'language' => get_bloginfo('language'),
						'charset' => get_bloginfo('charset'),
					);
				?>
				<?php foreach ($params as $name => $value) : ?>
					<input type="hidden" name="<?php echo $name ?>" value="<?php echo esc_attr($value) ?>">
				<?php endforeach ?>
				<button class="button button-primary" tabindex="1" type="submit"><?php _ex('Allow & Continue', 'button label', 'freemius') ?> &nbsp;&#10140;</button>
			</form>
		<?php endif ?>
	</div>
	<div class="fs-permissions">
		<a class="fs-trigger" href="#"><?php _e('What permissions are being granted?', 'freemius') ?></a>
		<ul>
			<li>
				<i class="dashicons dashicons-admin-users"></i>
				<div>
					<span><?php _e('Your Profile Overview', 'freemius') ?></span>
					<p>Name and email address</p>
				</div>
			</li>
			<li>
				<i class="dashicons dashicons-wordpress"></i>
				<div>
					<span><?php _e('Your Site Overview', 'freemius') ?></span>
					<p>Site address and WordPress version</p>
				</div>
			</li>
			<li>
				<i class="dashicons dashicons-admin-plugins"></i>
				<div>
					<span><?php _e('Current Plugin Events', 'freemius') ?></span>
					<p>Activation, deactivation and uninstall</p>
				</div>
			</li>
		</ul>
	</div>
	<div class="fs-terms">
		<a href="https://freemius.com/privacy/" target="_blank">Privacy Policy</a>
		&nbsp;&nbsp;-&nbsp;&nbsp;
		<a href="https://freemius.com/terms/" target="_blank">Terms of Service</a>
	</div>
</div>
<script type="text/javascript">
	(function($){
		$('.button.button-primary').on('click', function(){
			$(document.body).css({'cursor':'wait'});
			$(this).html('Activating...').css({'cursor':'wait'});
		});
		$('.fs-permissions .fs-trigger').on('click', function(){
			$('.fs-permissions').toggleClass('fs-open');
		});
	})(jQuery);
</script>