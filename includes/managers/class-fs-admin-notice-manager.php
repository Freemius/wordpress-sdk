<?php
    /**
     * @package     Freemius
     * @copyright   Copyright (c) 2015, Freemius, Inc.
     * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
     * @since       1.0.7
     */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    class FS_Admin_Notice_Manager {
        /**
         * @since 1.2.2
         *
         * @var string
         */
        protected $_module_unique_affix;
        /**
         * @var string
         */
        protected $_id;
        /**
         * @var string
         */
        protected $_title;
        /**
         * @var array[string]array
         */
        private $_notices = array();
        /**
         * @var FS_Key_Value_Storage
         */
        private $_sticky_storage;
        /**
         * @var FS_Logger
         */
        protected $_logger;
        /**
         * @since 2.0.0
         * @var int The ID of the blog that is associated with the current site level admin notices.
         */
        private $_blog_id = 0;
        /**
         * @since 2.0.0
         * @var bool
         */
        private $_is_network_notices;

        /**
         * @var FS_Admin_Notice_Manager[]
         */
        private static $_instances = array();

        /**
         * @param string $id
         * @param string $title
         * @param string $module_unique_affix
         * @param bool   $network_level_or_blog_id Since 2.0.0
         *
         * @return \FS_Admin_Notice_Manager
         */
        static function instance(
            $id,
            $title = '',
            $module_unique_affix = '',
            $network_level_or_blog_id = false
        ) {
            $key = strtolower( $id );

            if ( is_multisite() ) {
                if ( true === $network_level_or_blog_id ) {
                    $key .= ':ms';
                } else if ( is_numeric( $network_level_or_blog_id ) && $network_level_or_blog_id > 0 ) {
                    $key .= ":{$network_level_or_blog_id}";
                } else {
                    $network_level_or_blog_id = get_current_blog_id();

                    $key .= ":{$network_level_or_blog_id}";
                }
            }

            if ( ! isset( self::$_instances[ $key ] ) ) {
                self::$_instances[ $key ] = new FS_Admin_Notice_Manager(
                    $id,
                    $title,
                    $module_unique_affix,
                    $network_level_or_blog_id
                );
            }

            return self::$_instances[ $key ];
        }

        protected function __construct(
            $id,
            $title = '',
            $module_unique_affix = '',
            $network_level_or_blog_id = false
        ) {
            $this->_id                  = $id;
            $this->_logger              = FS_Logger::get_logger( WP_FS__SLUG . '_' . $this->_id . '_data', WP_FS__DEBUG_SDK, WP_FS__ECHO_DEBUG_SDK );
            $this->_title               = ! empty( $title ) ? $title : '';
            $this->_module_unique_affix = $module_unique_affix;
            $this->_sticky_storage      = FS_Key_Value_Storage::instance( 'admin_notices', $this->_id, $network_level_or_blog_id );

            if ( is_multisite() ) {
                $this->_is_network_notices = ( true === $network_level_or_blog_id );

                if ( is_numeric( $network_level_or_blog_id ) ) {
                    $this->_blog_id = $network_level_or_blog_id;
                }
            } else {
                $this->_is_network_notices = false;
            }

            if ( ( $this->_is_network_notices && fs_is_network_admin() ) ||
                 ( ! $this->_is_network_notices && fs_is_blog_admin() )
            ) {
                if ( 0 < count( $this->_sticky_storage ) ) {
                    $ajax_action_suffix = str_replace( ':', '-', $this->_id );

                    // If there are sticky notices for the current slug, add a callback
                    // to the AJAX action that handles message dismiss.
                    add_action( "wp_ajax_fs_dismiss_notice_action_{$ajax_action_suffix}", array(
                        &$this,
                        'dismiss_notice_ajax_callback'
                    ) );

                    foreach ( $this->_sticky_storage as $msg ) {
                        // Add admin notice.
                        $this->add(
                            $msg['message'],
                            $msg['title'],
                            $msg['type'],
                            true,
                            $msg['id'],
                            false
                        );
                    }
                }
            }
        }

        /**
         * Remove sticky message by ID.
         *
         * @author Vova Feldman (@svovaf)
         * @since  1.0.7
         *
         */
        function dismiss_notice_ajax_callback() {
            $this->_sticky_storage->remove( $_POST['message_id'] );
            wp_die();
        }

        /**
         * Rendered sticky message dismiss JavaScript.
         *
         * @author Vova Feldman (@svovaf)
         * @since  1.0.7
         */
        static function _add_sticky_dismiss_javascript() {
            $params = array();
            fs_require_once_template( 'sticky-admin-notice-js.php', $params );
        }

        private static $_added_sticky_javascript = false;

        /**
         * Hook to the admin_footer to add sticky message dismiss JavaScript handler.
         *
         * @author Vova Feldman (@svovaf)
         * @since  1.0.7
         */
        private static function has_sticky_messages() {
            if ( ! self::$_added_sticky_javascript ) {
                add_action( 'admin_footer', array( 'FS_Admin_Notice_Manager', '_add_sticky_dismiss_javascript' ) );
            }
        }

        /**
         * Handle admin_notices by printing the admin messages stacked in the queue.
         *
         * @author Vova Feldman (@svovaf)
         * @since  1.0.4
         *
         */
        function _admin_notices_hook() {
            if ( function_exists( 'current_user_can' ) &&
                 ! current_user_can( 'manage_options' )
            ) {
                // Only show messages to admins.
                return;
            }

            foreach ( $this->_notices as $id => $msg ) {
                fs_require_template( 'admin-notice.php', $msg );

                if ( $msg['sticky'] ) {
                    self::has_sticky_messages();
                }
            }
        }

        /**
         * Enqueue common stylesheet to style admin notice.
         *
         * @author Vova Feldman (@svovaf)
         * @since  1.0.7
         */
        function _enqueue_styles() {
            fs_enqueue_local_style( 'fs_common', '/admin/common.css' );
        }

        /**
         * Add admin message to admin messages queue, and hook to admin_notices / all_admin_notices if not yet hooked.
         *
         * @author Vova Feldman (@svovaf)
         * @since  1.0.4
         *
         * @param string $message
         * @param string $title
         * @param string $type
         * @param bool   $is_sticky
         * @param string $id Message ID
         * @param bool   $store_if_sticky
         *
         * @uses   add_action()
         */
        function add( $message, $title = '', $type = 'success', $is_sticky = false, $id = '', $store_if_sticky = true ) {
            $notices_type = $this->get_notices_type();

            if ( empty( $this->_notices ) ) {
                add_action( $notices_type, array( &$this, "_admin_notices_hook" ) );
                add_action( 'admin_enqueue_scripts', array( &$this, '_enqueue_styles' ) );
            }

            if ( '' === $id ) {
                $id = md5( $title . ' ' . $message . ' ' . $type );
            }

            $message_object = array(
                'message'    => $message,
                'title'      => $title,
                'type'       => $type,
                'sticky'     => $is_sticky,
                'id'         => $id,
                'manager_id' => $this->_id,
                'plugin'     => $this->_title,
            );

            if ( $is_sticky && $store_if_sticky ) {
                $this->_sticky_storage->{$id} = $message_object;
            }

            $this->_notices[ $id ] = $message_object;
        }

        /**
         * @author Vova Feldman (@svovaf)
         * @since  1.0.7
         *
         * @param string|string[] $ids
         */
        function remove_sticky( $ids ) {
            if ( ! is_array( $ids ) ) {
                $ids = array( $ids );
            }

            foreach ( $ids as $id ) {
                // Remove from sticky storage.
                $this->_sticky_storage->remove( $id );

                if ( isset( $this->_notices[ $id ] ) ) {
                    unset( $this->_notices[ $id ] );
                }
            }
        }

        /**
         * Check if sticky message exists by id.
         *
         * @author Vova Feldman (@svovaf)
         * @since  1.0.9
         *
         * @param $id
         *
         * @return bool
         */
        function has_sticky( $id ) {
            return isset( $this->_sticky_storage[ $id ] );
        }

        /**
         * Adds sticky admin notification.
         *
         * @author Vova Feldman (@svovaf)
         * @since  1.0.7
         *
         * @param string $message
         * @param string $id Message ID
         * @param string $title
         * @param string $type
         */
        function add_sticky( $message, $id, $title = '', $type = 'success' ) {
            if ( ! empty( $this->_module_unique_affix ) ) {
                $message = fs_apply_filter( $this->_module_unique_affix, "sticky_message_{$id}", $message );
                $title   = fs_apply_filter( $this->_module_unique_affix, "sticky_title_{$id}", $title );
            }

            $this->add( $message, $title, $type, true, $id );
        }

        /**
         * Clear all sticky messages.
         *
         * @author Vova Feldman (@svovaf)
         * @since  1.0.8
         */
        function clear_all_sticky() {
            $this->_sticky_storage->clear_all();
        }

        #--------------------------------------------------------------------------------
        #region Helper Method
        #--------------------------------------------------------------------------------

        /**
         * @author Vova Feldman (@svovaf)
         * @since  2.0.0
         *
         * @return string
         */
        private function get_notices_type() {
            return $this->_is_network_notices ?
                'network_admin_notices' :
                'admin_notices';
        }

        #endregion
    }