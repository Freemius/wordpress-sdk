<?php
/**
 *
 *
 * @package     Freemius
 * @copyright   Copyright (c) 2015, Freemius, Inc.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_FS__SECURITY_PARAMS_PREFIX', 's_' );

class FS_Security {
	/**
	 *
	 *
	 * @var FS_Security
	 * @since 1.0.3
	 */
	private static $_instance;
	/**
	 *
	 *
	 * @var FS_Logger
	 * @since 1.0.3
	 */
	private static $_logger;

	public static function instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new FS_Security();
			self::$_logger   = FS_Logger::get_logger( WP_FS__SLUG, WP_FS__DEBUG_SDK, WP_FS__ECHO_DEBUG_SDK );
		}

		return self::$_instance;
	}

	private function __construct() {
	}

	function get_secure_token( FS_Scope_Entity $entity, $timestamp, $action = '' ) {
		return md5(
			$timestamp .
			$entity->id .
			$entity->secret_key .
			$entity->public_key .
			$action
		);
	}

	function get_context_params( FS_Scope_Entity $entity, $timestamp = false, $action = '' ) {
		if ( false === $timestamp ) {
			$timestamp = time();
		}

		return array(
			's_ctx_type'   => $entity->get_type(),
			's_ctx_id'     => $entity->id,
			's_ctx_ts'     => $timestamp,
			's_ctx_secure' => $this->get_secure_token( $entity, $timestamp, $action ),
		);
	}
}
