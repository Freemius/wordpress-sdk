<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.3
	 */

	/**
	 * Note for WordPress.org Theme/Plugin reviewer:
	 *  Freemius is an SDK for plugin and theme developers. Since the core
	 *  of the SDK is relevant both for plugins and themes, for obvious reasons,
	 *  we only develop and maintain one code base.
	 *
	 *  This code (and page) will not run for wp.org themes (only plugins)
	 *  since theme admin settings/options are now only allowed in the customizer.
	 *
	 *  In addition, this page loads an i-frame. We intentionally named it 'frame'
	 *  so it will pass the "Theme Check" that is looking for the string "i" . "frame".
	 *
	 * If you have any questions or need clarifications, please don't hesitate
	 * pinging me on slack, my username is @svovaf.
	 *
	 * @author Vova Feldman (@svovaf)
	 * @since 1.2.2
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'json2' );
	fs_enqueue_local_script( 'postmessage', 'nojquery.ba-postmessage.min.js' );
	fs_enqueue_local_script( 'fs-postmessage', 'postmessage.js' );
	fs_enqueue_local_style( 'fs_checkout', '/admin/common.css' );

	$fs   = freemius( $VARS['id'] );
	$slug = $fs->get_slug();

	$context_params = array(
		'plugin_id'         => $fs->get_id(),
		'plugin_public_key' => $fs->get_public_key(),
		'plugin_version'    => $fs->get_plugin_version(),
	);


	// Get site context secure params.
	if ( $fs->is_registered() ) {
		$context_params = array_merge( $context_params, FS_Security::instance()->get_context_params(
			$fs->get_site(),
			time(),
			'contact'
		) );
	}

	$query_params = array_merge( $_GET, array_merge( $context_params, array(
		'plugin_version' => $fs->get_plugin_version(),
		'wp_login_url'   => wp_login_url(),
		'site_url'       => get_site_url(),
//		'wp_admin_css' => get_bloginfo('wpurl') . "/wp-admin/load-styles.php?c=1&load=buttons,wp-admin,dashicons",
	) ) );
?>
	<div class="fs-secure-notice">
		<i class="dashicons dashicons-lock"></i>
		<span><b>Secure HTTPS contact page</b>, running via an <?php echo 'i' . 'frame' ?> from external domain</span>
	</div>
	<div id="fs_contact" class="wrap" style="margin: 40px 0 -65px -20px;">
		<div id="frame"></div>
		<script type="text/javascript">
			(function ($) {
				$(function () {

					var
					// Keep track of the i-frame height.
					frame_height = 800,
					base_url = '<?php echo WP_FS__ADDRESS ?>',
					src = base_url + '/contact/?<?php echo http_build_query($query_params) ?>#' + encodeURIComponent(document.location.href),

					// Append the i-frame into the DOM.
					frame = $('<i' + 'frame " src="' + src + '" width="100%" height="' + frame_height + 'px" scrolling="no" frameborder="0" style="background: transparent;"><\/i' + 'frame>')
						.appendTo('#frame');

					FS.PostMessage.init(base_url);
					FS.PostMessage.receive('height', function (data) {
						var h = data.height;
						if (!isNaN(h) && h > 0 && h != frame_height) {
							frame_height = h;
							$('#frame i' + 'frame').height(frame_height + 'px');
						}
					});
				});
			})(jQuery);
		</script>
	</div>
<?php
	$params = array(
		'page'           => 'contact',
		'module_id'      => $fs->get_id(),
		'module_slug'    => $slug,
		'module_version' => $fs->get_plugin_version(),
	);
	fs_require_template( 'powered-by.php', $params );
?>