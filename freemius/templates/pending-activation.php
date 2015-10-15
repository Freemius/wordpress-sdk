<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.9
	 */

	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'json2' );
	fs_enqueue_local_script( 'postmessage', 'nojquery.ba-postmessage.min.js' );
	fs_enqueue_local_script( 'fs-postmessage', 'postmessage.js' );

	fs_enqueue_local_style( 'fs_connect', '/admin/connect.css' );

	$slug         = $VARS['slug'];
	$fs           = freemius( $slug );
	$current_user = wp_get_current_user();

	$first_name = $current_user->user_firstname;
	if ( empty( $first_name ) ) {
		$first_name = $current_user->nickname;
	}

	$site_url     = get_site_url();
	$protocol_pos = strpos( $site_url, '://' );
	if ( false !== $protocol_pos ) {
		$site_url = substr( $site_url, $protocol_pos + 3 );
	}
?>
<div id="fs_connect" class="wrap fs-anonymous-disabled">
	<div class="fs-visual">
		<b class="fs-site-icon"><i class="dashicons dashicons-wordpress"></i></b>
		<i class="dashicons dashicons-plus fs-first"></i>

		<div class="fs-plugin-icon">
			<object data="//plugins.svn.wordpress.org/<?php echo $slug ?>/assets/icon-128x128.png" type="image/png">
				<object data="//plugins.svn.wordpress.org/<?php echo $slug ?>/assets/icon-128x128.jpg" type="image/png">
					<object data="//plugins.svn.wordpress.org/<?php echo $slug ?>/assets/icon-256x256.png"
					        type="image/png">
						<object data="//plugins.svn.wordpress.org/<?php echo $slug ?>/assets/icon-256x256.jpg"
						        type="image/png">
							<img src="//wimg.freemius.com/plugin-icon.png"/>
						</object>
					</object>
				</object>
			</object>
		</div>
		<i class="dashicons dashicons-plus fs-second"></i>
		<img class="fs-connect-logo" width="80" height="80" src="//img.freemius.com/connect-logo.png"/>
	</div>
	<div class="fs-content">
		<p><?php
				echo $fs->apply_filters( 'pending_activation_message', sprintf(
					__fs( 'thanks-x' ) . '<br>' .
					__fs( 'pending-activation-message' ),
					$first_name,
					'<b>' . $fs->get_plugin_name() . '</b>',
					'<b>' . $current_user->user_email . '</b>'
				) )
			?></p>
	</div>
	<div class="fs-actions">
		<?php $fs_user = Freemius::_get_user_by_email( $current_user->user_email ) ?>
		<form method="post" action="<?php echo WP_FS__ADDRESS ?>/action/service/user/install/">
			<?php
				$params = array(
					'user_firstname'    => $current_user->user_firstname,
					'user_lastname'     => $current_user->user_lastname,
					'user_nickname'     => $current_user->user_nicename,
					'user_email'        => $current_user->user_email,
					'plugin_slug'       => $slug,
					'plugin_id'         => $fs->get_id(),
					'plugin_public_key' => $fs->get_public_key(),
					'plugin_version'    => $fs->get_plugin_version(),
					'return_url'        => wp_nonce_url( $fs->_get_admin_page_url(
						'',
						array( 'fs_action' => $slug . '_activate_new' )
					), $slug . '_activate_new' ),
					'account_url'       => wp_nonce_url( $fs->_get_admin_page_url(
						'account',
						array( 'fs_action' => 'sync_user' )
					), 'sync_user' ),
					'site_url'          => get_site_url(),
					'site_name'         => get_bloginfo( 'name' ),
					'platform_version'  => get_bloginfo( 'version' ),
					'language'          => get_bloginfo( 'language' ),
					'charset'           => get_bloginfo( 'charset' ),
				);
			?>
			<?php foreach ( $params as $name => $value ) : ?>
				<input type="hidden" name="<?php echo $name ?>" value="<?php echo esc_attr( $value ) ?>">
			<?php endforeach ?>
			<button class="button button-primary" tabindex="1"
			        type="submit"><?php _efs( 'resend-activation-email' ) ?></button>
		</form>
	</div>
	<div class="fs-permissions">
		<a class="fs-trigger" href="#"><?php _efs( 'what-permissions' ) ?></a>
		<ul>
			<li>
				<i class="dashicons dashicons-admin-users"></i>

				<div>
					<span><?php _efs( 'permissions-profile' ) ?></span>

					<p><?php _efs( 'permissions-profile_desc' ) ?></p>
				</div>
			</li>
			<li>
				<i class="dashicons dashicons-wordpress"></i>

				<div>
					<span><?php _efs( 'permissions-site' ) ?></span>

					<p><?php _efs( 'permissions-site_desc' ) ?></p>
				</div>
			</li>
			<li>
				<i class="dashicons dashicons-admin-plugins"></i>

				<div>
					<span><?php _efs( 'permissions-events' ) ?></span>

					<p><?php _efs( 'permissions-events_desc' ) ?></p>
				</div>
			</li>
		</ul>
	</div>
	<div class="fs-terms">
		<a href="https://freemius.com/privacy/" target="_blank"><?php _efs( 'privacy-policy' ) ?></a>
		&nbsp;&nbsp;-&nbsp;&nbsp;
		<a href="https://freemius.com/terms/" target="_blank"><?php _efs( 'tos' ) ?></a>
	</div>
</div>
<script type="text/javascript">
	(function ($) {
		$('.button.button-primary').on('click', function () {
			$(document.body).css({'cursor': 'wait'});
			$(this).html('Sending email...').css({'cursor': 'wait'});
		});
		$('.fs-permissions .fs-trigger').on('click', function () {
			$('.fs-permissions').toggleClass('fs-open');
		});
	})(jQuery);
</script>