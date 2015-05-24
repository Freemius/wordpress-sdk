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
	fs_enqueue_local_style( 'fs_common', '/admin/common.css' );

	$slug = $VARS['slug'];
	$fs = fs($slug);

	$timestamp = time();

	// Get site context secure params.
	$context_params = !$fs->is_registered() ?
		array() :
		FS_Security::instance()->get_context_params(
			$fs->get_site(),
			$timestamp,
			'checkout'
		);

	if ($fs->is_payments_sandbox())
		// Append plugin secure token for sandbox mode authentication.)
		$context_params['sandbox'] = FS_Security::instance()->get_secure_token(
			$fs->get_plugin(),
			$timestamp,
			'checkout'
		);

	$query_params = array_merge($context_params, $_GET, array(
		// Current plugin version.
		'plugin_version' => $fs->get_plugin_version(),
		'return_url'     => wp_nonce_url($fs->_get_admin_page_url(
			'account',
			array(
				'fs_action' => $slug . '_sync_license',
				'plugin_id' => isset($_GET['plugin_id']) ? $_GET['plugin_id'] : $fs->get_id()
			)
		), $slug . '_sync_license'),
		// Admin CSS URL for style/design competability.
		'wp_admin_css'   => get_bloginfo('wpurl') . "/wp-admin/load-styles.php?c=1&load=buttons,wp-admin,dashicons",
	));
?>
<div class="fs-secure-notice">
	<i class="dashicons dashicons-lock"></i>
	<span><b>Secure HTTPS Checkout</b> - PCI compliant, running via iframe from external domain</span>
</div>
<div id="fs_contact" class="wrap" style="margin: 40px 0 -65px -20px;">
	<div id="iframe"></div>
	<script type="text/javascript">
		(function($) {
			$(function () {

				var
					// Keep track of the iframe height.
					iframe_height = 800,
					base_url = '<?php echo WP_FS__ADDRESS ?>',
					// Pass the parent page URL into the Iframe in a meaningful way (this URL could be
					// passed via query string or hard coded into the child page, it depends on your needs).
					src = base_url + '/checkout/?<?php echo (isset($_REQUEST['XDEBUG_SESSION']) ? 'XDEBUG_SESSION=' . $_REQUEST['XDEBUG_SESSION'] . '&' : '') . http_build_query($query_params) ?>#' + encodeURIComponent(document.location.href),

					// Append the Iframe into the DOM.
					iframe = $('<iframe " src="' + src + '" width="100%" height="' + iframe_height + 'px" scrolling="no" frameborder="0" style="background: transparent;"><\/iframe>')
						.appendTo('#iframe');

				FS.PostMessage.init(base_url);
				FS.PostMessage.receive('height', function (data){
					var h = data.height;
					if (!isNaN(h) && h > 0 && h != iframe_height) {
						iframe_height = h;
						$("#iframe iframe").height(iframe_height + 'px');
					}
				});
				FS.PostMessage.receive('get_context', function (){
					// If the user didn't connect his account with Freemius,
					// once he accepts the Terms of Service and Privacy Policy,
					// and then click the purchase button, the context information
					// of the user will be shared with Freemius in order to complete the
					// purchase workflow and activate the license for the right user.
					<?php $current_user = wp_get_current_user() ?>
					FS.PostMessage.post('user_context', {
						user: {
							firstname: '<?php echo $current_user->user_firstname ?>',
							lastname: '<?php echo $current_user->user_lastname ?>',
							nickname: '<?php echo $current_user-> user_nicename ?>',
							email: '<?php echo $current_user->user_email ?>'
						},
						plugin: {
							id: '<?php echo $fs->get_id() ?>',
							slug: '<?php echo $slug ?>',
							public_key: '<?php echo $fs->get_public_key() ?>',
							version: '<?php echo $fs->get_plugin_version() ?>'
						},
						site: {
							name: '<?php echo get_bloginfo('name') ?>',
							version: '<?php echo get_bloginfo('version') ?>',
							language: '<?php echo get_bloginfo('language') ?>',
							charset: '<?php get_bloginfo('charset') ?>',
							account_url: '<?php echo wp_nonce_url($fs->_get_admin_page_url(
									'account',
									array('fs_action' => 'sync_user')
							), 'sync_user') ?>'
						}
					});
				});
			});
		})(jQuery);
	</script>
</div>
<?php fs_require_template('powered-by.php') ?>