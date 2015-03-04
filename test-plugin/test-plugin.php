<?php
	/*
	Plugin Name: Freemius Test Plugin
	Version: 1.0.0
	Author: Freemius
	Author URI: https://freemius.com
	License: GPLv2
	*/

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	define('WP_DM__SLUG', 'test-plugin');

	if (!class_exists('Test_Plugin')) :

		class Test_Plugin
		{
			function __construct()
			{
				add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
			}

			function admin_menu()
			{
				// Add main menu item and bind it to the settings page.
				add_object_page(
					__('Test Plugin', WP_DM__SLUG),
					__('Test Plugin', WP_DM__SLUG),
					'manage_options',
					WP_DM__SLUG,
					array(&$this, 'render_settings')
				);

				// Add sub-menu settings item.
				add_submenu_page(
					WP_DM__SLUG,
					__('Test Plugin Settings', WP_DM__SLUG),
					__('Settings', WP_DM__SLUG),
					'manage_options',
					WP_DM__SLUG,
					array(&$this, 'render_settings')
				);
			}

			function render_settings()
			{
				?>
				<h2><?php _e('Test Plugin Settings', WP_DM__SLUG) ?></h2>
				<p>
					Welcome to Freemius test plugin.
				</p>
			<?php
			}
		}

		$dp = new Test_Plugin();

	endif;
