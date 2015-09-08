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
		 * @var FS_Option_Manager API Caching layer
		 */
		private static $_cache;

		/**
		 * @var int Clock diff in seconds between current server to API server.
		 */
		private static $_clock_diff;

		/**
		 * @var Freemius_Api
		 */
		private $_api;

		/**
		 * @var string
		 */
		private $_slug;

		/**
		 * @var FS_Logger
		 * @since 1.0.4
		 */
		private $_logger;

		/**
		 * @param string      $slug
		 * @param string      $scope      'app', 'developer', 'user' or 'install'.
		 * @param number      $id         Element's id.
		 * @param string      $public_key Public key.
		 * @param bool        $is_sandbox
		 * @param bool|string $secret_key Element's secret key.
		 *
		 * @internal param \Freemius $freemius
		 * @return \FS_Api
		 */
		static function instance( $slug, $scope, $id, $public_key, $is_sandbox, $secret_key = false ) {
			$identifier = md5($slug . $scope . $id . $public_key . (is_string($secret_key) ? $secret_key : '') . json_encode($is_sandbox));

			if ( ! isset( self::$_instances[ $identifier ] ) ) {
				if ( 0 === count( self::$_instances ) ) {
					self::_init();
				}

				self::$_instances[ $identifier ] = new FS_Api($slug, $scope, $id, $public_key, $secret_key, $is_sandbox );
			}

			return self::$_instances[ $identifier ];
		}

		private static function _init() {
			if ( ! class_exists( 'Freemius_Api' ) ) {
				require_once( WP_FS__DIR_SDK . '/Freemius.php' );
			}

			self::$_options    = FS_Option_Manager::get_manager( WP_FS__OPTIONS_OPTION_NAME, true );
			self::$_cache      = FS_Option_Manager::get_manager( WP_FS__API_CACHE_OPTION_NAME, true );

			self::$_clock_diff = self::$_options->get_option( 'api_clock_diff', 0 );

			Freemius_Api::SetClockDiff( self::$_clock_diff );
		}

		/**
		 * @param string      $slug
		 * @param string      $scope      'app', 'developer', 'user' or 'install'.
		 * @param number      $id         Element's id.
		 * @param string      $public_key Public key.
		 * @param bool|string $secret_key Element's secret key.
		 * @param bool        $is_sandbox
		 *
		 * @internal param \Freemius $freemius
		 */
		private function __construct($slug, $scope, $id, $public_key, $secret_key, $is_sandbox)
		{
			$this->_api = new Freemius_Api( $scope, $id, $public_key, $secret_key, $is_sandbox );

			$this->_slug = $slug;
			$this->_logger = FS_Logger::get_logger(WP_FS__SLUG . '_' . $slug . '_api', WP_FS__DEBUG_SDK, WP_FS__ECHO_DEBUG_SDK);
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
		 * Get API request URL signed via query string.
		 *
		 * @param string $path
		 *
		 * @return string
		 */
		function get_signed_url($path)
		{
			return $this->_api->GetSignedUrl($path);
		}

		/**
		 * @param string $path
		 * @param bool   $flush
		 * @param int    $expiration (optional) Time until expiration in seconds from now, defaults to 24 hours
		 *
		 * @return stdClass|mixed
		 */
		function get($path = '/', $flush = false, $expiration = WP_FS__TIME_24_HOURS_IN_SEC)
		{
			$cache_key = $this->get_cache_key($path);

			// Always flush during development.
			if (WP_FS__DEV_MODE || $this->_api->IsSandbox())
				$flush = true;

			// Get result from cache.
			$cache_entry = self::$_cache->get_option($cache_key, false);

			$fetch = false;
			if ($flush ||
			    false === $cache_entry ||
			    !isset($cache_entry->timestamp) ||
			    !is_numeric($cache_entry->timestamp) ||
			    $cache_entry->timestamp < WP_RW__SCRIPT_START_TIME)
			{
				$fetch = true;
			}

			if ($fetch)
			{
				$result = $this->call($path);

				if (!is_object($result) || isset($result->error))
				{
					// If there was an error during a newer data fetch,
					// fallback to older data version.
					if (is_object($cache_entry) &&
						isset($cache_entry->result) &&
						!isset($cache_entry->result->error))
					{
						$result = $cache_entry->result;
					}
				}

				$cache_entry = new stdClass();
				$cache_entry->result = $result;
				$cache_entry->timestamp = WP_FS__SCRIPT_START_TIME + $expiration;
				self::$_cache->set_option($cache_key, $cache_entry, true);
			}

			return $cache_entry->result;
		}

		private function get_cache_key($path, $method = 'GET', $params = array())
		{
			$canonized = $this->_api->CanonizePath($path);
//			$exploded = explode('/', $canonized);
//			return $method . '_' . array_pop($exploded) . '_' . md5($canonized . json_encode($params));
			return $method . ':' . $canonized . (!empty($params) ? '#' . md5(json_encode($params))  : '');
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