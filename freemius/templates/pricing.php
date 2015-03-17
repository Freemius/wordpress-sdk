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
	$fs = fs($slug);

	$timestamp = time();

	// Get site context secure params.
	$context_params = FS_Security::instance()->get_context_params(
		$fs->get_site(),
		$timestamp,
		'upgrade'
	);

	$query_params = array_merge($context_params, array(
		'next' => $fs->_get_admin_page_url('account', array('fs_action' => 'sync_license')),
		'plugin_version' => $fs->get_plugin_version(),
		// Billing cycle.
		'billing_cycle' => fs_request_get('billing_cycle', WP_FS__PERIOD_ANNUALLY),
	));
?>

<div id="fs_pricing" class="wrap" style="margin: 0 0 -65px -20px;">
	<div id="iframe"></div>
	<form action="" method="POST">
		<input type="hidden" name="user_id" />
		<input type="hidden" name="user_email" />
		<input type="hidden" name="site_id" />
		<input type="hidden" name="public_key" />
		<input type="hidden" name="secret_key" />
		<input type="hidden" name="action" value="account" />
	</form>

	<script type="text/javascript">
		(function($, undef) {
			$(function () {
				var
				// Keep track of the iframe height.
					iframe_height = 800,
					base_url = '<?php echo WP_FS__ADDRESS ?>',
				// Pass the parent page URL into the Iframe in a meaningful way (this URL could be
				// passed via query string or hard coded into the child page, it depends on your needs).
					src = base_url + '/pricing/?<?php echo http_build_query($query_params) ?>#' + encodeURIComponent(document.location.href),

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
			});
		})(jQuery);
	</script>
</div>
<?php fs_require_template('powered-by.php') ?>