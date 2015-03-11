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

	class FS_Api {
		/**
		 * @var FS_Api[]
		 */
		private static $_instances = array();

		/**
		 * @var FS_Option_Manager Freemius options, options-manager.
		 */
		private static $_options;

		/**
		 * @var int Clock diff in seconds between current server to API server.
		 */
		private static $_clock_diff;

		/**
		 * @var Freemius_Api
		 */
		private $_api;

		/**
		 * @var Freemius
		 * @since 1.0.4
		 */
		private $_fs;

		/**
		 * @var FS_Logger
		 * @since 1.0.4
		 */
		private $_logger;

		/**
		 * @param Freemius $freemius
		 * @param string   $scope      'app', 'developer', 'user' or 'install'.
		 * @param number   $id         Element's id.
		 * @param string   $public_key Public key.
		 * @param string   $secret_key Element's secret key.
		 *
		 * @return \FS_Api
		 */
		static function instance( Freemius $freemius, $scope, $id, $public_key, $secret_key ) {
			$identifier = md5($freemius->get_slug() . $scope . $id . $public_key . $secret_key);

			if ( ! isset( self::$_instances[ $identifier ] ) ) {
				if ( 0 === count( self::$_instances ) ) {
					self::_init();
				}

				self::$_instances[ $identifier ] = new FS_Api($freemius, $scope, $id, $public_key, $secret_key );
			}

			return self::$_instances[ $identifier ];
		}

		private static function _init()
		{
			if ( ! class_exists( 'Freemius_Api' ) ) {
				require_once( WP_FS__DIR_SDK . '/Freemius.php' );
			}

			self::$_options = FS_Option_Manager::get_manager( WP_FS__OPTIONS_OPTION_NAME, true );

			self::$_clock_diff = self::$_options->get_option('api_clock_diff', 0);

			Freemius_Api::SetClockDiff(self::$_clock_diff);
		}

		/**
		 * @param \Freemius $freemius
		 * @param string    $scope  'app', 'developer', 'user' or 'install'.
		 * @param number    $id     Element's id.
		 * @param string    $public_key Public key.
		 * @param string    $secret_key Element's secret key.
		 */
		private function __construct(Freemius $freemius, $scope, $id, $public_key, $secret_key)
		{
			$this->_api = new Freemius_Api( $scope, $id, $public_key, $secret_key, !$freemius->is_live() );

			$this->_fs = $freemius;

			$this->_logger = FS_Logger::get_logger(WP_FS__SLUG . '_' . $this->_fs->get_slug() . '_api', WP_FS__DEBUG_SDK, WP_FS__ECHO_DEBUG_SDK);
		}

		/**
		 * Find clock diff between server and API server, and store the diff locally.
		 *
		 * @return bool|int False if clock diff didn't change, otherwise returns the clock diff in seconds.
		 */
		private function _sync_clock_diff()
		{
			$this->_logger->entrance();

			// Sync clock and store.
			$new_clock_diff = $this->_api->FindClockDiff();

			if ($new_clock_diff === self::$_clock_diff)
				return false;

			// Update API clock's diff.
			$this->_api->SetClockDiff(self::$_clock_diff);

			// Store new clock diff in storage.
			self::$_options->set_option('api_clock_diff', self::$_clock_diff, true);

			return $new_clock_diff;
		}

		/**
		 * Override API call to enable retry with servers' clock auto sync method.
		 *
		 * @param string $path
		 * @param string $method
		 * @param array  $params
		 * @param bool   $retry Is in retry or first call attempt.
		 *
		 * @return array|mixed|string|void
		 */
		private function _call($path, $method = 'GET', $params = array(), $retry = false) {
			$this->_logger->entrance();

			$result = $this->_api->Api( $path, $method, $params );

			if ( null !== $result &&
			     isset( $result->error ) &&
			     'request_expired' === $result->error->code
			) {

				if ( ! $retry ) {
					// Try to sync clock diff.
					if ( false !== $this->_sync_clock_diff() ) // Retry call with new synced clock.
					{
						return $this->_call( $path, $method, $params, true );
					}
				}
			}

			if ( null !== $result && isset( $result->error ) ) {
				// Log API errors.
				$this->_logger->error( $result->error->message );
			}

			return $result;
		}

		/**
		 * Override API call to wrap it in servers' clock sync method.
		 *
		 * @param string $path
		 * @param string $method
		 * @param array  $params
		 *
		 * @return array|mixed|string|void
		 * @throws Freemius_Exception
		 */
		function call($path, $method = 'GET', $params = array())
		{
			return $this->_call($path, $method, $params);
		}

		/**
		 * @return bool True if successful connectivity to the API.
		 */
		function test()
		{
			$this->_logger->entrance();

			return $this->_api->Test();
		}

		function get_url($path = '')
		{
			return $this->_api->GetUrl($path);
		}
	}