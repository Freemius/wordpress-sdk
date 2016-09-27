<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.6
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class FS_Plugin_Manager {
		/**
		 * @since 1.2.2
		 *
		 * @var string
		 */
		private $_module_type;
		/**
		 * @var string
		 */
		protected $_slug;
		/**
		 * @since 1.2.2
		 *
		 * @var FS_Plugin
		 */
		protected $_module;

		/**
		 * @var FS_Plugin_Manager[]
		 */
		private static $_instances = array();
		/**
		 * @var FS_Logger
		 */
		protected $_logger;

		/**
		 * @param number $module_id
		 * @param string $module_slug
		 * @param string $module_type
		 *
		 * @return FS_Plugin_Manager
		 */
		static function instance( $module_id, $module_slug, $module_type ) {
			if ( ! isset( self::$_instances[ $module_id ] ) ) {
				self::$_instances[ $module_id ] = new FS_Plugin_Manager( $module_id, $module_slug, $module_type );
			}

			return self::$_instances[ $module_id ];
        }

		protected function __construct( $module_id, $module_slug, $module_type ) {
			$this->_logger = FS_Logger::get_logger( WP_FS__SLUG . '_' . $module_id . '_' . 'plugins', WP_FS__DEBUG_SDK, WP_FS__ECHO_DEBUG_SDK );

			$this->_slug        = $module_slug;
			$this->_module_type = $module_type;

			$this->load();
		}

		protected function get_option_manager() {
			return FS_Option_Manager::get_manager( WP_FS__ACCOUNTS_OPTION_NAME, true );
		}

		protected function get_all_modules() {
			return $this->get_option_manager()->get_option( $this->_module_type . 's', array() );
		}

		/**
		 * Load plugin data from local DB.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 */
		function load() {
			$all_modules   = $this->get_all_modules();
			$this->_module = isset( $all_modules[ $this->_slug ] ) ?
				$all_modules[ $this->_slug ] :
				null;
		}

		/**
		 * Store plugin on local DB.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param bool|FS_Plugin $module
		 * @param bool           $flush
		 *
		 * @return bool|\FS_Plugin
		 */
		function store( $module = false, $flush = true ) {
			$all_modules = $this->get_all_modules();

			if (false !== $module ) {
				$this->_module = $module;
			}

			$all_modules[ $this->_slug ] = $this->_module;

			$options_manager = $this->get_option_manager();
			$options_manager->set_option( $this->_module_type . 's', $all_modules, $flush );

			return $this->_module;
		}

		/**
		 * Update local plugin data if different.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param \FS_Plugin $plugin
		 * @param bool       $store
		 *
		 * @return bool True if plugin was updated.
		 */
		function update( FS_Plugin $plugin, $store = true ) {
			if ( ! ($this->_module instanceof FS_Plugin ) ||
			     $this->_module->slug != $plugin->slug ||
			     $this->_module->public_key != $plugin->public_key ||
			     $this->_module->secret_key != $plugin->secret_key ||
			     $this->_module->parent_plugin_id != $plugin->parent_plugin_id ||
			     $this->_module->title != $plugin->title
			) {
				$this->store( $plugin, $store );

				return true;
			}

			return false;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param FS_Plugin $plugin
		 * @param bool      $store
		 */
		function set( FS_Plugin $plugin, $store = false ) {
			$this->_module = $plugin;

			if ( $store ) {
				$this->store();
			}
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @return bool|\FS_Plugin
		 */
		function get() {
			return isset( $this->_module ) ?
				$this->_module :
				false;
		}


	}