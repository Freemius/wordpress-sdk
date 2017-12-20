<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.2.3
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * Class FS_Storage
	 *
	 * A wrapper class for handling network level and single site level storage.
	 */
	class FS_Storage {
		/**
		 * @var FS_Storage
		 */
		private static $_instance;

        /**
         * @var FS_Key_Value_Storage Site level storage.
         */
		private $_storage;

        /**
         * @var FS_Key_Value_Storage Network level storage.
         */
		private $_network_storage;

        /**
         * @var bool
         */
		private $_is_theme;

        /**
         * @var bool
         */
		private $_is_multisite;

        /**
         * @var bool
         */
		private $_is_network_active;

        /**
         * Example:
         *
         * 'key' => array( '11' => true )
         *
         * #1 digit - 1 if theme
         * #2 digit - 1 if module was network activated
         *
         * @var array
         */
		private static $_BINARY_MAP = array(
            'activation_timestamp'       => array( '11' => true, '01' => true ),
            'affiliate_application_data' => true,
            'connectivity_test'          => true,
            'has_trial_plan'             => true,
            'install_sync_timestamp'     => true,
            'install_sync_cron'          => true,
            'install_timestamp'          => array( '11' => true, '01' => true ),
            'is_anonymous'               => false,
            'is_anonymous_ms'            => true,
            'is_on'                      => true,
            'is_pending_activation'      => array( '11' => true, '01' => true ),
            'is_plugin_new_install'      => true,
            'pending_license_key'        => array( '11' => true, '01' => true ),
            'plugin_last_version'        => true,
            'plugin_main_file'           => true,
            'plugin_version'             => true,
            'prev_is_premium'            => array( '11' => true, '01' => true ),
            'prev_user_id'               => array( '11' => true, '01' => true ),
            'sdk_downgrade_mode'         => true,
            'sdk_last_version'           => true,
            'sdk_upgrade_mode'           => true,
            'sdk_version'                => true,
            'sticky_optin_added'         => array( '11' => true, '01' => true ),
            'subscription'               => true,
            'sync_timestamp'             => true,
            'sync_cron'                  => true,
            'trial_plan'                 => true,
            'uninstall_reason'           => array( '11' => true, '01' => true ),
            'was_plugin_loaded'          => true,
        );

		/**
         * @author Leo Fajardo (@leorw)
         *
         * @param string $module_type
         * @param string $slug
         * @param bool   $is_theme
         * @param bool   $is_multisite
		 *
		 * @return FS_Storage
		 */
		static function instance( $module_type, $slug, $is_theme, $is_multisite ) {
            if ( ! isset( self::$_instance ) ) {
                self::$_instance = new FS_Storage( $module_type, $slug, $is_theme, $is_multisite );
            }

			return self::$_instance;
		}

		/**
         * @author Leo Fajardo (@leorw)
         *
         * @param string $module_type
		 * @param string $slug
         * @param bool   $is_theme
         * @param bool   $is_multisite
		 */
		private function __construct( $module_type, $slug, $is_theme, $is_multisite ) {
            $this->_storage = FS_Key_Value_Storage::instance( $module_type . '_data', $slug );

            $this->_is_theme          = $is_theme;
            $this->_is_multisite      = $is_multisite;
            $this->_is_network_active = false;

            if ( $is_multisite ) {
                $this->_network_storage = FS_Key_Value_Storage::instance( $module_type . '_data', $slug, true );
            }
		}

        /**
         * Tells this storage wrapper class that the context plugin is network active. This flag will affect how values
         * are retrieved/stored from/into the storage.
         *
         * @author Leo Fajardo (@leorw)
         */
		function set_network_active() {
            $this->_is_network_active = true;
        }

        /**
         * @author Leo Fajardo (@leorw)
         *
         * @param string $key
         * @param mixed  $value
         * @param bool   $flush
         */
        function store( $key, $value, $flush = true ) {
            if ( $this->is_multisite_storage( $key ) ) {
                $this->_network_storage->store( $key, $value, $flush );
            } else {
                $this->_storage->store( $key, $value, $flush );
            }
        }

        /**
         * @author Leo Fajardo (@leorw)
         *
         * @param bool     $store
         * @param string[] $exceptions Set of keys to keep and not clear.
         */
        function clear_all( $store = true, $exceptions = array() ) {
            $this->_storage->clear_all( $store, $exceptions );

            if ( $this->is_multisite ) {
                $this->_network_storage->clear_all( $store, $exceptions );
            }
        }

        /**
         * @author Leo Fajardo (@leorw)
         *
         * @param string $key
         * @param bool   $store
         */
        function remove( $key, $store = true ) {
            if ( $this->is_multisite_storage( $key ) ) {
                $this->_network_storage->remove( $key, $store );
            } else {
                $this->_storage->remove( $key, $store );
            }
        }

        /**
         * @author Leo Fajardo (@leorw)
         *
         * @param string $key
         * @param mixed  $default
         *
         * @return mixed
         */
        function get( $key, $default = false ) {
            return $this->is_multisite_storage( $key ) ?
                $this->_network_storage->get( $key, $default ) :
                $this->_storage->get( $key, $default );
        }

        /**
         * @author Leo Fajardo
         *
         * @param string $key
         *
         * @return bool
         */
        private function is_multisite_storage( $key ) {
            if ( ! $this->_is_multisite ) {
                return false;
            } else if ( is_bool( self::$_BINARY_MAP[ $key ] ) ) {
                return self::$_BINARY_MAP[ $key ];
            }

            /**
             * Example:
             *
             * 'key' => array( '11' => true )
             *
             * #1 digit - 1 if theme
             * #2 digit - 1 if module was network activated
             */
            $binary_key = ( (int) $this->_is_theme . (int) $this->_is_network_active );

            return ( true === self::$_BINARY_MAP[ $key ][ $binary_key ] );
        }

		# region Magic methods

        function __set( $k, $v ) {
            if ( $this->is_multisite_storage( $k ) ) {
                $this->_network_storage->{ $k } = $v;
            } else {
                $this->_storage->{ $k } = $v;
            }
        }

        function __isset( $k ) {
            return $this->is_multisite_storage( $k ) ?
                isset( $this->_network_storage->{ $k } ) :
                isset( $this->_storage->{ $k } );
        }

        function __unset( $k ) {
            if ( $this->is_multisite_storage( $k ) ) {
                unset( $this->_network_storage->{ $k } );
            } else {
                unset( $this->_storage->{ $k } );
            }
        }

        function __get( $k ) {
            return $this->is_multisite_storage( $k ) ?
                $this->_network_storage->{ $k } :
                $this->_storage->{ $k };
        }

        # endregion Magic methods
    }