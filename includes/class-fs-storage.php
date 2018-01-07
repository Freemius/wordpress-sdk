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
         * @var string
         */
        private $_module_type;

        /**
         * @var int The ID of the blog that is associated with the current site level options.
         */
        private $_blog_id = 0;

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
            'uninstall_reason'           => array( '11' => true, '01' => true ),
            'was_plugin_loaded'          => true,
            'network_user_id'            => true,
        );

		/**
         * @author Leo Fajardo (@leorw)
         *
         * @param string $module_type
         * @param string $slug
		 *
		 * @return FS_Storage
		 */
        static function instance( $module_type, $slug ) {
            if ( ! isset( self::$_instance ) ) {
                self::$_instance = new FS_Storage( $module_type, $slug );
            }

			return self::$_instance;
		}

		/**
         * @author Leo Fajardo (@leorw)
         *
         * @param string $module_type
		 * @param string $slug
		 */
        private function __construct( $module_type, $slug ) {
            $this->_module_type       = $module_type;
            $this->_is_multisite      = is_multisite();
            $this->_is_network_active = false;

            if ( $this->_is_multisite ) {
                $this->_blog_id         = get_current_blog_id();
                $this->_network_storage = FS_Key_Value_Storage::instance( $module_type . '_data', $slug, true );
            }

            $this->_storage = FS_Key_Value_Storage::instance( $module_type . '_data', $slug, $this->_blog_id );
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
         * Switch the context of the site level storage manager.
         *
         * @author Vova Feldman (@svovaf)
         * @since  1.2.4
         *
         * @param int $blog_id
         */
        function set_site_blog_context( $blog_id ) {
            $this->_blog_id = $blog_id;

            $this->_storage = FS_Key_Value_Storage::instance(
                $this->_module_type . '_data',
                $this->_storage->get_secondary_id(),
                $this->_blog_id
            );
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

            if ( $this->_is_multisite ) {
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
            if ( ! $this->_is_multisite || ! isset( self::$_BINARY_MAP[ $key ] ) ) {
                return false;
            } else if ( is_bool( self::$_BINARY_MAP[ $key ] ) ) {
                return self::$_BINARY_MAP[ $key ];
            }

            $is_theme = ( WP_FS__MODULE_TYPE_THEME === $this->_module_type );

            /**
             * Example:
             *
             * 'key' => array( '11' => true )
             *
             * #1 digit - 1 if theme
             * #2 digit - 1 if module was network activated
             */
            $binary_key = ( (int) $is_theme . (int) $this->_is_network_active );

            return ( isset( self::$_BINARY_MAP[ $key ][ $binary_key ] ) && true === self::$_BINARY_MAP[ $key ][ $binary_key ] );
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