<?php
	/*
	Plugin Name: Freemius Dummy Plugin
	Version: 1.0.0
	Author: Freemius
	Author URI: https://freemius.com
	License: GPLv2
	*/

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	define('WP_DM__SLUG', 'dummy-plugin');

	if (!class_exists('Dummy_Plugin')) :

		class Dummy_Plugin
		{
			function __construct()
			{
				add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
			}

			function admin_menu()
			{
				add_object_page(
					__('Dummy Plugin', WP_DM__SLUG),
					__('Dummy Plugin', WP_DM__SLUG),
					'manage_options',
					WP_DM__SLUG,
					array(&$this, 'render_settings')
				);

			}

			function render_settings()
			{
				?>
				<h2><?php _e('Dummy Plugin Settings', WP_DM__SLUG) ?></h2>
				<p>
					Welcome to Freemius dummy plugin.
				</p>
			<?php
			}
		}

		$dp = new Dummy_Plugin();

	endif;