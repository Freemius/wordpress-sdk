<?php
    /**
     * @package     Freemius
     * @copyright   Copyright (c) 2015, Freemius, Inc.
     * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
     * @since       2.1.0
     */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    class FS_GDPR_Manager {
        /**
         * @var FS_Option_Manager
         */
        private $_storage;
        /**
         * @var array
         */
        private $_data;
        /**
         * @var int
         */
        private $_option_name;

        #--------------------------------------------------------------------------------
        #region Singleton
        #--------------------------------------------------------------------------------

        /**
         * @var FS_GDPR_Manager
         */
        private static $_instance;

        /**
         * @return FS_GDPR_Manager
         */
        public static function instance() {
            if ( ! isset( self::$_instance ) ) {
                self::$_instance = new self();
            }

            return self::$_instance;
        }

        #endregion

        private function __construct() {
            $wp_user_id = Freemius::get_current_wp_user_id();

            $this->_storage     = FS_Option_Manager::get_manager( WP_FS__GDPR_OPTION_NAME, true, true );
            $this->_option_name = "u{$wp_user_id}";
            $this->_data        = $this->_storage->get_option( $this->_option_name, array() );

            if ( ! is_array( $this->_data ) ) {
                $this->_data = array();
            }
        }

        /**
         * @author Leo Fajardo (@leorw)
         * @since  2.1.0
         *
         * @return bool|null
         */
        public function is_required() {
            return isset( $this->_data['required'] ) ?
                $this->_data['required'] :
                null;
        }

        /**
         * @author Leo Fajardo (@leorw)
         * @since  2.1.0
         *
         * @param bool $is_required
         */
        public function store_is_required( $is_required ) {
            $this->_data['required'] = $is_required;

            $this->_storage->set_option( $this->_option_name, $this->_data, true );
        }
    }