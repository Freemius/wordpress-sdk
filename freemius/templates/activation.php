<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.3
	 */

	wp_enqueue_script('jquery');
	wp_enqueue_script('json2');
	fs_enqueue_local_script('postmessage', 'nojquery.ba-postmessage.min.js');
	fs_enqueue_local_script('fs-postmessage', 'postmessage.js');

	$slug = $VARS['slug'];
	$fs = freemius($slug);
	$current_user = wp_get_current_user();
?>
<div id="fs_activation" class="wrap" style="margin: 0 0 -65px -20px;">
	<div id="iframe"></div>
	<form action="" method="POST">
		<input type="hidden" name="fs_action" value="activate">
		<?php wp_nonce_field('activate_' . $fs->get_public_key()) ?>
		<input type="hidden" name="user_id" />
		<input type="hidden" name="user_email" />
		<input type="hidden" name="user_first" />
		<input type="hidden" name="user_last" />
		<input type="hidden" name="user_public_key" />
		<input type="hidden" name="user_secret_key" />
		<input type="hidden" name="user_is_verified" />
		<input type="hidden" name="plugin_id" />
		<input type="hidden" name="plugin_slug" />
		<input type="hidden" name="install_id" />
		<input type="hidden" name="install_public_key" />
		<input type="hidden" name="install_secret_key" />
		<input type="hidden" name="plan_id" />
		<input type="hidden" name="plan_name" />
		<!--	    <input type="hidden" name="plan_is_trial" />-->
		<input type="hidden" name="plan_title" />
		<input type="hidden" name="plans" />
	</form>

	<script type="text/javascript">
		(function($){
			$(function(){
				var
				// Keep track of the iframe height.
					iframe_height = 800,
					base_url = '<?php echo WP_FS__ADDRESS ?>',
				// Pass the parent page URL into the Iframe in a meaningful way (this URL could be
				// passed via query string or hard coded into the child page, it depends on your needs).
					src = base_url + '/signup/?<?php echo http_build_query(array(
	                    'plugin_slug' => $fs->get_slug(),
	                    'plugin_id' => $fs->get_id(),
	                    'plugin_public_key' => $fs->get_public_key(),
	                    'plugin_version' => $fs->get_plugin_version(),
	                    'wp_admin_css' => get_bloginfo('wpurl') . "/wp-admin/load-styles.php?c=1&load=buttons,wp-admin,dashicons",
	                    'password_reset' => fs_request_get('password_reset', ''),
	                )) ?>#' + encodeURIComponent(document.location.href);

				FS.PostMessage.init(base_url);

				// Append the Iframe into the DOM.
				var iframe = $('<iframe " src="' + src + '" width="100%" height="' + iframe_height + 'px" scrolling="no" frameborder="0" style="background: transparent;"><\/iframe>')
					.load(function(){
						var
							address = document.location.href,
							pos = address.indexOf('/wp-admin');

						address = address.substring(0, pos);
						FS.PostMessage.post('wp_account', {
							users: [<?php
                                    $users = $fs->get_all_users();
                                    if (is_array($users) && 0 < count($users))
                                    {
                                        $first = true;
                                        foreach ($users as $u)
                                        {
                                            /* @var $u FS_User */
                                            echo ($first ? '' : ', ' ) . "'" . $u->email . "'";
                                            $first = false;
                                        }
                                    }
                                ?>],
							current_user: {
								first: '<?php echo addslashes($current_user->user_firstname) ?>',
								last: '<?php echo addslashes($current_user->user_lastname) ?>',
								email: '<?php echo addslashes($current_user->user_email) ?>'
							},
							site: {
								address: address,
								title: '<?php echo addslashes(get_bloginfo('name')) ?>',
								version:  '<?php echo addslashes(get_bloginfo('version')) ?>',
								language:  '<?php echo addslashes(get_bloginfo('language')) ?>',
								charset:  '<?php echo addslashes(get_bloginfo('charset')) ?>'
							}
						}, iframe.get(0));
					})
					.appendTo('#iframe');

				FS.PostMessage.receive('height', function (data){
					var h = data.height;
					if (!isNaN(h) && h > 0 && h != iframe_height) {
						iframe_height = h;
						$("#iframe iframe").height(iframe_height + 'px');
					}
				});

				FS.PostMessage.receive('account', function(identity){
					if (null == identity.user)
						return;

					$(document.body).css({'cursor':'wait'});

					// Update user values.
					for (var i = 0, prop = ['id', 'email', 'first', 'last', 'public_key', 'secret_key', 'is_verified'], len = prop.length; i < len; i++)
						$('#fs_activation form input[name=user_' + prop[i] + ']').val(identity.user[prop[i]]);
					for (var i = 0, prop = ['id', 'public_key', 'secret_key'], len = prop.length; i < len; i++)
						$('#fs_activation form input[name=install_' + prop[i] + ']').val(identity.install[prop[i]]);
					for (var i = 0, prop = ['id', 'slug'], len = prop.length; i < len; i++)
						$('#fs_activation form input[name=plugin_' + prop[i] + ']').val(identity.plugin[prop[i]]);
					for (var i = 0, prop = ['id', 'title', 'name'], len = prop.length; i < len; i++)
						$('#fs_activation form input[name=plan_' + prop[i] + ']').val(identity.plan[prop[i]]);

					$('#fs_activation form input[name=plans]').val(encodeURIComponent(JSON.stringify(identity.plans)));

					$('#fs_activation form').submit();
				});
			});
		})(jQuery);
	</script>
</div>
<?php fs_require_template('powered-by.php') ?>