<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.4
	 *
	 * @link        https://github.com/easydigitaldownloads/EDD-License-handler/blob/master/EDD_SL_Plugin_Updater.php
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	// Uncomment this line for testing.
//	set_site_transient( 'update_plugins', null );

	class FS_Plugin_Updater {

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

		function __construct( Freemius $freemius ) {
			$this->_fs = $freemius;

			$this->_logger = FS_Logger::get_logger( WP_FS__SLUG . '_' . $freemius->get_slug() . '_updater', WP_FS__DEBUG_SDK, WP_FS__ECHO_DEBUG_SDK );

			$this->_filters();
		}

		/**
		 * Initiate required filters.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 */
		private function _filters() {
			add_filter( 'pre_set_site_transient_update_plugins', array(
				&$this,
				'pre_set_site_transient_update_plugins_filter'
			) );
			add_filter( 'plugins_api', array( &$this, 'plugins_api_filter' ), 10, 3 );

			if ( ! WP_FS__IS_PRODUCTION ) {
				add_filter( 'http_request_host_is_external', array(
						$this,
						'http_request_host_is_external_filter'
					), 10, 3 );
			}
		}

		/**
		 * Since WP version 3.6, a new security feature was added that denies access to repository with a local ip. During development mode we want to be able updating plugin versions via our localhost repository. This filter white-list all domains including "api.freemius".
		 *
		 * @link http://www.emanueletessore.com/wordpress-download-failed-valid-url-provided/
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 *
		 * @param $allow
		 * @param $host
		 * @param $url
		 *
		 * @return bool
		 */
		function http_request_host_is_external_filter($allow, $host, $url)
		{
			return (false !== strpos($host, 'freemius')) ? true : $allow;
		}

		/**
		 * Check for Updates at the defined API endpoint and modify the update array.
		 *
		 * This function dives into the update api just when WordPress creates its update array,
		 * then adds a custom API call and injects the custom plugin data retrieved from the API.
		 * It is reassembled from parts of the native WordPress plugin update code.
		 * See wp-includes/update.php line 121 for the original wp_update_plugins() function.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 *
		 * @uses FS_Api
		 *
		 * @param array $transient_data Update array build by WordPress.
		 *
		 * @return array Modified update array with custom plugin data.
		 */
		function pre_set_site_transient_update_plugins_filter( $transient_data ) {
			$this->_logger->entrance();

			if ( empty( $transient_data ) ) {
				return $transient_data;
			}

			// Get plugin's newest update.
			$new_version = $this->_fs->get_update();

			if ( is_object( $new_version ) ) {
				$this->_logger->log( 'Found newer plugin version ' . $new_version->version );

				$plugin_details              = new stdClass();
				$plugin_details->slug        = $this->_fs->get_slug();
				$plugin_details->new_version = $new_version->version;
				$plugin_details->url         = WP_FS__ADDRESS;
				$plugin_details->package     = $new_version->url;

				// Add plugin to transient data.
				$transient_data->response[ $this->_fs->get_plugin_basename() ] = $plugin_details;
			}

			return $transient_data;
		}

		/**
		 * Updates information on the "View version x.x details" page with custom data.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 *
		 * @uses FS_Api
		 *
		 * @param mixed  $data
		 * @param string $action
		 * @param object $args
		 *
		 * @return object
		 */
		function plugins_api_filter( $data, $action = '', $args = null ) {
			$this->_logger->entrance();

			if ( ( 'plugin_information' !== $action ) ||
			     ! isset( $args->slug ) ||
			     ( $this->_fs->get_slug() !== $args->slug )
			) {
				return $data;
			}

			/*$info = $this->_fs->get_api_site_scope()->call('/information.json');

			if ( !isset($info->error) ) {
				$data = $info;
			}*/

			return $data;
		}
	}