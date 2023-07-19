<?php
/**
 * Hold classes and constants to be ignored in PHPStan checks.
 * 
 * @see: https://phpstan.org/user-guide/discovering-symbols
 */

// Constants.
define( 'WP_FS__SCRIPT_START_TIME', 1 );
define( 'WP_PLUGIN_DIR', dirname( __FILE__, 2) );
define( 'WP_FS__SDK_VERSION', 1 );
define( 'WP_LANG_DIR', dirname( __FILE__, 2) . 'languages' );
define( 'WP_FS__REMOTE_ADDR', 1 );
define( 'WP_CONTENT_DIR', dirname( __FILE__, 3) );

// Classes.
