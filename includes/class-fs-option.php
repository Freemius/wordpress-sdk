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
	 * Class FS_Option
	 *
	 * A wrapper class for handling network level and single site level options.
	 */
	class FS_Option {
		/**
		 * @var FS_Option
		 */
		private static $_instance;

        /**
         * @var FS_Option_Manager Site level options.
         */
		private $_options;

        /**
         * @var FS_Option_Manager Network level options.
         */
		private $_network_options;

        /**
         * @var bool
         */
		private $_is_multisite;

		/**
         * @since 1.2.4
         * @var string[] Lazy collection of params on the site level.
         */
        private static $_SITE_LEVEL_PARAMS;

        /**
         * @author Leo Fajardo (@leorw)
         *
         * @param string $id
         * @param bool   $load
         * @param bool   $is_multisite
		 *
		 * @return FS_Option
		 */
		static function instance( $id, $load = false, $is_multisite = false ) {
            if ( ! isset( self::$_instance ) ) {
                self::$_instance = new FS_Option( $id, $load, $is_multisite );
            }

			return self::$_instance;
		}

		/**
         * @author Leo Fajardo (@leorw)
         *
         * @param string $id
         * @param bool   $load
         * @param bool   $is_multisite
		 */
		private function __construct( $id, $load = false, $is_multisite = false ) {
            $this->_options = FS_Option_Manager::get_manager( $id, $load );

            $this->_is_multisite = $is_multisite;

            if ( $is_multisite ) {
                $this->_network_options = FS_Option_Manager::get_manager( $id, $load, true );
            }
		}

        /**
         * @author Leo Fajardo (@leorw)
         *
         * @param string $option
         * @param mixed  $default
         * @param bool   $network_level
         *
         * @return mixed
         */
        function get_option( $option, $default = null, $network_level = true ) {
            return ( $this->_is_multisite && $network_level ) ?
                $this->_network_options->get_option( $option, $default ) :
                $this->_options->get_option( $option, $default );
        }

        /**
         * @author Leo Fajardo (@leorw)
         *
         * @param string $option
         * @param mixed  $value
         * @param bool   $flush
         * @param bool   $network_level
         */
        function set_option( $option, $value, $flush = false, $network_level = true ) {
            ( $this->_is_multisite && $network_level ) ?
                $this->_network_options->set_option( $option, $value, $flush ) :
                $this->_options->set_option( $option, $value, $flush );
        }

        /**
         * @author Leo Fajardo (@leorw)
         *
         * @param bool $flush
         * @param bool $network_level
         */
        function load( $flush = false, $network_level = true ) {
            if ( $this->_is_multisite && $network_level ) {
                $this->_network_options->load( $flush );
            } else {
                $this->_options->load( $flush );
            }
        }

        /**
         * @author Leo Fajardo (@leorw)
         *
         * @param bool $network_level
         */
        function store( $network_level = true ) {
            if ( $this->_is_multisite && $network_level ) {
                $this->_network_options->store();
            } else {
                $this->_options->store();
            }
        }

        #--------------------------------------------------------------------------------
        #region Helper Methods
        #--------------------------------------------------------------------------------

        /**
         * @author Vova Feldman (@svovaf)
         * @since  1.2.4
         *
         * @param string $option
         *
         * @return bool
         */
        private function is_site_option( $option ) {
            if ( WP_FS__ACCOUNTS_OPTION_NAME != $this->_id ) {
                return false;
            }

            if ( ! isset( self::$_SITE_LEVEL_PARAMS ) ) {
                self::$_SITE_LEVEL_PARAMS = array(
                    'sites',
                    'theme_sites',
                    'unique_id',
                );
            }

            return isset( self::$_SITE_LEVEL_PARAMS[ $option ] );
        }


        #endregion
    }