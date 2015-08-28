<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.4
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	define( 'WP_FS__SLUG', 'freemius' );
	define( 'WP_FS__DEV_MODE', false );

	/**
	 * Directories
	 */
	define( 'WP_FS__DIR', dirname( __FILE__ ) );
	define( 'WP_FS__DIR_INCLUDES', WP_FS__DIR . '/includes' );
	define( 'WP_FS__DIR_TEMPLATES', WP_FS__DIR . '/templates' );
	define( 'WP_FS__DIR_ASSETS', WP_FS__DIR . '/assets' );
	define( 'WP_FS__DIR_CSS', WP_FS__DIR_ASSETS . '/css' );
	define( 'WP_FS__DIR_JS', WP_FS__DIR_ASSETS . '/js' );
	define( 'WP_FS__DIR_SDK', WP_FS__DIR_INCLUDES . '/sdk' );


	/**
	 * Domain / URL / Address
	 */
	define( 'WP_FS__TESTING_DOMAIN', 'fswp:8080' );
	define( 'WP_FS__DOMAIN_PRODUCTION', 'wp.freemius.com' );
	define( 'WP_FS__DOMAIN_LOCALHOST', 'wp.freemius' );
	define( 'WP_FS__ADDRESS_LOCALHOST', 'http://' . WP_FS__DOMAIN_LOCALHOST . ':8080' );
	define( 'WP_FS__ADDRESS_PRODUCTION', 'https://' . WP_FS__DOMAIN_PRODUCTION );

	define( 'WP_FS__IS_PRODUCTION_MODE', ! defined( 'WP_FS__DEV_MODE' ) || ! WP_FS__DEV_MODE || ( WP_FS__TESTING_DOMAIN !== $_SERVER['HTTP_HOST'] ) );

	define( 'WP_FS__ADDRESS', ( WP_FS__IS_PRODUCTION_MODE ? WP_FS__ADDRESS_PRODUCTION : WP_FS__ADDRESS_LOCALHOST ) );

	define( 'WP_FS__IS_LOCALHOST', ( substr($_SERVER['REMOTE_ADDR'], 0, 4) == '127.' || $_SERVER['REMOTE_ADDR'] == '::1') );
	define( 'WP_FS__IS_LOCALHOST_FOR_SERVER', (false !== strpos($_SERVER['HTTP_HOST'], 'localhost')) );

	// Set API address for local testing.
	if ( ! WP_FS__IS_PRODUCTION_MODE ) {
		define( 'FS_API__ADDRESS', 'http://api.freemius:8080' );
		define( 'FS_API__SANDBOX_ADDRESS', 'http://sandbox-api.freemius:8080' );
	}

	define( 'WP_FS___OPTION_PREFIX', 'fs' . (WP_FS__IS_PRODUCTION_MODE ? '' : '_dbg') . '_' );

	if ( ! defined( 'WP_FS__ACCOUNTS_OPTION_NAME' ) ) {
		define( 'WP_FS__ACCOUNTS_OPTION_NAME', WP_FS___OPTION_PREFIX . 'accounts' );
	}
	define( 'WP_FS__OPTIONS_OPTION_NAME', WP_FS___OPTION_PREFIX . 'options' );

	define( 'WP_FS__IS_HTTPS', (
		                           // Checks if CloudFlare's HTTPS (Flexible SSL support)
		                           isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === strtolower( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) ||
	                           // Check if HTTPS request.
	                           ( isset( $_SERVER['HTTPS'] ) && 'on' == $_SERVER['HTTPS'] ) ||
	                           ( isset( $_SERVER['SERVER_PORT'] ) && 443 == $_SERVER['SERVER_PORT'] )
	);

	define('WP_FS__IS_POST_REQUEST', (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST'));

	/**
	 * Billing Frequencies
	 */
	define( 'WP_FS__PERIOD_ANNUALLY', 'annual' );
	define( 'WP_FS__PERIOD_MONTHLY', 'monthly' );
	define( 'WP_FS__PERIOD_LIFETIME', 'lifetime' );

	/**
	 * Plans
	 */
	define( 'WP_FS__PLAN_DEFAULT_PAID', false );
	define( 'WP_FS__PLAN_FREE', 'free' );
	define( 'WP_FS__PLAN_TRIAL', 'trial' );

	/**
	 * Times in seconds
	 */
//	define( 'WP_FS__TIME_5_MIN_IN_SEC', 300 );
	define( 'WP_FS__TIME_10_MIN_IN_SEC', 600 );
//	define( 'WP_FS__TIME_15_MIN_IN_SEC', 900 );
	define( 'WP_FS__TIME_24_HOURS_IN_SEC', 86400 );

	/**
	 * Debugging
	 */
	define( 'WP_FS__DEBUG_SDK', ! empty( $_GET['fs_dbg'] ) );
	define( 'WP_FS__ECHO_DEBUG_SDK', ! empty( $_GET['fs_dbg_echo'] ) );
	define( 'WP_FS__LOG_DATETIME_FORMAT', 'Y-n-d H:i:s' );


	define( 'WP_FS__SCRIPT_START_TIME', time() );
	define( 'WP_FS__LOWEST_PRIORITY', 999999999 );
