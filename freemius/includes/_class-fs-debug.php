<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.3
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	if (!class_exists( 'FS_Debug' )) {

		class FS_Debug {

			private static $_INSTANCE;

			public static function instance() {
				if ( ! isset( self::$INSTANCE ) ) {
					self::$_INSTANCE = new FS_Debug();
				}

				return self::$_INSTANCE;
			}

			function __construct() {
				add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
			}

			function admin_menu() {
				add_menu_page( __( 'Freemius', WP_FS__SLUG ), __( 'Freemius', WP_FS__SLUG ), 'manage_options', WP_FS__SLUG, array(
					&$this,
					'debug_dashboard'
				) );
			}

			function debug_dashboard() {
				?>
				<div class='wrap'>
					<h2><?php _e('Settings'); ?></h2>
					<form method='post' action='options.php'>
						<p class='submit'>
							<input name='submit' type='submit' id='submit' class='button-primary' value='<?php _e('Save') ?>' />
						</p>
					</form>
				</div>
			<?php
			}
		}

		// Init only once.
		FS_Debug::instance();
	}